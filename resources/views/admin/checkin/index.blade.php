@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold">Check-in lapangan</h1>
    <form id="checkin-form" method="POST" action="{{ route('admin.checkin.store') }}" class="mt-4 grid gap-3 rounded-2xl bg-white p-4">
        @csrf
        <label class="text-sm">Hasil pindai QR<textarea class="mt-1 w-full rounded-xl border px-3 py-2" name="token" id="qr-token" rows="2">{{ old('token') }}</textarea></label>
        <div class="grid gap-3 md:grid-cols-2">
            <label class="text-sm">Kode pendaftaran<input class="mt-1 w-full rounded-xl border px-3 py-2" name="code" value="{{ old('code') }}"></label>
            <label class="text-sm">Nomor lapak<input class="mt-1 w-full rounded-xl border px-3 py-2" name="booth_code" value="{{ old('booth_code') }}"></label>
        </div>
        <div class="flex flex-wrap gap-2">
            <button class="rounded-xl bg-nu px-4 py-2 text-sm font-semibold text-white">Validasi</button>
            <button class="rounded-xl border px-4 py-2 text-sm" type="button" id="start-scan">Pindai kamera</button>
        </div>
        <video id="scan-video" class="hidden max-h-64 rounded-xl bg-black" autoplay playsinline></video>
    </form>
    @isset($result)
        <article class="mt-4 rounded-2xl bg-white p-4">
            <p class="font-semibold">{{ $created ? 'Check-in tercatat' : 'Sudah check-in sebelumnya' }}</p>
            <p class="mt-2">{{ $result->registration->applicant->business_name }} · {{ $result->booth_code }}</p>
            <p class="text-sm">{{ $checkin->checked_in_at->timezone('Asia/Jakarta')->translatedFormat('l, j F Y H.i') }} WIB oleh {{ $checkin->user->name }}</p>
        </article>
    @endisset
    <script>
        document.getElementById('start-scan')?.addEventListener('click', async () => {
            if (!('BarcodeDetector' in window)) {
                alert('Browser ini belum mendukung pemindaian. Isi kode secara manual.');
                return;
            }
            const video = document.getElementById('scan-video');
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            video.srcObject = stream;
            video.classList.remove('hidden');
            const detector = new BarcodeDetector({ formats: ['qr_code'] });
            const timer = setInterval(async () => {
                const codes = await detector.detect(video);
                if (codes[0]) {
                    document.getElementById('qr-token').value = codes[0].rawValue;
                    clearInterval(timer);
                    stream.getTracks().forEach((track) => track.stop());
                    document.getElementById('checkin-form').submit();
                }
            }, 500);
        });
    </script>
@endsection
