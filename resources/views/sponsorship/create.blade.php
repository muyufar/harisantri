@extends('layouts.public')

@section('title', 'Ajukan Sponsorship')

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-10">
        <h1 class="text-3xl font-bold">Ajukan sponsorship</h1>
        <p class="mt-2 text-sm text-slate-600">Formulir ini tidak menghitung harga. Panitia akan menghubungi Anda, menyampaikan penawaran, lalu menentukan lokasi jika kerja sama disetujui.</p>
        @unless ($open)
            <p class="mt-4 rounded-xl bg-gold-soft px-4 py-3 text-sm">{{ $event->config()->registrationClosedMessage() }}</p>
        @else
            <form method="POST" action="{{ route('sponsorship.store') }}" class="mt-6 space-y-4" x-data="formState" @submit="sending = true">
                @csrf
                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
                @endif
                <label class="block text-sm"><span class="mb-1 block font-medium">Kategori</span>
                    <select name="sponsorship_category_id" class="w-full rounded-xl border border-slate-200 px-3 py-3" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                @foreach ([
                    ['company_name', 'Nama perusahaan / instansi'],
                    ['pic_name', 'Nama PIC'],
                    ['whatsapp', 'WhatsApp PIC'],
                    ['business_type', 'Jenis usaha'],
                    ['area_requirement', 'Kebutuhan luas area'],
                ] as [$name, $label])
                    <label class="block text-sm"><span class="mb-1 block font-medium">{{ $label }}</span>
                        <input class="w-full rounded-xl border border-slate-200 px-3 py-3" name="{{ $name }}" value="{{ old($name) }}" required>
                    </label>
                @endforeach
                <label class="block text-sm"><span class="mb-1 block font-medium">Kebutuhan fasilitas</span>
                    <textarea class="w-full rounded-xl border border-slate-200 px-3 py-3" name="facility_needs" rows="3">{{ old('facility_needs') }}</textarea>
                </label>
                <label class="block text-sm"><span class="mb-1 block font-medium">Catatan</span>
                    <textarea class="w-full rounded-xl border border-slate-200 px-3 py-3" name="notes" rows="3">{{ old('notes') }}</textarea>
                </label>
                <button class="w-full rounded-xl bg-gold px-5 py-3 font-semibold text-nu-dark" :class="sending && 'opacity-60'">Kirim pengajuan</button>
            </form>
        @endunless
    </section>
@endsection
