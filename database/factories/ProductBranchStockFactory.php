<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBranchStock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductBranchStock>
 */
class ProductBranchStockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'branch_id' => Branch::factory(),
            'expected_quantity' => $this->faker->numberBetween(0, 100),
        ];
    }
}
