@extends('catalog.layout')

@section('title', 'Корзина')

@section('content')
    <nav class="crumb">
        <a href="{{ localized_route('catalog.index') }}">Главная</a>
        <span> / </span>
        <span>Корзина</span>
    </nav>

    <h1 style="margin-top:0;">Корзина</h1>

    @if(session('success'))
        <p class="flash-ok">{{ session('success') }}</p>
    @endif

    @if($products->isEmpty())
        <p class="muted">Ваша корзина пуста.</p>
        <p><a href="{{ localized_route('catalog.index') }}">Продолжить покупки</a></p>
    @else
        <form action="{{ localized_route('catalog.cart.update') }}" method="post" id="cart-form">
            @csrf
            <div class="cart-table-wrap">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Товар</th>
                            <th>Модель</th>
                            <th>Кол-во</th>
                            <th>Цена</th>
                            <th>Сумма</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $item)
                            <tr>
                                <td>
                                    <div class="cart-product">
                                        @if($item['image'])
                                            <img src="{{ $item['image'] }}" alt="" width="56" height="56">
                                        @endif
                                        <div>
                                            @if(!empty($item['url']))
                                                <a href="{{ $item['url'] }}">{{ $item['name'] }}</a>
                                            @else
                                                {{ $item['name'] }}
                                            @endif
                                            @foreach($item['option'] as $opt)
                                                <div class="muted" style="font-size:.85rem;">{{ $opt['name'] }}: {{ $opt['value'] }}</div>
                                            @endforeach
                                            @unless($item['stock'])
                                                <div class="stock-out">*** Нет в наличии ***</div>
                                            @endunless
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $item['model'] }}</td>
                                <td>
                                    <input type="number" name="quantity[{{ $item['cart_id'] }}]"
                                           value="{{ $item['quantity'] }}" min="0"
                                           style="width:72px;padding:.35rem;">
                                </td>
                                <td>{{ number_format($item['price'], 2, '.', ' ') }} ₽</td>
                                <td>{{ number_format($item['total'], 2, '.', ' ') }} ₽</td>
                                <td>
                                    <button type="submit" form="cart-remove-{{ $item['cart_id'] }}"
                                            class="btn-link-danger" title="Удалить">✕</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="cart-actions">
                <button type="submit" class="btn-secondary">Обновить</button>
                <a href="{{ localized_route('catalog.checkout') }}" class="btn-cart" style="text-decoration:none;">Оформить заказ</a>
            </div>
        </form>

        @foreach($products as $item)
            <form id="cart-remove-{{ $item['cart_id'] }}"
                  action="{{ localized_route('catalog.cart.remove', $item['cart_id']) }}"
                  method="post" class="hidden-form">
                @csrf
                @method('DELETE')
            </form>
        @endforeach

        <div class="cart-totals">
            <table>
                @foreach($totals as $row)
                    <tr class="{{ $row['code'] === 'total' ? 'is-total' : '' }}">
                        <td>{{ $row['title'] }}</td>
                        <td>{{ number_format($row['value'], 2, '.', ' ') }} ₽</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
@endsection
