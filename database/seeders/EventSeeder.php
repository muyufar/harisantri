<?php

namespace Database\Seeders;

use App\Models\Booth;
use App\Models\BoothCategory;
use App\Models\BoothZone;
use App\Models\Event;
use App\Models\EventSetting;
use App\Models\PriceRule;
use App\Models\SponsorshipCategory;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::query()->updateOrCreate(
            ['slug' => 'hsn-2026'],
            [
                'name' => 'Hari Santri Nasional PCNU Kabupaten Magelang 2026',
                'activity_name' => 'Jalan Sehat Santri & Bazar UMKM',
                'starts_at' => '2026-10-31 07:00:00',
                'ends_at' => null,
                'venue' => 'Halaman MAJT Kabupaten Magelang',
                'organizer' => 'PCNU Kabupaten Magelang',
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
            ]
        );

        $settings = [
            'tagline' => 'Pendaftaran Lapak UMKM & Sponsorship HSN 2026',
            'description' => 'PCNU Kabupaten Magelang membuka pendaftaran lapak UMKM dan pengajuan sponsorship untuk Jalan Sehat Santri dan Bazar UMKM pada Hari Santri Nasional 2026.',
            'registration_closes_at' => '2026-10-28 23:59:00',
            'hold_hours' => '24',
            'max_slots_per_applicant' => '3',
            'contact_name' => 'Panitia HSN PCNU Kabupaten Magelang',
            'contact_whatsapp' => '6282340715548',
            'contact_email' => 'muyufar@numart.id',
            'instagram_url' => '',
            'facebook_url' => '',
            'youtube_url' => '',
            'qris_merchant_name' => 'PCNU KAB MAGELANG',
            'qris_nmid' => 'ID1026618538122',
            'qris_terminal_id' => 'A01',
            'terms' => "Pendaftar memilih slot yang masih tersedia, maksimal 3 slot untuk satu nomor WhatsApp, termasuk pesanan yang masih berjalan.\nHarga dihitung panitia dari data harga yang berlaku dan tidak mengikuti nominal yang diubah di peramban.\nSlot ditahan selama masa berlaku yang ditetapkan panitia. Jika bukti pembayaran belum masuk sampai batas waktu, slot dilepas.\nJika bukti sudah diunggah, slot tetap ditahan sampai panitia selesai memeriksa.\nBukti lapak terkonfirmasi diterbitkan setelah pembayaran disetujui.",
            'cancellation_policy' => 'Pembatalan dilakukan panitia. Slot yang dibatalkan kembali tersedia untuk pendaftar lain. Kebijakan ini dapat diperbarui panitia melalui dashboard.',
            'refund_policy' => 'Pengembalian dana dilakukan manual oleh panitia keuangan apabila pembayaran sudah masuk dan pendaftaran dibatalkan. Sistem hanya mencatat status refund, tidak memindahkan dana secara otomatis.',
            'faqs' => json_encode([
                [
                    'question' => 'Apakah pelaku UMKM wajib membuat akun?',
                    'answer' => 'Tidak. Pendaftaran UMKM memakai data penanggung jawab dan nomor WhatsApp. Kata sandi hanya untuk panitia.',
                ],
                [
                    'question' => 'Berapa lapak yang bisa dipesan?',
                    'answer' => 'Maksimal 3 slot UMKM untuk satu nomor WhatsApp, dihitung dari seluruh pesanan yang masih aktif.',
                ],
                [
                    'question' => 'Apakah tenda wajib?',
                    'answer' => 'Tidak. Tambahan tenda dipilih per slot. Biayanya mengikuti ukuran lapak.',
                ],
                [
                    'question' => 'Bagaimana cara sponsorship?',
                    'answer' => 'Calon sponsor mengajukan formulir tanpa harga otomatis. Panitia menghubungi, menawar harga, lalu menetapkan lokasi.',
                ],
                [
                    'question' => 'Bagaimana cara bayar?',
                    'answer' => 'Bayar lewat QRIS pada halaman pembayaran. Scan dengan aplikasi berlogo QRIS, lalu unggah bukti transaksi.',
                ],
                [
                    'question' => 'Kapan lapak dinyatakan pasti?',
                    'answer' => 'Setelah panitia menyetujui bukti pembayaran QRIS. Sebelum itu, status masih menunggu pembayaran atau verifikasi.',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];

        foreach ($settings as $key => $value) {
            EventSetting::query()->updateOrCreate(
                ['event_id' => $event->id, 'key' => $key],
                ['value' => $value]
            );
        }

        $umkm = BoothCategory::query()->updateOrCreate(
            ['slug' => 'umkm'],
            ['name' => 'Lapak UMKM', 'description' => 'Lapak berbayar dengan ukuran tetap dan tambahan tenda opsional.']
        );
        $sponsorCategory = BoothCategory::query()->updateOrCreate(
            ['slug' => 'sponsorship'],
            ['name' => 'Lapak Sponsorship', 'description' => 'Area kerja sama yang harganya ditetapkan panitia.']
        );

        PriceRule::query()->updateOrCreate(
            ['event_id' => $event->id, 'size' => '2x2'],
            ['place_price' => 100000, 'tent_price' => 100000, 'is_active' => true]
        );
        PriceRule::query()->updateOrCreate(
            ['event_id' => $event->id, 'size' => '3x3'],
            ['place_price' => 150000, 'tent_price' => 150000, 'is_active' => true]
        );

        $depan = $this->zone($event->id, 'UMKM 1 — Sisi Pohon', 'DEPAN', 'umkm', 40, 1);
        $barat = $this->zone($event->id, 'UMKM 2 — Tengah', 'BARAT', 'umkm', 15, 2);
        $timur = $this->zone($event->id, 'UMKM 3 — Belakang Sponsor', 'TIMUR', 'umkm', 15, 3);
        $sponsor = $this->zone($event->id, 'Area Sponsor', 'SPONSOR', 'sponsorship', 4, 4);

        // Koordinat pada gambar denah acara: UMKM 1 di sisi pohon, UMKM 2 di tengah, UMKM 3 dan sponsor di parkir.
        $this->grid($event->id, $depan, $umkm->id, 'umkm', 'mixed', 'U-', 1, 40, 8, 36.2, 21.8, 1.55, 2.05, 1.75, 2.45);
        $this->grid($event->id, $barat, $umkm->id, 'umkm', 'mixed', 'U-', 101, 15, 3, 51.6, 39.2, 2.05, 1.45, 2.45, 1.6);
        $this->grid($event->id, $timur, $umkm->id, 'umkm', 'mixed', 'U-', 201, 15, 3, 67.5, 67.0, 1.3, 1.95, 1.45, 2.5);
        $this->grid($event->id, $sponsor, $sponsorCategory->id, 'sponsorship', null, 'S-', 1, 4, 2, 59.6, 72.4, 2.2, 4.0, 2.5, 4.6);

        foreach ([
            ['Sponsor Utama', 'Dukungan utama kegiatan Jalan Sehat dan Bazar.'],
            ['Sponsor Pendukung', 'Dukungan kegiatan dengan kebutuhan area yang disepakati.'],
            ['Sponsor Media', 'Kemitraan publikasi dan dokumentasi.'],
            ['Sponsor Komunitas', 'Kelembagaan, komunitas, atau instansi mitra.'],
        ] as $index => [$name, $description]) {
            SponsorshipCategory::query()->updateOrCreate(
                ['event_id' => $event->id, 'name' => $name],
                ['description' => $description, 'sort_order' => $index + 1, 'is_active' => true]
            );
        }
    }

    private function zone(int $eventId, string $name, string $code, string $type, int $capacity, int $sort): BoothZone
    {
        return BoothZone::query()->updateOrCreate(
            ['event_id' => $eventId, 'code' => $code],
            ['name' => $name, 'type' => $type, 'capacity' => $capacity, 'sort_order' => $sort, 'is_active' => true]
        );
    }

    private function grid(int $eventId, BoothZone $zone, int $categoryId, string $type, ?string $size, string $prefix, int $start, int $count, int $columns, float $originX, float $originY, float $width, float $height, float $stepX, float $stepY): void
    {
        for ($i = 0; $i < $count; $i++) {
            $column = $i % $columns;
            $row = intdiv($i, $columns);
            $number = $start + $i;
            $booth = Booth::query()->firstOrNew([
                'event_id' => $eventId,
                'code' => $prefix.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            ]);
            $resolvedSize = $size === 'mixed'
                ? (($column + $row) % 2 === 0 ? '2x2' : '3x3')
                : $size;
            $booth->fill([
                'zone_id' => $zone->id,
                'category_id' => $categoryId,
                'type' => $type,
                'size' => $resolvedSize,
                'pos_x' => $originX + ($column * $stepX),
                'pos_y' => $originY + ($row * $stepY),
                'width' => $width,
                'height' => $height,
            ]);
            if (! $booth->exists) {
                $booth->state = 'available';
            }
            $booth->save();
        }
    }
}
