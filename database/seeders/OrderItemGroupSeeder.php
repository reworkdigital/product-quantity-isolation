<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Database\Seeders\Concerns\WithProgressBar;

class OrderItemGroupSeeder extends Seeder
{
    use WithProgressBar;

    public function run(): void
    {
        // For each order, create 1-5 groups
        $now = now();
        $buffer = [];
        $chunkInsert = 10000; // two columns only; safe for placeholders

        $totalOrders = (int) DB::table('order')->count();
        $bar = $this->progressStart($totalOrders, 'Seeding order item groups (per order)');

        DB::table('order')->orderBy('id')->select('id')->chunk(5000, function ($orders) use (&$buffer, $chunkInsert, $now, $bar) {
            foreach ($orders as $order) {
                $count = rand(1, 5);
                for ($i = 0; $i < $count; $i++) {
                    $buffer[] = [
                        'uuid' => (string) Str::uuid(),
                        'order_id' => (int) $order->id,
                        // timestamps optional (table has timestamps but nullable in migration style); keep lean
                        // 'created_at' => $now,
                        // 'updated_at' => $now,
                    ];

                    if (count($buffer) >= $chunkInsert) {
                        DB::table('order_item_groups')->insert($buffer);
                        $buffer = [];
                    }
                }
                $this->progressAdvance($bar, 1); // per order processed
            }
        });

        if (!empty($buffer)) {
            DB::table('order_item_groups')->insert($buffer);
        }

        $this->progressFinish($bar, 'Order item groups seeded.');
    }
}
