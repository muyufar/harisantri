@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold">Zona</h1>
    <p class="mt-2 max-w-3xl text-sm text-slate-600">
        Untuk menutup kotak dari pilihan pendaftar, pakai tombol di Dashboard → Kotak.
        Centang Aktif di bawah juga mengatur tampilan yang sama.
    </p>
    <form method="POST" action="{{ route('admin.zones.store') }}" class="mt-4 grid gap-2 rounded-2xl bg-white p-4 md:grid-cols-5">
        @csrf
        <input class="rounded-xl border px-3 py-2" name="name" placeholder="Nama zona" required>
        <input class="rounded-xl border px-3 py-2" name="code" placeholder="KODE" required>
        <select class="rounded-xl border px-3 py-2" name="type"><option value="umkm">UMKM</option><option value="sponsorship">Sponsorship</option></select>
        <input class="rounded-xl border px-3 py-2" type="number" name="capacity" min="1" value="4" required>
        <button class="rounded-xl bg-nu px-3 py-2 text-sm font-semibold text-white">Tambah zona</button>
    </form>
    <div class="mt-4 space-y-3">
        @foreach ($zones as $zone)
            <form method="POST" action="{{ route('admin.zones.update', $zone) }}" class="grid gap-2 rounded-2xl bg-white p-4 md:grid-cols-5">
                @csrf @method('PATCH')
                <input class="rounded-xl border px-3 py-2" name="name" value="{{ $zone->name }}">
                <p class="self-center text-sm">
                    {{ $zone->code }} · {{ $zone->type }} · terpakai {{ $zone->booths_count }}
                    @if ($zone->type === 'umkm')
                        · <span class="font-semibold {{ $zone->is_active ? 'text-nu' : 'text-nu-dark' }}">{{ $zone->is_active ? 'Tampil di daftar' : 'Disembunyikan' }}</span>
                    @endif
                </p>
                <input class="rounded-xl border px-3 py-2" type="number" name="capacity" min="1" value="{{ $zone->capacity }}">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($zone->is_active)> Tampil untuk pendaftar</label>
                <button class="rounded-xl border px-3 py-2 text-sm">Simpan</button>
            </form>
        @endforeach
    </div>
@endsection
