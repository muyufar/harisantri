# HSN 2026 — Pendaftaran Lapak UMKM & Sponsorship

Aplikasi pendaftaran lapak untuk Hari Santri Nasional PCNU Kabupaten Magelang 2026. Pelaku UMKM mendaftar tanpa akun sandi. Sponsorship diajukan tanpa harga otomatis. Panitia mengelola slot, denah, pembayaran transfer, laporan, dan check-in QR.

Stack: Laravel 12, MySQL, Tailwind CSS, Alpine.js. Zona waktu `Asia/Jakarta`.

## Yang perlu diganti sebelum tayang

Seeder membuat akun pengembangan. **Ganti kata sandi sebelum server dapat diakses orang lain.**

| Peran | Email |
|---|---|
| Super Admin | `admin@pcnu-magelang.test` |
| Admin Pendaftaran | `pendaftaran@pcnu-magelang.test` |
| Admin Keuangan | `keuangan@pcnu-magelang.test` |
| Petugas Lapangan | `lapangan@pcnu-magelang.test` |

Kata sandi awal: nilai `ADMIN_SEED_PASSWORD` di `.env`, atau `ChangeMe!HSN2026` jika variabel itu kosong.

Rekening `0000000000` dan nomor WhatsApp panitia juga bawaan. Logo bawaan adalah lambang Nahdlatul Ulama. Logo pengganti serta denah acara diunggah lewat menu **Informasi event**. Posisi lapak di denah awal hanya titik mulai; geser lewat **Editor peta** sesuai lapangan.

## Instalasi lokal

Butuh PHP 8.2 dengan ekstensi `pdo_mysql`, `gd`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, dan `zip`, plus Composer, Node.js, dan MySQL.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Atur `.env`:

```env
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=harisantri
DB_USERNAME=root
DB_PASSWORD=
ADMIN_SEED_PASSWORD=ganti-dengan-sandi-panjang
```

```bash
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000`. Di Laragon, arahkan document root ke folder `public`, lalu samakan `APP_URL` dengan domain situs agar tautan bertanda tangan (halaman pembayaran dan PDF) valid.

## Penjadwalan hold

Slot tanpa bukti pembayaran dilepas setelah `hold_hours` (bawaan 24 jam). Bukti yang sudah diunggah tidak dilepas sampai verifikasi selesai. Jalankan penjadwal setiap menit:

```bash
php artisan schedule:work
```

Di server:

```cron
* * * * * php /path/ke/aplikasi/artisan schedule:run >> /dev/null 2>&1
```

Perintah manual: `php artisan hsn:release-expired`.

## Cadangan database

```bash
php artisan hsn:backup
php artisan hsn:restore storage/app/backups/nama-file.sql --force
```

File cadangan masuk `storage/app/backups`. Untuk MySQL di luar Laragon, isi `MYSQLDUMP_PATH` dan `MYSQL_PATH`. Pulihkan hanya saat aplikasi dalam mode perawatan dan Anda sudah menyimpan salinan data yang sedang berjalan.

Setara manual:

```bash
mysqldump -h 127.0.0.1 -u pengguna harisantri > cadangan.sql
mysql -h 127.0.0.1 -u pengguna harisantri < cadangan.sql
```

## Deployment hosting PHP/MySQL

1. Unggah kode. Jangan unggah `.env` publik.
2. `composer install --no-dev --optimize-autoloader`
3. `npm ci && npm run build` (boleh di mesin lokal, lalu unggah `public/build`)
4. Document root domain mengarah ke `public/`.
5. Buat database dan isi `.env` production: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` persis URL situs.
6. `php artisan key:generate`, `php artisan migrate --force --seed` hanya pada instalasi pertama.
7. `php artisan storage:link`
8. Izin tulis untuk `storage/` dan `bootstrap/cache/`.
9. Ganti sandi seeder, rekening, dan nomor WhatsApp.
10. Pasang cron `schedule:run`.
11. `php artisan config:cache && php artisan route:cache && php artisan view:cache`

Bukti pembayaran disimpan di disk privat `storage/app/private/payment-proofs`, tidak lewat URL publik. Foto usaha juga privat dan hanya dibuka panitia yang berwenang.

## Pembayaran

Versi ini transfer manual. `App\Contracts\PaymentGateway` dan kolom `payments.gateway` menyiapkan penyambung QRIS atau payment gateway resmi berikutnya tanpa mengubah snapshot nominal pesanan.

## Pengujian

```bash
php artisan test
```

Pengujian mencakup kombinasi harga, batas tiga slot per WhatsApp, booking ganda, kedaluwarsa hold, bukti yang menahan slot, dan alur verifikasi.
