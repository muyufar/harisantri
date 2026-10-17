<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Models\Booth;
use App\Models\Event;
use Illuminate\Support\Collection;

class BoothMapService
{
    public function __construct(private PricingService $pricing) {}

    public function models(Event $event): Collection
    {
        return Booth::query()
            ->with(['zone', 'reservation.registrationItem.registration', 'reservation.sponsorshipRequest'])
            ->where('event_id', $event->id)
            ->orderBy('code')
            ->get();
    }

    /** @return Collection<int, array<string, mixed>> */
    public function present(Event $event): Collection
    {
        return $this->models($event)->map(function (Booth $booth) {
            $place = null;
            $tent = null;

            if ($booth->type === 'umkm') {
                try {
                    $price = $this->pricing->forBooth($booth, false);
                    $place = $price['place_price'];
                    $tent = $price['tent_fee'];
                } catch (BookingException) {
                    $place = null;
                    $tent = null;
                }
            }

            return [
                'id' => $booth->id,
                'code' => $booth->code,
                'zone' => $booth->zone?->name,
                'zone_id' => $booth->zone_id,
                'type' => $booth->type,
                'size' => $booth->size,
                'size_label' => $booth->sizeLabel(),
                'x' => $booth->pos_x,
                'y' => $booth->pos_y,
                'w' => $booth->width,
                'h' => $booth->height,
                'status' => $booth->displayStatus(),
                'status_label' => $booth->statusLabel(),
                'place_price' => $place,
                'tent_price' => $tent,
            ];
        })->values();
    }

    /** @param  array<int, int>  $selected */
    public function payload(Event $event, string $mode, array $selected = [], ?int $remaining = null): array
    {
        $booths = $this->present($event);

        return [
            'mode' => $mode,
            'floorplan' => $event->config()->floorplanUrl(),
            'max' => $remaining ?? $event->config()->maxSlots(),
            'booths' => $booths,
            'zones' => $event->zones()->orderBy('sort_order')->get(['id', 'name', 'type']),
            'selected' => array_values($selected),
            'saveUrl' => $mode === 'edit' ? route('admin.map.update') : null,
            'feedUrl' => route('booths.feed'),
            'landmarks' => $this->landmarks(),
        ];
    }

    public function stats(Event $event): array
    {
        $booths = $this->present($event);
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

    /** @return array<int, array<string, string|float>> */
    private function landmarks(): array
    {
        return [
            ['kind' => 'start', 'label' => 'Start', 'short' => 'Start', 'note' => 'Titik mulai Jalan Sehat, di tepi jalan sisi timur.', 'x' => 88, 'y' => 31, 'w' => 6, 'h' => 6],
            ['kind' => 'finish', 'label' => 'Finis', 'short' => 'Finis', 'note' => 'Titik finis Jalan Sehat, di sisi barat dekat gerbang.', 'x' => 6, 'y' => 40, 'w' => 8, 'h' => 6],
            ['kind' => 'panggung', 'label' => 'Panggung', 'short' => 'Panggung', 'note' => 'Panggung kegiatan, bukan lapak sewa.', 'x' => 23.5, 'y' => 50, 'w' => 6, 'h' => 7],
            ['kind' => 'vip', 'label' => 'Tenda VIP', 'short' => 'VIP', 'note' => 'Tenda VIP, bukan lapak sewa.', 'x' => 26, 'y' => 66, 'w' => 7, 'h' => 7],
            ['kind' => 'panitia', 'label' => 'Tenda Panitia', 'short' => 'Panitia', 'note' => 'Pos panitia, bukan lapak sewa.', 'x' => 21, 'y' => 76, 'w' => 6, 'h' => 6],
        ];
    }
}
