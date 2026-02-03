## Product Quantity Isolation Repository

This repository contains a small Laravel 10 application designed for two purposes:

1) Replicate and study performance issues in product quantity calculations (e.g., computing a product’s `purchasable` under heavy relational load).
2) Act as the base for a senior developer technical exercise focused on profiling, optimizing, and validating improvements.

The project includes high‑volume seeders (with progress bars), a demo HTTP endpoint to time calculations, and unit/performance tests.

### Requirements

- PHP 8.1+
- Composer
- MySQL (recommended)
- Laravel 10.x

### Usage modes

- Performance Replication:
    - Seed large datasets, hit the demo endpoint, profile SQL, and reproduce slow timings.
- Tech Test:
    - Run a smaller dataset by default, execute the performance test, implement improvements, and report before/after results.

### Performance Replication

1. Choose dataset size via ENV (start small, then scale):
   ```env
   # Small (local demo)
   SEED_WAREHOUSE_LOCATIONS=5
   SEED_PRODUCTS=5000
   SEED_STOCK_TRANSFERS=5000
   SEED_ORDERS=10000
   SEED_SHOW_PROGRESS=true
   ```
   ```env
   # Medium (~tens of minutes on a laptop)
   SEED_WAREHOUSE_LOCATIONS=5
   SEED_PRODUCTS=25000
   SEED_STOCK_TRANSFERS=25000
   SEED_ORDERS=50000
   ```
   ```env
   # Full (stress test; expect long runtimes and high memory)
   SEED_WAREHOUSE_LOCATIONS=5
   SEED_PRODUCTS=50000
   SEED_STOCK_TRANSFERS=50000
   SEED_ORDERS=100000
   ```
2. Fresh setup and seed:
   ```bash
   php artisan migrate:fresh --seed
   ```
3. Measure a single product:
   ```bash
   curl http://127.0.0.1:8000/products/{productId}/purchasable
   ```
   - Run multiple times and note `time_taken_milliseconds`.
4. Profiling tips:
   - Use `EXPLAIN` on the hot queries.
   - Compare timings with/without eager loading to spot N+1.
   - Consider MySQL tools (e.g., slow query log, `pt-query-digest`) or Laravel query logging.
5. Common bottlenecks to explore: joins over large `allocated_item`, `shipment_item`, and `stock_transfer_items` sets; missing or suboptimal indexes; recomputation per row.

### Quick start

If you just want to see it run end‑to‑end:

```bash
# 1) Clone and enter the repo
git clone <your-repo-url>
cd product-quantity-isolation

# 2) Install deps
composer install

# 3) Configure env
cp .env.example .env
# Edit .env with your DB credentials

# 4) Generate app key
php artisan key:generate

# 5) Migrate and seed (warning: default seeders generate large datasets)
php artisan migrate --seed

# 6) Start the server
php artisan serve

# 7) Hit the demo endpoint (replace {id})
curl http://127.0.0.1:8000/products/{id}/purchasable
```

### Installation (details)

1. Clone the repository:
   ```bash
   git clone <your-repo-url>
   cd product-quantity-isolation
   ```
2. Install dependencies:
   ```bash
   composer install
   ```
3. Copy the environment file and configure DB:
   ```bash
   cp .env.example .env
   ```
   Minimal DB settings (example):
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_user
   DB_PASSWORD=your_database_password
   ```
4. Generate an application key:
   ```bash
   php artisan key:generate
   ```
5. Run migrations and seeders:
   ```bash
   php artisan migrate --seed
   ```

### Seeding data (high‑volume dataset)

This project ships with high‑volume seeders to stress the product quantity calculations. By default, running `--seed` will generate a large dataset. You can control volumes with ENV variables before seeding:

- `SEED_WAREHOUSE_LOCATIONS` (default 5)
- `SEED_PRODUCTS` (default 50000)
- `SEED_STOCK_TRANSFERS` (default 50000)
- `SEED_ORDERS` (default 100000)
- `SEED_SHOW_PROGRESS` (default true when running via Artisan; set to `false` to disable)

Example for a small local run (recommended first):
```env
SEED_WAREHOUSE_LOCATIONS=5
SEED_PRODUCTS=500
SEED_STOCK_TRANSFERS=500
SEED_ORDERS=1000
SEED_SHOW_PROGRESS=true
```
Then run:
```bash
php artisan migrate:fresh --seed
```

Preset seed command (one-shot convenience):
```bash
# Small (recommended for local iteration)
php artisan app:seed --size=small

# Medium (heavier; minutes on a laptop)
php artisan app:seed --size=medium

# Full stress dataset (very large; guarded outside local unless --force)
php artisan app:seed --size=full
# Use --force to skip confirmation and allow outside local env (dangerous)
php artisan app:seed --size=full --force
```

Benchmark command (measure Product->purchasable):
```bash
# 100 samples, random products
php artisan app:bench:purchasable --samples=100 --random=1

