<?php

namespace App\Enums;

enum LedgerType: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Pemasukan',
            self::Expense => 'Pengeluaran',
        };
    }

    /** @return array<int, string> */
    public function categories(): array
    {
        return match ($this) {
            self::Income => ['Donasi', 'Sponsorship', 'Bantuan instansi', 'Iuran', 'Penjualan lain', 'Lainnya'],
            self::Expense => ['Konsumsi', 'Sewa peralatan', 'Transport', 'Cetak', 'Tenda & dekorasi', 'Operasional', 'Lainnya'],
        };
    }
}
