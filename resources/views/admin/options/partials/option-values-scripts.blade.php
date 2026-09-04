<script type="text/template" id="optionValueRowTemplate">
<tr>
    @foreach($languages as $language)
        <td><input type="text" name="option_value[__IDX__][name_{{ $language->code }}]" class="form-control form-control-sm" maxlength="128"></td>
    @endforeach
    <td>
        <div class="oc-image-picker oc-image-picker--sm">
            <a href="#" id="option-thumb-__IDX__" data-toggle="image" class="img-thumbnail">
                <img src="{{ asset('assets/admin/img/no_image.svg') }}" alt="" data-placeholder="{{ asset('assets/admin/img/no_image.svg') }}">
            </a>
            <input type="hidden" name="option_value[__IDX__][image]" id="option-image-__IDX__" value="">
        </div>
    </td>
    <td>
        <div class="option-value-color-wrap">
            <input type="color" class="option-value-color-picker" value="#cccccc" title="Выбрать цвет">
            <input type="text" name="option_value[__IDX__][color]" class="form-control form-control-sm option-value-color" placeholder="#rrggbb" maxlength="7" autocomplete="off">
        </div>
    </td>
    <td><input type="number" name="option_value[__IDX__][sort_order]" class="form-control form-control-sm" value="0" min="0"></td>
    <td><button type="button" class="oc-btn oc-btn-red oc-btn-sm remove-option-value" title="Удалить"><i class="fas fa-minus"></i></button></td>
</tr>
</script>
<script>
(function () {
    @php
        $valueRowCount = old('option_value') !== null
            ? count(old('option_value'))
            : (isset($option) ? $option->values->count() : 0);
    @endphp
    var valueIdx = {{ $valueRowCount }};
    function parseHex(val) {
        var m = String(val || '').trim().match(/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i);
        if (!m) return '';
        var h = m[1];
        if (h.length === 3) h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
        return '#' + h.toLowerCase();
    }
    function syncColor($row, fromPicker) {
        var $picker = $row.find('.option-value-color-picker');
        var $hex = $row.find('.option-value-color');
        if (fromPicker) {
            $hex.val($picker.val());
            return;
        }
        var hex = parseHex($hex.val());
        if (hex) {
            $hex.val(hex);
            $picker.val(hex);
        }
    }
    function toggleValuesSection() {
        var type = $('#optionType').val();
        var show = ['select', 'radio', 'checkbox'].indexOf(type) !== -1;
        $('#optionValuesSection').toggleClass('d-none', !show);
    }
    $('#optionType').on('change', toggleValuesSection);
    toggleValuesSection();
    $('#addOptionValue').on('click', function () {
        var tpl = $('#optionValueRowTemplate').html().replace(/__IDX__/g, valueIdx++);
        $('#optionValuesTable tbody').append(tpl);
    });
    $(document).on('click', '.remove-option-value', function () {
        $(this).closest('tr').remove();
    });
    $(document).on('input', '.option-value-color-picker', function () {
        syncColor($(this).closest('tr'), true);
    });
    $(document).on('input change', '.option-value-color', function () {
        syncColor($(this).closest('tr'), false);
    });
    $('#optionValuesTable tbody tr').each(function () {
        syncColor($(this), false);
    });
})();
</script>
