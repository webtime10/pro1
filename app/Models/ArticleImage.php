<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleImage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'article_id',
        'image',
        'sort_order',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
