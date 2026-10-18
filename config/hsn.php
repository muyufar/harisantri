<?php

return [
    'regencies' => [
        'Kabupaten Magelang',
        'Kota Magelang',
        'Lainnya',
    ],

    'districts' => [
        'Bandongan',
        'Borobudur',
        'Candimulyo',
        'Dukun',
        'Grabag',
        'Kajoran',
        'Kaliangkrik',
        'Mertoyudan',
        'Mungkid',
        'Muntilan',
        'Ngablak',
        'Ngluwar',
        'Pakis',
        'Salam',
        'Salaman',
        'Sawangan',
        'Secang',
        'Srumbung',
        'Tegalrejo',
        'Tempuran',
        'Windusari',
        'Magelang Tengah',
        'Magelang Selatan',
        'Magelang Utara',
    ],

    'product_types' => [
        'Makanan & minuman',
        'Busana muslim',
        'Kerajinan',
        'Buku & alat tulis',
        'Hasil pertanian',
        'Jasa',
        'Lainnya',
    ],

    /*
    |--------------------------------------------------------------------------
    | Area tenda UMKM pada denah (klik nomor 1–8)
    |--------------------------------------------------------------------------
    |
    | Kapasitas mengikuti legenda denah. Koordinat x/y/w/h dalam persen gambar
    | dipakai sebagai area klik. Slot konkret tetap dibuat sebagai booth agar
    | panitia bisa memindahkan posisi manual kemudian.
    |
    */
    'tent_areas' => [
        // Koordinat diukur dari badge nomor + baris tenda pada denah 1024x660.
        1 => ['name' => 'Kotak 1', 'group' => 'Tenda Utara', 'size' => '3x3', 'slots' => 15, 'color' => '#e11d48', 'x' => 29.0, 'y' => 30.5, 'w' => 20.0, 'h' => 4.0],
        2 => ['name' => 'Kotak 2', 'group' => 'Tenda Utara', 'size' => '2x2', 'slots' => 24, 'color' => '#2563eb', 'x' => 29.5, 'y' => 25.5, 'w' => 20.0, 'h' => 4.0],
        3 => ['name' => 'Kotak 3', 'group' => 'Tenda Utara', 'size' => '2x2', 'slots' => 18, 'color' => '#db2777', 'x' => 30.5, 'y' => 20.2, 'w' => 19.0, 'h' => 4.0],
        4 => ['name' => 'Kotak 4', 'group' => 'Tenda Utara', 'size' => '2x2', 'slots' => 17, 'color' => '#ea580c', 'x' => 31.5, 'y' => 15.8, 'w' => 18.0, 'h' => 4.0],
        5 => ['name' => 'Kotak 5', 'group' => 'Tenda Utara', 'size' => '2x2', 'slots' => 9, 'color' => '#7c3aed', 'x' => 37.5, 'y' => 11.2, 'w' => 12.5, 'h' => 4.0],
        6 => ['name' => 'Kotak 6', 'group' => 'Tenda Parkir', 'size' => '3x3', 'slots' => 3, 'color' => '#16a34a', 'x' => 53.0, 'y' => 30.5, 'w' => 7.0, 'h' => 4.8],
        7 => ['name' => 'Kotak 7', 'group' => 'Tenda Parkir', 'size' => '3x3', 'slots' => 5, 'color' => '#0891b2', 'x' => 56.8, 'y' => 34.0, 'w' => 4.2, 'h' => 11.0],
        8 => ['name' => 'Kotak 8', 'group' => 'Tenda Parkir', 'size' => '3x3', 'slots' => 2, 'color' => '#ca8a04', 'x' => 52.8, 'y' => 37.0, 'w' => 3.8, 'h' => 7.2],
    ],

    'permissions' => [
        'dashboard.view' => 'Melihat dashboard',
        'registrations.view' => 'Melihat pendaftaran',
        'registrations.manage' => 'Mengelola pendaftaran',
        'sponsorships.manage' => 'Mengelola sponsorship',
        'booths.manage' => 'Mengelola lapak dan peta',
        'payments.verify' => 'Verifikasi pembayaran',
        'prices.manage' => 'Mengelola harga',
        'users.manage' => 'Mengelola akun panitia',
        'settings.manage' => 'Mengelola informasi event',
        'reports.export' => 'Ekspor laporan',
        'checkin.perform' => 'Check-in lapangan',
        'audit.view' => 'Melihat audit',
    ],

    'roles' => [
        'super-admin' => [
            'name' => 'Super Admin',
            'permissions' => '*',
        ],
        'registration-admin' => [
            'name' => 'Admin Pendaftaran',
            'permissions' => [
                'dashboard.view',
                'registrations.view',
                'registrations.manage',
                'sponsorships.manage',
                'booths.manage',
                'settings.manage',
                'reports.export',
            ],
        ],
        'finance-admin' => [
            'name' => 'Admin Keuangan',
            'permissions' => [
                'dashboard.view',
                'registrations.view',
                'payments.verify',
                'prices.manage',
                'reports.export',
                'audit.view',
            ],
        ],
        'field-officer' => [
            'name' => 'Petugas Lapangan',
            'permissions' => [
                'dashboard.view',
                'registrations.view',
                'checkin.perform',
            ],
        ],
    ],
];
