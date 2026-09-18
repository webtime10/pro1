@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Языки',
    'breadcrumbs' => [['label' => 'Языки', 'url' => route('admin.languages.index')]],
])
<section class="content pt-0">
    @include('admin.partials.oc-list-toolbar', ['createUrl' => route('admin.languages.create')])
    <div class="container-fluid">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if(session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif
        <div class="oc-panel">
            <div class="oc-panel-heading"><i class="fas fa-list"></i> Список языков</div>
            <div class="oc-panel-body table-responsive">
                <table class="table oc-table mb-0">
                    <thead>
                        <tr>
                            <th>Код</th>
                            <th>Название</th>
                            <th>Locale</th>
                            <th>По умолчанию</th>
                            <th>Активен</th>
                            <th class="oc-col-action">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($languages as $item)
                            <tr>
                                <td><strong>{{ strtoupper($item->code) }}</strong></td>
                                <td><a href="{{ route('admin.languages.edit', $item->id) }}">{{ $item->name }}</a></td>
                                <td><code>{{ $item->locale }}</code></td>
                                <td>{{ $item->is_default ? 'Да' : 'Нет' }}</td>
                                <td>{{ $item->is_active ? 'Да' : 'Нет' }}</td>
                                <td class="oc-col-action">
                                    @include('admin.partials.oc-row-actions', [
                                        'editUrl' => route('admin.languages.edit', $item->id),
                                        'destroyUrl' => $item->is_default ? null : route('admin.languages.destroy', $item->id),
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Нет данных</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="oc-panel-footer">{{ $languages->links() }}</div>
        </div>
    </div>
</section>
@endsection
