<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Database\Seeders\Concerns\WithProgressBar;

class OrderShipmentItemSeeder extends Seeder
{
    use WithProgressBar;

    public function run(): void
    {
        // Chunk through orders to build shipment items from allocated items of the same order
        $columnsPerRow = 3; // shipment_id, allocated_item_id, quantity
        $maxPlaceholders = 65000;
        $maxRowsByPlaceholders = (int) floor($maxPlaceholders / $columnsPerRow);
        $insertChunk = min(20000, max(1000, $maxRowsByPlaceholders - 50));

        $buffer = [];

        $totalShipments = (int) DB::table('shipment')->count();
        $bar = $this->progressStart($totalShipments, 'Seeding order shipment items (per shipment)');

        // Process orders in pages to keep memory in check
        $pageSize = 2000;
        DB::table('order')->orderBy('id')->select('id')->chunk($pageSize, function ($orders) use (&$buffer, $insertChunk, $bar) {
            $orderIds = array_map(fn($o) => (int) $o->id, $orders->all());
            if (empty($orderIds)) {
                return;
            }

            // Load shipments for these orders
            $shipments = DB::table('shipment')
                ->whereIn('order_id', $orderIds)
                ->orderBy('id')
                ->get(['id', 'order_id']);
            if ($shipments->isEmpty()) {
                return;
            }

            // Build pool of allocated_item ids per order (only those linked to OrderItem model)
            $allocated = DB::table('allocated_item as ai')
                ->join('order_item as oi', function ($join) {
                    $join->on('oi.id', '=', 'ai.model_id')
                        ->where('ai.model', '=', 'OrderItem');
                })
                ->join('order_item_groups as oig', 'oig.uuid', '=', 'oi.order_item_group_uuid')
                ->whereIn('oig.order_id', $orderIds)
                ->get(['ai.id as allocated_item_id', 'oig.order_id']);

            if ($allocated->isEmpty()) {
                return;
            }

            $poolByOrder = [];
            foreach ($allocated as $row) {
                $oid = (int) $row->order_id;
                $poolByOrder[$oid][] = (int) $row->allocated_item_id;
            }

            foreach ($shipments as $sh) {
                $oid = (int) $sh->order_id;
                $pool = $poolByOrder[$oid] ?? [];
                if (empty($pool)) {
                    continue;
                }
                $items = rand(1, 5);
                for ($i = 0; $i < $items; $i++) {
                    $buffer[] = [
                        'shipment_id' => (int) $sh->id,
                        'allocated_item_id' => $pool[array_rand($pool)],
                        'quantity' => rand(1, 10),
                    ];

                    if (count($buffer) >= $insertChunk) {
                        DB::table('shipment_item')->insert($buffer);
                        $buffer = [];
                    }
                }
                $this->progressAdvance($bar, 1); // per shipment processed
            }
        });

        if (!empty($buffer)) {
            DB::table('shipment_item')->insert($buffer);
        }

        $this->progressFinish($bar, 'Order shipment items seeded.');
    }
}
