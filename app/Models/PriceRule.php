<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceRule extends Model
{
    protected $fillable = [
        'event_id',
        'size',
        'place_price',
        'tent_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'place_price' => 'integer',
            'tent_price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function sizeLabel(): string
    {
        return match ($this->size) {
            '2x2' => '2 × 2 meter',
            '3x3' => '3 × 3 meter',
            default => $this->size,
        };
    }
}
