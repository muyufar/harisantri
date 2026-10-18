@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold">Editor peta</h1>
    <p class="mt-2 max-w-2xl text-sm text-slate-600">Pengunjung memilih nomor area 1–8. Slot kecil di sini untuk panitia: geser bila posisi di dalam kotak perlu disesuaikan, lalu simpan.</p>
    <div class="mt-4">
        <x-booth-map :payload="$map" />
    </div>
@endsection
