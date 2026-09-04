@extends('catalog.layout')

@section('title', 'Заказ оформлен')

@section('content')
    <nav class="crumb">
        <a href="{{ localized_route('catalog.index') }}">Главная</a>
        <span> / </span>
        <span>Заказ оформлен</span>
    </nav>

    <h1 style="margin-top:0;">Ваш заказ принят!</h1>
    @if($orderId)
        <p>Номер заказа: <strong>#{{ $orderId }}</strong></p>
    @endif
    <p class="muted">Спасибо за покупку. Мы свяжемся с вами для подтверждения.</p>
    <p><a href="{{ localized_route('catalog.index') }}" class="btn-cart" style="text-decoration:none;">На главную</a></p>
@endsection
