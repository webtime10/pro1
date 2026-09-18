@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => $pageTitle,
    'breadcrumbs' => [
        ['label' => 'WT Filter', 'url' => route('admin.wt-filter.options')],
        ['label' => 'Опции фильтра', 'url' => route('admin.wt-filter.options')],
        ['label' => $optionName],
    ],
])
<section class="content pt-0">
  @include('admin.partials.oc-form-toolbar', [
      'formId' => 'wtFilterOptionForm',
      'backUrl' => route('admin.wt-filter.options'),
  ])
  <div class="container-fluid">
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <div class="oc-panel oc-form-panel">
      <form method="post" action="{{ route('admin.wt-filter.options.save', $optionId) }}" id="wtFilterOptionForm">
        @csrf
        @method('PUT')
        <div class="card card-outline card-outline-tabs mb-0 border-0">
          <div class="card-header p-0 border-bottom-0 bg-white">
            <ul class="nav nav-tabs" role="tablist">
              <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-general">Основное</a></li>
              <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-values">Значения</a></li>
            </ul>
          </div>
          <div class="card-body">
            @if($errors->any())
              <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <div class="tab-content">
              <div class="tab-pane fade show active" id="tab-general">
                <div class="form-group">
                  <label>Название</label>
                  @foreach($languages as $language)
                    <div class="input-group mb-2" style="max-width:560px;">
                      <div class="input-group-prepend">
                        <span class="input-group-text">{{ strtoupper($language->code) }}</span>
                      </div>
                      <input type="text"
                             name="option_description[{{ $language->id }}][name]"
                             value="{{ old('option_description.'.$language->id.'.name', optional($descriptions[$language->id])->name) }}"
                             class="form-control">
                    </div>
                  @endforeach
                </div>

                <div class="form-group">
                  <label for="input-keyword">Keyword</label>
                  <input type="text" name="keyword" id="input-keyword" class="form-control"
                         value="{{ old('keyword', $option->keyword) }}" style="max-width:280px;">
                </div>

                <div class="form-group">
                  <label>Категория</label>
                  <div class="well well-sm wt-filter-categories border rounded p-2" style="height:200px;overflow:auto;max-width:560px;">
                    @foreach($categories as $category)
                      <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" name="category_id[]"
                               id="cat-{{ $category['id'] }}" value="{{ $category['id'] }}"
                               @checked(in_array((int) $category['id'], array_map('intval', (array) $optionCategories), true))>
                        <label class="custom-control-label" for="cat-{{ $category['id'] }}">{{ $category['label'] }}</label>
                      </div>
                    @endforeach
                  </div>
                  <a href="#" class="wt-filter-cats-all">Выбрать все</a> /
                  <a href="#" class="wt-filter-cats-none">Снять все</a>
                </div>

                <div class="form-group">
                  <label for="input-type">Тип</label>
                  <select name="type" id="input-type" class="form-control" style="max-width:280px;">
                    @foreach($types as $key => $label)
                      <option value="{{ $key }}" @selected(old('type', $option->type) === $key)>{{ $label }}</option>
                    @endforeach
                  </select>
                  <div class="custom-control custom-checkbox mt-2">
                    <input type="checkbox" class="custom-control-input" name="color" value="1" id="input-is-color"
                           @checked((int) old('color', $option->color) === 1)>
                    <label class="custom-control-label" for="input-is-color">Показывать цветовые метки</label>
                  </div>
                </div>

                <div class="form-group">
                  <label for="input-sort-order">Порядок сортировки</label>
                  <input type="number" name="sort_order" id="input-sort-order" class="form-control"
                         value="{{ old('sort_order', $option->sort_order) }}" min="0" style="max-width:120px;">
                </div>

                <div class="form-group mb-0">
                  <label>Статус</label>
                  <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" name="status" value="1" id="input-status"
                           @checked((int) old('status', $option->status) === 1)>
                    <label class="custom-control-label" for="input-status">Включено</label>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade" id="tab-values">
                <div class="table-responsive">
                  <table class="table table-bordered mb-0">
                    <thead>
                      <tr>
                        <th>Изображение</th>
                        <th>Название опции</th>
                        <th>Keyword</th>
                        <th>Цвет</th>
                        <th class="text-right">Число</th>
                        <th class="text-right">Порядок</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse($values as $value)
                        <tr>
                          <td>
                            @include('admin.partials.image-picker', [
                                'name' => 'value['.$value['value_id'].'][image]',
                                'value' => $value['image'] ?? '',
                                'inputId' => 'wt-image-'.$value['value_id'],
                                'thumbId' => 'wt-thumb-'.$value['value_id'],
                                'size' => 'sm',
                            ])
                          </td>
                          <td>{{ $value['name'] ?: '—' }}</td>
                          <td>{{ $value['keyword'] ?: '—' }}</td>
                          <td style="max-width:168px;">
                            <div class="option-value-color-wrap">
                              <input type="color" class="option-value-color-picker"
                                     value="{{ $value['color'] !== '' ? $value['color'] : '#cccccc' }}" title="Выбрать цвет">
                              <input type="text" name="value[{{ $value['value_id'] }}][color]"
                                     value="{{ $value['color'] }}" class="form-control form-control-sm wt-filter-value-color"
                                     placeholder="#rrggbb" maxlength="7" autocomplete="off">
                            </div>
                          </td>
                          <td class="text-right">{{ $value['value_numeric'] !== null && $value['value_numeric'] !== '' ? $value['value_numeric'] : '—' }}</td>
                          <td class="text-right">{{ $value['sort_order'] }}</td>
                        </tr>
                      @empty
                        <tr><td colspan="6" class="text-center text-muted">Нет значений</td></tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
                <p class="text-muted mt-2 mb-0">Если заданы цвет и картинка — показывается цвет. Если только картинка — картинка. Плашка 20×20.</p>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
(function ($) {
  function parseHex(val) {
    var m = String(val || '').trim().match(/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i);
    if (!m) return '';
    var h = m[1];
    if (h.length === 3) h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
    return '#' + h.toLowerCase();
  }

  function applySwatch($row) {
    var hex = parseHex($row.find('.wt-filter-value-color').val());
    if (hex) {
      $row.find('.option-value-color-picker').val(hex);
    }
  }

  $(document).on('input change', '.wt-filter-value-color', function () {
    applySwatch($(this).closest('tr'));
  });
  $(document).on('input', '.option-value-color-picker', function () {
    var $row = $(this).closest('tr');
    $row.find('.wt-filter-value-color').val($(this).val());
    applySwatch($row);
  });

  $('#tab-values tbody tr').each(function () {
    applySwatch($(this));
  });

  $('.wt-filter-cats-all').on('click', function (e) {
    e.preventDefault();
    $('.wt-filter-categories :checkbox').prop('checked', true);
  });
  $('.wt-filter-cats-none').on('click', function (e) {
    e.preventDefault();
    $('.wt-filter-categories :checkbox').prop('checked', false);
  });
})(jQuery);
</script>
@endpush
