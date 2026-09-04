<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    protected $fillable = [
        'image',
        'date_available',
        'sort_order',
        'reviews_enabled',
        'status',
        'noindex',
        'viewed',
    ];

    protected $casts = [
        'date_available' => 'date',
        'reviews_enabled' => 'boolean',
        'status' => 'boolean',
        'noindex' => 'boolean',
    ];

    public function descriptions(): HasMany
    {
        return $this->hasMany(ArticleDescription::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ArticleImage::class)->orderBy('sort_order');
    }

    public function blogCategories(): BelongsToMany
    {
        return $this->belongsToMany(BlogCategory::class, 'article_blog_category')
            ->withPivot('main');
    }

    public function relatedArticles(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'article_related', 'article_id', 'related_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'article_product');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ArticleReview::class);
    }

    public function descriptionForLanguage(int $languageId): ?ArticleDescription
    {
        return $this->descriptions->firstWhere('language_id', $languageId);
    }

    public function mainBlogCategory(): ?BlogCategory
    {
        return $this->blogCategories->firstWhere('pivot.main', true)
            ?? $this->blogCategories->first();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', true)
            ->where(function (Builder $q) {
                $q->whereNull('date_available')
                    ->orWhereDate('date_available', '<=', now()->toDateString());
            });
    }
}
