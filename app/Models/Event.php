<?php

namespace App\Models;

use App\Support\EventConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'activity_name',
        'starts_at',
        'ends_at',
        'venue',
        'organizer',
        'timezone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function settings(): HasMany
    {
        return $this->hasMany(EventSetting::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(BoothZone::class);
    }

    public function booths(): HasMany
    {
        return $this->hasMany(Booth::class);
    }

    public function priceRules(): HasMany
    {
        return $this->hasMany(PriceRule::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function sponsorshipCategories(): HasMany
    {
        return $this->hasMany(SponsorshipCategory::class);
    }

    public function sponsorshipRequests(): HasMany
    {
        return $this->hasMany(SponsorshipRequest::class);
    }

    public function config(): EventConfig
    {
        return new EventConfig($this);
    }

    public function registrationIsOpen(): bool
    {
        $closes = $this->config()->registrationClosesAt();

        return $closes === null || now()->lt($closes);
    }

    public static function current(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->with('settings')
            ->orderBy('id')
            ->first();
    }
}
