<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BlogCategory extends Model
{
    protected $fillable = [
        'image',
        'parent_id',
        'top',
        'column',
        'sort_order',
        'status',
        'noindex',
    ];

    protected $casts = [
        'top' => 'boolean',
        'status' => 'boolean',
        'noindex' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function descriptions(): HasMany
    {
        return $this->hasMany(BlogCategoryDescription::class);
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_blog_category')
            ->withPivot('main');
    }

    public function descriptionForLanguage(int $languageId): ?BlogCategoryDescription
    {
        return $this->descriptions->firstWhere('language_id', $languageId);
    }

    public function descendantIdList(): array
    {
        return DB::table('blog_category_paths')
            ->where('path_id', $this->id)
            ->where('blog_category_id', '!=', $this->id)
            ->pluck('blog_category_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public static function deleteTrees(array $rootIds): int
    {
        $rootIds = array_values(array_unique(array_map('intval', $rootIds)));
        if ($rootIds === []) {
            return 0;
        }

        $allIds = collect($rootIds);
        foreach ($rootIds as $rootId) {
            $descendants = DB::table('blog_category_paths')
                ->where('path_id', $rootId)
                ->where('blog_category_id', '!=', $rootId)
                ->pluck('blog_category_id');
            $allIds = $allIds->merge($descendants);
        }

        $allIds = $allIds->map(fn ($id) => (int) $id)->unique()->values();
        if ($allIds->isEmpty()) {
            return 0;
        }

        $ordered = DB::table('blog_category_paths')
            ->whereIn('blog_category_id', $allIds)
            ->groupBy('blog_category_id')
            ->orderByRaw('MAX(level) DESC')
            ->pluck('blog_category_id')
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
        $excludeIds = array_map('intval', $excludeIds);

        $walk = function ($parentId, $depth) use (&$walk, $all, $lang, &$rows, $excludeIds) {
            $items = $all->filter(function ($c) use ($parentId) {
                if ($parentId === null) {
                    return $c->parent_id === null;
                }

                return (int) $c->parent_id === (int) $parentId;
            })->sortBy('sort_order')->values();

            foreach ($items as $cat) {
                if (in_array((int) $cat->id, $excludeIds, true)) {
                    continue;
                }
                $d = $cat->descriptions->firstWhere('language_id', $lang->id);
                $rows->push([
                    'id' => $cat->id,
                    'label' => str_repeat('— ', $depth).($d->name ?? ('#'.$cat->id)),
                ]);
                $walk($cat->id, $depth + 1);
            }
        };

        $walk(null, 0);

        return $rows;
    }

    public static function rebuildPaths(): void
    {
        DB::table('blog_category_paths')->delete();

        $categories = static::query()->pluck('parent_id', 'id')->toArray();
        $rowsToInsert = [];

        foreach ($categories as $categoryId => $parentId) {
            $chain = [(int) $categoryId];
            $currParentId = $parentId ? (int) $parentId : null;

            while ($currParentId !== null && isset($categories[$currParentId])) {
                array_unshift($chain, $currParentId);
                $currParentId = $categories[$currParentId] ? (int) $categories[$currParentId] : null;
            }

            foreach ($chain as $level => $pathId) {
                $rowsToInsert[] = [
                    'blog_category_id' => $categoryId,
                    'path_id' => $pathId,
                    'level' => $level,
                ];
            }
        }

        foreach (array_chunk($rowsToInsert, 500) as $chunk) {
            DB::table('blog_category_paths')->insert($chunk);
        }
    }
}
