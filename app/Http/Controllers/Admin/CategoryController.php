<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryDescription;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Админка категорий каталога (Laravel-аналог OpenCart admin/controller/catalog/category.php).
 *
 * Роуты (resource): admin/categories → admin.categories.*
 * В OpenCart те же действия: index / add+getForm / edit+getForm / delete.
 *
 * Пустой slug по языкам заполняется из названия дефолтного языка (один URL на все локали),
 * админ может переопределить slug вручную для любого языка.
 */
class CategoryController extends Controller
{
    /**
     * Список категорий в админке.
     *
     * Откуда: OpenCart ControllerCatalogCategory::index() → getList().
     * Зачем: показать таблицу категорий с родителем и пагинацией.
     *
     * Маршрут: GET admin/categories → admin.categories.index
     * View: admin.categories.index
     */
    public function index()
    {
        $pageTitle = 'Categories - Список';
        $defaultLanguage = Language::getDefault();

        // parent + descriptions — для колонок «родитель» / «название»
        // withCount('children') — чтобы в списке видеть, есть ли подкатегории
        $categories = Category::with(['parent.descriptions', 'descriptions'])
            ->withCount('children')
            ->orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('admin.categories.index', compact('categories', 'pageTitle', 'defaultLanguage'));
    }

    /**
     * Форма создания категории (пустая).
     *
     * Откуда: OpenCart ControllerCatalogCategory::add() → getForm() (без POST).
     * Зачем: отдать create-view + список родителей для select + языки для вкладок name/slug.
     *
     * Маршрут: GET admin/categories/create → admin.categories.create
     * View: admin.categories.create
     */
    public function create()
    {
        $pageTitle = 'Categories - Создание';
        $languages = Language::forAdminForms();
        $defaultLanguage = Language::getDefault();

        // Дерево для выбора parent_id (как category_id parent в OC)
        $parentOptions = Category::treeForParentSelect($defaultLanguage, []);

        return view('admin.categories.create', compact('pageTitle', 'parentOptions', 'languages', 'defaultLanguage'));
    }

    /**
     * Сохранение новой категории + описаний по языкам.
     *
     * Откуда: OpenCart ControllerCatalogCategory::add() при POST → model->addCategory().
     * Зачем: валидация, запись в categories + category_descriptions, пересчёт path (как oc_category_path).
     *
     * Маршрут: POST admin/categories → admin.categories.store
     */
    public function store(Request $request)
    {
        // Пустой select родителя → null (корневая категория), не строка ""
        $request->merge([
            'parent_id' => $request->filled('parent_id') ? (int) $request->parent_id : null,
        ]);

        $languages = Language::forAdminForms();
        if ($languages->isEmpty()) {
            return redirect()->route('admin.languages.index')
                ->with('info', 'Добавьте хотя бы один язык — после этого в формах появятся поля названий.');
        }

        // Пустые slug_* → из name дефолтного языка; затем unique видит итоговые значения.
        $this->mergeLocalizedSlugsFromDefaultName($request, $languages);

        $rules = array_merge(
            [
                'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
                'image' => 'nullable|string|max:255',
                'top' => 'nullable|boolean',
                'column' => 'nullable|integer|min:0|max:12',
                'sort_order' => 'nullable|integer|min:0',
                'status' => 'nullable|boolean',
            ],
            $this->descriptionRules($languages)
        );

        $request->validate($rules);

        // Транзакция: категория + все языковые описания + paths атомарно
        DB::transaction(function () use ($request, $languages) {
            $category = Category::create([
                'parent_id' => $request->input('parent_id'),
                'image' => $request->input('image'),
                'top' => $request->boolean('top'),
                'column' => (int) $request->input('column', 0),
                'sort_order' => (int) $request->input('sort_order', 0),
                'status' => $request->boolean('status'),
            ]);

            foreach ($languages as $language) {
                $suffix = $language->code;
                $name = trim((string) $request->input('name_'.$suffix, ''));
                $slug = (string) $request->input('slug_'.$suffix);

                CategoryDescription::create([
                    'category_id' => $category->id,
                    'language_id' => $language->id,
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $request->input('description_'.$suffix),
                    'meta_title' => $request->input('meta_title_'.$suffix),
                    'meta_description' => $request->input('meta_description_'.$suffix),
                    'meta_keyword' => $request->input('meta_keyword_'.$suffix),
                ]);
            }

            // Как oc_category_path: плоские пути parent→child для быстрых запросов по дереву
            Category::rebuildPaths();
        });

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория успешно создана');
    }

