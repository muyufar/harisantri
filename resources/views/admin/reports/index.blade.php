@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold">Laporan</h1>
    <div class="mt-4 space-y-4">
        @foreach ([
            'registrants' => 'Pendaftar UMKM',
            'booths' => 'Penggunaan lapak',
            'finance' => 'Keuangan',
            'sponsorships' => 'Sponsorship',
        ] as $report => $label)
            <article class="rounded-2xl bg-white p-4">
                <h2 class="font-semibold">{{ $label }}</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach (['csv' => 'CSV', 'xlsx' => 'Excel', 'pdf' => 'PDF'] as $format => $name)
                        <a class="rounded-xl border px-3 py-2 text-sm" href="{{ route('admin.reports.download', ['report' => $report, 'format' => $format]) }}">{{ $name }}</a>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>
@endsection
