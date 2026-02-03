<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderShipmentItem extends Model
{
    use HasFactory;

    protected $table = 'shipment_item';

    public $timestamps = false;
}
