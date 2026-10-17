@extends('layouts.public')

@section('content')
    @if (! $event)
        <section class="mx-auto max-w-3xl px-4 py-20 text-center">
            <h1 class="text-3xl font-bold">Event belum dikonfigurasi</h1>
            <p class="mt-3 text-slate-600">Jalankan migrasi dan seeder agar data Hari Santri Nasional 2026 tersedia.</p>
        </section>
    @else
        @php($config = $event->config())
        <section class="mx-auto grid max-w-6xl gap-8 px-4 py-12 md:grid-cols-[1.3fr_.7fr] md:items-center">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-nu">{{ $event->organizer }}</p>
                <h1 class="mt-3 text-4xl font-bold leading-tight md:text-5xl">{{ $config->get('tagline') }}</h1>
                <p class="mt-4 max-w-xl text-slate-600">{{ $config->get('description') }}</p>
                <dl class="mt-6 grid gap-3 text-sm sm:grid-cols-3">
                    <div class="rounded-2xl bg-white p-4"><dt class="text-slate-500">Tanggal</dt><dd class="font-semibold">{{ $event->starts_at->timezone('Asia/Jakarta')->translatedFormat('l, j F Y') }}</dd></div>
                    <div class="rounded-2xl bg-white p-4"><dt class="text-slate-500">Waktu</dt><dd class="font-semibold">07.00 WIB sampai selesai</dd></div>
                    <div class="rounded-2xl bg-white p-4"><dt class="text-slate-500">Tempat</dt><dd class="font-semibold">{{ $event->venue }}</dd></div>
                </dl>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('register.create') }}" class="rounded-xl bg-nu px-5 py-3 text-center font-semibold text-white">Daftar Lapak UMKM</a>
                    <a href="{{ route('sponsorship.create') }}" class="rounded-xl border border-gold bg-gold-soft px-5 py-3 text-center font-semibold text-nu-dark">Ajukan Sponsorship</a>
                </div>
            </div>
            <div class="rounded-3xl bg-nu-dark p-6 text-white" x-data="countdown('{{ $event->starts_at->toIso8601String() }}')">
                <p class="text-sm text-white/70" x-text="label">Menuju pelaksanaan</p>
                <div class="mt-4 grid grid-cols-4 gap-2" x-show="!done">
                    <template x-for="part in parts" :key="part.key">
                        <div class="rounded-2xl bg-white/10 p-3 text-center">
                            <div class="text-2xl font-bold" x-text="part.value"></div>
                            <div class="text-xs uppercase" x-text="part.key"></div>
                        </div>
                    </template>
                </div>
                <p class="mt-4 text-sm text-white/80">{{ $event->activity_name }}</p>
            </div>
        </section>

        <section id="informasi" class="mx-auto max-w-6xl px-4 py-8">
            <h2 class="text-2xl font-bold">Harga lapak UMKM</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                @forelse ($prices as $price)
                    <article class="rounded-3xl border border-slate-200 bg-white p-6">
                        <h3 class="text-xl font-bold">{{ $price->sizeLabel() }}</h3>
                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex justify-between"><dt>Tempat saja</dt><dd class="font-semibold">{{ rupiah($price->place_price) }}</dd></div>
                            <div class="flex justify-between"><dt>Tambahan tenda</dt><dd class="font-semibold">{{ rupiah($price->tent_price) }}</dd></div>
                            <div class="flex justify-between border-t border-slate-100 pt-2"><dt>Total dengan tenda</dt><dd class="font-semibold text-nu">{{ rupiah($price->place_price + $price->tent_price) }}</dd></div>
                        </dl>
                    </article>
                @empty
                    <p class="text-sm text-slate-600">Harga belum diatur panitia.</p>
                @endforelse
            </div>
            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <article class="rounded-3xl bg-white p-6">
                    <h3 class="font-bold">Lapak UMKM</h3>
                    <p class="mt-2 text-sm text-slate-600">Lihat denah sebagai gambaran letak, lalu pilih lapak yang masih tersedia dari daftar. Tentukan perlu tenda atau tidak. Maksimal {{ $config->maxSlots() }} slot untuk satu nomor WhatsApp.</p>
                </article>
                <article class="rounded-3xl bg-gold-soft p-6">
                    <h3 class="font-bold">Lapak sponsorship</h3>
                    <p class="mt-2 text-sm text-slate-700">Tidak ada tarif otomatis. Calon sponsor mengajukan kebutuhan, lalu panitia menghubungi, menawar harga, dan menetapkan lokasi.</p>
                </article>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 py-8">
            <div class="flex items-end justify-between gap-4">
                <h2 class="text-2xl font-bold">Peta dan ketersediaan</h2>
                <a class="text-sm font-semibold text-nu" href="{{ route('map') }}">Buka peta</a>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-4">
                <div class="rounded-2xl bg-white p-4"><p class="text-sm text-slate-500">UMKM tersedia</p><p class="text-2xl font-bold">{{ $stats['available'] }}</p></div>
                <div class="rounded-2xl bg-white p-4"><p class="text-sm text-slate-500">Sedang dipesan</p><p class="text-2xl font-bold">{{ $stats['held'] }}</p></div>
                <div class="rounded-2xl bg-white p-4"><p class="text-sm text-slate-500">Terkonfirmasi</p><p class="text-2xl font-bold">{{ $stats['occupied'] }}</p></div>
                <div class="rounded-2xl bg-white p-4"><p class="text-sm text-slate-500">Sponsor tersedia</p><p class="text-2xl font-bold">{{ $stats['sponsorship_available'] }}</p></div>
            </div>
            <div class="mt-4">
                <x-booth-map :payload="$map" />
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 py-8">
            <h2 class="text-2xl font-bold">Alur pendaftaran</h2>
            <ol class="mt-4 grid gap-3 md:grid-cols-3">
                @foreach ([
                    'Isi data penanggung jawab dan usaha. Akun sandi tidak diperlukan.',
                    'Pilih lapak yang masih tersedia dari daftar. Tenda bersifat pilihan pada tiap slot.',
                    'Periksa ringkasan harga yang dihitung server, lalu setujui ketentuan.',
                    'Transfer ke rekening panitia sebesar nominal yang tertera dan unggah bukti.',
                    'Panitia menerima atau menolak pembayaran. Jika ditolak, bukti dapat diunggah ulang.',
                    'Bukti konfirmasi dan QR check-in terbit setelah pembayaran disetujui.',
                ] as $step)
                    <li class="rounded-2xl bg-white p-4 text-sm"><span class="font-semibold text-nu">{{ $loop->iteration }}.</span> {{ $step }}</li>
                @endforeach
            </ol>
            <p class="mt-4 text-sm text-slate-600">Slot ditahan {{ $config->holdHours() }} jam setelah pesanan dibuat. Pendaftaran ditutup {{ $config->registrationClosesAt()?->timezone('Asia/Jakarta')->translatedFormat('l, j F Y H.i') }} WIB.</p>
        </section>

        <section class="mx-auto max-w-6xl px-4 py-8">
            <h2 class="text-2xl font-bold">Pertanyaan</h2>
            <div class="mt-4 space-y-3">
                @foreach ($config->faqs() as $faq)
                    <details class="rounded-2xl bg-white p-4">
                        <summary class="cursor-pointer font-semibold">{{ $faq['question'] }}</summary>
                        <p class="mt-2 text-sm text-slate-600">{{ $faq['answer'] }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        <section id="kontak" class="mx-auto max-w-6xl px-4 py-8">
            <div class="rounded-3xl bg-white p-6">
                <h2 class="text-2xl font-bold">Kontak panitia</h2>
                <p class="mt-2">{{ $config->get('contact_name') }}</p>
                <p class="text-sm text-slate-600">{{ $config->get('contact_email') }}</p>
                <a class="mt-4 inline-flex rounded-xl bg-nu px-5 py-3 font-semibold text-white" href="{{ \App\Support\Phone::whatsappUrl((string) $config->get('contact_whatsapp'), 'Halo panitia HSN 2026, saya ingin bertanya tentang lapak.') }}">WhatsApp panitia</a>
                <div class="mt-6 text-sm text-slate-600">
                    <p class="font-semibold text-ink">Pembatalan</p>
                    <p class="whitespace-pre-line">{{ $config->get('cancellation_policy') }}</p>
                    <p class="mt-3 font-semibold text-ink">Refund</p>
                    <p class="whitespace-pre-line">{{ $config->get('refund_policy') }}</p>
                </div>
            </div>
        </section>
    @endif
@endsection
