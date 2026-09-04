<?php

namespace App\Models;

use App\Models\Option;
use App\Models\OptionValue;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOptionValue extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_option_id',
        'product_id',
        'option_id',
        'option_value_id',
        'quantity',
        'subtract',
        'price',
        'price_prefix',
        'points',
        'points_prefix',
        'weight',
        'weight_prefix',
    ];

    protected $casts = [
        'subtract' => 'boolean',
        'price' => 'decimal:4',
        'weight' => 'decimal:8',
    ];

    public function productOption(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    public function optionValue(): BelongsTo
    {
        return $this->belongsTo(OptionValue::class);
    }
}
