<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use App\Models\Product;
use App\Models\WarehouseLocation;
use Database\Seeders\Concerns\WithProgressBar;

class ProductToWarehouseLocationSeeder extends Seeder
{
    use WithProgressBar;

    public function run(): void
    {
        $products = Product::query()->pluck('id')->all();
        $warehouses = WarehouseLocation::query()->pluck('uuid')->all();

        if (empty($products) || empty($warehouses)) {
            return;
        }

        $now = now();

        // Avoid MySQL 1390: too many placeholders (limit ~65,535)
        // We insert 8 columns per row, so safe rows per insert <= floor(65000 / 8) ≈ 8125–8191.
        // Use a conservative dynamic chunk size with a safety margin, capped at 5000.
        $columnsPerRow = 8;
        $maxPlaceholders = 65000; // conservative below 65535
        $maxRowsByPlaceholders = (int) floor($maxPlaceholders / $columnsPerRow);
        $chunk = min(5000, max(1000, $maxRowsByPlaceholders - 10)); // 1000 min for reasonable batch size

        $total = count($products) * count($warehouses);
        $bar = $this->progressStart($total, 'Seeding product_to_warehouse_location matrix');

        $buffer = [];

        foreach ($products as $pid) {
            foreach ($warehouses as $wuuid) {
                $buffer[] = [
                    'product_id' => $pid,
                    'warehouse_location_uuid' => $wuuid,
                    'on_hand_quantity' => rand(1, 100),
                    'pre_order_quantity' => rand(1, 100),
                    'orderable_quantity' => rand(1, 100),
                    'threshold' => rand(1, 100),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($buffer) >= $chunk) {
                    DB::table('product_to_warehouse_location')->insert($buffer);
                    $this->progressAdvance($bar, count($buffer));
                    $buffer = [];
                }
            }
        }

        if (!empty($buffer)) {
            DB::table('product_to_warehouse_location')->insert($buffer);
            $this->progressAdvance($bar, count($buffer));
        }

        $this->progressFinish($bar, 'Product to warehouse location matrix seeded.');
    }
}
