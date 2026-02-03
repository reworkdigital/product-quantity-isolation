<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;
use App\Models\ProductToWarehouseLocation;
use App\Models\WarehouseLocation;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
        ];
    }

    /**
     * create a product to warehouse location with the given stock levels;
     *
     * if no warehouse location is provided, a new one will be created
     */
    public function withWarehouseStock(
        ?WarehouseLocation $warehouse = null,
        int $on_hand_quantity = 1,
        int $pre_order_quantity = 0,
        int $orderable_quantity = 0,
        int $threshold = 0,
    ): Factory {
        return $this->afterCreating(function (Product $product) use (
            $on_hand_quantity,
            $pre_order_quantity,
            $orderable_quantity,
            $threshold,
            $warehouse,
        ) {
            $warehouse_location_uuid = ($warehouse ?? WarehouseLocation::factory()->create())->uuid;

            ProductToWarehouseLocation::factory()->create([
                'product_id' => $product->id,
                'warehouse_location_uuid' => $warehouse_location_uuid,
                'on_hand_quantity' => $on_hand_quantity,
                'pre_order_quantity' => $pre_order_quantity,
                'orderable_quantity' => $orderable_quantity,
                'threshold' => $threshold,
            ]);
        });
    }
}
