<?php

namespace App\Models;

use App\Models\Language;
use App\Models\Option;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OptionDescription extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'option_id',
        'language_id',
        'name',
    ];

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
