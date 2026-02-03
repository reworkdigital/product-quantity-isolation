<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderItemGroup extends Model
{
    use HasFactory;

    protected $table = 'order_item_groups';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($order_item_group) {
            do {
                $uuid = Str::uuid();
            } while ($order_item_group::query()->where('uuid', $uuid)->exists());

            $order_item_group->uuid = $uuid;
        });
    }
}
