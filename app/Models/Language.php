<?php

namespace App\Models;

use App\Support\CatalogCache;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = [
        'code',
        'name',
        'locale',
        'directory',
        'image',
        'sort_order',
        'status',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'status' => 'boolean',
    ];

    public static function getActive()
    {
        return CatalogCache::remember('catalog.languages.active', fn () => static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get());
    }

    /**
     * Языки для форм админки: если все «неактивны», берём любые — иначе поля названия не рисуются.
     */
    public static function forAdminForms()
    {
/*
активный язык
SELECT * 
FROM languages 
WHERE status = 1 
ORDER BY sort_order ASC, id ASC;
*/

        $langs = static::getActive();
        if ($langs->isEmpty()) {
            $langs = static::query()->orderBy('sort_order')->orderBy('id')->get();
        }

        return $langs;
    }
  /*
    SELECT * FROM `languages` 
    WHERE `is_default` = 1 
      AND `is_active` = 1 
    LIMIT 1;

    // врнуть все языки
    */
    public static function getDefault(): ?self
    {
        return CatalogCache::remember(
            'catalog.languages.default',
            fn () => static::query()
                ->where('is_default', true)
                ->where('is_active', true)
                ->first()
        );
    }
}
