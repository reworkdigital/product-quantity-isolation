<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrderStatus;

class OrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['slug' => 'open', 'name' => 'Open'],
            ['slug' => 'cancelled', 'name' => 'Cancelled'],
        ];

        foreach ($statuses as $data) {
            OrderStatus::query()->updateOrCreate(
                ['slug' => $data['slug']],
                ['name' => $data['name']]
            );
        }
    }
}
