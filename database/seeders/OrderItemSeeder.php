<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use Database\Seeders\Concerns\WithProgressBar;

class OrderItemSeeder extends Seeder
{
    use WithProgressBar;

    public function run(): void
    {
        $productIds = Product::query()->pluck('id')->all();
        if (empty($productIds)) return;

        // Bias towards popular products
        $popularCount = max(1, (int) floor(count($productIds) * 0.05));
        shuffle($productIds);
        $popular = array_slice($productIds, 0, $popularCount);

        // placeholder-safe chunking: 3 columns per row
        $columnsPerRow = 3;
        $maxPlaceholders = 65000;
        $maxRowsByPlaceholders = (int) floor($maxPlaceholders / $columnsPerRow);
        $itemChunk = min(20000, max(1000, $maxRowsByPlaceholders - 50));

        $totalGroups = (int) DB::table('order_item_groups')->count();
        $bar = $this->progressStart($totalGroups, 'Seeding order items (per group)');

        $buffer = [];

        DB::table('order_item_groups')->orderBy('uuid')->select(['uuid'])->chunk(5000, function ($groups) use (&$buffer, $itemChunk, $popular, $productIds, $bar) {
            foreach ($groups as $g) {
                $count = rand(1, 10);
                for ($i = 0; $i < $count; $i++) {
                    $pid = (rand(1,100) <= 70) ? $popular[array_rand($popular)] : $productIds[array_rand($productIds)];
                    $buffer[] = [
                        'order_item_group_uuid' => $g->uuid,
                        'product_id' => $pid,
                        'quantity' => rand(1, 10),
                    ];

                    if (count($buffer) >= $itemChunk) {
                        DB::table('order_item')->insert($buffer);
                        $buffer = [];
                    }
                }
                $this->progressAdvance($bar, 1); // per group processed
            }
        });

        if (!empty($buffer)) {
            DB::table('order_item')->insert($buffer);
        }

        $this->progressFinish($bar, 'Order items seeded.');
    }
}
