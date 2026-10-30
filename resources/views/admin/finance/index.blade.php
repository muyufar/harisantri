@extends('layouts.admin')

@section('title', 'Keuangan')

@section('content')
    <h1 class="text-2xl font-bold">Pencatatan keuangan</h1>
    <p class="mt-2 max-w-3xl text-sm text-slate-600">
        Pemasukan lapak diambil otomatis dari pembayaran yang sudah diterima. Donasi, sponsorship yang sudah cair, dan pengeluaran panitia dicatat di buku kas ini.
    </p>

    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl bg-nu-soft p-4">
            <p class="text-xs font-medium text-nu-dark/80">Pemasukan lapak</p>
            <p class="mt-1 text-xl font-bold text-nu-dark">{{ rupiah($boothIncome) }}</p>
        </article>
        <article class="rounded-2xl bg-white p-4">
            <p class="text-xs font-medium text-slate-500">Pemasukan lain</p>
            <p class="mt-1 text-xl font-bold">{{ rupiah($otherIncome) }}</p>
        </article>
        <article class="rounded-2xl bg-gold-soft p-4">
            <p class="text-xs font-medium text-nu-dark/80">Pengeluaran</p>
            <p class="mt-1 text-xl font-bold text-nu-dark">{{ rupiah($expense) }}</p>
        </article>
        <article class="rounded-2xl bg-nu-dark p-4 text-white">
            <p class="text-xs font-medium text-white/70">Saldo</p>
            <p class="mt-1 text-xl font-bold">{{ rupiah($balance) }}</p>
        </article>
    </div>

    <form
        method="POST"
        action="{{ route('admin.finance.store') }}"
        class="mt-4 grid gap-3 rounded-2xl bg-white p-4 md:grid-cols-2"
        x-data="{ type: @js(old('type', 'expense')), categories: @js($categories) }"
    >
        @csrf
        <label class="text-sm">
            Jenis
            <select class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" name="type" x-model="type" required>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm">
            Kategori
            <select class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" name="category" required>
                <template x-for="category in categories[type] || []" :key="category">
                    <option :value="category" x-text="category" :selected="category === @js(old('category'))"></option>
                </template>
            </select>
        </label>
        <label class="text-sm">
            Nominal
            <input class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" type="number" name="amount" min="1" value="{{ old('amount') }}" required>
        </label>
        <label class="text-sm">
            Tanggal
            <input class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" type="date" name="occurred_on" value="{{ old('occurred_on', now()->timezone('Asia/Jakarta')->toDateString()) }}" required>
        </label>
        <label class="text-sm md:col-span-2">
            Keterangan
            <input class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" name="description" maxlength="500" value="{{ old('description') }}" placeholder="Contoh: sewa sound system" required>
        </label>
        <button class="rounded-xl bg-nu px-4 py-3 text-sm font-semibold text-white md:col-span-2">Simpan catatan</button>
    </form>

    <form class="mt-4 flex gap-2" method="GET">
        <select class="rounded-xl border border-slate-200 px-3 py-2 text-sm" name="type" onchange="this.form.requestSubmit()">
            <option value="">Semua catatan</option>
            <option value="income" @selected(request('type') === 'income')>Pemasukan</option>
            <option value="expense" @selected(request('type') === 'expense')>Pengeluaran</option>
        </select>
    </form>

    <div class="mt-3 overflow-x-auto rounded-2xl bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="text-slate-500">
                <tr>
                    <th class="p-3">Tanggal</th>
                    <th>Jenis</th>
                    <th>Kategori</th>
                    <th>Keterangan</th>
                    <th>Nominal</th>
                    <th>Pencatat</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr class="border-t border-slate-100">
                        <td class="p-3 whitespace-nowrap">{{ $entry->occurred_on->format('d/m/Y') }}</td>
                        <td>{{ $entry->type->label() }}</td>
                        <td>{{ $entry->category }}</td>
                        <td>{{ $entry->description }}</td>
                        <td class="whitespace-nowrap font-semibold">{{ rupiah($entry->amount) }}</td>
                        <td>{{ $entry->recorder?->name ?? '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.finance.destroy', $entry) }}" onsubmit="return confirm('Hapus catatan ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-sm font-semibold text-nu-dark underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="p-4 text-slate-500" colspan="7">Belum ada catatan selain pemasukan lapak.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $entries->links() }}</div>
@endsection
