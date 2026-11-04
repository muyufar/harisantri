<?php

namespace Tests\Feature;

use App\Enums\LedgerType;
use App\Enums\PaymentStatus;
use App\Models\Booth;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\HsnTestCase;

class SyncFinanceReportTest extends HsnTestCase
{
    public function test_command_aligns_cash_book_with_the_official_finance_report(): void
    {
        $event = Event();
        $booths = Booth::query()->where('type', 'umkm')->limit(3)->get();
        $booking = app(\App\Services\BookingService::class);
        $payments = app(\App\Services\PaymentService::class);
        $admin = User::query()->where('email', 'admin@pcnu-magelang.test')->firstOrFail();

        foreach ([
            ['081211100001', $booths[0], 100000],
            ['081211100002', $booths[1], 150000],
            ['081211100003', $booths[2], 200000],
        ] as [$phone, $booth, $ignored]) {
            $registration = $booking->create($event, $this->applicant($phone, 'Usaha '.$phone), [
                ['booth_id' => $booth->id, 'with_tent' => $booth->size === '3x3'],
            ]);
            $registration->payment->update([
                'status' => PaymentStatus::Submitted,
                'submitted_at' => now(),
                'proof_path' => 'payment-proofs/demo.jpg',
            ]);
            $registration->update(['status' => \App\Enums\RegistrationStatus::AwaitingVerification]);
            $payments->approve($registration->fresh(), $admin);
        }

        LedgerEntry::query()->create([
            'event_id' => $event->id,
            'type' => LedgerType::Income,
            'category' => 'Donasi',
            'amount' => 999,
            'occurred_on' => '2026-10-01',
            'description' => 'Akan diganti',
            'recorded_by' => $admin->id,
        ]);

        Artisan::call('hsn:sync-finance-report', ['--force' => true]);

        $boothIncome = (int) Payment::query()
            ->where('status', PaymentStatus::Verified)
            ->whereHas('registration', fn ($query) => $query->where('event_id', $event->id))
            ->sum('amount');
        $otherIncome = (int) LedgerEntry::query()->where('event_id', $event->id)->where('type', LedgerType::Income)->sum('amount');
        $expense = (int) LedgerEntry::query()->where('event_id', $event->id)->where('type', LedgerType::Expense)->sum('amount');

        $this->assertSame(10150000, $boothIncome);
        $this->assertSame(2320000, $otherIncome);
        $this->assertSame(3914500, $expense);
        $this->assertSame(8555500, $boothIncome + $otherIncome - $expense);
        $this->assertDatabaseHas('ledger_entries', [
            'description' => 'TOTAL PEMASUKAN TENAND SPONSOR',
            'amount' => 2100000,
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'description' => 'UANG KEBERSIHAN PEDAGANG LIAR',
            'amount' => 220000,
        ]);
        $this->assertDatabaseMissing('ledger_entries', ['description' => 'Akan diganti']);
    }
}
