<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RegistrationItem extends Model
{
    protected $fillable = [
        'registration_id',
        'booth_id',
        'booth_code',
        'zone_name',
        'size',
        'with_tent',
        'place_price',
        'tent_price',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'with_tent' => 'boolean',
            'place_price' => 'integer',
            'tent_price' => 'integer',
            'line_total' => 'integer',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function booth(): BelongsTo
    {
        return $this->belongsTo(Booth::class);
    }

    public function reservation(): HasOne
    {
        return $this->hasOne(BoothReservation::class);
    }

    public function checkin(): HasOne
    {
        return $this->hasOne(BoothCheckin::class);
    }

    public function sizeLabel(): string
    {
        return match ($this->size) {
            '2x2' => '2 × 2 meter',
            '3x3' => '3 × 3 meter',
            default => $this->size ?: 'Menyesuaikan',
        };
    }
}
