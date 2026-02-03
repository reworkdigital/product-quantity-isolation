<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;

class ProductPurchasableTest extends TestCase
{
    public function test_uses_quantity()
    {
        $product = $this->setupProduct(
            on_hand_quantity: 1,
        );

        $this->assertEquals(1, $product->purchasable);
    }

    public function test_uses_orderable_quantity()
    {
        $product = $this->setupProduct(
            orderable_quantity: 1,
        );

        $this->assertEquals(1, $product->purchasable);
    }

    public function test_uses_pre_order_quantity()
    {
        $product = $this->setupProduct(
            orderable_quantity: 1,
        );

        $this->assertEquals(1, $product->purchasable);
    }

    public function setupProduct(
        int $on_hand_quantity = 0,
        int $pre_order_quantity = 0,
        int $orderable_quantity = 0,
        int $threshold = 0,
    ): Product {
        return Product::factory()->withWarehouseStock(
            on_hand_quantity: $on_hand_quantity,
            pre_order_quantity: $pre_order_quantity,
            orderable_quantity: $orderable_quantity,
            threshold: $threshold,
        )->create();
    }
}
