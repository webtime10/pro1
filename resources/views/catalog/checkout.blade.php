@extends('catalog.layout')

@section('title', 'Оформление заказа')

@section('content')
    <nav class="crumb">
        <a href="{{ localized_route('catalog.index') }}">Главная</a>
        <span> / </span>
        <a href="{{ localized_route('catalog.cart') }}">Корзина</a>
        <span> / </span>
        <span>Оформление</span>
    </nav>

    <h1 style="margin-top:0;">Оформление заказа</h1>
    <p class="muted">Гостевой заказ (как checkout в OpenCart): данные покупателя, адрес, подтверждение.</p>

    @if($errors->any())
        <div class="flash-err">
            <ul style="margin:0;padding-left:1.1rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="checkout-layout">
        <form class="checkout-form" method="post" action="{{ localized_route('catalog.checkout.confirm') }}">
            @csrf
            <div class="panel">
                <div class="panel-h">1. Ваши данные</div>
                <div class="panel-b checkout-grid">
                    <label>Имя *
                        <input type="text" name="firstname" value="{{ old('firstname') }}" required maxlength="32">
                    </label>
                    <label>Фамилия *
                        <input type="text" name="lastname" value="{{ old('lastname') }}" required maxlength="32">
                    </label>
                    <label>E-Mail *
                        <input type="email" name="email" value="{{ old('email') }}" required maxlength="96">
                    </label>
                    <label>Телефон *
                        <input type="text" name="telephone" value="{{ old('telephone') }}" required maxlength="32">
                    </label>
                </div>
            </div>

            <div class="panel">
                <div class="panel-h">2. Адрес доставки / оплаты</div>
                <div class="panel-b checkout-grid">
                    <label>Компания
                        <input type="text" name="company" value="{{ old('company') }}" maxlength="60">
                    </label>
                    <label>Адрес *
                        <input type="text" name="address_1" value="{{ old('address_1') }}" required maxlength="128">
                    </label>
                    <label>Адрес 2
                        <input type="text" name="address_2" value="{{ old('address_2') }}" maxlength="128">
                    </label>
                    <label>Город *
                        <input type="text" name="city" value="{{ old('city') }}" required maxlength="128">
                    </label>
                    <label>Индекс
                        <input type="text" name="postcode" value="{{ old('postcode') }}" maxlength="10">
                    </label>
                    <label>Страна *
                        <select name="country_id" id="checkout-country" required>
                            @foreach($countries as $c)
                                <option value="{{ $c->country_id }}" @selected(old('country_id', 176) == $c->country_id)>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label>Регион *
                        <select name="zone_id" id="checkout-zone" required>
                            @foreach($zones as $z)
                                <option value="{{ $z->zone_id }}" @selected(old('zone_id') == $z->zone_id)>
                                    {{ $z->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="full">Комментарий к заказу
                        <textarea name="comment" rows="3">{{ old('comment') }}</textarea>
                    </label>
                </div>
            </div>

            <div class="panel">
                <div class="panel-h">3. Способ оплаты / доставки</div>
                <div class="panel-b">
                    <p style="margin:0 0 .5rem;"><strong>Доставка:</strong> Доставка (0 ₽)</p>
                    <p style="margin:0;"><strong>Оплата:</strong> Оплата при получении</p>
                </div>
            </div>

            <button type="submit" class="btn-cart">Подтвердить заказ</button>
        </form>

        <aside class="checkout-summary panel">
            <div class="panel-h">Ваш заказ</div>
            <div class="panel-b">
                @foreach($products as $item)
                    <div class="checkout-line">
                        <div>
                            <strong>{{ $item['name'] }}</strong> × {{ $item['quantity'] }}
                            @foreach($item['option'] as $opt)
                                <div class="muted" style="font-size:.82rem;">{{ $opt['name'] }}: {{ $opt['value'] }}</div>
                            @endforeach
                        </div>
                        <div>{{ number_format($item['total'], 2, '.', ' ') }} ₽</div>
                    </div>
                @endforeach
                <hr>
                @foreach($totals as $row)
                    <div class="checkout-line {{ $row['code'] === 'total' ? 'is-total' : '' }}">
                        <div>{{ $row['title'] }}</div>
                        <div>{{ number_format($row['value'], 2, '.', ' ') }} ₽</div>
                    </div>
                @endforeach
            </div>
        </aside>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var country = document.getElementById('checkout-country');
    var zone = document.getElementById('checkout-zone');
    if (!country || !zone) return;
    country.addEventListener('change', function () {
        fetch(@json(localized_route('catalog.checkout.zones')) + '?country_id=' + encodeURIComponent(country.value), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            zone.innerHTML = '';
            (data.zones || []).forEach(function (z) {
                var opt = document.createElement('option');
                opt.value = z.zone_id;
                opt.textContent = z.name;
                zone.appendChild(opt);
            });
        });
    });
})();
</script>
@endpush
