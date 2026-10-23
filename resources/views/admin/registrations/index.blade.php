@extends('layouts.admin')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Pendaftar UMKM</h1>
        @if (auth()->user()->canPermission('registrations.manage'))
            <a class="rounded-xl bg-nu px-4 py-2 text-sm font-semibold text-white" href="{{ route('admin.registrations.create') }}">Daftarkan peserta</a>
        @endif
    </div>
    <form class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap" method="GET">
        <input class="min-w-[12rem] flex-1 rounded-xl border border-slate-200 px-3 py-2" name="q" value="{{ request('q') }}" placeholder="Nama usaha, PIC, NIK, WhatsApp, email, kotak, slot, atau kode">
        <select class="rounded-xl border border-slate-200 px-3 py-2" name="kotak" onchange="this.form.requestSubmit()">
            <option value="">Semua kotak</option>
            @foreach ($areas as $number => $area)
                <option value="{{ $number }}" @selected((int) request('kotak') === $number)>{{ $area['name'] }}</option>
            @endforeach
        </select>
        <select class="rounded-xl border border-slate-200 px-3 py-2" name="tenda" onchange="this.form.requestSubmit()">
            <option value="">Semua tenda</option>
            <option value="1" @selected(request('tenda') === '1')>Pakai tenda</option>
            <option value="0" @selected(request('tenda') === '0')>Tanpa tenda</option>
        </select>
        <select class="rounded-xl border border-slate-200 px-3 py-2" name="multi" onchange="this.form.requestSubmit()">
            <option value="">Semua jumlah lapak</option>
            <option value="1" @selected(request('multi') === '1')>Lebih dari 1 lapak</option>
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

    @if (request('multi') === '1')
        <p class="mt-3 rounded-xl bg-gold-soft px-4 py-3 text-sm text-nu-dark">
            Menampilkan pendaftar aktif dengan nomor WhatsApp yang memesan lebih dari satu slot
            (satu kode atau beberapa kode). Ada <strong>{{ $multiWhatsappCount }}</strong> nomor WhatsApp.
        </p>
    @endif

    <div class="mt-4 overflow-x-auto rounded-2xl bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="text-slate-500">
                <tr>
                    <th class="p-3">No</th>
                    <th>Kode</th>
                    <th>Usaha</th>
                    <th>Lapak</th>
                    <th>Slot</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Bukti diunggah</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registrations as $registration)
                    <tr class="border-t border-slate-100 {{ $registration->is_multi_lapak ? 'bg-gold-soft/40' : '' }}">
                        <td class="p-3 text-slate-500">{{ $registrations->firstItem() + $loop->index }}</td>
                        <td><a class="font-semibold text-nu" href="{{ route('admin.registrations.show', $registration) }}">{{ $registration->code }}</a></td>
                        <td>
                            <div>{{ $registration->applicant->business_name }}</div>
                            <div class="text-xs text-slate-500">{{ \App\Support\Phone::display($registration->whatsapp) }}</div>
                        </td>
                        <td>{{ $registration->items->pluck('zone_name')->unique()->join(', ') }}</td>
                        <td class="whitespace-nowrap">
                            <span class="font-semibold">{{ $registration->items_count }}</span>
                            @if ($registration->is_multi_lapak)
                                <span class="ml-1 rounded-full bg-nu px-2 py-0.5 text-xs font-semibold text-white">WA {{ $registration->wa_slot_total }} slot</span>
                            @endif
                        </td>
                        <td>{{ rupiah($registration->grand_total) }}</td>
                        <td>{{ $registration->status->label() }}</td>
                        <td class="whitespace-nowrap">{{ $registration->payment?->submitted_at?->timezone('Asia/Jakarta')->format('d/m/Y H.i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td class="p-4 text-slate-500" colspan="8">Belum ada pendaftar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $registrations->links() }}</div>
@endsection
