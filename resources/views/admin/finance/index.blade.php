@extends('layouts.admin')

@section('title', 'Keuangan')

@section('content')
    <h1 class="text-2xl font-bold">Pencatatan keuangan</h1>
    <p class="mt-2 max-w-3xl text-sm text-slate-600">
        Pemasukan lapak UMKM masuk otomatis dari pembayaran yang sudah diterima.
        Uang lain, seperti donasi, sponsorship yang sudah cair, bantuan instansi, atau iuran, dicatat lewat formulir di bawah dan tidak tercampur dengan lapak.
    </p>

    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl bg-nu-soft p-4">
            <p class="text-xs font-medium text-nu-dark/80">Pemasukan lapak</p>
            <p class="mt-1 text-xl font-bold text-nu-dark">{{ rupiah($boothIncome) }}</p>
        </article>
        <article class="rounded-2xl bg-white p-4">
            <p class="text-xs font-medium text-slate-500">Uang masuk selain UMKM</p>
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

    <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <form method="POST" action="{{ route('admin.finance.store') }}" class="grid gap-3 rounded-2xl border-2 border-nu bg-white p-4">
            @csrf
            <input type="hidden" name="type" value="income">
            <div>
                <h2 class="font-bold text-nu">Uang masuk selain UMKM</h2>
                <p class="mt-1 text-sm text-slate-600">Donasi, sponsorship yang sudah diterima, bantuan instansi, iuran, atau penjualan lain.</p>
            </div>
            <label class="text-sm">
                Sumber
                <select class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" name="category" required>
                    @foreach ($categories['income'] as $category)
                        <option value="{{ $category }}" @selected(old('type', 'income') === 'income' && old('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm">
                Nominal
                <input class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" type="number" name="amount" min="1" value="{{ old('type') === 'income' ? old('amount') : '' }}" required>
            </label>
            <label class="text-sm">
                Tanggal diterima
                <input class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" type="date" name="occurred_on" value="{{ old('type') === 'income' ? old('occurred_on') : now()->timezone('Asia/Jakarta')->toDateString() }}" required>
            </label>
            <label class="text-sm">
                Dari siapa / keterangan
                <input class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" name="description" maxlength="500" value="{{ old('type') === 'income' ? old('description') : '' }}" placeholder="Contoh: Donasi MWCNU Mertoyudan" required>
            </label>
            <button class="rounded-xl bg-nu px-4 py-3 text-sm font-semibold text-white">Catat uang masuk</button>
        </form>

        <form method="POST" action="{{ route('admin.finance.store') }}" class="grid content-start gap-3 rounded-2xl bg-white p-4">
            @csrf
            <input type="hidden" name="type" value="expense">
            <div>
                <h2 class="font-bold">Pengeluaran</h2>
                <p class="mt-1 text-sm text-slate-600">Biaya panitia yang keluar dari kas kegiatan.</p>
            </div>
            <label class="text-sm">
                Kategori
                <select class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" name="category" required>
                    @foreach ($categories['expense'] as $category)
                        <option value="{{ $category }}" @selected(old('type') === 'expense' && old('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm">
                Nominal
                <input class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" type="number" name="amount" min="1" value="{{ old('type') === 'expense' ? old('amount') : '' }}" required>
            </label>
            <label class="text-sm">
                Tanggal
                <input class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" type="date" name="occurred_on" value="{{ old('type') === 'expense' ? old('occurred_on') : now()->timezone('Asia/Jakarta')->toDateString() }}" required>
            </label>
            <label class="text-sm">
                Keterangan
                <input class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" name="description" maxlength="500" value="{{ old('type') === 'expense' ? old('description') : '' }}" placeholder="Contoh: sewa sound system" required>
            </label>
            <button class="rounded-xl bg-nu-dark px-4 py-3 text-sm font-semibold text-white">Catat pengeluaran</button>
        </form>
    </div>

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
