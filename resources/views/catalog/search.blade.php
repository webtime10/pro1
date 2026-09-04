@extends('catalog.layout')

@section('title', $q ? 'Поиск: '.$q : 'Поиск')

@section('content')
    <nav class="crumb">
        <a href="{{ localized_route('catalog.index') }}">Главная</a>
        <span> / </span>
        <span>Поиск</span>
    </nav>

    <h1 style="margin-top:0;">Поиск товаров</h1>

    @if(mb_strlen($q) < 2)
        <p class="muted">Введите минимум 2 символа в строку поиска в шапке сайта.</p>
    @else
        <p class="muted">По запросу «{{ $q }}» найдено: {{ $products->total() }}</p>
        @include('catalog.partials.product-grid', ['products' => $products, 'lang' => $lang])
    @endif
@endsection
