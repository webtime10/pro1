<?php

namespace App\Models;

use App\Models\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $table = 'customer';

    protected $primaryKey = 'customer_id';

    public $timestamps = false;

    protected $fillable = [
        'customer_group_id',
        'store_id',
        'language_id',
        'firstname',
        'lastname',
        'email',
        'telephone',
        'password',
        'custom_field',
        'newsletter',
        'ip',
        'status',
        'safe',
        'token',
        'code',
        'date_added',
    ];

    protected $casts = [
        'newsletter' => 'boolean',
        'status' => 'boolean',
        'safe' => 'boolean',
        'date_added' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'token',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'customer_id', 'customer_id');
    }
}
