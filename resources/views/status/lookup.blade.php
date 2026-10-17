@extends('layouts.public')

@section('title', 'Cek Pendaftaran')

@section('content')
    <section class="mx-auto max-w-xl px-4 py-10">
        <h1 class="text-3xl font-bold">Cek pendaftaran</h1>
        <p class="mt-2 text-sm text-slate-600">Masukkan kode pendaftaran dan nomor WhatsApp yang dipakai saat mendaftar. Detail pembayaran hanya tampil jika keduanya sesuai.</p>
        <form method="POST" action="{{ route('status.store') }}" class="mt-6 space-y-4" x-data="formState" @submit="sending = true">
            @csrf
            @error('code')<p class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>@enderror
            <label class="block text-sm"><span class="mb-1 block font-medium">Kode pendaftaran</span>
                <input class="w-full rounded-xl border border-slate-200 px-3 py-3" name="code" value="{{ old('code') }}" required>
            </label>
            <label class="block text-sm"><span class="mb-1 block font-medium">Nomor WhatsApp</span>
                <input class="w-full rounded-xl border border-slate-200 px-3 py-3" name="whatsapp" required>
            </label>
            <button class="w-full rounded-xl bg-nu px-5 py-3 font-semibold text-white" :class="sending && 'opacity-60'">Lihat status</button>
        </form>
    </section>
@endsection
