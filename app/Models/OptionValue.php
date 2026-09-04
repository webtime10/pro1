<?php

namespace App\Models;

use App\Models\Option;
use App\Models\OptionValueDescription;
use App\Models\ProductOptionValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OptionValue extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'option_id',
        'image',
        'color',
        'sort_order',
    ];

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    public function descriptions(): HasMany
    {
        return $this->hasMany(OptionValueDescription::class);
    }

    public function productOptionValues(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class);
    }
}
