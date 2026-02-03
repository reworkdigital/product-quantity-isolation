<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Database\Seeders\Concerns\WithProgressBar;

class OrderShipmentSeeder extends Seeder
{
    use WithProgressBar;

    public function run(): void
    {
        // For each order, create 1-5 shipments with random deleted_at
        $now = now();
        $buffer = [];
        // 2-3 columns per row, safe high chunk
        $chunkInsert = 20000;

        $totalOrders = (int) DB::table('order')->count();
        $bar = $this->progressStart($totalOrders, 'Seeding order shipments (per order)');

        DB::table('order')->orderBy('id')->select('id')->chunk(5000, function ($orders) use (&$buffer, $chunkInsert, $now, $bar) {
            foreach ($orders as $order) {
                $count = rand(1, 5);
                for ($i = 0; $i < $count; $i++) {
                    $buffer[] = [
                        'order_id' => (int) $order->id,
                        'deleted_at' => rand(0,1) ? null : $now,
                    ];

                    if (count($buffer) >= $chunkInsert) {
                        DB::table('shipment')->insert($buffer);
                        $buffer = [];
                    }
                }
                $this->progressAdvance($bar, 1); // per order processed
            }
        });

        if (!empty($buffer)) {
            DB::table('shipment')->insert($buffer);
        }

        $this->progressFinish($bar, 'Order shipments seeded.');
    }
}
