<?php

namespace Database\Seeders;

use App\Models\PriceList;
use Illuminate\Database\Seeder;

class PriceListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $priceLists = [
            [
                'name' => 'Standard Retail',
                'code' => 'STD-RETAIL',
                'currency' => 'USD',
                'is_active' => true,
                'valid_from' => now(),
                'valid_to' => now()->addYear(),
            ],
            [
                'name' => 'Wholesale',
                'code' => 'WHOLESALE',
                'currency' => 'USD',
                'is_active' => true,
                'valid_from' => now(),
                'valid_to' => now()->addYear(),
            ],
            [
                'name' => 'VIP Customer',
                'code' => 'VIP',
                'currency' => 'USD',
                'is_active' => true,
                'valid_from' => now(),
                'valid_to' => now()->addYear(),
            ],
            [
                'name' => 'European Market',
                'code' => 'EU-MARKET',
                'currency' => 'EUR',
                'is_active' => true,
                'valid_from' => now(),
                'valid_to' => now()->addYear(),
            ],
            [
                'name' => 'UK Market',
                'code' => 'UK-MARKET',
                'currency' => 'GBP',
                'is_active' => true,
                'valid_from' => now(),
                'valid_to' => now()->addYear(),
            ],
            [
                'name' => 'Hungary Market',
                'code' => 'HU-MARKET',
                'currency' => 'HUF',
                'is_active' => true,
                'valid_from' => now(),
                'valid_to' => now()->addYear(),
            ],
            [
                'name' => 'Partner Pricing',
                'code' => 'PARTNER',
                'currency' => 'USD',
                'is_active' => true,
                'valid_from' => now(),
                'valid_to' => now()->addMonths(6),
            ],
            [
                'name' => 'Seasonal Discount',
                'code' => 'SEASONAL',
                'currency' => 'USD',
                'is_active' => true,
                'valid_from' => now(),
                'valid_to' => now()->addMonths(3),
            ],
            [
                'name' => 'Volume Discount',
                'code' => 'VOLUME',
                'currency' => 'USD',
                'is_active' => true,
                'valid_from' => now(),
                'valid_to' => now()->addYear(),
            ],
            [
                'name' => 'Promotional',
                'code' => 'PROMO',
                'currency' => 'USD',
                'is_active' => true,
                'valid_from' => now(),
                'valid_to' => now()->addMonth(),
            ],
        ];

        foreach ($priceLists as $priceList) {
            PriceList::create($priceList);
        }
    }
}
