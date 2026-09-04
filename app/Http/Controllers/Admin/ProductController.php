<?php

namespace App\Http\Controllers\Admin;

use App\Http\Concerns\SyncsProductCatalogData;
use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Language;
use App\Models\Manufacturer;
use App\Models\Option;
use App\Models\Product;
use App\Models\ProductDescription;
use App\Services\WtFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use SyncsProductCatalogData;

    private const LIST_PER_PAGE = 50;

    /**
     * Список для 1M+: узкий SELECT, join названия, номерная пагинация.
     * COUNT без JOIN; без фильтров — кэш total на 60 сек.
     */
    public function index(Request $request)
    {
        $pageTitle = 'Товары';
        $defaultLanguage = Language::getDefault();
        $languageId = $defaultLanguage?->id;

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'status' => $request->input('status', ''),
            'quantity_filter' => $request->input('quantity_filter', ''),
            'category_id' => $request->input('category_id', ''),
        ];

        $query = Product::query()
            ->select([
                'products.id',
                'products.image',
                'products.sku',
                'products.model',
                'products.price',
                'products.quantity',
                'products.status',
                'products.sort_order',
            ]);

        if ($languageId) {
            $query->leftJoin('product_descriptions as pd', function ($join) use ($languageId) {
                $join->on('pd.product_id', '=', 'products.id')
                    ->where('pd.language_id', '=', $languageId);
            })->addSelect(['pd.name as list_name', 'pd.slug as list_slug']);
        }

        $this->applyProductListFilters($query, $filters, $languageId, useJoinAlias: true);

        $total = $this->productListTotal($filters, $languageId);
        $filterQuery = collect($filters)->reject(fn ($v) => $v === '' || $v === null)->all();

        $products = $query
            ->orderByDesc('products.id')
            ->paginate(self::LIST_PER_PAGE, ['*'], 'page', null, $total)
            ->appends($filterQuery);

        $categoryOptions = Category::treeForParentSelect($defaultLanguage, []);

        return view('admin.products.index', compact(
            'pageTitle',
            'products',
            'filters',
            'defaultLanguage',
            'categoryOptions',
        ));
    }

    /** Подсказки поиска товаров в админке (Meilisearch, от 2 символов). */
    public function suggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['items' => []]);
        }

        try {
            $raw = Product::search($q)->take(10)->raw();
        } catch (\Throwable) {
            return response()->json(['items' => []]);
        }

        $items = collect($raw['hits'] ?? [])
            ->map(function (array $hit) {
                $id = (int) ($hit['id'] ?? 0);
                if ($id < 1) {
                    return null;
                }

                return [
                    'id' => $id,
                    'name' => (string) ($hit['name'] ?? $hit['model'] ?? ('#'.$id)),
                    'sku' => (string) ($hit['sku'] ?? ''),
                    'price' => number_format((float) ($hit['price'] ?? 0), 2, '.', ' ').' ₽',
                    'url' => '/admin/products/'.$id.'/edit',
                ];
            })
            ->filter()
            ->values();

        return response()->json(['items' => $items]);
    }

    /** @param  array{search: string, status: mixed, quantity_filter: string, category_id: mixed}  $filters */
    private function applyProductListFilters($query, array $filters, ?int $languageId, bool $useJoinAlias): void
    {
        if (mb_strlen($filters['search']) >= 2) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search, $languageId, $useJoinAlias) {
                $q->where('products.sku', $search)
                    ->orWhere('products.model', 'like', $search.'%');
                if (! $languageId) {
                    return;
                }
                if ($useJoinAlias) {
                    $q->orWhere('pd.name', 'like', $search.'%');
                } else {
                    $q->orWhereExists(function ($sub) use ($search, $languageId) {
                        $sub->selectRaw('1')
                            ->from('product_descriptions')
                            ->whereColumn('product_descriptions.product_id', 'products.id')
                            ->where('product_descriptions.language_id', $languageId)
                            ->where('product_descriptions.name', 'like', $search.'%');
                    });
                }
            });
        }

        if ($filters['status'] !== '' && $filters['status'] !== null) {
            $query->where('products.status', (bool) (int) $filters['status']);
        }

        if ($filters['quantity_filter'] === 'out_of_stock') {
            $query->where('products.quantity', '<=', 0);
        }

        if ($filters['category_id'] !== '' && $filters['category_id'] !== null) {
            $categoryId = (int) $filters['category_id'];
            $query->whereExists(function ($q) use ($categoryId) {
                $q->selectRaw('1')
                    ->from('category_product')
                    ->whereColumn('category_product.product_id', 'products.id')
                    ->where('category_product.category_id', $categoryId);
            });
        }
    }

    /** @param  array{search: string, status: mixed, quantity_filter: string, category_id: mixed}  $filters */
    private function productListTotal(array $filters, ?int $languageId): int
    {
        $hasFilters = collect($filters)->contains(fn ($v) => $v !== '' && $v !== null);

        if (! $hasFilters) {
            try {
                return (int) cache()->remember('admin.products.list_total', 60, fn () => Product::query()->count());
            } catch (\Throwable) {
                return (int) Product::query()->count();
            }
        }

        $countQuery = Product::query()->select('products.id');
        $this->applyProductListFilters($countQuery, $filters, $languageId, useJoinAlias: false);

        return (int) $countQuery->toBase()->getCountForPagination();
    }

    private function forgetProductListTotalCache(): void
    {
        try {
            cache()->forget('admin.products.list_total');
        } catch (\Throwable) {
            // file cache may be unwritable under php-fpm
        }
    }

    /** Быстрое сохранение цены / остатка / статуса со списка (без AJAX). */
    public function quickUpdate(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'price' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'status' => 'nullable|boolean',
        ]);

        if (array_key_exists('price', $data) && $data['price'] !== null) {
            $product->price = $data['price'];
        }
        $product->quantity = $data['quantity'];
        $product->status = $request->boolean('status');
        $product->save();

        $this->forgetProductListTotalCache();

        return redirect()
            ->back()
            ->with('success', 'Product #'.$product->id.' updated');
    }

    public function destroySelected(Request $request)
    {
        $request->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'exists:products,id'],
        ], [
            'selected.required' => 'Select at least one product',
        ]);

        $ids = collect($request->input('selected'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $wtFilter = app(WtFilterService::class);
        $deleted = 0;

        DB::transaction(function () use ($ids, $wtFilter, &$deleted) {
            foreach ($ids as $productId) {
                $product = Product::find($productId);
                if (! $product) {
                    continue;
                }
                $product->delete();
                $wtFilter->deleteProductFromIndex($productId);
                $deleted++;
            }
        });

        $this->forgetProductListTotalCache();

        return redirect()->route('admin.products.index')
            ->with('success', $deleted === 1
                ? 'Product deleted'
                : "Deleted products: {$deleted}");
    }

    public function create()
    {
        return view('admin.products.create', $this->formData('Товар — создание'));
    }

    public function store(Request $request)
    {
        $languages = Language::forAdminForms();
        if ($languages->isEmpty()) {
            return redirect()->route('admin.languages.index')
                ->with('info', 'Добавьте хотя бы один язык — после этого в формах появятся поля названий.');
        }

        $rules = $this->productDataRules();
        $rules = array_merge($rules, $this->descriptionRules($languages));

        $this->mergeLocalizedSlugsFromDefaultName($request, $languages);
        $request->validate($rules);

        DB::transaction(function () use ($request, $languages) {
            $product = Product::create($this->productDataFromRequest($request));
            $product->categories()->sync($request->category_ids);
            $this->syncDescriptions($product, $request, $languages);
            $this->syncProductAttributes($product, $request);
            $this->syncProductOptions($product, $request);
            $this->syncProductImages($product, $request);
            $this->syncWtFilterProductValues($product, $request);
        });

        $this->forgetProductListTotalCache();

        return redirect()->route('admin.products.index')->with('success', 'Товар создан');
    }

    public function edit(string $id)
    {
        $product = Product::with([
            'descriptions',
            'categories',
            'productAttributes',
            'productOptions.values',
            'images',
        ])->findOrFail($id);

        return view('admin.products.edit', array_merge(
            $this->formData('Товар — редактирование'),
            compact('product')
        ));
    }

    public function update(Request $request, string $id)
    {
        $product = Product::with('descriptions')->findOrFail($id);
        $languages = Language::forAdminForms();
        if ($languages->isEmpty()) {
            return redirect()->route('admin.languages.index')
                ->with('info', 'Добавьте хотя бы один язык — после этого в формах появятся поля названий.');
        }

        $rules = $this->productDataRules();
        $rules = array_merge($rules, $this->descriptionRules($languages, $product));

        $this->mergeLocalizedSlugsFromDefaultName($request, $languages);
        $request->validate($rules);

        DB::transaction(function () use ($request, $languages, $product) {
            $product->update($this->productDataFromRequest($request));
            $product->categories()->sync($request->category_ids);
            $this->syncDescriptions($product, $request, $languages);
            $this->syncProductAttributes($product, $request);
            $this->syncProductOptions($product, $request);
            $this->syncProductImages($product, $request);
            $this->syncWtFilterProductValues($product, $request);
        });

        return redirect()->route('admin.products.index')->with('success', 'Товар обновлён');
    }

    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $productId = (int) $product->id;
        $product->delete();
        app(WtFilterService::class)->deleteProductFromIndex($productId);

        $this->forgetProductListTotalCache();

        return redirect()->route('admin.products.index')->with('success', 'Товар удалён');
    }

    /** Сохранить вкладку «Опции фильтра», если она была загружена на форме. */
    private function syncWtFilterProductValues(Product $product, Request $request): void
    {
        if (! $request->boolean('wt_filter_product_tab')) {
            return;
        }

        app(WtFilterService::class)->saveProductFilterValues((int) $product->id, [
            'wt_filter_product_option' => $request->input('wt_filter_product_option', []),
            'category_ids' => $request->input('category_ids', []),
        ]);
    }

    private function formData(string $pageTitle): array
    {
        $languages = Language::forAdminForms();
        $defaultLanguage = Language::getDefault();
        $manufacturers = Manufacturer::orderBy('sort_order')->orderBy('name')->get();
        $categoryOptions = Category::treeForParentSelect($defaultLanguage, []);

        $allAttributes = Attribute::query()
            ->with([
                'descriptions',
                'attributeGroup.descriptions' => fn ($q) => $q->where('language_id', $defaultLanguage?->id),
            ])
            ->orderBy('sort_order')
            ->get();

        $allOptions = Option::query()
            ->with(['descriptions', 'values.descriptions'])
            ->orderBy('sort_order')
            ->get();

        $optionsJson = $allOptions->map(function (Option $option) use ($defaultLanguage) {
            $desc = $option->descriptions->firstWhere('language_id', $defaultLanguage?->id);

            return [
                'id' => $option->id,
                'type' => $option->type,
                'name' => $desc->name ?? ('#'.$option->id),
                'values' => $option->values->map(function ($value) use ($defaultLanguage) {
                    $vDesc = $value->descriptions->firstWhere('language_id', $defaultLanguage?->id);

                    return [
                        'id' => $value->id,
                        'name' => $vDesc->name ?? ('#'.$value->id),
                    ];
                })->values(),
            ];
        })->values();

        return compact(
            'pageTitle',
            'languages',
            'defaultLanguage',
            'manufacturers',
            'categoryOptions',
            'allAttributes',
            'allOptions',
            'optionsJson',
        );
    }

    /**
     * Пустой slug по любому языку → Str::slug(name дефолтного языка).
     * Непустой — нормализуется через Str::slug (ручной override админа).
     */
    private function mergeLocalizedSlugsFromDefaultName(Request $request, $languages): void
    {
        $defaultLanguage = $languages->firstWhere('is_default', true) ?? $languages->first();
        if (! $defaultLanguage) {
            return;
        }

        foreach ($languages as $language) {
            $nameKey = 'name_'.$language->code;
            $request->merge([
                $nameKey => trim((string) $request->input($nameKey, '')),
            ]);
        }

        $baseSlug = Str::slug((string) $request->input('name_'.$defaultLanguage->code, ''));

        foreach ($languages as $language) {
            $key = 'slug_'.$language->code;
            $slugRaw = $request->input($key);

            if (blank($slugRaw)) {
                $request->merge([$key => $baseSlug !== '' ? $baseSlug : null]);
            } else {
                $normalized = Str::slug(trim((string) $slugRaw));
                $request->merge([$key => $normalized !== '' ? $normalized : null]);
            }
        }
    }

    /**
     * Правила name/slug/meta по языкам.
     * Название и slug обязательны для каждого языка.
     */
    private function descriptionRules($languages, ?Product $product = null): array
    {
        $rules = [];

        foreach ($languages as $language) {
            $suffix = $language->code;
            $desc = $product?->descriptions->firstWhere('language_id', $language->id);

            $unique = Rule::unique('product_descriptions', 'slug')
                ->where('language_id', $language->id)
                ->ignore($desc?->id);

            $rules['name_'.$suffix] = ['required', 'string', 'min:1', 'max:255'];
            $rules['slug_'.$suffix] = [
                'required',
                'string',
                'min:1',
                'max:255',
                $unique,
            ];
            $rules['description_'.$suffix] = 'nullable|string';
            $rules['tag_'.$suffix] = 'nullable|string';
            $rules['meta_title_'.$suffix] = 'nullable|string|max:255';
            $rules['meta_description_'.$suffix] = 'nullable|string|max:255';
            $rules['meta_keyword_'.$suffix] = 'nullable|string|max:255';
        }

        return $rules;
    }

    private function syncDescriptions(Product $product, Request $request, $languages): void
    {
        foreach ($languages as $language) {
            $suffix = $language->code;
            $name = trim((string) $request->input('name_'.$suffix, ''));
            $slug = (string) $request->input('slug_'.$suffix);

            ProductDescription::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'language_id' => $language->id,
                ],
                [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $request->input('description_'.$suffix),
                    'tag' => $request->input('tag_'.$suffix),
                    'meta_title' => $request->input('meta_title_'.$suffix),
                    'meta_description' => $request->input('meta_description_'.$suffix),
                    'meta_keyword' => $request->input('meta_keyword_'.$suffix),
                ]
            );
        }
    }
}
