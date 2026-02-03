<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        return [
            'id' => $this->faker->unique()->numberBetween(1, 1000000),
            'restore_key' => $this->faker->uuid(),
        ];
    }
}
