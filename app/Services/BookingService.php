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
use App\Support\TentLayout;
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
            throw new BookingException($event->config()->registrationClosedMessage());
        }

        app(ExpiryService::class)->releaseExpired();

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

    public function restore(Registration $registration, ?int $areaNumber = null): Registration
    {
        $target = null;
        if ($areaNumber) {
            $target = TentLayout::area($areaNumber);
            if (! $target) {
                throw new BookingException('Nomor kotak tidak valid.');
            }
        }

        return DB::transaction(function () use ($registration, $areaNumber, $target) {
            $registration = Registration::query()->lockForUpdate()->findOrFail($registration->id);
            $registration->load(['event', 'items', 'payment']);

            if ($registration->status !== RegistrationStatus::Expired) {
                throw new BookingException('Hanya pendaftaran kedaluwarsa yang bisa diaktifkan kembali.');
            }

            if ($registration->items->isEmpty()) {
                throw new BookingException('Pendaftaran ini tidak punya slot.');
            }

            $phone = Phone::normalize($registration->whatsapp);
            $this->lockPhone($phone);
            $max = $registration->event->config()->maxSlots();
            $active = $this->activeSlotCount($registration->event, $phone);
            $needed = $registration->items->count();

            if ($active + $needed > $max) {
                throw new BookingException("Nomor WhatsApp ini sudah memakai {$active} slot aktif. Batasnya {$max} slot, jadi pendaftaran ini belum bisa diaktifkan.");
            }

            $holdUntil = now()->addHours($registration->event->config()->holdHours());
            $maps = app(BoothMapService::class);
            $usedBoothIds = [];

            foreach ($registration->items as $item) {
                $item = RegistrationItem::query()->lockForUpdate()->findOrFail($item->id);
                $item->load(['booth.zone', 'reservation']);
                $booth = $this->reclaimBooth($registration, $item, $maps, $usedBoothIds, $areaNumber);
                $usedBoothIds[] = $booth->id;
                $item->reservation?->delete();
                $booth->unsetRelation('reservation');
                $booth->load('reservation.registrationItem.registration');
                $this->releaseStaleReservation($booth);

                try {
                    BoothReservation::create([
                        'booth_id' => $booth->id,
                        'registration_item_id' => $item->id,
                        'expires_at' => $holdUntil,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    throw new BookingException('Slot yang dipilih baru saja terisi. Coba aktifkan lagi.');
                }

                $price = $this->pricing->forBooth($booth, (bool) $item->with_tent);
                $item->update([
                    'booth_id' => $booth->id,
                    'booth_code' => $booth->code,
                    'zone_name' => $booth->zone?->name ?? $item->zone_name,
                    'size' => $booth->size,
                    'place_price' => $price['place_price'],
                    'tent_price' => $price['tent_price'],
                    'line_total' => $price['line_total'],
                ]);
            }

            $this->syncTotals($registration);

            $registration->update([
                'status' => RegistrationStatus::AwaitingPayment,
                'hold_expires_at' => $holdUntil,
                'cancelled_at' => null,
            ]);

            if ($registration->payment && in_array($registration->payment->status, [PaymentStatus::Pending, PaymentStatus::Rejected], true)) {
                $registration->payment->update([
                    'status' => PaymentStatus::Pending,
                    'amount' => $registration->grand_total,
                ]);
            } elseif (! $registration->payment) {
                Payment::create([
                    'registration_id' => $registration->id,
                    'amount' => $registration->grand_total,
                    'method' => 'qris',
                    'gateway' => 'manual',
                    'status' => PaymentStatus::Pending,
                ]);
            }

            $this->audit->log('registration.restored', $registration, [
                'code' => $registration->code,
                'slots' => $needed,
                'area' => $target['name'] ?? null,
                'hold_expires_at' => $holdUntil->toDateTimeString(),
            ]);

            return $registration->fresh(['items', 'payment']);
        });
    }

    /**
     * @param  array<int, int>  $usedBoothIds
     */
    private function reclaimBooth(Registration $registration, RegistrationItem $item, BoothMapService $maps, array $usedBoothIds, ?int $targetArea = null): Booth
    {
        $currentArea = $this->areaNumber($item);
        $stay = $targetArea === null || $targetArea === $currentArea;
        $current = $item->booth;
        if ($stay && $current && ! in_array($current->id, $usedBoothIds, true)) {
            $current->load(['reservation.registrationItem.registration', 'zone']);
            if (
                $current->event_id === $registration->event_id
                && $current->type === 'umkm'
                && $current->state === 'available'
                && $current->size === $item->size
                && $current->displayStatus() === 'available'
            ) {
                return $current;
            }
        }

        $area = $targetArea ?? $currentArea;
        if (! $area) {
            throw new BookingException('Kotak asal slot '.$item->booth_code.' tidak dikenali, jadi pendaftaran belum bisa diaktifkan.');
        }

        return $maps->allocateBooth(
            $registration->event,
            $area,
            array_values(array_unique(array_filter([
                ...$usedBoothIds,
                $stay ? $item->booth_id : null,
            ]))),
            requireOpen: false,
        );
    }

    private function syncTotals(Registration $registration): void
    {
        $items = $registration->items()->get();
        $grand = (int) $items->sum('line_total');
        $registration->update([
            'subtotal' => (int) $items->sum('place_price'),
            'tent_total' => (int) $items->sum('tent_price'),
            'grand_total' => $grand,
        ]);

        $payment = $registration->payment()->first();
        if ($payment && $payment->status !== PaymentStatus::Refunded) {
            $payment->update(['amount' => $grand]);
        }
    }

    private function areaNumber(RegistrationItem $item): ?int
    {
        $code = $item->booth?->zone?->code;
        if (is_numeric($code)) {
            return (int) $code;
        }

        foreach (TentLayout::areas() as $number => $area) {
            if ($area['name'] === $item->zone_name) {
                return $number;
            }
        }

        return null;
    }

    public function dropItem(Registration $registration, RegistrationItem $item): Registration
    {
        return DB::transaction(function () use ($registration, $item) {
            $registration = Registration::query()->lockForUpdate()->findOrFail($registration->id);

            if (! in_array($registration->status, [
                RegistrationStatus::AwaitingPayment,
                RegistrationStatus::AwaitingVerification,
                RegistrationStatus::PaymentRejected,
                RegistrationStatus::Confirmed,
            ], true)) {
                throw new BookingException('Pendaftaran yang sudah tidak aktif tidak bisa dikurangi slotnya.');
            }

            $item = RegistrationItem::query()->lockForUpdate()->findOrFail($item->id);
            if ($item->registration_id !== $registration->id) {
                throw new BookingException('Slot ini bukan bagian dari pendaftaran tersebut.');
            }

            if ($registration->items()->count() < 2) {
                throw new BookingException('Ini satu-satunya slot. Batalkan seluruh pendaftaran jika ingin melepasnya.');
            }

            $slot = $item->booth_code;
            $zone = $item->zone_name;
            $item->reservation?->delete();
            $item->delete();
            $this->syncTotals($registration);

            $this->audit->log('registration.item_removed', $registration, [
                'code' => $registration->code,
                'slot' => $slot,
                'zone' => $zone,
                'grand_total' => $registration->fresh()->grand_total,
            ]);

            return $registration->fresh(['items']);
        });
    }

    public function relocate(Registration $registration, RegistrationItem $item, int $areaNumber): Registration
    {
        $area = TentLayout::area($areaNumber);
        if (! $area) {
            throw new BookingException('Nomor kotak tidak valid.');
        }

        return DB::transaction(function () use ($registration, $item, $areaNumber, $area) {
            $registration = Registration::query()->with('event')->lockForUpdate()->findOrFail($registration->id);

            if (! in_array($registration->status, [
                RegistrationStatus::AwaitingPayment,
                RegistrationStatus::AwaitingVerification,
                RegistrationStatus::PaymentRejected,
                RegistrationStatus::Confirmed,
            ], true)) {
                throw new BookingException('Pendaftaran yang sudah tidak aktif tidak bisa dipindah.');
            }

            $item = RegistrationItem::query()->lockForUpdate()->findOrFail($item->id);
            $item->load(['reservation', 'booth.zone']);
            if ($item->registration_id !== $registration->id) {
                throw new BookingException('Slot ini bukan bagian dari pendaftaran tersebut.');
            }

            $current = (int) ($item->booth?->zone?->code ?? 0);
            if ($current === $areaNumber) {
                throw new BookingException('Pendaftar sudah berada di kotak ini.');
            }

            $booth = app(BoothMapService::class)->allocateBooth(
                $registration->event,
                $areaNumber,
                array_filter([$item->booth_id]),
                requireOpen: false,
            );
            $expiresAt = $item->reservation?->expires_at;
            $from = $item->zone_name;
            $item->reservation?->delete();

            try {
                BoothReservation::create([
                    'booth_id' => $booth->id,
                    'registration_item_id' => $item->id,
                    'expires_at' => $expiresAt,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw new BookingException('Slot di kotak tujuan baru saja terisi. Pilih kotak lain.');
            }

            $price = $this->pricing->forBooth($booth, (bool) $item->with_tent);
            $item->update([
                'booth_id' => $booth->id,
                'booth_code' => $booth->code,
                'zone_name' => $booth->zone?->name ?? $area['name'],
                'size' => $booth->size,
                'place_price' => $price['place_price'],
                'tent_price' => $price['tent_price'],
                'line_total' => $price['line_total'],
            ]);
            $this->syncTotals($registration);

            $this->audit->log('registration.relocated', $registration, [
                'code' => $registration->code,
                'from' => $from,
                'to' => $area['name'],
                'slot' => $booth->code,
            ]);

            return $registration->fresh(['items']);
        });
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
