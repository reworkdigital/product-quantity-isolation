<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use App\Models\OrderItemGroup;

class OrderItemGroupFactory extends Factory
{
    protected $model = OrderItemGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $random_order = Order::inRandomOrder()->first();

        if (!$random_order) {
            $random_order = Order::factory()->create();
        }

        return [
            'uuid' => $this->faker->uuid,
            'order_id' => $random_order->id,
        ];
    }
}
