<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    protected $table = 'order_status';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'order_status_id',
        'language_id',
        'name',
    ];
}
