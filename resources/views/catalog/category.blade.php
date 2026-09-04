@extends('catalog.layout')

@section('title', $catDesc->name)

@section('sidebar')
    @include('catalog.partials.wt-filter', $wtFilter)
    @if(($menuCategories ?? collect())->isNotEmpty())
        <div class="cat-menu" style="margin-top:1rem;">
            @include('catalog.partials.category-menu', ['items' => $menuCategories])
        </div>
    @endif
@endsection

@section('content')
    <nav class="crumb">
        <a href="{{ localized_route('catalog.index') }}">Главная</a>
        @foreach($breadcrumb as $i => $item)
            <span> / </span>
            @if($i < count($breadcrumb) - 1 && !empty($item['id']))
                <a href="{{ catalog_category_url($item['id']) }}">{{ $item['name'] }}</a>
            @else
                <span>{{ $item['name'] }}</span>
            @endif
        @endforeach
    </nav>

    <h1 style="margin-top:0;">{{ $catDesc->name }}</h1>
    @if($catDesc->description)
        <div class="product-meta" style="margin-bottom:1.25rem;">{!! $catDesc->description !!}</div>
    @endif

    @if($children->isNotEmpty())
        <ul class="subcat-list">
            @foreach($children as $child)
                @php $cd = $child->descriptions->firstWhere('language_id', $lang->id); @endphp
                @if($cd)
                    <li><a href="{{ catalog_category_url($child) }}">{{ $cd->name }}</a></li>
                @endif
            @endforeach
        </ul>
    @endif

    <h2 style="font-size:1.1rem;margin:0 0 1rem;">
        Товары
        <span class="muted" data-role="heading-total" style="font-weight:400;font-size:.95rem;">
            ({{ $products->total() }})
        </span>
    </h2>

    <div id="wt-filter-results">
        @include('catalog.partials.product-grid', ['products' => $products, 'lang' => $lang])
    </div>
@endsection
