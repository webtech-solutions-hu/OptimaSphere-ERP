<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles first
        $this->call([
            RoleSeeder::class,
        ]);

        // Seed master data
        $this->call([
            CategorySeeder::class,
            UnitSeeder::class,
            PriceListSeeder::class,
            SupplierSeeder::class,
            CustomerSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
