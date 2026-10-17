<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case Draft = 'draft';
    case AwaitingPayment = 'awaiting_payment';
    case AwaitingVerification = 'awaiting_verification';
    case Confirmed = 'confirmed';
    case PaymentRejected = 'payment_rejected';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::AwaitingPayment => 'Menunggu Pembayaran',
            self::AwaitingVerification => 'Menunggu Verifikasi',
            self::Confirmed => 'Terkonfirmasi',
            self::PaymentRejected => 'Pembayaran Ditolak',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Kedaluwarsa',
        };
    }
}
