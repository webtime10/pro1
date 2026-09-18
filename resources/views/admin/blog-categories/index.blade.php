@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Категории блога',
    'breadcrumbs' => [['label' => 'Категории блога', 'url' => route('admin.blog-categories.index')]],
])
<section class="content pt-0">
    @include('admin.partials.oc-list-toolbar', [
        'createUrl' => route('admin.blog-categories.create'),
        'showBulkDelete' => false,
    ])
    <div class="container-fluid">
        <div class="oc-panel">
            <div class="oc-panel-heading"><i class="fas fa-list"></i> Список</div>
            <div class="oc-panel-body table-responsive">
                <table class="table oc-table mb-0">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Slug</th>
                            <th>Родитель</th>
                            <th class="text-right">Порядок</th>
                            <th>Статус</th>
                            <th class="oc-col-action">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $item)
                            @php
                                $defId = $defaultLanguage?->id;
                                $d = $defId ? $item->descriptions->firstWhere('language_id', $defId) : null;
                                $pd = $item->parent && $defId ? $item->parent->descriptions->firstWhere('language_id', $defId) : null;
                            @endphp
                            <tr>
                                <td><a href="{{ route('admin.blog-categories.edit', $item->id) }}">{{ $d->name ?? '—' }}</a></td>
                                <td>{{ $d->slug ?? '—' }}</td>
                                <td>{{ $pd->name ?? '—' }}</td>
                                <td class="text-right">{{ $item->sort_order }}</td>
                                <td>{{ $item->status ? 'Вкл.' : 'Выкл.' }}</td>
                                <td class="oc-col-action">
                                    @include('admin.partials.oc-row-actions', [
                                        'editUrl' => route('admin.blog-categories.edit', $item->id),
                                        'destroyUrl' => route('admin.blog-categories.destroy', $item->id),
                                        'destroyConfirm' => 'Удалить категорию блога?',
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Нет категорий</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="oc-panel-footer">{{ $categories->links() }}</div>
        </div>
    </div>
</section>
@endsection
