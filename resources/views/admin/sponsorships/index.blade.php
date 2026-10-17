@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold">Sponsorship</h1>
    <form class="mt-4 flex gap-2" method="GET">
        <input class="flex-1 rounded-xl border border-slate-200 px-3 py-2" name="q" value="{{ request('q') }}" placeholder="Cari instansi atau kode">
        <button class="rounded-xl bg-nu px-4 py-2 text-sm font-semibold text-white">Cari</button>
    </form>
    <div class="mt-4 overflow-x-auto rounded-2xl bg-white">
        <table class="min-w-full text-sm">
            <thead class="text-left text-slate-500"><tr><th class="p-3">Kode</th><th>Instansi</th><th>Status</th><th>Harga</th></tr></thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr class="border-t border-slate-100">
                        <td class="p-3"><a class="font-semibold text-nu" href="{{ route('admin.sponsorships.show', $entry) }}">{{ $entry->code }}</a></td>
                        <td>{{ $entry->company_name }}</td>
                        <td>{{ $entry->status->label() }}</td>
                        <td>{{ $entry->agreed_price ? rupiah($entry->agreed_price) : 'Belum ditetapkan' }}</td>
                    </tr>
                @empty
                    <tr><td class="p-4" colspan="4">Belum ada pengajuan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $entries->links() }}</div>
@endsection
