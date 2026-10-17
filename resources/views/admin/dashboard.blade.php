@extends('layouts.admin')

@section('content')
    @if ($defaultPassword)
        <p class="mb-4 rounded-xl bg-gold-soft px-4 py-3 text-sm">Kata sandi bawaan masih aktif. Ganti segera di menu Akun panitia.</p>
    @endif
    <h1 class="text-2xl font-bold">Ringkasan</h1>
    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            'Lapak UMKM' => $stats['umkm_total'],
            'Tersedia' => $stats['available'],
            'Dipesan sementara' => $stats['held'],
            'Terkonfirmasi' => $stats['occupied'],
            'Pendaftaran' => $registrations,
            'Sponsorship' => $sponsorships,
            'Menunggu verifikasi' => $pendingPayments,
            'Pemasukan diterima' => rupiah($income),
            'Belum lunas' => rupiah($outstanding),
            'Pemakaian tenda' => $tents,
        ] as $label => $value)
            <article class="rounded-2xl bg-white p-4">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-xl font-bold">{{ $value }}</p>
            </article>
        @endforeach
    </div>
@endsection
