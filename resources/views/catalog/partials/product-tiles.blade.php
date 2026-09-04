@foreach($products as $product)
    @php
        $pd = $product->descriptions->firstWhere('language_id', $lang->id);
        $img = $product->image
            ? (str_starts_with($product->image, 'http')
                ? $product->image
                : asset('storage/'.ltrim($product->image, '/')))
            : null;
    @endphp
    @if($pd)
        <a href="{{ catalog_product_url($product, $lang, $category ?? null) }}" class="product-tile">
            <div class="thumb">
                @if($img)
                    <img src="{{ $img }}" alt="{{ $pd->name }}"
                         width="400" height="400"
                         loading="lazy"
                         decoding="async">
                @else
                    <span class="no-img">Нет фото</span>
                @endif
            </div>
            <div class="body">
                <h3>{{ $pd->name }}</h3>
                @if($product->manufacturer)
                    <span class="muted">{{ $product->manufacturer->name }}</span>
                @endif
                <span class="price">{{ number_format((float) $product->price, 2, '.', ' ') }} ₽</span>
            </div>
        </a>
    @endif
@endforeach
