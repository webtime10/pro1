@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Язык — создание',
    'breadcrumbs' => [
        ['label' => 'Языки', 'url' => route('admin.languages.index')],
        ['label' => 'Создание', 'url' => route('admin.languages.create')],
    ],
])
<section class="content pt-0">
    @include('admin.partials.oc-form-toolbar', ['formId' => 'languageForm', 'backUrl' => route('admin.languages.index')])
    <div class="container-fluid">
        <div class="oc-panel oc-form-panel">
            <form id="languageForm" action="{{ route('admin.languages.store') }}" method="POST">
                @csrf
                <div class="oc-panel-body">
                    @if ($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    <div class="form-group">
                        <label for="code">Код языка <span class="text-danger">*</span></label>
                        <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required maxlength="10">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="name">Название <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="100">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="locale">Locale <span class="text-danger">*</span></label>
                        <input type="text" name="locale" id="locale" class="form-control @error('locale') is-invalid @enderror" value="{{ old('locale') }}" required maxlength="255">
                        @error('locale')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="directory">Directory (OpenCart)</label>
                        <input type="text" name="directory" id="directory" class="form-control" value="{{ old('directory') }}" maxlength="32">
                    </div>
                    <div class="form-group">
                        <label for="sort_order">Порядок сортировки</label>
                        <input type="number" name="sort_order" id="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}> Язык по умолчанию</label>
                    </div>
                    <div class="form-group mb-0">
                        <label><input type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}> Активен</label>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
