<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use Database\Seeders\Concerns\WithProgressBar;

class ProductSeeder extends Seeder
{
    use WithProgressBar;

    public function run(): void
    {
        $count = 50000;
        // Create in chunks to avoid memory spikes
        $chunk = 5000;

        $bar = $this->progressStart($count, 'Seeding products');
        for ($i = 0; $i < $count; $i += $chunk) {
            $batch = min($chunk, $count - $i);
            Product::factory()->count($batch)->create();
            $this->progressAdvance($bar, $batch);
        }
        $this->progressFinish($bar, 'Products seeded.');
    }
}
