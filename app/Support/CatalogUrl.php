<?php

namespace App\Support;

use App\Models\Article;
use App\Models\ArticleDescription;
use App\Models\BlogCategory;
use App\Models\BlogCategoryDescription;
use App\Models\Category;
use App\Models\CategoryDescription;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductDescription;
use App\Support\CatalogCache;
use Illuminate\Support\Facades\DB;

/**
 * SEO-URL витрины (без /category/ и /product/):
 *  - категория: /slug или /parent/slug (макс. 1 уровень родителя)
 *  - товар:     /category-slug/product-slug
 */
class CatalogUrl
{
    /** Базовый сегмент slug (системные пути). $ в lookahead — чтобы ru не резало rubashki-… */
    public const SLUG_SEGMENT = '(?!cart$|checkout$|search$|login$|logout$|admin$|up$|storage$|build$|category$|product$|blog$)[a-zA-Z0-9_-]+';

    /**
     * Первый сегмент SEO для дефолтного языка: ещё и не код языка из БД.
     * Иначе /en/… перехватится как категория «en».
     */
    public static function slugPattern(): string
    {
        return CatalogCache::remember('catalog.slug_pattern', function () {
            try {
                $codes = Language::query()
                    ->where('is_active', true)
                    ->pluck('code')
                    ->filter()
                    ->map(fn ($c) => preg_quote((string) $c, '/'))
                    ->values()
                    ->all();
            } catch (\Throwable) {
                $codes = [];
            }

            $deny = array_merge(
                ['cart', 'checkout', 'search', 'login', 'logout', 'admin', 'up', 'storage', 'build', 'category', 'product', 'blog'],
                $codes
            );

            $denyAlt = implode('|', array_map(fn ($d) => $d.'$', $deny));

            return '(?!'.$denyAlt.')[a-zA-Z0-9_-]+';
        });
    }

    public static function category(Category|int $category, ?Language $lang = null): string
    {
        $lang ??= CatalogLocale::current();
        $categoryId = $category instanceof Category ? (int) $category->id : (int) $category;

        $slug = null;
        $parentId = null;
        if ($category instanceof Category) {
            $parentId = $category->parent_id;
            if ($category->relationLoaded('descriptions')) {
                $slug = $category->descriptions->firstWhere('language_id', $lang->id)?->slug;
            }
        }

        $slug ??= CategoryDescription::query()
            ->where('category_id', $categoryId)
            ->where('language_id', $lang->id)
            ->value('slug');

        if (! $slug) {
            return localized_route('catalog.index', [], true, $lang);
        }

        $parentId ??= $category instanceof Category
            ? $category->parent_id
            : Category::query()->where('id', $categoryId)->value('parent_id');

        if ($parentId) {
            $parentSlug = null;
            if ($category instanceof Category && $category->relationLoaded('parent') && $category->parent) {
                if ($category->parent->relationLoaded('descriptions')) {
                    $parentSlug = $category->parent->descriptions->firstWhere('language_id', $lang->id)?->slug;
                }
            }
            $parentSlug ??= CategoryDescription::query()
                ->where('category_id', $parentId)
                ->where('language_id', $lang->id)
                ->value('slug');
            if ($parentSlug) {
                return localized_route('catalog.seo.two', [
                    'seoA' => $parentSlug,
                    'seoB' => $slug,
                ], true, $lang);
            }
        }

        return localized_route('catalog.seo.one', ['seoA' => $slug], true, $lang);
    }

    public static function product(Product|int $product, ?Language $lang = null, ?Category $preferredCategory = null): string
    {
        $lang ??= CatalogLocale::current();
        $productId = $product instanceof Product ? (int) $product->id : (int) $product;

        $productSlug = null;
        if ($product instanceof Product && $product->relationLoaded('descriptions')) {
            $productSlug = $product->descriptions->firstWhere('language_id', $lang->id)?->slug;
        }
        $productSlug ??= ProductDescription::query()
            ->where('product_id', $productId)
            ->where('language_id', $lang->id)
            ->value('slug');

        if (! $productSlug) {
            return localized_route('catalog.index', [], true, $lang);
        }

        $categoryId = $preferredCategory?->id;
        $categorySlug = null;
        if ($preferredCategory) {
            if ($preferredCategory->relationLoaded('descriptions')) {
                $categorySlug = $preferredCategory->descriptions->firstWhere('language_id', $lang->id)?->slug;
            }
        } elseif ($product instanceof Product && $product->relationLoaded('categories') && $product->categories->isNotEmpty()) {
            $cat = $product->categories->sortBy('id')->first();
            $categoryId = $cat?->id;
            if ($cat?->relationLoaded('descriptions')) {
                $categorySlug = $cat->descriptions->firstWhere('language_id', $lang->id)?->slug;
            }
        }

        if (! $categoryId) {
            $categoryId = DB::table('category_product')
                ->where('product_id', $productId)
                ->orderBy('category_id')
                ->value('category_id');
        }

        $categorySlug ??= $categoryId
            ? CategoryDescription::query()
                ->where('category_id', $categoryId)
                ->where('language_id', $lang->id)
                ->value('slug')
            : null;

        if (! $categorySlug) {
            return localized_route('catalog.seo.one', ['seoA' => $productSlug], true, $lang);
        }

        return localized_route('catalog.seo.two', [
            'seoA' => $categorySlug,
            'seoB' => $productSlug,
        ], true, $lang);
    }

