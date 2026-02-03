<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\OrderItem;
use App\Models\OrderItemGroup;
use App\Models\Product;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'order_item_group_uuid' => OrderItemGroup::factory(),
            'product_id' => Product::factory(),
            'quantity' => rand(1, 20),
        ];
    }
}
