@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Группы атрибутов',
    'breadcrumbs' => [['label' => 'Группы атрибутов', 'url' => route('admin.attribute-groups.index')]],
])
<section class="content pt-0">
    @include('admin.partials.oc-list-toolbar', ['createUrl' => route('admin.attribute-groups.create')])
    <div class="container-fluid">
        <div class="oc-panel">
            <div class="oc-panel-heading"><i class="fas fa-list"></i> Список групп атрибутов</div>
            <div class="oc-panel-body table-responsive">
                <table class="table oc-table mb-0">
                    <thead>
                        <tr>
                            <th>Название группы</th>
                            <th>Атрибутов</th>
                            <th class="text-right">Порядок сортировки</th>
                            <th class="oc-col-action">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attributeGroups as $group)
                            @php $d = $group->descriptions->first(); @endphp
                            <tr>
                                <td><a href="{{ route('admin.attribute-groups.edit', $group->id) }}">{{ $d->name ?? '#'.$group->id }}</a></td>
                                <td>{{ $group->attributes_count }}</td>
                                <td class="text-right">{{ $group->sort_order }}</td>
                                <td class="oc-col-action">
                                    @include('admin.partials.oc-row-actions', ['editUrl' => route('admin.attribute-groups.edit', $group->id)])
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Нет групп атрибутов</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($attributeGroups->hasPages())
                <div class="oc-panel-footer">{{ $attributeGroups->links() }}</div>
            @endif
        </div>
    </div>
</section>
@endsection