# Deterministic sweep over specific ids and JSON output
php artisan app:bench:purchasable --samples=50 --product-ids=1,2,3,4,5 --random=0 --json=1
```
Options:
- `--samples` (default 50)
- `--product-ids` (comma list). If omitted, scans existing product ids.
- `--random` 1/0 (default 1). When 0, iterates deterministically over ids.
- `--warmup` (default 5) warm-up iterations ignored in stats.
- `--json` 1/0 (default 0) to print machine-readable output.

Seeder execution order (as configured in `Database\Seeders\DatabaseSeeder`):
1. `WarehouseLocationSeeder`
2. `ProductSeeder`
3. `ProductToWarehouseLocationSeeder`
4. `OrderStatusSeeder`
5. `StockTransferSeeder`
6. `StockTransferItemSeeder`
7. `OrderSeeder`
8. `OrderItemGroupSeeder`
9. `OrderItemSeeder`
10. `AllocatedItemSeeder`
11. `OrderShipmentSeeder`
12. `OrderShipmentItemSeeder`

Notes:
- All large inserts are performed in placeholder‑safe chunks to avoid MySQL error 1390 (“too many placeholders”).
- Progress bars are displayed for all long‑running seeders (coarse steps per batch/parent). Disable with `SEED_SHOW_PROGRESS=false`.
- Some seeders randomly set `deleted_at`/`received_at` and cross‑reference earlier rows to simulate real data distributions.

Run an individual seeder (optional):
```bash
php artisan db:seed --class=Database\\Seeders\\OrderItemSeeder
```

### Demo endpoint

A minimal route is provided to exercise the calculation and show timing:

- GET `/products/{product}/purchasable`
  - Resolves `{product}` to a `Product` model ID.
  - Returns JSON with `product_id`, computed `purchasable`, and `time_taken_milliseconds`.

Example:
```bash
curl http://127.0.0.1:8000/products/123/purchasable
```

### Tech Test (Senior Developer)

This repo is also used as a performance optimization exercise.

For candidates:
1. Setup and seed a small dataset:
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan migrate:fresh --seed
   ```
2. Run tests (including the performance test):
   ```bash
   php artisan test
   ```
3. Task overview:
   - Improve the performance of computing a product’s `purchasable` quantity.
   - Provide before/after timing for the demo endpoint and/or the perf test.
   - Include any schema/index/query changes and the rationale.
4. Deliverables:
   - A short write‑up (approach, trade‑offs, results),
   - Code changes (PR or patch),
   - Evidence of improvements (timings, EXPLAIN, test diffs if applicable).

See `docs/TECH_TEST.md` for full candidate instructions.

### Tests

Run all tests:
```bash
php artisan test
```

- Unit tests: `tests/Unit/ProductPurchasableTest.php` — validate the `purchasable` accessor against basic scenarios.
- Performance test: `tests/Performance/ProductPurchasablePerformanceTest.php` — creates a product with many related rows to measure timing.
  - Tip: This test assumes control over the DB contents. Run against a fresh/empty DB or a dedicated test database. You can configure test DB settings in `phpunit.xml`:
    ```xml
    <env name="DB_CONNECTION" value="mysql"/>
    <env name="DB_DATABASE" value="your_test_database_name"/>
    ```

### Troubleshooting

- MySQL 1390 (too many placeholders): Seeders have built‑in chunking to prevent this. If you still hit limits, lower the ENV counts or reduce chunk sizes (seeders have inline comments indicating where to adjust).
- Slug length for `warehouse_locations.slug`: Factory ensures ≤ 50 chars; if you import data manually, keep this limit in mind.
- Large dataset memory/time: Start with the smaller ENV values above. Consider `php -d memory_limit=2G artisan ...` if needed.
- Re‑seeding from scratch: use `php artisan migrate:fresh --seed` (warning: this drops all tables first).

### Non‑goals
- Building a full commerce platform.
- Implementing business workflows beyond what’s needed to reproduce quantity calculations.

### Data safety
- These seeders can generate very large datasets. Point your `.env` at a disposable DB and avoid running against production or shared environments.

### Project purpose and structure

The goal is to provide a reproducible environment to profile and optimize product quantity calculations under heavy relational load. Key places to look:

- Models implementing calculations and relations:
  - `App\Models\ProductToWarehouseLocation` (accessors `purchasable`, `unshippedOrders`, `outgoingTransferQuantity`)
  - `App\Models\WarehouseLocation` (query builders that compute unshipped orders)
- High‑volume seeders in `database/seeders` (split per model with progress bars)
- Demo route in `routes/web.php`
