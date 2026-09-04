<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Language;
use App\Services\WtFilterCopyService;
use App\Support\CatalogCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class WtFilterController extends Controller
{
    public function __construct(private \App\Services\WtFilterService $filter) {}

    public function options(): View
    {
        $langId = (int) (\App\Models\Language::getDefault()?->id ?? 1);

        $options = DB::table('wt_filter_option as o')
            ->leftJoin('wt_filter_option_description as od', function ($join) use ($langId) {
                $join->on('o.option_id', '=', 'od.option_id')->where('od.language_id', $langId);
            })
            ->orderBy('o.sort_order')
            ->orderBy('od.name')
            ->select([
                'o.option_id',
                'o.type',
                'o.status',
                'o.sort_order',
                'o.expanded_desktop',
                'o.expanded_mobile',
                'od.name',
            ])
            ->paginate(40);

        return view('admin.wt-filter.options', [
            'pageTitle' => 'Опции фильтра',
            'options' => $options,
            'types' => $this->filterTypes(),
        ]);
    }

    /** @return array<string, string> */
    private function filterTypes(): array
    {
        return [
            'checkbox' => 'Чекбокс',
            'checkbox_image' => 'Чекбокс с картинкой',
            'radio' => 'Радиокнопка',
            'radio_image' => 'Радиокнопка с картинкой',
            'slider_range' => 'Слайдер (диапазон)',
            'select' => 'Список',
        ];
    }

    public function optionsUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'option_id' => 'required|integer',
            'field' => 'required|in:status,expanded_desktop,expanded_mobile,sort_order,type',
            'value' => 'nullable',
        ]);

        $field = $data['field'];
        if ($field === 'type') {
            $allowed = array_keys($this->filterTypes());
            $value = in_array((string) $data['value'], $allowed, true) ? (string) $data['value'] : 'checkbox';
        } else {
            $value = in_array($field, ['status', 'expanded_desktop', 'expanded_mobile', 'sort_order'], true)
                ? (int) $data['value']
                : (string) $data['value'];
        }

        DB::table('wt_filter_option')
            ->where('option_id', (int) $data['option_id'])
            ->update([$field => $value]);

        return response()->json(['status' => true]);
    }

    public function optionEdit(int $option): View
    {
        $row = DB::table('wt_filter_option')->where('option_id', $option)->first();
        abort_if(! $row, 404);

        $languages = Language::forAdminForms();
        $langId = (int) (Language::getDefault()?->id ?? ($languages->first()?->id ?? 1));

        $descriptions = DB::table('wt_filter_option_description')
            ->where('option_id', $option)
            ->get()
            ->keyBy('language_id');

        $optionCategories = DB::table('wt_filter_option_to_category')
            ->where('option_id', $option)
            ->pluck('category_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $rawValues = DB::table('wt_filter_option_value as ov')
            ->leftJoin('wt_filter_option_value_description as ovd', function ($join) use ($langId) {
                $join->on('ov.value_id', '=', 'ovd.value_id')->where('ovd.language_id', $langId);
            })
            ->where('ov.option_id', $option)
            ->orderBy('ov.sort_order')
            ->orderBy('ovd.name')
            ->select([
                'ov.value_id',
                'ov.keyword',
                'ov.color',
                'ov.image',
                'ov.value_numeric',
                'ov.sort_order',
                'ovd.name',
            ])
            ->get();

        $catalogById = collect();
        if ($rawValues->isNotEmpty() && Schema::hasTable('option_values')) {
            $catalogQuery = DB::table('option_values')
                ->whereIn('id', $rawValues->pluck('value_id')->all())
                ->select('id', 'image');
            if (Schema::hasColumn('option_values', 'color')) {
                $catalogQuery->addSelect('color');
            }
            $catalogById = $catalogQuery->get()->keyBy('id');
        }

        $values = $rawValues
            ->map(function ($value) use ($catalogById) {
                $catalog = $catalogById->get((int) $value->value_id);
                $image = trim((string) ($value->image ?? ''));
                if ($image === '' && $catalog) {
                    $image = trim((string) ($catalog->image ?? ''));
                }
                $color = $this->normalizeAdminColor((string) ($value->color ?? ''));
                if ($color === '' && $catalog) {
                    $color = $this->normalizeAdminColor((string) ($catalog->color ?? ''));
                }

                return [
                    'value_id' => (int) $value->value_id,
                    'name' => (string) ($value->name ?? ''),
                    'keyword' => (string) ($value->keyword ?? ''),
                    'color' => $color,
                    'image' => $image,
                    'thumb' => $this->valueThumbUrl($image),
                    'value_numeric' => $value->value_numeric,
                    'sort_order' => (int) $value->sort_order,
                ];
            })
            ->all();

        $optionName = (string) ($descriptions[$langId]->name ?? ('#'.$option));

        return view('admin.wt-filter.option-form', [
            'pageTitle' => 'Опция фильтра',
            'optionId' => $option,
            'optionName' => $optionName,
            'option' => $row,
            'languages' => $languages,
            'descriptions' => $descriptions,
            'types' => $this->filterTypes(),
            'categories' => Category::treeForParentSelect(),
            'optionCategories' => old('category_id', $optionCategories),
            'values' => $values,
        ]);
    }

    public function optionSave(Request $request, int $option): RedirectResponse
    {
        abort_if(! DB::table('wt_filter_option')->where('option_id', $option)->exists(), 404);

        $allowedTypes = array_keys($this->filterTypes());
        $data = $request->validate([
            'type' => 'required|in:'.implode(',', $allowedTypes),
            'keyword' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable',
            'color' => 'nullable',
            'option_description' => 'nullable|array',
            'option_description.*.name' => 'nullable|string|max:255',
            'category_id' => 'nullable|array',
            'category_id.*' => 'integer',
            'value' => 'nullable|array',
            'value.*.color' => 'nullable|string|max:7',
            'value.*.image' => 'nullable|string|max:255',
        ]);

        $type = (string) $data['type'];
        $isColor = $request->boolean('color');
        $useImage = in_array($type, ['checkbox_image', 'radio_image'], true);

        DB::table('wt_filter_option')->where('option_id', $option)->update([
            'type' => $type,
            'keyword' => trim((string) ($data['keyword'] ?? '')),
            'status' => $request->boolean('status') ? 1 : 0,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'color' => $isColor ? 1 : 0,
            'image' => $useImage ? 1 : 0,
        ]);

        foreach (Language::forAdminForms() as $lang) {
            $name = trim((string) $request->input('option_description.'.$lang->id.'.name', ''));
            DB::table('wt_filter_option_description')->updateOrInsert(
                ['option_id' => $option, 'language_id' => (int) $lang->id],
                ['name' => $name, 'description' => '', 'postfix' => '']
            );
        }

        DB::table('wt_filter_option_to_category')->where('option_id', $option)->delete();
        foreach ((array) $request->input('category_id', []) as $categoryId) {
            $categoryId = (int) $categoryId;
            if ($categoryId > 0) {
                DB::table('wt_filter_option_to_category')->insert([
                    'option_id' => $option,
                    'category_id' => $categoryId,
                ]);
            }
        }

        foreach ((array) $request->input('value', []) as $valueId => $row) {
            DB::table('wt_filter_option_value')
                ->where('option_id', $option)
                ->where('value_id', (int) $valueId)
                ->update([
                    'color' => $this->normalizeAdminColor((string) ($row['color'] ?? '')),
                    'image' => trim((string) ($row['image'] ?? '')),
                ]);
        }

        CatalogCache::forgetFilter();

        return redirect()
            ->route('admin.wt-filter.options.edit', $option)
            ->with('success', 'Опция сохранена');
    }

    private function normalizeAdminColor(string $color): string
    {
        $color = trim($color);

        if ($color === '' || ! preg_match('/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i', $color, $m)) {
            return '';
        }

        $hex = strtolower($m[1]);

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return '#'.$hex;
    }

    private function valueThumbUrl(string $image): string
    {
        $image = ltrim($image, '/');

        if ($image === '') {
            return '';
        }

        foreach (['storage/'.$image, 'uploads/'.$image, $image] as $rel) {
            if (is_file(public_path($rel))) {
                return asset($rel);
            }
        }

        return asset('storage/'.$image);
    }

    public function settings(): View
    {
        return view('admin.wt-filter.settings', [
            'pageTitle' => 'Настройки фильтра',
            'groupExpanded' => $this->filter->groupExpanded(),
            'showCounts' => $this->filter->showCounts() ? 1 : 0,
        ]);
    }

    public function settingsSave(Request $request): \Illuminate\Http\RedirectResponse
    {
        $raw = $request->input('group_expanded', []);
        $normalized = [
            'p' => ['desktop' => ! empty($raw['p']['desktop']) ? 1 : 0, 'mobile' => ! empty($raw['p']['mobile']) ? 1 : 0],
            'm' => ['desktop' => ! empty($raw['m']['desktop']) ? 1 : 0, 'mobile' => ! empty($raw['m']['mobile']) ? 1 : 0],
            's' => ['desktop' => ! empty($raw['s']['desktop']) ? 1 : 0, 'mobile' => ! empty($raw['s']['mobile']) ? 1 : 0],
            'd' => ['desktop' => ! empty($raw['d']['desktop']) ? 1 : 0, 'mobile' => ! empty($raw['d']['mobile']) ? 1 : 0],
        ];

        $this->filter->setSetting('group_expanded', $normalized);
        $this->filter->setSetting('show_counts', $request->boolean('show_counts') ? '1' : '0');

        return redirect()
            ->route('admin.wt-filter.settings')
            ->with('success', 'Настройки сохранены');
    }

    public function copyAttributes(): View
    {
        return view('admin.wt-filter.copy-attributes', [
            'pageTitle' => 'Копирование фильтров',
            'types'     => ['checkbox', 'radio', 'select'],
        ]);
    }

    public function copyAttributesRun(Request $request, WtFilterCopyService $service): JsonResponse
    {
        @set_time_limit(300);

        $validated = $request->validate([
            'step'                 => 'required|in:truncate,option,filter,attribute,finalize',
            'copy_type'            => 'nullable|in:checkbox,radio,select',
            'attribute_separator'  => 'nullable|string|max:16',
            'copy_truncate'        => 'nullable',
            'copy_option'          => 'nullable',
            'copy_filter'          => 'nullable',
            'copy_attribute'       => 'nullable',
            'copy_store'           => 'nullable|array',
            'copy_store.*'         => 'integer',
        ]);

        $result = $service->runStep($validated['step'], [
            'copy_type'           => $validated['copy_type'] ?? 'checkbox',
            'attribute_separator' => $validated['attribute_separator'] ?? ',',
            'copy_truncate'       => ! empty($validated['copy_truncate']),
            'copy_store'          => $validated['copy_store'] ?? [0],
        ]);

        CatalogCache::forgetFilter();

        if (! empty($result['error'])) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * AJAX для вкладки «Опции фильтра» на форме товара (как OC catalog/wt_filter/callback).
     */
    public function productFormCallback(Request $request): JsonResponse
    {
        $categoryId = (int) $request->input('category_id', 0);
        $productId = (int) $request->input('product_id', 0);
        $langId = (int) (\App\Models\Language::getDefault()?->id ?? 1);

        if ($categoryId < 1) {
            return response()->json(['message' => 'Сначала выберите категорию для этого товара.']);
        }

        $productValues = $productId > 0 ? $this->filter->getProductValues($productId) : [];
        $results = $this->filter->getOptionsByCategoryId($categoryId, $langId, activeOnly: false);

        if ($results === []) {
            return response()->json(['message' => 'Для выбранной категории нет опций фильтра. Сначала выполните «Копирование атрибутов».']);
        }

        $options = [];
        foreach ($results as $option) {
            $oid = (int) $option['option_id'];
            $type = (string) $option['type'];
            $values = [];

            if (! in_array($type, ['slide', 'slide_dual', 'slider_single', 'slider_range', 'text'], true)) {
                foreach ($option['values'] as $value) {
                    $vid = (string) $value['value_id'];
                    $values[] = [
                        'value_id' => $vid,
                        'name' => $value['name'],
                        'selected' => isset($productValues[$oid][$vid]),
                    ];
                }
            }

            $row = [
                'option_id' => (string) $oid,
                'name' => $option['name'],
                'postfix' => $option['postfix'],
                'status' => (int) $option['status'],
                'type' => $type,
                'slide_value_min' => '',
                'slide_value_max' => '',
                'values' => $values,
            ];

            if (isset($productValues[$oid]['0'])) {
                $pv = $productValues[$oid]['0'];
                $min = (float) ($pv->slide_value_min ?? 0);
                $max = (float) ($pv->slide_value_max ?? 0);
                $row['slide_value_min'] = $min ? rtrim(rtrim(number_format($min, 4, '.', ''), '0'), '.') : '';
                $row['slide_value_max'] = $max ? rtrim(rtrim(number_format($max, 4, '.', ''), '0'), '.') : '';
            }

            $options[] = $row;
        }

        return response()->json(['options' => $options]);
    }
}
