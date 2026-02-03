<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AllocatedItem;
use App\Models\OrderItem;
use App\Models\WarehouseLocation;

class AllocatedItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AllocatedItem::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $order_item = OrderItem::factory()->create();

        return [
            'model' => 'OrderItem',
            'model_id' => $order_item->id,
            'warehouse_location_uuid' => WarehouseLocation::factory(),
            'allocated_quantity' => $order_item->quantity,
            'required_quantity' => $order_item->quantity,
        ];
    }
}
