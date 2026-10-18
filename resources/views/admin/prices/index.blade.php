@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold">Harga UMKM</h1>
    <p class="mt-2 text-sm text-slate-600">Perubahan harga tidak mengubah pesanan yang sudah tercatat.</p>
    <div class="mt-4 space-y-3">
        @foreach ($rules as $rule)
            <form method="POST" action="{{ route('admin.prices.update', $rule) }}" class="grid gap-2 rounded-2xl bg-white p-4 md:grid-cols-4">
                @csrf @method('PATCH')
                <select class="rounded-xl border px-3 py-2" name="size">
                    <option value="2x2" @selected($rule->size === '2x2')>2 × 2 meter</option>
                    <option value="3x3" @selected($rule->size === '3x3')>3 × 3 meter</option>
                </select>
                <label class="text-sm">Tempat<input class="mt-1 w-full rounded-xl border px-3 py-2" type="number" name="place_price" value="{{ $rule->place_price }}" min="0"></label>
                <label class="text-sm">Tambahan tenda<input class="mt-1 w-full rounded-xl border px-3 py-2" type="number" name="tent_price" value="{{ $rule->tent_price }}" min="0"></label>
                <button class="rounded-xl bg-nu px-3 py-2 text-sm font-semibold text-white">Simpan</button>
            </form>
        @endforeach
    </div>
@endsection
