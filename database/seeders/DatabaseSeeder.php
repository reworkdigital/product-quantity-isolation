<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Optional test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Orchestrate all seeders; counts can be overridden via env vars
        $this->call([
            WarehouseLocationSeeder::class,
            ProductSeeder::class,
            ProductToWarehouseLocationSeeder::class,
            OrderStatusSeeder::class,
            // Stock transfers and items
            StockTransferSeeder::class,
            StockTransferItemSeeder::class,
            // Orders graph split into per-model seeders
            OrderSeeder::class,
            OrderItemGroupSeeder::class,
            OrderItemSeeder::class,
            AllocatedItemSeeder::class,
            OrderShipmentSeeder::class,
            OrderShipmentItemSeeder::class,
        ]);
    }
}
