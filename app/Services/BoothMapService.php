<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Models\Booth;
use App\Models\BoothZone;
use App\Models\Event;
use App\Support\TentLayout;
use Illuminate\Support\Collection;

class BoothMapService
{
    /** @var array<string, Collection<int, array<string, mixed>>> */
    private array $presented = [];

    public function __construct(private PricingService $pricing) {}

    public function models(Event $event, bool $activeZonesOnly = true): Collection
    {
        return Booth::query()
            ->with(['zone', 'reservation.registrationItem.registration', 'reservation.sponsorshipRequest'])
            ->where('event_id', $event->id)
            ->when(
                $activeZonesOnly,
                fn ($query) => $query->whereHas('zone', fn ($zone) => $zone->where('is_active', true)),
                fn ($query) => $query->whereHas('zone')
            )
            ->orderBy('code')
            ->get();
    }

    /** @return Collection<int, array<string, mixed>> */
    public function present(Event $event, bool $activeZonesOnly = true): Collection
    {
        $cacheKey = $event->id.':'.($activeZonesOnly ? 'open' : 'all');
        if (isset($this->presented[$cacheKey])) {
            return $this->presented[$cacheKey];
        }

        return $this->presented[$cacheKey] = $this->models($event, $activeZonesOnly)->map(function (Booth $booth) {
            $place = null;
            $tent = null;

            if ($booth->type === 'umkm') {
                try {
                    $price = $this->pricing->forBooth($booth, true);
                    $place = $price['place_price'];
                    $tent = $price['tent_fee'];
                } catch (BookingException) {
                    $place = null;
                    $tent = null;
                }
            }

            $areaNumber = is_numeric($booth->zone?->code) ? (int) $booth->zone->code : null;

            return [
                'id' => $booth->id,
                'code' => $booth->code,
                'label' => $areaNumber ? 'Kotak '.$areaNumber : $booth->code,
                'area' => $areaNumber,
                'zone' => $booth->zone?->name,
                'zone_id' => $booth->zone_id,
                'type' => $booth->type,
                'size' => $booth->size,
                'size_label' => $booth->sizeLabel(),
                'x' => $booth->pos_x,
                'y' => $booth->pos_y,
                'w' => $booth->width,
                'h' => $booth->height,
                'open' => (bool) $booth->zone?->is_active,
                'status' => $booth->displayStatus(),
                'status_label' => $booth->statusLabel(),
                'place_price' => $place,
                'tent_price' => $tent,
                'line_total' => $place !== null && $tent !== null ? $place + $tent : null,
            ];
        })->values();
    }

    /** @return array<int, array<string, mixed>> */
    public function areaCards(Event $event, bool $activeZonesOnly = false): array
    {
        return $this->cardsFrom($event, $this->present($event, $activeZonesOnly), $activeZonesOnly);
    }

    /** @param  Collection<int, array<string, mixed>>  $presented
     * @return array<int, array<string, mixed>>
     */
    private function cardsFrom(Event $event, Collection $presented, bool $activeZonesOnly = true): array
    {
        $booths = $presented->where('type', 'umkm');
        $zoneOpen = BoothZone::query()
            ->where('event_id', $event->id)
            ->where('type', 'umkm')
            ->pluck('is_active', 'code');
        $cards = [];

        foreach (TentLayout::areas() as $number => $area) {
            $open = (bool) ($zoneOpen[(string) $number] ?? true);
            if ($activeZonesOnly && ! $open) {
                continue;
            }

            $rows = $booths->where('area', $number);
            $available = $open ? $rows->where('status', 'available')->count() : 0;
            $status = ! $open ? 'closed' : (
                $available > 0 ? 'available' : (
                    $rows->where('status', 'held')->isNotEmpty() ? 'held' : (
                        $rows->where('status', 'occupied')->isNotEmpty() ? 'occupied' : 'blocked'
                    )
                )
            );

            $sample = $rows->first();
            $cards[] = [
                'number' => $number,
                'name' => $area['name'],
                'group' => $area['group'],
                'size' => $area['size'],
                'size_label' => match ($area['size']) {
                    '2x2' => '2 × 2 meter',
                    '3x3' => '3 × 3 meter',
                    default => $area['size'],
                },
                'color' => $area['color'],
                'x' => $area['x'],
                'y' => $area['y'],
                'w' => $area['w'],
                'h' => $area['h'],
                'open' => $open,
                'total' => $rows->count(),
                'stock' => $rows->where('status', 'available')->count(),
                'available' => $available,
                'held' => $rows->where('status', 'held')->count(),
                'occupied' => $rows->where('status', 'occupied')->count(),
                'blocked' => $rows->where('status', 'blocked')->count(),
                'status' => $status,
                'status_label' => match ($status) {
                    'closed' => 'Ditutup sementara',
                    'available' => $available.' slot tersedia',
                    'held' => 'Sedang dipesan',
                    'occupied' => 'Penuh',
                    default => 'Tidak tersedia',
                },
                'place_price' => $sample['place_price'] ?? null,
                'tent_price' => $sample['tent_price'] ?? null,
                'line_total' => $sample['line_total'] ?? null,
                'zone_id' => $rows->first()['zone_id'] ?? BoothZone::query()
                    ->where('event_id', $event->id)
                    ->where('code', (string) $number)
                    ->value('id'),
            ];
        }

        return $cards;
    }

