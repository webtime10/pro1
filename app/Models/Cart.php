<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    protected $table = 'cart';

    protected $primaryKey = 'cart_id';

    public $timestamps = false;

    protected $fillable = [
        'api_id',
        'customer_id',
        'session_id',
        'product_id',
        'subscription_plan_id',
        'option',
        'quantity',
        'override',
        'price',
        'date_added',
    ];

    protected $casts = [
        'override' => 'boolean',
        'price' => 'decimal:4',
        'date_added' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
