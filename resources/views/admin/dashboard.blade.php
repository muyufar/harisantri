@extends('layouts.admin')

@section('content')
    @if ($defaultPassword)
        <p class="mb-4 rounded-xl bg-gold-soft px-4 py-3 text-sm">Kata sandi bawaan masih aktif. Ganti segera di menu Akun panitia.</p>
    @endif

    @php
        $stallTotal = max(0, (int) $stats['umkm_total']);
        $filled = (int) $stats['occupied'] + (int) $stats['held'];
        $moneyTotal = max(0, $income + $outstanding);
        $statusOrder = [
            \App\Enums\RegistrationStatus::Confirmed,
            \App\Enums\RegistrationStatus::AwaitingVerification,
            \App\Enums\RegistrationStatus::AwaitingPayment,
            \App\Enums\RegistrationStatus::PaymentRejected,
            \App\Enums\RegistrationStatus::Expired,
            \App\Enums\RegistrationStatus::Cancelled,
        ];
        $statusMax = max(1, ...array_map(fn ($status) => $byStatus[$status->value] ?? 0, $statusOrder));
    @endphp

    <h1 class="text-2xl font-bold">Ringkasan</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $event->name }}@if ($event->starts_at) · {{ $event->starts_at->timezone('Asia/Jakarta')->format('d/m/Y') }}@endif</p>

    <section class="mt-5 rounded-3xl bg-white p-5">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">Lapak UMKM</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $stats['occupied'] }} dari {{ $stallTotal }} lapak sudah terkonfirmasi. {{ $stats['available'] }} masih kosong, {{ $stats['held'] }} sedang dipesan.</p>
            </div>
            <p class="text-3xl font-bold text-nu">{{ $stallTotal > 0 ? (int) round($filled / $stallTotal * 100) : 0 }}%</p>
        </div>
        <div class="mt-4 flex h-4 overflow-hidden rounded-full bg-slate-100" role="img" aria-label="Isi lapak UMKM">
            <div class="bg-nu" style="flex: {{ (int) $stats['occupied'] }} 1 0"></div>
            <div class="bg-gold" style="flex: {{ (int) $stats['held'] }} 1 0"></div>
            <div class="bg-slate-300" style="flex: {{ (int) $stats['available'] }} 1 0"></div>
            @if ((int) $stats['blocked'] > 0)
                <div class="bg-slate-500" style="flex: {{ (int) $stats['blocked'] }} 1 0"></div>
            @endif
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Terkonfirmasi', $stats['occupied'], 'bg-nu'],
                ['Dipesan sementara', $stats['held'], 'bg-gold'],
                ['Tersedia', $stats['available'], 'bg-slate-300'],
                ['Tidak tersedia', $stats['blocked'], 'bg-slate-500'],
            ] as [$label, $value, $swatch])
                @continue($label === 'Tidak tersedia' && (int) $value === 0)
                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                    <p class="flex items-center gap-2 text-sm text-slate-500"><span class="h-2.5 w-2.5 rounded-full {{ $swatch }}"></span>{{ $label }}</p>
                    <p class="mt-1 text-2xl font-bold">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($areas as $area)
                @php
                    $areaTotal = (int) $area['total'];
                    $areaFilled = (int) $area['occupied'] + (int) $area['held'];
                @endphp
                <article class="rounded-2xl border border-slate-100 p-4">
                    <div class="flex items-baseline justify-between gap-2">
                        <p class="font-semibold">{{ $area['name'] }}</p>
                        <p class="text-sm text-slate-500">{{ $area['size_label'] }}</p>
                    </div>
                    <p class="mt-2 text-sm">
                        @if ($areaTotal === 0)
                            Belum ada slot
                        @elseif ((int) $area['available'] === 0 && (int) $area['held'] > 0)
                            Penuh · {{ $area['held'] }} sedang dipesan
                        @elseif ((int) $area['available'] === 0)
                            Penuh
                        @else
                            Sisa {{ $area['available'] }} dari {{ $areaTotal }}
                        @endif
                    </p>
                    <div class="mt-3 flex h-2.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="bg-nu" style="flex: {{ (int) $area['occupied'] }} 1 0"></div>
                        <div class="bg-gold" style="flex: {{ (int) $area['held'] }} 1 0"></div>
                        <div class="bg-slate-300" style="flex: {{ max(0, $areaTotal - $areaFilled - (int) $area['blocked']) }} 1 0"></div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">{{ $area['occupied'] }} terkonfirmasi · {{ $area['held'] }} dipesan</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="mt-4 grid gap-4 lg:grid-cols-2">
        <article class="rounded-3xl bg-white p-5">
            <h2 class="text-lg font-bold">Pembayaran</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl bg-nu-soft px-4 py-3">
                    <p class="text-sm text-nu-dark">Pemasukan diterima</p>
                    <p class="mt-1 text-2xl font-bold text-nu-dark">{{ rupiah($income) }}</p>
                </div>
                <div class="rounded-2xl bg-gold-soft px-4 py-3">
                    <p class="text-sm text-nu-dark">Belum lunas</p>
                    <p class="mt-1 text-2xl font-bold text-nu-dark">{{ rupiah($outstanding) }}</p>
                </div>
            </div>
            <div class="mt-4 flex h-3 overflow-hidden rounded-full bg-gold" role="img" aria-label="Perbandingan pemasukan dan tagihan belum lunas">
                <div class="bg-nu" style="flex: {{ $income }} 1 0"></div>
                <div style="flex: {{ max(0, $outstanding) }} 1 0"></div>
            </div>
            <p class="mt-2 text-sm text-slate-500">{{ $pendingPayments }} pendaftaran menunggu verifikasi bukti.</p>
        </article>

        <article class="rounded-3xl bg-white p-5">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="text-lg font-bold">Pendaftaran</h2>
                <p class="text-sm text-slate-500">{{ $registrations }} UMKM · {{ $sponsorships }} sponsor</p>
            </div>
            <div class="mt-4 space-y-3">
                @foreach ($statusOrder as $status)
                    @php($count = $byStatus[$status->value] ?? 0)
                    <div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            @if (auth()->user()->canPermission('registrations.view'))
                                <a class="font-medium text-nu" href="{{ route('admin.registrations.index', ['status' => $status->value]) }}">{{ $status->label() }}</a>
                            @else
                                <span class="font-medium">{{ $status->label() }}</span>
                            @endif
                            <span class="font-semibold">{{ $count }}</span>
                        </div>
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full {{ $status === \App\Enums\RegistrationStatus::AwaitingVerification && $count > 0 ? 'bg-gold' : 'bg-nu' }}" style="width: {{ (int) round($count / $statusMax * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="mt-4 rounded-2xl bg-slate-50 px-4 py-3 text-sm">Pemakaian tenda panitia: <span class="font-semibold">{{ $tents }} slot</span></p>
        </article>
    </section>
@endsection
