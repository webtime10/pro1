@extends('catalog.layout')

@section('title', $metaTitle ?? $desc->name)

@section('sidebar')
    @include('catalog.blog._sidebar')
@endsection

@section('content')
    <nav class="crumb">
        <a href="{{ localized_route('catalog.index') }}">Главная</a>
        <span> / </span>
        <a href="{{ catalog_blog_url() }}">{{ \App\Models\BlogSetting::getValue('name', 'Блог') }}</a>
        @if($category && $catDesc)
            <span> / </span>
            <a href="{{ catalog_blog_category_url($category, $lang) }}">{{ $catDesc->name }}</a>
        @endif
        <span> / </span>
        <span>{{ $desc->name }}</span>
    </nav>

    @if(session('success'))
        <div class="flash-ok" style="margin-bottom:1rem;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash-err">{{ session('error') }}</div>
    @endif

    <article>
        <h1 style="margin-top:0;">{{ $desc->meta_h1 ?: $desc->name }}</h1>
        <p class="muted">{{ optional($article->date_available)->format('d.m.Y') }} · {{ $article->viewed }} просмотров
            @if($avgRating)
                · оценка {{ number_format((float) $avgRating, 1) }}
            @endif
        </p>

        @if($article->image)
            <div class="product-image" style="max-width:640px;margin:1rem 0;aspect-ratio:auto;">
                <img src="{{ catalog_image_url($article->image) }}" alt="{{ $desc->name }}">
            </div>
        @endif
        @if($article->images->isNotEmpty())
            <div class="product-thumbs" style="margin-bottom:1rem;">
                @foreach($article->images as $img)
                    <img src="{{ catalog_image_url($img->image) }}" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:6px;">
                @endforeach
            </div>
        @endif

        <div class="product-meta">{!! $desc->description !!}</div>
    </article>

    @if($article->relatedArticles->isNotEmpty())
        <h2 style="font-size:1.1rem;">Похожие статьи</h2>
        <div class="product-grid">
            @foreach($article->relatedArticles as $rel)
                @include('catalog.blog._card', ['article' => $rel, 'excerptLength' => 120, 'reviewStatus' => false])
            @endforeach
        </div>
    @endif

    @if($article->products->isNotEmpty())
        <h2 style="font-size:1.1rem;">Товары</h2>
        <div class="product-grid">
            @include('catalog.partials.product-tiles', ['products' => $article->products, 'lang' => $lang])
        </div>
    @endif

    @if($reviewStatus)
        <h2 style="font-size:1.1rem;">Отзывы ({{ $reviews->count() }})</h2>
        @forelse($reviews as $review)
            <div class="card" style="margin-bottom:.75rem;">
                <strong>{{ $review->author }}</strong>
                <span class="muted"> · {{ $review->rating }}/5 · {{ $review->created_at?->format('d.m.Y') }}</span>
                <p style="margin:.5rem 0 0;">{{ $review->text }}</p>
            </div>
        @empty
            <p class="muted">Пока нет отзывов.</p>
        @endforelse

        @if($reviewGuest)
            <h3 style="font-size:1rem;">Написать отзыв</h3>
            <form method="post" action="{{ localized_route('catalog.blog.review', ['articleSlug' => $desc->slug]) }}" class="card">
                @csrf
                <div class="checkout-grid">
                    <label>Имя
                        <input type="text" name="author" required maxlength="64" value="{{ old('author') }}">
                    </label>
                    <label>Оценка
                        <select name="rating">
                            @for($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}" {{ (int) old('rating', 5) === $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </label>
                    <label class="full">Текст
                        <textarea name="text" rows="4" required>{{ old('text') }}</textarea>
                    </label>
                </div>
                <button type="submit" class="btn-cart" style="margin-top:1rem;">Отправить</button>
            </form>
        @endif
    @endif
@endsection
