@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Атрибут — редактирование',
    'breadcrumbs' => [
        ['label' => 'Атрибуты', 'url' => route('admin.attributes.index')],
        ['label' => 'Редактирование', 'url' => route('admin.attributes.edit', $attribute->id)],
    ],
])
<section class="content pt-0">
    @include('admin.partials.oc-form-toolbar', [
        'formId' => 'attributeForm',
        'backUrl' => route('admin.attributes.index'),
        'destroyUrl' => route('admin.attributes.destroy', $attribute->id),
    ])
    <div class="container-fluid">
        <div class="oc-panel oc-form-panel">
            <form action="{{ route('admin.attributes.update', $attribute->id) }}" method="post" id="attributeForm">
                @csrf @method('PUT')
                <div class="oc-panel-body">
                    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                    <div class="form-group">
                        <label>Группа атрибутов <span class="text-danger">*</span></label>
                        <select name="attribute_group_id" class="form-control" required>
                            @foreach($attributeGroups as $g)
                                @php $gd = $g->descriptions->first(); @endphp
                                <option value="{{ $g->id }}" {{ old('attribute_group_id', $attribute->attribute_group_id) == $g->id ? 'selected' : '' }}>{{ $gd->name ?? '#'.$g->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    @include('admin.partials.lang-name-fields', ['entity' => $attribute, 'maxLength' => 64])
                    <div class="form-group mb-0">
                        <label>Порядок сортировки</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $attribute->sort_order) }}" min="0" style="max-width:10rem">
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