    /**
     * Форма редактирования существующей категории.
     *
     * Откуда: OpenCart ControllerCatalogCategory::edit() → getForm() (GET).
     * Зачем: загрузить категорию, описания, дерево родителей (без себя и потомков — нельзя выбрать себя родителем).
     *
     * Маршрут: GET admin/categories/{id}/edit → admin.categories.edit
     * View: admin.categories.edit
     */
    public function edit(string $id)
    {
        $pageTitle = 'Categories - Редактирование';
        $category = Category::with('descriptions')->withCount('children')->findOrFail($id);
        $languages = Language::forAdminForms();
        $defaultLanguage = Language::getDefault();

        // Исключаем себя и всех потомков из выбора parent (защита от циклов в дереве)
        $excludeIds = [(int) $category->id, ...$category->descendantIdList()];
        $parentOptions = Category::treeForParentSelect($defaultLanguage, $excludeIds);

        return view('admin.categories.edit', compact('category', 'pageTitle', 'parentOptions', 'languages', 'defaultLanguage'));
    }

    /**
     * Обновление категории + описаний.
     *
     * Откуда: OpenCart ControllerCatalogCategory::edit() при POST → model->editCategory().
     * Зачем: сохранить изменения; запретить parent_id = себе/потомку; пересчитать paths.
     *
     * Маршрут: PUT/PATCH admin/categories/{id} → admin.categories.update
     */
    public function update(Request $request, string $id)
    {
        $request->merge([
            'parent_id' => $request->filled('parent_id') ? (int) $request->parent_id : null,
        ]);

        $category = Category::with('descriptions')->findOrFail($id);
        $languages = Language::forAdminForms();
        if ($languages->isEmpty()) {
            return redirect()->route('admin.languages.index')
                ->with('info', 'Добавьте хотя бы один язык — после этого в формах появятся поля названий.');
        }

        // Пустые slug_* → из name дефолтного языка; затем unique видит итоговые значения.
        $this->mergeLocalizedSlugsFromDefaultName($request, $languages);

        $rules = array_merge(
            [
                'parent_id' => [
                    'nullable',
                    'integer',
                    'exists:categories,id',
                    // Нельзя назначить родителем себя или своего потомка
                    Rule::notIn(array_merge([(int) $category->id], $category->descendantIdList())),
                ],
                'image' => 'nullable|string|max:255',
                'top' => 'nullable|boolean',
                'column' => 'nullable|integer|min:0|max:12',
                'sort_order' => 'nullable|integer|min:0',
                'status' => 'nullable|boolean',
            ],
            $this->descriptionRules($languages, $category)
        );

        $request->validate($rules);

        DB::transaction(function () use ($request, $languages, $category) {
            $category->update([
                'parent_id' => $request->input('parent_id'),
                'image' => $request->input('image'),
                'top' => $request->boolean('top'),
                'column' => (int) $request->input('column', 0),
                'sort_order' => (int) $request->input('sort_order', 0),
                'status' => $request->boolean('status'),
            ]);

            foreach ($languages as $language) {
                $suffix = $language->code;
                $name = trim((string) $request->input('name_'.$suffix, ''));
                $slug = (string) $request->input('slug_'.$suffix);

                // Есть — обновить, нет — создать (как upsert описаний в OC)
                CategoryDescription::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'language_id' => $language->id,
                    ],
                    [
                        'name' => $name,
                        'slug' => $slug,
                        'description' => $request->input('description_'.$suffix),
                        'meta_title' => $request->input('meta_title_'.$suffix),
                        'meta_description' => $request->input('meta_description_'.$suffix),
                        'meta_keyword' => $request->input('meta_keyword_'.$suffix),
                    ]
                );
            }

            Category::rebuildPaths();
        });

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория успешно обновлена');
    }

    /**
     * Удаление категории вместе со всеми вложенными.
     *
     * Предупреждение о каскаде — на клиенте (confirm в списке).
     * После удаления — rebuildPaths().
     *
     * Маршрут: DELETE admin/categories/{id} → admin.categories.destroy
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        $nestedCount = count($category->descendantIdList());

        $deleted = 0;
        DB::transaction(function () use ($category, &$deleted) {
            $deleted = Category::deleteTrees([(int) $category->id]);
            Category::rebuildPaths();
        });

        if ($nestedCount > 0) {
            return redirect()->route('admin.categories.index')
                ->with('success', "Категория удалена вместе со вложенными (всего: {$deleted})");
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория успешно удалена');
    }

    /**
     * Массовое удаление: выбранные категории + все их вложения.
     *
     * Маршрут: POST admin/categories/destroy-selected → admin.categories.destroy-selected
     */
    public function destroySelected(Request $request)
    {
        $request->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'exists:categories,id'],
        ], [
            'selected.required' => 'Выберите хотя бы одну категорию для удаления',
        ]);

        $ids = collect($request->input('selected'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $deleted = 0;
        DB::transaction(function () use ($ids, &$deleted) {
            $deleted = Category::deleteTrees($ids);
            Category::rebuildPaths();
        });

        return redirect()->route('admin.categories.index')
            ->with('success', $deleted === 1
                ? 'Категория успешно удалена'
                : "Удалено категорий (включая вложенные): {$deleted}");
    }

    /**
     * Пустой slug по любому языку → Str::slug(name дефолтного языка).
     * Непустой — нормализуется через Str::slug (ручной override админа).
     * Вызывать до validate(), чтобы unique проверял итоговые значения.
     */
    private function mergeLocalizedSlugsFromDefaultName(Request $request, $languages): void
    {
        $defaultLanguage = $languages->firstWhere('is_default', true) ?? $languages->first();
        if (! $defaultLanguage) {
            return;
        }

        // Пробелы не считаются заполненным названием
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
                // Пустой baseSlug не подставляем — required на slug отловит пустоту
                $request->merge([$key => $baseSlug !== '' ? $baseSlug : null]);
            } else {
                $normalized = Str::slug(trim((string) $slugRaw));
                $request->merge([$key => $normalized !== '' ? $normalized : null]);
            }
        }
    }

    /**
     * Правила name/slug/meta по языкам.
     * Вызывать после mergeLocalizedSlugsFromDefaultName: slug уже заполнен для всех языков.
     * Название и slug обязательны для каждого языка.
     */
    private function descriptionRules($languages, ?Category $category = null): array
    {
        $rules = [];

        foreach ($languages as $language) {
            $suffix = $language->code;

            $unique = Rule::unique('category_descriptions', 'slug')
                ->where('language_id', $language->id);

            if ($category) {
                $desc = $category->descriptions->firstWhere('language_id', $language->id);
                $unique = $unique->ignore($desc?->id);
            }

            $rules['name_'.$suffix] = ['required', 'string', 'min:1', 'max:255'];

            $rules['slug_'.$suffix] = [
                'required',
                'string',
                'min:1',
                'max:255',
                $unique,
            ];

            $rules['description_'.$suffix] = 'nullable|string';
            $rules['meta_title_'.$suffix] = 'nullable|string|max:255';
            $rules['meta_description_'.$suffix] = 'nullable|string|max:255';
            $rules['meta_keyword_'.$suffix] = 'nullable|string|max:255';
        }

        return $rules;
    }
}
