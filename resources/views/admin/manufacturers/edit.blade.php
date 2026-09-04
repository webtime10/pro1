@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Производитель — редактирование',
    'breadcrumbs' => [
        ['label' => 'Производители', 'url' => route('admin.manufacturers.index')],
        ['label' => 'Редактирование', 'url' => route('admin.manufacturers.edit', $manufacturer->id)],
    ],
])
<section class="content pt-0">
    @include('admin.partials.oc-form-toolbar', [
        'formId' => 'manufacturerForm',
        'backUrl' => route('admin.manufacturers.index'),
        'destroyUrl' => route('admin.manufacturers.destroy', $manufacturer->id),
    ])
    <div class="container-fluid">
        <div class="oc-panel oc-form-panel">
            <form action="{{ route('admin.manufacturers.update', $manufacturer->id) }}" method="post" id="manufacturerForm">
                @csrf @method('PUT')
                <div class="oc-panel-body">
                    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                    <div class="form-group">
                        <label>Название <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $manufacturer->name) }}" required maxlength="64">
                    </div>
                    <div class="form-group">
                        <label>Изображение</label>
                        @include('admin.partials.image-picker', [
                            'name' => 'image',
                            'value' => old('image', $manufacturer->image),
                            'inputId' => 'input-manufacturer-image',
                            'thumbId' => 'thumb-manufacturer-image',
                        ])
                    </div>
                    <div class="form-group mb-0">
                        <label>Порядок сортировки</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $manufacturer->sort_order) }}" min="0">
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
