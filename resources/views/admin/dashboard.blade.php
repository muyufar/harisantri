@extends('layouts.admin')

@section('content')
    @if ($defaultPassword)
        <p class="mb-4 rounded-xl bg-gold-soft px-4 py-3 text-sm">Kata sandi bawaan masih aktif. Ganti segera di menu Akun panitia.</p>
    @endif

    @php
        $stallTotal = max(0, (int) $stats['umkm_total']);
        $filled = (int) $stats['occupied'] + (int) $stats['held'];
        $fillPercent = $stallTotal > 0 ? (int) round($filled / $stallTotal * 100) : 0;
        $confirmedPeople = (int) ($byStatus[\App\Enums\RegistrationStatus::Confirmed->value] ?? 0);
        $needsAction = [
            [\App\Enums\RegistrationStatus::AwaitingVerification, 'Menunggu verifikasi', 'bg-gold-soft text-nu-dark'],
            [\App\Enums\RegistrationStatus::AwaitingPayment, 'Menunggu pembayaran', 'bg-slate-100 text-slate-700'],
            [\App\Enums\RegistrationStatus::PaymentRejected, 'Pembayaran ditolak', 'bg-red-50 text-red-700'],
        ];
        $otherStatuses = [
            [\App\Enums\RegistrationStatus::Confirmed, 'Terkonfirmasi'],
            [\App\Enums\RegistrationStatus::Expired, 'Kedaluwarsa'],
            [\App\Enums\RegistrationStatus::Cancelled, 'Dibatalkan'],
        ];
    @endphp

    <header class="mb-4">
        <h1 class="text-2xl font-bold">Ringkasan</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $event->name }}@if ($event->starts_at) · {{ $event->starts_at->timezone('Asia/Jakarta')->format('d/m/Y') }}@endif</p>
    </header>

    @if (auth()->user()->canPermission('settings.manage'))
        @php
            $paused = $event->config()->registrationPaused();
        @endphp
        <div class="mb-4 flex flex-col gap-3 rounded-2xl border-2 p-4 sm:flex-row sm:items-center sm:justify-between {{ $paused ? 'border-gold bg-gold-soft' : 'border-nu bg-nu-soft' }}">
            <div>
                <p class="text-base font-bold {{ $paused ? 'text-nu-dark' : 'text-nu' }}">
                    Pendaftaran publik: {{ $paused ? 'Sementara ditutup' : 'Sedang dibuka' }}
                </p>
                <p class="mt-1 text-sm text-slate-600">
                    @if ($paused)
                        Form daftar UMKM &amp; sponsorship tidak bisa diakses. Pembayaran yang sudah ada tetap berjalan.
                    @else
                        Klik tombol di kanan untuk menutup sementara tanpa mengubah tanggal tutup otomatis.
                    @endif
                </p>
            </div>
            <form method="POST" action="{{ route('admin.settings.registration') }}" class="shrink-0">
                @csrf
                <input type="hidden" name="pause" value="{{ $paused ? '0' : '1' }}">
                <button
                    type="submit"
                    class="w-full rounded-xl px-5 py-3 text-sm font-bold sm:w-auto {{ $paused ? 'bg-nu text-white' : 'bg-nu-dark text-white' }}"
                    @if (! $paused) onclick="return confirm('Tutup sementara pendaftaran publik?')" @endif
                >
                    {{ $paused ? 'Buka kembali pendaftaran' : 'Tutup sementara pendaftaran' }}
                </button>
            </form>
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-2xl bg-white p-4">
            <h2 class="text-base font-bold">Pembayaran</h2>
            <div class="mt-3 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-nu-soft px-3 py-3">
                    <p class="text-xs font-medium text-nu-dark/80">Pemasukan diterima</p>
                    <p class="mt-1 text-xl font-bold tracking-tight text-nu-dark">{{ rupiah($income) }}</p>
                </div>
                <div class="rounded-xl bg-gold-soft px-3 py-3">
                    <p class="text-xs font-medium text-nu-dark/80">Belum lunas</p>
                    <p class="mt-1 text-xl font-bold tracking-tight text-nu-dark">{{ rupiah($outstanding) }}</p>
                </div>
            </div>
            <div class="mt-3 flex h-2.5 overflow-hidden rounded-full bg-gold" role="img" aria-label="Perbandingan pemasukan dan tagihan belum lunas">
                <div class="bg-nu" style="flex: {{ (int) $income }} 1 0"></div>
                <div style="flex: {{ max(0, (int) $outstanding) }} 1 0"></div>
            </div>
            <p class="mt-2 text-sm text-slate-600">
                @if (auth()->user()->canPermission('registrations.view') && $pendingPayments > 0)
                    <a class="font-semibold text-nu" href="{{ route('admin.registrations.index', ['status' => \App\Enums\RegistrationStatus::AwaitingVerification->value]) }}">{{ $pendingPayments }} bukti menunggu dicek</a>
                @else
                    {{ $pendingPayments }} bukti menunggu dicek
                @endif
            </p>
        </section>

        <section class="rounded-2xl bg-white p-4">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="text-base font-bold">Pendaftaran</h2>
                <p class="text-xs text-slate-500">{{ $registrations }} UMKM · {{ $sponsorships }} sponsor</p>
            </div>
            <div class="mt-3 grid grid-cols-3 gap-2">
                @foreach ($needsAction as [$status, $label, $tone])
                    @php
                        $count = (int) ($byStatus[$status->value] ?? 0);
                    @endphp
                    @if (auth()->user()->canPermission('registrations.view'))
                        <a class="rounded-xl {{ $tone }} px-3 py-2.5 transition hover:opacity-90" href="{{ route('admin.registrations.index', ['status' => $status->value]) }}">
                            <p class="text-xl font-bold leading-none">{{ $count }}</p>
                            <p class="mt-1 text-[11px] leading-tight">{{ $label }}</p>
                        </a>
                    @else
                        <div class="rounded-xl {{ $tone }} px-3 py-2.5">
                            <p class="text-xl font-bold leading-none">{{ $count }}</p>
                            <p class="mt-1 text-[11px] leading-tight">{{ $label }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="mt-3 grid grid-cols-3 gap-2 text-sm">
                @foreach ($otherStatuses as [$status, $label])
                    @php
                        $count = (int) ($byStatus[$status->value] ?? 0);
                    @endphp
                    <div class="rounded-xl border border-slate-100 px-3 py-2">
                        @if (auth()->user()->canPermission('registrations.view'))
                            <a class="block" href="{{ route('admin.registrations.index', ['status' => $status->value]) }}">
                                <p class="text-lg font-bold leading-none">{{ $count }}</p>
                                <p class="mt-1 text-[11px] text-slate-500">{{ $label }}</p>
                            </a>
                        @else
                            <p class="text-lg font-bold leading-none">{{ $count }}</p>
                            <p class="mt-1 text-[11px] text-slate-500">{{ $label }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-sm text-slate-600">{{ $confirmedPeople }} pendaftar terkonfirmasi mengisi {{ $stats['occupied'] }} lapak · tenda panitia {{ $tents }} slot</p>
        </section>
    </div>

    <section class="mt-4 rounded-2xl bg-white p-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-base font-bold">Lapak UMKM</h2>
                <p class="mt-1 text-sm text-slate-600">
                    {{ $stats['occupied'] }} terisi · {{ $stats['held'] }} dipesan · {{ $stats['available'] }} kosong
                    @if ((int) $stats['blocked'] > 0)
                        · {{ $stats['blocked'] }} ditutup
                    @endif
                </p>
            </div>
            <p class="text-3xl font-bold text-nu">{{ $fillPercent }}%</p>
        </div>
        <div class="mt-3 flex h-3 overflow-hidden rounded-full bg-slate-100" role="img" aria-label="Isi lapak UMKM">
            <div class="bg-nu" style="flex: {{ (int) $stats['occupied'] }} 1 0"></div>
            <div class="bg-gold" style="flex: {{ (int) $stats['held'] }} 1 0"></div>
            <div class="bg-slate-300" style="flex: {{ (int) $stats['available'] }} 1 0"></div>
            @if ((int) $stats['blocked'] > 0)
                <div class="bg-slate-500" style="flex: {{ (int) $stats['blocked'] }} 1 0"></div>
            @endif
        </div>
        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-nu"></span>Terkonfirmasi {{ $stats['occupied'] }}</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-gold"></span>Dipesan {{ $stats['held'] }}</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-slate-300"></span>Kosong {{ $stats['available'] }}</span>
            <span>{{ $stallTotal }} slot total</span>
        </div>
    </section>

    <section class="mt-4 rounded-2xl bg-white p-4">
        <h2 class="text-base font-bold">Kotak</h2>
        <div class="mt-3 grid gap-2.5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($areas as $area)
                @php
                    $areaTotal = (int) $area['total'];
                    $areaFilled = (int) $area['occupied'] + (int) $area['held'];
                    $areaFree = (int) $area['available'];
                @endphp
                <article class="rounded-xl border border-slate-100 px-3 py-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold">{{ $area['name'] }}</p>
                            <p class="text-xs text-slate-500">{{ $area['size_label'] }}</p>
                        </div>
                        <p class="rounded-lg px-2 py-1 text-xs font-semibold {{ $areaFree > 0 ? 'bg-nu-soft text-nu-dark' : 'bg-slate-100 text-slate-500' }}">
                            @if ($areaTotal === 0)
                                —
                            @elseif ($areaFree === 0)
                                Penuh
                            @else
                                Sisa {{ $areaFree }}
                            @endif
                        </p>
                    </div>
                    <div class="mt-2.5 flex h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="bg-nu" style="flex: {{ (int) $area['occupied'] }} 1 0"></div>
                        <div class="bg-gold" style="flex: {{ (int) $area['held'] }} 1 0"></div>
                        <div class="bg-slate-400" style="flex: {{ (int) $area['blocked'] }} 1 0"></div>
                        <div style="flex: {{ max(0, $areaTotal - $areaFilled - (int) $area['blocked']) }} 1 0"></div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">{{ $area['occupied'] }} terisi · {{ $area['held'] }} dipesan · {{ $areaTotal }} slot</p>
                </article>
            @endforeach
        </div>
    </section>
@endsection
