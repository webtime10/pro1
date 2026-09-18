<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Список GEMINI_API_KEY из config (через запятую) и round-robin.
 */
final class GeminiApiKeys
{
    public const ROUND_ROBIN_CACHE_PREFIX = 'gemini_api_key_rr:';

    /**
     * @return list<string>
     */
    public static function fromConfig(string $configKeyPath = 'services.gemini.key'): array
    {
        $raw = trim((string) config($configKeyPath, ''), " \t\n\r\0\x0B\"'");
        if ($raw === '') {
            return [];
        }

        $keys = [];
        foreach (explode(',', $raw) as $part) {
            $k = trim($part, " \t\n\r\0\x0B\"'");
            if ($k !== '') {
                $keys[] = $k;
            }
        }

        return array_values($keys);
    }

    public static function nextRoundRobin(string $configKeyPath = 'services.gemini.key'): string
    {
        $keys = self::fromConfig($configKeyPath);
        if ($keys === []) {
            return '';
        }

        if (count($keys) === 1) {
            return $keys[0];
        }

        $cacheKey = self::ROUND_ROBIN_CACHE_PREFIX.$configKeyPath;
        $index = (int) Cache::get($cacheKey, -1);
        $index = ($index + 1) % count($keys);
        Cache::forever($cacheKey, $index);

        return $keys[$index];
    }
}
