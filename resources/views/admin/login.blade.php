<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk panitia</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon-hari-santri.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-canvas px-4">
    <form method="POST" action="{{ route('admin.login') }}" class="w-full max-w-md rounded-3xl bg-white p-6 shadow-sm">
        @csrf
        <div class="flex items-center justify-center gap-4">
            <img src="{{ asset('images/logo-nu.png') }}" alt="Logo Nahdlatul Ulama, PCNU Kabupaten Magelang" class="h-16 w-auto">
            <img src="{{ asset('images/logo-hari-santri.png') }}" alt="Logo Hari Santri 2026" class="h-16 w-auto">
        </div>
        <h1 class="mt-4 text-2xl font-bold">Masuk panitia</h1>
        <p class="mt-1 text-sm text-slate-600">HSN 2026 — PCNU Kabupaten Magelang</p>
        @error('email')<p class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>@enderror
        <label class="mt-4 block text-sm"><span class="mb-1 block font-medium">Email</span>
            <input class="w-full rounded-xl border border-slate-200 px-3 py-3" type="email" name="email" value="{{ old('email') }}" required>
        </label>
        <label class="mt-4 block text-sm"><span class="mb-1 block font-medium">Kata sandi</span>
            <input class="w-full rounded-xl border border-slate-200 px-3 py-3" type="password" name="password" required>
        </label>
        <label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1"> Ingat sesi ini</label>
        <button class="mt-5 w-full rounded-xl bg-nu px-5 py-3 font-semibold text-white">Masuk</button>
    </form>
</body>
</html>
