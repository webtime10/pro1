<!DOCTYPE html>
<html lang="{{ $catalogLang->code ?? 'ru' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Каталог') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/catalog-ocean.css') }}?v=1">
    @stack('head')
</head>
<body class="theme-ocean">
<header class="site">
    <div class="inner">
        <h1><a href="{{ localized_route('catalog.index') }}">{{ config('app.name') }}</a></h1>
        <form class="site-search" id="site-search" action="{{ localized_route('catalog.search') }}"
              method="get" role="search" autocomplete="off"
              data-suggest-url="{{ localized_route('catalog.search.suggest') }}">
            <div class="site-search-field">
                <input type="text" name="q" id="site-search-q" value="{{ request('q') }}"
                       placeholder="Поиск товаров (от 2 букв)…" aria-label="Поиск"
                       aria-autocomplete="list" aria-controls="search-suggest" autocomplete="off">
            </div>
            <button type="submit">Найти</button>
        </form>
        <div class="header-nav">
            @if(($catalogLanguages ?? collect())->count() > 1)
                <div class="lang-switch" aria-label="Language">
                    @foreach($catalogLanguages as $switchLang)
                        <a href="{{ \App\Support\CatalogLocale::switchUrl($switchLang) }}"
                           class="{{ ($catalogLang?->id ?? null) === $switchLang->id ? 'is-active' : '' }}"
                           hreflang="{{ $switchLang->code }}">{{ $switchLang->code }}</a>
                    @endforeach
                </div>
            @endif
            <div
                class="cart-widget"
                data-cart-widget
                data-info-url="{{ localized_route('catalog.cart.info') }}"
                data-add-url="{{ localized_route('catalog.cart.add') }}"
                data-remove-url-template="{{ localized_route('catalog.cart.remove', ['cartId' => '__ID__']) }}"
            >
                <button type="button" class="cart-toggle" data-cart-toggle aria-expanded="false">
                    <span>Корзина</span>
                    <span class="cart-count" data-cart-count hidden>0</span>
                    <span data-cart-total>0.00 ₽</span>
                </button>
                <div class="cart-dropdown" data-cart-dropdown>
                    <div data-cart-empty class="muted" style="padding:.35rem 0;">Корзина пуста</div>
                    <div data-cart-list hidden></div>
                    <div data-cart-totals hidden style="margin-top:.5rem;border-top:1px solid var(--border);padding-top:.5rem;"></div>
                    <div class="cart-dd-actions">
                        <a class="btn-cart-link" href="{{ localized_route('catalog.cart') }}">Корзина</a>
                        <a class="btn-checkout-link" href="{{ localized_route('catalog.checkout') }}">Оформить</a>
                    </div>
                </div>
            </div>
            <nav>
                <a href="{{ localized_route('catalog.index') }}">Главная</a>
                @if(\App\Models\BlogSetting::bool('blog_menu', true))
                    <span class="muted"> · </span>
                    <a href="{{ catalog_blog_url() }}">{{ \App\Models\BlogSetting::getValue('name', 'Блог') }}</a>
                @endif
                @auth
                    <span class="muted"> · </span>
                    <a href="{{ url('/admin') }}">Админка</a>
                @else
                    <span class="muted"> · </span>
                    <a href="{{ url('/login') }}">Вход</a>
                @endauth
            </nav>
        </div>
    </div>
</header>
<div id="cart-toast" role="status"></div>
<div id="search-suggest" class="search-suggest" role="listbox"></div>
<div class="wrap">
    <div class="catalog-layout">
        <aside class="catalog-sidebar" aria-label="Сайдбар">
            @hasSection('sidebar')
                @yield('sidebar')
            @else
                <div class="cat-menu">
                    <h2>Категории</h2>
                    @include('catalog.partials.category-menu', ['items' => $menuCategories ?? collect()])
                </div>
            @endif
        </aside>
        <div class="catalog-main">
            @yield('content')
            <footer>
                {{ config('app.name') }}
            </footer>
        </div>
    </div>
</div>
<script src="{{ asset('js/catalog-search-suggest.js') }}?v=2" defer></script>
<script src="{{ asset('js/catalog-load-more.js') }}?v=1" defer></script>
<script src="{{ asset('js/catalog-cart.js') }}?v=1" defer></script>
@stack('scripts')
</body>
</html>
