@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => $pageTitle,
    'breadcrumbs' => [
        ['label' => 'WT Filter', 'url' => route('admin.wt-filter.options')],
        ['label' => 'Настройки'],
    ],
])
<section class="content pt-0">
  <div class="container-fluid">
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form method="post" action="{{ route('admin.wt-filter.settings.save') }}" class="oc-panel" style="max-width:720px;">
      @csrf
      <div class="oc-panel-heading"><i class="fas fa-cog"></i> Настройки фильтра</div>
      <div class="oc-panel-body p-4">
        <div class="form-group">
          <label>Количество у опций</label>
          <select name="show_counts" class="form-control">
            <option value="1" @selected($showCounts)>Включено</option>
            <option value="0" @selected(!$showCounts)>Выключено</option>
          </select>
        </div>

        <label>Состояние групп по умолчанию</label>
        <table class="table table-bordered table-sm wt-filter-list mb-3">
          <thead>
            <tr>
              <th>Группа</th>
              <th class="text-center">Деск</th>
              <th class="text-center">Моб</th>
            </tr>
          </thead>
          <tbody>
            @foreach(['p' => 'Цена', 'm' => 'Производитель', 's' => 'Наличие', 'd' => 'Акции'] as $key => $label)
            <tr>
              <td>{{ $label }}</td>
              <td class="text-center">
                <label class="wt-toggle">
                  <input type="checkbox" name="group_expanded[{{ $key }}][desktop]" value="1"
                         @checked(!empty($groupExpanded[$key]['desktop']))>
                  <span class="wt-toggle__track"><span class="wt-toggle__thumb"></span></span>
                </label>
              </td>
              <td class="text-center">
                <label class="wt-toggle">
                  <input type="checkbox" name="group_expanded[{{ $key }}][mobile]" value="1"
                         @checked(!empty($groupExpanded[$key]['mobile']))>
                  <span class="wt-toggle__track"><span class="wt-toggle__thumb"></span></span>
                </label>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
        <p class="text-muted mb-3">Вкл = группа открыта. Опции товаров — в «Опции фильтра».</p>
        <button type="submit" class="btn btn-primary">Сохранить</button>
      </div>
    </form>
  </div>
</section>
@endsection
