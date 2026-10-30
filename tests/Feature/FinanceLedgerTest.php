<?php

namespace Tests\Feature;

use App\Enums\LedgerType;
use App\Models\LedgerEntry;
use App\Models\User;
use Tests\HsnTestCase;

class FinanceLedgerTest extends HsnTestCase
{
    public function test_finance_admin_can_record_income_and_expense(): void
    {
        $finance = User::query()->where('email', 'keuangan@pcnu-magelang.test')->firstOrFail();
        $field = User::query()->where('email', 'lapangan@pcnu-magelang.test')->firstOrFail();

        $this->actingAs($field)->get(route('admin.finance.index'))->assertForbidden();

        $this->actingAs($finance)
            ->get(route('admin.finance.index'))
            ->assertOk()
            ->assertSee('Pencatatan keuangan')
            ->assertSee('Pemasukan lapak');

        $this->actingAs($finance)
            ->post(route('admin.finance.store'), [
                'type' => 'income',
                'category' => 'Donasi',
                'amount' => 500000,
                'occurred_on' => '2026-10-30',
                'description' => 'Donasi panitia ranting',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->actingAs($finance)
            ->post(route('admin.finance.store'), [
                'type' => 'expense',
                'category' => 'Sewa peralatan',
                'amount' => 150000,
                'occurred_on' => '2026-10-30',
                'description' => 'Sewa sound system',
            ])
            ->assertRedirect();

        $this->actingAs($finance)
            ->get(route('admin.finance.index'))
            ->assertOk()
            ->assertSee('Donasi panitia ranting')
            ->assertSee('Sewa sound system')
            ->assertSee('Rp350.000', false);

        $income = LedgerEntry::query()->where('type', LedgerType::Income)->firstOrFail();
        $this->actingAs($finance)
            ->delete(route('admin.finance.destroy', $income))
            ->assertRedirect();

        $this->assertDatabaseMissing('ledger_entries', ['id' => $income->id]);
    }
}
