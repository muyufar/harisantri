<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Registration extends Model
{
    protected $fillable = [
        'event_id',
        'applicant_id',
        'code',
        'type',
        'status',
        'whatsapp',
        'hold_expires_at',
        'terms_accepted_at',
        'confirmed_at',
        'cancelled_at',
        'subtotal',
        'tent_total',
        'grand_total',
        'cancellation_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'hold_expires_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'subtotal' => 'integer',
            'tent_total' => 'integer',
            'grand_total' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RegistrationItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function occupiesBooth(): bool
    {
        return match ($this->status) {
            RegistrationStatus::Confirmed, RegistrationStatus::AwaitingVerification => true,
            RegistrationStatus::AwaitingPayment, RegistrationStatus::PaymentRejected => $this->hold_expires_at?->isFuture() === true,
            default => false,
        };
    }

    public function canUploadProof(): bool
    {
        return in_array($this->status, [RegistrationStatus::AwaitingPayment, RegistrationStatus::PaymentRejected], true)
            && $this->hold_expires_at?->isFuture() === true;
    }
}
