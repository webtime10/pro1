@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Атрибут — создание',
    'breadcrumbs' => [
        ['label' => 'Атрибуты', 'url' => route('admin.attributes.index')],
        ['label' => 'Создание', 'url' => route('admin.attributes.create')],
    ],
])
<section class="content pt-0">
    @include('admin.partials.oc-form-toolbar', ['formId' => 'attributeForm', 'backUrl' => route('admin.attributes.index')])
    <div class="container-fluid">
        <div class="oc-panel oc-form-panel">
            <form action="{{ route('admin.attributes.store') }}" method="post" id="attributeForm">
                @csrf
                <div class="oc-panel-body">
                    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                    <div class="form-group">
                        <label>Группа атрибутов <span class="text-danger">*</span></label>
                        <select name="attribute_group_id" class="form-control" required>
                            <option value="">—</option>
                            @foreach($attributeGroups as $g)
                                @php $gd = $g->descriptions->first(); @endphp
                                <option value="{{ $g->id }}" {{ old('attribute_group_id') == $g->id ? 'selected' : '' }}>{{ $gd->name ?? '#'.$g->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    @include('admin.partials.lang-name-fields', ['entity' => null, 'maxLength' => 64])
                    <div class="form-group mb-0">
                        <label>Порядок сортировки</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0" style="max-width:10rem">
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
