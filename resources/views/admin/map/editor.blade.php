@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold">Editor peta</h1>
    <p class="mt-2 max-w-2xl text-sm text-slate-600">Pengunjung melihat gambar denah dan memilih lapak dari daftar. Kotak di sini hanya untuk panitia: geser bila posisi perlu disesuaikan, lalu simpan.</p>
    <div class="mt-4">
        <x-booth-map :payload="$map" />
    </div>
@endsection
