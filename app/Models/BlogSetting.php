<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BlogSetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $all = static::allCached();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /** @return array<string, string|null> */
    public static function allCached(): array
    {
        return Cache::remember('blog.settings', 60, function () {
            return static::query()->pluck('value', 'key')->all();
        });
    }

    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
            );
        }

        Cache::forget('blog.settings');
    }

    public static function int(string $key, int $default): int
    {
        return (int) static::getValue($key, $default);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = static::getValue($key, $default ? '1' : '0');

        return $value === true || $value === 1 || $value === '1';
    }
}
