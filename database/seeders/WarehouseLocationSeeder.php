<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WarehouseLocation;
use Database\Seeders\Concerns\WithProgressBar;

class WarehouseLocationSeeder extends Seeder
{
    use WithProgressBar;

    public function run(): void
    {
        $count = 5;
        $bar = $this->progressStart($count, 'Seeding warehouse locations');
        for ($i = 0; $i < $count; $i++) {
            WarehouseLocation::factory()->create();
            $this->progressAdvance($bar, 1);
        }
        $this->progressFinish($bar, 'Warehouse locations seeded.');
    }
}
