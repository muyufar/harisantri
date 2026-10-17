<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoothReservation extends Model
{
    protected $fillable = [
        'booth_id',
        'registration_item_id',
        'sponsorship_request_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function booth(): BelongsTo
    {
        return $this->belongsTo(Booth::class);
    }

    public function registrationItem(): BelongsTo
    {
        return $this->belongsTo(RegistrationItem::class);
    }

    public function sponsorshipRequest(): BelongsTo
    {
        return $this->belongsTo(SponsorshipRequest::class);
    }
}
