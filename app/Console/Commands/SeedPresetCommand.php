<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedPresetCommand extends Command
{
    protected $signature = 'app:seed {--size=small : Dataset size: small|medium|full} {--force : Do not ask for confirmation}';

    protected $description = 'Reset DB and seed with a dataset preset (small, medium, full)';

    public function handle(): int
    {
        $size = strtolower((string) $this->option('size'));
        $force = (bool) $this->option('force');

        $presets = [
            'small' => [
                'SEED_WAREHOUSE_LOCATIONS' => 5,
                'SEED_PRODUCTS' => 500,
                'SEED_STOCK_TRANSFERS' => 500,
                'SEED_ORDERS' => 1000,
                'SEED_SHOW_PROGRESS' => 'true',
            ],
            'medium' => [
                'SEED_WAREHOUSE_LOCATIONS' => 5,
                'SEED_PRODUCTS' => 25000,
                'SEED_STOCK_TRANSFERS' => 25000,
                'SEED_ORDERS' => 50000,
                'SEED_SHOW_PROGRESS' => 'true',
            ],
            'full' => [
                'SEED_WAREHOUSE_LOCATIONS' => 5,
                'SEED_PRODUCTS' => 50000,
                'SEED_STOCK_TRANSFERS' => 50000,
                'SEED_ORDERS' => 100000,
                'SEED_SHOW_PROGRESS' => 'true',
            ],
        ];

        if (! array_key_exists($size, $presets)) {
            $this->error("Invalid size '{$size}'. Use: small | medium | full");
            return self::INVALID;
        }

        $preset = $presets[$size];

        if ($size === 'full' && app()->environment() !== 'local' && ! $force) {
            $this->error('Refusing to run full seed outside local env without --force.');
            return self::INVALID;
        }

        $this->line('Applying seed preset: ' . strtoupper($size));
        foreach ($preset as $k => $v) {
            putenv($k.'='.$v);
            $this->line("  - $k=$v");
        }

        // Row count estimate (rough order of magnitude, child tables generate additional rows)
        $p = (int) $preset['SEED_PRODUCTS'];
        $w = (int) $preset['SEED_WAREHOUSE_LOCATIONS'];
        $o = (int) $preset['SEED_ORDERS'];
        $st = (int) $preset['SEED_STOCK_TRANSFERS'];
        $ptw = $p * $w; // product_to_warehouse_location rows

        $this->line('Estimated base row counts:');
        foreach ([
            'products' => $p,
            'warehouses' => $w,
            'product_to_warehouse_location' => $ptw,
            'orders' => $o,
            'stock_transfers' => $st,
        ] as $k => $v) {
            $this->line("  - $k: $v");
        }

        if (! $force && ! $this->confirm('This will DROP and re-create tables (migrate:fresh). Proceed?')) {
            return self::INVALID;
        }

        $exit = Artisan::call('migrate:fresh', ['--seed' => true]);
        $this->output->write(Artisan::output());

        return $exit;
    }
}
