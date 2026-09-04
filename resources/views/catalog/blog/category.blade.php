@extends('catalog.layout')

@section('title', $metaTitle ?? $catDesc->name)

@section('sidebar')
    @include('catalog.blog._sidebar')
@endsection

@section('content')
    <nav class="crumb">
        <a href="{{ localized_route('catalog.index') }}">Главная</a>
        <span> / </span>
        <a href="{{ catalog_blog_url() }}">{{ \App\Models\BlogSetting::getValue('name', 'Блог') }}</a>
        <span> / </span>
        <span>{{ $catDesc->name }}</span>
    </nav>
    <h1 style="margin-top:0;">{{ $catDesc->meta_h1 ?: $catDesc->name }}</h1>
    @if($catDesc->description)
        <div class="product-meta" style="margin-bottom:1.25rem;">{!! $catDesc->description !!}</div>
    @endif
    @if($children->isNotEmpty())
        <ul class="subcat-list">
            @foreach($children as $child)
                @php $cd = $child->descriptions->firstWhere('language_id', $lang->id); @endphp
                @if($cd)
                    <li><a href="{{ catalog_blog_category_url($child, $lang) }}">{{ $cd->name }}</a></li>
                @endif
            @endforeach
        </ul>
    @endif
    <div class="product-grid">
        @forelse($articles as $article)
            @include('catalog.blog._card')
        @empty
            <p class="muted">В этой категории пока нет статей.</p>
        @endforelse
    </div>
    <div class="catalog-pager">{{ $articles->links() }}</div>
@endsection
