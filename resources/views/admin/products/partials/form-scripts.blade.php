<script type="text/template" id="productAttributeRowTemplate">
<tr class="product-attribute-row">
    <td>
        <select name="product_attribute[__IDX__][attribute_id]" class="form-control form-control-sm" required>
            <option value="">—</option>
            @foreach($allAttributes as $attr)
                @php
                    $attrName = $attr->descriptions->firstWhere('language_id', $defaultLanguage?->id)?->name ?? '#'.$attr->id;
                    $groupName = $attr->attributeGroup?->descriptions->firstWhere('language_id', $defaultLanguage?->id)?->name ?? '';
                @endphp
                <option value="{{ $attr->id }}">{{ $groupName ? $groupName.' > ' : '' }}{{ $attrName }}</option>
            @endforeach
        </select>
    </td>
    @foreach($languages as $language)
        <td><input type="text" name="product_attribute[__IDX__][text][{{ $language->id }}]" class="form-control form-control-sm"></td>
    @endforeach
    <td class="text-center"><button type="button" class="oc-btn oc-btn-red oc-btn-sm remove-product-attribute" title="Удалить"><i class="fas fa-minus"></i></button></td>
</tr>
</script>

<script type="text/template" id="productOptionBlockTemplate">
<div class="card card-outline card-secondary mb-3 product-option-block" data-idx="__IDX__">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-5">
                <select name="product_option[__IDX__][option_id]" class="form-control product-option-select" required>
                    <option value="">— выберите опцию —</option>
                    @foreach($allOptions as $opt)
                        @php $optName = $opt->descriptions->firstWhere('language_id', $defaultLanguage?->id)?->name ?? '#'.$opt->id; @endphp
                        <option value="{{ $opt->id }}" data-type="{{ $opt->type }}">{{ $optName }} ({{ $opt->type }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="mb-0">
                    <input type="hidden" name="product_option[__IDX__][required]" value="0">
                    <input type="checkbox" name="product_option[__IDX__][required]" value="1"> Обязательная
                </label>
            </div>
            <div class="col-md-3 text-right">
                <button type="button" class="oc-btn oc-btn-red oc-btn-sm remove-product-option" title="Удалить"><i class="fas fa-trash-alt"></i></button>
            </div>
        </div>
    </div>
    <div class="card-body p-2">
        <div class="product-option-value-field">
            <label>Значение (text / textarea / date / file…)</label>
            <input type="text" name="product_option[__IDX__][value]" class="form-control">
        </div>
        <div class="product-option-values-table d-none"></div>
    </div>
</div>
</script>

<script>
(function () {
    var optionsCatalog = @json($optionsJson);
    @php
        $attributeRowCount = old('product_attribute') !== null
            ? count(old('product_attribute'))
            : (isset($product) ? $product->productAttributes->groupBy('attribute_id')->count() : 0);
        $optionRowCount = old('product_option') !== null
            ? count(old('product_option'))
            : (isset($product) ? $product->productOptions->count() : 0);
    @endphp
    var attributeIdx = {{ $attributeRowCount }};
    var optionIdx = {{ $optionRowCount }};

    function buildOptionValuesTable(idx, optionId) {
        var option = optionsCatalog.find(function (o) { return String(o.id) === String(optionId); });
        if (!option || ['select', 'radio', 'checkbox'].indexOf(option.type) === -1) {
            return '';
        }
        var html = '<table class="table table-sm table-bordered mb-0"><thead><tr>' +
            '<th>Значение опции</th><th>Кол-во</th><th>Вычитать</th><th>Цена +/-</th><th>Баллы +/-</th><th>Вес +/-</th>' +
            '</tr></thead><tbody>';
        option.values.forEach(function (val, vIdx) {
            html += '<tr><td>' + val.name +
                '<input type="hidden" name="product_option[' + idx + '][product_option_value][' + vIdx + '][option_value_id]" value="' + val.id + '"></td>' +
                '<td><input type="number" min="0" class="form-control form-control-sm" name="product_option[' + idx + '][product_option_value][' + vIdx + '][quantity]" value="0"></td>' +
                '<td class="text-center"><input type="hidden" name="product_option[' + idx + '][product_option_value][' + vIdx + '][subtract]" value="0">' +
                '<input type="checkbox" name="product_option[' + idx + '][product_option_value][' + vIdx + '][subtract]" value="1"></td>' +
                '<td><div class="input-group input-group-sm"><select name="product_option[' + idx + '][product_option_value][' + vIdx + '][price_prefix]" class="form-control"><option value="+">+</option><option value="-">-</option></select>' +
                '<input type="number" step="0.0001" class="form-control" name="product_option[' + idx + '][product_option_value][' + vIdx + '][price]" value="0"></div></td>' +
                '<td><div class="input-group input-group-sm"><select name="product_option[' + idx + '][product_option_value][' + vIdx + '][points_prefix]" class="form-control"><option value="+">+</option><option value="-">-</option></select>' +
                '<input type="number" class="form-control" name="product_option[' + idx + '][product_option_value][' + vIdx + '][points]" value="0"></div></td>' +
                '<td><div class="input-group input-group-sm"><select name="product_option[' + idx + '][product_option_value][' + vIdx + '][weight_prefix]" class="form-control"><option value="+">+</option><option value="-">-</option></select>' +
                '<input type="number" step="0.00000001" class="form-control" name="product_option[' + idx + '][product_option_value][' + vIdx + '][weight]" value="0"></div></td></tr>';
        });
        html += '</tbody></table>';
        return html;
    }

    function refreshOptionBlock($block) {
        var idx = $block.data('idx');
        var optionId = $block.find('.product-option-select').val();
        var option = optionsCatalog.find(function (o) { return String(o.id) === String(optionId); });
        var hasValues = option && ['select', 'radio', 'checkbox'].indexOf(option.type) !== -1;
        $block.find('.product-option-value-field').toggleClass('d-none', hasValues);
        var $tableWrap = $block.find('.product-option-values-table');
        if (hasValues) {
            $tableWrap.removeClass('d-none').html(buildOptionValuesTable(idx, optionId));
        } else {
            $tableWrap.addClass('d-none').empty();
        }
    }

    $('#addProductAttribute').on('click', function () {
        var tpl = $('#productAttributeRowTemplate').html().replace(/__IDX__/g, attributeIdx++);
        $('#productAttributeTable tbody').append(tpl);
    });

    $(document).on('click', '.remove-product-attribute', function () {
        $(this).closest('tr').remove();
    });

    $('#addProductOption').on('click', function () {
        var tpl = $('#productOptionBlockTemplate').html().replace(/__IDX__/g, optionIdx++);
        var $block = $(tpl);
        $('#productOptionsContainer').append($block);
    });

    $(document).on('click', '.remove-product-option', function () {
        $(this).closest('.product-option-block').remove();
    });

    $(document).on('change', '.product-option-select', function () {
        refreshOptionBlock($(this).closest('.product-option-block'));
    });
})();
</script>

<link rel="stylesheet" href="{{ asset('assets/admin/css/wt_filter_product.css') }}">
<script>
(function () {
    var callbackUrl = @json(route('admin.wt-filter.product-form'));
    var productId = @json($isEdit ? (int) $product->id : 0);
    var textSelect = 'Выберите';
    var selectCategoryMsg = 'Сначала выберите категорию для этого товара (вкладка «Связи»).';
    var categoryLen = null;
    var lastCategoryId = null;

    function currentCategoryId() {
        var $checked = $('input[name="category_ids[]"]:checked');
        if (!$checked.length) {
            return null;
        }
        return $checked.last().val();
    }

    function updateSelectedLabel($checkbox) {
        var $switcher = $checkbox.closest('.switcher');
        var $selected = $switcher.find('.selected');
        var text = $checkbox.parent('label').text();
        var length = $selected.find('span').length;

        if ($checkbox.prop('checked')) {
            $selected.append('<span id="v-' + $checkbox.val() + '">' + text + '</span>').find('b').remove();
        } else if (length === 1) {
            $('#v-' + $checkbox.val()).replaceWith('<b>' + textSelect + '</b>');
        } else {
            $('#v-' + $checkbox.val()).remove();
        }
    }

    function renderOptions(json) {
        var $tab = $('#tab-wt-filter');
        if (json.message) {
            $tab.html('<p class="text-muted mb-0">' + json.message + '</p>');
            return;
        }

        var html = [];
        html.push('<input type="hidden" name="wt_filter_product_tab" value="1" />');
        html.push('<table class="table table-bordered product-wt-filter-values">');

        (json.options || []).forEach(function (option) {
            html.push('<tr' + (!option.status ? ' class="table-secondary"' : '') + '>');
            html.push('<td width="20%"><strong>' + option.name + '</strong></td><td width="80%">');

            if (option.type === 'slide' || option.type === 'slide_dual' || option.type === 'slider_single' || option.type === 'slider_range') {
                html.push('<input type="hidden" name="wt_filter_product_option[' + option.option_id + '][values][0][selected]" value="1" />');
                html.push('<input type="text" name="wt_filter_product_option[' + option.option_id + '][values][0][slide_value_min]" value="' + (option.slide_value_min || '') + '" size="5" class="form-control form-control-sm d-inline-block" style="width:90px;" />');
                html.push('&nbsp;&mdash;&nbsp;');
                html.push('<input type="text" name="wt_filter_product_option[' + option.option_id + '][values][0][slide_value_max]" value="' + (option.slide_value_max || '') + '" size="5" class="form-control form-control-sm d-inline-block" style="width:90px;" />');
                html.push(option.postfix || '');
            } else if (option.values && option.values.length) {
                var values = [];
                var selecteds = [];

                option.values.forEach(function (value) {
                    if (value.selected) {
                        selecteds.push('<span id="v-' + value.value_id + '">' + value.name + (option.postfix || '') + '</span>');
                    }
                    values.push('<div>');
                    values.push('<label><input type="checkbox" name="wt_filter_product_option[' + option.option_id + '][values][' + value.value_id + '][selected]" value="' + value.value_id + '"' + (value.selected ? ' checked' : '') + ' /> ' + value.name + (option.postfix || '') + '</label>');
                    values.push('</div>');
                });

                if (!selecteds.length) {
                    selecteds = ['<b>' + textSelect + '</b>'];
                }

                html.push('<div class="switcher"><div class="selected">' + selecteds.join('') + '</div><div class="values">' + values.join('') + '</div></div>');
            } else {
                html.push('<span class="text-muted">Нет значений</span>');
            }

            html.push('</td></tr>');
        });

        html.push('</table>');
        $tab.html(html.join(''));
    }

    function updateTab() {
        var categoryId = currentCategoryId();
        lastCategoryId = categoryId;

        if (!categoryId) {
            $('#tab-wt-filter').html('<p class="text-muted mb-0">' + selectCategoryMsg + '</p>');
            return;
        }

        $.get(callbackUrl, {
            category_id: categoryId,
            product_id: productId || undefined
        }, renderOptions, 'json');
    }

    $(document).on('click', '.switcher .selected', function () {
        var $this = $(this).parent('.switcher');
        if (!$this.hasClass('active')) {
            $('.switcher').removeClass('active');
            $this.addClass('active');
        } else {
            $this.removeClass('active');
        }
    });

    $(document).on('change', '.switcher input[type="checkbox"]', function () {
        updateSelectedLabel($(this));
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.switcher').length) {
            $('.switcher.active').removeClass('active');
        }
    });

    $(document).on('change', 'input[name="category_ids[]"]', function () {
        updateTab();
    });

    categoryLen = $('input[name="category_ids[]"]:checked').length;
    setInterval(function () {
        var len = $('input[name="category_ids[]"]:checked').length;
        if (categoryLen !== len) {
            categoryLen = len;
            updateTab();
        }
    }, 500);

    updateTab();
})();
</script>
<script type="text/template" id="productAdditionalImageRowTemplate">
<tr id="image-row__IDX__">
    <td>
        <div class="oc-image-picker oc-image-picker--md">
            <a href="#" id="additional-thumb-__IDX__" data-toggle="image" class="img-thumbnail">
                <img src="{{ asset('assets/admin/img/no_image.svg') }}" alt="" data-placeholder="{{ asset('assets/admin/img/no_image.svg') }}">
            </a>
            <input type="hidden" name="product_image[__IDX__][image]" id="additional-image-__IDX__" value="">
        </div>
    </td>
    <td class="text-right align-middle">
        <input type="number" name="product_image[__IDX__][sort_order]" value="0" min="0"
               placeholder="Порядок сортировки" class="form-control" style="max-width:160px;margin-left:auto;">
    </td>
    <td class="align-middle">
        <button type="button" class="oc-btn oc-btn-red oc-btn-sm remove-product-image" title="Удалить">
            <i class="fas fa-minus"></i>
        </button>
    </td>
</tr>
</script>
<script>
(function () {
    var imageRow = {{ isset($product) ? $product->images->count() : 0 }};
    @if(old('product_image') !== null)
        imageRow = {{ count(old('product_image')) }};
    @endif

    $('#addProductImage').on('click', function () {
        var html = $('#productAdditionalImageRowTemplate').html().replace(/__IDX__/g, imageRow++);
        $('#productAdditionalImages tbody').append(html);
    });

    $(document).on('click', '.remove-product-image', function () {
        $(this).closest('tr').remove();
    });
})();
</script>
