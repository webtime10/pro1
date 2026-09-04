@extends('catalog.layout')

@section('title', $prodDesc->name)

@section('content')
    @php
        $img = $product->image
            ? (str_starts_with($product->image, 'http')
                ? $product->image
                : asset('storage/'.ltrim($product->image, '/')))
            : null;
        $gallery = collect();
        if ($img) {
            $gallery->push(['src' => $img, 'path' => $product->image]);
        }
        foreach ($product->images as $extra) {
            if ($extra->image === '' || $extra->image === $product->image) {
                continue;
            }
            $gallery->push([
                'src' => catalog_image_url($extra->image),
                'path' => $extra->image,
            ]);
        }
    @endphp

    <nav class="crumb">
        <a href="{{ localized_route('catalog.index') }}">Главная</a>
        @foreach($product->categories->take(1) as $cat)
            @php $cd = $cat->descriptions->firstWhere('language_id', $lang->id); @endphp
            @if($cd)
                <span> / </span>
                <a href="{{ catalog_category_url($cat) }}">{{ $cd->name }}</a>
            @endif
        @endforeach
        <span> / </span>
        <span>{{ $prodDesc->name }}</span>
    </nav>

    <article class="product-page">
        <div class="product-hero">
            <div class="product-gallery">
                <div class="product-image">
                    @if($gallery->isNotEmpty())
                        <img id="product-main-image" src="{{ $gallery->first()['src'] }}" alt="{{ $prodDesc->name }}">
                    @else
                        <span class="muted">Нет изображения</span>
                    @endif
                </div>
                @if($gallery->count() > 1)
                    <div class="product-thumbs" id="product-thumbs">
                        @foreach($gallery as $i => $item)
                            <button type="button" class="{{ $i === 0 ? 'is-active' : '' }}" data-src="{{ $item['src'] }}">
                                <img src="{{ $item['src'] }}" alt="">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="product-buy">
                <h1>{{ $prodDesc->name }}</h1>
                <p class="price-lg">{{ number_format((float) $product->price, 2, '.', ' ') }} ₽</p>

                <p class="muted" style="margin:0 0 .35rem;">
                    Модель: <strong>{{ $product->model }}</strong>
                    @if($product->sku)
                        · SKU: <strong>{{ $product->sku }}</strong>
                    @endif
                </p>
                @if($product->manufacturer)
                    <p class="muted" style="margin:0 0 .75rem;">Производитель: {{ $product->manufacturer->name }}</p>
                @endif

                <p style="margin:0 0 1rem;">
                    @if($product->quantity > 0)
                        <span class="stock-ok">✓ В наличии ({{ $product->quantity }})</span>
                    @else
                        <span class="stock-out">✗ Нет на складе</span>
                    @endif
                </p>

                @if($product->productOptions->isNotEmpty())
                    <form class="product-options" data-add-to-cart data-product-id="{{ $product->id }}">
                        @csrf
                        @foreach($product->productOptions->sortBy(fn ($po) => $po->option?->sort_order ?? 0) as $po)
                            @php
                                $optName = $po->option?->descriptions->firstWhere('language_id', $lang->id)?->name
                                    ?? ('Опция #'.$po->option_id);
                                $type = $po->option?->type ?? 'select';
                                $req = $po->required ? ' *' : '';
                            @endphp
                            <div class="option-row">
                                <label class="opt-label">{{ $optName }}{{ $req }}</label>

                                @if(in_array($type, ['select'], true))
                                    <select name="option[{{ $po->id }}]" @required($po->required)>
                                        <option value="">--- Выберите ---</option>
                                        @foreach($po->values as $pov)
                                            @php
                                                $valName = $pov->optionValue?->descriptions->firstWhere('language_id', $lang->id)?->name
                                                    ?? ('#'.$pov->option_value_id);
                                                $priceHint = '';
                                                if ((float) $pov->price != 0.0) {
                                                    $priceHint = ' ('.$pov->price_prefix.number_format((float) $pov->price, 2, '.', ' ').')';
                                                }
                                            @endphp
                                            <option value="{{ $pov->id }}">{{ $valName }}{{ $priceHint }}</option>
                                        @endforeach
                                    </select>
                                @elseif(in_array($type, ['radio'], true))
                                    <div class="option-choices">
                                        @foreach($po->values as $pov)
                                            @php
                                                $valName = $pov->optionValue?->descriptions->firstWhere('language_id', $lang->id)?->name
                                                    ?? ('#'.$pov->option_value_id);
                                                $priceHint = '';
                                                if ((float) $pov->price != 0.0) {
                                                    $priceHint = ' ('.$pov->price_prefix.number_format((float) $pov->price, 2, '.', ' ').')';
                                                }
                                                $swatch = catalog_option_swatch(
                                                    $pov->optionValue?->color,
                                                    $pov->optionValue?->image,
                                                    $valName
                                                );
                                            @endphp
                                            <label>
                                                <input type="radio" name="option[{{ $po->id }}]" value="{{ $pov->id }}"
                                                       @required($po->required)>
                                                @include('catalog.partials.option-swatch', $swatch)
                                                {{ $valName }}{{ $priceHint }}
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif(in_array($type, ['checkbox'], true))
                                    <div class="option-choices">
                                        @foreach($po->values as $pov)
                                            @php
                                                $valName = $pov->optionValue?->descriptions->firstWhere('language_id', $lang->id)?->name
                                                    ?? ('#'.$pov->option_value_id);
                                                $priceHint = '';
                                                if ((float) $pov->price != 0.0) {
                                                    $priceHint = ' ('.$pov->price_prefix.number_format((float) $pov->price, 2, '.', ' ').')';
                                                }
                                                $swatch = catalog_option_swatch(
                                                    $pov->optionValue?->color,
                                                    $pov->optionValue?->image,
                                                    $valName
                                                );
                                            @endphp
                                            <label>
                                                <input type="checkbox" name="option[{{ $po->id }}][]" value="{{ $pov->id }}">
                                                @include('catalog.partials.option-swatch', $swatch)
                                                {{ $valName }}{{ $priceHint }}
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif($type === 'textarea')
                                    <textarea name="option[{{ $po->id }}]" rows="3" @required($po->required)>{{ $po->value }}</textarea>
                                @else
                                    <input type="text" name="option[{{ $po->id }}]" value="{{ $po->value }}" @required($po->required)>
                                @endif
                            </div>
                        @endforeach

                        <div class="option-row">
                            <label class="opt-label">Количество</label>
                            <input type="number" name="quantity" value="{{ max(1, (int) $product->minimum) }}"
                                   min="{{ max(1, (int) $product->minimum) }}" style="max-width:100px;">
                        </div>

                        <button type="submit" class="btn-cart">В корзину</button>
                    </form>
                @else
                    <form class="product-options" data-add-to-cart data-product-id="{{ $product->id }}">
                        @csrf
                        <div class="option-row">
                            <label class="opt-label">Количество</label>
                            <input type="number" name="quantity" value="{{ max(1, (int) $product->minimum) }}"
                                   min="{{ max(1, (int) $product->minimum) }}" style="max-width:100px;">
                        </div>
                        <button type="submit" class="btn-cart">В корзину</button>
                    </form>
                @endif
            </div>
        </div>

        @if($prodDesc->description)
            <div class="panel">
                <div class="panel-h">Описание</div>
                <div class="panel-b">{!! $prodDesc->description !!}</div>
            </div>
        @endif

        @if($attributeGroups->isNotEmpty())
            <div class="panel">
                <div class="panel-h">Характеристики</div>
                <div class="panel-b">
                    @foreach($attributeGroups as $group)
                        <div class="attr-group">{{ $group['name'] }}</div>
                        <table class="attr-table">
                            <tbody>
                                @foreach($group['rows'] as $row)
                                    <tr>
                                        <th>{{ $row['name'] }}</th>
                                        <td>{{ $row['text'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endforeach
                </div>
            </div>
        @endif

        @if($product->weight > 0 || $product->length > 0)
            <div class="panel">
                <div class="panel-h">Габариты</div>
                <div class="panel-b muted">
                    @if($product->weight > 0)
                        Вес: {{ rtrim(rtrim(number_format((float) $product->weight, 4, '.', ''), '0'), '.') }}
                    @endif
                    @if($product->length > 0 || $product->width > 0 || $product->height > 0)
                        · Размер (Д×Ш×В):
                        {{ rtrim(rtrim(number_format((float) $product->length, 2, '.', ''), '0'), '.') }}
                        × {{ rtrim(rtrim(number_format((float) $product->width, 2, '.', ''), '0'), '.') }}
                        × {{ rtrim(rtrim(number_format((float) $product->height, 2, '.', ''), '0'), '.') }}
                    @endif
                </div>
            </div>
        @endif
    </article>
@endsection

@push('scripts')
<script>
(function () {
    var main = document.getElementById('product-main-image');
    var thumbs = document.getElementById('product-thumbs');
    if (!main || !thumbs) return;
    thumbs.addEventListener('click', function (e) {
        var btn = e.target.closest('button[data-src]');
        if (!btn) return;
        main.src = btn.getAttribute('data-src');
        thumbs.querySelectorAll('button').forEach(function (el) { el.classList.remove('is-active'); });
        btn.classList.add('is-active');
    });
})();
</script>
@endpush
