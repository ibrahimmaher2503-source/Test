<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####??')),
            'barcode' => $this->faker->unique()->ean13(),
            'barcode_source' => 'generated',
            'name_en' => ucfirst($name),
            'name_ar' => $name,
            'category_id' => Category::factory(),
            'unit' => $this->faker->randomElement(['pcs', 'box', 'kg']),
            'status' => 'active',
            'is_active' => true,
        ];
    }

    public function withoutBarcode(): static
    {
        return $this->state(fn (array $attributes): array => [
            'barcode' => null,
            'barcode_source' => 'generated',
        ]);
    }

    public function pendingReview(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending_review',
            'barcode_source' => 'existing',
        ]);
    }
}
