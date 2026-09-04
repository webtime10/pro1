<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryDescription;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductDescription;
use App\Http\Controllers\Catalog\WtFilterController;
use App\Services\WtFilterService;
use App\Support\CatalogLocale;
use App\Support\CatalogUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CatalogController extends Controller
{
    private const PER_PAGE = 24;

    private function lang(): Language
    {
        return CatalogLocale::current();
    }

    /** Главная: корневые категории (как витрина OC). */
    public function index(): View
    {
        $lang = $this->lang();
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('status', true)
            ->orderBy('sort_order')
            ->with(['descriptions' => fn ($q) => $q->where('language_id', $lang->id)])
            ->get();

        return view('catalog.index', compact('categories', 'lang'));
    }

    /** Поиск товаров через Meilisearch (Scout). */
    public function search(Request $request): View|JsonResponse
    {
        $lang = $this->lang();
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            $products = Product::query()
                ->whereRaw('1 = 0')
                ->paginate(self::PER_PAGE)
                ->withQueryString();
        } else {
            $products = Product::search($q)
                ->orderBy('sort_order')
                ->paginate(self::PER_PAGE)
                ->withQueryString();

            $products->load([
                'descriptions' => fn ($query) => $query->where('language_id', $lang->id)->select(['id', 'product_id', 'language_id', 'name', 'slug']),
                'manufacturer:id,name',
            ]);
        }

        if ($this->wantsAjaxProducts($request)) {
            return $this->ajaxProductsResponse($products, $lang);
        }

        return view('catalog.search', compact('products', 'lang', 'q'));
    }

    /** Подсказки поиска (от 2 символов) — JSON для выпадающего списка. */
    public function suggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['items' => []]);
        }

        try {
            $raw = Product::search($q)->take(8)->raw();
        } catch (\Throwable) {
            return response()->json(['items' => []]);
        }

        $lang = $this->lang();
        $items = collect($raw['hits'] ?? [])
            ->map(function (array $hit) use ($lang) {
                $slug = $hit['slug'] ?? null;
                $id = isset($hit['id']) ? (int) $hit['id'] : 0;
                if (! $slug && ! $id) {
                    return null;
                }

                return [
                    'name' => (string) ($hit['name'] ?? $hit['model'] ?? ('#'.($hit['id'] ?? ''))),
                    'url' => $id
                        ? CatalogUrl::product($id, $lang)
                        : CatalogUrl::productBySlug((string) $slug, $lang),
                    'price' => number_format((float) ($hit['price'] ?? 0), 2, '.', ' ').' ₽',
                    'sku' => (string) ($hit['sku'] ?? ''),
                ];
            })
            ->filter()
            ->values();

        return response()->json(['items' => $items]);
    }

    /** Один сегмент: /shirts-bulk */
    public function seoOne(Request $request): View|JsonResponse
    {
        $lang = $this->lang();
        $seoA = (string) $request->route('seoA');
        $resolved = CatalogUrl::resolveOne($seoA, $lang);

        return $this->renderCategory($request, $resolved['category'], $resolved['catDesc'], $lang);
    }

    /** Два сегмента: /parent/child или /category/product */
    public function seoTwo(Request $request): View|JsonResponse
    {
        $lang = $this->lang();
        $seoA = (string) $request->route('seoA');
        $seoB = (string) $request->route('seoB');
        $resolved = CatalogUrl::resolveTwo($seoA, $seoB, $lang);

        if ($resolved['type'] === 'product') {
            return $this->renderProduct($resolved['product'], $resolved['prodDesc'], $lang);
        }

        return $this->renderCategory($request, $resolved['category'], $resolved['catDesc'], $lang);
    }

    private function renderCategory(
        Request $request,
        Category $category,
        CategoryDescription $catDesc,
        Language $lang,
    ): View|JsonResponse {
        $filterService = app(WtFilterService::class);
        $params = $filterService->decodeParams((string) $request->query(WtFilterService::URL_KEY, ''));
        $hasFilter = $params !== [];

        $products = $hasFilter
            ? $this->paginateFilteredCategoryProducts($category, $lang, $request, $filterService, $params)
            : $this->paginateCategoryProducts($category, $lang, $request);

        if ($this->wantsAjaxProducts($request)) {
            $category->loadMissing([
                'descriptions' => fn ($q) => $q->where('language_id', $lang->id),
                'parent.descriptions' => fn ($q) => $q->where('language_id', $lang->id),
            ]);

            return $this->ajaxProductsResponse($products, $lang, $category);
        }

        $category->loadMissing([
            'descriptions' => fn ($q) => $q->where('language_id', $lang->id),
            'parent.descriptions' => fn ($q) => $q->where('language_id', $lang->id),
        ]);

        $children = Category::query()
            ->where('parent_id', $category->id)
            ->where('status', true)
            ->orderBy('sort_order')
            ->with([
                'descriptions' => fn ($q) => $q->where('language_id', $lang->id),
                'parent.descriptions' => fn ($q) => $q->where('language_id', $lang->id),
            ])
            ->get();

        $breadcrumb = $this->buildCategoryBreadcrumb($category, $lang);
        $wtFilter = app(WtFilterController::class)->buildModuleData($category, $lang, $request);

        return view('catalog.category', compact(
            'category',
            'catDesc',
            'children',
            'products',
            'lang',
            'breadcrumb',
            'wtFilter'
        ));
    }

    /**
     * @param  array<string, list<string>>  $params
     */
    private function paginateFilteredCategoryProducts(
        Category $category,
        Language $lang,
        Request $request,
        WtFilterService $filter,
        array $params,
    ): LengthAwarePaginator {
        $perPage = self::PER_PAGE;
        $page = max(1, (int) $request->input('page', 1));
        $offset = ($page - 1) * $perPage;

        $total = $filter->getTotalProducts((int) $category->id, $params);
        $pageIds = $filter->getProductIds((int) $category->id, $params, $offset, $perPage);

        $items = $pageIds === []
            ? collect()
            : Product::catalogCardsByIds($pageIds, (int) $lang->id);

        return (new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
            'query' => $request->query(),
        ]));
    }

    private function renderProduct(Product $product, ProductDescription $prodDesc, Language $lang): View
    {
        $product->load([
            'descriptions' => fn ($q) => $q->where('language_id', $lang->id),
            'manufacturer',
            'categories.descriptions' => fn ($q) => $q->where('language_id', $lang->id),
            'productAttributes' => fn ($q) => $q->where('language_id', $lang->id),
            'productAttributes.attribute.descriptions' => fn ($q) => $q->where('language_id', $lang->id),
            'productAttributes.attribute.attributeGroup.descriptions' => fn ($q) => $q->where('language_id', $lang->id),
            'productOptions.option.descriptions' => fn ($q) => $q->where('language_id', $lang->id),
            'productOptions.values.optionValue.descriptions' => fn ($q) => $q->where('language_id', $lang->id),
            'images',
        ]);

        $product->increment('viewed');

        $attributeGroups = $product->productAttributes
            ->groupBy(fn ($pa) => $pa->attribute?->attribute_group_id ?? 0)
            ->map(function ($rows) use ($lang) {
                $first = $rows->first();
                $group = $first?->attribute?->attributeGroup;
                $groupName = $group?->descriptions->firstWhere('language_id', $lang->id)?->name
                    ?? 'Характеристики';

                return [
                    'name' => $groupName,
                    'rows' => $rows->sortBy(fn ($pa) => $pa->attribute?->sort_order ?? 0)->values()->map(function ($pa) use ($lang) {
                        return [
                            'name' => $pa->attribute?->descriptions->firstWhere('language_id', $lang->id)?->name
                                ?? ('#'.($pa->attribute_id ?? '')),
                            'text' => $pa->text,
                        ];
                    }),
                ];
            })
            ->values();

        return view('catalog.product', compact('product', 'prodDesc', 'lang', 'attributeGroups'));
    }

    private function paginateCategoryProducts(Category $category, Language $lang, Request $request): LengthAwarePaginator
    {
        $perPage = self::PER_PAGE;
        $page = max(1, (int) $request->input('page', 1));
        $offset = ($page - 1) * $perPage;

        $total = app(WtFilterService::class)->getTotalProducts((int) $category->id, []);

        $pageIds = DB::table('category_product as cp')
            ->join('products as p', 'p.id', '=', 'cp.product_id')
            ->where('cp.category_id', $category->id)
            ->where('p.status', true)
            ->orderByDesc('cp.product_id')
            ->offset($offset)
            ->limit($perPage)
            ->pluck('p.id')
            ->all();

        $items = $pageIds === []
            ? collect()
            : Product::catalogCardsByIds($pageIds, (int) $lang->id);

        return (new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]))->withQueryString();
    }

    private function wantsAjaxProducts(Request $request): bool
    {
        return $request->boolean('ajax') || $request->ajax();
    }

    private function ajaxProductsResponse(LengthAwarePaginator $products, Language $lang, ?Category $category = null): JsonResponse
    {
        return response()->json([
            'html' => view('catalog.partials.product-tiles', [
                'products' => $products,
                'lang' => $lang,
                'category' => $category,
            ])->render(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'next_page' => $products->hasMorePages() ? $products->currentPage() + 1 : null,
        ]);
    }

    /** Цепочка категорий для хлебных крошек. */
    private function buildCategoryBreadcrumb(Category $category, Language $lang): array
    {
        $pathIds = DB::table('category_paths')
            ->where('category_id', $category->id)
            ->orderBy('level')
            ->pluck('path_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($pathIds === []) {
            $pathIds = [(int) $category->id];
        }

        $cats = Category::query()
            ->whereIn('id', $pathIds)
            ->with(['descriptions' => fn ($q) => $q->where('language_id', $lang->id)])
            ->get()
            ->keyBy('id');

        $chain = [];
        foreach ($pathIds as $id) {
            $current = $cats->get($id);
            if (! $current) {
                continue;
            }
            $d = $current->descriptions->firstWhere('language_id', $lang->id);
            $chain[] = [
                'id' => $current->id,
                'name' => $d->name ?? ('#'.$current->id),
                'slug' => $d->slug ?? null,
            ];
        }

        return $chain;
    }
}
