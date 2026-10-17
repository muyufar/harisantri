@extends('layouts.public')

@section('content')
    <section class="mx-auto max-w-xl px-4 py-20 text-center">
        <h1 class="text-3xl font-bold">Tautan tidak berlaku</h1>
        <p class="mt-3 text-slate-600">Buka kembali status dengan kode pendaftaran dan nomor WhatsApp.</p>
        <a class="mt-6 inline-flex rounded-xl bg-nu px-5 py-3 font-semibold text-white" href="{{ route('status.create') }}">Cek pendaftaran</a>
    </section>
@endsection
