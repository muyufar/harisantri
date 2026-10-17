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
