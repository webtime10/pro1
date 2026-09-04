<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleDescription extends Model
{
    protected $fillable = [
        'article_id',
        'language_id',
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_h1',
        'meta_description',
        'meta_keyword',
        'tag',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
