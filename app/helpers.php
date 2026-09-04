<?php

use App\Models\Article;
use App\Models\BlogCategory;
use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use App\Support\CatalogLocale;
use App\Support\CatalogUrl;
use Illuminate\Support\Arr;

if (! function_exists('catalog_lang')) {
    function catalog_lang(): Language
    {
        return CatalogLocale::current();
    }
}

if (! function_exists('localized_route')) {
    /**
     * Роут витрины с учётом текущего (или переданного) языка из БД:
     * default → /path , иначе → /{code}/path
     */
    function localized_route(string $name, mixed $parameters = [], bool $absolute = true, ?Language $language = null): string
    {
        $language ??= CatalogLocale::current();
        $parameters = Arr::wrap($parameters);
        $name = preg_replace('/^localized\./', '', $name) ?: $name;

        if ($language->is_default) {
            return route($name, $parameters, $absolute);
        }

        return route('localized.'.$name, array_merge(['locale' => $language->code], $parameters), $absolute);
    }
}

if (! function_exists('catalog_category_url')) {
    function catalog_category_url(Category|int $category, ?Language $language = null): string
    {
        return CatalogUrl::category($category, $language);
    }
}

if (! function_exists('catalog_product_url')) {
    function catalog_product_url(Product|int $product, ?Language $language = null, ?Category $category = null): string
    {
        return CatalogUrl::product($product, $language, $category);
    }
}

if (! function_exists('catalog_blog_url')) {
    function catalog_blog_url(): string
    {
        return CatalogUrl::blogIndex();
    }
}

if (! function_exists('catalog_blog_category_url')) {
    function catalog_blog_category_url(BlogCategory|int $category, ?Language $language = null): string
    {
        return CatalogUrl::blogCategory($category, $language);
    }
}

if (! function_exists('catalog_article_url')) {
    function catalog_article_url(Article|int $article, ?Language $language = null, ?BlogCategory $category = null): string
    {
        return CatalogUrl::blogArticle($article, $language, $category);
    }
}

if (! function_exists('catalog_image_url')) {
    function catalog_image_url(?string $path): string
    {
        return app(\App\Services\ImageManagerService::class)->publicUrl((string) $path);
    }
}

if (! function_exists('catalog_hex_color')) {
    function catalog_hex_color(?string $color): string
    {
        $color = trim((string) $color);

        if (! preg_match('/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i', $color, $m)) {
            return '';
        }

        $hex = strtolower($m[1]);
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return '#'.$hex;
    }
}

if (! function_exists('catalog_color_from_name')) {
    function catalog_color_from_name(?string $name): string
    {
        $key = mb_strtolower(trim((string) $name));
        $key = preg_replace('/\s+/u', ' ', $key) ?? $key;

        $map = [
            'белый' => '#ffffff',
            'чёрный' => '#111111',
            'черный' => '#111111',
            'серый' => '#9e9e9e',
            'синий' => '#1565c0',
            'голубой' => '#42a5f5',
            'красный' => '#e53935',
            'зелёный' => '#43a047',
            'зеленый' => '#43a047',
            'бежевый' => '#d7c4a3',
            'коричневый' => '#6d4c41',
            'хаки' => '#6b8e23',
            'бордовый' => '#7b1e3a',
            'телесный' => '#e0b090',
            'индиго' => '#3f51b5',
        ];

        return $map[$key] ?? '';
    }
}

if (! function_exists('catalog_option_swatch')) {
    /**
     * Плашка значения опции: HEX важнее картинки, картинка — только если цвета нет.
     *
     * @return array{color: string, image: string}
     */
    function catalog_option_swatch(?string $color, ?string $image, ?string $name = null): array
    {
        $hex = catalog_hex_color($color);
        if ($hex === '') {
            $hex = catalog_color_from_name($name);
        }
        if ($hex !== '') {
            return ['color' => $hex, 'image' => ''];
        }

        $path = ltrim(str_replace('\\', '/', trim((string) $image)), '/');
        if ($path === '') {
            return ['color' => '', 'image' => ''];
        }

        $images = app(\App\Services\ImageManagerService::class);
        $url = $images->publicUrl($path);

        return [
            'color' => '',
            'image' => $url === $images->placeholderUrl() ? '' : $url,
        ];
    }
}
