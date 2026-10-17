<?php

namespace App\Console\Commands;

use App\Services\ExpiryService;
use Illuminate\Console\Command;

class ReleaseExpiredHolds extends Command
{
    protected $signature = 'hsn:release-expired';

    protected $description = 'Melepas slot UMKM yang melewati batas hold tanpa pembayaran tervalidasi';

    public function handle(ExpiryService $expiry): int
    {
        $count = $expiry->releaseExpired();
        $this->info("Slot kedaluwarsa dilepas: {$count}");

        return self::SUCCESS;
    }
}
