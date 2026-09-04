<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleDescription;
use App\Models\ArticleReview;
use App\Models\BlogCategory;
use App\Models\BlogCategoryDescription;
use App\Models\BlogSetting;
use App\Models\Language;
use App\Support\CatalogLocale;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    private function lang(): Language
    {
        return CatalogLocale::current();
    }

    public function index(Request $request): View
    {
        $lang = $this->lang();
        $articles = $this->articleListQuery($lang)->paginate($this->limit())->withQueryString();
        $categories = $this->menuCategories($lang);
        $heading = BlogSetting::getValue('html_h1') ?: BlogSetting::getValue('name', 'Блог');

        return view('catalog.blog.index', [
            'lang' => $lang,
            'articles' => $articles,
            'categories' => $categories,
            'heading' => $heading,
            'metaTitle' => BlogSetting::getValue('meta_title') ?: $heading,
            'metaDescription' => BlogSetting::getValue('meta_description'),
            'excerptLength' => BlogSetting::int('article_description_length', 200),
            'reviewStatus' => BlogSetting::bool('review_status', true),
        ]);
    }

    public function show(string $slug): View
    {
        $lang = $this->lang();
        $catDesc = BlogCategoryDescription::query()
            ->where('language_id', $lang->id)
            ->where('slug', $slug)
            ->first();

        if ($catDesc) {
            return $this->categoryPage($lang, $catDesc);
        }

        return $this->article($slug);
    }

    public function categoryPage(Language $lang, BlogCategoryDescription $catDesc): View
    {

        $category = BlogCategory::query()
            ->where('id', $catDesc->blog_category_id)
            ->where('status', true)
            ->firstOrFail();

        $childIds = $category->descendantIdList();
        $filterIds = array_merge([(int) $category->id], $childIds);

        $articles = $this->articleListQuery($lang)
            ->whereHas('blogCategories', fn ($q) => $q->whereIn('blog_categories.id', $filterIds))
            ->paginate($this->limit())
            ->withQueryString();

        $children = BlogCategory::query()
            ->where('parent_id', $category->id)
            ->where('status', true)
            ->orderBy('sort_order')
            ->with(['descriptions' => fn ($q) => $q->where('language_id', $lang->id)])
            ->get();

        return view('catalog.blog.category', [
            'lang' => $lang,
            'category' => $category,
            'catDesc' => $catDesc,
            'children' => $children,
            'articles' => $articles,
            'categories' => $this->menuCategories($lang),
            'excerptLength' => BlogSetting::int('article_description_length', 200),
            'reviewStatus' => BlogSetting::bool('review_status', true),
            'metaTitle' => $catDesc->meta_title ?: $catDesc->name,
            'metaDescription' => $catDesc->meta_description,
        ]);
    }

    public function articleInCategory(string $categorySlug, string $articleSlug): View
    {
        return $this->article($articleSlug, $categorySlug);
    }

    private function article(string $articleSlug, ?string $categorySlug = null): View
    {
        $lang = $this->lang();
        $desc = ArticleDescription::query()
            ->where('language_id', $lang->id)
            ->where('slug', $articleSlug)
            ->firstOrFail();

        $article = Article::query()
            ->published()
            ->with([
                'descriptions' => fn ($q) => $q->where('language_id', $lang->id),
                'images',
                'blogCategories.descriptions' => fn ($q) => $q->where('language_id', $lang->id),
                'relatedArticles' => fn ($q) => $q->published()->with(['descriptions' => fn ($d) => $d->where('language_id', $lang->id)]),
                'products' => fn ($q) => $q->where('status', true)->with(['descriptions' => fn ($d) => $d->where('language_id', $lang->id)]),
            ])
            ->findOrFail($desc->article_id);

        $article->increment('viewed');

        $category = null;
        $catDesc = null;
        if ($categorySlug) {
            $catDesc = BlogCategoryDescription::query()
                ->where('language_id', $lang->id)
                ->where('slug', $categorySlug)
                ->first();
            if ($catDesc) {
                $category = $article->blogCategories->firstWhere('id', $catDesc->blog_category_id);
            }
        }
        $category ??= $article->mainBlogCategory();
        $catDesc = $category
            ? ($category->descriptions->firstWhere('language_id', $lang->id)
                ?? $catDesc)
            : $catDesc;

        $reviews = ArticleReview::query()
            ->where('article_id', $article->id)
            ->where('status', true)
            ->orderByDesc('id')
            ->get();

        $avgRating = $reviews->avg('rating');

        return view('catalog.blog.article', [
            'lang' => $lang,
            'article' => $article,
            'desc' => $desc,
            'category' => $category,
            'catDesc' => $catDesc,
            'categories' => $this->menuCategories($lang),
            'reviews' => $reviews,
            'avgRating' => $avgRating,
            'reviewStatus' => BlogSetting::bool('review_status', true) && $article->reviews_enabled,
            'reviewGuest' => BlogSetting::bool('review_guest', true),
            'metaTitle' => $desc->meta_title ?: $desc->name,
            'metaDescription' => $desc->meta_description,
        ]);
    }

    public function review(Request $request, string $articleSlug)
    {
        $lang = $this->lang();
        $desc = ArticleDescription::query()
            ->where('language_id', $lang->id)
            ->where('slug', $articleSlug)
            ->firstOrFail();

        $article = Article::query()->published()->findOrFail($desc->article_id);

        if (! BlogSetting::bool('review_status', true) || ! $article->reviews_enabled) {
            return back()->with('error', 'Отзывы к этой статье выключены.');
        }

        $request->validate([
            'author' => 'required|string|min:2|max:64',
            'text' => 'required|string|min:10|max:3000',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        ArticleReview::create([
            'article_id' => $article->id,
            'customer_id' => 0,
            'author' => $request->input('author'),
            'text' => $request->input('text'),
            'rating' => (int) $request->input('rating'),
            'status' => false,
        ]);

        return back()->with('success', 'Спасибо! Отзыв появится после проверки.');
    }

    private function articleListQuery(Language $lang)
    {
        return Article::query()
            ->published()
            ->with([
                'descriptions' => fn ($q) => $q->where('language_id', $lang->id),
                'blogCategories.descriptions' => fn ($q) => $q->where('language_id', $lang->id),
            ])
            ->withAvg(['reviews' => fn ($q) => $q->where('status', true)], 'rating')
            ->orderByDesc('date_available')
            ->orderByDesc('id');
    }

    private function menuCategories(Language $lang)
    {
        return BlogCategory::query()
            ->where('status', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with(['descriptions' => fn ($q) => $q->where('language_id', $lang->id)])
            ->get();
    }

    private function limit(): int
    {
        return max(1, BlogSetting::int('article_limit', 20));
    }
}
