import './bootstrap';
import Alpine from 'alpinejs';
import { registerBoothMap } from './booth-map';

document.addEventListener('alpine:init', () => {
    registerBoothMap(Alpine);

    Alpine.data('countdown', (iso) => ({
        label: 'Menuju pelaksanaan',
        parts: [],
        done: false,
        init() {
            this.tick();
            this.timer = setInterval(() => this.tick(), 1000);
        },
        tick() {
            const target = new Date(iso).getTime();
            const diff = target - Date.now();
            if (Number.isNaN(target)) {
                this.label = 'Jadwal menyusul';
                this.parts = [];
                return;
            }
            if (diff <= 0) {
                this.done = true;
                this.label = 'Acara telah dimulai';
                this.parts = [];
                clearInterval(this.timer);
                return;
            }
            const total = Math.floor(diff / 1000);
            const pad = (value) => String(value).padStart(2, '0');
            this.parts = [
                { key: 'hari', value: pad(Math.floor(total / 86400)) },
                { key: 'jam', value: pad(Math.floor((total % 86400) / 3600)) },
                { key: 'menit', value: pad(Math.floor((total % 3600) / 60)) },
                { key: 'detik', value: pad(total % 60) },
            ];
        },
    }));

    Alpine.data('formState', () => ({
        sending: false,
    }));
});

window.Alpine = Alpine;
Alpine.start();
