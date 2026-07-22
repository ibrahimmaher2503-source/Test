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
        Category::factory()->createMany([
            ['name_en' => 'Beverages', 'name_ar' => 'مشروبات'],
            ['name_en' => 'Snacks', 'name_ar' => 'سناكس'],
            ['name_en' => 'Cleaning', 'name_ar' => 'منظفات'],
        ]);
    }
}
