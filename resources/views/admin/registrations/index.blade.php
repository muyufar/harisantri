@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold">Pendaftar UMKM</h1>
    <form class="mt-4 flex flex-col gap-2 sm:flex-row" method="GET">
        <input class="flex-1 rounded-xl border border-slate-200 px-3 py-2" name="q" value="{{ request('q') }}" placeholder="Nama, NIK, WhatsApp, lapak, atau kode">
        <select class="rounded-xl border border-slate-200 px-3 py-2" name="status">
            <option value="">Semua status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <button class="rounded-xl bg-nu px-4 py-2 text-sm font-semibold text-white">Cari</button>
    </form>
    <div class="mt-4 overflow-x-auto rounded-2xl bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="text-slate-500"><tr><th class="p-3">Kode</th><th>Usaha</th><th>Lapak</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($registrations as $registration)
                    <tr class="border-t border-slate-100">
                        <td class="p-3"><a class="font-semibold text-nu" href="{{ route('admin.registrations.show', $registration) }}">{{ $registration->code }}</a></td>
                        <td>{{ $registration->applicant->business_name }}</td>
                        <td>{{ $registration->items->pluck('zone_name')->unique()->join(', ') }}</td>
                        <td>{{ rupiah($registration->grand_total) }}</td>
                        <td>{{ $registration->status->label() }}</td>
                    </tr>
                @empty
                    <tr><td class="p-4 text-slate-500" colspan="5">Belum ada pendaftar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $registrations->links() }}</div>
@endsection
