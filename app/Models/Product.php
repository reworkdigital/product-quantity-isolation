<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

   protected $table = 'product';

    protected $appends = [
        'purchasable',
    ];

    // Accessors & Mutators

    protected function purchasable(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->warehouseStock?->sum('purchasable')
        );
    }

    // Relationships

    public function warehouseStock()
    {
        return $this->hasMany(
            ProductToWarehouseLocation::class,
            'product_id',
            'id'
        );
    }
}
