<?php

namespace App\Models;

use App\Models\OptionDescription;
use App\Models\OptionValue;
use App\Models\ProductOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Option extends Model
{
    public const TYPES = [
        'select',
        'radio',
        'checkbox',
        'text',
        'textarea',
        'file',
        'date',
        'time',
        'datetime',
    ];

    protected $fillable = [
        'type',
        'sort_order',
    ];

    public $timestamps = false;

    public function descriptions(): HasMany
    {
        return $this->hasMany(OptionDescription::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(OptionValue::class);
    }

    public function productOptions(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }
}
