@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Статья',
    'breadcrumbs' => [
        ['label' => 'Статьи', 'url' => route('admin.articles.index')],
        ['label' => 'Редактирование'],
    ],
])
<section class="content pt-0">
    @include('admin.partials.oc-form-toolbar', [
        'formId' => 'articleForm',
        'backUrl' => route('admin.articles.index'),
        'destroyUrl' => route('admin.articles.destroy', $article->id),
    ])
    <div class="container-fluid">
        <div class="oc-panel oc-form-panel">
            <div class="oc-panel-body">
                <form id="articleForm" action="{{ route('admin.articles.update', $article->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('admin.articles._form')
                </form>
            </div>
        </div>
    </div>
</section>
@include('admin.partials.slug-auto-sync')
@include('admin.articles._form-scripts')
@endsection
