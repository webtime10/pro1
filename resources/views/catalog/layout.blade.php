<!DOCTYPE html>
<html lang="{{ $catalogLang->code ?? 'ru' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Каталог') — {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; margin: 0; background: #f4f6f9; color: #222; line-height: 1.5; }
        a { color: #0d6efd; text-decoration: none; }
        a:hover { text-decoration: underline; }
        header.site {
            background: #fff; border-bottom: 1px solid #dee2e6;
            position: relative; z-index: 200; overflow: visible;
        }
        header.site .inner {
            max-width: 1180px; margin: 0 auto; padding: 0.75rem 1.25rem;
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
            overflow: visible;
        }
        header.site h1 { margin: 0; font-size: 1.25rem; }
        .wrap { max-width: 1180px; margin: 0 auto; padding: 1.25rem 1.25rem 2rem; }
        .catalog-layout { display: grid; grid-template-columns: 280px 1fr; gap: 1.5rem; align-items: start; }
        @media (max-width: 991px) {
            .catalog-layout { grid-template-columns: 1fr; }
        }
        .catalog-sidebar { min-width: 0; }
        .catalog-sidebar .wt-filter {
          position: sticky;
          top: .75rem;
          max-height: calc(100vh - 1.5rem);
          display: flex;
          flex-direction: column;
          overflow: hidden;
        }
        .catalog-sidebar .wt-filter__drawer {
          display: flex;
          flex-direction: column;
          min-height: 0;
          max-height: calc(100vh - 1.5rem);
          overflow: hidden;
        }
        .catalog-sidebar .wt-filter__scroll {
          flex: 1 1 auto;
          min-height: 0;
          overflow: auto;
        }
        .catalog-sidebar .wt-filter__actions--desktop {
          display: block;
          flex: 0 0 auto;
          margin: 0;
          padding: 10px 15px 15px;
          border-top: 1px solid #eee;
          background: #fff;
        }
        /* Пока drawer ≤991 из OC-стилей прячет фильтр — в сайдбаре оставляем видимым до телефона */
        @media (max-width: 991px) and (min-width: 576px) {
          .catalog-sidebar .wt-filter,
          .catalog-sidebar #wt-filter {
            position: sticky !important;
            inset: auto !important;
            visibility: visible !important;
            pointer-events: auto !important;
            z-index: 1 !important;
            background: #fff !important;
            border: 1px solid #dee2e6 !important;
          }
          .catalog-sidebar .wt-filter__drawer {
            position: static !important;
            transform: none !important;
            width: 100% !important;
            max-width: 100% !important;
            box-shadow: none !important;
            display: flex !important;
            flex-direction: column !important;
          }
          .catalog-sidebar .wt-filter-fab,
          .catalog-sidebar .wt-filter__backdrop,
          .catalog-sidebar .wt-filter__m-header,
          .catalog-sidebar .wt-filter__m-footer {
            display: none !important;
          }
          .catalog-sidebar .wt-filter__heading--desktop,
          .catalog-sidebar .wt-filter__status--desktop,
          .catalog-sidebar .wt-filter__actions--desktop {
            display: block !important;
          }
          .catalog-sidebar .wt-filter__scroll {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            overflow: auto !important;
            max-height: none !important;
          }
          .catalog-sidebar .wt-filter,
          .catalog-sidebar .wt-filter__drawer {
            max-height: calc(100vh - 1.5rem) !important;
            overflow: hidden !important;
          }
        }
        .cat-menu {
            background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: .75rem 0;
            position: sticky; top: .75rem;
        }
        .cat-menu h2 {
            margin: 0; padding: .35rem 1rem .75rem; font-size: .85rem; text-transform: uppercase;
            letter-spacing: .04em; color: #6c757d; border-bottom: 1px solid #f1f3f5;
        }
        .cat-menu ul { list-style: none; margin: 0; padding: 0; }
        .cat-menu > ul > li > a {
            display: block; padding: .45rem 1rem; color: #212529; font-weight: 600; font-size: .95rem;
        }
        .cat-menu > ul > li > a:hover,
        .cat-menu a.is-active { background: #e7f1ff; color: #0d6efd; text-decoration: none; }
        .cat-menu .sub { padding: 0 0 .35rem 0; }
        .cat-menu .sub a {
            display: block; padding: .3rem 1rem .3rem 1.6rem; color: #495057; font-size: .88rem; font-weight: 400;
        }
        .cat-menu .sub .sub a { padding-left: 2.2rem; font-size: .84rem; color: #6c757d; }
        .catalog-main { min-width: 0; }
        .crumb { font-size: 0.9rem; color: #555; margin-bottom: 1rem; }
        .crumb a { color: #555; }
        .product-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;
        }
        .product-tile {
            display: flex; flex-direction: column; background: #fff; border: 1px solid #dee2e6;
            border-radius: 8px; overflow: hidden; color: inherit; text-decoration: none;
            transition: box-shadow .15s, transform .15s;
        }
        .product-tile:hover { box-shadow: 0 6px 18px rgba(0,0,0,.1); transform: translateY(-2px); text-decoration: none; color: inherit; }
        .product-tile .thumb {
            position: relative;
            aspect-ratio: 1 / 1;
            width: 100%;
            background: #eef1f4;
            overflow: hidden;
        }
        .product-tile .thumb img {
            position: absolute; inset: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            display: block;
        }
        .product-tile .thumb .no-img {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            color: #adb5bd; font-size: .85rem;
        }
        .product-tile .body { padding: .85rem 1rem 1rem; flex: 1; display: flex; flex-direction: column; gap: .35rem; }
        .product-tile h3 { margin: 0; font-size: .98rem; line-height: 1.3; font-weight: 600; }
        .product-tile .price { margin-top: auto; font-weight: 700; color: #198754; font-size: 1.05rem; }
        .card { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 1rem; }
        .card h2, .card h3 { margin: 0 0 .5rem; font-size: 1.05rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
        .price { font-weight: 600; color: #198754; font-size: 1.1rem; }
        .muted { color: #6c757d; font-size: .9rem; }
        .panel {
            background: #fff; border: 1px solid #dee2e6; border-radius: 8px; margin: 1rem 0;
        }
        .panel-h {
            padding: .65rem 1rem; border-bottom: 1px solid #dee2e6; font-weight: 600; background: #f8f9fa;
            border-radius: 8px 8px 0 0;
        }
        .panel-b { padding: 1rem; }
        .attr-table { width: 100%; border-collapse: collapse; }
        .attr-table th, .attr-table td { padding: .5rem .65rem; border-bottom: 1px solid #eee; text-align: left; vertical-align: top; }
        .attr-table th { width: 35%; color: #6c757d; font-weight: 500; }
        .attr-group { margin: 0 0 1rem; font-size: .85rem; text-transform: uppercase; letter-spacing: .03em; color: #495057; }
        .product-hero { display: grid; grid-template-columns: minmax(240px, 380px) 1fr; gap: 1.5rem; }
        @media (max-width: 760px) { .product-hero { grid-template-columns: 1fr; } }
        .product-image {
            background: #fff; border: 1px solid #dee2e6; border-radius: 8px; aspect-ratio: 1;
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .product-image img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .product-gallery { display: flex; flex-direction: column; gap: .65rem; }
        .product-thumbs { display: flex; flex-wrap: wrap; gap: .4rem; }
        .product-thumbs button {
            width: 58px; height: 58px; padding: 3px;
            border: 1px solid #dee2e6; border-radius: 6px;
            background: #fff; cursor: pointer;
        }
        .product-thumbs button.is-active { border-color: #229ac8; }
        .product-thumbs img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .product-buy h1 { margin: 0 0 .75rem; font-size: 1.6rem; }
        .product-buy .price-lg { font-size: 1.6rem; font-weight: 700; color: #198754; margin: 0 0 1rem; }
        .option-row { margin: 0 0 1rem; }
        .option-row label.opt-label { display: block; font-weight: 600; margin-bottom: .35rem; }
        .option-row select, .option-row input[type=text], .option-row textarea {
            width: 100%; max-width: 360px; padding: .45rem .6rem; border: 1px solid #ced4da; border-radius: 6px;
        }
        .option-choices { display: flex; flex-wrap: wrap; gap: .5rem .85rem; }
        .option-choices label { display: inline-flex; align-items: center; gap: .35rem; font-size: .92rem; }
        .opt-swatch {
            flex: 0 0 20px; width: 20px; height: 20px; border-radius: 2px;
            border: 1px solid rgba(0,0,0,.2); box-sizing: border-box; overflow: hidden;
            display: inline-block; vertical-align: middle;
        }
        .opt-swatch--img { padding: 0; background: #f3f3f3; }
        .opt-swatch--img img { display: block; width: 20px; height: 20px; object-fit: cover; }
        .btn-cart {
            display: inline-block; margin-top: .5rem; padding: .65rem 1.25rem; background: #198754; color: #fff;
            border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; font-weight: 600;
        }
        .btn-cart:hover { background: #157347; }
        .stock-ok { color: #198754; }
        .stock-out { color: #dc3545; }
        .product-meta { margin: 1rem 0; padding: 1rem; background: #fff; border-radius: 8px; border: 1px solid #dee2e6; }
        footer { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #dee2e6; font-size: .85rem; color: #888; }
        .site-search { display: flex; gap: .5rem; flex: 1; max-width: 420px; align-items: stretch; }
        .site-search-field { position: relative; flex: 1; min-width: 0; }
        .site-search input {
            width: 100%; padding: .45rem .65rem; border: 1px solid #ced4da; border-radius: 6px; font-size: .95rem;
        }
        .site-search button { padding: .45rem .85rem; background: #0d6efd; color: #fff; border: none; border-radius: 6px; cursor: pointer; }
        .search-suggest {
            display: none; position: fixed; z-index: 99999; background: #fff; border: 1px solid #adb5bd;
            border-radius: 8px; box-shadow: 0 12px 28px rgba(0,0,0,.2); max-height: 360px; overflow: auto;
        }
        .search-suggest.is-open { display: block; }
        .search-suggest a {
            display: flex; justify-content: space-between; gap: .75rem; align-items: baseline;
            padding: .65rem .85rem; color: #212529; text-decoration: none; border-bottom: 1px solid #f1f3f5;
        }
        .search-suggest a:last-child { border-bottom: 0; }
        .search-suggest a:hover, .search-suggest a.is-active { background: #e7f1ff; }
        .search-suggest .sg-name { font-size: .95rem; }
        .search-suggest .sg-meta { font-size: .82rem; color: #198754; font-weight: 600; white-space: nowrap; }
        .search-suggest .sg-empty { padding: .85rem; color: #6c757d; font-size: .9rem; }
        .catalog-pager { margin: 2rem 0 1rem; }
        .pagination-meta { margin-bottom: .75rem; font-size: .9rem; }
        .pagination-wrap { margin: 0 0 1rem; }
        .pagination-wrap .pagination {
            display: flex; flex-wrap: wrap; gap: .35rem; list-style: none; margin: 0; padding: 0;
        }
        .pagination-wrap .page-item .page-link,
        .pagination-wrap .page-item span.page-link {
            display: inline-block; padding: .35rem .65rem; border: 1px solid #dee2e6; border-radius: 4px;
            background: #fff; font-size: .9rem; color: #0d6efd; text-decoration: none; line-height: 1.25;
        }
        .pagination-wrap .page-item.active .page-link {
            background: #0d6efd; color: #fff; border-color: #0d6efd;
        }
        .pagination-wrap .page-item.disabled .page-link {
            color: #6c757d; background: #f8f9fa; pointer-events: none;
        }
        .pagination-wrap .page-item .page-link:hover {
            background: #e7f1ff; text-decoration: none;
        }
        .load-more-wrap { display: flex; justify-content: center; margin-top: .25rem; }
        .btn-load-more {
            display: inline-block; min-width: 200px; padding: .65rem 1.25rem;
            background: #fff; color: #0d6efd; border: 1px solid #0d6efd; border-radius: 6px;
            font-size: .95rem; font-weight: 600; cursor: pointer;
        }
        .btn-load-more:hover { background: #e7f1ff; }
        .btn-load-more:disabled { opacity: .65; cursor: wait; }
        .subcat-list { list-style: none; margin: 0 0 1.25rem; padding: 0; display: flex; flex-wrap: wrap; gap: .5rem; }
        .subcat-list a {
            display: inline-block; padding: .35rem .75rem; background: #fff; border: 1px solid #dee2e6;
            border-radius: 999px; color: #212529; font-size: .9rem;
        }
        .subcat-list a:hover { border-color: #0d6efd; color: #0d6efd; text-decoration: none; }
        .header-nav { display: flex; align-items: center; gap: .75rem; }
        .lang-switch { display: flex; gap: .35rem; align-items: center; font-size: .85rem; }
        .lang-switch a {
            color: #6c757d; text-decoration: none; padding: .2rem .4rem; border-radius: 4px;
            border: 1px solid transparent; text-transform: uppercase;
        }
        .lang-switch a:hover { color: #0d6efd; text-decoration: none; }
        .lang-switch a.is-active {
            color: #0d6efd; border-color: #0d6efd; font-weight: 700;
        }
        .cart-widget { position: relative; }
        .cart-toggle {
            display: inline-flex; align-items: center; gap: .45rem; padding: .4rem .7rem;
            border: 1px solid #dee2e6; border-radius: 6px; background: #fff; color: #212529;
            font-size: .9rem; cursor: pointer; text-decoration: none;
        }
        .cart-toggle:hover { border-color: #0d6efd; color: #0d6efd; text-decoration: none; }
        .cart-count {
            min-width: 1.25rem; height: 1.25rem; padding: 0 .35rem; border-radius: 999px;
            background: #dc3545; color: #fff; font-size: .75rem; font-weight: 700;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .cart-dropdown {
            display: none; position: absolute; right: 0; top: calc(100% + .45rem); width: min(360px, 92vw);
            background: #fff; border: 1px solid #adb5bd; border-radius: 8px;
            box-shadow: 0 12px 28px rgba(0,0,0,.18); z-index: 300; padding: .75rem;
        }
        .cart-widget.is-open .cart-dropdown { display: block; }
        .cart-dd-item {
            display: flex; justify-content: space-between; gap: .5rem; align-items: flex-start;
            padding: .55rem 0; border-bottom: 1px solid #f1f3f5;
        }
        .cart-dd-main { display: flex; gap: .55rem; min-width: 0; }
        .cart-dd-main img { width: 44px; height: 44px; object-fit: cover; border-radius: 4px; background: #eef1f4; }
        .cart-dd-remove {
            border: 0; background: transparent; color: #dc3545; cursor: pointer; font-size: 1rem; line-height: 1;
        }
        .cart-dd-total-line {
            display: flex; justify-content: space-between; gap: .75rem; padding: .25rem 0; font-size: .92rem;
        }
        .cart-dd-actions { display: flex; gap: .5rem; margin-top: .75rem; }
        .cart-dd-actions a {
            flex: 1; text-align: center; padding: .5rem .65rem; border-radius: 6px; font-size: .9rem; font-weight: 600;
            text-decoration: none;
        }
        .cart-dd-actions .btn-cart-link { background: #f8f9fa; color: #212529; border: 1px solid #dee2e6; }
        .cart-dd-actions .btn-checkout-link { background: #198754; color: #fff; }
        #cart-toast {
            position: fixed; right: 1rem; bottom: 1rem; z-index: 9999; background: #198754; color: #fff;
            padding: .75rem 1rem; border-radius: 8px; box-shadow: 0 8px 20px rgba(0,0,0,.18);
            opacity: 0; pointer-events: none; transition: opacity .2s;
        }
        #cart-toast.is-show { opacity: 1; }
        .flash-ok { background: #d1e7dd; color: #0f5132; padding: .65rem .85rem; border-radius: 6px; }
        .flash-err { background: #f8d7da; color: #842029; padding: .65rem .85rem; border-radius: 6px; margin-bottom: 1rem; }
        .cart-table-wrap { overflow-x: auto; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; }
        .cart-table { width: 100%; border-collapse: collapse; }
        .cart-table th, .cart-table td { padding: .75rem; border-bottom: 1px solid #eee; text-align: left; vertical-align: top; }
        .cart-table th { background: #f8f9fa; font-size: .85rem; }
        .cart-product { display: flex; gap: .75rem; align-items: flex-start; }
        .cart-product img { width: 56px; height: 56px; object-fit: cover; border-radius: 4px; background: #eef1f4; }
        .cart-actions { display: flex; gap: .75rem; flex-wrap: wrap; margin: 1rem 0; align-items: center; }
        .btn-secondary {
            display: inline-block; padding: .65rem 1.1rem; background: #fff; color: #212529;
            border: 1px solid #ced4da; border-radius: 6px; font-weight: 600; cursor: pointer;
        }
        .btn-link-danger { border: 0; background: transparent; color: #dc3545; cursor: pointer; font-size: 1.1rem; }
        .hidden-form { display: none; }
        .cart-totals { display: flex; justify-content: flex-end; margin-top: 1rem; }
        .cart-totals table { min-width: 280px; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; border-collapse: collapse; }
        .cart-totals td { padding: .55rem .85rem; }
        .cart-totals tr.is-total td { font-weight: 700; border-top: 1px solid #dee2e6; }
        .checkout-layout { display: grid; grid-template-columns: 1fr 320px; gap: 1.25rem; align-items: start; }
        @media (max-width: 900px) { .checkout-layout { grid-template-columns: 1fr; } }
        .checkout-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .85rem; }
        .checkout-grid label { display: flex; flex-direction: column; gap: .3rem; font-size: .9rem; font-weight: 600; }
        .checkout-grid label.full { grid-column: 1 / -1; }
        .checkout-grid input, .checkout-grid select, .checkout-grid textarea {
            font-weight: 400; padding: .45rem .6rem; border: 1px solid #ced4da; border-radius: 6px; font-size: .95rem;
        }
        .checkout-line { display: flex; justify-content: space-between; gap: .75rem; margin-bottom: .65rem; font-size: .92rem; }
        .checkout-line.is-total { font-weight: 700; margin-top: .35rem; }
        @media (max-width: 640px) { .checkout-grid { grid-template-columns: 1fr; } }
    </style>
    @stack('head')
</head>
<body>
<header class="site">
    <div class="inner">
        <h1><a href="{{ localized_route('catalog.index') }}" style="color:inherit;">{{ config('app.name') }}</a></h1>
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
                    <div data-cart-totals hidden style="margin-top:.5rem;border-top:1px solid #eee;padding-top:.5rem;"></div>
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
