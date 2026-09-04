@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Новая категория блога',
    'breadcrumbs' => [
        ['label' => 'Категории блога', 'url' => route('admin.blog-categories.index')],
        ['label' => 'Создание'],
    ],
])
<section class="content pt-0">
    @include('admin.partials.oc-form-toolbar', ['formId' => 'blogCategoryForm', 'backUrl' => route('admin.blog-categories.index')])
    <div class="container-fluid">
        <div class="oc-panel oc-form-panel">
            <div class="oc-panel-body">
                <form id="blogCategoryForm" action="{{ route('admin.blog-categories.store') }}" method="POST">
                    @csrf
                    @include('admin.blog-categories._form')
                </form>
            </div>
        </div>
    </div>
</section>
@include('admin.partials.slug-auto-sync')
@endsection
