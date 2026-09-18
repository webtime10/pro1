@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Новая статья',
    'breadcrumbs' => [
        ['label' => 'Статьи', 'url' => route('admin.articles.index')],
        ['label' => 'Создание'],
    ],
])
<section class="content pt-0">
@php
    $translateBtn = '<button type="button" class="oc-btn oc-btn-primary js-article-translate" title="Перевод RU → EN, UK"><i class="fas fa-language"></i></button>';
@endphp
@include('admin.partials.oc-form-toolbar', [
    'formId' => 'articleForm',
    'backUrl' => route('admin.articles.index'),
    'extraButtons' => $translateBtn,
])
    <div class="container-fluid">
        <div class="oc-panel oc-form-panel">
            <div class="oc-panel-body">
                <form id="articleForm" action="{{ route('admin.articles.store') }}" method="POST">
                    @csrf
                    @include('admin.articles._form')
                </form>
            </div>
        </div>
    </div>
</section>
@include('admin.partials.slug-auto-sync')
@include('admin.articles._form-scripts')
@endsection
