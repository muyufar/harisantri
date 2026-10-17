<?php

namespace App\Enums;

enum SponsorshipStatus: string
{
    case NewRequest = 'new';
    case Contacted = 'contacted';
    case Negotiation = 'negotiation';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::NewRequest => 'Pengajuan Baru',
            self::Contacted => 'Sedang Dihubungi',
            self::Negotiation => 'Negosiasi',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Completed => 'Selesai',
        };
    }
}
