<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogSetting;
use Illuminate\Http\Request;

class BlogSettingController extends Controller
{
    public function edit()
    {
        $pageTitle = 'Блог — настройки';
        $settings = BlogSetting::allCached();

        return view('admin.blog-settings.edit', compact('pageTitle', 'settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:64',
            'html_h1' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'meta_keyword' => 'nullable|string|max:255',
            'article_limit' => 'required|integer|min:1|max:100',
            'article_description_length' => 'required|integer|min:20|max:2000',
            'blog_menu' => 'nullable|boolean',
            'review_status' => 'nullable|boolean',
            'review_guest' => 'nullable|boolean',
        ]);

        BlogSetting::putMany([
            'name' => $request->input('name'),
            'html_h1' => $request->input('html_h1', ''),
            'meta_title' => $request->input('meta_title', ''),
            'meta_description' => $request->input('meta_description', ''),
            'meta_keyword' => $request->input('meta_keyword', ''),
            'article_limit' => (int) $request->input('article_limit'),
            'article_description_length' => (int) $request->input('article_description_length'),
            'blog_menu' => $request->boolean('blog_menu'),
            'review_status' => $request->boolean('review_status'),
            'review_guest' => $request->boolean('review_guest'),
        ]);

        return redirect()->route('admin.blog-settings.edit')
            ->with('success', 'Настройки блога сохранены');
    }
}
