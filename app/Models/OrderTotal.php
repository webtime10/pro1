<?php

namespace App\Models;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTotal extends Model
{
    protected $table = 'order_total';

    protected $primaryKey = 'order_total_id';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'extension',
        'code',
        'title',
        'value',
        'sort_order',
    ];

    protected $casts = [
        'value' => 'decimal:4',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
