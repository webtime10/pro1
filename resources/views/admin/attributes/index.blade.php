@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Атрибуты',
    'breadcrumbs' => [['label' => 'Атрибуты', 'url' => route('admin.attributes.index')]],
])
<section class="content pt-0">
    @php
        $filterHtml = '<form method="get" class="form-inline mb-0"><select name="attribute_group_id" class="form-control form-control-sm" onchange="this.form.submit()"><option value="">— все группы —</option>';
        foreach ($attributeGroups as $g) {
            $gd = $g->descriptions->first();
            $sel = (string)$attributeGroupId === (string)$g->id ? ' selected' : '';
            $filterHtml .= '<option value="'.$g->id.'"'.$sel.'>'.e($gd->name ?? '#'.$g->id).'</option>';
        }
        $filterHtml .= '</select></form>';
    @endphp
    @include('admin.partials.oc-list-toolbar', [
        'createUrl' => route('admin.attributes.create'),
        'toolbarLeft' => $filterHtml,
    ])
    <div class="container-fluid">
        <div class="oc-panel">
            <div class="oc-panel-heading"><i class="fas fa-list"></i> Список атрибутов</div>
            <div class="oc-panel-body table-responsive">
                <table class="table oc-table mb-0">
                    <thead>
                        <tr>
                            <th>Название атрибута</th>
                            <th>Группа</th>
                            <th class="text-right">Порядок сортировки</th>
                            <th class="oc-col-action">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attributes as $attr)
                            @php
                                $d = $attr->descriptions->first();
                                $gd = $attr->attributeGroup?->descriptions->first();
                            @endphp
                            <tr>
                                <td><a href="{{ route('admin.attributes.edit', $attr->id) }}">{{ $d->name ?? '#'.$attr->id }}</a></td>
                                <td>{{ $gd->name ?? '—' }}</td>
                                <td class="text-right">{{ $attr->sort_order }}</td>
                                <td class="oc-col-action">
                                    @include('admin.partials.oc-row-actions', ['editUrl' => route('admin.attributes.edit', $attr->id)])
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Нет атрибутов</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($attributes->hasPages())
                <div class="oc-panel-footer">{{ $attributes->links() }}</div>
            @endif
        </div>
    </div>
</section>
@endsection
