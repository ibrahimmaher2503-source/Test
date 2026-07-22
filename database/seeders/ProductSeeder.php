<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBranchStock;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();

        $withBarcode = Product::factory()
            ->count(15)
            ->recycle($categories)
            ->create();

        $withoutBarcode = Product::factory()
            ->withoutBarcode()
            ->recycle($categories)
            ->count(3)
            ->create();

        $pendingReview = Product::factory()
            ->pendingReview()
            ->recycle($categories)
            ->count(2)
            ->create();

        $branches = Branch::all();
        $withBarcode->merge($withoutBarcode)->merge($pendingReview)->each(function (Product $product) use ($branches): void {
            foreach ($branches as $branch) {
                ProductBranchStock::factory()->create([
                    'product_id' => $product->id,
                    'branch_id' => $branch->id,
                    'expected_quantity' => fake()->numberBetween(0, 100),
                ]);
            }
        });
    }
}
