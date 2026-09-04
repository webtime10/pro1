<?php

namespace App\Models;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderHistory extends Model
{
    protected $table = 'order_history';

    protected $primaryKey = 'order_history_id';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'order_status_id',
        'notify',
        'comment',
        'date_added',
    ];

    protected $casts = [
        'notify' => 'boolean',
        'date_added' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
