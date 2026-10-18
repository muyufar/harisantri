<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\SponsorshipStatus;
use App\Models\Booth;
use App\Models\Registration;
use App\Models\SponsorshipCategory;
use App\Models\SponsorshipRequest;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\HsnTestCase;

class RegistrationFlowTest extends HsnTestCase
{
    public function test_public_registration_ignores_a_tampered_total_and_confirms_after_verification(): void
    {
        Storage::fake('local');

        $this->post(route('register.applicant'), $this->applicant() + [
            'logo' => UploadedFile::fake()->image('logo.jpg'),
        ])->assertRedirect(route('register.booths'));

        $this->post(route('register.booths.store'), [
            'area_numbers' => [2],
            'with_tent' => [2 => '1'],
            'grand_total' => 1,
        ])->assertRedirect(route('register.confirm'));

        $this->post(route('register.store'), [
            'terms' => '1',
            'grand_total' => 1,
        ])->assertRedirect();

        $registration = Registration::query()->firstOrFail();
        $this->assertSame(200000, $registration->grand_total);
        $this->assertTrue($registration->items()->firstOrFail()->with_tent);
        $this->assertSame(RegistrationStatus::AwaitingPayment, $registration->status);

        $this->post(URL::temporarySignedRoute('register.proof', now()->addHour(), $registration), [
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect();

        $this->assertSame(RegistrationStatus::AwaitingVerification, $registration->fresh()->status);

        $this->get(URL::signedRoute('register.pdf', [$registration, 'konfirmasi']))->assertForbidden();

        $finance = User::query()->where('email', 'keuangan@pcnu-magelang.test')->firstOrFail();
        $field = User::query()->where('email', 'lapangan@pcnu-magelang.test')->firstOrFail();

        $this->actingAs($field)->post(route('admin.registrations.approve', $registration))->assertForbidden();
        $this->actingAs($finance)->post(route('admin.registrations.approve', $registration))->assertRedirect();

        $registration->refresh();
        $this->assertSame(RegistrationStatus::Confirmed, $registration->status);
        $this->get(URL::signedRoute('register.pdf', [$registration, 'konfirmasi']))->assertOk();

        $item = $registration->items()->firstOrFail();
        $this->actingAs($field)->post(route('admin.checkin.store'), [
            'token' => app(QrService::class)->token($item),
        ])->assertOk()->assertSee('Check-in tercatat');
    }

    public function test_registration_without_tent_charges_only_the_place_price(): void
    {
        $this->post(route('register.applicant'), $this->applicant('081298765432', 'Tanpa Tenda'))
            ->assertRedirect(route('register.booths'));

        $this->post(route('register.booths.store'), [
            'area_numbers' => [2],
            'with_tent' => [2 => '0'],
        ])->assertRedirect(route('register.confirm'));

        $this->post(route('register.store'), ['terms' => '1'])->assertRedirect();

        $registration = Registration::query()->where('whatsapp', 'like', '%81298765432')->firstOrFail();
        $this->assertSame(100000, $registration->grand_total);
        $this->assertFalse($registration->items()->firstOrFail()->with_tent);
    }

    public function test_status_lookup_hides_details_until_whatsapp_matches(): void
    {
        $booth = Booth::query()->where('type', 'umkm')->firstOrFail();
        $registration = app(\App\Services\BookingService::class)->create(Event(), $this->applicant(), [
            ['booth_id' => $booth->id, 'with_tent' => false],
        ]);

        $this->from(route('status.create'))
            ->post(route('status.store'), [
                'code' => $registration->code,
                'whatsapp' => '089876543210',
            ])
            ->assertRedirect(route('status.create'))
            ->assertSessionHasErrors('code');

        $this->post(route('status.store'), [
            'code' => $registration->code,
            'whatsapp' => '081234567890',
        ])->assertRedirect();
    }

    public function test_sponsorship_application_has_no_automatic_price(): void
    {
        $category = SponsorshipCategory::query()->firstOrFail();

        $this->post(route('sponsorship.store'), [
            'sponsorship_category_id' => $category->id,
            'company_name' => 'Koperasi Santri Magelang',
            'pic_name' => 'Ahmad Fauzi',
            'whatsapp' => '081234000111',
            'business_type' => 'Koperasi',
            'area_requirement' => 'Sekitar 6 meter',
            'facility_needs' => 'Listrik dan meja',
            'notes' => 'Siap dihubungi siang hari.',
            'agreed_price' => 999999,
        ])->assertRedirect();

        $entry = SponsorshipRequest::query()->firstOrFail();
        $this->assertNull($entry->agreed_price);
        $this->assertSame(SponsorshipStatus::NewRequest, $entry->status);
        $this->assertNull($entry->booth_id);
    }

    public function test_admin_can_cancel_a_registration_and_release_the_booth(): void
    {
        $booth = Booth::query()->where('type', 'umkm')->firstOrFail();
        $registration = app(\App\Services\BookingService::class)->create(Event(), $this->applicant(), [
            ['booth_id' => $booth->id, 'with_tent' => false],
        ]);
        $admin = User::query()->where('email', 'admin@pcnu-magelang.test')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.registrations.show', $registration))
            ->post(route('admin.registrations.cancel', $registration), [
                'note' => 'Pendaftar mengundurkan diri',
                'refund' => '1',
            ])
            ->assertRedirect(route('admin.registrations.show', $registration))
            ->assertSessionHas('status');

        $registration->refresh();
        $this->assertSame(RegistrationStatus::Cancelled, $registration->status);
        $this->assertSame('Pendaftar mengundurkan diri', $registration->cancellation_note);
        $this->assertNull($booth->fresh()->reservation);
    }

    public function test_payment_page_shows_qris_instead_of_bank_transfer(): void
    {
        $booth = Booth::query()->where('type', 'umkm')->firstOrFail();
        $registration = app(\App\Services\BookingService::class)->create(Event(), $this->applicant(), [
            ['booth_id' => $booth->id, 'with_tent' => false],
        ]);

        $this->assertSame('qris', $registration->payment->method);

        $this->get(URL::temporarySignedRoute('register.payment', now()->addHour(), $registration))
            ->assertOk()
            ->assertSee('Bayar dengan QRIS')
            ->assertSee('PCNU KAB MAGELANG')
            ->assertSee('ID1026618538122')
            ->assertSee('qris-pcnu-magelang.jpg')
            ->assertDontSee('Transfer manual')
            ->assertDontSee('Nomor rekening');
    }
}

function Event(): \App\Models\Event
{
    return \App\Models\Event::current();
}
