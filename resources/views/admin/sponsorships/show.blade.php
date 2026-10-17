@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold">{{ $entry->code }}</h1>
    <p>{{ $entry->company_name }} · {{ $entry->pic_name }} · {{ $entry->status->label() }}</p>
    <p class="mt-2 text-sm">{{ $entry->business_type }} · area {{ $entry->area_requirement }}</p>
    <p class="text-sm">{{ $entry->facility_needs }}</p>
    <p class="text-sm">{{ $entry->notes }}</p>
    <a class="mt-3 inline-flex rounded-xl bg-nu px-4 py-2 text-sm font-semibold text-white" href="{{ \App\Support\Phone::whatsappUrl($entry->whatsapp, 'Halo '.$entry->pic_name.', terkait sponsorship '.$entry->code) }}">WhatsApp PIC</a>

    <form method="POST" action="{{ route('admin.sponsorships.offer', $entry) }}" class="mt-6 grid gap-3 rounded-2xl bg-white p-4 md:grid-cols-2">
        @csrf
        <label class="text-sm">Nominal penawaran<input class="mt-1 w-full rounded-xl border px-3 py-2" type="number" name="amount" min="0" required></label>
        <label class="text-sm">Catatan lokasi<input class="mt-1 w-full rounded-xl border px-3 py-2" name="location_note"></label>
        <label class="text-sm md:col-span-2">Catatan internal<textarea class="mt-1 w-full rounded-xl border px-3 py-2" name="notes"></textarea></label>
        <button class="rounded-xl bg-nu px-4 py-2 text-sm font-semibold text-white">Simpan penawaran</button>
    </form>
    <ul class="mt-4 space-y-2 text-sm">
        @foreach ($entry->offers as $offer)
            <li class="rounded-xl bg-white px-4 py-3">{{ rupiah($offer->amount) }} · {{ $offer->location_note }} · {{ $offer->user->name }}</li>
        @endforeach
    </ul>

    <form method="POST" action="{{ route('admin.sponsorships.approve', $entry) }}" class="mt-4 flex flex-col gap-2 rounded-2xl bg-white p-4 sm:flex-row">
        @csrf
        <select class="flex-1 rounded-xl border px-3 py-2" name="booth_id" required>
            @foreach ($booths as $booth)
                <option value="{{ $booth->id }}">{{ $booth->code }} · {{ $booth->statusLabel() }}</option>
            @endforeach
        </select>
        <button class="rounded-xl bg-gold px-4 py-2 text-sm font-semibold">Setujui dan tetapkan lokasi</button>
    </form>

    <form method="POST" action="{{ route('admin.sponsorships.status', $entry) }}" class="mt-4 flex gap-2">
        @csrf
        <select class="rounded-xl border px-3 py-2" name="status">
            @foreach ($statuses as $status)
                @if ($status->value !== 'approved')
                    <option value="{{ $status->value }}" @selected($entry->status === $status)>{{ $status->label() }}</option>
                @endif
            @endforeach
        </select>
        <button class="rounded-xl border px-4 py-2 text-sm">Ubah status</button>
    </form>
@endsection
