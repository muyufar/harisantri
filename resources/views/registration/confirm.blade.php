@extends('layouts.public')

@section('title', 'Konfirmasi Pendaftaran')

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-10">
        <p class="text-sm font-semibold text-nu">Langkah 3 dari 4</p>
        <h1 class="mt-2 text-3xl font-bold">Periksa pesanan</h1>
        <div class="mt-6 rounded-3xl bg-white p-5">
            <h2 class="font-semibold">{{ $applicant['business_name'] }}</h2>
            <p class="text-sm text-slate-600">{{ $applicant['full_name'] }} · {{ \App\Support\Phone::display($applicant['whatsapp']) }}</p>
            <p class="mt-2 text-sm">{{ $applicant['product_type'] }} — {{ $applicant['product_description'] }}</p>
            <p class="mt-2 text-sm text-slate-600">{{ $applicant['address'] }}, {{ $applicant['district'] }}, {{ $applicant['regency'] }}</p>
        </div>
        <div class="mt-4 space-y-3">
            @foreach ($quote['lines'] as $line)
                <article class="rounded-2xl bg-white p-4 text-sm">
                    <p class="font-semibold">{{ $line['code'] }} · {{ $line['zone'] }} · {{ $line['size'] }}</p>
                    <p>Tempat {{ rupiah($line['place_price']) }}</p>
                    <p>Tenda {{ $line['with_tent'] ? rupiah($line['tent_price']) : 'tidak diambil' }}</p>
                    <p class="font-semibold">Subtotal {{ rupiah($line['line_total']) }}</p>
                </article>
            @endforeach
        </div>
        <p class="mt-4 text-lg font-bold">Total pembayaran {{ rupiah($quote['grand_total']) }}</p>
        <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4" x-data="formState" @submit="sending = true">
            @csrf
            <label class="flex gap-3 text-sm">
                <input type="checkbox" name="terms" value="1" required>
                <span class="whitespace-pre-line">Saya menyetujui ketentuan berikut: {{ $event->config()->get('terms') }}</span>
            </label>
            @error('terms')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            <button class="w-full rounded-xl bg-nu px-5 py-3 font-semibold text-white" :class="sending && 'opacity-60'">Buat pesanan dan tahan slot</button>
        </form>
    </section>
@endsection
