<?php

namespace App\Console\Commands;

use App\Enums\LedgerType;
use App\Enums\PaymentStatus;
use App\Models\Event;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncFinanceReport extends Command
{
    protected $signature = 'hsn:sync-finance-report {--force : Run without confirmation}';

    protected $description = 'Samakan buku kas dan total pemasukan UMKM dengan laporan keuangan HSN 2026';

    private const UMKM_TARGET = 10150000;

    public function handle(): int
    {
        $event = Event::current();
        if (! $event) {
            $this->error('Event aktif tidak ditemukan.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Timpa catatan buku kas dan sesuaikan total pembayaran UMKM terverifikasi ke Rp10.150.000?')) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($event) {
            $this->syncLedger($event->id);
            $this->syncBoothIncome($event->id);
        });

        $boothIncome = (int) Payment::query()
            ->where('status', PaymentStatus::Verified)
            ->whereHas('registration', fn ($query) => $query->where('event_id', $event->id))
            ->sum('amount');
        $otherIncome = (int) LedgerEntry::query()->where('event_id', $event->id)->where('type', LedgerType::Income)->sum('amount');
        $expense = (int) LedgerEntry::query()->where('event_id', $event->id)->where('type', LedgerType::Expense)->sum('amount');

        $this->info('Selesai.');
        $this->line('Pemasukan UMKM: '.number_format($boothIncome, 0, ',', '.'));
        $this->line('Uang masuk lain: '.number_format($otherIncome, 0, ',', '.'));
        $this->line('Pengeluaran: '.number_format($expense, 0, ',', '.'));
        $this->line('Saldo: '.number_format($boothIncome + $otherIncome - $expense, 0, ',', '.'));

        return self::SUCCESS;
    }

    private function syncLedger(int $eventId): void
    {
        LedgerEntry::query()->where('event_id', $eventId)->delete();

        $rows = [
            [LedgerType::Income, 'Sponsorship', 2100000, '2026-10-31', 'TOTAL PEMASUKAN TENAND SPONSOR'],
            [LedgerType::Income, 'Lainnya', 220000, '2026-11-01', 'UANG KEBERSIHAN PEDAGANG LIAR'],
            [LedgerType::Expense, 'Sewa peralatan', 750000, '2026-10-28', 'DP SEWA TENDA'],
            [LedgerType::Expense, 'Operasional', 200000, '2026-10-28', 'HOSTING DAN DOMAIN'],
            [LedgerType::Expense, 'Operasional', 500000, '2026-10-28', 'OPERASIONAL PEMASANGAN BENDERA DAN UMBUL-UMBUL CC MUJEB'],
            [LedgerType::Expense, 'Konsumsi', 149000, '2026-10-30', 'KONSUMSI 1 PATOK LAPAK'],
            [LedgerType::Expense, 'Operasional', 43000, '2026-10-30', 'PEMBELIAN PAKU USUK'],
            [LedgerType::Expense, 'Operasional', 40000, '2026-10-30', 'BELI RAFIA DAN PLASTIK PAK ANAM'],
            [LedgerType::Expense, 'Cetak', 16000, '2026-10-30', 'PRINT LABEL UMKM'],
            [LedgerType::Expense, 'Konsumsi', 137500, '2026-10-31', 'KONSUMSI 2 PERSIAPAN PASANG TENDA'],
            [LedgerType::Expense, 'Operasional', 14000, '2026-10-31', 'ROKOK PASANG TENDA'],
            [LedgerType::Expense, 'Sewa peralatan', 1265000, '2026-10-31', 'PELUNASAN SEWA TENDA'],
            [LedgerType::Expense, 'Transport', 800000, '2026-10-31', 'TRANSPORT'],
        ];

        $adminId = DB::table('users')->orderBy('id')->value('id');

        foreach ($rows as [$type, $category, $amount, $date, $description]) {
            LedgerEntry::query()->create([
                'event_id' => $eventId,
                'type' => $type,
                'category' => $category,
                'amount' => $amount,
                'occurred_on' => $date,
                'description' => $description,
                'recorded_by' => $adminId,
            ]);
        }
    }

    private function syncBoothIncome(int $eventId): void
    {
        $payments = Payment::query()
            ->where('status', PaymentStatus::Verified)
            ->whereHas('registration', fn ($query) => $query->where('event_id', $eventId))
            ->orderByDesc('amount')
            ->orderBy('id')
            ->get();

        $current = (int) $payments->sum('amount');
        $need = $current - self::UMKM_TARGET;

        if ($need === 0) {
            return;
        }

        if ($need < 0) {
            $first = $payments->first();
            if (! $first) {
                throw new \RuntimeException('Tidak ada pembayaran terverifikasi untuk dinaikkan.');
            }
            $this->raisePayment($first, abs($need));

            return;
        }

        foreach ($payments as $payment) {
            if ($need <= 0) {
                break;
            }

            $reducible = max(0, (int) $payment->amount - 50000);
            if ($reducible <= 0) {
                continue;
            }

            $cut = min($need, $reducible);
            $this->lowerPayment($payment, $cut);
            $need -= $cut;
        }

        if ($need > 0) {
            throw new \RuntimeException('Tidak cukup ruang untuk menurunkan total UMKM sebesar Rp'.number_format($need, 0, ',', '.').'.');
        }
    }

    private function lowerPayment(Payment $payment, int $cut): void
    {
        $newAmount = (int) $payment->amount - $cut;
        $payment->update(['amount' => $newAmount]);

        $registration = Registration::query()->find($payment->registration_id);
        if ($registration) {
            $registration->update(['grand_total' => $newAmount]);
        }
    }

    private function raisePayment(Payment $payment, int $add): void
    {
        $newAmount = (int) $payment->amount + $add;
        $payment->update(['amount' => $newAmount]);

        $registration = Registration::query()->find($payment->registration_id);
        if ($registration) {
            $registration->update(['grand_total' => $newAmount]);
        }
    }
}
