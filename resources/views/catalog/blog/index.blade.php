@extends('catalog.layout')

@section('title', $metaTitle ?? 'Блог')

@section('sidebar')
    @include('catalog.blog._sidebar')
@endsection

@section('content')
    <nav class="crumb">
        <a href="{{ localized_route('catalog.index') }}">Главная</a>
        <span> / </span>
        <span>{{ $heading }}</span>
    </nav>
    <h1 style="margin-top:0;">{{ $heading }}</h1>
    <div class="product-grid">
        @forelse($articles as $article)
            @include('catalog.blog._card')
        @empty
            <p class="muted">Пока нет статей.</p>
        @endforelse
    </div>
    <div class="catalog-pager">{{ $articles->links() }}</div>
@endsection
