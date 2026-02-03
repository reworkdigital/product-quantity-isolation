<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\OrderStatus;

class OrderStatusFactory extends Factory
{
    protected $model = OrderStatus::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'slug' => fake()->unique()->slug,
            'name' => fake()->unique()->word,
        ];
    }
}
