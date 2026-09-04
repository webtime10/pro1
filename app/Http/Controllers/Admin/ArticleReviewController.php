<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleReview;
use App\Models\Language;
use Illuminate\Http\Request;

class ArticleReviewController extends Controller
{
    public function index()
    {
        $pageTitle = 'Блог — отзывы';
        $defaultLanguage = Language::getDefault();

        $reviews = ArticleReview::with(['article.descriptions'])
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.article-reviews.index', compact('reviews', 'pageTitle', 'defaultLanguage'));
    }

    public function edit(string $id)
    {
        $pageTitle = 'Блог — отзыв';
        $review = ArticleReview::with('article.descriptions')->findOrFail($id);
        $defaultLanguage = Language::getDefault();

        return view('admin.article-reviews.edit', compact('review', 'pageTitle', 'defaultLanguage'));
    }

    public function update(Request $request, string $id)
    {
        $review = ArticleReview::findOrFail($id);

        $request->validate([
            'author' => 'required|string|max:64',
            'text' => 'required|string|min:3',
            'rating' => 'required|integer|min:1|max:5',
            'status' => 'nullable|boolean',
        ]);

        $review->update([
            'author' => $request->input('author'),
            'text' => $request->input('text'),
            'rating' => (int) $request->input('rating'),
            'status' => $request->boolean('status'),
        ]);

        return redirect()->route('admin.article-reviews.index')
            ->with('success', 'Отзыв сохранён');
    }

    public function destroy(string $id)
    {
        ArticleReview::findOrFail($id)->delete();

        return redirect()->route('admin.article-reviews.index')
            ->with('success', 'Отзыв удалён');
    }
}
