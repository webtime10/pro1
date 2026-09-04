<?php

namespace App\Models;

use App\Models\Language;
use App\Models\Option;
use App\Models\OptionValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OptionValueDescription extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'option_value_id',
        'language_id',
        'option_id',
        'name',
    ];

    public function optionValue(): BelongsTo
    {
        return $this->belongsTo(OptionValue::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
