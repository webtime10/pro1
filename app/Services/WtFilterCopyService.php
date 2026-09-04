<?php

namespace App\Services;

use App\Models\Language;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Копирование источников в wt_filter_* (как OpenCart wt_filter copyFiltersStep).
 *
 * Источники (чекбоксы):
 * - option    → options / option_values (option_id без смещения)
 * - filter    → filter_groups / filters (+10000)
 * - attribute → attributes / product_attributes (+30000)
 */
class WtFilterCopyService
{
    /** option_id для стандартных OC-фильтров */
    public const FILTER_OPTION_ID_OFFSET = 10000;

    /** value_id для стандартных OC-фильтров */
    public const FILTER_VALUE_ID_OFFSET = 10000;

    /** option_id для атрибутов */
    public const OPTION_ID_OFFSET = 30000;

    private const KEYWORD_BATCH_SIZE = 200;

    /**
     * @param  array{
     *   copy_type?: string,
     *   attribute_separator?: string,
     *   copy_truncate?: bool,
     *   copy_store?: array<int|string>
     * }  $data
     * @return array{success: bool, step: string, message: string, complete?: bool, error?: string}
     */
    public function runStep(string $step, array $data = []): array
    {
        $messages = [
            'truncate'  => 'Очистка существующих фильтров...',
            'option'    => 'Копирование опций товаров...',
            'filter'    => 'Копирование стандартных фильтров...',
            'attribute' => 'Копирование атрибутов...',
            'finalize'  => 'Привязка категорий и keyword...',
        ];

        try {
            match ($step) {
                'truncate'  => $this->truncate(),
                'option'    => $this->copyOptions($data),
                'filter'    => $this->copyFilters($data),
                'attribute' => $this->copyAttributes($data),
                'finalize'  => $this->finalize($data),
                default     => throw new \InvalidArgumentException('Неизвестный шаг копирования.'),
            };
        } catch (Throwable $e) {
            return [
                'success' => false,
                'step'    => $step,
                'error'   => $e->getMessage(),
                'message' => $e->getMessage(),
            ];
        }

        $result = [
            'success' => true,
            'step'    => $step,
            'message' => $messages[$step] ?? $step,
        ];

        if ($step === 'finalize') {
            $result['complete'] = true;
            $result['message'] = 'Готово';
        }

        return $result;
    }

