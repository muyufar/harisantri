@extends('layouts.public')

@section('title', $entry->code)

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-10">
        <h1 class="text-3xl font-bold">{{ $entry->code }}</h1>
        <p class="mt-2">Status: <strong>{{ $entry->status->label() }}</strong></p>
        <p class="text-sm text-slate-600">Pengajuan baru menunggu tindak lanjut panitia.</p>
        <div class="mt-6 rounded-3xl bg-white p-5 text-sm">
            <p class="font-semibold">{{ $entry->company_name }}</p>
            <p>{{ $entry->pic_name }} · {{ $entry->category?->name }}</p>
            <p class="mt-2">Kebutuhan area: {{ $entry->area_requirement }}</p>
            @if ($entry->agreed_price)
                <p class="mt-2">Harga yang ditetapkan panitia: {{ rupiah($entry->agreed_price) }}</p>
            @else
                <p class="mt-2">Belum ada harga. Nominal hanya diisi panitia.</p>
            @endif
            @if ($entry->booth)
                <p>Lokasi: {{ $entry->booth->code }}</p>
            @endif
        </div>
        <a class="mt-6 inline-flex rounded-xl bg-nu px-5 py-3 font-semibold text-white" href="{{ $whatsapp }}">Hubungi panitia</a>
    </section>
@endsection
