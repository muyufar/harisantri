@extends('layouts.admin')

@section('title', 'Pengumuman jadwal')

@section('content')
    <h1 class="text-2xl font-bold">Pengumuman perubahan jadwal</h1>
    <p class="mt-2 max-w-3xl text-sm text-slate-600">
        Buka chat WhatsApp satu per satu, lalu kirim manual. Teks sudah berisi permohonan maaf, tanggal baru, dan tautan flyer.
    </p>

    <div class="mt-4 grid gap-4 lg:grid-cols-[280px_1fr]">
        <article class="rounded-2xl bg-white p-4">
            <p class="text-sm font-semibold">Flyer</p>
            <a class="mt-2 block" href="{{ $flyerUrl }}" target="_blank">
                <img class="w-full rounded-xl border border-slate-200" src="{{ $flyerUrl }}" alt="Flyer perubahan jadwal 1 November 2026">
            </a>
            <a class="mt-2 inline-block text-sm text-nu" href="{{ $flyerUrl }}" download>Unduh JPG</a>
        </article>

        <div
            class="rounded-2xl bg-white p-4"
            x-data="announcementBlast(@js($recipients))"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-semibold">Daftar penerima</p>
                    <p class="text-sm text-slate-500" x-text="summary()"></p>
                </div>
                <button
                    type="button"
                    class="rounded-xl bg-nu px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                    @click="openNext()"
                    :disabled="done || !recipients.length"
                    x-text="openedCount() === 0 ? 'Buka pertama' : 'Buka berikutnya'"
                ></button>
            </div>

            <p class="mt-3 rounded-xl bg-gold-soft px-3 py-2 text-sm" x-show="current" x-cloak>
                Fokus: <span class="font-semibold" x-text="current?.business"></span>
                · <span x-text="current?.whatsapp"></span>
                · <span x-text="current?.status"></span>
            </p>

            <div class="mt-4 max-h-[28rem] overflow-auto rounded-xl border border-slate-100">
                <table class="min-w-full text-left text-sm">
                    <thead class="sticky top-0 bg-slate-50 text-slate-500">
                        <tr>
                            <th class="p-2">No</th>
                            <th>Kode</th>
                            <th>Usaha</th>
                            <th>WhatsApp</th>
                            <th>Status</th>
                            <th>Kirim</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, i) in recipients" :key="row.id">
                            <tr class="border-t border-slate-100" :class="i === index && 'bg-nu-soft'">
                                <td class="p-2 text-slate-500" x-text="i + 1"></td>
                                <td x-text="row.code"></td>
                                <td>
                                    <div class="font-medium" x-text="row.business"></div>
                                    <div class="text-xs text-slate-500" x-text="row.name"></div>
                                </td>
                                <td class="whitespace-nowrap" x-text="row.whatsapp"></td>
                                <td x-text="row.status"></td>
                                <td>
                                    <button type="button" class="text-nu underline" @click="openOne(i)">Buka</button>
                                    <span class="ml-2 text-xs text-slate-500" x-text="sent[row.id] ? 'sudah dibuka' : ''"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function announcementBlast(recipients) {
    return {
        recipients,
        index: 0,
        done: false,
        sent: {},
        get current() {
            return this.recipients[this.index] || null;
        },
        openedCount() {
            return Object.keys(this.sent).length;
        },
        summary() {
            return this.openedCount() + ' / ' + this.recipients.length + ' dibuka · kirim manual di WhatsApp';
        },
        openOne(i) {
            const row = this.recipients[i];
            if (!row) return;
            window.open(row.url, '_blank');
            this.sent[row.id] = true;
            this.index = i;
            this.done = this.openedCount() >= this.recipients.length;
        },
        openNext() {
            if (!this.recipients.length) return;

            let next = this.index;
            if (this.sent[this.current?.id]) {
                next = this.index + 1;
            }
            while (next < this.recipients.length && this.sent[this.recipients[next].id]) {
                next += 1;
            }
            if (next >= this.recipients.length) {
                this.done = true;
                return;
            }
            this.openOne(next);
        },
    };
}
</script>
@endpush
