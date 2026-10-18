<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Exceptions\BookingException;
use App\Models\Booth;
use App\Models\Event;
use App\Models\PriceRule;
use App\Models\Registration;
use App\Services\BookingService;
use App\Services\ExpiryService;
use App\Services\PaymentService;
use App\Services\PricingService;
use Illuminate\Http\UploadedFile;
use Tests\HsnTestCase;

class BookingRulesTest extends HsnTestCase
{
    public function test_price_combinations_come_from_the_database(): void
    {
        $event = Event::current();
        $small = Booth::query()->where('size', '2x2')->firstOrFail();
        $large = Booth::query()->where('size', '3x3')->firstOrFail();
        $pricing = app(PricingService::class);

        $this->assertSame(100000, $pricing->forBooth($small, false)['line_total']);
        $this->assertSame(200000, $pricing->forBooth($small, true)['line_total']);
        $this->assertSame(150000, $pricing->forBooth($large, false)['line_total']);
        $this->assertSame(300000, $pricing->forBooth($large, true)['line_total']);

        $quote = app(BookingService::class)->quote($event, [
            ['booth_id' => $small->id, 'with_tent' => true],
            ['booth_id' => $large->id, 'with_tent' => false],
        ]);

        $this->assertSame(350000, $quote['grand_total']);
    }

    public function test_three_slots_is_the_maximum_for_one_whatsapp_number(): void
    {
        $event = Event::current();
        $booths = Booth::query()->where('type', 'umkm')->where('size', '2x2')->orderBy('code')->take(6)->get();
        $service = app(BookingService::class);

        $first = $service->create($event, $this->applicant(), [
            ['booth_id' => $booths[0]->id, 'with_tent' => false],
            ['booth_id' => $booths[1]->id, 'with_tent' => true],
        ]);
        $this->assertSame(300000, $first->grand_total);

        $service->create($event, $this->applicant(), [
            ['booth_id' => $booths[2]->id, 'with_tent' => false],
        ]);

        try {
            $service->create($event, $this->applicant(), [
                ['booth_id' => $booths[3]->id, 'with_tent' => false],
            ]);
            $this->fail('Slot keempat seharusnya ditolak.');
        } catch (BookingException $exception) {
            $this->assertStringContainsString('slot', strtolower($exception->getMessage()));
        }

        $other = $service->create($event, $this->applicant('081298765432', 'Keripik Magelang'), [
            ['booth_id' => $booths[3]->id, 'with_tent' => false],
            ['booth_id' => $booths[4]->id, 'with_tent' => false],
            ['booth_id' => $booths[5]->id, 'with_tent' => false],
        ]);

        $this->assertSame(3, $other->items()->count());
    }

    public function test_one_order_cannot_take_more_than_three_slots(): void
    {
        $booths = Booth::query()->where('type', 'umkm')->orderBy('id')->take(4)->get();

        $this->expectException(BookingException::class);

        app(BookingService::class)->create(Event::current(), $this->applicant(), $booths->map(fn ($booth) => [
            'booth_id' => $booth->id,
            'with_tent' => false,
        ])->all());
    }

    public function test_the_same_booth_cannot_be_booked_twice(): void
    {
        $event = Event::current();
        $booth = Booth::query()->where('type', 'umkm')->firstOrFail();
        $service = app(BookingService::class);

        $service->create($event, $this->applicant(), [
            ['booth_id' => $booth->id, 'with_tent' => false],
        ]);

        try {
            $service->create($event, $this->applicant('081200000001', 'Usaha Kedua'), [
                ['booth_id' => $booth->id, 'with_tent' => true],
            ]);
            $this->fail('Booking ganda seharusnya ditolak.');
        } catch (BookingException $exception) {
            $this->assertStringContainsString($booth->code, $exception->getMessage());
        }

        $this->assertSame(1, $booth->fresh()->reservation()->count());
        $this->assertSame(1, Registration::query()->count());
    }

    public function test_expired_hold_releases_the_booth_but_uploaded_proof_does_not(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $event = Event::current();
        $booths = Booth::query()->where('type', 'umkm')->orderBy('id')->take(2)->get();
        $service = app(BookingService::class);

        $waiting = $service->create($event, $this->applicant(), [
            ['booth_id' => $booths[0]->id, 'with_tent' => false],
        ]);
        $reviewing = $service->create($event, $this->applicant('081211111111', 'Jenang Mungkid'), [
            ['booth_id' => $booths[1]->id, 'with_tent' => false],
        ]);

        app(PaymentService::class)->submitProof($reviewing, UploadedFile::fake()->image('bukti.jpg'));

        $this->travel(1)->hours();
        $this->assertSame(0, app(ExpiryService::class)->releaseExpired());

        $this->travel(1)->hours();
        $this->assertSame(1, app(ExpiryService::class)->releaseExpired());

        $this->assertSame(RegistrationStatus::Expired, $waiting->fresh()->status);
        $this->assertNull($booths[0]->fresh()->reservation);
        $this->assertSame(RegistrationStatus::AwaitingVerification, $reviewing->fresh()->status);
        $this->assertNotNull($booths[1]->fresh()->reservation);

        $replacement = $service->create($event, $this->applicant('081222222222', 'Usaha Pengganti'), [
            ['booth_id' => $booths[0]->id, 'with_tent' => false],
        ]);
        $this->assertSame(RegistrationStatus::AwaitingPayment, $replacement->status);
    }

    public function test_later_price_changes_do_not_rewrite_the_snapshot(): void
    {
        $event = Event::current();
        $booth = Booth::query()->where('size', '2x2')->firstOrFail();
        $registration = app(BookingService::class)->create($event, $this->applicant(), [
            ['booth_id' => $booth->id, 'with_tent' => true],
        ]);

        PriceRule::query()->where('size', '2x2')->update(['place_price' => 1, 'tent_price' => 1]);

        $this->assertSame(200000, $registration->fresh()->grand_total);
        $this->assertSame(PaymentStatus::Pending, $registration->payment->status);
        $this->assertSame(200000, $registration->payment->amount);
    }
}
