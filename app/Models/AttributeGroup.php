<?php

namespace App\Models;

use App\Models\Attribute;
use App\Models\AttributeGroupDescription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeGroup extends Model
{
    protected $fillable = [
        'sort_order',
    ];

    public function descriptions(): HasMany
    {
        return $this->hasMany(AttributeGroupDescription::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class);
    }
}
