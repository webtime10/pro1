<?php

namespace App\Models;

use App\Models\Option;
use App\Models\Product;
use App\Models\ProductOptionValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductOption extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'option_id',
        'value',
        'required',
    ];

    protected $casts = [
        'required' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class);
    }
}
