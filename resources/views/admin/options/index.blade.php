@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Опции',
    'breadcrumbs' => [['label' => 'Опции', 'url' => route('admin.options.index')]],
])
<section class="content pt-0">
    @include('admin.partials.oc-list-toolbar', ['createUrl' => route('admin.options.create')])
    <div class="container-fluid">
        <div class="oc-panel">
            <div class="oc-panel-heading"><i class="fas fa-list"></i> Список опций</div>
            <div class="oc-panel-body table-responsive">
                <table class="table oc-table mb-0">
                    <thead>
                        <tr>
                            <th>Название опции</th>
                            <th>Тип</th>
                            <th>Значений</th>
                            <th class="text-right">Порядок сортировки</th>
                            <th class="oc-col-action">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($options as $option)
                            @php $d = $option->descriptions->first(); @endphp
                            <tr>
                                <td><a href="{{ route('admin.options.edit', $option->id) }}">{{ $d->name ?? '#'.$option->id }}</a></td>
                                <td>{{ $option->type }}</td>
                                <td>{{ $option->values_count }}</td>
                                <td class="text-right">{{ $option->sort_order }}</td>
                                <td class="oc-col-action">
                                    @include('admin.partials.oc-row-actions', ['editUrl' => route('admin.options.edit', $option->id)])
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Нет опций</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($options->hasPages())
                <div class="oc-panel-footer">{{ $options->links() }}</div>
            @endif
        </div>
    </div>
</section>
@endsection
