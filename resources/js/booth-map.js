export function registerBoothMap(Alpine) {
    Alpine.data('boothMap', (config) => ({
        booths: config.booths || [],
        areas: config.areas || [],
        mode: config.mode || 'view',
        max: Number(config.max || 3),
        floorplan: config.floorplan,
        landmarks: config.landmarks || [],
        zones: config.zones || [],
        saveUrl: config.saveUrl,
        feedUrl: config.feedUrl,
        selected: Array.isArray(config.selected) ? [...new Set(config.selected.map(Number))] : [],
        quantities: config.quantities && !Array.isArray(config.quantities) ? config.quantities : {},
        tentChoices: config.tentChoices && !Array.isArray(config.tentChoices) ? config.tentChoices : {},
        active: null,
        notice: '',
        scale: 1,
        panX: 0,
        panY: 0,
        panning: false,
        dragBooth: null,
        dragStart: null,
        lastX: 0,
        lastY: 0,
        saving: false,
        filterSize: 'all',
        filterZone: 'all',
        filterStatus: 'all',
        query: '',
        init() {
            const form = this.$el.closest('form');
            if (form && this.mode === 'select') {
                form.addEventListener('submit', (event) => {
                    const missing = this.selected.filter((number) => this.tentValue(number) === '');
                    if (missing.length === 0) return;
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    this.notice = `Pilih pakai tenda atau tidak untuk Kotak ${missing.join(', ')}.`;
                }, true);
            }
            if (this.mode !== 'edit' && this.feedUrl) {
                this.timer = setInterval(() => this.refresh(), 20000);
            }
        },
        destroy() {
            clearInterval(this.timer);
        },
        get visible() {
            return this.booths.filter((booth) => {
                if (this.filterSize !== 'all' && booth.size !== this.filterSize) return false;
                if (this.filterZone !== 'all' && String(booth.zone_id) !== String(this.filterZone)) return false;
                if (this.filterStatus !== 'all' && booth.status !== this.filterStatus) return false;
                return true;
            });
        },
        isSelected(number) {
            return this.selected.includes(Number(number));
        },
        areaOf(number) {
            return this.areas.find((area) => Number(area.number) === Number(number));
        },
        boothOf(id) {
            return this.booths.find((booth) => Number(booth.id) === Number(id));
        },
        areaButtonClass(area) {
            const selected = this.isSelected(area.number);
            if (selected) return 'ring-4 ring-nu';
            if (area.status === 'occupied' || area.available < 1) return 'opacity-80';
            return 'hover:ring-2 hover:ring-white';
        },
        statusClass(status) {
            if (status === 'occupied') return 'text-red-700';
            if (status === 'held') return 'text-amber-700';
            if (status === 'blocked') return 'text-slate-600';
            return 'text-nu';
        },
        toggleArea(area) {
            if (this.mode === 'view') {
                this.active = area;
                return;
            }
            if (this.mode !== 'select') {
                this.active = area;
                return;
            }
            const number = Number(area.number);
            if (this.isSelected(number)) {
                this.selected = this.selected.filter((value) => value !== number);
                this.clearTent(number);
                this.clearQty(number);
                this.notice = '';
                return;
            }
            if (area.available < 1) {
                this.notice = `Kotak ${number} sudah penuh.`;
                return;
            }
            if (this.slotCount() >= this.max) {
                this.notice = `Kuota pilihan pada pendaftaran ini ${this.max} slot.`;
                return;
            }
            this.selected.push(number);
            this.quantities = { ...this.quantities, [number]: 1 };
            this.notice = `Kotak ${number} dipilih. Atur jumlah slot, lalu tentukan pakai tenda atau tidak.`;
        },
        quantity(number) {
            if (!this.isSelected(number)) return 0;
            const value = Number(this.quantities[number] ?? this.quantities[String(number)] ?? 1);
            return Math.max(1, value);
        },
        slotCount() {
            return this.selected.reduce((sum, number) => sum + this.quantity(number), 0);
        },
        changeQty(number, delta) {
            const value = Number(number);
            const area = this.areaOf(value);
            if (!area || !this.isSelected(value)) return;
            const next = this.quantity(value) + delta;
            if (next < 1) {
                this.toggleArea(area);
                return;
            }
            if (delta > 0 && this.slotCount() >= this.max) {
                this.notice = `Kuota pilihan pada pendaftaran ini ${this.max} slot.`;
                return;
            }
            if (next > area.available) {
                this.notice = `Kotak ${value} hanya tersisa ${area.available} slot.`;
                return;
            }
            this.quantities = { ...this.quantities, [value]: next };
            this.notice = `Kotak ${value}: ${next} slot dalam blok yang sama.`;
        },
        clearQty(number) {
            const next = { ...this.quantities };
            delete next[number];
            delete next[String(number)];
            this.quantities = next;
        },
        setTent(number, withTent) {
            const value = Number(number);
            if (!this.isSelected(value)) {
                const area = this.areaOf(value);
                if (!area) return;
                this.toggleArea(area);
                if (!this.isSelected(value)) return;
            }
            this.tentChoices = { ...this.tentChoices, [value]: withTent };
            this.notice = withTent
                ? `Kotak ${value} memakai tenda panitia.`
                : `Kotak ${value} tanpa tenda.`;
        },
        clearTent(number) {
            const next = { ...this.tentChoices };
            delete next[number];
            delete next[String(number)];
            this.tentChoices = next;
        },
        tentValue(number) {
            const value = this.tentChoices[number] ?? this.tentChoices[String(number)];
            if (value === true || value === 1 || value === '1') return '1';
            if (value === false || value === 0 || value === '0') return '0';
            return '';
        },
        placeTotal(area) {
            return area?.place_price ?? null;
        },
        tentTotal(area) {
            if (area?.place_price === null || area?.place_price === undefined) return null;
            return Number(area.place_price) + Number(area.tent_price || 0);
        },
        color(booth) {
            if (booth.status === 'blocked') return 'border border-slate-700 bg-slate-500/70';
            if (booth.status === 'occupied') return 'border border-red-800 bg-red-600/70';
            if (booth.status === 'held') return 'border border-amber-700 bg-amber-400/75';
            if (booth.size === '3x3') return 'border border-gold/80 bg-transparent hover:bg-white/25';
            return 'border border-transparent bg-transparent hover:bg-white/25';
        },
        money(value) {
            if (value === null || value === undefined) return 'Ditentukan panitia';
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
        },
        choose(booth) {
            this.active = booth;
        },
        chooseLandmark(mark) {
            this.active = {
                zone: 'Fasilitas',
                code: mark.label,
                size_label: mark.note,
                status_label: 'Bukan lapak sewa',
                type: 'landmark',
            };
        },
        startPointer(event) {
            if (event.target.closest('button')) return;
            this.panning = true;
            this.lastX = event.clientX;
            this.lastY = event.clientY;
        },
        startBooth(booth, event) {
            if (this.mode !== 'edit') return;
            event.stopPropagation();
            this.dragBooth = booth;
            this.active = booth;
            this.dragStart = { clientX: event.clientX, clientY: event.clientY, x: booth.x, y: booth.y };
        },
        movePointer(event) {
            if (this.dragBooth && this.$refs.stage) {
                const rect = this.$refs.stage.getBoundingClientRect();
                const dx = ((event.clientX - this.dragStart.clientX) / rect.width) * 100;
                const dy = ((event.clientY - this.dragStart.clientY) / rect.height) * 100;
                this.dragBooth.x = this.clamp(this.dragStart.x + dx, 0, 100 - this.dragBooth.w);
                this.dragBooth.y = this.clamp(this.dragStart.y + dy, 0, 100 - this.dragBooth.h);
                return;
            }
            if (!this.panning) return;
            this.panX += event.clientX - this.lastX;
            this.panY += event.clientY - this.lastY;
            this.lastX = event.clientX;
            this.lastY = event.clientY;
        },
        endPointer() {
            this.panning = false;
            this.dragBooth = null;
        },
        zoom(delta) {
            this.scale = Math.min(3, Math.max(1, Math.round((this.scale + delta) * 10) / 10));
        },
        onWheel(event) {
            this.zoom(event.deltaY < 0 ? 0.15 : -0.15);
        },
        clamp(value, min, max) {
            return Math.min(max, Math.max(min, value));
        },
        async refresh() {
            try {
                const response = await fetch(this.feedUrl, { headers: { Accept: 'application/json' } });
                if (!response.ok) return;
                const data = await response.json();
                this.booths = data.booths || this.booths;
                if (data.areas) {
                    this.areas = data.areas;
                    this.selected = this.selected.filter((number) => {
                        const area = this.areaOf(number);
                        return area && (area.available > 0 || this.isSelected(number));
                    });
                    this.selected.forEach((number) => {
                        const area = this.areaOf(number);
                        if (area && area.available < 1) {
                            this.selected = this.selected.filter((value) => value !== number);
                            this.clearTent(number);
                            this.clearQty(number);
                            this.notice = `Kotak ${number} baru saja penuh. Pilih nomor lain.`;
                            return;
                        }
                        if (area && this.quantity(number) > area.available) {
                            this.quantities = { ...this.quantities, [number]: area.available };
                            this.notice = `Kotak ${number} tersisa ${area.available} slot. Jumlah pilihan disesuaikan.`;
                        }
                    });
                }
            } catch (error) {
                // Peta tetap menampilkan data terakhir bila jaringan terganggu.
            }
        },
        async save() {
            this.saving = true;
            this.notice = '';
            const response = await fetch(this.saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    booths: this.booths.map((booth) => ({
                        id: booth.id,
                        x: Math.round(booth.x * 100) / 100,
                        y: Math.round(booth.y * 100) / 100,
                        w: booth.w,
                        h: booth.h,
                    })),
                }),
            });
            this.saving = false;
            this.notice = response.ok ? 'Posisi lapak disimpan.' : 'Posisi belum tersimpan.';
        },
    }));
}
