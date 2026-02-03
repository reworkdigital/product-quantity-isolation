<?php

namespace Performance;

use App\Models\AllocatedItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemGroup;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductToWarehouseLocation;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\WarehouseLocation;
use Tests\TestCase;


class ProductPurchasablePerformanceTest extends TestCase
{
    public function test_product_purchasable_speed_less_than_50_ms()
    {
        $product = Product::factory()->create();

        // generate a large number of warehouse locations & product to warehouse locations for the product of interest
        for ($i = 0; $i < 1000; $i++) {
            $warehouseLocation = WarehouseLocation::factory()->create();
            ProductToWarehouseLocation::factory()
                ->create([
                    'product_id' => $product->id,
                    'warehouse_location_uuid' => $warehouseLocation->uuid,
                    'on_hand_quantity' => rand(0, 100),
                    'pre_order_quantity' => rand(0, 100),
                    'orderable_quantity' => rand(0, 100),
                    'threshold' => rand(0, 10),
                ]);
        }

        // generate a large number of unrelated product to warehouse locations (use the same warehouse locations as above to increase complexity)
        for ($i = 0; $i < 10000; $i++) {
            ProductToWarehouseLocation::factory()
                ->create([
                    'product_id' => Product::factory()->create()->id,
                    'warehouse_location_uuid' => WarehouseLocation::inRandomOrder()->first()->uuid,
                    'on_hand_quantity' => rand(0, 100),
                    'pre_order_quantity' => rand(0, 100),
                    'orderable_quantity' => rand(0, 100),
                    'threshold' => rand(0, 10),
                ]);
        }

        // generate a large number of stock transfers
        // for random warehouse locations (all combinations of from/to - should all have product to warehouse location entries for the product of interest)
        // with some transfer items for the product of interest and some for other products
        for ($i = 0; $i < 5000; $i++) {
            $warehouse_to = WarehouseLocation::inRandomOrder()->first();
            $warehouse_from = WarehouseLocation::inRandomOrder()->where('uuid', '!=', $warehouse_to->uuid)->first();

            $st = StockTransfer::factory()->create([
                'warehouse_from_uuid' => $warehouse_to->uuid,
                'warehouse_to_uuid' => $warehouse_from->uuid,
                'received_at' => rand(0, 1) ? now() : null, // some received, some not
                'deleted_at' => rand(0, 1) ? now() : null, // some deleted, some not
            ]);

            // add some transfer items for the product of interest
            StockTransferItem::factory()->count(rand(1, 10))->create([
                'stock_transfer_id' => $st->id,
                'product_id' => $product->id,
                'quantity' => rand(1, 20),
            ]);

            // add some transfer items for other products
            StockTransferItem::factory()->count(rand(1, 10))->create([
                'stock_transfer_id' => $st->id,
                'product_id' => Product::inRandomOrder()->where('id', '!=', $product->id)->first()->id,
                'quantity' => rand(1, 20),
            ]);
        }

        $open_order_status = OrderStatus::factory()->create(['slug' => 'open']);

        // generate a large number of orders, some with shipments with shipment items, for the product of interest and other products
        $order_collection = collect(Order::factory()->count(5000)->create([
            'cloned_to_id' => rand(0, 1) ? null : Order::factory()->create()->id, // some cloned, some not
            'order_status_id' => rand(0, 1) ? $open_order_status->id : null, // some open, some null
            'deleted_at' => rand(0, 1) ? null : now(), // some deleted, some not
        ]));

        // for each order, add order item groups, order items, allocated items, shipments, and shipment items
        $order_collection->each(function ($order) use ($product) {
            // add some order item groups with some order items for the product of interest and other products;
            // then add some allocated items for some of the order items;
            // finally add some shipments with shipment items for some of the allocated items
            for ($j = 0; $j < rand(1, 5); $j++) {
                $oig = OrderItemGroup::factory()->create(['order_id' => $order->id]);

                // add some order items for the product of interest
                for ($k = 0; $k < rand(1, 3); $k++) {
                    $oi = OrderItem::factory()->create([
                        'order_item_group_uuid' => $oig->uuid,
                        'product_id' => $product->id,
                    ]);

                    // add (or not) allocated items for this order item
                    if (rand(0, 1)) {
                        for ($l = 0; $l < rand(1, 3); $l++) {
                            $allocated_item = AllocatedItem::factory()->create([
                                'model' => 'OrderItem',
                                'model_id' => $oi->id,
                                'warehouse_location_uuid' => WarehouseLocation::inRandomOrder()->first()->uuid,
                                'required_quantity' => rand(1, 10),
                                'allocated_quantity' => rand(0, 10),
                            ]);

                            // add (or not) shipments with shipment items for this allocated item
                            if (rand(0, 1)) {
                                $shipment = OrderShipment::factory()->create([
                                    'order_id' => $order->id,
                                    'deleted_at' => rand(0, 1) ? null : now(), // some deleted, some not
                                ]);

                                OrderShipmentItem::factory()->create([
                                    'shipment_id' => $shipment->id,
                                    'allocated_item_id' => $allocated_item->id,
                                    'quantity' => rand(1, 10),
                                ]);
                            }
                        }
                    }
                }

                // add some order items for other products
                for ($k = 0; $k < rand(1, 3); $k++) {
                    $oi = OrderItem::factory()->create([
                        'order_item_group_uuid' => $oig->uuid,
                        'product_id' => Product::inRandomOrder()->where('id', '!=', $product->id)->first()->id,
                    ]);

                    // add (or not) allocated items for this order item
                    if (rand(0, 1)) {
                        for ($l = 0; $l < rand(1, 3); $l++) {
                            $allocated_item = AllocatedItem::factory()->create([
                                'model' => 'OrderItem',
                                'model_id' => $oi->id,
                                'warehouse_location_uuid' => WarehouseLocation::inRandomOrder()->first()->uuid,
                                'required_quantity' => rand(1, 10),
                                'allocated_quantity' => rand(0, 10),
                            ]);

                            // add (or not) shipments with shipment items for this allocated item
                            if (rand(0, 1)) {
                                $shipment = OrderShipment::factory()->create([
                                    'order_id' => $order->id,
                                    'deleted_at' => rand(0, 1) ? null : now(), // some deleted, some not
                                ]);
                                OrderShipmentItem::factory()->create([
                                    'shipment_id' => $shipment->id,
                                    'allocated_item_id' => $allocated_item->id,
                                    'quantity' => rand(1, 10),
                                ]);
                            }
                        }
                    }
                }
            }
        });

        // now time the purchasable check
        $start_time = microtime(true);
        $purchasable = $product->purchasable;
        $end_time = microtime(true);

        $duration_ms = ($end_time - $start_time) * 1000;

        $this->assertLessThan(50, $duration_ms, "Product purchasable check took too long: {$duration_ms} ms");
    }
}
