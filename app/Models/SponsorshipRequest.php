<?php

namespace App\Models;

use App\Enums\SponsorshipStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SponsorshipRequest extends Model
{
    protected $fillable = [
        'event_id',
        'sponsorship_category_id',
        'booth_id',
        'code',
        'company_name',
        'pic_name',
        'whatsapp',
        'business_type',
        'area_requirement',
        'facility_needs',
        'notes',
        'status',
        'agreed_price',
    ];

    protected function casts(): array
    {
        return [
            'status' => SponsorshipStatus::class,
            'agreed_price' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SponsorshipCategory::class, 'sponsorship_category_id');
    }

    public function booth(): BelongsTo
    {
        return $this->belongsTo(Booth::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(SponsorshipOffer::class);
    }

    public function latestOffer(): ?SponsorshipOffer
    {
        return $this->offers()->latest()->first();
    }
}
