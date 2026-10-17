@extends('layouts.public')

@section('title', 'Pembayaran '.$registration->code)

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-10">
        <p class="text-sm font-semibold text-nu">Langkah 4</p>
        <h1 class="mt-2 text-3xl font-bold">{{ $registration->code }}</h1>
        <p class="mt-2">Status: <strong>{{ $registration->status->label() }}</strong></p>
        <p class="text-sm text-slate-600">{{ $registration->applicant->business_name }}</p>

        <div class="mt-6 space-y-3">
            @foreach ($registration->items as $item)
                <article class="rounded-2xl bg-white p-4 text-sm">
                    <p class="font-semibold">{{ $item->booth_code }} · {{ $item->zone_name }} · {{ $item->sizeLabel() }}</p>
                    <p>Tempat {{ rupiah($item->place_price) }} · tenda {{ rupiah($item->tent_price) }}</p>
                </article>
            @endforeach
        </div>
        <p class="mt-4 text-lg font-bold">Nominal transfer {{ rupiah($registration->grand_total) }}</p>

        @if ($registration->status !== \App\Enums\RegistrationStatus::Confirmed)
            <div class="mt-4 rounded-3xl bg-white p-5 text-sm">
                <p class="font-semibold">Transfer manual</p>
                <p class="mt-2">Bank: {{ $instructions['bank'] }}</p>
                <p>Nomor rekening: {{ $instructions['account_number'] }}</p>
                <p>Atas nama: {{ $instructions['account_holder'] }}</p>
                <p class="mt-2">{{ $instructions['note'] }}</p>
                @if ($registration->hold_expires_at && $registration->canUploadProof())
                    <p class="mt-2">Batas unggah bukti: {{ $registration->hold_expires_at->timezone('Asia/Jakarta')->translatedFormat('l, j F Y H.i') }} WIB.</p>
                @endif
            </div>
        @endif

        @if ($rejection)
            <p class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">Ditolak: {{ $rejection->reason }}</p>
        @endif

        @if ($registration->canUploadProof())
            <form method="POST" action="{{ URL::temporarySignedRoute('register.proof', now()->addDays(7), $registration) }}" enctype="multipart/form-data" class="mt-4 space-y-3" x-data="formState" @submit="sending = true">
                @csrf
                <label class="block text-sm">
                    <span class="mb-1 block font-medium">Bukti pembayaran JPG, PNG, atau PDF</span>
                    <input type="file" name="proof" accept="image/jpeg,image/png,application/pdf" required>
                </label>
                @error('proof')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                <button class="rounded-xl bg-nu px-5 py-3 font-semibold text-white" :class="sending && 'opacity-60'">Kirim bukti</button>
            </form>
        @endif

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <a class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-center text-sm font-semibold" href="{{ URL::temporarySignedRoute('register.pdf', now()->addDay(), [$registration, 'pengajuan']) }}">Unduh bukti pengajuan</a>
            @if ($registration->status === \App\Enums\RegistrationStatus::Confirmed)
                <a class="rounded-xl bg-nu px-5 py-3 text-center text-sm font-semibold text-white" href="{{ URL::temporarySignedRoute('register.pdf', now()->addDay(), [$registration, 'konfirmasi']) }}">Unduh bukti konfirmasi</a>
            @endif
            <a class="rounded-xl px-5 py-3 text-center text-sm font-semibold text-nu" href="{{ route('status.create') }}">Cek status</a>
        </div>

        @if ($qrImages)
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($registration->items as $item)
                    <article class="rounded-2xl bg-white p-4 text-center">
                        <p class="font-semibold">{{ $item->booth_code }}</p>
                        <img class="mx-auto mt-3 h-40 w-40" alt="QR {{ $item->booth_code }}" src="{{ $qrImages[$item->id]['image'] }}">
                        <p class="mt-2 text-xs text-slate-500">Tunjukkan QR ini kepada petugas saat check-in.</p>
                    </article>
                @endforeach
            </div>
        @else
            <p class="mt-6 text-sm text-slate-600">QR check-in muncul setelah pembayaran disetujui panitia.</p>
        @endif
    </section>
@endsection
