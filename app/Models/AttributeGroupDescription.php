<?php

namespace App\Models;

use App\Models\AttributeGroup;
use App\Models\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeGroupDescription extends Model
{
    protected $fillable = [
        'attribute_group_id',
        'language_id',
        'name',
    ];

    public function attributeGroup(): BelongsTo
    {
        return $this->belongsTo(AttributeGroup::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
