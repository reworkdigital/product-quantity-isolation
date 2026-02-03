<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\WarehouseLocation;
use Illuminate\Support\Str;

/**
 * @extends Factory<WarehouseLocation>
 */
class WarehouseLocationFactory extends Factory
{
    protected $model = WarehouseLocation::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Ensure slug <= 50 characters to satisfy schema (warehouse_locations.slug length 50)
        $base = Str::slug($this->faker->words(2, true)); // compact 2-word slug
        $suffix = Str::lower(Str::random(6)); // add short random to reduce collision chance
        $slug = substr($base . '-' . $suffix, 0, 50);
        $slug = rtrim($slug, '-');

        return [
            'uuid' => $this->faker->uuid,
            'slug' => $slug,
            'name' => $this->faker->company,
        ];
    }
}
