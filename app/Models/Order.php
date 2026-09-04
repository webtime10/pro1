<?php

namespace App\Models;

use App\Models\OrderHistory;
use App\Models\OrderProduct;
use App\Models\OrderTotal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'order';

    protected $primaryKey = 'order_id';

    public $timestamps = false;

    protected $fillable = [
        'subscription_id',
        'invoice_no',
        'invoice_prefix',
        'transaction_id',
        'store_id',
        'store_name',
        'store_url',
        'customer_id',
        'customer_group_id',
        'firstname',
        'lastname',
        'email',
        'telephone',
        'custom_field',
        'payment_address_id',
        'payment_firstname',
        'payment_lastname',
        'payment_company',
        'payment_address_1',
        'payment_address_2',
        'payment_city',
        'payment_postcode',
        'payment_country',
        'payment_country_id',
        'payment_zone',
        'payment_zone_id',
        'payment_address_format',
        'payment_custom_field',
        'payment_method',
        'shipping_address_id',
        'shipping_firstname',
        'shipping_lastname',
        'shipping_company',
        'shipping_address_1',
        'shipping_address_2',
        'shipping_city',
        'shipping_postcode',
        'shipping_country',
        'shipping_country_id',
        'shipping_zone',
        'shipping_zone_id',
        'shipping_address_format',
        'shipping_custom_field',
        'shipping_method',
        'comment',
        'total',
        'order_status_id',
        'affiliate_id',
        'commission',
        'marketing_id',
        'tracking',
        'language_id',
        'language_code',
        'currency_id',
        'currency_code',
        'currency_value',
        'ip',
        'forwarded_ip',
        'user_agent',
        'accept_language',
        'date_added',
        'date_modified',
    ];

    protected $casts = [
        'total' => 'decimal:4',
        'commission' => 'decimal:4',
        'currency_value' => 'decimal:8',
        'date_added' => 'datetime',
        'date_modified' => 'datetime',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(OrderProduct::class, 'order_id', 'order_id');
    }

    public function totals(): HasMany
    {
        return $this->hasMany(OrderTotal::class, 'order_id', 'order_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderHistory::class, 'order_id', 'order_id');
    }
}
