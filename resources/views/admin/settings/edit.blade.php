@extends('layouts.admin')

@section('content')
    <h1 class="text-2xl font-bold">Informasi event</h1>
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="mt-4 grid gap-3 md:grid-cols-2">
        @csrf @method('PUT')
        <label class="text-sm">Nama<input class="mt-1 w-full rounded-xl border px-3 py-2" name="name" value="{{ $event->name }}"></label>
        <label class="text-sm">Kegiatan<input class="mt-1 w-full rounded-xl border px-3 py-2" name="activity_name" value="{{ $event->activity_name }}"></label>
        <label class="text-sm">Mulai<input class="mt-1 w-full rounded-xl border px-3 py-2" type="datetime-local" name="starts_at" value="{{ $event->starts_at->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') }}"></label>
        <label class="text-sm">Pendaftaran ditutup<input class="mt-1 w-full rounded-xl border px-3 py-2" type="datetime-local" name="registration_closes_at" value="{{ $config->registrationClosesAt()?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') }}"></label>
        <label class="text-sm">Tempat<input class="mt-1 w-full rounded-xl border px-3 py-2" name="venue" value="{{ $event->venue }}"></label>
        <label class="text-sm">Penyelenggara<input class="mt-1 w-full rounded-xl border px-3 py-2" name="organizer" value="{{ $event->organizer }}"></label>
        <label class="text-sm md:col-span-2">Judul publik<input class="mt-1 w-full rounded-xl border px-3 py-2" name="tagline" value="{{ $config->get('tagline') }}"></label>
        <label class="text-sm md:col-span-2">Deskripsi<textarea class="mt-1 w-full rounded-xl border px-3 py-2" name="description" rows="3">{{ $config->get('description') }}</textarea></label>
        <label class="text-sm">Jam hold<input class="mt-1 w-full rounded-xl border px-3 py-2" type="number" name="hold_hours" value="{{ $config->holdHours() }}"></label>
        <label class="text-sm">Maksimal slot / WhatsApp<input class="mt-1 w-full rounded-xl border px-3 py-2" type="number" name="max_slots_per_applicant" value="{{ $config->maxSlots() }}"></label>
        <label class="text-sm">Nama kontak<input class="mt-1 w-full rounded-xl border px-3 py-2" name="contact_name" value="{{ $config->get('contact_name') }}"></label>
        <label class="text-sm">WhatsApp panitia<input class="mt-1 w-full rounded-xl border px-3 py-2" name="contact_whatsapp" value="{{ $config->get('contact_whatsapp') }}"></label>
        <label class="text-sm">Email<input class="mt-1 w-full rounded-xl border px-3 py-2" name="contact_email" value="{{ $config->get('contact_email') }}"></label>
        <label class="text-sm">Instagram<input class="mt-1 w-full rounded-xl border px-3 py-2" name="instagram_url" value="{{ $config->get('instagram_url') }}"></label>
        <label class="text-sm">Facebook<input class="mt-1 w-full rounded-xl border px-3 py-2" name="facebook_url" value="{{ $config->get('facebook_url') }}"></label>
        <label class="text-sm">YouTube<input class="mt-1 w-full rounded-xl border px-3 py-2" name="youtube_url" value="{{ $config->get('youtube_url') }}"></label>
        <label class="text-sm">Nama merchant QRIS<input class="mt-1 w-full rounded-xl border px-3 py-2" name="qris_merchant_name" value="{{ $config->get('qris_merchant_name', $config->get('bank_account_holder')) }}"></label>
        <label class="text-sm">NMID QRIS<input class="mt-1 w-full rounded-xl border px-3 py-2" name="qris_nmid" value="{{ $config->get('qris_nmid') }}"></label>
        <label class="text-sm">Terminal ID QRIS<input class="mt-1 w-full rounded-xl border px-3 py-2" name="qris_terminal_id" value="{{ $config->get('qris_terminal_id') }}"></label>
        <label class="text-sm">Gambar QRIS<input class="mt-1 w-full" type="file" name="qris" accept="image/jpeg,image/png,image/webp"></label>
        @if ($config->get('qris_path') || is_file(public_path('images/qris-pcnu-magelang.jpg')))
            <div class="text-sm">
                <p class="mb-1 font-medium">Pratinjau QRIS</p>
                <img class="max-h-48 rounded-xl border border-slate-200" alt="Pratinjau QRIS" src="{{ $config->qrisUrl() }}">
            </div>
        @endif
        <label class="text-sm">Logo resmi<input class="mt-1 w-full" type="file" name="logo" accept="image/*"></label>
        <label class="text-sm">Denah aerial<input class="mt-1 w-full" type="file" name="floorplan" accept="image/jpeg,image/png,image/webp"></label>
        <label class="text-sm md:col-span-2">Syarat<textarea class="mt-1 w-full rounded-xl border px-3 py-2" name="terms" rows="4">{{ $config->get('terms') }}</textarea></label>
        <label class="text-sm">Pembatalan<textarea class="mt-1 w-full rounded-xl border px-3 py-2" name="cancellation_policy" rows="3">{{ $config->get('cancellation_policy') }}</textarea></label>
        <label class="text-sm">Refund<textarea class="mt-1 w-full rounded-xl border px-3 py-2" name="refund_policy" rows="3">{{ $config->get('refund_policy') }}</textarea></label>
        <div class="md:col-span-2 space-y-2" x-data="{ faqs: {{ \Illuminate\Support\Js::from(old('faqs', $config->faqs() ?: [['question' => '', 'answer' => '']])) }} }">
            <template x-for="(faq, index) in faqs" :key="index">
                <div class="grid gap-2 md:grid-cols-2">
                    <input class="rounded-xl border px-3 py-2" :name="`faqs[${index}][question]`" x-model="faq.question" placeholder="Pertanyaan">
                    <textarea class="rounded-xl border px-3 py-2" :name="`faqs[${index}][answer]`" x-model="faq.answer" placeholder="Jawaban"></textarea>
                </div>
            </template>
            <button type="button" class="text-sm font-semibold text-nu" @click="faqs.push({question: '', answer: ''})">Tambah pertanyaan</button>
        </div>
        <button class="rounded-xl bg-nu px-4 py-3 font-semibold text-white md:col-span-2">Simpan informasi</button>
    </form>
@endsection
