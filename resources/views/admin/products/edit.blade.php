@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => $pageTitle,
    'breadcrumbs' => [
        ['label' => 'Товары', 'url' => route('admin.products.index')],
        ['label' => 'Редактирование', 'url' => route('admin.products.edit', $product->id)],
    ],
])
<section class="content pt-0">
    @include('admin.partials.oc-form-toolbar', [
        'formId' => 'productForm',
        'backUrl' => route('admin.products.index'),
        'destroyUrl' => route('admin.products.destroy', $product->id),
    ])
    <div class="container-fluid">
        @include('admin.products._form')
    </div>
</section>
@endsection
