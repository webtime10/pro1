<?php

namespace App\Models;

use App\Models\OrderProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderOption extends Model
{
    protected $table = 'order_option';

    protected $primaryKey = 'order_option_id';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'order_product_id',
        'product_option_id',
        'product_option_value_id',
        'name',
        'value',
        'type',
    ];

    public function orderProduct(): BelongsTo
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id', 'order_product_id');
    }
}
