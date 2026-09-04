@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Опция — редактирование',
    'breadcrumbs' => [
        ['label' => 'Опции', 'url' => route('admin.options.index')],
        ['label' => 'Редактирование', 'url' => route('admin.options.edit', $option->id)],
    ],
])
<section class="content pt-0">
    @include('admin.partials.oc-form-toolbar', [
        'formId' => 'optionForm',
        'backUrl' => route('admin.options.index'),
        'destroyUrl' => route('admin.options.destroy', $option->id),
    ])
    <div class="container-fluid">
        <div class="oc-panel oc-form-panel">
            <form action="{{ route('admin.options.update', $option->id) }}" method="post" id="optionForm">
                @csrf @method('PUT')
                <div class="oc-panel-body">
                    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Тип <span class="text-danger">*</span></label>
                            <select name="type" id="optionType" class="form-control" required>
                                @foreach(\App\Models\Option::TYPES as $type)
                                    <option value="{{ $type }}" {{ old('type', $option->type) === $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Порядок сортировки</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $option->sort_order) }}" min="0">
                        </div>
                    </div>
                    @include('admin.partials.lang-name-fields', ['entity' => $option, 'maxLength' => 128])
                    @include('admin.options.partials.option-values')
                </div>
            </form>
        </div>
    </div>
</section>
@include('admin.options.partials.option-values-scripts')
@endsection
