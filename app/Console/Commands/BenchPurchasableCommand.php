<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class BenchPurchasableCommand extends Command
{
    protected $signature = 'app:bench:purchasable {--samples=50} {--product-ids=} {--random=1} {--warmup=5} {--json=0}';

    protected $description = 'Benchmark Product->purchasable computation and print timing stats';

    public function handle(): int
    {
        $samples = max(1, (int) $this->option('samples'));
        $warmup = max(0, (int) $this->option('warmup'));
        $asJson = (bool) (int) $this->option('json');
        $productIdsOpt = (string) $this->option('product-ids');
        $useRandom = (bool) (int) $this->option('random');

        // Resolve candidate product IDs
        if ($productIdsOpt) {
            $ids = collect(explode(',', $productIdsOpt))
                ->map(fn($v) => (int) trim($v))
                ->filter(fn($v) => $v > 0)
                ->values()
                ->all();
        } else {
            $ids = DB::table('product')->orderBy('id')->pluck('id')->all();
        }

        if (empty($ids)) {
            $this->error('No products found to benchmark. Seed data first.');
            return self::INVALID;
        }

        // Build sample list (with replacement if needed)
        $pick = function () use ($ids, $useRandom) {
            if ($useRandom) {
                return $ids[array_rand($ids)];
            }
            static $i = 0; $val = $ids[$i % count($ids)]; $i++; return $val;
        };

        // Warmup
        if ($warmup > 0) {
            $this->line("Warming up ({$warmup})...");
            for ($i = 0; $i < $warmup; $i++) {
                $pid = $pick();
                $p = Product::find($pid);
                if ($p) { $tmp = $p->purchasable; unset($tmp); }
            }
        }

        $rows = [];
        $times = [];
        $slowest = ['ms' => -1, 'product_id' => null, 'purchasable' => null];

        $this->line("Running {$samples} samples...");
        for ($i = 0; $i < $samples; $i++) {
            $pid = $pick();
            $p = Product::find($pid);
            if (!$p) { continue; }
            $start = microtime(true);
            $val = $p->purchasable;
            $ms = (microtime(true) - $start) * 1000.0;
            $msRounded = round($ms, 3);
            $rows[] = ['#' => $i + 1, 'product_id' => $pid, 'ms' => $msRounded, 'purchasable' => $val];
            $times[] = $ms;
            if ($ms > $slowest['ms']) { $slowest = ['ms' => $msRounded, 'product_id' => $pid, 'purchasable' => $val]; }
        }

        sort($times);
        $count = count($times);
        if ($count === 0) {
            $this->error('No timings collected.');
            return self::INVALID;
        }

        $avg = array_sum($times) / $count;
        $min = $times[0];
        $max = $times[$count - 1];
        $pct = function(array $a, float $p) {
            $n = count($a);
            if ($n === 0) return null;
            $rank = ($p/100) * ($n - 1);
            $lo = (int) floor($rank);
            $hi = (int) ceil($rank);
            if ($lo === $hi) return $a[$lo];
            $w = $rank - $lo;
            return $a[$lo] * (1-$w) + $a[$hi]*$w;
        };

        $summary = [
            'samples' => $count,
            'min_ms' => round($min, 3),
            'avg_ms' => round($avg, 3),
            'p50_ms' => round($pct($times, 50), 3),
            'p90_ms' => round($pct($times, 90), 3),
            'p95_ms' => round($pct($times, 95), 3),
            'p99_ms' => round($pct($times, 99), 3),
            'max_ms' => round($max, 3),
            'slowest' => $slowest,
        ];

        if ($asJson) {
            $this->line(json_encode(['summary' => $summary, 'samples' => $rows], JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info('Summary (ms)');
        foreach ($summary as $k => $v) {
            if ($k === 'slowest') continue;
            $this->line(str_pad($k, 10) . ': ' . $v);
        }
        $this->line('Slowest sample: product_id=' . $summary['slowest']['product_id'] . ' ms=' . $summary['slowest']['ms']);

        $this->table(['#','product_id','ms','purchasable'], array_slice($rows, 0, 10));

        return self::SUCCESS;
    }
}
