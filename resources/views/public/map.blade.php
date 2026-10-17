@extends('layouts.public')

@section('title', 'Peta Lapak')

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-10">
        <h1 class="text-3xl font-bold">Peta lokasi lapak</h1>
        <p class="mt-2 max-w-3xl text-sm text-slate-600">Gambar ini denah acara: UMKM 1 di sisi pohon, UMKM 2 di tengah, tenda biru untuk sponsorship, dan UMKM 3 di sampingnya. Start di tepi jalan sisi timur, finis di sisi barat. Status pemesanan ada di daftar di bawah gambar.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-4">
            <div class="rounded-2xl bg-white p-4 text-sm">Tersedia <strong>{{ $stats['available'] }}</strong></div>
            <div class="rounded-2xl bg-white p-4 text-sm">Dipesan <strong>{{ $stats['held'] }}</strong></div>
            <div class="rounded-2xl bg-white p-4 text-sm">Terisi <strong>{{ $stats['occupied'] }}</strong></div>
            <div class="rounded-2xl bg-white p-4 text-sm">Sponsor tersedia <strong>{{ $stats['sponsorship_available'] }}</strong></div>
        </div>
        <div class="mt-6">
            <x-booth-map :payload="$map" />
        </div>
    </section>
@endsection
