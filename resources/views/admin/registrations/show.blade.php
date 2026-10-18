@extends('layouts.admin')

@section('content')
    <a class="text-sm text-nu" href="{{ route('admin.registrations.index') }}">Kembali</a>
    <h1 class="mt-2 text-2xl font-bold">{{ $registration->code }}</h1>
    <p>{{ $registration->status->label() }} · {{ rupiah($registration->grand_total) }}</p>
    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <article class="rounded-2xl bg-white p-4 text-sm">
            <p class="font-semibold">{{ $registration->applicant->business_name }}</p>
            <p>{{ $registration->applicant->full_name }}</p>
            <p>NIK {{ $registration->applicant->nik }}</p>
            <p>{{ \App\Support\Phone::display($registration->whatsapp) }}</p>
            <p>{{ $registration->applicant->email }}</p>
            <p class="mt-2">{{ $registration->applicant->address }}, {{ $registration->applicant->district }}, {{ $registration->applicant->regency }}</p>
            <p class="mt-2">{{ $registration->applicant->product_type }} — {{ $registration->applicant->product_description }}</p>
            @if ($registration->applicant->special_notes)
                <p class="mt-2">Catatan: {{ $registration->applicant->special_notes }}</p>
            @endif
            @if ($registration->applicant->logo_path)
                <a class="mt-2 inline-block text-nu" href="{{ route('admin.registrations.logo', $registration) }}">Lihat foto usaha</a>
            @endif
            <a class="mt-3 inline-flex rounded-xl bg-nu px-4 py-2 font-semibold text-white" href="{{ \App\Support\Phone::whatsappUrl($registration->whatsapp, 'Halo '.$registration->applicant->full_name.', terkait pendaftaran '.$registration->code) }}">WhatsApp pendaftar</a>
        </article>
        <article class="rounded-2xl bg-white p-4 text-sm">
            @foreach ($registration->items as $item)
                <p class="mb-2">{{ $item->zone_name }} · slot {{ $item->booth_code }} · {{ $item->sizeLabel() }} · {{ $item->with_tent ? 'pakai tenda' : 'tanpa tenda' }} · {{ rupiah($item->line_total) }}</p>
            @endforeach
            <p>Pembayaran: {{ $registration->payment?->status->label() }}</p>
            @if ($registration->payment?->proof_path)
                @php($proofUrl = route('admin.registrations.proof', $registration))
                <a class="mt-2 inline-flex rounded-xl bg-nu px-4 py-2 font-semibold text-white" href="{{ $proofUrl }}" target="_blank">Lihat bukti bayar</a>
                @if (! str_ends_with(strtolower($registration->payment->proof_path), '.pdf'))
                    <a class="mt-3 block" href="{{ $proofUrl }}" target="_blank">
                        <img class="max-h-80 rounded-xl border border-slate-200" alt="Bukti pembayaran {{ $registration->code }}" src="{{ $proofUrl }}">
                    </a>
                @endif
            @endif
            @foreach ($registration->payment?->verifications ?? [] as $verification)
                <p class="mt-2">{{ $verification->decision === 'approved' ? 'Diterima' : 'Ditolak' }} oleh {{ $verification->user->name }} @if($verification->reason) — {{ $verification->reason }} @endif</p>
            @endforeach
        </article>
    </div>
    @if (auth()->user()->canPermission('payments.verify') && $registration->status === \App\Enums\RegistrationStatus::AwaitingVerification)
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <form method="POST" action="{{ route('admin.registrations.approve', $registration) }}" onsubmit="return confirm('Terima pembayaran ini?')">
                @csrf
                <button class="rounded-xl bg-nu px-4 py-2 text-sm font-semibold text-white">Terima pembayaran</button>
            </form>
            <form method="POST" action="{{ route('admin.registrations.reject', $registration) }}" class="space-y-2">
                @csrf
                <textarea class="w-full rounded-xl border border-slate-200 px-3 py-2" name="reason" placeholder="Alasan penolakan" required></textarea>
                <button class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white">Tolak pembayaran</button>
            </form>
        </div>
    @endif
    @if (auth()->user()->canPermission('registrations.manage'))
        <form method="POST" action="{{ route('admin.registrations.cancel', $registration) }}" class="mt-4 space-y-2 rounded-2xl bg-white p-4" onsubmit="return confirm('Batalkan pendaftaran dan lepas slot?')">
            @csrf
            <textarea class="w-full rounded-xl border border-slate-200 px-3 py-2" name="note" placeholder="Catatan pembatalan"></textarea>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="refund" value="1"> Catat refund jika pembayaran sudah diterima</label>
            <button class="rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-700">Batalkan</button>
        </form>
    @endif
@endsection
