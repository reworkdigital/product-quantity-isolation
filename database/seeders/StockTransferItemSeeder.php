<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use Database\Seeders\Concerns\WithProgressBar;

class StockTransferItemSeeder extends Seeder
{
    use WithProgressBar;

    public function run(): void
    {
        $productIds = Product::query()->pluck('id')->all();
        if (empty($productIds)) {
            return;
        }

        // Popular subset for bias (hot products)
        $popularCount = max(1, (int) floor(count($productIds) * 0.05)); // 5%
        shuffle($productIds);
        $popular = array_slice($productIds, 0, $popularCount);

        $now = now();

        // Placeholder-safe chunking: 5 columns per row
        $columnsPerRow = 5;
        $maxPlaceholders = 65000;
        $maxRowsByPlaceholders = (int) floor($maxPlaceholders / $columnsPerRow);
        $itemChunk = min(10000, max(1000, $maxRowsByPlaceholders - 50));

        $buffer = [];

        $totalTransfers = (int) DB::table('stock_transfers')->count();
        $bar = $this->progressStart($totalTransfers, 'Seeding stock transfer items (per transfer)');

        // Iterate transfers in chunks to avoid loading all ids
        $pageSize = 5000;
        DB::table('stock_transfers')->orderBy('id')
            ->select('id')
            ->chunk($pageSize, function ($rows) use (&$buffer, $popular, $productIds, $now, $itemChunk, $bar) {
                foreach ($rows as $row) {
                    $itemsForTransfer = rand(1, 10);
                    for ($k = 0; $k < $itemsForTransfer; $k++) {
                        $pid = (rand(1,100) <= 70)
                            ? $popular[array_rand($popular)]
                            : $productIds[array_rand($productIds)];

                        $buffer[] = [
                            'stock_transfer_id' => (int) $row->id,
                            'product_id' => $pid,
                            'quantity' => rand(1, 10),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        if (count($buffer) >= $itemChunk) {
                            DB::table('stock_transfer_items')->insert($buffer);
                            $buffer = [];
                        }
                    }
                    $this->progressAdvance($bar, 1); // per transfer processed
                }
            });

        if (!empty($buffer)) {
            DB::table('stock_transfer_items')->insert($buffer);
        }

        $this->progressFinish($bar, 'Stock transfer items seeded.');
    }
}
