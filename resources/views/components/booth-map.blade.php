@props(['payload'])

<div x-data="boothMap(@js($payload))" class="space-y-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        @if ($payload['mode'] === 'edit')
        <div class="grid flex-1 gap-3 sm:grid-cols-3">
            <label class="text-sm">
                <span class="mb-1 block font-medium">Ukuran</span>
                <select x-model="filterSize" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2">
                    <option value="all">Semua ukuran</option>
                    <option value="2x2">2 × 2 meter</option>
                    <option value="3x3">3 × 3 meter</option>
                </select>
            </label>
            <label class="text-sm">
                <span class="mb-1 block font-medium">Zona</span>
                <select x-model="filterZone" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2">
                    <option value="all">Semua zona</option>
                    <template x-for="zone in zones" :key="zone.id">
                        <option :value="zone.id" x-text="zone.name"></option>
                    </template>
                </select>
            </label>
            <label class="text-sm">
                <span class="mb-1 block font-medium">Status</span>
                <select x-model="filterStatus" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2">
                    <option value="all">Semua status</option>
                    <option value="available">Tersedia</option>
                    <option value="held">Sedang dipesan</option>
                    <option value="occupied">Sudah terisi</option>
                    <option value="blocked">Tidak tersedia</option>
                </select>
            </label>
        </div>
        @endif
        <div class="flex gap-2">
            <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold" @click="zoom(-0.2)">−</button>
            <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold" @click="zoom(0.2)">+</button>
            <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold" @click="scale = 1; panX = 0; panY = 0">Reset</button>
            <button type="button" x-show="mode === 'edit'" x-cloak class="rounded-xl bg-nu px-3 py-2 text-sm font-semibold text-white" @click="save" :disabled="saving" x-text="saving ? 'Menyimpan...' : 'Simpan posisi'"></button>
        </div>
    </div>

    @if ($payload['mode'] === 'edit')
        <div class="flex flex-wrap gap-3 text-xs font-medium">
            <span class="rounded-full bg-nu px-3 py-1 text-white">Lapak 2 × 2</span>
            <span class="rounded-full border-2 border-gold bg-nu-dark px-3 py-1 text-white">Lapak 3 × 3</span>
            <span class="rounded-full bg-amber-400 px-3 py-1">Sedang dipesan</span>
            <span class="rounded-full bg-red-600 px-3 py-1 text-white">Sudah terisi</span>
            <span class="rounded-full bg-slate-400 px-3 py-1 text-white">Tidak tersedia</span>
        </div>
    @else
        <p class="text-sm text-slate-600">Gambar hanya gambaran letak acara. Status tiap lapak — tersedia, sedang dipesan, atau sudah terisi — ada di daftar di bawah. Lapak dipilih dari daftar, bukan dari gambar.</p>
    @endif

    <p x-show="notice" x-cloak class="rounded-xl bg-gold-soft px-4 py-3 text-sm" x-text="notice"></p>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white touch-none" @pointermove="movePointer" @pointerup="endPointer" @pointerleave="endPointer">
        <div @wheel.prevent="onWheel" @pointerdown="startPointer">
            <div x-ref="stage" class="relative w-full origin-top-left" :style="`aspect-ratio: 1024 / 661; transform: translate(${panX}px, ${panY}px) scale(${scale})`">
                <img :src="floorplan" alt="Denah lokasi lapak" class="absolute inset-0 h-full w-full select-none object-fill" draggable="false">
                @if ($payload['mode'] === 'edit')
                    <div class="absolute inset-0">
                        <template x-for="mark in landmarks" :key="mark.kind">
                            <button
                                type="button"
                                class="absolute z-10 rounded-md hover:bg-white/20"
                                :style="`left:${mark.x}%;top:${mark.y}%;width:${mark.w}%;height:${mark.h}%`"
                                @click.stop="chooseLandmark(mark)"
                                :aria-label="mark.label"
                                :title="mark.label"
                            ></button>
                        </template>
                        <template x-for="booth in visible" :key="booth.id">
                            <button
                                type="button"
                                data-booth
                                class="absolute rounded-sm"
                                :class="color(booth)"
                                :style="`left:${booth.x}%;top:${booth.y}%;width:${booth.w}%;height:${booth.h}%`"
                                :aria-label="booth.code"
                                :title="booth.code"
                                @pointerdown="startBooth(booth, $event)"
                                @click.stop="choose(booth)"
                            ></button>
                        </template>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($payload['mode'] === 'edit')
        <div x-show="active" x-cloak class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-nu" x-text="active?.zone"></p>
            <h3 class="text-xl font-bold" x-text="active?.code"></h3>
            <p class="mt-1 text-sm" x-text="active?.size_label"></p>
            <p class="mt-1 text-sm font-medium" x-text="active?.status_label"></p>
            <p class="mt-2 text-sm" x-show="active?.type === 'umkm'">Tempat <span x-text="money(active?.place_price)"></span> · tenda <span x-text="money(active?.tent_price)"></span></p>
            <p class="mt-2 text-sm" x-show="active?.type === 'sponsorship'">Harga sponsorship ditentukan panitia setelah pengajuan.</p>
        </div>
    @else
        @php
            $listed = collect($payload['booths']);
            if ($payload['mode'] === 'select') {
                $listed = $listed->where('type', 'umkm');
            }
            $groups = $listed->groupBy(fn ($booth) => $booth['zone'] ?: 'Lainnya');
        @endphp

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <template x-for="zone in zoneSummary" :key="zone.id">
                <article class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h3 class="font-semibold" x-text="zone.name"></h3>
                    <p class="mt-2 text-sm">Tersedia <strong class="text-nu" x-text="zone.available"></strong> dari <span x-text="zone.total"></span></p>
                    <p class="mt-1 text-xs text-slate-500">Dipesan <span x-text="zone.held"></span> · Terisi <span x-text="zone.occupied"></span><span x-show="zone.blocked > 0"> · Ditutup <span x-text="zone.blocked"></span></span></p>
                </article>
            </template>
        </div>

        <div class="space-y-3">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h3 class="font-semibold">{{ $payload['mode'] === 'select' ? 'Pilih lapak dari daftar' : 'Daftar lapak' }}</h3>
                    <p class="text-sm text-slate-600" x-show="mode === 'select'" x-cloak>Terpilih <span x-text="selected.length"></span> dari <span x-text="max"></span> lapak.</p>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <label class="text-sm">
                    <span class="mb-1 block font-medium">Cari nomor</span>
                    <input x-model="query" type="search" placeholder="Contoh U-001" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2">
                </label>
                <label class="text-sm">
                    <span class="mb-1 block font-medium">Ukuran</span>
                    <select x-model="filterSize" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2">
                        <option value="all">Semua ukuran</option>
                        <option value="2x2">2 × 2 meter</option>
                        <option value="3x3">3 × 3 meter</option>
                    </select>
                </label>
                <label class="text-sm">
                    <span class="mb-1 block font-medium">Zona</span>
                    <select x-model="filterZone" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2">
                        <option value="all">Semua zona</option>
                        <template x-for="zone in zones" :key="zone.id">
                            <option :value="zone.id" x-text="zone.name"></option>
                        </template>
                    </select>
                </label>
                <label class="text-sm">
                    <span class="mb-1 block font-medium">Status</span>
                    <select x-model="filterStatus" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2">
                        <option value="all">Semua status</option>
                        <option value="available">Tersedia</option>
                        <option value="held">Sedang dipesan</option>
                        <option value="occupied">Sudah terisi</option>
                        <option value="blocked">Tidak tersedia</option>
                    </select>
                </label>
            </div>

            <div class="flex flex-wrap gap-2 text-xs font-medium">
                <span class="rounded-full bg-nu px-3 py-1 text-white">Tersedia</span>
                <span class="rounded-full bg-amber-400 px-3 py-1">Sedang dipesan</span>
                <span class="rounded-full bg-red-600 px-3 py-1 text-white">Sudah terisi</span>
                <span class="rounded-full bg-slate-500 px-3 py-1 text-white">Tidak tersedia</span>
            </div>

            <p x-show="!anyListed" x-cloak class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">Tidak ada lapak pada filter ini.</p>

            @foreach ($groups as $zoneName => $booths)
                <section class="space-y-2" x-show="zoneHasVisible({{ (int) $booths->first()['zone_id'] }})">
                    <h4 class="text-sm font-semibold text-nu">{{ $zoneName }}</h4>
                    <div class="grid gap-2">
                        @foreach ($booths as $booth)
                            <div
                                x-data="{ id: {{ (int) $booth['id'] }} }"
                                x-show="rowVisible(id)"
                                class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-3"
                                :class="boothOf(id)?.status !== 'available' ? 'opacity-60' : (isSelected(id) ? 'border-nu ring-1 ring-nu' : '')"
                            >
                                @if ($payload['mode'] === 'select')
                                    <input
                                        id="booth-check-{{ $booth['id'] }}"
                                        type="checkbox"
                                        name="booth_ids[]"
                                        value="{{ $booth['id'] }}"
                                        class="mt-1"
                                        aria-label="{{ $booth['code'] }}"
                                        @disabled($booth['status'] !== 'available')
                                        @checked(in_array($booth['id'], $payload['selected'], true))
                                        :disabled="boothOf(id)?.status !== 'available'"
                                        @change="onListChange($event)"
                                    >
                                @endif
                                <span class="min-w-0 flex-1">
                                    <span class="block font-semibold">{{ $booth['code'] }}</span>
                                    <span class="block text-sm text-slate-600">
                                        {{ $booth['size_label'] }}
                                        @if ($booth['type'] === 'umkm')
                                            · tempat {{ rupiah($booth['place_price']) }}
                                        @else
                                            · harga ditentukan panitia
                                        @endif
                                    </span>
                                    <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold" :class="statusClass(boothOf(id)?.status)" x-text='boothOf(id)?.status_label || @json($booth["status_label"])'></span>
                                </span>
                                @if ($payload['mode'] === 'select')
                                    <select name="tents[{{ $booth['id'] }}]" class="rounded-lg border border-slate-200 px-2 py-1 text-sm" @disabled($booth['status'] !== 'available') :disabled="boothOf(id)?.status !== 'available'">
                                        <option value="0">Tanpa tenda</option>
                                        <option value="1">Dengan tenda ({{ rupiah($booth['tent_price']) }})</option>
                                    </select>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</div>
