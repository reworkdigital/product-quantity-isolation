<?php

use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/products/{product}/purchasable', function (Product $product) {
    $start = microtime(true);
    $purchasable = $product->purchasable;
    $end = microtime(true);

    return [
        'product_id' => $product->id,
        'purchasable' => $purchasable,
        'time_taken_milliseconds' => round(($end - $start) * 1000),
    ];
});
