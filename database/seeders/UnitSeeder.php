<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            [
                'code' => 'PCS',
                'name' => 'Pieces',
                'symbol' => 'pcs',
                'type' => 'quantity',
                'is_active' => true,
            ],
            [
                'code' => 'KG',
                'name' => 'Kilogram',
                'symbol' => 'kg',
                'type' => 'weight',
                'is_active' => true,
            ],
            [
                'code' => 'L',
                'name' => 'Liter',
                'symbol' => 'L',
                'type' => 'volume',
                'is_active' => true,
            ],
            [
                'code' => 'M',
                'name' => 'Meter',
                'symbol' => 'm',
                'type' => 'length',
                'is_active' => true,
            ],
            [
                'code' => 'BOX',
                'name' => 'Box',
                'symbol' => 'box',
                'type' => 'quantity',
                'is_active' => true,
            ],
            [
                'code' => 'SET',
                'name' => 'Set',
                'symbol' => 'set',
                'type' => 'quantity',
                'is_active' => true,
            ],
            [
                'code' => 'PACK',
                'name' => 'Package',
                'symbol' => 'pack',
                'type' => 'quantity',
                'is_active' => true,
            ],
            [
                'code' => 'HOUR',
                'name' => 'Hour',
                'symbol' => 'hr',
                'type' => 'time',
                'is_active' => true,
            ],
            [
                'code' => 'SQM',
                'name' => 'Square Meter',
                'symbol' => 'm²',
                'type' => 'area',
                'is_active' => true,
            ],
            [
                'code' => 'UNIT',
                'name' => 'Unit',
                'symbol' => 'unit',
                'type' => 'quantity',
                'is_active' => true,
            ],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }
    }
}
