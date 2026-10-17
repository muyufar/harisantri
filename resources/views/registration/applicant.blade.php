@extends('layouts.public')

@section('title', 'Daftar Lapak UMKM')

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-10">
        <p class="text-sm font-semibold text-nu">Langkah 1 dari 4</p>
        <h1 class="mt-2 text-3xl font-bold">Data pendaftar</h1>
        @unless ($open)
            <p class="mt-4 rounded-xl bg-gold-soft px-4 py-3 text-sm">Pendaftaran sudah ditutup.</p>
        @else
            <form method="POST" action="{{ route('register.applicant') }}" enctype="multipart/form-data" class="mt-6 space-y-4" x-data="formState" @submit="sending = true">
                @csrf
                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
                @endif
                @foreach ([
                    ['full_name', 'Nama lengkap penanggung jawab', 'text', true],
                    ['nik', 'NIK penanggung jawab', 'text', true],
                    ['business_name', 'Nama usaha / brand', 'text', true],
                    ['whatsapp', 'Nomor WhatsApp aktif', 'tel', true],
                    ['email', 'Email (opsional)', 'email', false],
                    ['address', 'Alamat usaha', 'text', true],
                ] as [$name, $label, $type, $required])
                    <label class="block text-sm">
                        <span class="mb-1 block font-medium">{{ $label }}</span>
                        <input class="w-full rounded-xl border border-slate-200 px-3 py-3" type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $draft[$name] ?? '') }}" @required($required) @if($name === 'nik') inputmode="numeric" maxlength="16" autocomplete="off" @endif>
                    </label>
                @endforeach
                <label class="block text-sm">
                    <span class="mb-1 block font-medium">Kabupaten / kota</span>
                    <select class="w-full rounded-xl border border-slate-200 px-3 py-3" name="regency" required>
                        @foreach (config('hsn.regencies') as $regency)
                            <option value="{{ $regency }}" @selected(old('regency', $draft['regency'] ?? '') === $regency)>{{ $regency }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block font-medium">Kecamatan</span>
                    <input class="w-full rounded-xl border border-slate-200 px-3 py-3" name="district" list="districts" value="{{ old('district', $draft['district'] ?? '') }}" required>
                    <datalist id="districts">
                        @foreach (config('hsn.districts') as $district)
                            <option value="{{ $district }}"></option>
                        @endforeach
                    </datalist>
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block font-medium">Jenis dagangan</span>
                    <select class="w-full rounded-xl border border-slate-200 px-3 py-3" name="product_type" required>
                        @foreach (config('hsn.product_types') as $type)
                            <option value="{{ $type }}" @selected(old('product_type', $draft['product_type'] ?? '') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block font-medium">Deskripsi produk</span>
                    <textarea class="w-full rounded-xl border border-slate-200 px-3 py-3" name="product_description" rows="4" required>{{ old('product_description', $draft['product_description'] ?? '') }}</textarea>
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block font-medium">Foto produk atau logo usaha (opsional)</span>
                    <input class="w-full text-sm" type="file" name="logo" accept="image/jpeg,image/png,image/webp">
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block font-medium">Catatan kebutuhan khusus</span>
                    <textarea class="w-full rounded-xl border border-slate-200 px-3 py-3" name="special_notes" rows="3">{{ old('special_notes', $draft['special_notes'] ?? '') }}</textarea>
                </label>
                <button class="w-full rounded-xl bg-nu px-5 py-3 font-semibold text-white" :class="sending && 'opacity-60'">Lanjut pilih lapak</button>
            </form>
        @endunless
    </section>
@endsection
