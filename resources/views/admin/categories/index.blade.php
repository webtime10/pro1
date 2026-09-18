@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Категории',
    'breadcrumbs' => [['label' => 'Категории', 'url' => route('admin.categories.index')]],
])
<section class="content pt-0">
    @include('admin.partials.oc-list-toolbar', [
        'createUrl' => route('admin.categories.create'),
        'listFormId' => 'form-category',
        'bulkDeleteConfirm' => 'Удалить выбранные категории? Все вложенные подкатегории тоже будут удалены.',
    ])
    <div class="container-fluid">
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        <div class="oc-panel">
            <div class="oc-panel-heading"><i class="fas fa-list"></i> Список категорий</div>
            <div class="oc-panel-body table-responsive">
                <form id="form-category" method="post" action="{{ route('admin.categories.destroy-selected') }}">
                    @csrf
                    <table class="table oc-table oc-table-catalog mb-0">
                        <thead>
                            <tr>
                                <th style="width:1px" class="text-center">
                                    <input type="checkbox" title="Выбрать все"
                                           onclick="$('#form-category .category-select').prop('checked', this.checked);">
                                </th>
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
                                    <td class="text-center">
                                        <input type="checkbox" class="category-select" name="selected[]" value="{{ $item->id }}">
                                    </td>
                                    <td><a href="{{ route('admin.categories.edit', $item->id) }}">{{ $d->name ?? '—' }}</a></td>
                                    <td>{{ $d->slug ?? '—' }}</td>
                                    <td>{{ $pd->name ?? '—' }}</td>
                                    <td class="text-right">{{ $item->sort_order }}</td>
                                    <td>{{ $item->status ? 'Вкл.' : 'Выкл.' }}</td>
                                    <td class="oc-col-action">
                                        @include('admin.partials.oc-row-actions', [
                                            'editUrl' => route('admin.categories.edit', $item->id),
                                            'destroyUrl' => route('admin.categories.destroy', $item->id),
                                            'destroyConfirm' => $item->children_count > 0
                                                ? 'У этой категории есть вложенные подкатегории. Они тоже будут удалены. Продолжить?'
                                                : 'Удалить категорию?',
                                        ])
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">Нет категорий</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </form>
            </div>
            <div class="oc-panel-footer">{{ $categories->links() }}</div>
        </div>
    </div>
</section>
@endsection
