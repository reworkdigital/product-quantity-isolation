<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\WarehouseLocation;
use Database\Seeders\Concerns\WithProgressBar;

class StockTransferSeeder extends Seeder
{
    use WithProgressBar;

    public function run(): void
    {
        $count = (int) env('SEED_STOCK_TRANSFERS', 50000);
        $warehouses = WarehouseLocation::query()->pluck('uuid')->all();
        if (count($warehouses) < 2 || $count <= 0) {
            return;
        }

        $now = now();
        $transferChunk = 2000;

        $bar = $this->progressStart($count, 'Seeding stock transfers');
        for ($i = 0; $i < $count; $i += $transferChunk) {
            $batch = min($transferChunk, $count - $i);

            $transfers = [];
            for ($t = 0; $t < $batch; $t++) {
                // distinct from/to
                $from = $warehouses[array_rand($warehouses)];
                do {
                    $to = $warehouses[array_rand($warehouses)];
                } while ($to === $from);

                $transfers[] = [
                    'warehouse_from_uuid' => $from,
                    'warehouse_to_uuid' => $to,
                    'received_at' => rand(0,1) ? $now : null,
                    'deleted_at' => rand(0,1) ? $now : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('stock_transfers')->insert($transfers);
            $this->progressAdvance($bar, $batch);
        }
        $this->progressFinish($bar, 'Stock transfers seeded.');
    }
}
