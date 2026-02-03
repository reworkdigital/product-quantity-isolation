<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\OrderStatus;
use Database\Seeders\Concerns\WithProgressBar;

class OrderSeeder extends Seeder
{
    use WithProgressBar;

    public function run(): void
    {
        $orderCount = (int) env('SEED_ORDERS', 100000);
        if ($orderCount <= 0) {
            return;
        }

        $now = now();
        $openId = optional(OrderStatus::query()->where('slug', 'open')->first())->id;

        // Determine starting order id to avoid conflicts if rerun
        $maxExistingId = (int) (DB::table('order')->max('id') ?? 0);
        $nextOrderId = $maxExistingId + 1;

        $chunk = 1000;
        $bar = $this->progressStart($orderCount, 'Seeding orders');
        for ($i = 0; $i < $orderCount; $i += $chunk) {
            $batch = min($chunk, $orderCount - $i);
            $rows = [];
            for ($j = 0; $j < $batch; $j++) {
                $oid = $nextOrderId++;
                $rows[] = [
                    'id' => $oid,
                    'restore_key' => (string) Str::uuid(),
                    'order_status_id' => (rand(0,1) && $openId) ? $openId : null,
                    'cloned_to_id' => null, // set after some orders exist
                    'deleted_at' => rand(0,1) ? null : $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('order')->insert($rows);
            $this->progressAdvance($bar, $batch);

            // Randomly set cloned_to_id referencing earlier orders
            $allIds = DB::table('order')->orderBy('id')->pluck('id')->all();
            foreach ($rows as $r) {
                if (rand(0,1) && !empty($allIds)) {
                    // choose a target less than current id if possible
                    $candidates = array_filter($allIds, fn($id) => $id < $r['id']);
                    if (!empty($candidates)) {
                        $target = $candidates[array_rand($candidates)];
                        DB::table('order')->where('id', $r['id'])->update(['cloned_to_id' => $target, 'updated_at' => $now]);
                    }
                }
            }
        }
        $this->progressFinish($bar, 'Orders seeded.');
    }
}
