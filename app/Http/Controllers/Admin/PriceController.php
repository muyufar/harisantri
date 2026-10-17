<?php

namespace App\Http\Controllers\Admin;

use App\Models\PriceRule;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PriceController extends AdminController
{
    public function index()
    {
        return view('admin.prices.index', [
            'rules' => $this->event()->priceRules()->orderBy('size')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $event = $this->event();
        $data = $this->validated($request, $event->id);
        $rule = PriceRule::create($data + ['event_id' => $event->id, 'is_active' => true]);
        $audit->log('price.created', $rule, ['size' => $rule->size, 'place' => $rule->place_price, 'tent' => $rule->tent_price]);

        return back()->with('status', 'Harga ditambahkan. Pesanan yang sudah dibuat tidak berubah.');
    }

    public function update(Request $request, PriceRule $price, AuditLogger $audit)
    {
        abort_unless($price->event_id === $this->event()->id, 404);
        $before = [$price->place_price, $price->tent_price];
        $price->update($this->validated($request, $price->event_id, $price->id));
        $audit->log('price.updated', $price, [
            'size' => $price->size,
            'from' => $before,
            'to' => [$price->place_price, $price->tent_price],
        ]);

        return back()->with('status', 'Harga diperbarui. Riwayat transaksi tetap memakai nominal saat checkout.');
    }

    private function validated(Request $request, int $eventId, ?int $ignore = null): array
    {
        return $request->validate([
            'size' => ['required', 'in:2x2,3x3', Rule::unique('price_rules', 'size')->where('event_id', $eventId)->ignore($ignore)],
            'place_price' => ['required', 'integer', 'min:0'],
            'tent_price' => ['required', 'integer', 'min:0'],
        ]);
    }
}
