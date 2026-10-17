@extends('layouts.public')

@section('title', 'Pilih Lapak')

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-10">
        <p class="text-sm font-semibold text-nu">Langkah 2 dari 4</p>
        <h1 class="mt-2 text-3xl font-bold">Pilih lapak</h1>
        <p class="mt-2 text-sm text-slate-600">Denah hanya gambaran letak. Centang lapak yang masih tersedia pada daftar. Slot belum dikunci sampai Anda menekan konfirmasi. Sisa kuota nomor ini: {{ $remaining }} dari maksimal {{ $event->config()->maxSlots() }} slot aktif ({{ $active }} sudah terpakai).</p>
        @if ($errors->any())
            <div class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif
        @if ($remaining < 1)
            <p class="mt-4 rounded-xl bg-gold-soft px-4 py-3 text-sm">Kuota 3 slot untuk nomor WhatsApp ini sudah terpakai pada pesanan aktif.</p>
        @else
            <form method="POST" action="{{ route('register.booths.store') }}" class="mt-6 space-y-4" x-data="formState" @submit="sending = true">
                @csrf
                <x-booth-map :payload="$map" />
                <button class="w-full rounded-xl bg-nu px-5 py-3 font-semibold text-white sm:w-auto" :class="sending && 'opacity-60'">Lanjut ke ringkasan</button>
            </form>
        @endif
    </section>
@endsection
