<?php

namespace App\Models;

use App\Models\Attribute;
use App\Models\Language;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttribute extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'attribute_id',
        'language_id',
        'text',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
