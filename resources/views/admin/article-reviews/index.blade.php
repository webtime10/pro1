@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Отзывы блога',
    'breadcrumbs' => [['label' => 'Отзывы', 'url' => route('admin.article-reviews.index')]],
])
<section class="content pt-0">
    @include('admin.partials.oc-list-toolbar', ['showBulkDelete' => false])
    <div class="container-fluid">
        <div class="oc-panel">
            <div class="oc-panel-heading"><i class="fas fa-list"></i> Список</div>
            <div class="oc-panel-body table-responsive">
                <table class="table oc-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Статья</th>
                            <th>Автор</th>
                            <th>Оценка</th>
                            <th>Статус</th>
                            <th>Дата</th>
                            <th class="oc-col-action">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reviews as $item)
                            @php
                                $d = $defaultLanguage ? optional($item->article)->descriptions->firstWhere('language_id', $defaultLanguage->id) : null;
                            @endphp
                            <tr>
                                <td>{{ $d->name ?? '—' }}</td>
                                <td>{{ $item->author }}</td>
                                <td>{{ $item->rating }}</td>
                                <td>{{ $item->status ? 'Одобрен' : 'На модерации' }}</td>
                                <td>{{ $item->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="oc-col-action">
                                    @include('admin.partials.oc-row-actions', [
                                        'editUrl' => route('admin.article-reviews.edit', $item->id),
                                        'destroyUrl' => route('admin.article-reviews.destroy', $item->id),
                                        'destroyConfirm' => 'Удалить отзыв?',
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Нет отзывов</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="oc-panel-footer">{{ $reviews->links() }}</div>
        </div>
    </div>
</section>
@endsection
