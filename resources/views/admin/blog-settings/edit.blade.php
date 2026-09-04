@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Настройки блога',
    'breadcrumbs' => [['label' => 'Настройки блога', 'url' => route('admin.blog-settings.edit')]],
])
<section class="content pt-0">
    @include('admin.partials.oc-form-toolbar', ['formId' => 'blogSettingsForm', 'backUrl' => route('admin.articles.index')])
    <div class="container-fluid">
        <div class="oc-panel oc-form-panel">
            <div class="oc-panel-body">
                <form id="blogSettingsForm" action="{{ route('admin.blog-settings.update') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Название / пункт меню</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $settings['name'] ?? 'Блог') }}">
                    </div>
                    <div class="form-group">
                        <label>H1 на главной блога</label>
                        <input type="text" name="html_h1" class="form-control" value="{{ old('html_h1', $settings['html_h1'] ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label>Meta title</label>
                        <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $settings['meta_title'] ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label>Meta description</label>
                        <input type="text" name="meta_description" class="form-control" value="{{ old('meta_description', $settings['meta_description'] ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label>Meta keyword</label>
                        <input type="text" name="meta_keyword" class="form-control" value="{{ old('meta_keyword', $settings['meta_keyword'] ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label>Статей на страницу</label>
                        <input type="number" name="article_limit" class="form-control" min="1" max="100" style="max-width:12rem;"
                               value="{{ old('article_limit', $settings['article_limit'] ?? 20) }}">
                    </div>
                    <div class="form-group">
                        <label>Длина анонса (символов)</label>
                        <input type="number" name="article_description_length" class="form-control" min="20" max="2000" style="max-width:12rem;"
                               value="{{ old('article_description_length', $settings['article_description_length'] ?? 200) }}">
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="blog_menu" value="0">
                        <label class="d-block"><input type="checkbox" name="blog_menu" value="1" {{ !empty($settings['blog_menu']) && $settings['blog_menu'] !== '0' ? 'checked' : '' }}> Показывать блог в шапке</label>
                        <input type="hidden" name="review_status" value="0">
                        <label class="d-block"><input type="checkbox" name="review_status" value="1" {{ !empty($settings['review_status']) && $settings['review_status'] !== '0' ? 'checked' : '' }}> Отзывы включены</label>
                        <input type="hidden" name="review_guest" value="0">
                        <label class="d-block"><input type="checkbox" name="review_guest" value="1" {{ !empty($settings['review_guest']) && $settings['review_guest'] !== '0' ? 'checked' : '' }}> Гости могут оставлять отзывы</label>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
