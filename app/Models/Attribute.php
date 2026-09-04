<?php

namespace App\Models;

use App\Models\AttributeDescription;
use App\Models\AttributeGroup;
use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    protected $fillable = [
        'attribute_group_id',
        'sort_order',
    ];

    public $timestamps = false;

    public function attributeGroup(): BelongsTo
    {
        return $this->belongsTo(AttributeGroup::class);
    }

    public function descriptions(): HasMany
    {
        return $this->hasMany(AttributeDescription::class);
    }

    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }
}
