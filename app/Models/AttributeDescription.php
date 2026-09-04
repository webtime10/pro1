<?php

namespace App\Models;

use App\Models\Attribute;
use App\Models\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeDescription extends Model
{
    protected $fillable = [
        'attribute_id',
        'language_id',
        'name',
    ];

    public $timestamps = false;

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
