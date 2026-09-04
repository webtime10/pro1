@php
    $d = $article->descriptions->firstWhere('language_id', $lang->id);
    $cat = $article->mainBlogCategory();
    $excerpt = $d ? \Illuminate\Support\Str::limit(trim(strip_tags($d->description ?? '')), $excerptLength) : '';
    $img = $article->image ? catalog_image_url($article->image) : null;
@endphp
@if($d)
    <a href="{{ catalog_article_url($article, $lang, $cat) }}" class="product-tile">
        <div class="thumb">
            @if($img)
                <img src="{{ $img }}" alt="{{ $d->name }}" loading="lazy">
            @else
                <span class="no-img">Нет фото</span>
            @endif
        </div>
        <div class="body">
            <h3>{{ $d->name }}</h3>
            @if($excerpt)
                <span class="muted">{{ $excerpt }}</span>
            @endif
            <span class="muted">{{ optional($article->date_available)->format('d.m.Y') }} · {{ $article->viewed }} просм.</span>
            @if($reviewStatus && !empty($article->reviews_avg_rating))
                <span class="muted">Оценка {{ number_format((float) $article->reviews_avg_rating, 1) }}</span>
            @endif
        </div>
    </a>
@endif
