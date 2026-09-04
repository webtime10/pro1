<?php

namespace App\Http\Controllers\Admin;

use App\Http\Concerns\SyncsBlogLocalizedFields;
use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogCategoryDescription;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BlogCategoryController extends Controller
{
    use SyncsBlogLocalizedFields;

    public function index()
    {
        $pageTitle = 'Блог — категории';
        $defaultLanguage = Language::getDefault();

        $categories = BlogCategory::with(['parent.descriptions', 'descriptions'])
            ->withCount('children')
            ->orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('admin.blog-categories.index', compact('categories', 'pageTitle', 'defaultLanguage'));
    }

    public function create()
    {
        $pageTitle = 'Блог — новая категория';
        $languages = Language::forAdminForms();
        $defaultLanguage = Language::getDefault();
        $parentOptions = BlogCategory::treeForParentSelect($defaultLanguage, []);

        $category = null;

        return view('admin.blog-categories.create', compact('pageTitle', 'parentOptions', 'languages', 'defaultLanguage', 'category'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'parent_id' => $request->filled('parent_id') ? (int) $request->parent_id : null,
        ]);

        $languages = Language::forAdminForms();
        if ($languages->isEmpty()) {
            return redirect()->route('admin.languages.index')
                ->with('info', 'Добавьте хотя бы один язык — после этого в формах появятся поля названий.');
        }

        $this->mergeLocalizedSlugsFromDefaultName($request, $languages);

        $request->validate(array_merge([
            'parent_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'image' => 'nullable|string|max:255',
            'top' => 'nullable|boolean',
            'column' => 'nullable|integer|min:0|max:12',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ], $this->descriptionRules('blog_category_descriptions', $languages)));

        DB::transaction(function () use ($request, $languages) {
            $category = BlogCategory::create([
                'parent_id' => $request->input('parent_id'),
                'image' => $request->input('image'),
                'top' => $request->boolean('top'),
                'column' => (int) $request->input('column', 0),
                'sort_order' => (int) $request->input('sort_order', 0),
                'status' => $request->boolean('status'),
            ]);

            foreach ($languages as $language) {
                $suffix = $language->code;
                BlogCategoryDescription::create([
                    'blog_category_id' => $category->id,
                    'language_id' => $language->id,
                    'name' => trim((string) $request->input('name_'.$suffix, '')),
                    'slug' => (string) $request->input('slug_'.$suffix),
                    'description' => $request->input('description_'.$suffix),
                    'meta_title' => $request->input('meta_title_'.$suffix),
                    'meta_h1' => $request->input('meta_h1_'.$suffix),
                    'meta_description' => $request->input('meta_description_'.$suffix),
                    'meta_keyword' => $request->input('meta_keyword_'.$suffix),
                ]);
            }

            BlogCategory::rebuildPaths();
        });

        return redirect()->route('admin.blog-categories.index')
            ->with('success', 'Категория блога создана');
    }

    public function edit(string $id)
    {
        $pageTitle = 'Блог — категория';
        $category = BlogCategory::with('descriptions')->withCount('children')->findOrFail($id);
        $languages = Language::forAdminForms();
        $defaultLanguage = Language::getDefault();
        $excludeIds = [(int) $category->id, ...$category->descendantIdList()];
        $parentOptions = BlogCategory::treeForParentSelect($defaultLanguage, $excludeIds);

        return view('admin.blog-categories.edit', compact('category', 'pageTitle', 'parentOptions', 'languages', 'defaultLanguage'));
    }

    public function update(Request $request, string $id)
    {
        $request->merge([
            'parent_id' => $request->filled('parent_id') ? (int) $request->parent_id : null,
        ]);

        $category = BlogCategory::with('descriptions')->findOrFail($id);
        $languages = Language::forAdminForms();
        if ($languages->isEmpty()) {
            return redirect()->route('admin.languages.index')
                ->with('info', 'Добавьте хотя бы один язык — после этого в формах появятся поля названий.');
        }

        $this->mergeLocalizedSlugsFromDefaultName($request, $languages);

        $request->validate(array_merge([
            'parent_id' => [
                'nullable',
                'integer',
                'exists:blog_categories,id',
                Rule::notIn(array_merge([(int) $category->id], $category->descendantIdList())),
            ],
            'image' => 'nullable|string|max:255',
            'top' => 'nullable|boolean',
            'column' => 'nullable|integer|min:0|max:12',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ], $this->descriptionRules('blog_category_descriptions', $languages, $category)));

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
                BlogCategoryDescription::updateOrCreate(
                    [
                        'blog_category_id' => $category->id,
                        'language_id' => $language->id,
                    ],
                    [
                        'name' => trim((string) $request->input('name_'.$suffix, '')),
                        'slug' => (string) $request->input('slug_'.$suffix),
                        'description' => $request->input('description_'.$suffix),
                        'meta_title' => $request->input('meta_title_'.$suffix),
                        'meta_h1' => $request->input('meta_h1_'.$suffix),
                        'meta_description' => $request->input('meta_description_'.$suffix),
                        'meta_keyword' => $request->input('meta_keyword_'.$suffix),
                    ]
                );
            }

            BlogCategory::rebuildPaths();
        });

        return redirect()->route('admin.blog-categories.index')
            ->with('success', 'Категория блога обновлена');
    }

    public function destroy(string $id)
    {
        $category = BlogCategory::findOrFail($id);
        $nestedCount = count($category->descendantIdList());
        $deleted = 0;

        DB::transaction(function () use ($category, &$deleted) {
            $deleted = BlogCategory::deleteTrees([(int) $category->id]);
            BlogCategory::rebuildPaths();
        });

        return redirect()->route('admin.blog-categories.index')
            ->with('success', $nestedCount > 0
                ? "Категория удалена вместе со вложенными (всего: {$deleted})"
                : 'Категория блога удалена');
    }
}
