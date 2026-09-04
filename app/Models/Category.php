<?php

namespace App\Models;

use App\Models\CategoryDescription;
use App\Models\Language;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Category extends Model
{
    protected $fillable = [
        'image',
        'parent_id',
        'top',
        'column',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'top' => 'boolean',
        'status' => 'boolean',
    ];

    /** Self-reference: без use App\Models\Category — класс уже в этом файле. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

    public function descriptions(): HasMany
    {
        return $this->hasMany(CategoryDescription::class, 'category_id', 'id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product');
    }

    public function descriptionForLanguage(int $languageId): ?CategoryDescription
    {
        return $this->descriptions->firstWhere('language_id', $languageId);
    }

    /**
     * ID всех вложенных категорий (чтобы не выбрать себя/потомка родителем).
     */
    public function descendantIdList(): array
    {
        return DB::table('category_paths')
            ->where('path_id', $this->id)
            ->where('category_id', '!=', $this->id)
            ->pluck('category_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    /**
     * Удалить категории вместе со всеми потомками (глубже → раньше).
     *
     * @param  array<int>  $rootIds
     * @return int число реально удалённых записей
     */
    public static function deleteTrees(array $rootIds): int
    {
        $rootIds = array_values(array_unique(array_map('intval', $rootIds)));
        if ($rootIds === []) {
            return 0;
        }

        $allIds = collect($rootIds);
        foreach ($rootIds as $rootId) {
            $descendants = DB::table('category_paths')
                ->where('path_id', $rootId)
                ->where('category_id', '!=', $rootId)
                ->pluck('category_id');
            $allIds = $allIds->merge($descendants);
        }

        $allIds = $allIds->map(fn ($id) => (int) $id)->unique()->values();
        if ($allIds->isEmpty()) {
            return 0;
        }

        // Сначала самые глубокие — меньше риск FK по category_paths / parent_id
        $ordered = DB::table('category_paths')
            ->whereIn('category_id', $allIds)
            ->groupBy('category_id')
            ->orderByRaw('MAX(level) DESC')
            ->pluck('category_id')
            ->map(fn ($id) => (int) $id);

        $ordered = $ordered->merge($allIds->diff($ordered))->unique()->values();

        $deleted = 0;
        foreach ($ordered as $id) {
            if (static::whereKey($id)->delete()) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Список для select «Родитель»: дерево с отступами, как в OpenCart.
     *
     * @param  array<int>  $excludeIds  id, которые нельзя выбрать (себя и потомков при редактировании)
     */
    public static function treeForParentSelect(?Language $lang = null, array $excludeIds = []): Collection
    {
        $lang = $lang ?? Language::getDefault();
        if (! $lang) {
            return collect();
        }

        $all = static::with(['descriptions' => fn ($q) => $q->where('language_id', $lang->id)])
            ->orderBy('sort_order')
            ->get();

        $rows = collect();

        $walk = function ($parentId, $depth) use (&$walk, $all, $lang, &$rows, $excludeIds) {
            $items = $all->filter(function ($c) use ($parentId) {
                if ($parentId === null) {
                    return $c->parent_id === null;
                }

                return (int) $c->parent_id === (int) $parentId;
            })->sortBy('sort_order')->values();

            foreach ($items as $cat) {
                if (in_array((int) $cat->id, array_map('intval', $excludeIds), true)) {
                    continue;
                }
                $d = $cat->descriptions->firstWhere('language_id', $lang->id);
                $label = str_repeat('— ', $depth).($d->name ?? ('#'.$cat->id));
                $rows->push(['id' => $cat->id, 'label' => $label]);
                $walk($cat->id, $depth + 1);
            }
        };

        $walk(null, 0);

        return $rows;
    }

    /**
     * Пересобрать category_paths для всего дерева (после изменений).
     * Иерархия строится в памяти; вставка — пачками по 500 строк.
     */
    public static function rebuildPaths(): void
    {
        // 1. Очищаем таблицу путей
        DB::table('category_paths')->delete();

        // 2. Выгружаем все категории одним запросом [id => parent_id]
        $categories = static::query()->pluck('parent_id', 'id')->toArray();

        $rowsToInsert = [];

        // 3. Строим цепочки путей в памяти PHP без повторных обращений к БД
        foreach ($categories as $categoryId => $parentId) {
            $chain = [(int) $categoryId];
            $currParentId = $parentId ? (int) $parentId : null;

            while ($currParentId !== null && isset($categories[$currParentId])) {
                array_unshift($chain, $currParentId);
                $currParentId = $categories[$currParentId] ? (int) $categories[$currParentId] : null;
            }

            foreach ($chain as $level => $pathId) {
                $rowsToInsert[] = [
                    'category_id' => $categoryId,
                    'path_id' => $pathId,
                    'level' => $level,
                ];
            }
        }

        // 4. Пакетированная вставка (bulk insert) пачками по 500 строк
        if (! empty($rowsToInsert)) {
            foreach (array_chunk($rowsToInsert, 500) as $chunk) {
                DB::table('category_paths')->insert($chunk);
            }
        }

        \App\Support\CatalogCache::forgetMenu();
        \App\Support\CatalogCache::forgetFilter();
    }
}
