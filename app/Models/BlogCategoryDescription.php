<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogCategoryDescription extends Model
{
    protected $fillable = [
        'blog_category_id',
        'language_id',
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_h1',
        'meta_description',
        'meta_keyword',
    ];

    public function blogCategory(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
