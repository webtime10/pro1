@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Группа атрибутов — редактирование',
    'breadcrumbs' => [
        ['label' => 'Группы атрибутов', 'url' => route('admin.attribute-groups.index')],
        ['label' => 'Редактирование', 'url' => route('admin.attribute-groups.edit', $attributeGroup->id)],
    ],
])
<section class="content pt-0">
    @include('admin.partials.oc-form-toolbar', [
        'formId' => 'attributeGroupForm',
        'backUrl' => route('admin.attribute-groups.index'),
        'destroyUrl' => route('admin.attribute-groups.destroy', $attributeGroup->id),
    ])
    <div class="container-fluid">
        <div class="oc-panel oc-form-panel">
            <form action="{{ route('admin.attribute-groups.update', $attributeGroup->id) }}" method="post" id="attributeGroupForm">
                @csrf @method('PUT')
                <div class="oc-panel-body">
                    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                    @include('admin.partials.lang-name-fields', ['entity' => $attributeGroup, 'maxLength' => 64])
                    <div class="form-group mb-0">
                        <label>Порядок сортировки</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $attributeGroup->sort_order) }}" min="0" style="max-width:10rem">
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
