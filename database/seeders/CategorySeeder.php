<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'code' => 'electronics',
                'description' => 'Electronic devices and components',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Office Supplies',
                'slug' => 'office-supplies',
                'code' => 'office-supplies',
                'description' => 'Office stationery and equipment',
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'name' => 'Furniture',
                'slug' => 'furniture',
                'code' => 'furniture',
                'description' => 'Office and home furniture',
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'name' => 'Raw Materials',
                'slug' => 'raw-materials',
                'code' => 'raw-materials',
                'description' => 'Manufacturing raw materials',
                'is_active' => true,
                'sort_order' => 40,
            ],
            [
                'name' => 'Tools & Equipment',
                'slug' => 'tools-equipment',
                'code' => 'tools-equipment',
                'description' => 'Industrial tools and equipment',
                'is_active' => true,
                'sort_order' => 50,
            ],
            [
                'name' => 'Safety Equipment',
                'slug' => 'safety-equipment',
                'code' => 'safety-equipment',
                'description' => 'Personal protective equipment',
                'is_active' => true,
                'sort_order' => 60,
            ],
            [
                'name' => 'Packaging Materials',
                'slug' => 'packaging-materials',
                'code' => 'packaging-materials',
                'description' => 'Boxes, tape, and packaging supplies',
                'is_active' => true,
                'sort_order' => 70,
            ],
            [
                'name' => 'Cleaning Supplies',
                'slug' => 'cleaning-supplies',
                'code' => 'cleaning-supplies',
                'description' => 'Cleaning products and equipment',
                'is_active' => true,
                'sort_order' => 80,
            ],
            [
                'name' => 'IT Hardware',
                'slug' => 'it-hardware',
                'code' => 'it-hardware',
                'description' => 'Computers, servers, and networking equipment',
                'is_active' => true,
                'sort_order' => 90,
            ],
            [
                'name' => 'Software Licenses',
                'slug' => 'software-licenses',
                'code' => 'software-licenses',
                'description' => 'Software products and subscriptions',
                'is_active' => true,
                'sort_order' => 100,
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::create($categoryData);
        }
    }
}
