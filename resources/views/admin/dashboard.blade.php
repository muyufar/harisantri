@extends('layouts.admin')

@section('content')
    @if ($defaultPassword)
        <p class="mb-3 rounded-xl bg-gold-soft px-4 py-2 text-sm">Kata sandi bawaan masih aktif. Ganti segera di menu Akun panitia.</p>
    @endif

    @php
        $stallTotal = max(0, (int) $stats['umkm_total']);
        $filled = (int) $stats['occupied'] + (int) $stats['held'];
        $confirmedPeople = (int) ($byStatus[\App\Enums\RegistrationStatus::Confirmed->value] ?? 0);
        $statusOrder = [
            \App\Enums\RegistrationStatus::AwaitingVerification,
            \App\Enums\RegistrationStatus::AwaitingPayment,
            \App\Enums\RegistrationStatus::PaymentRejected,
            \App\Enums\RegistrationStatus::Confirmed,
            \App\Enums\RegistrationStatus::Expired,
            \App\Enums\RegistrationStatus::Cancelled,
        ];
    @endphp

    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
        <h1 class="text-xl font-bold">Ringkasan</h1>
        <p class="text-sm text-slate-500">{{ $event->name }}@if ($event->starts_at) · {{ $event->starts_at->timezone('Asia/Jakarta')->format('d/m/Y') }}@endif</p>
    </div>

    <div class="mt-3 overflow-hidden rounded-xl bg-white">
        <div class="grid divide-y divide-slate-100 lg:grid-cols-2 lg:divide-x lg:divide-y-0">
            <section class="px-4 py-3">
                <h2 class="text-sm font-semibold">Pembayaran</h2>
                <dl class="mt-2 grid grid-cols-2 gap-3">
                    <div>
                        <dt class="text-xs text-slate-500">Pemasukan diterima</dt>
                        <dd class="text-lg font-semibold leading-tight">{{ rupiah($income) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Belum lunas</dt>
                        <dd class="text-lg font-semibold leading-tight">{{ rupiah($outstanding) }}</dd>
                    </div>
                </dl>
                <div class="mt-2 flex h-1.5 overflow-hidden rounded-full bg-gold" role="img" aria-label="Perbandingan pemasukan dan tagihan belum lunas">
                    <div class="bg-nu" style="flex: {{ (int) $income }} 1 0"></div>
                    <div style="flex: {{ max(0, (int) $outstanding) }} 1 0"></div>
                </div>
                <p class="mt-2 text-sm">
                    @if (auth()->user()->canPermission('registrations.view') && $pendingPayments > 0)
                        <a class="font-medium text-nu" href="{{ route('admin.registrations.index', ['status' => \App\Enums\RegistrationStatus::AwaitingVerification->value]) }}">{{ $pendingPayments }} bukti menunggu dicek</a>
                    @else
                        {{ $pendingPayments }} bukti menunggu dicek
                    @endif
                </p>
            </section>

            <section class="px-4 py-3">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="text-sm font-semibold">Pendaftaran</h2>
                    <p class="text-xs text-slate-500">{{ $registrations }} UMKM · {{ $sponsorships }} sponsor</p>
                </div>
                <p class="mt-2 text-sm leading-6">
                    @foreach ($statusOrder as $status)
                        @php
                            $count = (int) ($byStatus[$status->value] ?? 0);
                        @endphp
                        @if (! $loop->first)<span class="text-slate-300"> · </span>@endif
                        @if (auth()->user()->canPermission('registrations.view'))
                            <a class="{{ $count > 0 && in_array($status, [\App\Enums\RegistrationStatus::AwaitingVerification, \App\Enums\RegistrationStatus::AwaitingPayment], true) ? 'font-semibold text-nu' : 'text-ink' }}" href="{{ route('admin.registrations.index', ['status' => $status->value]) }}">{{ $count }} {{ strtolower($status->label()) }}</a>
                        @else
                            <span>{{ $count }} {{ strtolower($status->label()) }}</span>
                        @endif
                    @endforeach
                </p>
                <p class="mt-1 text-sm text-slate-600">{{ $confirmedPeople }} pendaftar terkonfirmasi mengisi {{ $stats['occupied'] }} lapak. Pemakaian tenda panitia: {{ $tents }} slot.</p>
            </section>
        </div>

        <section class="border-t border-slate-100 px-4 py-3">
            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                <h2 class="text-sm font-semibold">Lapak UMKM</h2>
                <p class="text-sm text-slate-600">{{ $stats['occupied'] }} terisi · {{ $stats['held'] }} dipesan · {{ $stats['available'] }} kosong@if ((int) $stats['blocked'] > 0) · {{ $stats['blocked'] }} ditutup@endif · {{ $stallTotal }} slot, {{ $stallTotal > 0 ? (int) round($filled / $stallTotal * 100) : 0 }}% terpakai</p>
            </div>
            <div class="mt-2 flex h-2 overflow-hidden rounded-full bg-slate-200" role="img" aria-label="Isi lapak UMKM">
                <div class="bg-nu" style="flex: {{ (int) $stats['occupied'] }} 1 0"></div>
                <div class="bg-gold" style="flex: {{ (int) $stats['held'] }} 1 0"></div>
                @if ((int) $stats['blocked'] > 0)
                    <div class="bg-slate-500" style="flex: {{ (int) $stats['blocked'] }} 1 0"></div>
                @endif
            </div>
        </section>

        <section class="border-t border-slate-100 px-4 py-3">
            <h2 class="text-sm font-semibold">Kotak</h2>
            <div class="mt-2 overflow-x-auto">
                <table class="w-full min-w-[36rem] text-left text-sm">
                    <thead class="text-xs text-slate-500">
                        <tr>
                            <th class="py-1 pr-3 font-medium">Kotak</th>
                            <th class="py-1 pr-3 font-medium">Ukuran</th>
                            <th class="py-1 pr-3 font-medium">Slot</th>
                            <th class="py-1 pr-3 font-medium">Terisi</th>
                            <th class="py-1 pr-3 font-medium">Dipesan</th>
                            <th class="py-1 pr-3 font-medium">Kosong</th>
                            <th class="py-1 font-medium">Isi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($areas as $area)
                            @php
                                $areaTotal = (int) $area['total'];
                                $areaFilled = (int) $area['occupied'] + (int) $area['held'];
                            @endphp
                            <tr class="border-t border-slate-100">
                                <td class="py-1.5 pr-3 font-medium">{{ $area['name'] }}</td>
                                <td class="py-1.5 pr-3 text-slate-600">{{ $area['size_label'] }}</td>
                                <td class="py-1.5 pr-3">{{ $areaTotal }}</td>
                                <td class="py-1.5 pr-3">{{ $area['occupied'] }}</td>
                                <td class="py-1.5 pr-3">{{ $area['held'] }}</td>
                                <td class="py-1.5 pr-3 {{ (int) $area['available'] > 0 ? 'font-semibold' : 'text-slate-400' }}">{{ $area['available'] }}</td>
                                <td class="w-28 py-1.5">
                                    <div class="flex h-1.5 overflow-hidden rounded-full bg-slate-200">
                                        <div class="bg-nu" style="flex: {{ (int) $area['occupied'] }} 1 0"></div>
                                        <div class="bg-gold" style="flex: {{ (int) $area['held'] }} 1 0"></div>
                                        <div class="bg-slate-400" style="flex: {{ (int) $area['blocked'] }} 1 0"></div>
                                        <div style="flex: {{ max(0, $areaTotal - $areaFilled - (int) $area['blocked']) }} 1 0"></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
