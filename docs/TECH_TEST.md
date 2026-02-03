# Tech Test — Product Quantity Optimization

## Goal
Improve the performance of computing a product’s `purchasable` while preserving correctness.

## Context
This codebase simulates heavy relational load around product availability using large seeders. The `purchasable` accessor ultimately depends on allocations, shipments (shipped quantities), and outgoing stock transfers.

As a high level explanation, the `purchasable` accessor computes the quantity of a product that is available for purchase, taking into account:
- Available on hand (e.g. in stock) for each warehouse location
- Allocated items (e.g. backordered items)
- Shipped orders (e.g. partially shipped items)
- Outgoing stock transfers (e.g. items that are being transferred out of the warehouse to another warehouse)

Key places to look:
- `App\Models\ProductToWarehouseLocation` — accessors `purchasable`, `unshippedOrders`, `outgoingTransferQuantity`
- `App\Models\WarehouseLocation` — query builders that compute unshipped orders
- Seeded tables: `allocated_item`, `order`, `order_item`, `order_item_groups`, `shipment`, `shipment_item`, `stock_transfers`, `stock_transfer_items`, `product_to_warehouse_location`

## Constraints
- Keep existing behavior and tests passing.
- You may add/modify indexes and migrations, and refactor queries where appropriate.
- Avoid changing public endpoints or semantics unless justified; if you do, document why.
- Keep code readable and maintainable.
- Keep in mind the product quantity calculations may be recomputed frequently in real‑world scenarios, for example when a customer places an order, when a stock transfer is created, etc.

## Setup
1. Create a private fork of the codebase.
2. Install and configure the project as per README (use the "Tech Test" small dataset preset).
3. Seed a small dataset first to iterate quickly:
   ```bash
   php artisan app:seed --size=small
   ```
4. Run tests (unit + performance):
   ```bash
   php artisan test
   ```
5. Optional: collect a baseline benchmark
   ```bash
   php artisan app:bench:purchasable --samples=100 --random=1
   # Deterministic over specific IDs
   php artisan app:bench:purchasable --samples=50 --product-ids=1,2,3,4,5 --random=0 --json=1
   ```

## Task
1. Profile the calculation path for `purchasable`.
2. Identify the primary bottleneck(s) and propose at least one improvement.
3. Implement the improvement(s).
4. Demonstrate measurable performance gains.

## What to submit
- Ensure you regularly commit your work, so we can review your progress at the end and see any decision making.
- Create a pull request and give us access to your private fork.
- A short write‑up (markdown is fine) that includes:
  - Problem analysis (what’s slow and why),
  - Your approach and trade‑offs,
  - Before/after timings (endpoint and/or performance test),
  - Any `EXPLAIN` output/screenshots or query evidence supporting the improvement.

## How we evaluate
- Correctness (existing behavior maintained; tests pass).
- Performance delta (clear, measurable improvement with evidence).
- Code quality (clarity, maintainability, appropriate use of DB features).
- Reasoning (quality of analysis and trade‑off discussion).

## Tips
- Use the demo endpoint `GET /products/{product}/purchasable` and capture `time_taken_milliseconds`.
- For any caching/precomputation, describe invalidation strategy (even if not fully implemented).
- If you adjust the schema, include a migration and rationale.
