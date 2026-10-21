<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementSend extends Model
{
    public const KEY_DATE_CHANGE_1_NOV_2026 = 'jadwal-1-nov-2026';

    protected $fillable = [
        'event_id',
        'registration_id',
        'key',
        'sent_by',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
