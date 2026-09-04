<?php

namespace App\Models;

use App\Models\Order;
use App\Models\OrderOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderProduct extends Model
{
    protected $table = 'order_product';

    protected $primaryKey = 'order_product_id';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'product_id',
        'master_id',
        'name',
        'model',
        'quantity',
        'price',
        'total',
        'tax',
        'reward',
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'total' => 'decimal:4',
        'tax' => 'decimal:4',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(OrderOption::class, 'order_product_id', 'order_product_id');
    }
}
