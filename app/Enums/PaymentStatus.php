<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Belum dibayar',
            self::Submitted => 'Menunggu verifikasi',
            self::Verified => 'Diterima',
            self::Rejected => 'Ditolak',
            self::Refunded => 'Refund dicatat',
        };
    }
}
