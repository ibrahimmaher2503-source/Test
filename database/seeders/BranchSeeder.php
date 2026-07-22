<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Branch::factory()->createMany([
            ['name_en' => 'Downtown Branch', 'name_ar' => 'فرع وسط البلد', 'code' => 'BR-01'],
            ['name_en' => 'Warehouse', 'name_ar' => 'المخزن الرئيسي', 'code' => 'BR-02'],
        ]);
    }
}
