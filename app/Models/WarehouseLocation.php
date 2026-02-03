<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'warehouse_locations';

    public $incrementing = false;

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    protected $appends = [
        'unshipped_orders',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($warehouseLocation) {
            do {
                $uuid = Str::uuid();
            } while ($warehouseLocation::query()->where('uuid', $uuid)->exists());

            $warehouseLocation->uuid = $uuid;
        });
    }

    // Methods

    public function unshippedOrdersFor(Product $product): int
    {
        $productId = (int) $product->getAttribute('id');
        $byProduct = $this->unshippedOrdersByProduct();

        return $byProduct[$productId] ?? 0;
    }

    public function unshippedOrdersByProduct(): array
    {
        if ($this->__unshippedOrdersCache !== null) {
            return $this->__unshippedOrdersCache;
        }

        $uuid = $this->resolveWarehouseLocationUuid();

        $allocFromOrderItem = $this->buildAllocationQuery($uuid);

        $allocUnion = $allocFromOrderItem;

        $shipped = $this->buildShippedQuery();

        // with_balance: balance per allocated row after deducting shipped
        $withBalance = DB::query()
            ->fromSub($allocUnion, 'a')
            ->leftJoinSub($shipped, 'sh', 'sh.allocated_item_id', '=', 'a.allocated_item_id')
            ->select([
                'a.product_id',
                DB::raw('GREATEST(a.qty - COALESCE(sh.shipped_qty, 0), 0) as balance'),
            ]);

        $rows = DB::query()
            ->fromSub($withBalance, 'with_balance')
            ->groupBy('product_id')
            ->select([
                'product_id',
                DB::raw('SUM(balance) as unshipped'),
            ])
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $pid = (int) $row->product_id;
            $unshipped = (int) $row->unshipped;
            $result[$pid] = $unshipped;
        }

        return $this->__unshippedOrdersCache = $result;
    }

    private function resolveWarehouseLocationUuid(): string
    {
        return (string) ($this->getAttribute('uuid') ?? $this->uuid);
    }

    /**
     * Build an allocation query for a specific model type (OrderItem or OrderItemLink).
     *
     * @param  string  $warehouseUuid  The warehouse location UUID
     * @param  string  $quantityField  The quantity field to select (e.g., 'required_quantity', 'allocated_quantity')
     * @param  Product|null  $product  Optional product to filter by
     */
    protected function buildAllocationQuery(
        string $warehouseUuid,
        string $quantityField = 'required_quantity',
        ?Product $product = null
    ): Builder {
        $allocated_item_table = (new AllocatedItem)->getTable();

        $query = AllocatedItem::query()
            ->join("order_item as oi", function ($join) use ($allocated_item_table) {
                $join->on("oi.id", '=', "$allocated_item_table.model_id")
                    ->where("$allocated_item_table.model", '=', 'OrderItem');
            });

        $query->join('order_item_groups as oig', 'oig.uuid', '=', 'oi.order_item_group_uuid')
            ->join('order as o', 'o.id', '=', 'oig.order_id')
            ->leftJoin('order_status as os', 'os.id', '=', 'o.order_status_id')
            ->where("$allocated_item_table.warehouse_location_uuid", $warehouseUuid)
            ->whereNull('o.deleted_at')
            ->whereNull('o.cloned_to_id')
            ->where(function ($q) {
                $q->where('os.slug', '=', 'open')
                    ->orWhereNull('o.order_status_id');
            });

        if ($product) {
            $query->where('oi.product_id', $product->id);
        }

        $query->select([
            "$allocated_item_table.id as allocated_item_id",
            "oi.product_id as product_id",
            "$allocated_item_table.{$quantityField} as qty",
        ]);

        return $query;
    }

    protected function buildShippedQuery(): Builder
    {
        $order_shipment_item_table = (new OrderShipmentItem)->getTable();

        return OrderShipmentItem::query()
            ->join('shipment as s', 's.id', '=', "$order_shipment_item_table.shipment_id")
            ->whereNull('s.deleted_at')
            ->groupBy("$order_shipment_item_table.allocated_item_id")
            ->select([
                "$order_shipment_item_table.allocated_item_id",
                DB::raw("SUM($order_shipment_item_table.quantity) as shipped_qty"),
            ]);
    }
}
