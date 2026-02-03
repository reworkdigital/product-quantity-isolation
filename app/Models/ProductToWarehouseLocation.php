<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductToWarehouseLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_to_warehouse_location';

    // Accessors & Mutators

    protected function purchasable(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                $unshipped = (int) ($this->unshipped_orders ?? 0);
                $threshold = (int) ($this->threshold ?? 0);
                $outgoingTransfers = (int) ($this->outgoing_transfer_quantity ?? 0);
                $preOrderQuantity = (int) ($this->pre_order_quantity ?? 0);

                if ($preOrderQuantity > 0) {
                    return ($preOrderQuantity - $threshold) - $unshipped - $outgoingTransfers;
                }

                $onHand = (int) ($this->on_hand_quantity ?? 0);
                $orderable = (int) ($this->orderable_quantity ?? 0);

                return ($onHand + $orderable - $threshold) - $unshipped - $outgoingTransfers;
            }
        );
    }

    protected function unshippedOrders(): Attribute
    {
        return Attribute::make(
            get: fn (): int => (int) (
                $this->warehouseLocation?->unshippedOrdersFor($this->product) ?? 0
            ),
        );
    }

    public function outgoingTransferQuantity(): Attribute
    {
        return Attribute::get(function (): int {
            if ($this->relationLoaded('outgoingStockTransferItems')) {
                return (int) $this->outgoingStockTransferItems->sum('quantity');
            }

            return (int) $this->outgoingStockTransferItems()->sum('quantity');
        });
    }

    // Relationships

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(
            WarehouseLocation::class,
            'warehouse_location_uuid',
            'uuid');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
            'product_id',
        );
    }

    public function outgoingStockTransferItems(): HasMany
    {
        return $this->hasMany(StockTransferItem::class, 'product_id', 'product_id')
            ->whereHas('stockTransfer', function ($query) {
                $query->where('warehouse_from_uuid', $this->warehouse_location_uuid)
                    ->whereNull('received_at')
                    ->whereNull('deleted_at');
            });
    }
}
