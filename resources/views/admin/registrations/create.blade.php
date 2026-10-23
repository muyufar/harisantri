@extends('layouts.admin')

@section('title', 'Daftarkan peserta')

@section('content')
    <a class="text-sm text-nu" href="{{ route('admin.registrations.index') }}">Kembali</a>
    <h1 class="mt-2 text-2xl font-bold">Daftarkan peserta</h1>
    <p class="mt-2 max-w-3xl text-sm text-slate-600">
        Pendaftaran dari backoffice tetap bisa saat formulir publik ditutup atau kotak disembunyikan.
        Maksimal {{ $maxSlots }} slot untuk satu nomor WhatsApp. Harga dihitung dari ukuran kotak dan pilihan tenda.
    </p>

    <form method="POST" action="{{ route('admin.registrations.store') }}" enctype="multipart/form-data" class="mt-4 grid gap-4 lg:grid-cols-2">
        @csrf
        <section class="space-y-3 rounded-2xl bg-white p-4">
            <h2 class="font-semibold">Data peserta</h2>
            @foreach ([
                ['full_name', 'Nama lengkap penanggung jawab', 'text', true],
                ['nik', 'NIK', 'text', true],
                ['business_name', 'Nama usaha', 'text', true],
                ['whatsapp', 'WhatsApp', 'tel', true],
                ['email', 'Email (opsional)', 'email', false],
                ['address', 'Alamat usaha', 'text', true],
            ] as [$name, $label, $type, $required])
                <label class="block text-sm">
                    <span class="mb-1 block font-medium">{{ $label }}</span>
                    <input class="w-full rounded-xl border border-slate-200 px-3 py-2" type="{{ $type }}" name="{{ $name }}" value="{{ old($name) }}" @required($required) @if($name === 'nik') inputmode="numeric" maxlength="16" @endif>
                </label>
            @endforeach
            <label class="block text-sm">
                <span class="mb-1 block font-medium">Kabupaten / kota</span>
                <select class="w-full rounded-xl border border-slate-200 px-3 py-2" name="regency" required>
                    @foreach (config('hsn.regencies') as $regency)
                        <option value="{{ $regency }}" @selected(old('regency') === $regency)>{{ $regency }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1 block font-medium">Kecamatan</span>
                <input class="w-full rounded-xl border border-slate-200 px-3 py-2" name="district" list="districts" value="{{ old('district') }}" required>
                <datalist id="districts">
                    @foreach (config('hsn.districts') as $district)
                        <option value="{{ $district }}"></option>
                    @endforeach
                </datalist>
            </label>
            <label class="block text-sm">
                <span class="mb-1 block font-medium">Jenis dagangan</span>
                <select class="w-full rounded-xl border border-slate-200 px-3 py-2" name="product_type" required>
                    @foreach (config('hsn.product_types') as $type)
                        <option value="{{ $type }}" @selected(old('product_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                <span class="mb-1 block font-medium">Deskripsi produk</span>
                <textarea class="w-full rounded-xl border border-slate-200 px-3 py-2" name="product_description" rows="3" required>{{ old('product_description') }}</textarea>
            </label>
            <label class="block text-sm">
                <span class="mb-1 block font-medium">Foto usaha (opsional)</span>
                <input class="w-full" type="file" name="logo" accept="image/jpeg,image/png,image/webp">
            </label>
            <label class="block text-sm">
                <span class="mb-1 block font-medium">Catatan</span>
                <textarea class="w-full rounded-xl border border-slate-200 px-3 py-2" name="special_notes" rows="2">{{ old('special_notes') }}</textarea>
            </label>
        </section>

        <section class="space-y-3">
            <div class="rounded-2xl bg-white p-4">
                <h2 class="font-semibold">Kotak</h2>
                <div class="mt-3 space-y-3">
                    @foreach ($areas as $area)
                        @php($stock = (int) ($area['stock'] ?? $area['available']))
                        <div class="rounded-xl border border-slate-100 p-3 {{ $stock < 1 ? 'opacity-60' : '' }}">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold">{{ $area['name'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $area['size_label'] }} · sisa {{ $stock }} slot @unless ($area['open']) · disembunyikan dari publik @endunless</p>
                                </div>
                            </div>
                            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                <label class="text-sm">
                                    Jumlah
                                    <input class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" type="number" name="slots[{{ $area['number'] }}][quantity]" min="0" max="{{ min(3, $stock) }}" value="{{ old('slots.'.$area['number'].'.quantity', 0) }}" @disabled($stock < 1)>
                                </label>
                                <label class="text-sm">
                                    Tenda
                                    <select class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" name="slots[{{ $area['number'] }}][with_tent]" @disabled($stock < 1)>
                                        <option value="0" @selected(old('slots.'.$area['number'].'.with_tent', '0') == '0')>Tanpa tenda</option>
                                        <option value="1" @selected(old('slots.'.$area['number'].'.with_tent') == '1')>Pakai tenda panitia</option>
                                    </select>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <label class="flex items-start gap-2 rounded-2xl bg-gold-soft p-4 text-sm">
                <input class="mt-1" type="checkbox" name="mark_paid" value="1" @checked(old('mark_paid'))>
                <span>Sudah dibayar. Pendaftaran langsung terkonfirmasi tanpa unggah bukti. Kosongkan jika peserta masih akan membayar.</span>
            </label>
            <button class="w-full rounded-xl bg-nu px-4 py-3 font-semibold text-white">Simpan pendaftaran</button>
        </section>
    </form>
@endsection
