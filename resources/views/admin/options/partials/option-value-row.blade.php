<tr>
    @foreach($languages as $language)
        @php $c = $language->code; @endphp
        <td>
            @if($loop->first && !empty($row['id']))
                <input type="hidden" name="option_value[{{ $idx }}][id]" value="{{ $row['id'] }}">
            @endif
            <input type="text" name="option_value[{{ $idx }}][name_{{ $c }}]" class="form-control form-control-sm"
                   value="{{ $row['name_'.$c] ?? '' }}" maxlength="128">
        </td>
    @endforeach
    <td>
        @include('admin.partials.image-picker', [
            'name' => 'option_value['.$idx.'][image]',
            'value' => $row['image'] ?? '',
            'inputId' => 'option-image-'.$idx,
            'thumbId' => 'option-thumb-'.$idx,
            'size' => 'sm',
        ])
    </td>
    @php $hex = $row['color'] ?? ''; @endphp
    <td>
        <div class="option-value-color-wrap">
            <input type="color" class="option-value-color-picker" value="{{ $hex !== '' ? $hex : '#cccccc' }}" title="Выбрать цвет">
            <input type="text" name="option_value[{{ $idx }}][color]" value="{{ $hex }}"
                   class="form-control form-control-sm option-value-color" placeholder="#rrggbb" maxlength="7" autocomplete="off">
        </div>
    </td>
    <td><input type="number" name="option_value[{{ $idx }}][sort_order]" class="form-control form-control-sm" value="{{ $row['sort_order'] ?? 0 }}" min="0"></td>
    <td><button type="button" class="oc-btn oc-btn-red oc-btn-sm remove-option-value" title="Удалить"><i class="fas fa-minus"></i></button></td>
</tr>
