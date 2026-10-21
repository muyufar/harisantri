<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
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

        $registration->refresh();
        $this->assertSame(RegistrationStatus::AwaitingVerification, $registration->status);
        $this->assertNotNull($registration->payment->submitted_at);

        $admin = User::query()->where('email', 'admin@pcnu-magelang.test')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('admin.registrations.index'))
            ->assertOk()
            ->assertSee('Bukti diunggah', false)
            ->assertSee($registration->payment->submitted_at->timezone('Asia/Jakarta')->format('d/m/Y H.i'), false);

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

    public function test_two_slots_can_be_taken_in_the_same_numbered_block(): void
    {
        $this->post(route('register.applicant'), $this->applicant('081255500001', 'Dua Slot Satu Kotak'))
            ->assertRedirect(route('register.booths'));

        $this->post(route('register.booths.store'), [
            'area_numbers' => [2],
            'quantities' => [2 => 2],
            'with_tent' => [2 => '0'],
        ])->assertRedirect(route('register.confirm'));

        $this->post(route('register.store'), ['terms' => '1'])->assertRedirect();

        $registration = Registration::query()->where('whatsapp', 'like', '%81255500001')->firstOrFail();
        $items = $registration->items()->with('booth.zone')->get();
        $this->assertCount(2, $items);
        $this->assertEquals(['2', '2'], $items->map(fn ($item) => (string) $item->booth->zone->code)->all());
        $this->assertCount(2, $items->pluck('booth_id')->unique());
        $this->assertSame(200000, $registration->grand_total);

        $admin = User::query()->where('email', 'admin@pcnu-magelang.test')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('admin.registrations.index', ['kotak' => 2]))
            ->assertOk()
            ->assertSee('Dua Slot Satu Kotak')
            ->assertSee('value="2" selected', false);
        $this->actingAs($admin)
            ->get(route('admin.registrations.index', ['kotak' => 1]))
            ->assertOk()
            ->assertDontSee('Dua Slot Satu Kotak');
        $this->actingAs($admin)
            ->get(route('admin.registrations.index', ['tenda' => '0']))
            ->assertOk()
            ->assertSee('Dua Slot Satu Kotak')
            ->assertSee('value="0" selected', false);
        $this->actingAs($admin)
            ->get(route('admin.registrations.index', ['tenda' => '1']))
            ->assertOk()
            ->assertDontSee('Dua Slot Satu Kotak');
        $this->actingAs($admin)
            ->get(route('admin.registrations.index', ['multi' => '1']))
            ->assertOk()
            ->assertSee('Dua Slot Satu Kotak')
            ->assertSee('WA 2 slot', false)
            ->assertSee('Lebih dari 1 lapak', false);
    }

    public function test_admin_can_move_a_booth_to_another_box_of_the_same_size(): void
    {
        $booth = Booth::query()->whereHas('zone', fn ($query) => $query->where('code', '2'))->firstOrFail();
        $registration = app(\App\Services\BookingService::class)->create(Event(), $this->applicant('081266600002', 'Pindah Kotak'), [
            ['booth_id' => $booth->id, 'with_tent' => false],
        ]);
        $item = $registration->items()->firstOrFail();
        $admin = User::query()->where('email', 'admin@pcnu-magelang.test')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.registrations.move', $registration), [
                'item_id' => $item->id,
                'area' => 3,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $item->refresh()->load('booth.zone');
        $this->assertSame('3', (string) $item->booth->zone->code);
        $this->assertSame('Kotak 3', $item->zone_name);
        $this->assertSame(100000, $registration->fresh()->grand_total);
        $this->assertNotNull($item->booth->reservation);

        $this->actingAs($admin)
            ->get(route('admin.registrations.show', $registration))
            ->assertOk()
            ->assertSee('Kotak 1')
            ->assertSee('Kotak 5');

        $this->actingAs($admin)
            ->post(route('admin.registrations.move', $registration), [
                'item_id' => $item->id,
                'area' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $item->refresh()->load('booth.zone');
        $this->assertSame('1', (string) $item->booth->zone->code);
        $this->assertSame('3x3', $item->size);
        $this->assertSame(150000, $item->line_total);
        $this->assertSame(150000, $registration->fresh()->grand_total);
        $this->assertSame(150000, $registration->payment()->first()->amount);
    }

    public function test_admin_registration_list_accepts_a_page_size(): void
    {
        $admin = User::query()->where('email', 'admin@pcnu-magelang.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.registrations.index', ['per_page' => 50]))
            ->assertOk()
            ->assertSee('value="50" selected', false);

        $this->actingAs($admin)
            ->get(route('admin.registrations.index', ['per_page' => 15]))
            ->assertOk()
            ->assertSee('value="10" selected', false);
    }

    public function test_admin_can_search_registrations_by_name_and_stall_box(): void
    {
        $booth = Booth::query()->whereHas('zone', fn ($query) => $query->where('code', '2'))->firstOrFail();
        $other = Booth::query()->whereHas('zone', fn ($query) => $query->where('code', '3'))->firstOrFail();
        app(\App\Services\BookingService::class)->create(Event(), $this->applicant('081277700001', 'Sulastri Snacks'), [
            ['booth_id' => $booth->id, 'with_tent' => false],
        ]);
        app(\App\Services\BookingService::class)->create(Event(), $this->applicant('081277700002', 'Warung Lain'), [
            ['booth_id' => $other->id, 'with_tent' => false],
        ]);
        $admin = User::query()->where('email', 'admin@pcnu-magelang.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.registrations.index', ['q' => 'sulastri']))
            ->assertOk()
            ->assertSee('Sulastri Snacks')
            ->assertDontSee('Warung Lain');

        $this->actingAs($admin)
            ->get(route('admin.registrations.index', ['q' => 'Kotak 2']))
            ->assertOk()
            ->assertSee('Sulastri Snacks')
            ->assertDontSee('Warung Lain');

        $this->actingAs($admin)
            ->get(route('admin.registrations.index', ['q' => '2-']))
            ->assertOk()
            ->assertSee('Sulastri Snacks');
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

    public function test_admin_can_restore_an_expired_registration_on_the_same_booth(): void
    {
        $booth = Booth::query()->whereHas('zone', fn ($query) => $query->where('code', '2'))->firstOrFail();
        $registration = app(\App\Services\BookingService::class)->create(Event(), $this->applicant('081288800001', 'Aktif Lagi'), [
            ['booth_id' => $booth->id, 'with_tent' => true],
        ]);
        $total = $registration->grand_total;

        $this->travel(3)->hours();
        app(\App\Services\ExpiryService::class)->releaseExpired();
        $registration->refresh();
        $this->assertSame(RegistrationStatus::Expired, $registration->status);
        $this->assertNull($booth->fresh()->reservation);

        $admin = User::query()->where('email', 'admin@pcnu-magelang.test')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('admin.registrations.show', $registration))
            ->assertOk()
            ->assertSee('Aktifkan kembali')
            ->assertSee('Tetap di kotak semula')
            ->assertSee('Kotak 4')
            ->assertSee('Kotak 1');

        $this->actingAs($admin)
            ->post(route('admin.registrations.restore', $registration))
            ->assertRedirect()
            ->assertSessionHas('status');

        $registration->refresh()->load('items.booth.zone', 'payment');
        $item = $registration->items->first();
        $this->assertSame(RegistrationStatus::AwaitingPayment, $registration->status);
        $this->assertTrue($registration->hold_expires_at->isFuture());
        $this->assertSame($booth->id, $item->booth_id);
        $this->assertSame($total, $registration->grand_total);
        $this->assertTrue($item->with_tent);
        $this->assertSame(PaymentStatus::Pending, $registration->payment->status);
        $this->assertNotNull($item->booth->reservation);
    }

    public function test_admin_can_restore_an_expired_registration_into_another_box(): void
    {
        $booths = Booth::query()->whereHas('zone', fn ($query) => $query->where('code', '3'))->orderBy('code')->limit(2)->get();
        $registration = app(\App\Services\BookingService::class)->create(Event(), $this->applicant('081288800009', 'Pindah Saat Aktifkan'), [
            ['booth_id' => $booths[0]->id, 'with_tent' => true],
            ['booth_id' => $booths[1]->id, 'with_tent' => true],
        ]);
        $total = $registration->grand_total;

        $this->travel(3)->hours();
        app(\App\Services\ExpiryService::class)->releaseExpired();

        $admin = User::query()->where('email', 'admin@pcnu-magelang.test')->firstOrFail();
        $this->actingAs($admin)
            ->post(route('admin.registrations.restore', $registration), ['area' => 4])
            ->assertRedirect()
            ->assertSessionHas('status');

        $items = $registration->fresh()->items()->with('booth.zone')->get();
        $this->assertCount(2, $items);
        $this->assertEquals(['4', '4'], $items->map(fn ($item) => (string) $item->booth->zone->code)->all());
        $this->assertCount(2, $items->pluck('booth_id')->unique());
        $this->assertTrue($items->every(fn ($item) => $item->with_tent));
        $this->assertSame($total, $registration->fresh()->grand_total);
    }

    public function test_restoring_moves_to_another_slot_in_the_same_box_when_the_old_one_is_taken(): void
    {
        $booths = Booth::query()->whereHas('zone', fn ($query) => $query->where('code', '2'))->orderBy('code')->limit(2)->get();
        $original = $booths[0];
        $registration = app(\App\Services\BookingService::class)->create(Event(), $this->applicant('081288800002', 'Pindah Saat Aktif'), [
            ['booth_id' => $original->id, 'with_tent' => false],
        ]);

        $this->travel(3)->hours();
        app(\App\Services\ExpiryService::class)->releaseExpired();

        $taken = app(\App\Services\BookingService::class)->create(Event(), $this->applicant('081288800003', 'Pengganti Slot'), [
            ['booth_id' => $original->id, 'with_tent' => false],
        ]);

        $admin = User::query()->where('email', 'admin@pcnu-magelang.test')->firstOrFail();
        $this->actingAs($admin)
            ->post(route('admin.registrations.restore', $registration))
            ->assertRedirect()
            ->assertSessionHas('status');

        $item = $registration->fresh()->items()->with('booth.zone')->firstOrFail();
        $this->assertNotSame($original->id, $item->booth_id);
        $this->assertSame('2', (string) $item->booth->zone->code);
        $this->assertSame(100000, $registration->fresh()->grand_total);
        $this->assertSame($original->id, $taken->fresh()->items()->first()->booth_id);
    }

    public function test_restore_is_rejected_when_the_whatsapp_quota_is_full(): void
    {
        $booths = Booth::query()->whereHas('zone', fn ($query) => $query->where('code', '2'))->orderBy('code')->limit(4)->get();
        $expired = app(\App\Services\BookingService::class)->create(Event(), $this->applicant('081288800004', 'Kuota Penuh'), [
            ['booth_id' => $booths[0]->id, 'with_tent' => false],
        ]);

        $this->travel(3)->hours();
        app(\App\Services\ExpiryService::class)->releaseExpired();

        app(\App\Services\BookingService::class)->create(Event(), $this->applicant('081288800004', 'Kuota Penuh'), [
            ['booth_id' => $booths[1]->id, 'with_tent' => false],
            ['booth_id' => $booths[2]->id, 'with_tent' => false],
            ['booth_id' => $booths[3]->id, 'with_tent' => false],
        ]);

        $admin = User::query()->where('email', 'admin@pcnu-magelang.test')->firstOrFail();
        $this->actingAs($admin)
            ->from(route('admin.registrations.show', $expired))
            ->post(route('admin.registrations.restore', $expired))
            ->assertRedirect(route('admin.registrations.show', $expired))
            ->assertSessionHasErrors('status');

        $this->assertSame(RegistrationStatus::Expired, $expired->fresh()->status);
    }

    public function test_admin_dashboard_groups_the_summary(): void
    {
        $admin = User::query()->where('email', 'admin@pcnu-magelang.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Lapak UMKM')
            ->assertSee('Pembayaran')
            ->assertSee('Pemasukan diterima')
            ->assertSee('tenda panitia')
            ->assertSee('Kotak 1')
            ->assertSee('Sisa');
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

    public function test_admin_can_temporarily_pause_and_resume_registration(): void
    {
        $admin = User::query()->where('email', 'admin@pcnu-magelang.test')->firstOrFail();
        $event = Event();

        $this->assertTrue($event->registrationIsOpen());

        $this->actingAs($admin)
            ->post(route('admin.settings.registration'), [
                'pause' => '1',
                'message' => 'Slot sedang ditinjau panitia.',
            ])
            ->assertRedirect();

        $event->unsetRelation('settings');
        $this->assertFalse($event->fresh()->registrationIsOpen());

        $this->get(route('register.create'))
            ->assertOk()
            ->assertSee('Slot sedang ditinjau panitia.')
            ->assertDontSee('Nama lengkap penanggung jawab');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Slot sedang ditinjau panitia.')
            ->assertDontSee('Daftar Lapak UMKM');

        $this->actingAs($admin)
            ->post(route('admin.settings.registration'), ['pause' => '0'])
            ->assertRedirect();

        $event->unsetRelation('settings');
        $this->assertTrue($event->fresh()->registrationIsOpen());
    }
}

function Event(): \App\Models\Event
{
    return \App\Models\Event::current();
}
