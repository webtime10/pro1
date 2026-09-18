<?php

namespace App\Services;

use App\Models\Language;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ArticleTranslateService
{
    /** @var list<string> */
    private const FIELDS = [
        'name',
        'description',
        'meta_h1',
        'meta_title',
        'meta_description',
        'meta_keyword',
        'tag',
    ];

    public function __construct(
        private GeminiService $gemini,
        private AiTextCleaner $cleaner,
    ) {}

    /**
     * Перевод полей статьи с исходного языка на остальные активные (обычно en + uk).
     *
     * @param  array<string, string>  $sourceFields
     * @param  Collection<int, Language>|null  $languages
     * @return array{source_code: string, source: array<string, string>, translations: array<string, array<string, string>>}
     */
    public function translate(array $sourceFields, string $sourceCode = 'ru', ?Collection $languages = null): array
    {
        $languages ??= Language::forAdminForms();
        $sourceCode = strtolower(trim($sourceCode));

        $source = [];
        foreach (self::FIELDS as $field) {
            $raw = $this->stringifyField($sourceFields[$field] ?? '');
            $source[$field] = $field === 'description'
                ? $this->cleaner->clean($raw)
                : $this->cleaner->cleanPlain($raw);
        }

        if (trim(strip_tags($source['name'].$source['description'])) === '') {
            throw new \InvalidArgumentException('Нет русского текста для перевода: заполните название или описание.');
        }

        $targets = $languages
            ->filter(fn (Language $lang) => $lang->is_active && strtolower($lang->code) !== $sourceCode)
            ->values();

        if ($targets->isEmpty()) {
            throw new \RuntimeException('Нет целевых языков (en/uk). Добавьте их в админке.');
        }

        $targetCodes = $targets->map(fn (Language $l) => strtolower($l->code))->values()->all();
        $targetNames = $targets->mapWithKeys(fn (Language $l) => [
            strtolower($l->code) => $l->name,
        ])->all();

        $instruction = $this->buildInstruction($sourceCode, $targetCodes, $targetNames);
        $material = json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $timeout = max(60, (int) config('services.gemini.chat_timeout', 900));
        $raw = $this->gemini->chat($material, $instruction, $timeout, [
            'temperature' => 0.2,
            'responseMimeType' => 'application/json',
        ]);

        if ($raw === null || trim($raw) === '') {
            $detail = trim($this->gemini->lastError());
            $hint = $detail !== '' ? $detail : 'Проверьте ключи или повторите.';
            throw new \RuntimeException('Gemini не вернул перевод. '.$hint);
        }

        $parsed = $this->decodeJson($raw);
        // Иногда модель оборачивает ответ: { "translations": { "en": ... } }
        if (! isset($parsed[$targetCodes[0]]) && is_array($parsed['translations'] ?? null)) {
            $parsed = $parsed['translations'];
        }

        // SEO для исходного языка (ru): title / description / keywords / h1 / tags
        $sourceSeo = is_array($parsed[$sourceCode] ?? null) ? $parsed[$sourceCode] : [];
        foreach (['meta_title', 'meta_description', 'meta_h1', 'meta_keyword', 'tag'] as $seoField) {
            $seoValue = $this->cleaner->cleanPlain($this->stringifyField($sourceSeo[$seoField] ?? ''));
            if ($seoValue !== '') {
                $source[$seoField] = $seoValue;
            }
        }
        // Если meta пустые — подстрахуем из названия
        if ($source['meta_title'] === '' && $source['name'] !== '') {
            $source['meta_title'] = $source['name'];
        }
        if ($source['meta_h1'] === '' && $source['name'] !== '') {
            $source['meta_h1'] = $source['name'];
        }

        $translations = [];

        foreach ($targetCodes as $code) {
            $block = is_array($parsed[$code] ?? null) ? $parsed[$code] : [];
            $row = [];
            foreach (self::FIELDS as $field) {
                $value = $this->stringifyField($block[$field] ?? '');
                $row[$field] = $field === 'description'
                    ? $this->cleaner->clean($value)
                    : $this->cleaner->cleanPlain($value);
            }
            if ($row['meta_title'] === '' && $row['name'] !== '') {
                $row['meta_title'] = $row['name'];
            }
            if ($row['meta_h1'] === '' && $row['name'] !== '') {
                $row['meta_h1'] = $row['name'];
            }
            $row['slug'] = Str::slug($row['name'] !== '' ? $row['name'] : ($source['name'] ?: 'article'));
            $translations[$code] = $row;
        }

        return [
            'source_code' => $sourceCode,
            'source' => $source,
            'translations' => $translations,
        ];
    }

    /**
     * @param  list<string>  $targetCodes
     * @param  array<string, string>  $targetNames
     */
    private function buildInstruction(string $sourceCode, array $targetCodes, array $targetNames): string
    {
        $langs = [];
        foreach ($targetCodes as $code) {
            $langs[] = $code.' ('.($targetNames[$code] ?? $code).')';
        }
        $langsList = implode(', ', $langs);
        $allCodes = array_values(array_unique(array_merge([$sourceCode], $targetCodes)));
        $fields = implode(', ', self::FIELDS);

        return <<<PROMPT
Ты — SEO-редактор и переводчик технического блога о веб-разработке и e-commerce.

Тематика блога (держись её в формулировках title/description/keywords):
OpenCart, WordPress, WooCommerce, Laravel, PHP, модули/расширения, темы, админка, витрина, API, БД, SEO магазина, практические гайды по программированию.

Исходный язык контента: {$sourceCode}.
Нужны языки в ответе: {$this->jsonList($allCodes)}.
Перевод полного текста — на: {$langsList}.

Задача:
A) Проанализируй статью (name + description HTML).
B) Для языка "{$sourceCode}" СГЕНЕРИРУЙ SEO-поля (не копируй сырой абзац целиком).
C) Для языков {$langsList} сделай полный перевод статьи + SEO на этом языке.

