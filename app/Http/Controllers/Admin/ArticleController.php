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
use App\Services\AiTextCleaner;
use App\Services\ArticleTranslateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

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

    public function translate(Request $request, ArticleTranslateService $translator): JsonResponse
    {
        $default = Language::getDefault();
        $sourceCode = strtolower((string) $request->input('source_code', $default?->code ?? 'ru'));

        $fields = [
            'name' => (string) $request->input('name', ''),
            'description' => (string) $request->input('description', ''),
            'meta_h1' => (string) $request->input('meta_h1', ''),
            'meta_title' => (string) $request->input('meta_title', ''),
            'meta_description' => (string) $request->input('meta_description', ''),
            'meta_keyword' => (string) $request->input('meta_keyword', ''),
            'tag' => (string) $request->input('tag', ''),
        ];

        try {
            $result = $translator->translate($fields, $sourceCode);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $saved = false;
        $articleId = (int) $request->input('article_id', 0);
        if ($articleId > 0) {
            $article = Article::query()->find($articleId);
            $languages = Language::forAdminForms()->keyBy(fn (Language $l) => strtolower((string) $l->code));

            if ($article && $languages->isNotEmpty()) {
                DB::transaction(function () use ($article, $languages, $result, $sourceCode, &$saved) {
                    $sourceLang = $languages->get($sourceCode);
                    if ($sourceLang) {
                        $existingSource = ArticleDescription::query()
                            ->where('article_id', $article->id)
                            ->where('language_id', $sourceLang->id)
                            ->first();

                        ArticleDescription::updateOrCreate(
                            [
                                'article_id' => $article->id,
                                'language_id' => $sourceLang->id,
                            ],
                            [
                                'name' => $result['source']['name'] ?? '',
                                'slug' => $existingSource?->slug
                                    ?: (\Illuminate\Support\Str::slug((string) ($result['source']['name'] ?? 'article')) ?: 'article'),
                                'description' => $result['source']['description'] ?? '',
                                'meta_title' => $result['source']['meta_title'] ?? ($existingSource->meta_title ?? ''),
                                'meta_h1' => $result['source']['meta_h1'] ?? ($existingSource->meta_h1 ?? ''),
                                'meta_description' => $result['source']['meta_description'] ?? ($existingSource->meta_description ?? ''),
                                'meta_keyword' => $result['source']['meta_keyword'] ?? ($existingSource->meta_keyword ?? ''),
                                'tag' => $result['source']['tag'] ?? ($existingSource->tag ?? ''),
                            ]
                        );
                    }

                    foreach ($result['translations'] as $code => $row) {
                        $lang = $languages->get(strtolower((string) $code));
                        if (! $lang) {
                            continue;
                        }
                        ArticleDescription::updateOrCreate(
                            [
                                'article_id' => $article->id,
                                'language_id' => $lang->id,
                            ],
                            [
                                'name' => $row['name'] ?? '',
                                'slug' => $row['slug'] ?? (\Illuminate\Support\Str::slug((string) ($row['name'] ?? 'article')) ?: 'article'),
                                'description' => $row['description'] ?? '',
                                'meta_title' => $row['meta_title'] ?? '',
                                'meta_h1' => $row['meta_h1'] ?? '',
                                'meta_description' => $row['meta_description'] ?? '',
                                'meta_keyword' => $row['meta_keyword'] ?? '',
                                'tag' => $row['tag'] ?? '',
                            ]
                        );
                    }

                    $saved = true;
                });
            }
        }

        return response()->json([
            'ok' => true,
            'saved' => $saved,
            'article_id' => $articleId ?: null,
            'source_code' => $result['source_code'],
            'source' => $result['source'],
            'translations' => $result['translations'],
        ]);
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

            $cleaner = app(AiTextCleaner::class);

            foreach ($languages as $language) {
                $suffix = $language->code;
                ArticleDescription::updateOrCreate(
                    [
                        'article_id' => $article->id,
                        'language_id' => $language->id,
                    ],
                    [
                        'name' => $cleaner->cleanPlain((string) $request->input('name_'.$suffix, '')),
                        'slug' => (string) $request->input('slug_'.$suffix),
                        'description' => $cleaner->clean((string) $request->input('description_'.$suffix, '')),
                        'meta_title' => $cleaner->cleanPlain((string) $request->input('meta_title_'.$suffix, '')),
                        'meta_h1' => $cleaner->cleanPlain((string) $request->input('meta_h1_'.$suffix, '')),
                        'meta_description' => $cleaner->cleanPlain((string) $request->input('meta_description_'.$suffix, '')),
                        'meta_keyword' => $cleaner->cleanPlain((string) $request->input('meta_keyword_'.$suffix, '')),
                        'tag' => $cleaner->cleanPlain((string) $request->input('tag_'.$suffix, '')),
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