    /** @param  array<int, int>  $selectedAreas */
    /** @param  array<int, bool>  $tentChoices */
    /** @param  array<int, int>  $quantities */
    public function payload(Event $event, string $mode, array $selectedAreas = [], ?int $remaining = null, array $tentChoices = [], array $quantities = []): array
    {
        $activeZonesOnly = $mode !== 'edit';
        $booths = $this->present($event, $activeZonesOnly);

        return [
            'mode' => $mode,
            'floorplan' => $event->config()->floorplanUrl(),
            'max' => $remaining ?? $event->config()->maxSlots(),
            'booths' => $mode === 'edit' ? $this->present($event, false) : [],
            'areas' => $this->cardsFrom($event, $mode === 'edit' ? $this->present($event, false) : $booths, $activeZonesOnly),
            'zones' => $event->zones()
                ->when($activeZonesOnly, fn ($query) => $query->where('is_active', true))
                ->orderBy('sort_order')
                ->get(['id', 'name', 'type', 'code', 'is_active']),
            'selected' => array_values(array_map('intval', $selectedAreas)),
            'quantities' => $quantities,
            'tentChoices' => $tentChoices,
            'saveUrl' => $mode === 'edit' ? route('admin.map.update') : null,
            'feedUrl' => route('booths.feed'),
            'landmarks' => $this->landmarks(),
        ];
    }

    public function stats(Event $event, bool $activeZonesOnly = true): array
    {
        $booths = $this->present($event, $activeZonesOnly);
        $umkm = $booths->where('type', 'umkm');
        $sponsor = $booths->where('type', 'sponsorship');

        return [
            'umkm_total' => $umkm->count(),
            'available' => $umkm->where('status', 'available')->count(),
            'held' => $umkm->where('status', 'held')->count(),
            'occupied' => $umkm->where('status', 'occupied')->count(),
            'blocked' => $umkm->where('status', 'blocked')->count(),
            'sponsorship_total' => $sponsor->count(),
            'sponsorship_available' => $sponsor->where('status', 'available')->count(),
        ];
    }

    /**
     * Ambil satu booth kosong di area nomor tersebut.
     *
     * @param  array<int, int>  $excludeBoothIds
     */
    public function allocateBooth(Event $event, int $areaNumber, array $excludeBoothIds = [], bool $requireOpen = true): Booth
    {
        if (! TentLayout::area($areaNumber)) {
            throw new BookingException('Nomor area tenda tidak valid.');
        }

        $booths = Booth::query()
            ->with(['zone', 'reservation.registrationItem.registration', 'reservation.sponsorshipRequest'])
            ->where('event_id', $event->id)
            ->where('type', 'umkm')
            ->whereHas('zone', function ($query) use ($areaNumber, $requireOpen) {
                $query->where('code', (string) $areaNumber);
                if ($requireOpen) {
                    $query->where('is_active', true);
                }
            })
            ->when($excludeBoothIds !== [], fn ($query) => $query->whereNotIn('id', $excludeBoothIds))
            ->orderBy('code')
            ->get();

        if ($requireOpen && $booths->isEmpty()) {
            throw new BookingException('Kotak '.$areaNumber.' sementara tidak dibuka untuk pendaftaran.');
        }

        foreach ($booths as $booth) {
            if ($booth->state === 'available' && $booth->displayStatus() === 'available') {
                return $booth;
            }
        }

        throw new BookingException('Kotak '.$areaNumber.' sudah penuh. Pilih nomor area lain.');
    }

    /** @return array<int, array<string, string|float>> */
    private function landmarks(): array
    {
        return [
            ['kind' => 'start', 'label' => 'Start', 'short' => 'Start', 'note' => 'Titik mulai Jalan Sehat.', 'x' => 88, 'y' => 28, 'w' => 6, 'h' => 6],
            ['kind' => 'finish', 'label' => 'Finis', 'short' => 'Finis', 'note' => 'Titik finis Jalan Sehat.', 'x' => 4, 'y' => 36, 'w' => 7, 'h' => 6],
            ['kind' => 'panggung', 'label' => 'Panggung', 'short' => 'Panggung', 'note' => 'Panggung kegiatan, bukan lapak sewa.', 'x' => 18, 'y' => 48, 'w' => 7, 'h' => 7],
            ['kind' => 'vip', 'label' => 'Tenda VIP', 'short' => 'VIP', 'note' => 'Tenda VIP, bukan lapak sewa.', 'x' => 24, 'y' => 58, 'w' => 7, 'h' => 7],
            ['kind' => 'panitia', 'label' => 'Tenda Panitia', 'short' => 'Panitia', 'note' => 'Pos panitia, bukan lapak sewa.', 'x' => 14, 'y' => 66, 'w' => 7, 'h' => 6],
            ['kind' => 'sponsor', 'label' => 'Tenda Sponsor', 'short' => 'Sponsor', 'note' => 'Area sponsorship, diajukan lewat formulir sponsor.', 'x' => 58, 'y' => 52, 'w' => 12, 'h' => 10],
        ];
    }
}
