<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\WarehouseLocation;

/**
 * @extends Factory<StockTransfer>
 */
class StockTransferFactory extends Factory
{
    protected $model = StockTransfer::class;

    public function definition(): array
    {
        return [
            'warehouse_from_uuid' => WarehouseLocation::factory()->state(['name' => 'Source']),
            'warehouse_to_uuid' => WarehouseLocation::factory()->state(['name' => 'Destination']),
            'received_at' => null,
        ];
    }

    public function withItems(int $count = 2): static
    {
        return $this->afterCreating(function (StockTransfer $transfer) use ($count) {
            StockTransferItem::factory()->count($count)->create([
                'stock_transfer_id' => $transfer->id,
            ]);
        });
    }

    public function noItems(): static
    {
        return $this->state([]);
    }

    public function untransferred(): self
    {
        return $this->state([
            'transferred_at' => null,
            'received_at' => null,
        ]);
    }

    public function receivedNow(): self
    {
        return $this->state([
            'received_at' => now(),
        ]);
    }
}
