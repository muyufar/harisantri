<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use App\Enums\SponsorshipStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booth extends Model
{
    protected $fillable = [
        'event_id',
        'zone_id',
        'category_id',
        'code',
        'type',
        'size',
        'state',
        'pos_x',
        'pos_y',
        'width',
        'height',
    ];

    protected function casts(): array
    {
        return [
            'pos_x' => 'float',
            'pos_y' => 'float',
            'width' => 'float',
            'height' => 'float',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(BoothZone::class, 'zone_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BoothCategory::class, 'category_id');
    }

    public function reservation(): HasOne
    {
        return $this->hasOne(BoothReservation::class);
    }

    public function registrationItems(): HasMany
    {
        return $this->hasMany(RegistrationItem::class);
    }

    public function sizeLabel(): string
    {
        return match ($this->size) {
            '2x2' => '2 × 2 meter',
            '3x3' => '3 × 3 meter',
            default => 'Menyesuaikan',
        };
    }

    public function displayStatus(): string
    {
        if ($this->state === 'blocked') {
            return 'blocked';
        }

        $reservation = $this->relationLoaded('reservation')
            ? $this->reservation
            : $this->reservation()->with(['registrationItem.registration', 'sponsorshipRequest'])->first();

        if ($reservation?->sponsorship_request_id) {
            $sponsorship = $reservation->sponsorshipRequest;
            if ($sponsorship && in_array($sponsorship->status, [SponsorshipStatus::Approved, SponsorshipStatus::Completed], true)) {
                return 'occupied';
            }

            if ($sponsorship && $sponsorship->status !== SponsorshipStatus::Rejected) {
                return 'held';
            }

            return 'available';
        }

        $registration = $reservation?->registrationItem?->registration;

        if (! $registration || ! $registration->occupiesBooth()) {
            return 'available';
        }

        return $registration->status === RegistrationStatus::Confirmed ? 'occupied' : 'held';
    }

    public function statusLabel(): string
    {
        return match ($this->displayStatus()) {
            'occupied' => 'Sudah terisi',
            'held' => 'Sedang dipesan',
            'blocked' => 'Tidak tersedia',
            default => 'Tersedia',
        };
    }
}
