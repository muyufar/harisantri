@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold">Slot lapak</h1>
    <form method="POST" action="{{ route('admin.booths.store') }}" class="mt-4 grid gap-2 rounded-2xl bg-white p-4 md:grid-cols-6">
        @csrf
        <select class="rounded-xl border px-3 py-2" name="zone_id" required>
            @foreach ($zones as $zone)
                <option value="{{ $zone->id }}">{{ $zone->name }}</option>
            @endforeach
        </select>
        <input class="rounded-xl border px-3 py-2" name="code" placeholder="U-301" required>
        <select class="rounded-xl border px-3 py-2" name="type">
            <option value="umkm">UMKM</option>
            <option value="sponsorship">Sponsorship</option>
        </select>
        <select class="rounded-xl border px-3 py-2" name="size">
            <option value="2x2">2 × 2</option>
            <option value="3x3">3 × 3</option>
        </select>
        <input class="rounded-xl border px-3 py-2" name="pos_x" placeholder="X %" value="10">
        <button class="rounded-xl bg-nu px-3 py-2 text-sm font-semibold text-white">Tambah</button>
    </form>
    <div class="mt-4 overflow-x-auto rounded-2xl bg-white">
        <table class="min-w-full text-sm">
            <thead class="text-left text-slate-500"><tr><th class="p-3">Kode</th><th>Zona</th><th>Tipe</th><th>Status dasar</th><th></th></tr></thead>
            <tbody>
                @foreach ($booths as $booth)
                    <tr class="border-t border-slate-100">
                        <td class="p-3">{{ $booth->code }}</td>
                        <td>{{ $booth->zone->name }}</td>
                        <td>{{ $booth->type }} {{ $booth->sizeLabel() }}</td>
                        <td>{{ $booth->state === 'blocked' ? 'Diblokir' : 'Aktif' }} · {{ $booth->statusLabel() }}</td>
                        <td class="space-x-2 p-3 text-right">
                            <form class="inline" method="POST" action="{{ route('admin.booths.update', $booth) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="state" value="{{ $booth->state === 'blocked' ? 'available' : 'blocked' }}">
                                <button class="text-nu">{{ $booth->state === 'blocked' ? 'Aktifkan' : 'Blokir' }}</button>
                            </form>
                            <form class="inline" method="POST" action="{{ route('admin.booths.destroy', $booth) }}" onsubmit="return confirm('Hapus slot ini?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $booths->links() }}</div>
@endsection