    public static function productBySlug(string $productSlug, ?Language $lang = null): string
    {
        $lang ??= CatalogLocale::current();
        $productId = ProductDescription::query()
            ->where('language_id', $lang->id)
            ->where('slug', $productSlug)
            ->value('product_id');

        return $productId
            ? self::product((int) $productId, $lang)
            : localized_route('catalog.seo.one', ['seoA' => $productSlug], true, $lang);
    }

    public static function categoryBySlug(string $categorySlug, ?Language $lang = null): string
    {
        $lang ??= CatalogLocale::current();
        $categoryId = CategoryDescription::query()
            ->where('language_id', $lang->id)
            ->where('slug', $categorySlug)
            ->value('category_id');

        return $categoryId
            ? self::category((int) $categoryId, $lang)
            : localized_route('catalog.seo.one', ['seoA' => $categorySlug], true, $lang);
    }

    /**
     * Разбор двух сегментов: сначала товар в категории, иначе дочерняя категория.
     *
     * @return array{type: 'product'|'category', category: Category, product?: Product, catDesc: CategoryDescription, prodDesc?: ProductDescription}
     */
    public static function resolveTwo(string $seoA, string $seoB, Language $lang): array
    {
        $parentDesc = CategoryDescription::query()
            ->where('language_id', $lang->id)
            ->where('slug', $seoA)
            ->first();

        abort_if(! $parentDesc, 404);

        $parent = Category::query()
            ->where('id', $parentDesc->category_id)
            ->where('status', true)
            ->firstOrFail();

        // 1) товар в этой категории: /category/product
        $prodDesc = ProductDescription::query()
            ->where('language_id', $lang->id)
            ->where('slug', $seoB)
            ->first();

        if ($prodDesc) {
            $inCategory = DB::table('category_product')
                ->where('category_id', $parent->id)
                ->where('product_id', $prodDesc->product_id)
                ->exists();

            if ($inCategory) {
                $product = Product::query()
                    ->where('id', $prodDesc->product_id)
                    ->where('status', true)
                    ->first();
                if ($product) {
                    return [
                        'type' => 'product',
                        'category' => $parent,
                        'catDesc' => $parentDesc,
                        'product' => $product,
                        'prodDesc' => $prodDesc,
                    ];
                }
            }
        }

        // 2) дочерняя категория: /parent/child
        $childDesc = CategoryDescription::query()
            ->where('language_id', $lang->id)
            ->where('slug', $seoB)
            ->first();

        if ($childDesc) {
            $child = Category::query()
                ->where('id', $childDesc->category_id)
                ->where('parent_id', $parent->id)
                ->where('status', true)
                ->first();
            if ($child) {
                return [
                    'type' => 'category',
                    'category' => $child,
                    'catDesc' => $childDesc,
                ];
            }
        }

        abort(404);
    }

    /**
     * @return array{type: 'category', category: Category, catDesc: CategoryDescription}
     */
    public static function resolveOne(string $seoA, Language $lang): array
    {
        $catDesc = CategoryDescription::query()
            ->where('language_id', $lang->id)
            ->where('slug', $seoA)
            ->firstOrFail();

        $category = Category::query()
            ->where('id', $catDesc->category_id)
            ->where('status', true)
            ->firstOrFail();

        return [
            'type' => 'category',
            'category' => $category,
            'catDesc' => $catDesc,
        ];
    }

    public static function blogIndex(?Language $lang = null): string
    {
        return localized_route('catalog.blog.index', [], true, $lang);
    }

    public static function blogCategory(BlogCategory|int $category, ?Language $lang = null): string
    {
        $lang ??= CatalogLocale::current();
        $id = $category instanceof BlogCategory ? (int) $category->id : (int) $category;
        $slug = null;

        if ($category instanceof BlogCategory && $category->relationLoaded('descriptions')) {
            $slug = $category->descriptions->firstWhere('language_id', $lang->id)?->slug;
        }

        $slug ??= BlogCategoryDescription::query()
            ->where('blog_category_id', $id)
            ->where('language_id', $lang->id)
            ->value('slug');

        if (! $slug) {
            return self::blogIndex($lang);
        }

        return localized_route('catalog.blog.show', ['slug' => $slug], true, $lang);
    }

    public static function blogArticle(Article|int $article, ?Language $lang = null, ?BlogCategory $category = null): string
    {
        $lang ??= CatalogLocale::current();
        $id = $article instanceof Article ? (int) $article->id : (int) $article;
        $slug = null;

        if ($article instanceof Article && $article->relationLoaded('descriptions')) {
            $slug = $article->descriptions->firstWhere('language_id', $lang->id)?->slug;
        }

        $slug ??= ArticleDescription::query()
            ->where('article_id', $id)
            ->where('language_id', $lang->id)
            ->value('slug');

        if (! $slug) {
            return self::blogIndex($lang);
        }

        $category ??= $article instanceof Article ? $article->mainBlogCategory() : null;
        $catSlug = null;
        if ($category) {
            if ($category->relationLoaded('descriptions')) {
                $catSlug = $category->descriptions->firstWhere('language_id', $lang->id)?->slug;
            }
            $catSlug ??= BlogCategoryDescription::query()
                ->where('blog_category_id', $category->id)
                ->where('language_id', $lang->id)
                ->value('slug');
        }

        if ($catSlug) {
            return localized_route('catalog.blog.article', [
                'categorySlug' => $catSlug,
                'articleSlug' => $slug,
            ], true, $lang);
        }

        return localized_route('catalog.blog.show', ['slug' => $slug], true, $lang);
    }
}
