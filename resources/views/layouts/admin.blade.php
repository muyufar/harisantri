<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Panitia HSN 2026</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon-hari-santri.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
    @php
        $links = [
            ['admin.dashboard', 'Dashboard', 'dashboard.view'],
            ['admin.registrations.index', 'Pendaftar UMKM', 'registrations.view'],
            ['admin.registrations.create', 'Daftarkan peserta', 'registrations.manage'],
            ['admin.announcements.date-change', 'Pengumuman jadwal', 'registrations.manage'],
            ['admin.sponsorships.index', 'Sponsorship', 'sponsorships.manage'],
            ['admin.booths.index', 'Slot lapak', 'booths.manage'],
            ['admin.zones.index', 'Zona', 'booths.manage'],
            ['admin.map.edit', 'Editor peta', 'booths.manage'],
            ['admin.prices.index', 'Harga', 'prices.manage'],
            ['admin.settings.edit', 'Informasi event', 'settings.manage'],
            ['admin.users.index', 'Akun panitia', 'users.manage'],
            ['admin.reports.index', 'Laporan', 'reports.export'],
            ['admin.checkin.index', 'Check-in', 'checkin.perform'],
            ['admin.audit.index', 'Audit', 'audit.view'],
        ];
    @endphp
    <div class="min-h-screen md:grid md:grid-cols-[240px_1fr]">
        <aside class="border-b border-white/10 bg-nu-dark text-white md:min-h-screen md:border-b-0">
            <div class="px-4 py-4">
                <div class="inline-flex max-w-full items-center gap-2 rounded-xl bg-white px-2 py-1.5">
                    <img src="{{ asset('images/logo-nu.png') }}" alt="Logo Nahdlatul Ulama" class="h-10 w-auto max-w-[45%]">
                    <img src="{{ asset('images/logo-hari-santri.png') }}" alt="Logo Hari Santri 2026" class="h-10 w-auto max-w-[55%]">
                </div>
                <p class="mt-2 text-sm font-semibold">Panitia HSN 2026</p>
                <p class="text-xs text-white/70">{{ auth()->user()->name }}</p>
            </div>
            <details class="px-4 pb-4 md:hidden">
                <summary class="cursor-pointer rounded-lg bg-white/10 px-3 py-2 text-sm">Menu</summary>
                <div class="mt-2 space-y-1">
                    @foreach ($links as [$route, $label, $permission])
                        @if (auth()->user()->canPermission($permission))
                            <a class="block rounded-lg px-3 py-2 text-sm hover:bg-white/10 {{ request()->routeIs($route) ? 'bg-white/15' : '' }}" href="{{ route($route) }}">{{ $label }}</a>
                        @endif
                    @endforeach
                </div>
            </details>
            <nav class="hidden space-y-1 px-3 pb-6 md:block">
                @foreach ($links as [$route, $label, $permission])
                    @if (auth()->user()->canPermission($permission))
                        <a class="block rounded-lg px-3 py-2 text-sm hover:bg-white/10 {{ request()->routeIs($route) ? 'bg-white/15' : '' }}" href="{{ route($route) }}">{{ $label }}</a>
                    @endif
                @endforeach
            </nav>
            <form method="POST" action="{{ route('admin.logout') }}" class="px-4 pb-4">
                @csrf
                <button class="text-sm underline" type="submit">Keluar</button>
            </form>
        </aside>
        <section class="px-4 py-6 md:px-8">
            @if (session('status'))
                <p class="mb-4 rounded-xl bg-nu-soft px-4 py-3 text-sm text-nu-dark">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            @yield('content')
        </section>
    </div>
    @stack('scripts')
</body>
</html>
