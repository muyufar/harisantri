<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class HsnTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-17 09:00:00');
        $this->seed();
    }

    /** @return array<string, string|null> */
    protected function applicant(string $phone = '081234567890', string $business = 'Bakpia Santri'): array
    {
        return [
            'full_name' => 'Siti Aminah',
            'business_name' => $business,
            'whatsapp' => $phone,
            'email' => null,
            'address' => 'Jl. Soekarno Hatta, Mungkid',
            'district' => 'Mungkid',
            'regency' => 'Kabupaten Magelang',
            'product_type' => 'Makanan & minuman',
            'product_description' => 'Bakpia isi kacang dan keju untuk bazar santri.',
            'special_notes' => null,
        ];
    }
}