    public function truncate(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('wt_filter_option')->truncate();
        DB::table('wt_filter_option_description')->truncate();
        DB::table('wt_filter_option_to_category')->truncate();
        DB::table('wt_filter_option_to_store')->truncate();
        DB::table('wt_filter_option_value')->truncate();
        DB::table('wt_filter_option_value_description')->truncate();
        DB::table('wt_filter_option_value_to_product')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Опции товаров → wt_filter_* (option_id = options.id).
     *
     * @param  array{copy_type?: string}  $data
     */
    public function copyOptions(array $data): void
    {
        $copyType = ! empty($data['copy_type']) ? (string) $data['copy_type'] : 'checkbox';

        DB::insert('
            INSERT INTO wt_filter_option (option_id, type, status, sort_order, image)
            SELECT id, ?, 1, sort_order, IF(type = \'image\', 1, 0)
            FROM options
            ON DUPLICATE KEY UPDATE
                sort_order = VALUES(sort_order)
        ', [$copyType]);

        DB::insert('
            INSERT INTO wt_filter_option_description (option_id, language_id, name, description)
            SELECT option_id, language_id, name, \'\'
            FROM option_descriptions
            ON DUPLICATE KEY UPDATE name = VALUES(name)
        ');

        $optionHasColor = Schema::hasColumn('option_values', 'color');

        if ($optionHasColor) {
            DB::insert('
                INSERT INTO wt_filter_option_value (value_id, option_id, image, color, sort_order)
                SELECT id, option_id, IFNULL(image, \'\'), IFNULL(color, \'\'), sort_order
                FROM option_values
                ON DUPLICATE KEY UPDATE
                    option_id = VALUES(option_id),
                    image = IF(wt_filter_option_value.image = \'\', VALUES(image), wt_filter_option_value.image),
                    color = IF(wt_filter_option_value.color = \'\', VALUES(color), wt_filter_option_value.color),
                    sort_order = VALUES(sort_order)
            ');

            DB::update('
                UPDATE wt_filter_option fo
                SET fo.color = 1
                WHERE EXISTS (
                    SELECT 1 FROM wt_filter_option_value ov
                    WHERE ov.option_id = fo.option_id AND ov.color <> \'\'
                )
            ');
        } else {
            DB::insert('
                INSERT INTO wt_filter_option_value (value_id, option_id, image, sort_order)
                SELECT id, option_id, IFNULL(image, \'\'), sort_order
                FROM option_values
                ON DUPLICATE KEY UPDATE
                    option_id = VALUES(option_id),
                    image = IF(wt_filter_option_value.image = \'\', VALUES(image), wt_filter_option_value.image),
                    sort_order = VALUES(sort_order)
            ');
        }

        DB::insert('
            INSERT INTO wt_filter_option_value_description (value_id, option_id, language_id, name)
            SELECT option_value_id, option_id, language_id, name
            FROM option_value_descriptions
            ON DUPLICATE KEY UPDATE
                option_id = VALUES(option_id),
                name = VALUES(name)
        ');

        // Снести старые связи только для option_id, которые есть в options
        DB::delete('
            DELETE oov2p FROM wt_filter_option_value_to_product oov2p
            INNER JOIN options o ON o.id = oov2p.option_id
        ');

        DB::insert('
            INSERT INTO wt_filter_option_value_to_product
                (product_id, value_id, option_id, category_id, slide_value_min, slide_value_max)
            SELECT pov.product_id, pov.option_value_id, pov.option_id, cp.category_id, 0, 0
            FROM product_option_values pov
            INNER JOIN category_product cp ON cp.product_id = pov.product_id
            WHERE pov.quantity > 0 AND cp.category_id > 0
            ON DUPLICATE KEY UPDATE
                slide_value_min = VALUES(slide_value_min),
                slide_value_max = VALUES(slide_value_max)
        ');
    }

    /**
     * Стандартные фильтры OC → wt_filter_* (option_id/value_id +10000).
     *
     * @param  array{copy_type?: string}  $data
     */
    public function copyFilters(array $data): void
    {
        if (! Schema::hasTable('filter_groups') || ! Schema::hasTable('filters') || ! Schema::hasTable('product_filters')) {
            return;
        }

        $copyType = ! empty($data['copy_type']) ? (string) $data['copy_type'] : 'checkbox';
        $optOffset = self::FILTER_OPTION_ID_OFFSET;
        $valOffset = self::FILTER_VALUE_ID_OFFSET;

        DB::insert('
            INSERT INTO wt_filter_option (option_id, type, status, sort_order)
            SELECT (id + ?), ?, 1, sort_order
            FROM filter_groups
            ON DUPLICATE KEY UPDATE
                sort_order = VALUES(sort_order)
        ', [$optOffset, $copyType]);

        DB::insert('
            INSERT INTO wt_filter_option_description (option_id, language_id, name, description)
            SELECT (filter_group_id + ?), language_id, name, \'\'
            FROM filter_group_descriptions
            ON DUPLICATE KEY UPDATE name = VALUES(name)
        ', [$optOffset]);

        DB::insert('
            INSERT INTO wt_filter_option_value (value_id, option_id, sort_order)
            SELECT (id + ?), (filter_group_id + ?), sort_order
            FROM filters
            ON DUPLICATE KEY UPDATE
                option_id = VALUES(option_id),
                sort_order = VALUES(sort_order)
        ', [$valOffset, $optOffset]);

        DB::insert('
            INSERT INTO wt_filter_option_value_description (value_id, option_id, language_id, name)
            SELECT (filter_id + ?), (filter_group_id + ?), language_id, name
            FROM filter_descriptions
            ON DUPLICATE KEY UPDATE
                option_id = VALUES(option_id),
                name = VALUES(name)
        ', [$valOffset, $optOffset]);

        DB::delete('
            DELETE FROM wt_filter_option_value_to_product
            WHERE option_id >= ? AND option_id < ?
        ', [$optOffset, self::OPTION_ID_OFFSET]);

        DB::insert('
            INSERT INTO wt_filter_option_value_to_product
                (product_id, value_id, option_id, category_id, slide_value_min, slide_value_max)
            SELECT
                pf.product_id,
                (pf.filter_id + ?),
                (f.filter_group_id + ?),
                cp.category_id,
                0, 0
            FROM product_filters pf
            INNER JOIN filters f ON f.id = pf.filter_id
            INNER JOIN category_product cp ON cp.product_id = pf.product_id
            WHERE cp.category_id > 0
            ON DUPLICATE KEY UPDATE
                slide_value_min = VALUES(slide_value_min),
                slide_value_max = VALUES(slide_value_max)
        ', [$valOffset, $optOffset]);
    }

    /**
     * @param  array{copy_type?: string, attribute_separator?: string}  $data
     */
    public function copyAttributes(array $data): void
    {
        $copyType = ! empty($data['copy_type']) ? (string) $data['copy_type'] : 'checkbox';
        $languageId = (int) (Language::getDefault()?->id ?? 0);

        if ($languageId < 1) {
            throw new \RuntimeException('Не задан язык по умолчанию.');
        }

        $previousSqlMode = DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode ?? '';
        DB::statement("SET SESSION sql_mode = ''");

        try {
            DB::update('UPDATE product_attributes SET text = TRIM(text)');

            $offset = self::OPTION_ID_OFFSET;
            $normalize = "CONCAT(UCASE(LEFT(TRIM(text), 1)), LCASE(SUBSTRING(TRIM(text), 2)))";

            /*
             * value_id: вместо CRC32 — SHA1 → decimal (меньше коллизий).
             * Ключ: attribute_id + нормализованный текст.
             */
            $valueIdExpr = "CONV(SUBSTRING(SHA1(CONCAT(attribute_id, ':', {$normalize})), 1, 15), 16, 10)";
            $slideExpr = "IF(TRIM(text) REGEXP '^-?[0-9]+([.,][0-9]+)?$', CAST(REPLACE(TRIM(text), ',', '.') AS DECIMAL(15,4)), 0)";

            DB::insert('
                INSERT INTO wt_filter_option (option_id, status, type, sort_order)
                SELECT (id + ?), 1, ?, sort_order
                FROM attributes
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    type = VALUES(type),
                    sort_order = VALUES(sort_order)
            ', [$offset, $copyType]);

            DB::insert('
                INSERT INTO wt_filter_option_description (option_id, language_id, name, description)
                SELECT (attribute_id + ?), language_id, name, \'\'
                FROM attribute_descriptions
                ON DUPLICATE KEY UPDATE name = VALUES(name)
            ', [$offset]);

            DB::insert("
                INSERT INTO wt_filter_option_value (option_id, value_id)
                SELECT (attribute_id + ?), {$valueIdExpr}
                FROM product_attributes
                WHERE language_id = ?
                GROUP BY attribute_id, {$normalize}
                ON DUPLICATE KEY UPDATE option_id = VALUES(option_id)
            ", [$offset, $languageId]);

            DB::insert("
                INSERT INTO wt_filter_option_value_description (option_id, value_id, language_id, name)
                SELECT (attribute_id + ?), {$valueIdExpr}, language_id, TRIM(text)
                FROM product_attributes
                WHERE language_id = ?
                GROUP BY attribute_id, {$normalize}
                ON DUPLICATE KEY UPDATE
                    option_id = VALUES(option_id),
                    name = VALUES(name)
            ", [$offset, $languageId]);

            $languages = Language::forAdminForms();

            foreach ($languages as $language) {
                if ((int) $language->id === $languageId) {
                    continue;
                }

                $normPa = "CONCAT(UCASE(LEFT(TRIM(pa.text), 1)), LCASE(SUBSTRING(TRIM(pa.text), 2)))";
                $normPa2 = "CONCAT(UCASE(LEFT(TRIM(pa2.text), 1)), LCASE(SUBSTRING(TRIM(pa2.text), 2)))";
                $valueIdPa2 = "CONV(SUBSTRING(SHA1(CONCAT(pa2.attribute_id, ':', {$normPa2})), 1, 15), 16, 10)";

                DB::insert("
                    INSERT INTO wt_filter_option_value_description (option_id, value_id, language_id, name)
                    SELECT
                        (pa.attribute_id + ?),
                        {$valueIdPa2} AS value_id,
                        ?,
                        {$normPa}
                    FROM product_attributes pa
                    INNER JOIN product_attributes pa2 ON (
                        pa2.product_id = pa.product_id
                        AND pa2.attribute_id = pa.attribute_id
                        AND pa2.language_id = ?
                    )
                    WHERE pa.language_id = ?
                    GROUP BY pa.attribute_id, {$normPa}
                    ON DUPLICATE KEY UPDATE name = VALUES(name)
                ", [$offset, (int) $language->id, $languageId, (int) $language->id]);
            }

            // Пересборка связей атрибутов (option_id >= 30000) + slide_value_* + category_id
            DB::delete('DELETE FROM wt_filter_option_value_to_product WHERE option_id >= ?', [$offset]);

            DB::insert("
                INSERT INTO wt_filter_option_value_to_product
                    (product_id, option_id, value_id, category_id, slide_value_min, slide_value_max)
                SELECT
                    pa.product_id,
                    (pa.attribute_id + ?),
                    {$valueIdExpr} AS value_id,
                    cp.category_id,
                    {$slideExpr} AS slide_value_min,
                    {$slideExpr} AS slide_value_max
                FROM product_attributes pa
                INNER JOIN category_product cp ON cp.product_id = pa.product_id
                WHERE pa.language_id = ?
                  AND cp.category_id > 0
                ON DUPLICATE KEY UPDATE
                    slide_value_min = VALUES(slide_value_min),
                    slide_value_max = VALUES(slide_value_max)
            ", [$offset, $languageId]);

            $this->splitBySeparator((string) ($data['attribute_separator'] ?? ''), $languageId);
        } finally {
            DB::statement('SET SESSION sql_mode = ?', [$previousSqlMode]);
        }
    }

    /**
     * @param  array{copy_store?: array<int|string>}  $data
     */
    public function finalize(array $data = []): void
    {
        $languageId = (int) (Language::getDefault()?->id ?? 0);

        DB::insert('
            INSERT INTO wt_filter_option_to_category (option_id, category_id)
            SELECT oov2p.option_id, cp.category_id
            FROM wt_filter_option_value_to_product oov2p
            LEFT JOIN category_product cp ON cp.product_id = oov2p.product_id
            WHERE cp.category_id IS NOT NULL AND cp.category_id != 0
            GROUP BY oov2p.option_id, cp.category_id
            ON DUPLICATE KEY UPDATE category_id = VALUES(category_id)
        ');

        $stores = ! empty($data['copy_store']) && is_array($data['copy_store'])
            ? $data['copy_store']
            : [0];

        foreach ($stores as $storeId) {
            DB::insert('
                INSERT INTO wt_filter_option_to_store (option_id, store_id)
                SELECT option_id, ?
                FROM wt_filter_option
                ON DUPLICATE KEY UPDATE store_id = VALUES(store_id)
            ', [(int) $storeId]);
        }

        if ($languageId > 0) {
            $this->generateKeywordsBatched($languageId);
        }
    }

    private function generateKeywordsBatched(int $languageId): void
    {
        $options = DB::select('
            SELECT oo.option_id, ood.name
            FROM wt_filter_option oo
            LEFT JOIN wt_filter_option_description ood ON oo.option_id = ood.option_id
            WHERE ood.language_id = ? AND (oo.keyword = \'\' OR oo.keyword IS NULL)
        ', [$languageId]);

        $this->batchUpdateKeywords('wt_filter_option', 'option_id', $options);

        $values = DB::select('
            SELECT oov.value_id, oovd.name
            FROM wt_filter_option_value oov
            LEFT JOIN wt_filter_option_value_description oovd ON oov.value_id = oovd.value_id
            WHERE oovd.language_id = ? AND (oov.keyword = \'\' OR oov.keyword IS NULL)
        ', [$languageId]);

        $this->batchUpdateKeywords('wt_filter_option_value', 'value_id', $values);
    }

    /**
     * @param  list<object{option_id?: mixed, value_id?: mixed, name: mixed}>  $rows
     */
    private function batchUpdateKeywords(string $table, string $pkColumn, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        foreach (array_chunk($rows, self::KEYWORD_BATCH_SIZE) as $chunk) {
            $cases = [];
            $ids = [];
            $caseBindings = [];
            $idBindings = [];

            foreach ($chunk as $row) {
                $id = $pkColumn === 'option_id' ? (int) $row->option_id : $row->value_id;
                $keyword = $this->translit((string) $row->name);

                if ($keyword === '') {
                    continue;
                }

                $cases[] = 'WHEN ? THEN ?';
                $ids[] = '?';
                $caseBindings[] = $id;
                $caseBindings[] = $keyword;
                $idBindings[] = $id;
            }

            if ($cases === []) {
                continue;
            }

            $sql = "UPDATE `{$table}` SET `keyword` = CASE `{$pkColumn}` "
                .implode(' ', $cases)
                ." ELSE `keyword` END WHERE `{$pkColumn}` IN (".implode(',', $ids).')';

            DB::update($sql, array_merge($caseBindings, $idBindings));
        }
    }

    private function splitBySeparator(string $separator, int $languageId): void
    {
        if ($separator === '') {
            return;
        }

        $rows = DB::select('
            SELECT *
            FROM wt_filter_option_value_description
            WHERE language_id = ?
              AND TRIM(name) LIKE ?
        ', [$languageId, '%'.$separator.'%']);

        foreach ($rows as $result) {
            // Части составного значения по всем языкам (индекс explode должен совпадать)
            $langRows = DB::select('
                SELECT language_id, name
                FROM wt_filter_option_value_description
                WHERE value_id = ?
            ', [$result->value_id]);

            $partsByLang = [];

            foreach ($langRows as $row) {
                $partsByLang[(int) $row->language_id] = explode($separator, (string) $row->name);
            }

            $parts = $partsByLang[$languageId] ?? explode($separator, (string) $result->name);

            foreach ($parts as $index => $part) {
                $value = $this->ucfirstUtf8(trim($part));

                if ($value === '') {
                    continue;
                }

                $slide = $this->parseSlideNumber($value);

                $existing = DB::selectOne('
                    SELECT value_id
                    FROM wt_filter_option_value_description
                    WHERE language_id = ?
                      AND option_id = ?
                      AND LOWER(TRIM(name)) = ?
                    LIMIT 1
                ', [$languageId, (int) $result->option_id, mb_strtolower($value, 'UTF-8')]);

                if ($existing) {
                    $valueId = $existing->value_id;
                } else {
                    $valueId = DB::table('wt_filter_option_value')->insertGetId([
                        'option_id' => (int) $result->option_id,
                    ], 'value_id');
                }

                // Описания атомарного значения для всех языков (по тому же индексу части)
                foreach ($partsByLang as $langId => $langParts) {
                    $partName = isset($langParts[$index])
                        ? $this->ucfirstUtf8(trim($langParts[$index]))
                        : $value;

                    if ($partName === '') {
                        $partName = $value;
                    }

                    DB::insert('
                        INSERT INTO wt_filter_option_value_description
                            (option_id, value_id, language_id, name)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            option_id = VALUES(option_id),
                            name = VALUES(name)
                    ', [
                        (int) $result->option_id,
                        $valueId,
                        (int) $langId,
                        $partName,
                    ]);
                }

                DB::insert('
                    INSERT INTO wt_filter_option_value_to_product
                        (product_id, option_id, value_id, category_id, slide_value_min, slide_value_max)
                    SELECT oov2p.product_id, ?, ?, oov2p.category_id, ?, ?
                    FROM wt_filter_option_value_to_product oov2p
                    WHERE oov2p.option_id = ? AND oov2p.value_id = ?
                    ON DUPLICATE KEY UPDATE
                        slide_value_min = VALUES(slide_value_min),
                        slide_value_max = VALUES(slide_value_max)
                ', [
                    (int) $result->option_id,
                    $valueId,
                    $slide,
                    $slide,
                    (int) $result->option_id,
                    $result->value_id,
                ]);
            }

            // Удалить исходное составное значение целиком (все языки description + связи)
            if ($parts) {
                DB::table('wt_filter_option_value')->where('value_id', $result->value_id)->delete();
                DB::table('wt_filter_option_value_description')
                    ->where('value_id', $result->value_id)
                    ->delete();
                DB::table('wt_filter_option_value_to_product')
                    ->where('option_id', (int) $result->option_id)
                    ->where('value_id', $result->value_id)
                    ->delete();
            }
        }
    }

    private function parseSlideNumber(string $text): float
    {
        $text = str_replace(',', '.', $text);

        if (preg_match('/-?\d+(?:\.\d+)?/', $text, $m)) {
            return (float) $m[0];
        }

        return 0.0;
    }

    /**
     * Транслит названия → ЧПУ-keyword (RU + UA → латиница).
     */
    public function translit(string $string): string
    {
        $replace = [
            // Общая кириллица (RU / UA)
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y',
            'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
            'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh',
            'щ' => 'shch', 'ь' => '', 'ю' => 'yu', 'я' => 'ya',

            // Украинские
            'ґ' => 'g', 'є' => 'ye', 'і' => 'i', 'ї' => 'yi',

            // Русские
            'ё' => 'yo', 'ъ' => '', 'ы' => 'y', 'э' => 'e',

            // Апострофы (в т.ч. украинский U+02BC)
            "'" => '', '’' => '', '`' => '', 'ʼ' => '',

            // Разделители
            ' ' => '-', '+' => 'plus',
        ];

        $string = mb_strtolower($string, 'UTF-8');
        $string = strtr($string, $replace);
        $string = (string) preg_replace('![^a-z0-9]+!iu', '-', $string);
        $string = (string) preg_replace('!-{2,}!', '-', $string);

        return trim($string, '-');
    }

    private function ucfirstUtf8(string $str): string
    {
        if ($str === '') {
            return '';
        }

        return mb_strtoupper(mb_substr($str, 0, 1, 'UTF-8'), 'UTF-8').mb_substr($str, 1, null, 'UTF-8');
    }
}
