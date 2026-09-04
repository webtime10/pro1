@php
    $showValues = in_array(old('type', $option->type ?? 'select'), ['select', 'radio', 'checkbox'], true);
    $valueRows = old('option_value');
    if ($valueRows === null && isset($option)) {
        $valueRows = $option->values->map(function ($v) use ($languages) {
            $row = ['id' => $v->id, 'sort_order' => $v->sort_order, 'image' => $v->image, 'color' => $v->color ?? ''];
            foreach ($languages as $lang) {
                $d = $v->descriptions->firstWhere('language_id', $lang->id);
                $row['name_'.$lang->code] = $d->name ?? '';
            }
            return $row;
        })->all();
    }
    $valueRows = $valueRows ?: [];
@endphp
<div id="optionValuesSection" class="{{ $showValues ? '' : 'd-none' }}">
    <hr>
    <h5>Значения опции</h5>
    <button type="button" class="oc-btn oc-btn-green oc-btn-sm mb-2" id="addOptionValue" title="Добавить значение"><i class="fas fa-plus"></i></button>
    <table class="table table-bordered" id="optionValuesTable">
        <thead>
            <tr>
                @foreach($languages as $language)
                    <th>{{ $language->name }}</th>
                @endforeach
                <th>Изображение</th>
                <th>Цвет</th>
                <th>Порядок</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($valueRows as $idx => $row)
                @include('admin.options.partials.option-value-row', ['idx' => $idx, 'row' => $row])
            @endforeach
        </tbody>
    </table>
</div>
