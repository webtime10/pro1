<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use App\Services\WtFilterService;
use App\Support\CatalogLocale;
use App\Support\CatalogUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class WtFilterController extends Controller
{
    private const PER_PAGE = 24;

    public function __construct(private WtFilterService $filter) {}

    /** AJAX: счётчики + total. */
    public function callback(Request $request): JsonResponse
    {
        $lang = CatalogLocale::current();
        $category = $this->resolveCategory($request);
        if (! $category) {
            return response()->json(['success' => false], 404);
        }

        $params = $this->filter->decodeParams((string) $request->input(WtFilterService::URL_KEY, ''));
        $showCounts = $this->filter->showCounts();
        $counters = $showCounts ? $this->filter->getCounters((int) $category->id, $params) : [];
        $total = $this->filter->getTotalProducts((int) $category->id, $params);

        return response()->json([
            'success' => true,
            'total' => $total,
            'text_total' => $this->totalText($total),
            'values' => $counters,
            'href' => $this->categoryFilterUrl($category, $lang, $params),
        ]);
    }

    /** AJAX: HTML товаров + пагинация. */
    public function refresh(Request $request): JsonResponse
    {
        $lang = CatalogLocale::current();
        $category = $this->resolveCategory($request);
        if (! $category) {
            return response()->json(['success' => false], 404);
        }

        $category->loadMissing([
            'descriptions' => fn ($q) => $q->where('language_id', $lang->id),
            'parent.descriptions' => fn ($q) => $q->where('language_id', $lang->id),
        ]);

        $params = $this->filter->decodeParams((string) $request->input(WtFilterService::URL_KEY, ''));
        $page = max(1, (int) $request->input('page', 1));
        $limit = max(1, min(100, (int) $request->input('limit', self::PER_PAGE)));
        $start = ($page - 1) * $limit;

        $total = $this->filter->getTotalProducts((int) $category->id, $params);
        $ids = $this->filter->getProductIds((int) $category->id, $params, $start, $limit);

        $items = $ids === []
            ? collect()
            : Product::catalogCardsByIds($ids, (int) $lang->id);

        $products = new LengthAwarePaginator($items, $total, $limit, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
            'query' => $request->query(),
        ]);

        $encoded = $this->filter->encodeParams($params);
        $href = $this->categoryFilterUrl($category, $lang, $params);

        return response()->json([
            'success' => true,
            'total' => $total,
            'text_total' => $this->totalText($total),
            'products' => view('catalog.partials.product-grid', [
                'products' => $products,
                'lang' => $lang,
                'category' => $category,
            ])->render(),
            'pagination' => (string) $products->onEachSide(2)->withQueryString()->links('pagination::bootstrap-4'),
            'href' => $href,
            WtFilterService::URL_KEY => $encoded,
            'values' => $this->filter->showCounts()
                ? $this->filter->getCounters((int) $category->id, $params)
                : [],
        ]);
    }

    /**
     * Данные модуля фильтра для категории.
     *
     * @return array<string, mixed>
     */
    public function buildModuleData(Category $category, Language $lang, Request $request): array
    {
        $params = $this->filter->decodeParams((string) $request->query(WtFilterService::URL_KEY, ''));
        $showCounts = $this->filter->showCounts();
        $counters = $showCounts ? $this->filter->getCounters((int) $category->id, $params) : [];
        $optionsRaw = $this->filter->getOptionsByCategoryId((int) $category->id, (int) $lang->id);
        $priceLimits = $this->filter->getPriceLimits((int) $category->id, $params);
        $groupExpanded = $this->filter->groupExpanded();

        $min = (int) floor($priceLimits['min']);
        $max = (int) ceil($priceLimits['max']);
        $from = $min;
        $to = $max;

        if (! empty($params['p'][0]) && $this->filter->isRange((string) $params['p'][0])) {
            $range = $this->filter->rangeParts((string) $params['p'][0]);
            if ($range) {
                $from = (int) floor((float) $range['from']);
                $to = (int) ceil((float) $range['to']);
            }
        }

        $options = [];
        foreach ($optionsRaw as $option) {
            $values = [];
            foreach ($option['values'] as $value) {
                $key = $option['option_id'].$value['value_id'];
                $count = $counters[$key] ?? 0;
                $selected = ! empty($params[$option['option_id']])
                    && in_array((string) $value['value_id'], array_map('strval', $params[$option['option_id']]), true);

                if ($showCounts && ! $selected && $count < 1) {
                    continue;
                }

                $swatch = catalog_option_swatch(
                    (string) ($value['color'] ?? ''),
                    (string) ($value['image'] ?? ''),
                    (string) ($value['name'] ?? '')
                );
                $color = $swatch['color'];
                $thumb = $swatch['image'];

                $values[] = [
                    'value_id' => $value['value_id'],
                    'name' => $value['name'].($option['postfix'] ?? ''),
                    'count' => $count,
                    'selected' => $selected,
                    'disabled' => $showCounts && ! $selected && $count < 1,
                    'image' => $thumb,
                    'color' => $color,
                ];
            }

            if ($values === [] && ! in_array($option['type'], ['slider_range', 'slider_single', 'slide', 'slide_dual'], true)) {
                continue;
            }

            $type = (string) $option['type'];
            $hasImages = collect($values)->contains(fn ($v) => $v['image'] !== '');
            $hasColors = collect($values)->contains(fn ($v) => $v['color'] !== '');
            $asSwatches = $hasImages || $hasColors;
            if ($asSwatches && $type === 'checkbox') {
                $type = 'checkbox_image';
            } elseif ($asSwatches && $type === 'radio') {
                $type = 'radio_image';
            } elseif ($asSwatches && $type === 'select') {
                $type = 'checkbox_image';
            } elseif (! $asSwatches && $type === 'radio_image') {
                $type = 'radio';
            } elseif (! $asSwatches && $type === 'checkbox_image') {
                $type = 'checkbox';
            }

            $options[] = [
                'option_id' => $option['option_id'],
                'name' => $option['name'],
                'type' => $type,
                'values' => $values,
                'expanded_desktop' => (int) ($option['expanded_desktop'] ?? 1),
                'expanded_mobile' => (int) ($option['expanded_mobile'] ?? 0),
            ];
        }

        $manufacturers = [];
        foreach ($this->filter->getManufacturersByCategoryId((int) $category->id) as $row) {
            $key = 'm'.$row['value_id'];
            $count = $counters[$key] ?? 0;
            $selected = ! empty($params['m']) && in_array((string) $row['value_id'], array_map('strval', $params['m']), true);
            if ($showCounts && ! $selected && $count < 1) {
                continue;
            }
            $manufacturers[] = [
                'value_id' => $row['value_id'],
                'name' => $row['name'],
                'count' => $count,
                'selected' => $selected,
                'disabled' => $showCounts && ! $selected && $count < 1,
            ];
        }

        $stock = [];
        foreach ([['in', 'В наличии'], ['out', 'Нет в наличии']] as [$id, $name]) {
            $key = 's'.$id;
            $count = $counters[$key] ?? 0;
            $selected = ! empty($params['s']) && in_array($id, $params['s'], true);
            if ($showCounts && ! $selected && $count < 1) {
                continue;
            }
            $stock[] = [
                'value_id' => $id,
                'name' => $name,
                'count' => $count,
                'selected' => $selected,
                'disabled' => $showCounts && ! $selected && $count < 1,
            ];
        }

        $path = (string) $category->id;

        return [
            'category_id' => (int) $category->id,
            'path' => $path,
            'url_key' => WtFilterService::URL_KEY,
            'params' => $this->filter->encodeParams($params),
            'callback' => localized_route('catalog.wt-filter.callback'),
            'refresh' => localized_route('catalog.wt-filter.refresh'),
            'search_manufacturers' => '',
            'show_counts' => $showCounts,
            'group_expanded' => $groupExpanded,
            'wt_limit' => 8,
            'total' => $this->filter->getTotalProducts((int) $category->id, $params),
            'text_total' => $this->totalText($this->filter->getTotalProducts((int) $category->id, $params)),
            'options' => $options,
            'manufacturers' => $manufacturers,
            'stock' => $stock,
            'discount' => [],
            'popular_filters' => [],
            'price' => [
                'min' => $min,
                'max' => $max,
                'from' => $from,
                'to' => $to,
            ],
            'currency_symbol' => '₽',
            'accent_color' => '#229ac8',
            'accent_rgb' => '34, 154, 200',
            'manufacturer_id' => 0,
            'special' => 0,
            'category_url' => CatalogUrl::category($category, $lang),
            'href' => $this->categoryFilterUrl($category, $lang, $params),
            'heading_title' => 'Фильтр',
            'text_filter' => 'Фильтр',
            'text_filter_short' => 'Фильтр',
            'text_filters_title' => 'Фильтры',
            'text_price' => 'Цена',
            'text_manufacturer' => 'Производитель',
            'text_stock' => 'Наличие',
            'text_discount' => 'Акции',
            'text_reset' => 'Сбросить',
            'text_apply_filter' => 'Применить',
            'text_cancel' => 'Закрыть',
            'text_empty_filter' => 'Нет доступных фильтров',
            'text_search_manufacturer' => 'Поиск производителя',
            'text_search_manufacturer_empty' => 'Ничего не найдено',
            'text_show_more' => 'Ещё %s',
            'text_show_less' => 'Свернуть',
            'text_show_total' => 'Товаров: %s',
            'text_popular_filters' => 'Популярное',
        ];
    }

    private function resolveCategory(Request $request): ?Category
    {
        $id = (int) $request->input('category_id', 0);
        if ($id > 0) {
            return Category::query()->where('status', true)->find($id);
        }

        // fallback: path slug from current SEO is not passed — require category_id
        return null;
    }

    /** @param  array<string, list<string>>  $params */
    private function categoryFilterUrl(Category $category, Language $lang, array $params): string
    {
        $url = CatalogUrl::category($category, $lang);
        $encoded = $this->filter->encodeParams($params);
        if ($encoded === '') {
            return $url;
        }

        $sep = str_contains($url, '?') ? '&' : '?';

        return $url.$sep.WtFilterService::URL_KEY.'='.rawurlencode($encoded);
    }

    private function totalText(int $total): string
    {
        return 'Товаров: '.$total;
    }
}
