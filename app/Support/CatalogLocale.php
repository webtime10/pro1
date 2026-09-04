<?php

namespace App\Support;

use App\Models\CategoryDescription;
use App\Models\Language;
use App\Support\CatalogCache;
use Illuminate\Support\Facades\Route;

/**
 * Текущий язык витрины. Никаких захардкоженных ru/en — только запись из таблицы languages.
 */
class CatalogLocale
{
    private static ?Language $current = null;

    /**
     * Префикс языка в URL: только активные недефолтные коды из таблицы languages.
     * Нельзя делать широкий regex — иначе /noski-bulk перехватится как «язык».
     */
    public static function localePattern(): string
    {
        return CatalogCache::remember('catalog.locale_pattern', function () {
            try {
                $codes = Language::query()
                    ->where('is_active', true)
                    ->where('is_default', false)
                    ->orderBy('sort_order')
                    ->pluck('code')
                    ->filter()
                    ->map(fn ($c) => preg_quote((string) $c, '/'))
                    ->values()
                    ->all();
            } catch (\Throwable) {
                $codes = [];
            }

            if ($codes === []) {
                return '[a-z]{2,3}(?:-[a-zA-Z]{2,4})?';
            }

            return implode('|', $codes);
        });
    }

    public static function set(?Language $language): void
    {
        self::$current = $language;
        if ($language) {
            app()->setLocale($language->code);
        }
    }

    public static function current(): Language
    {
        if (self::$current) {
            return self::$current;
        }

        $lang = Language::getDefault();
        abort_if(! $lang, 503, 'Не задан язык по умолчанию в админке.');
        self::$current = $lang;

        return $lang;
    }

    public static function isDefault(?Language $language = null): bool
    {
        $language ??= self::current();

        return (bool) $language->is_default;
    }

    /** Активный язык по коду из URL (или null). */
    public static function findActiveByCode(string $code): ?Language
    {
        return Language::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
    }

    /**
     * URL той же страницы на другом языке (slug из descriptions целевого языка).
     */
    public static function switchUrl(Language $target): string
    {
        $route = Route::current();
        if (! $route) {
            return localized_route('catalog.index', [], true, $target);
        }

        $name = (string) $route->getName();
        $name = preg_replace('/^localized\./', '', $name) ?: 'catalog.index';
        $params = $route->parameters();
        unset($params['locale']);
        $current = self::current();

        if (in_array($name, ['catalog.blog.index', 'catalog.blog.show', 'catalog.blog.article'], true)) {
            try {
                if ($name === 'catalog.blog.index') {
                    return CatalogUrl::blogIndex($target);
                }

                if ($name === 'catalog.blog.article') {
                    $articleSlug = (string) ($params['articleSlug'] ?? '');
                    $articleId = \App\Models\ArticleDescription::query()
                        ->where('language_id', $current->id)
                        ->where('slug', $articleSlug)
                        ->value('article_id');
                    if ($articleId) {
                        $article = \App\Models\Article::with('blogCategories.descriptions', 'descriptions')->find($articleId);
                        if ($article) {
                            return CatalogUrl::blogArticle($article, $target, $article->mainBlogCategory());
                        }
                    }
                }

                $slug = (string) ($params['slug'] ?? '');
                $catId = \App\Models\BlogCategoryDescription::query()
                    ->where('language_id', $current->id)
                    ->where('slug', $slug)
                    ->value('blog_category_id');
                if ($catId) {
                    return CatalogUrl::blogCategory((int) $catId, $target);
                }
                $articleId = \App\Models\ArticleDescription::query()
                    ->where('language_id', $current->id)
                    ->where('slug', $slug)
                    ->value('article_id');
                if ($articleId) {
                    $article = \App\Models\Article::with('blogCategories.descriptions', 'descriptions')->find($articleId);
                    if ($article) {
                        return CatalogUrl::blogArticle($article, $target, $article->mainBlogCategory());
                    }
                }
            } catch (\Throwable) {
            }
        }

        if (in_array($name, ['catalog.seo.one', 'catalog.seo.two'], true)) {
            try {
                if ($name === 'catalog.seo.two') {
                    $resolved = CatalogUrl::resolveTwo(
                        (string) ($params['seoA'] ?? ''),
                        (string) ($params['seoB'] ?? ''),
                        $current
                    );
                    if ($resolved['type'] === 'product') {
                        return CatalogUrl::product($resolved['product'], $target, $resolved['category']);
                    }

                    return CatalogUrl::category($resolved['category'], $target);
                }

                $slug = (string) ($params['seoA'] ?? '');
                $catId = CategoryDescription::query()
                    ->where('language_id', $current->id)
                    ->where('slug', $slug)
                    ->value('category_id');
                if ($catId) {
                    return CatalogUrl::category((int) $catId, $target);
                }
            } catch (\Throwable) {
                // fallback ниже
            }
        }

        return localized_route(
            in_array($name, ['catalog.seo.one', 'catalog.seo.two'], true) ? 'catalog.index' : $name,
            [],
            true,
            $target
        );
    }
}
