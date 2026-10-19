@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold">Pendaftar UMKM</h1>
    <form class="mt-4 flex flex-col gap-2 sm:flex-row" method="GET">
        <input class="flex-1 rounded-xl border border-slate-200 px-3 py-2" name="q" value="{{ request('q') }}" placeholder="Nama, NIK, WhatsApp, lapak, atau kode">
        <select class="rounded-xl border border-slate-200 px-3 py-2" name="kotak" onchange="this.form.requestSubmit()">
            <option value="">Semua kotak</option>
            @foreach ($areas as $number => $area)
                <option value="{{ $number }}" @selected((int) request('kotak') === $number)>{{ $area['name'] }}</option>
            @endforeach
        </select>
        <select class="rounded-xl border border-slate-200 px-3 py-2" name="status">
            <option value="">Semua status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <label class="text-sm">
            <span class="mb-1 block font-medium sm:sr-only">Tampil</span>
            <select class="rounded-xl border border-slate-200 px-3 py-2" name="per_page" onchange="this.form.requestSubmit()">
                @foreach ([10, 50, 100, 200] as $size)
                    <option value="{{ $size }}" @selected($registrations->perPage() === $size)>{{ $size }} data</option>
                @endforeach
            </select>
        </label>
        <button class="rounded-xl bg-nu px-4 py-2 text-sm font-semibold text-white">Cari</button>
    </form>
    <div class="mt-4 overflow-x-auto rounded-2xl bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="text-slate-500"><tr><th class="p-3">Kode</th><th>Usaha</th><th>Lapak</th><th>Total</th><th>Status</th><th>Bukti diunggah</th></tr></thead>
            <tbody>
                @forelse ($registrations as $registration)
                    <tr class="border-t border-slate-100">
                        <td class="p-3"><a class="font-semibold text-nu" href="{{ route('admin.registrations.show', $registration) }}">{{ $registration->code }}</a></td>
                        <td>{{ $registration->applicant->business_name }}</td>
                        <td>{{ $registration->items->pluck('zone_name')->unique()->join(', ') }}</td>
                        <td>{{ rupiah($registration->grand_total) }}</td>
                        <td>{{ $registration->status->label() }}</td>
                        <td class="whitespace-nowrap">{{ $registration->payment?->submitted_at?->timezone('Asia/Jakarta')->format('d/m/Y H.i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td class="p-4 text-slate-500" colspan="6">Belum ada pendaftar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $registrations->links() }}</div>
@endsection
