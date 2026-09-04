@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Производители',
    'breadcrumbs' => [['label' => 'Производители', 'url' => route('admin.manufacturers.index')]],
])
<section class="content pt-0">
    @include('admin.partials.oc-list-toolbar', ['createUrl' => route('admin.manufacturers.create')])
    <div class="container-fluid">
        <div class="oc-panel">
            <div class="oc-panel-heading"><i class="fas fa-list"></i> Список производителей</div>
            <div class="oc-panel-body table-responsive">
                <table class="table oc-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th class="text-right">Порядок</th>
                            <th class="oc-col-action">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($manufacturers as $m)
                            <tr>
                                <td><a href="{{ route('admin.manufacturers.edit', $m->id) }}">{{ $m->name }}</a></td>
                                <td class="text-right">{{ $m->sort_order }}</td>
                                <td class="oc-col-action">
                                    @include('admin.partials.oc-row-actions', ['editUrl' => route('admin.manufacturers.edit', $m->id)])
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">Нет записей</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="oc-panel-footer">{{ $manufacturers->links() }}</div>
        </div>
    </div>
</section>
@endsection