Верни ТОЛЬКО валидный JSON без markdown и без пояснений.
Структура:
{
  "{$sourceCode}": {
    "meta_h1": "",
    "meta_title": "",
    "meta_description": "",
    "meta_keyword": "",
    "tag": ""
  },
  "en": {
    "name": "",
    "description": "",
    "meta_h1": "",
    "meta_title": "",
    "meta_description": "",
    "meta_keyword": "",
    "tag": ""
  },
  "uk": { ... те же поля, что у en ... }
}

Поля перевода (для en/uk): {$fields}.

Правила SEO (для КАЖДОГО языка, включая {$sourceCode}):
1) meta_title — цепкий SEO-title под сниппет Google: 50–60 символов (макс. ~65). Язык поля = язык ключа. Включи главную тему/технологию из статьи (OpenCart / WordPress / WooCommerce / Laravel / PHP и т.д.), если она есть в тексте. Без кликбейта и капса.
2) meta_description — 140–160 символов. Кратко: о чём статья + практическая польза. Без кавычек «» и без HTML. Не начинай с «В этой статье…» / «In this article…».
3) meta_h1 — человеческий заголовок страницы (можно чуть длиннее title), без HTML.
4) meta_keyword — 5–12 ключевых фраз через запятую, релевантных тексту и нише (opencart, laravel, wordpress, woocommerce, php…).
5) tag — 3–8 тегов через запятую (короткие ярлыки).

Правила перевода (en / uk):
6) description — сохрани ту же HTML-разметку (p, strong, em, ul, ol, li, h1-h6, a, br, blockquote, pre, code). Меняй только текст внутри тегов. Одна HTML-строка, не массив.
7) name — естественный перевод заголовка.
8) Не оставляй русский в en/uk. Не выдумывай факты, которых нет в тексте.
9) Тон: экспертный, ясный, для разработчиков и владельцев магазинов.
PROMPT;
    }

    /**
     * @param  mixed  $value
     */
    private function stringifyField(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_string($value)) {
            return $value;
        }
        if (is_numeric($value) || is_bool($value)) {
            return (string) $value;
        }
        if (is_array($value)) {
            $flat = [];
            array_walk_recursive($value, function ($item) use (&$flat) {
                if (is_scalar($item) || $item === null) {
                    $flat[] = (string) $item;
                }
            });

            return implode("\n", array_filter($flat, fn ($s) => trim($s) !== ''));
        }

        return '';
    }

    /**
     * @param  list<string>  $items
     */
    private function jsonList(array $items): string
    {
        return implode(', ', array_map(fn ($c) => '"'.$c.'"', $items));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $raw): array
    {
        $raw = trim($raw);
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/u', $raw, $m)) {
            $raw = trim($m[1]);
        }

        $data = json_decode($raw, true);
        if (is_array($data)) {
            return $data;
        }

        if (preg_match('/\{[\s\S]*\}/u', $raw, $m)) {
            $data = json_decode($m[0], true);
            if (is_array($data)) {
                return $data;
            }
        }

        Log::warning('[ArticleTranslateService] bad JSON: '.mb_substr($raw, 0, 400));

        throw new \RuntimeException('Не удалось разобрать ответ Gemini (ожидался JSON).');
    }
}
