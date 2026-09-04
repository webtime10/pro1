@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Статьи блога',
    'breadcrumbs' => [['label' => 'Статьи', 'url' => route('admin.articles.index')]],
])
<section class="content pt-0">
    @include('admin.partials.oc-list-toolbar', [
        'createUrl' => route('admin.articles.create'),
        'showBulkDelete' => false,
    ])
    <div class="container-fluid">
        <div class="oc-panel">
            <div class="oc-panel-heading"><i class="fas fa-list"></i> Список</div>
            <div class="oc-panel-body table-responsive">
                <table class="table oc-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Slug</th>
                            <th>Дата</th>
                            <th>Просмотры</th>
                            <th>Статус</th>
                            <th class="oc-col-action">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($articles as $item)
                            @php
                                $d = $defaultLanguage ? $item->descriptions->firstWhere('language_id', $defaultLanguage->id) : null;
                            @endphp
                            <tr>
                                <td><a href="{{ route('admin.articles.edit', $item->id) }}">{{ $d->name ?? '—' }}</a></td>
                                <td>{{ $d->slug ?? '—' }}</td>
                                <td>{{ optional($item->date_available)->format('Y-m-d') }}</td>
                                <td>{{ $item->viewed }}</td>
                                <td>{{ $item->status ? 'Вкл.' : 'Выкл.' }}</td>
                                <td class="oc-col-action">
                                    @include('admin.partials.oc-row-actions', [
                                        'editUrl' => route('admin.articles.edit', $item->id),
                                        'destroyUrl' => route('admin.articles.destroy', $item->id),
                                        'destroyConfirm' => 'Удалить статью?',
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Нет статей</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="oc-panel-footer">{{ $articles->links() }}</div>
        </div>
    </div>
</section>
@endsection
