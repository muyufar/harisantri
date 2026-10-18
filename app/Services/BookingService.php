<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Exceptions\BookingException;
use App\Models\Applicant;
use App\Models\Booth;
use App\Models\BoothReservation;
use App\Models\Event;
use App\Models\Payment;
use App\Models\PhoneLock;
use App\Models\Registration;
use App\Models\RegistrationItem;
use App\Support\Phone;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(
        private PricingService $pricing,
        private CodeGenerator $codes,
        private AuditLogger $audit,
    ) {}

    public function activeSlotCount(Event $event, string $whatsapp): int
    {
        $phone = Phone::normalize($whatsapp);

        return RegistrationItem::query()
            ->whereHas('registration', function ($query) use ($event, $phone) {
                $query->where('event_id', $event->id)
                    ->where('whatsapp', $phone)
                    ->where('type', 'umkm')
                    ->where(function ($query) {
                        $query->whereIn('status', [
                            RegistrationStatus::Confirmed->value,
                            RegistrationStatus::AwaitingVerification->value,
                        ])->orWhere(function ($query) {
                            $query->whereIn('status', [
                                RegistrationStatus::AwaitingPayment->value,
                                RegistrationStatus::PaymentRejected->value,
                            ])->where('hold_expires_at', '>', now());
                        });
                    });
            })
            ->count();
    }

    /**
     * @param  array<int, array{booth_id: int, with_tent: bool}>  $selections
     * @return array{lines: array<int, array<string, mixed>>, subtotal: int, tent_total: int, grand_total: int}
     */
    public function quote(Event $event, array $selections): array
    {
        $lines = [];

        foreach ($this->normalizeSelections($selections) as $selection) {
            $booth = Booth::query()->with('zone')->find($selection['booth_id']);

            if (! $booth || $booth->event_id !== $event->id || $booth->type !== 'umkm') {
                throw new BookingException('Salah satu lapak tidak valid.');
            }

            $price = $this->pricing->forBooth($booth, $selection['with_tent']);
            $area = $selection['area'] ?? (is_numeric($booth->zone?->code) ? (int) $booth->zone->code : null);
            $lines[] = [
                'booth' => $booth,
                'booth_id' => $booth->id,
                'area' => $area,
                'code' => $area ? 'Kotak '.$area : $booth->code,
                'zone' => $booth->zone?->name,
                'size' => $booth->sizeLabel(),
                'with_tent' => $selection['with_tent'],
                'place_price' => $price['place_price'],
                'tent_price' => $price['tent_price'],
                'line_total' => $price['line_total'],
            ];
        }

        return [
            'lines' => $lines,
            'subtotal' => (int) collect($lines)->sum('place_price'),
            'tent_total' => (int) collect($lines)->sum('tent_price'),
            'grand_total' => (int) collect($lines)->sum('line_total'),
        ];
    }

    /**
     * @param  array<string, mixed>  $applicantData
     * @param  array<int, array{booth_id: int, with_tent: bool}>  $selections
     */
    public function create(Event $event, array $applicantData, array $selections): Registration
    {
        if (! $event->registrationIsOpen()) {
            throw new BookingException('Pendaftaran sudah ditutup.');
        }

        $selections = $this->normalizeSelections($selections);
        $phone = Phone::normalize((string) $applicantData['whatsapp']);
        $max = $event->config()->maxSlots();

        if (count($selections) > $max) {
            throw new BookingException("Maksimal {$max} lapak untuk satu pendaftaran.");
        }

        try {
            return DB::transaction(function () use ($event, $applicantData, $selections, $phone, $max) {
                $this->lockPhone($phone);

                $active = $this->activeSlotCount($event, $phone);
                if ($active + count($selections) > $max) {
                    throw new BookingException("Nomor WhatsApp ini sudah memakai {$active} slot aktif. Batasnya {$max} slot, termasuk pesanan yang masih berjalan.");
                }

                $ids = collect($selections)->pluck('booth_id')->sort()->values();
                $booths = Booth::query()
                    ->with('zone')
                    ->whereIn('id', $ids)
                    ->orderBy('id')
                    ->when($this->supportsRowLock(), fn ($query) => $query->lockForUpdate())
                    ->get()
                    ->keyBy('id');

                if ($booths->count() !== $ids->count()) {
                    throw new BookingException('Salah satu lapak tidak ditemukan.');
                }

                $lines = [];
                foreach ($selections as $selection) {
                    /** @var Booth $booth */
                    $booth = $booths[$selection['booth_id']];
                    $booth->load('reservation.registrationItem.registration');

                    if ($booth->event_id !== $event->id || $booth->type !== 'umkm') {
                        throw new BookingException('Lapak tidak valid untuk pendaftaran UMKM.');
                    }

                    if ($booth->state !== 'available') {
                        throw new BookingException("Lapak {$booth->code} tidak tersedia.");
                    }

                    $this->releaseStaleReservation($booth);

                    if ($booth->displayStatus() !== 'available') {
                        throw new BookingException("Lapak {$booth->code} sedang dipesan.");
                    }

                    $price = $this->pricing->forBooth($booth, $selection['with_tent']);
                    $lines[] = ['booth' => $booth, 'with_tent' => $selection['with_tent'], 'price' => $price];
                }

                $applicant = Applicant::create([
                    'full_name' => $applicantData['full_name'],
                    'nik' => $applicantData['nik'],
                    'business_name' => $applicantData['business_name'],
                    'whatsapp' => $phone,
                    'email' => $applicantData['email'] ?? null,
                    'address' => $applicantData['address'],
                    'district' => $applicantData['district'],
                    'regency' => $applicantData['regency'],
                    'product_type' => $applicantData['product_type'],
                    'product_description' => $applicantData['product_description'],
                    'logo_path' => $applicantData['logo_path'] ?? null,
                    'special_notes' => $applicantData['special_notes'] ?? null,
                ]);

                $holdUntil = now()->addHours($event->config()->holdHours());

                $registration = Registration::create([
                    'event_id' => $event->id,
                    'applicant_id' => $applicant->id,
                    'code' => $this->codes->make(),
                    'type' => 'umkm',
                    'status' => RegistrationStatus::AwaitingPayment,
                    'whatsapp' => $phone,
                    'hold_expires_at' => $holdUntil,
                    'terms_accepted_at' => now(),
                    'subtotal' => collect($lines)->sum(fn ($line) => $line['price']['place_price']),
                    'tent_total' => collect($lines)->sum(fn ($line) => $line['price']['tent_price']),
                    'grand_total' => collect($lines)->sum(fn ($line) => $line['price']['line_total']),
                ]);

                foreach ($lines as $line) {
                    $item = RegistrationItem::create([
                        'registration_id' => $registration->id,
                        'booth_id' => $line['booth']->id,
                        'booth_code' => $line['booth']->code,
                        'zone_name' => $line['booth']->zone?->name ?? '-',
                        'size' => $line['booth']->size,
                        'with_tent' => $line['with_tent'],
                        'place_price' => $line['price']['place_price'],
                        'tent_price' => $line['price']['tent_price'],
                        'line_total' => $line['price']['line_total'],
                    ]);

                    try {
                        BoothReservation::create([
                            'booth_id' => $line['booth']->id,
                            'registration_item_id' => $item->id,
                            'expires_at' => $holdUntil,
                        ]);
                    } catch (UniqueConstraintViolationException) {
                        throw new BookingException("Lapak {$line['booth']->code} baru saja dipesan pendaftar lain.");
                    }
                }

                Payment::create([
                    'registration_id' => $registration->id,
                    'amount' => $registration->grand_total,
                    'method' => 'qris',
                    'gateway' => 'manual',
                    'status' => PaymentStatus::Pending,
                ]);

                $this->audit->log('registration.created', $registration, [
                    'code' => $registration->code,
                    'slots' => count($lines),
                    'total' => $registration->grand_total,
                ]);

                return $registration->load('items', 'payment', 'applicant', 'event');
            });
        } catch (UniqueConstraintViolationException) {
            throw new BookingException('Salah satu lapak baru saja dipesan pendaftar lain. Pilih slot lain.');
        }
    }

    /** @param  array<int, array{booth_id: int, with_tent?: bool}>  $selections */
    private function normalizeSelections(array $selections): array
    {
        $normalized = [];

        foreach ($selections as $selection) {
            $id = (int) ($selection['booth_id'] ?? 0);
            if ($id <= 0) {
                throw new BookingException('Pilihan lapak tidak valid.');
            }
            if (isset($normalized[$id])) {
                throw new BookingException('Slot yang sama dipilih lebih dari sekali.');
            }
            $normalized[$id] = [
                'booth_id' => $id,
                'with_tent' => (bool) ($selection['with_tent'] ?? false),
            ];
        }

        if ($normalized === []) {
            throw new BookingException('Pilih minimal satu lapak.');
        }

        return array_values($normalized);
    }

    private function lockPhone(string $phone): void
    {
        PhoneLock::query()->insertOrIgnore([
            'phone' => $phone,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $query = PhoneLock::query()->where('phone', $phone);
        if ($this->supportsRowLock()) {
            $query->lockForUpdate();
        }
        $query->first();
    }

    private function releaseStaleReservation(Booth $booth): void
    {
        $reservation = $booth->reservation;
        if (! $reservation) {
            return;
        }

        $registration = $reservation->registrationItem?->registration;
        if ($registration && $registration->occupiesBooth()) {
            return;
        }

        if ($registration && in_array($registration->status, [RegistrationStatus::AwaitingPayment, RegistrationStatus::PaymentRejected], true)) {
            $registration->update(['status' => RegistrationStatus::Expired]);
        }

        $reservation->delete();
        $booth->unsetRelation('reservation');
    }

    private function supportsRowLock(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'pgsql'], true);
    }
}
