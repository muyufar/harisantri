<?php

namespace App\Console\Commands;

use App\Enums\RegistrationStatus;
use App\Models\Booth;
use App\Models\BoothReservation;
use App\Models\BoothZone;
use App\Models\Event;
use App\Models\RegistrationItem;
use App\Support\TentLayout;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RelayoutDenah extends Command
{
    protected $signature = 'hsn:relayout-denah {--dry-run : Hanya hitung tanpa menulis}';

    protected $description = 'Cadangkan data denah/pendaftaran, pasang layout kotak 1–8, lalu pindahkan pendaftar aktif ke area terdekat';

    public function handle(): int
    {
        $event = Event::current();
        if (! $event) {
            $this->error('Event aktif tidak ditemukan.');

            return self::FAILURE;
        }

        $backup = $this->backup($event);
        $this->info('Cadangan: '.$backup);

        $items = RegistrationItem::query()
            ->with(['booth.zone', 'registration', 'reservation'])
            ->whereHas('registration', fn ($query) => $query->where('event_id', $event->id))
            ->get();

        $plan = [];
        foreach ($items as $item) {
            $preferred = $this->preferredAreas($item->booth?->zone?->code, $item->booth?->size);
            $plan[] = [
                'item_id' => $item->id,
                'registration_id' => $item->registration_id,
                'old_booth' => $item->booth_code,
                'old_zone' => $item->zone_name,
                'preferred' => $preferred,
                'active' => $item->registration?->occupiesBooth() ?? false,
            ];
        }

        if ($this->option('dry-run')) {
            $this->table(['item', 'lama', 'zona', 'preferensi', 'aktif'], collect($plan)->map(fn ($row) => [
                $row['item_id'],
                $row['old_booth'],
                $row['old_zone'],
                implode(',', $row['preferred']),
                $row['active'] ? 'ya' : 'tidak',
            ])->all());

            return self::SUCCESS;
        }

        DB::transaction(function () use ($event, $plan) {
            TentLayout::seed($event);

            $used = [];
            foreach ($plan as $row) {
                $item = RegistrationItem::query()->with('reservation')->find($row['item_id']);
                if (! $item) {
                    continue;
                }

                $booth = $this->pickBooth($event, $row['preferred'], $used, $row['active']);
                if (! $booth) {
                    $this->warn("Item {$item->id} belum dipindah: kapasitas preferensi penuh.");
                    continue;
                }

                $used[] = $booth->id;
                $oldReservation = $item->reservation;
                $oldReservation?->delete();

                $item->update([
                    'booth_id' => $booth->id,
                    'booth_code' => $booth->code,
                    'zone_name' => $booth->zone?->name ?? $item->zone_name,
                    'size' => $booth->size,
                ]);

                if ($row['active'] || in_array($item->registration?->status, [
                    RegistrationStatus::AwaitingPayment,
                    RegistrationStatus::AwaitingVerification,
                    RegistrationStatus::PaymentRejected,
                    RegistrationStatus::Confirmed,
                ], true)) {
                    BoothReservation::query()->updateOrCreate(
                        ['booth_id' => $booth->id],
                        [
                            'registration_item_id' => $item->id,
                            'sponsorship_request_id' => null,
                            'expires_at' => $item->registration?->hold_expires_at,
                        ]
                    );
                }
            }

            // Bersihkan booth lama yang sudah tidak terpakai.
            Booth::query()
                ->where('event_id', $event->id)
                ->whereDoesntHave('reservation')
                ->whereDoesntHave('registrationItems')
                ->whereDoesntHave('zone', fn ($query) => $query->whereIn('code', array_merge(
                    array_map('strval', array_keys(TentLayout::areas())),
                    ['SPONSOR']
                )))
                ->delete();

            BoothZone::query()
                ->where('event_id', $event->id)
                ->whereNotIn('code', array_merge(array_map('strval', array_keys(TentLayout::areas())), ['SPONSOR']))
                ->whereDoesntHave('booths')
                ->delete();
        });

        $this->info('Denah kotak 1–8 aktif. Pendaftar aktif dipetakan ke area terdekat.');

        return self::SUCCESS;
    }

    private function backup(Event $event): string
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);
        $path = $dir.'/relayout-'.$event->id.'-'.now()->format('Ymd-His').'.json';

        $payload = [
            'created_at' => now()->toIso8601String(),
            'event' => $event->toArray(),
            'zones' => BoothZone::query()->where('event_id', $event->id)->get()->toArray(),
            'booths' => Booth::query()->where('event_id', $event->id)->get()->toArray(),
            'registration_items' => RegistrationItem::query()
                ->whereHas('registration', fn ($query) => $query->where('event_id', $event->id))
                ->get()
                ->toArray(),
            'reservations' => BoothReservation::query()
                ->whereHas('booth', fn ($query) => $query->where('event_id', $event->id))
                ->get()
                ->toArray(),
        ];

        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }

    /** @return array<int, int> */
    private function preferredAreas(?string $oldZoneCode, ?string $size): array
    {
        $north = [1, 2, 3, 4, 5];
        $parkir = [6, 7, 8];
        $bySize = collect(TentLayout::areas())
            ->filter(fn ($area) => $area['size'] === $size)
            ->keys()
            ->map(fn ($n) => (int) $n)
            ->all();

        $base = match ($oldZoneCode) {
            'DEPAN', '1', '2', '3', '4', '5' => $north,
            'BARAT' => [2, 3, 4, 1, 5],
            'TIMUR', '6', '7', '8' => $parkir,
            default => array_keys(TentLayout::areas()),
        };

        $ordered = array_values(array_unique(array_merge(
            array_values(array_intersect($base, $bySize)),
            $base,
            array_keys(TentLayout::areas())
        )));

        return array_map('intval', $ordered);
    }

    /** @param  array<int, int>  $preferred */
    /** @param  array<int, int>  $used */
    private function pickBooth(Event $event, array $preferred, array $used, bool $needReservation): ?Booth
    {
        foreach ($preferred as $area) {
            $booth = Booth::query()
                ->with('zone')
                ->where('event_id', $event->id)
                ->where('type', 'umkm')
                ->whereHas('zone', fn ($query) => $query->where('code', (string) $area))
                ->whereNotIn('id', $used)
                ->when($needReservation, fn ($query) => $query->whereDoesntHave('reservation'))
                ->orderBy('code')
                ->first();

            if ($booth) {
                return $booth;
            }
        }

        return null;
    }
}
