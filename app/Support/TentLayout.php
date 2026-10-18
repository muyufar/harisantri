<?php

namespace App\Support;

use App\Models\Booth;
use App\Models\BoothCategory;
use App\Models\BoothZone;
use App\Models\Event;

class TentLayout
{
    /** @return array<int, array{name: string, group: string, size: string, slots: int, color: string, x: float, y: float, w: float, h: float}> */
    public static function areas(): array
    {
        return config('hsn.tent_areas', []);
    }

    public static function area(int $number): ?array
    {
        return self::areas()[$number] ?? null;
    }

    public static function zoneCode(int $number): string
    {
        return (string) $number;
    }

    /**
     * Susun ulang zona dan slot denah. Booth lama yang masih dipakai pendaftaran
     * dipetahankan dulu, lalu dipindah oleh perintah relayout.
     */
    public static function seed(Event $event): void
    {
        $umkm = BoothCategory::query()->updateOrCreate(
            ['slug' => 'umkm'],
            ['name' => 'Lapak UMKM', 'description' => 'Area tenda berbayar menurut nomor kotak 1–8.']
        );
        $sponsorCategory = BoothCategory::query()->updateOrCreate(
            ['slug' => 'sponsorship'],
            ['name' => 'Lapak Sponsorship', 'description' => 'Area kerja sama yang harganya ditetapkan panitia.']
        );

        $keepCodes = [];

        foreach (self::areas() as $number => $area) {
            $zone = BoothZone::query()->updateOrCreate(
                ['event_id' => $event->id, 'code' => self::zoneCode($number)],
                [
                    'name' => $area['name'],
                    'type' => 'umkm',
                    'capacity' => $area['slots'],
                    'sort_order' => $number,
                    'is_active' => true,
                ]
            );

            for ($i = 1; $i <= $area['slots']; $i++) {
                $code = $number.'-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                $keepCodes[] = $code;
                $col = ($i - 1) % 8;
                $row = intdiv($i - 1, 8);
                $booth = Booth::query()->firstOrNew([
                    'event_id' => $event->id,
                    'code' => $code,
                ]);
                $booth->fill([
                    'zone_id' => $zone->id,
                    'category_id' => $umkm->id,
                    'type' => 'umkm',
                    'size' => $area['size'],
                    'pos_x' => $area['x'] + ($col * ($area['w'] / 8)),
                    'pos_y' => $area['y'] + ($row * 0.9),
                    'width' => max(1.2, $area['w'] / 8),
                    'height' => max(1.5, $area['h'] - 0.4),
                ]);
                if (! $booth->exists) {
                    $booth->state = 'available';
                }
                $booth->save();
            }
        }

        $sponsor = BoothZone::query()->updateOrCreate(
            ['event_id' => $event->id, 'code' => 'SPONSOR'],
            [
                'name' => 'Tenda Sponsor',
                'type' => 'sponsorship',
                'capacity' => 12,
                'sort_order' => 90,
                'is_active' => true,
            ]
        );

        for ($i = 1; $i <= 12; $i++) {
            $code = 'S-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $keepCodes[] = $code;
            $col = ($i - 1) % 4;
            $row = intdiv($i - 1, 4);
            $booth = Booth::query()->firstOrNew([
                'event_id' => $event->id,
                'code' => $code,
            ]);
            $booth->fill([
                'zone_id' => $sponsor->id,
                'category_id' => $sponsorCategory->id,
                'type' => 'sponsorship',
                'size' => null,
                'pos_x' => 58.0 + ($col * 3.2),
                'pos_y' => 52.0 + ($row * 3.8),
                'width' => 2.8,
                'height' => 3.2,
            ]);
            if (! $booth->exists) {
                $booth->state = 'available';
            }
            $booth->save();
        }

        // Nonaktifkan zona lama yang bukan 1–8 / sponsor.
        BoothZone::query()
            ->where('event_id', $event->id)
            ->whereNotIn('code', array_merge(array_map('strval', array_keys(self::areas())), ['SPONSOR']))
            ->update(['is_active' => false]);

        // Hapus booth orphan yang tidak dipakai reservation/item dan tidak termasuk layout baru.
        Booth::query()
            ->where('event_id', $event->id)
            ->whereNotIn('code', $keepCodes)
            ->whereDoesntHave('reservation')
            ->whereDoesntHave('registrationItems')
            ->delete();
    }
}
