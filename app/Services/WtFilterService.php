<?php

namespace App\Services;

use App\Support\CatalogCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Витринная логика WT Filter (порт с OpenCart).
 * Параметры: option_id:value,value;m:3;p:100-500;s:in
 */
class WtFilterService
{
    public const URL_KEY = 'filter_wt_filter';

    public const PART_SEP = ';';

    public const OPT_SEP = ':';

    public const VAL_SEP = ',';

    public function getSetting(string $key, mixed $default = null): mixed
    {
        if (! $this->hasTable('wt_filter_settings')) {
            return $default;
        }

        $bag = CatalogCache::remember('wtfilter.settings.bag', function () {
            return DB::table('wt_filter_settings')->pluck('value', 'key')->all();
        }, 120);

        if (! array_key_exists($key, $bag)) {
            return $default;
        }

        $row = $bag[$key];
        $json = json_decode((string) $row, true);

        return json_last_error() === JSON_ERROR_NONE ? $json : $row;
    }

    public function setSetting(string $key, mixed $value): void
    {
        $stored = is_array($value) || is_object($value)
            ? json_encode($value, JSON_UNESCAPED_UNICODE)
            : (string) $value;

        DB::table('wt_filter_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $stored]
        );
        CatalogCache::forget('wtfilter.settings.bag');
        CatalogCache::forgetFilter();
    }

    public function groupExpanded(): array
    {
        $defaults = [
            'p' => ['desktop' => 1, 'mobile' => 0],
            'm' => ['desktop' => 1, 'mobile' => 0],
            's' => ['desktop' => 1, 'mobile' => 0],
            'd' => ['desktop' => 1, 'mobile' => 0],
        ];

        $raw = $this->getSetting('group_expanded', $defaults);

        if (! is_array($raw)) {
            return $defaults;
        }

        foreach ($defaults as $key => $row) {
            if (! isset($raw[$key]) || ! is_array($raw[$key])) {
                continue;
            }
            $defaults[$key] = [
                'desktop' => ! empty($raw[$key]['desktop']) ? 1 : 0,
                'mobile' => ! empty($raw[$key]['mobile']) ? 1 : 0,
            ];
        }

        return $defaults;
    }

    public function showCounts(): bool
    {
        $v = $this->getSetting('show_counts', '1');

        return (bool) (int) $v;
    }

    public function isRange(string $string): bool
    {
        return (bool) preg_match('/^(-|)(\d+\.)?\d+?\-(-|)(\d+\.)?\d+?$/', $string);
    }

    public function rangeParts(string $string): ?array
    {
        if (! preg_match('/^((-|)(\d+\.)?\d+?)\-((-|)(\d+\.)?\d+?)$/', $string, $m)) {
            return null;
        }

        return ['from' => $m[1], 'to' => $m[4]];
    }

    /** @return array<string, list<string>> */
    public function decodeParams(?string $params): array
    {
        $out = [];

        if ($params === null || $params === '') {
            return $out;
        }

        foreach (explode(self::PART_SEP, $params) as $part) {
            $option = explode(self::OPT_SEP, $part, 2);
            if (! isset($option[1]) || $option[1] === '') {
                continue;
            }

            $key = (string) $option[0];
            $raw = (string) $option[1];

            if ($this->isRange($raw)) {
                $out[$key] = [$raw];
                continue;
            }

            $values = [];
            foreach (explode(self::VAL_SEP, $raw) as $valueId) {
                if ($valueId === '') {
                    continue;
                }
                if ($key === 's' && ($valueId === 'in' || $valueId === 'out')) {
                    $values[] = $valueId;
                } elseif ($key === 'd' && ($valueId === '1' || $valueId === 'yes')) {
                    $values[] = '1';
                } else {
                    $values[] = (string) $valueId;
                }
            }

            if ($values) {
                $out[$key] = array_values(array_unique($values));
            }
        }

        return $out;
    }

    /** @param array<string, list<string|int>> $params */
    public function encodeParams(array $params): string
    {
        $parts = [];

        foreach ($params as $optionId => $values) {
            if (! is_array($values) || $values === []) {
                continue;
            }
            $vals = array_map(static fn ($v) => (string) $v, $values);
            $parts[] = $optionId.self::OPT_SEP.implode(self::VAL_SEP, $vals);
        }

        return implode(self::PART_SEP, $parts);
    }

