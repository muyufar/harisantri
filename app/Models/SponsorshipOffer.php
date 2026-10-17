<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SponsorshipOffer extends Model
{
    protected $fillable = [
        'sponsorship_request_id',
        'user_id',
        'amount',
        'location_note',
        'notes',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(SponsorshipRequest::class, 'sponsorship_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
