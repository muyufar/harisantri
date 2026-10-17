export function registerBoothMap(Alpine) {
    Alpine.data('boothMap', (config) => ({
        booths: config.booths || [],
        mode: config.mode || 'view',
        max: Number(config.max || 3),
        floorplan: config.floorplan,
        landmarks: config.landmarks || [],
        zones: config.zones || [],
        saveUrl: config.saveUrl,
        feedUrl: config.feedUrl,
        selected: [],
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
            this.syncFromForm();
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
        syncFromForm() {
            this.selected = Array.from(document.querySelectorAll('input[name="booth_ids[]"]:checked')).map((input) => Number(input.value));
        },
        isSelected(id) {
            return this.selected.includes(Number(id));
        },
        boothOf(id) {
            return this.booths.find((booth) => Number(booth.id) === Number(id));
        },
        rowVisible(id) {
            const booth = this.boothOf(id);
            if (!booth) return false;
            const query = this.query.trim().toLowerCase();
            if (query && !`${booth.code} ${booth.zone || ''}`.toLowerCase().includes(query)) return false;
            if (this.filterSize !== 'all' && booth.size !== this.filterSize) return false;
            if (this.filterZone !== 'all' && String(booth.zone_id) !== String(this.filterZone)) return false;
            if (this.filterStatus !== 'all' && booth.status !== this.filterStatus) return false;
            return true;
        },
        zoneHasVisible(zoneId) {
            return this.booths.some((booth) => String(booth.zone_id) === String(zoneId) && this.rowVisible(booth.id));
        },
        get anyListed() {
            return this.booths.some((booth) => (this.mode !== 'select' || booth.type === 'umkm') && this.rowVisible(booth.id));
        },
        get zoneSummary() {
            return (this.zones || []).map((zone) => {
                const rows = this.booths.filter((booth) => String(booth.zone_id) === String(zone.id));
                return {
                    id: zone.id,
                    name: zone.name,
                    total: rows.length,
                    available: rows.filter((booth) => booth.status === 'available').length,
                    held: rows.filter((booth) => booth.status === 'held').length,
                    occupied: rows.filter((booth) => booth.status === 'occupied').length,
                    blocked: rows.filter((booth) => booth.status === 'blocked').length,
                };
            }).filter((zone) => zone.total > 0);
        },
        statusClass(status) {
            if (status === 'occupied') return 'bg-red-600 text-white';
            if (status === 'held') return 'bg-amber-400 text-slate-900';
            if (status === 'blocked') return 'bg-slate-500 text-white';
            return 'bg-nu text-white';
        },
        onListChange(event) {
            const checked = document.querySelectorAll('input[name="booth_ids[]"]:checked').length;
            if (event.target.checked && checked > this.max) {
                event.target.checked = false;
                this.notice = `Kuota pilihan pada pendaftaran ini ${this.max} lapak.`;
            } else {
                this.notice = '';
            }
            this.syncFromForm();
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
                this.booths = data.booths;
                data.booths.forEach((booth) => {
                    const input = document.getElementById(`booth-check-${booth.id}`);
                    if (input?.checked && booth.status !== 'available') {
                        input.checked = false;
                        this.notice = `${booth.code} baru saja dipesan. Pilih slot lain.`;
                    }
                });
                this.syncFromForm();
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
