@extends('layouts.admin')

@section('title', 'Pengumuman jadwal')

@section('content')
    <h1 class="text-2xl font-bold">Pengumuman perubahan jadwal</h1>
    <p class="mt-2 max-w-3xl text-sm text-slate-600">
        Buka chat WhatsApp, kirim manual, lalu status tersimpan sebagai sudah dikirim (tetap ada setelah refresh).
        Belum dikirim ditampilkan di atas.
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
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-sm" @click="filter = 'all'" :class="filter === 'all' && 'bg-nu-soft'">Semua</button>
                    <button type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-sm" @click="filter = 'pending'" :class="filter === 'pending' && 'bg-nu-soft'">Belum</button>
                    <button type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-sm" @click="filter = 'sent'" :class="filter === 'sent' && 'bg-nu-soft'">Sudah</button>
                    <button
                        type="button"
                        class="rounded-xl bg-nu px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                        @click="openNext()"
                        :disabled="!nextPending()"
                        x-text="sentCount() === 0 ? 'Buka pertama' : 'Buka berikutnya'"
                    ></button>
                </div>
            </div>

            <p class="mt-3 rounded-xl bg-gold-soft px-3 py-2 text-sm" x-show="current" x-cloak>
                Fokus: <span class="font-semibold" x-text="current?.business"></span>
                · <span x-text="current?.whatsapp"></span>
                · <span x-text="current?.status"></span>
            </p>
            <p class="mt-2 text-sm text-red-600" x-show="error" x-text="error" x-cloak></p>

            <div class="mt-4 max-h-[28rem] overflow-auto rounded-xl border border-slate-100">
                <table class="min-w-full text-left text-sm">
                    <thead class="sticky top-0 bg-slate-50 text-slate-500">
                        <tr>
                            <th class="p-2">No</th>
                            <th>Kode</th>
                            <th>Usaha</th>
                            <th>WhatsApp</th>
                            <th>Status</th>
                            <th>WA</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, i) in visible()" :key="row.id">
                            <tr class="border-t border-slate-100" :class="row.id === current?.id && 'bg-nu-soft'">
                                <td class="p-2 text-slate-500" x-text="i + 1"></td>
                                <td x-text="row.code"></td>
                                <td>
                                    <div class="font-medium" x-text="row.business"></div>
                                    <div class="text-xs text-slate-500" x-text="row.name"></div>
                                </td>
                                <td class="whitespace-nowrap" x-text="row.whatsapp"></td>
                                <td x-text="row.status"></td>
                                <td>
                                    <span class="font-semibold text-nu" x-show="row.sent" x-cloak>Terkirim<span x-show="row.sent_at" x-text="' · ' + row.sent_at"></span></span>
                                    <span class="text-slate-400" x-show="!row.sent">Belum</span>
                                </td>
                                <td class="whitespace-nowrap">
                                    <button type="button" class="text-nu underline" @click="openRow(row)">Buka</button>
                                    <button type="button" class="ml-2 text-slate-600 underline" @click="toggleSent(row)" x-text="row.sent ? 'Batalkan' : 'Tandai'"></button>
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
        filter: 'pending',
        index: 0,
        error: '',
        saving: false,
        csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
        get current() {
            return this.recipients[this.index] || this.nextPending() || null;
        },
        sentCount() {
            return this.recipients.filter((row) => row.sent).length;
        },
        summary() {
            return this.sentCount() + ' / ' + this.recipients.length + ' sudah dikirim WA';
        },
        visible() {
            if (this.filter === 'sent') return this.recipients.filter((row) => row.sent);
            if (this.filter === 'pending') return this.recipients.filter((row) => !row.sent);
            return this.recipients;
        },
        nextPending() {
            return this.recipients.find((row) => !row.sent) || null;
        },
        openRow(row) {
            window.open(row.url, '_blank');
            this.index = this.recipients.findIndex((item) => item.id === row.id);
            if (!row.sent) {
                this.markSent(row, true);
            }
        },
        openNext() {
            const row = this.nextPending();
            if (!row) return;
            this.openRow(row);
        },
        toggleSent(row) {
            this.markSent(row, !row.sent);
        },
        async markSent(row, sent) {
            this.error = '';
            this.saving = true;
            try {
                const response = await fetch(row.mark_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({ sent }),
                });
                if (!response.ok) {
                    throw new Error('Gagal menyimpan status.');
                }
                const data = await response.json();
                row.sent = data.sent;
                row.sent_at = data.sent_at;
            } catch (e) {
                this.error = e.message || 'Gagal menyimpan status.';
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endpush