    /**
     * Опции категории со значениями (витрина + вкладка товара в админке).
     *
     * @return list<array<string, mixed>>
     */
    public function getOptionsByCategoryId(int $categoryId, int $languageId, bool $activeOnly = true): array
    {
        if ($categoryId < 1 || ! $this->hasTable('wt_filter_option')) {
            return [];
        }

        $cacheKey = 'wtfilter.options.'.$categoryId.'.'.$languageId.'.'.($activeOnly ? '1' : '0');

        return CatalogCache::rememberFilter($cacheKey, function () use ($categoryId, $languageId, $activeOnly) {
            return $this->loadOptionsByCategoryId($categoryId, $languageId, $activeOnly);
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadOptionsByCategoryId(int $categoryId, int $languageId, bool $activeOnly): array
    {
        $query = DB::table('wt_filter_option as oo')
            ->join('wt_filter_option_description as ood', 'oo.option_id', '=', 'ood.option_id')
            ->join('wt_filter_option_to_category as o2c', 'oo.option_id', '=', 'o2c.option_id')
            ->where('ood.language_id', $languageId)
            ->where('o2c.category_id', $categoryId)
            ->orderBy('oo.sort_order')
            ->orderBy('ood.name')
            ->select([
                'oo.option_id',
                'oo.type',
                'oo.status',
                'oo.keyword',
                'oo.image',
                'oo.color',
                'oo.expanded_desktop',
                'oo.expanded_mobile',
                'ood.name',
                'ood.postfix',
            ]);

        if ($activeOnly) {
            $query->where('oo.status', 1);
        }

        $options = $query->get();

        if ($options->isEmpty()) {
            return [];
        }

        $ids = $options->pluck('option_id')->map(fn ($id) => (int) $id)->all();

        $hasCatalogColor = $this->hasColumn('option_values', 'color');
        $catalogColorSql = $hasCatalogColor
            ? 'COALESCE(NULLIF(TRIM(oov.color), \'\'), NULLIF(TRIM(cat.color), \'\')) as color'
            : 'oov.color as color';

        $values = DB::table('wt_filter_option_value as oov')
            ->join('wt_filter_option_value_description as oovd', function ($join) use ($languageId) {
                $join->on('oov.value_id', '=', 'oovd.value_id')
                    ->where('oovd.language_id', $languageId);
            })
            ->leftJoin('option_values as cat', 'cat.id', '=', 'oov.value_id')
            ->whereIn('oov.option_id', $ids)
            ->orderBy('oov.sort_order')
            ->orderBy('oovd.name')
            ->selectRaw("
                oov.value_id,
                oov.option_id,
                COALESCE(NULLIF(TRIM(oov.image), ''), NULLIF(TRIM(cat.image), '')) as image,
                {$catalogColorSql},
                oov.value_numeric,
                oovd.name
            ")
            ->get()
            ->groupBy('option_id');

        $data = [];

        foreach ($options as $option) {
            $oid = (int) $option->option_id;
            $row = (array) $option;
            $row['option_id'] = $oid;
            $row['status'] = (int) ($option->status ?? 1);
            $row['expanded_desktop'] = (int) ($option->expanded_desktop ?? 1);
            $row['expanded_mobile'] = (int) ($option->expanded_mobile ?? 0);
            $row['values'] = [];

            foreach ($values->get($oid, collect()) as $value) {
                $row['values'][] = (array) $value;
            }

            $data[] = $row;
        }

        return $data;
    }

    /**
     * @param  array<string, list<string>>  $params
     * @return array<string, int> key = option_id.value_id | mID | sin | sout
     */
    public function getCounters(int $categoryId, array $params = []): array
    {
        if ($this->isEmptyFilter($params)) {
            return CatalogCache::rememberFilter(
                'wtfilter.counters.'.$categoryId,
                fn () => $this->computeUnfilteredCounters($categoryId)
            );
        }

        return $this->computeFilteredCounters($categoryId, $params);
    }

    private function isEmptyFilter(array $params): bool
    {
        foreach ($params as $values) {
            if (is_array($values) && $values !== []) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, int> */
    private function computeUnfilteredCounters(int $categoryId): array
    {
        $counters = [];

        foreach (DB::select('
            SELECT wfv.option_id, wfv.value_id, COUNT(*) AS total
            FROM wt_filter_option_value_to_product wfv
            INNER JOIN products p ON p.id = wfv.product_id AND p.status = 1
            WHERE wfv.category_id = ?
            GROUP BY wfv.option_id, wfv.value_id
        ', [$categoryId]) as $row) {
            $counters[$row->option_id.$row->value_id] = (int) $row->total;
        }

        foreach (DB::select('
            SELECT p.manufacturer_id AS value_id, COUNT(*) AS total
            FROM products p
            INNER JOIN category_product cp ON cp.product_id = p.id AND cp.category_id = ?
            WHERE p.status = 1 AND p.manufacturer_id IS NOT NULL AND p.manufacturer_id > 0
            GROUP BY p.manufacturer_id
        ', [$categoryId]) as $row) {
            $counters['m'.$row->value_id] = (int) $row->total;
        }

        $stock = DB::selectOne('
            SELECT
                SUM(CASE WHEN p.quantity > 0 THEN 1 ELSE 0 END) AS sin,
                SUM(CASE WHEN p.quantity <= 0 THEN 1 ELSE 0 END) AS sout
            FROM products p
            INNER JOIN category_product cp ON cp.product_id = p.id AND cp.category_id = ?
            WHERE p.status = 1
        ', [$categoryId]);
        $counters['sin'] = (int) ($stock->sin ?? 0);
        $counters['sout'] = (int) ($stock->sout ?? 0);

        return $counters;
    }

    /**
     * @param  array<string, list<string>>  $params
     * @return array<string, int>
     */
    private function computeFilteredCounters(int $categoryId, array $params): array
    {
        $counters = [];
        $baseSql = $this->productBaseSql($categoryId, $params, excludeOption: null);

        $sql = "
            SELECT wfv.option_id, wfv.value_id, COUNT(DISTINCT p.id) AS total
            FROM ({$baseSql}) p
            INNER JOIN wt_filter_option_value_to_product wfv
                ON wfv.product_id = p.id AND wfv.category_id = ?
            GROUP BY wfv.option_id, wfv.value_id
        ";

        foreach (DB::select($sql, array_merge($this->bindings, [$categoryId])) as $row) {
            $counters[$row->option_id.$row->value_id] = (int) $row->total;
        }

        foreach ($params as $optionId => $values) {
            if (in_array((string) $optionId, ['p', 'm', 's', 'd'], true) || $this->isRange((string) ($values[0] ?? ''))) {
                continue;
            }

            $subset = $params;
            unset($subset[$optionId]);
            $subSql = $this->productBaseSql($categoryId, $subset, excludeOption: null);

            $sql = "
                SELECT wfv.option_id, wfv.value_id, COUNT(DISTINCT p.id) AS total
                FROM ({$subSql}) p
                INNER JOIN wt_filter_option_value_to_product wfv
                    ON wfv.product_id = p.id AND wfv.category_id = ?
                WHERE wfv.option_id = ?
                GROUP BY wfv.option_id, wfv.value_id
            ";

            foreach (DB::select($sql, array_merge($this->bindings, [$categoryId, (int) $optionId])) as $row) {
                $counters[$row->option_id.$row->value_id] = (int) $row->total;
            }
        }

        $mSql = $this->productBaseSql($categoryId, $params, excludeOption: 'm');
        $sql = "
            SELECT p.manufacturer_id AS value_id, COUNT(DISTINCT p.id) AS total
            FROM ({$mSql}) p
            WHERE p.manufacturer_id IS NOT NULL AND p.manufacturer_id > 0
            GROUP BY p.manufacturer_id
        ";
        foreach (DB::select($sql, $this->bindings) as $row) {
            $counters['m'.$row->value_id] = (int) $row->total;
        }

        $sSql = $this->productBaseSql($categoryId, $params, excludeOption: 's');
        $sql = "
            SELECT
                SUM(CASE WHEN p.quantity > 0 THEN 1 ELSE 0 END) AS sin,
                SUM(CASE WHEN p.quantity <= 0 THEN 1 ELSE 0 END) AS sout
            FROM ({$sSql}) p
        ";
        $stock = DB::selectOne($sql, $this->bindings);
        $counters['sin'] = (int) ($stock->sin ?? 0);
        $counters['sout'] = (int) ($stock->sout ?? 0);

        return $counters;
    }

    /** @param  array<string, list<string>>  $params */
    public function getTotalProducts(int $categoryId, array $params = []): int
    {
        if ($this->isEmptyFilter($params)) {
            return (int) CatalogCache::rememberFilter(
                'wtfilter.total.'.$categoryId,
                fn () => (int) DB::table('category_product as cp')
                    ->join('products as p', 'p.id', '=', 'cp.product_id')
                    ->where('cp.category_id', $categoryId)
                    ->where('p.status', true)
                    ->count()
            );
        }

        $sql = $this->productBaseSql($categoryId, $params);
        $row = DB::selectOne("SELECT COUNT(*) AS total FROM ({$sql}) x", $this->bindings);

        return (int) ($row->total ?? 0);
    }

    /**
     * @param  array<string, list<string>>  $params
     * @return list<int>
     */
    public function getProductIds(int $categoryId, array $params, int $start, int $limit): array
    {
        $sql = $this->productBaseSql($categoryId, $params);
        $sql = "SELECT p.id FROM ({$sql}) p ORDER BY p.id DESC LIMIT ".(int) $limit.' OFFSET '.(int) $start;

        return array_map(
            static fn ($row) => (int) $row->id,
            DB::select($sql, $this->bindings)
        );
    }

    /** @param  array<string, list<string>>  $params */
    public function getPriceLimits(int $categoryId, array $params = []): array
    {
        $subset = $params;
        unset($subset['p']);

        if ($this->isEmptyFilter($subset)) {
            return CatalogCache::rememberFilter(
                'wtfilter.prices.'.$categoryId,
                function () use ($categoryId) {
                    $row = DB::selectOne('
                        SELECT MIN(p.price) AS min_price, MAX(p.price) AS max_price
                        FROM products p
                        INNER JOIN category_product cp ON cp.product_id = p.id AND cp.category_id = ?
                        WHERE p.status = 1
                    ', [$categoryId]);

                    return [
                        'min' => (float) ($row->min_price ?? 0),
                        'max' => (float) ($row->max_price ?? 0),
                    ];
                }
            );
        }

        $sql = $this->productBaseSql($categoryId, $subset);
        $row = DB::selectOne("SELECT MIN(p.price) AS min_price, MAX(p.price) AS max_price FROM ({$sql}) p", $this->bindings);

        return [
            'min' => (float) ($row->min_price ?? 0),
            'max' => (float) ($row->max_price ?? 0),
        ];
    }

    /** @return list<array{value_id:int,name:string}> */
    public function getManufacturersByCategoryId(int $categoryId): array
    {
        return CatalogCache::rememberFilter('wtfilter.manufacturers.'.$categoryId, function () use ($categoryId) {
            $rows = DB::table('products as p')
                ->join('category_product as cp', 'cp.product_id', '=', 'p.id')
                ->join('manufacturers as m', 'm.id', '=', 'p.manufacturer_id')
                ->where('cp.category_id', $categoryId)
                ->where('p.status', 1)
                ->where('p.manufacturer_id', '>', 0)
                ->groupBy('m.id', 'm.name')
                ->orderBy('m.name')
                ->select(['m.id as value_id', 'm.name'])
                ->get();

            return $rows->map(fn ($r) => [
                'value_id' => (int) $r->value_id,
                'name' => (string) $r->name,
            ])->all();
        });
    }

    /** @var list<mixed> */
    private array $bindings = [];

    /**
     * Базовый SELECT id товаров категории с учётом фильтра.
     *
     * @param  array<string, list<string>>  $params
     */
    private function productBaseSql(int $categoryId, array $params, ?string $excludeOption = null): string
    {
        $joinBindings = [];
        $whereBindings = [];
        $conditions = ['p.status = 1'];
        $joins = [];
        $i = 0;

        if ($excludeOption !== null) {
            unset($params[$excludeOption]);
        }

        // Цена — плейсхолдеры в WHERE, после JOIN.
        if (! empty($params['p'][0]) && $this->isRange((string) $params['p'][0])) {
            $range = $this->rangeParts((string) $params['p'][0]);
            if ($range) {
                $conditions[] = 'p.price >= ? AND p.price <= ?';
                $whereBindings[] = (float) $range['from'];
                $whereBindings[] = (float) $range['to'];
            }
            unset($params['p']);
        }

        // Производитель — тоже WHERE, после JOIN по опциям.
        if (! empty($params['m'])) {
            $ids = array_values(array_filter(array_map('intval', $params['m'])));
            if ($ids) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $conditions[] = "p.manufacturer_id IN ({$placeholders})";
                array_push($whereBindings, ...$ids);
            }
            unset($params['m']);
        }

        // Наличие
        if (! empty($params['s'])) {
            $hasIn = in_array('in', $params['s'], true);
            $hasOut = in_array('out', $params['s'], true);
            if ($hasIn && ! $hasOut) {
                $conditions[] = 'p.quantity > 0';
            } elseif ($hasOut && ! $hasIn) {
                $conditions[] = 'p.quantity <= 0';
            }
            unset($params['s']);
        }

        unset($params['d']);

        foreach ($params as $optionId => $values) {
            if (! ctype_digit((string) $optionId) || $values === []) {
                continue;
            }

            if ($this->isRange((string) $values[0])) {
                continue;
            }

            $alias = 'wfv'.$i++;
            $ids = array_values(array_filter(array_map('intval', $values)));
            if (! $ids) {
                continue;
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $joins[] = "INNER JOIN wt_filter_option_value_to_product {$alias}
                ON {$alias}.product_id = p.id
                AND {$alias}.category_id = ?
                AND {$alias}.option_id = ?
                AND {$alias}.value_id IN ({$placeholders})";

            $joinBindings[] = $categoryId;
            $joinBindings[] = (int) $optionId;
            array_push($joinBindings, ...$ids);
        }

        $joinSql = $joins ? "\n".implode("\n", $joins) : '';
        $whereSql = implode(' AND ', $conditions);
        $select = $joins ? 'SELECT DISTINCT p.id, p.price, p.quantity, p.manufacturer_id' : 'SELECT p.id, p.price, p.quantity, p.manufacturer_id';

        // Порядок ?: cp.category_id, затем JOIN, затем WHERE (цена/бренд).
        $this->bindings = array_merge([$categoryId], $joinBindings, $whereBindings);

        return "
            {$select}
            FROM products p
            INNER JOIN category_product cp ON cp.product_id = p.id AND cp.category_id = ?
            {$joinSql}
            WHERE {$whereSql}
        ";
    }

    /**
     * Выбранные значения фильтра у товара: [option_id][value_id] => row.
     *
     * @return array<int, array<string, object>>
     */
    public function getProductValues(int $productId): array
    {
        if ($productId < 1 || ! $this->hasTable('wt_filter_option_value_to_product')) {
            return [];
        }

        $rows = DB::table('wt_filter_option_value_to_product')
            ->where('product_id', $productId)
            ->get();

        $data = [];
        foreach ($rows as $row) {
            $data[(int) $row->option_id][(string) $row->value_id] = $row;
        }

        return $data;
    }

    /**
     * Сохранить значения с вкладки товара (ручной override).
     *
     * @param  array{wt_filter_product_option?: array, category_ids?: array}  $data
     */
    public function saveProductFilterValues(int $productId, array $data): void
    {
        if ($productId < 1 || ! $this->hasTable('wt_filter_option_value_to_product')) {
            return;
        }

        DB::table('wt_filter_option_value_to_product')->where('product_id', $productId)->delete();

        $options = $data['wt_filter_product_option'] ?? null;
        if (! is_array($options) || $options === []) {
            return;
        }

        $categories = [];
        foreach ((array) ($data['category_ids'] ?? []) as $categoryId) {
            $categoryId = (int) $categoryId;
            if ($categoryId > 0) {
                $categories[$categoryId] = $categoryId;
            }
        }
        if ($categories === []) {
            $categories = [0];
        }

        $rows = [];
        foreach ($options as $optionId => $payload) {
            $optionId = (int) $optionId;
            if ($optionId < 1 || empty($payload['values']) || ! is_array($payload['values'])) {
                continue;
            }

            foreach ($payload['values'] as $valueId => $value) {
                if (! is_array($value) || ! array_key_exists('selected', $value)) {
                    continue;
                }

                $slideMin = isset($value['slide_value_min']) ? (float) $value['slide_value_min'] : 0.0;
                $slideMax = isset($value['slide_value_max']) ? (float) $value['slide_value_max'] : 0.0;

                foreach ($categories as $categoryId) {
                    $rows[] = [
                        'product_id' => $productId,
                        'option_id' => $optionId,
                        'value_id' => (string) $valueId,
                        'category_id' => (int) $categoryId,
                        'slide_value_min' => $slideMin,
                        'slide_value_max' => $slideMax,
                    ];
                }
            }
        }

        if ($rows !== []) {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('wt_filter_option_value_to_product')->insert($chunk);
            }
        }

        CatalogCache::forgetFilter();
    }

    public function deleteProductFromIndex(int $productId): void
    {
        if ($productId < 1 || ! $this->hasTable('wt_filter_option_value_to_product')) {
            return;
        }

        DB::table('wt_filter_option_value_to_product')->where('product_id', $productId)->delete();
        CatalogCache::forgetFilter();
    }

    private function hasTable(string $table): bool
    {
        static $tables = [];

        if (! array_key_exists($table, $tables)) {
            $tables[$table] = Schema::hasTable($table);
        }

        return $tables[$table];
    }

    private function hasColumn(string $table, string $column): bool
    {
        static $columns = [];
        $key = $table.'.'.$column;
        if (! array_key_exists($key, $columns)) {
            $columns[$key] = Schema::hasColumn($table, $column);
        }

        return $columns[$key];
    }
}
