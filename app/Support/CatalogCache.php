<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Короткий кэш витрины (Redis). Теги — чтобы сбрасывать фильтр целиком.
 */
class CatalogCache
{
    public const TTL = 180;

    public static function remember(string $key, callable $callback, int $ttl = self::TTL): mixed
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Throwable) {
            return $callback();
        }
    }

    public static function rememberFilter(string $key, callable $callback, int $ttl = self::TTL): mixed
    {
        try {
            return Cache::tags(['wtfilter'])->remember($key, $ttl, $callback);
        } catch (\Throwable) {
            return self::remember($key, $callback, $ttl);
        }
    }

    public static function forget(string $key): void
    {
        try {
            Cache::forget($key);
        } catch (\Throwable) {
        }
    }

    public static function forgetFilter(): void
    {
        try {
            Cache::tags(['wtfilter'])->flush();
        } catch (\Throwable) {
        }
    }

    public static function forgetMenu(): void
    {
        try {
            foreach (\App\Models\Language::query()->pluck('id') as $id) {
                Cache::forget('catalog.menu_categories.'.$id);
            }
        } catch (\Throwable) {
        }
    }

    public static function forgetLanguages(): void
    {
        foreach (['catalog.languages.default', 'catalog.languages.active', 'catalog.slug_pattern', 'catalog.locale_pattern'] as $key) {
            self::forget($key);
        }
        self::forgetMenu();
    }
}
