@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => $pageTitle,
    'breadcrumbs' => [
        ['label' => 'WT Filter', 'url' => route('admin.wt-filter.options')],
        ['label' => 'Опции фильтра'],
    ],
])
<section class="content pt-0">
  <div class="container-fluid">
    <div class="oc-panel">
      <div class="oc-panel-heading"><i class="fas fa-list"></i> Список опций фильтра</div>
      <div class="oc-panel-body">
        <div class="wt-filter-table-wrap">
          <table class="table wt-filter-list">
            <thead>
              <tr>
                <th>ID</th>
                <th>Название опции</th>
                <th class="wt-col-type">Тип</th>
                <th class="wt-col-sort text-center">Сорт.</th>
                <th class="wt-col-expanded text-center" colspan="2">
                  <div class="wt-expanded-head">Состояние</div>
                  <div class="wt-expanded-sub">
                    <span>Деск</span>
                    <span>Моб</span>
                  </div>
                </th>
                <th class="wt-col-status text-center">Статус</th>
                <th class="text-center" style="width:70px">Действие</th>
              </tr>
            </thead>
            <tbody>
              @forelse($options as $option)
                @php $type = (string) ($option->type ?: 'checkbox'); @endphp
                <tr>
                  <td class="text-muted">{{ $option->option_id }}</td>
                  <td>
                    <a href="{{ route('admin.wt-filter.options.edit', $option->option_id) }}" class="wt-option-name">
                      {{ $option->name ?: '—' }}
                    </a>
                  </td>
                  <td class="wt-col-type">
                    <div class="wt-type-select wt-type-select--{{ $type }}">
                      <select class="wt-opt-field wt-type-select__control" data-option-id="{{ $option->option_id }}" data-field="type">
                        @foreach($types as $key => $label)
                          <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                        @endforeach
                      </select>
                      <i class="fas fa-chevron-down wt-type-select__icon"></i>
                    </div>
                  </td>
                  <td class="text-center">
                    <input type="number" class="wt-opt-field wt-sort-input" data-option-id="{{ $option->option_id }}"
                           data-field="sort_order" value="{{ $option->sort_order }}" inputmode="numeric">
                  </td>
                  <td class="text-center">
                    <label class="wt-toggle" title="Десктоп">
                      <input type="checkbox" class="wt-opt-field" data-option-id="{{ $option->option_id }}"
                             data-field="expanded_desktop" value="1" @checked((int) $option->expanded_desktop === 1)>
                      <span class="wt-toggle__track"><span class="wt-toggle__thumb"></span></span>
                    </label>
                  </td>
                  <td class="text-center">
                    <label class="wt-toggle" title="Мобильный">
                      <input type="checkbox" class="wt-opt-field" data-option-id="{{ $option->option_id }}"
                             data-field="expanded_mobile" value="1" @checked((int) $option->expanded_mobile === 1)>
                      <span class="wt-toggle__track"><span class="wt-toggle__thumb"></span></span>
                    </label>
                  </td>
                  <td class="text-center">
                    <label class="wt-toggle" title="Статус">
                      <input type="checkbox" class="wt-opt-field" data-option-id="{{ $option->option_id }}"
                             data-field="status" value="1" @checked((int) $option->status === 1)>
                      <span class="wt-toggle__track"><span class="wt-toggle__thumb"></span></span>
                    </label>
                  </td>
                  <td class="text-center">
                    <a href="{{ route('admin.wt-filter.options.edit', $option->option_id) }}" class="oc-btn oc-btn-green oc-btn-sm" title="Редактировать"><i class="fas fa-pencil-alt"></i></a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-muted py-5">Опций нет. Сначала выполните «Копирование фильтров».</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      @if($options->total() > 0)
        <div class="oc-panel-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
          <span class="small text-muted">
            {{ $options->firstItem() }}–{{ $options->lastItem() }}
            из {{ number_format($options->total(), 0, '.', ' ') }}
            (стр. {{ $options->currentPage() }} / {{ $options->lastPage() }})
          </span>
          @if($options->hasPages())
            <div>
              {{ $options->onEachSide(2)->links('pagination::bootstrap-4') }}
            </div>
          @endif
        </div>
      @endif
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
$(function () {
  $('.wt-opt-field').on('change', function () {
    var $el = $(this);
    var value = $el.is(':checkbox') ? ($el.prop('checked') ? 1 : 0) : $el.val();

    if ($el.data('field') === 'type') {
      $el.closest('.wt-type-select').attr('class', 'wt-type-select wt-type-select--' + (value || 'checkbox'));
    }

    $.post('{{ route('admin.wt-filter.options.update') }}', {
      _token: $('meta[name="csrf-token"]').attr('content'),
      option_id: $el.data('option-id'),
      field: $el.data('field'),
      value: value
    });
  });
});
</script>
@endpush
