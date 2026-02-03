<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\WarehouseLocation;
use Database\Seeders\Concerns\WithProgressBar;

class AllocatedItemSeeder extends Seeder
{
    use WithProgressBar;

    public function run(): void
    {
        $warehouseUuids = WarehouseLocation::query()->pluck('uuid')->all();
        if (empty($warehouseUuids)) return;

        $now = now();

        // placeholder limit: 8 columns per row
        $columnsPerRow = 8;
        $maxPlaceholders = 65000;
        $maxRowsByPlaceholders = (int) floor($maxPlaceholders / $columnsPerRow);
        $allocChunk = min(5000, max(1000, $maxRowsByPlaceholders - 50));

        $totalOrderItems = (int) DB::table('order_item')->count();
        $bar = $this->progressStart($totalOrderItems, 'Seeding allocated items (per order item)');

        $buffer = [];

        DB::table('order_item')->orderBy('id')->select(['id'])->chunk(5000, function ($items) use (&$buffer, $warehouseUuids, $now, $allocChunk, $bar) {
            foreach ($items as $it) {
                $per = rand(1, 5);
                for ($a = 0; $a < $per; $a++) {
                    $rq = rand(1, 10);
                    $aq = rand(1, 10);
                    $buffer[] = [
                        'model' => 'OrderItem',
                        'model_id' => (int) $it->id,
                        'warehouse_location_uuid' => $warehouseUuids[array_rand($warehouseUuids)],
                        'required_quantity' => $rq,
                        'allocated_quantity' => $aq,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ];

                    if (count($buffer) >= $allocChunk) {
                        DB::table('allocated_item')->insert($buffer);
                        $buffer = [];
                    }
                }
                $this->progressAdvance($bar, 1); // per order item processed
            }
        });

        if (!empty($buffer)) {
            DB::table('allocated_item')->insert($buffer);
        }

        $this->progressFinish($bar, 'Allocated items seeded.');
    }
}
