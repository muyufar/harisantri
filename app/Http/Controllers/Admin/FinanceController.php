<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LedgerType;
use App\Enums\PaymentStatus;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceController extends AdminController
{
    public function index(Request $request)
    {
        $event = $this->event();
        $type = $request->string('type')->toString();
        if (! in_array($type, ['income', 'expense'], true)) {
            $type = '';
        }

        $entries = LedgerEntry::query()
            ->with('recorder')
            ->where('event_id', $event->id)
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $otherIncome = (int) LedgerEntry::query()
            ->where('event_id', $event->id)
            ->where('type', LedgerType::Income)
            ->sum('amount');
        $expense = (int) LedgerEntry::query()
            ->where('event_id', $event->id)
            ->where('type', LedgerType::Expense)
            ->sum('amount');
        $boothIncome = (int) Payment::query()
            ->where('status', PaymentStatus::Verified)
            ->whereHas('registration', fn ($query) => $query->where('event_id', $event->id))
            ->sum('amount');

        return view('admin.finance.index', [
            'entries' => $entries,
            'boothIncome' => $boothIncome,
            'otherIncome' => $otherIncome,
            'expense' => $expense,
            'balance' => $boothIncome + $otherIncome - $expense,
            'categories' => [
                LedgerType::Income->value => LedgerType::Income->categories(),
                LedgerType::Expense->value => LedgerType::Expense->categories(),
            ],
        ]);
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $event = $this->event();
        $data = $request->validate([
            'type' => ['required', Rule::enum(LedgerType::class)],
            'category' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'occurred_on' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
        ]);

        $type = LedgerType::from($data['type']);
        if (! in_array($data['category'], $type->categories(), true)) {
            return back()->withErrors(['category' => 'Kategori tidak sesuai jenis catatan.'])->withInput();
        }

        $entry = LedgerEntry::query()->create([
            'event_id' => $event->id,
            'type' => $type,
            'category' => $data['category'],
            'amount' => $data['amount'],
            'occurred_on' => $data['occurred_on'],
            'description' => $data['description'],
            'recorded_by' => $request->user()->id,
        ]);

        $audit->log('ledger.created', $entry, [
            'type' => $type->value,
            'amount' => $entry->amount,
            'category' => $entry->category,
        ]);

        return back()->with(
            'status',
            $type === LedgerType::Income
                ? 'Uang masuk selain UMKM dicatat.'
                : 'Pengeluaran dicatat.'
        );
    }

    public function destroy(LedgerEntry $entry, AuditLogger $audit)
    {
        abort_unless($entry->event_id === $this->event()->id, 404);

        $audit->log('ledger.deleted', $entry, [
            'type' => $entry->type->value,
            'amount' => $entry->amount,
            'category' => $entry->category,
            'description' => $entry->description,
        ]);
        $entry->delete();

        return back()->with('status', 'Catatan keuangan dihapus.');
    }
}
