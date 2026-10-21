<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventSetting;
use App\Models\PriceRule;
use App\Models\SponsorshipCategory;
use App\Support\TentLayout;
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
                'starts_at' => '2026-11-01 07:00:00',
                'ends_at' => null,
                'venue' => 'Halaman MAJT An-Nuur Kabupaten Magelang',
                'organizer' => 'PCNU Kabupaten Magelang',
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
            ]
        );

        $settings = [
            'tagline' => 'Pendaftaran Lapak UMKM & Sponsorship HSN 2026',
            'description' => 'PCNU Kabupaten Magelang membuka pendaftaran lapak UMKM dan pengajuan sponsorship untuk Jalan Sehat Santri dan Bazar UMKM pada Hari Santri Nasional 2026.',
            'registration_closes_at' => '2026-10-31 23:59:00',
            'hold_hours' => '2',
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
            'terms' => "Pendaftar memilih nomor area tenda (1–8) yang masih tersedia, maksimal 3 slot untuk satu nomor WhatsApp, termasuk pesanan yang masih berjalan.\nHarga dihitung panitia dari ukuran kotak yang dipilih dan tidak mengikuti nominal yang diubah di peramban.\nPosisi tepat di dalam kotak ditentukan panitia.\nSlot ditahan selama masa berlaku yang ditetapkan panitia. Jika bukti pembayaran belum masuk sampai batas waktu, slot dilepas.\nJika bukti sudah diunggah, slot tetap ditahan sampai panitia selesai memeriksa.\nBukti lapak terkonfirmasi diterbitkan setelah pembayaran disetujui.",
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
                    'question' => 'Apa arti nomor 1 sampai 8?',
                    'answer' => 'Itu nomor area tenda pada denah. Anda memilih nomor area; panitia yang menempatkan posisi tepat di dalam area itu.',
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

        PriceRule::query()->updateOrCreate(
            ['event_id' => $event->id, 'size' => '2x2'],
            ['place_price' => 100000, 'tent_price' => 100000, 'is_active' => true]
        );
        PriceRule::query()->updateOrCreate(
            ['event_id' => $event->id, 'size' => '3x3'],
            ['place_price' => 150000, 'tent_price' => 150000, 'is_active' => true]
        );

        TentLayout::seed($event);

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
}
