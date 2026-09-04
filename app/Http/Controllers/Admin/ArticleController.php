<?php

namespace App\Http\Controllers\Admin;

use App\Http\Concerns\SyncsBlogLocalizedFields;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleDescription;
use App\Models\ArticleImage;
use App\Models\BlogCategory;
use App\Models\Language;
use App\Models\ProductDescription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArticleController extends Controller
{
    use SyncsBlogLocalizedFields;

    public function index()
    {
        $pageTitle = 'Блог — статьи';
        $defaultLanguage = Language::getDefault();

        $articles = Article::with('descriptions')
            ->orderByDesc('id')
            ->paginate(15);

        return view('admin.articles.index', compact('articles', 'pageTitle', 'defaultLanguage'));
    }

    public function create()
    {
        return $this->form(null);
    }

    public function store(Request $request)
    {
        return $this->save($request, null);
    }

    public function edit(string $id)
    {
        $article = Article::with(['descriptions', 'images', 'blogCategories', 'relatedArticles.descriptions', 'products.descriptions'])
            ->findOrFail($id);

        return $this->form($article);
    }

    public function update(Request $request, string $id)
    {
        $article = Article::with('descriptions')->findOrFail($id);

        return $this->save($request, $article);
    }

    public function destroy(string $id)
    {
        Article::findOrFail($id)->delete();

        return redirect()->route('admin.articles.index')
            ->with('success', 'Статья удалена');
    }

    public function suggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $lang = Language::getDefault();
        if (mb_strlen($q) < 2 || ! $lang) {
            return response()->json(['items' => []]);
        }

        $items = ArticleDescription::query()
            ->where('language_id', $lang->id)
            ->where('name', 'like', '%'.$q.'%')
            ->orderBy('name')
            ->limit(10)
            ->get(['article_id', 'name'])
            ->map(fn ($row) => [
                'id' => (int) $row->article_id,
                'name' => $row->name,
            ]);

        return response()->json(['items' => $items]);
    }

    public function suggestProducts(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $lang = Language::getDefault();
        if (mb_strlen($q) < 2 || ! $lang) {
            return response()->json(['items' => []]);
        }

        $items = ProductDescription::query()
            ->where('language_id', $lang->id)
            ->where('name', 'like', '%'.$q.'%')
            ->orderBy('name')
            ->limit(10)
            ->get(['product_id', 'name'])
            ->map(fn ($row) => [
                'id' => (int) $row->product_id,
                'name' => $row->name,
            ]);

        return response()->json(['items' => $items]);
    }

    private function form(?Article $article)
    {
        $pageTitle = $article ? 'Блог — статья' : 'Блог — новая статья';
        $languages = Language::forAdminForms();
        $defaultLanguage = Language::getDefault();
        $blogCategories = BlogCategory::with('descriptions')
            ->orderBy('sort_order')
            ->get();

        return view($article ? 'admin.articles.edit' : 'admin.articles.create', compact(
            'pageTitle',
            'languages',
            'defaultLanguage',
            'blogCategories',
            'article'
        ));
    }

    private function save(Request $request, ?Article $article)
    {
        $languages = Language::forAdminForms();
        if ($languages->isEmpty()) {
            return redirect()->route('admin.languages.index')
                ->with('info', 'Добавьте хотя бы один язык — после этого в формах появятся поля названий.');
        }

        $this->mergeLocalizedSlugsFromDefaultName($request, $languages);

        $request->validate(array_merge([
            'image' => 'nullable|string|max:255',
            'date_available' => 'nullable|date',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
            'reviews_enabled' => 'nullable|boolean',
            'blog_category_ids' => 'nullable|array',
            'blog_category_ids.*' => 'integer|exists:blog_categories,id',
            'main_blog_category_id' => 'nullable|integer|exists:blog_categories,id',
            'related_article_ids' => 'nullable|array',
            'related_article_ids.*' => 'integer|exists:articles,id',
            'related_product_ids' => 'nullable|array',
            'related_product_ids.*' => 'integer|exists:products,id',
            'article_image' => 'nullable|array',
            'article_image.*.image' => 'nullable|string|max:255',
            'article_image.*.sort_order' => 'nullable|integer|min:0',
        ], $this->descriptionRules('article_descriptions', $languages, $article)));

        DB::transaction(function () use ($request, $languages, &$article) {
            $payload = [
                'image' => $request->input('image'),
                'date_available' => $request->input('date_available') ?: now()->toDateString(),
                'sort_order' => (int) $request->input('sort_order', 0),
                'status' => $request->boolean('status'),
                'reviews_enabled' => $request->boolean('reviews_enabled'),
            ];

            if ($article) {
                $article->update($payload);
            } else {
                $article = Article::create($payload);
            }

            foreach ($languages as $language) {
                $suffix = $language->code;
                ArticleDescription::updateOrCreate(
                    [
                        'article_id' => $article->id,
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
                        'tag' => $request->input('tag_'.$suffix),
                    ]
                );
            }

            $categoryIds = collect($request->input('blog_category_ids', []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();
            $mainId = (int) $request->input('main_blog_category_id', 0);
            $sync = [];
            foreach ($categoryIds as $id) {
                $sync[$id] = ['main' => $id === $mainId];
            }
            if ($mainId && ! isset($sync[$mainId])) {
                $sync[$mainId] = ['main' => true];
            }
            $article->blogCategories()->sync($sync);

            $related = collect($request->input('related_article_ids', []))
                ->map(fn ($id) => (int) $id)
                ->reject(fn ($id) => $id === (int) $article->id)
                ->unique()
                ->values()
                ->all();
            $article->relatedArticles()->sync($related);
            foreach ($related as $relatedId) {
                $other = Article::find($relatedId);
                if ($other && ! $other->relatedArticles()->where('related_id', $article->id)->exists()) {
                    $other->relatedArticles()->attach($article->id);
                }
            }

            $products = collect($request->input('related_product_ids', []))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
            $article->products()->sync($products);

            $article->images()->delete();
            foreach ($request->input('article_image', []) as $row) {
                $path = trim((string) ($row['image'] ?? ''));
                if ($path === '') {
                    continue;
                }
                ArticleImage::create([
                    'article_id' => $article->id,
                    'image' => $path,
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                ]);
            }
        });

        return redirect()->route('admin.articles.index')
            ->with('success', $article->wasRecentlyCreated ? 'Статья создана' : 'Статья обновлена');
    }
}
