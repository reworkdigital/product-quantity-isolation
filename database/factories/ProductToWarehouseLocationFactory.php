<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;
use App\Models\ProductToWarehouseLocation;
use App\Models\WarehouseLocation;

/**
 * @extends Factory<ProductToWarehouseLocation>
 */
class ProductToWarehouseLocationFactory extends Factory
{
    protected $model = ProductToWarehouseLocation::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'warehouse_location_uuid' => WarehouseLocation::factory(),
            'on_hand_quantity' => $this->faker->numberBetween(0, 100),
            'pre_order_quantity' => $this->faker->numberBetween(0, 50),
            'orderable_quantity' => $this->faker->numberBetween(0, 50),
            'threshold' => $this->faker->numberBetween(0, 20),
        ];
    }
}
