<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pendaftaran Lapak UMKM & Sponsorship HSN 2026')</title>
    <link rel="icon" href="{{ asset('images/logo-nu.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink antialiased">
    @php($event = $currentEvent)
    <header class="sticky top-0 z-40 border-b border-nu/10 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ $event?->config()->logoUrl() ?? asset('images/logo-nu.png') }}" alt="Logo Nahdlatul Ulama, PCNU Kabupaten Magelang" class="h-14 w-auto">
                <span class="hidden text-sm font-semibold leading-tight sm:block">HSN 2026<br><span class="font-medium text-slate-500">PCNU Kabupaten Magelang</span></span>
            </a>
            <button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold md:hidden" type="button" onclick="document.getElementById('nav').classList.toggle('hidden')">Menu</button>
            <nav id="nav" class="absolute left-0 right-0 top-full hidden border-b border-slate-200 bg-white px-4 py-3 md:static md:flex md:items-center md:gap-5 md:border-0 md:bg-transparent md:p-0">
                <a class="block py-2 text-sm font-medium hover:text-nu md:py-0" href="{{ route('home') }}">Home</a>
                <a class="block py-2 text-sm font-medium hover:text-nu md:py-0" href="{{ route('home') }}#informasi">Informasi Lapak</a>
                <a class="block py-2 text-sm font-medium hover:text-nu md:py-0" href="{{ route('map') }}">Peta Lapak</a>
                <a class="block py-2 text-sm font-medium hover:text-nu md:py-0" href="{{ route('register.create') }}">Daftar</a>
                <a class="block py-2 text-sm font-medium hover:text-nu md:py-0" href="{{ route('status.create') }}">Cek Pendaftaran</a>
                <a class="block py-2 text-sm font-medium hover:text-nu md:py-0" href="{{ route('home') }}#kontak">Kontak</a>
            </nav>
        </div>
    </header>

    <main>
        @if (session('status'))
            <div class="mx-auto mt-4 max-w-6xl px-4">
                <p class="rounded-xl bg-nu-soft px-4 py-3 text-sm text-nu-dark">{{ session('status') }}</p>
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="mt-16 border-t border-slate-200 bg-nu-dark text-white">
        <div class="mx-auto grid max-w-6xl gap-6 px-4 py-10 md:grid-cols-3">
            <div>
                <img src="{{ $event?->config()->logoUrl() ?? asset('images/logo-nu.png') }}" alt="" class="mb-3 h-12 w-auto">
                <p class="font-semibold">{{ $event?->organizer ?? 'PCNU Kabupaten Magelang' }}</p>
                <p class="mt-2 text-sm text-white/80">{{ $event?->name ?? 'Hari Santri Nasional 2026' }}</p>
            </div>
            <div class="text-sm">
                <p>{{ $event?->venue ?? 'Halaman MAJT Kabupaten Magelang' }}</p>
                <p class="mt-1">{{ $event?->starts_at?->timezone('Asia/Jakarta')->translatedFormat('l, j F Y') }}</p>
            </div>
            <div class="flex flex-wrap gap-3 text-sm">
                @if ($event && $event->config()->get('instagram_url'))
                    <a class="underline" href="{{ $event->config()->get('instagram_url') }}">Instagram</a>
                @endif
                @if ($event && $event->config()->get('facebook_url'))
                    <a class="underline" href="{{ $event->config()->get('facebook_url') }}">Facebook</a>
                @endif
                @if ($event && $event->config()->get('youtube_url'))
                    <a class="underline" href="{{ $event->config()->get('youtube_url') }}">YouTube</a>
                @endif
            </div>
        </div>
    </footer>
</body>
</html>
