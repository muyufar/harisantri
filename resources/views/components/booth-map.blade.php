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

    @if ($payload['mode'] === 'select')
        <p class="text-sm text-slate-600">Klik kotak bernomor pada denah untuk memilih area tenda. Panitia yang mengatur sisi/posisi tepat di dalam kotak tersebut.</p>
        <p class="text-sm font-medium text-nu" x-show="mode === 'select'">Terpilih <span x-text="selected.length"></span> dari <span x-text="max"></span> nomor.</p>
    @elseif ($payload['mode'] === 'edit')
        <div class="flex flex-wrap gap-3 text-xs font-medium">
            <span class="rounded-full bg-nu px-3 py-1 text-white">Slot internal</span>
            <span class="rounded-full bg-amber-400 px-3 py-1">Sedang dipesan</span>
            <span class="rounded-full bg-red-600 px-3 py-1 text-white">Sudah terisi</span>
            <span class="rounded-full bg-slate-400 px-3 py-1 text-white">Tidak tersedia</span>
        </div>
    @else
        <p class="text-sm text-slate-600">Denah menampilkan delapan area tenda. Status ketersediaan tiap nomor ada pada kartu di bawah.</p>
    @endif

    <p x-show="notice" x-cloak class="rounded-xl bg-gold-soft px-4 py-3 text-sm" x-text="notice"></p>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white touch-none" @pointermove="movePointer" @pointerup="endPointer" @pointerleave="endPointer">
        <div @wheel.prevent="onWheel" @pointerdown="startPointer">
            <div x-ref="stage" class="relative w-full origin-top-left" :style="`aspect-ratio: 1024 / 660; transform: translate(${panX}px, ${panY}px) scale(${scale})`">
                <img :src="floorplan" alt="Denah lokasi tenda HSN 2026" class="absolute inset-0 h-full w-full select-none object-fill" draggable="false">
                <div class="absolute inset-0">
                    <template x-for="area in areas" :key="area.number">
                        <button
                            type="button"
                            class="absolute z-30 cursor-pointer rounded-md border-2 border-white shadow-md transition"
                            :class="areaButtonClass(area)"
                            :style="`left:${area.x}%;top:${area.y}%;width:${area.w}%;height:${area.h}%;background:${area.color}99`"
                            @click.stop="toggleArea(area)"
                            :aria-label="`Kotak ${area.number}`"
                            :title="`Kotak ${area.number}`"
                            :disabled="mode === 'select' && area.available < 1 && !isSelected(area.number)"
                        >
                            <span class="absolute -left-1 -top-3 flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold text-white ring-2 ring-white shadow-md" :style="`background:${area.color}`" x-text="area.number"></span>
                        </button>
                    </template>

                    @if ($payload['mode'] === 'edit')
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
                                class="absolute z-30 rounded-sm"
                                :class="color(booth)"
                                :style="`left:${booth.x}%;top:${booth.y}%;width:${booth.w}%;height:${booth.h}%`"
                                :aria-label="booth.code"
                                :title="booth.code"
                                @pointerdown="startBooth(booth, $event)"
                                @click.stop="choose(booth)"
                            ></button>
                        </template>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($payload['mode'] === 'edit')
        <div x-show="active" x-cloak class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-nu" x-text="active?.zone"></p>
            <h3 class="text-xl font-bold" x-text="active?.code"></h3>
            <p class="mt-1 text-sm" x-text="active?.size_label"></p>
            <p class="mt-1 text-sm font-medium" x-text="active?.status_label"></p>
            <p class="mt-2 text-sm" x-show="active?.type === 'umkm'">Paket <span x-text="money(active?.line_total ?? ((active?.place_price || 0) + (active?.tent_price || 0)))"></span></p>
            <p class="mt-2 text-sm" x-show="active?.type === 'sponsorship'">Harga sponsorship ditentukan panitia setelah pengajuan.</p>
        </div>
    @else
        <template x-if="mode === 'select'">
            <div>
                <template x-for="number in selected" :key="'sel-'+number">
                    <input type="hidden" name="area_numbers[]" :value="number">
                </template>
            </div>
        </template>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <template x-for="area in areas" :key="'card-'+area.number">
                <button
                    type="button"
                    class="rounded-2xl border bg-white p-4 text-left transition"
                    :class="isSelected(area.number) ? 'border-nu ring-2 ring-nu/30' : 'border-slate-200'"
                    @click="toggleArea(area)"
                    :disabled="mode === 'select' && area.available < 1 && !isSelected(area.number)"
                >
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold text-white" :style="`background:${area.color}`" x-text="area.number"></span>
                        <div>
                            <h3 class="font-semibold" x-text="area.name"></h3>
                            <p class="text-xs text-slate-500" x-text="area.group"></p>
                        </div>
                        <span class="ml-auto text-xs font-semibold text-nu" x-show="isSelected(area.number)">Dipilih</span>
                    </div>
                    <p class="mt-3 text-sm" x-text="area.size_label"></p>
                    <p class="mt-1 text-sm font-semibold text-nu" x-show="area.line_total" x-text="money(area.line_total)"></p>
                    <p class="mt-2 text-xs font-semibold" :class="statusClass(area.status)" x-text="area.status_label"></p>
                    <p class="mt-1 text-xs text-slate-500">Tersedia <span x-text="area.available"></span> / <span x-text="area.total"></span></p>
                </button>
            </template>
        </div>
    @endif
</div>
