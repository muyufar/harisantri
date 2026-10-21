@extends('layouts.admin')

@section('title', 'Pengumuman jadwal')

@section('content')
    <h1 class="text-2xl font-bold">Pengumuman perubahan jadwal</h1>
    <p class="mt-2 max-w-3xl text-sm text-slate-600">
        WhatsApp tidak mengirim otomatis: sistem hanya membuka chat berisi teks siap kirim.
        Klik <strong>Kirim</strong> di WhatsApp sebelum hitungan habis. Satu jendela dipakai berulang agar tidak diblokir popup.
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
            x-data="announcementBlast(@js($recipients), {{ (int) $intervalSeconds }})"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-semibold">Antrian WhatsApp</p>
                    <p class="text-sm text-slate-500" x-text="summary()"></p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="rounded-xl bg-nu px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" @click="start()" :disabled="running || done || !recipients.length">Mulai kirim</button>
                    <button type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold disabled:opacity-50" @click="pause()" :disabled="!running">Jeda</button>
                    <button type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold disabled:opacity-50" @click="resume()" :disabled="running || done || openedCount() === 0">Lanjut otomatis</button>
                    <button type="button" class="rounded-xl border border-nu px-4 py-2 text-sm font-semibold text-nu disabled:opacity-50" @click="sendNextNow()" :disabled="done || !current">Buka berikutnya sekarang</button>
                </div>
            </div>

            <p class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700" x-show="error" x-text="error" x-cloak></p>

            <p class="mt-3 rounded-xl bg-gold-soft px-3 py-2 text-sm" x-show="current" x-cloak>
                <template x-if="running">
                    <span>
                        Klik <strong>Kirim</strong> di WhatsApp untuk
                        <span class="font-semibold" x-text="current?.business"></span>
                        (<span x-text="current?.whatsapp"></span>).
                        Chat berikutnya dalam <span class="font-semibold" x-text="countdown"></span> dtk.
                    </span>
                </template>
                <template x-if="!running">
                    <span>
                        Siap: <span class="font-semibold" x-text="current?.business"></span>
                        · <span x-text="current?.whatsapp"></span>
                        · <span x-text="current?.status"></span>
                    </span>
                </template>
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
                                    <span class="ml-2 text-xs text-slate-500" x-text="sent[row.id] ? 'chat dibuka' : ''"></span>
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
function announcementBlast(recipients, intervalSeconds) {
    return {
        recipients,
        intervalSeconds,
        index: 0,
        countdown: 0,
        running: false,
        done: false,
        error: '',
        sent: {},
        waWindow: null,
        timer: null,
        tick: null,
        get current() {
            return this.recipients[this.index] || null;
        },
        openedCount() {
            return Object.keys(this.sent).length;
        },
        summary() {
            return this.openedCount() + ' / ' + this.recipients.length + ' chat dibuka · jeda ' + this.intervalSeconds + ' detik';
        },
        navigate(url) {
            this.error = '';
            if (this.waWindow && !this.waWindow.closed) {
                try {
                    this.waWindow.location.href = url;
                    this.waWindow.focus();
                    return true;
                } catch (e) {
                    this.waWindow = null;
                }
            }
            this.waWindow = window.open(url, 'hsnWaBlast');
            if (!this.waWindow) {
                this.error = 'Popup diblokir peramban. Izinkan popup untuk situs ini, lalu tekan Mulai kirim lagi.';
                return false;
            }
            return true;
        },
        openOne(i) {
            const row = this.recipients[i];
            if (!row) return;
            if (!this.navigate(row.url)) return;
            this.sent[row.id] = true;
            this.index = i;
            this.done = false;
        },
        openCurrent() {
            if (!this.current) {
                this.finish();
                return false;
            }
            if (!this.navigate(this.current.url)) {
                this.pause();
                return false;
            }
            this.sent[this.current.id] = true;
            return true;
        },
        start() {
            if (!this.recipients.length) return;
            this.done = false;
            this.error = '';
            if (!this.openCurrent()) return;
            this.running = true;
            this.queueNext();
        },
        pause() {
            this.running = false;
            this.clearTimers();
            this.countdown = 0;
        },
        resume() {
            if (this.done) return;
            this.error = '';
            this.running = true;
            this.queueNext();
        },
        sendNextNow() {
            this.clearTimers();
            this.error = '';
            if (this.sent[this.current?.id] && this.index < this.recipients.length - 1) {
                this.index += 1;
            }
            if (!this.openCurrent()) return;
            if (this.index >= this.recipients.length - 1) {
                this.finish();
                return;
            }
            if (this.running) {
                this.queueNext();
            }
        },
        queueNext() {
            this.clearTimers();
            if (!this.running) return;
            if (this.index >= this.recipients.length - 1) {
                this.finish();
                return;
            }
            this.countdown = this.intervalSeconds;
            this.tick = setInterval(() => {
                this.countdown = Math.max(0, this.countdown - 1);
            }, 1000);
            this.timer = setTimeout(() => {
                this.index += 1;
                if (!this.openCurrent()) return;
                this.queueNext();
            }, this.intervalSeconds * 1000);
        },
        finish() {
            this.running = false;
            this.done = true;
            this.clearTimers();
            this.countdown = 0;
        },
        clearTimers() {
            if (this.timer) clearTimeout(this.timer);
            if (this.tick) clearInterval(this.tick);
            this.timer = null;
            this.tick = null;
        },
    };
}
</script>
@endpush
