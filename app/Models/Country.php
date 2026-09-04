<?php

namespace App\Models;

use App\Models\Zone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $table = 'country';

    protected $primaryKey = 'country_id';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'iso_code_2',
        'iso_code_3',
        'address_format_id',
        'postcode_required',
        'status',
    ];

    protected $casts = [
        'postcode_required' => 'boolean',
        'status' => 'boolean',
    ];

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class, 'country_id', 'country_id');
    }
}
