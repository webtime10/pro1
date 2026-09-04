@php
    $optionId = $row['option_id'] ?? '';
    $selectedOption = $allOptions->firstWhere('id', (int) $optionId);
    $optionType = $selectedOption?->type ?? '';
    $hasValues = in_array($optionType, ['select', 'radio', 'checkbox'], true);
    $valueRows = $row['product_option_value'] ?? [];
@endphp
<div class="card card-outline card-secondary mb-3 product-option-block" data-idx="{{ $idx }}">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-5">
                <select name="product_option[{{ $idx }}][option_id]" class="form-control product-option-select" required>
                    <option value="">— выберите опцию —</option>
                    @foreach($allOptions as $opt)
                        @php $optName = $opt->descriptions->firstWhere('language_id', $defaultLanguage?->id)?->name ?? '#'.$opt->id; @endphp
                        <option value="{{ $opt->id }}" data-type="{{ $opt->type }}" {{ (string)$optionId === (string)$opt->id ? 'selected' : '' }}>
                            {{ $optName }} ({{ $opt->type }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="mb-0">
                    <input type="hidden" name="product_option[{{ $idx }}][required]" value="0">
                    <input type="checkbox" name="product_option[{{ $idx }}][required]" value="1" {{ !empty($row['required']) ? 'checked' : '' }}> Обязательная
                </label>
            </div>
            <div class="col-md-3 text-right">
                <button type="button" class="oc-btn oc-btn-red oc-btn-sm remove-product-option" title="Удалить"><i class="fas fa-trash-alt"></i></button>
            </div>
        </div>
    </div>
    <div class="card-body p-2">
        <div class="product-option-value-field {{ $hasValues ? 'd-none' : '' }}">
            <label>Значение (text / textarea / date / file…)</label>
            <input type="text" name="product_option[{{ $idx }}][value]" class="form-control" value="{{ $row['value'] ?? '' }}">
        </div>
        <div class="product-option-values-table {{ $hasValues ? '' : 'd-none' }}">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Значение опции</th>
                        <th>Кол-во</th>
                        <th>Вычитать</th>
                        <th>Цена +/-</th>
                        <th>Баллы +/-</th>
                        <th>Вес +/-</th>
                    </tr>
                </thead>
                <tbody>
                    @if($hasValues && $selectedOption)
                        @foreach($selectedOption->values as $vIdx => $optVal)
                            @php
                                $vDesc = $optVal->descriptions->firstWhere('language_id', $defaultLanguage?->id);
                                $saved = collect($valueRows)->firstWhere('option_value_id', $optVal->id) ?? [];
                            @endphp
                            <tr>
                                <td>
                                    {{ $vDesc->name ?? '#'.$optVal->id }}
                                    <input type="hidden" name="product_option[{{ $idx }}][product_option_value][{{ $vIdx }}][option_value_id]" value="{{ $optVal->id }}">
                                </td>
                                <td><input type="number" min="0" class="form-control form-control-sm" name="product_option[{{ $idx }}][product_option_value][{{ $vIdx }}][quantity]" value="{{ $saved['quantity'] ?? 0 }}"></td>
                                <td class="text-center">
                                    <input type="hidden" name="product_option[{{ $idx }}][product_option_value][{{ $vIdx }}][subtract]" value="0">
                                    <input type="checkbox" name="product_option[{{ $idx }}][product_option_value][{{ $vIdx }}][subtract]" value="1" {{ !empty($saved['subtract']) ? 'checked' : '' }}>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <select name="product_option[{{ $idx }}][product_option_value][{{ $vIdx }}][price_prefix]" class="form-control">
                                            <option value="+" {{ ($saved['price_prefix'] ?? '+') === '+' ? 'selected' : '' }}>+</option>
                                            <option value="-" {{ ($saved['price_prefix'] ?? '') === '-' ? 'selected' : '' }}>-</option>
                                        </select>
                                        <input type="number" step="0.0001" class="form-control" name="product_option[{{ $idx }}][product_option_value][{{ $vIdx }}][price]" value="{{ $saved['price'] ?? 0 }}">
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <select name="product_option[{{ $idx }}][product_option_value][{{ $vIdx }}][points_prefix]" class="form-control">
                                            <option value="+" {{ ($saved['points_prefix'] ?? '+') === '+' ? 'selected' : '' }}>+</option>
                                            <option value="-" {{ ($saved['points_prefix'] ?? '') === '-' ? 'selected' : '' }}>-</option>
                                        </select>
                                        <input type="number" class="form-control" name="product_option[{{ $idx }}][product_option_value][{{ $vIdx }}][points]" value="{{ $saved['points'] ?? 0 }}">
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <select name="product_option[{{ $idx }}][product_option_value][{{ $vIdx }}][weight_prefix]" class="form-control">
                                            <option value="+" {{ ($saved['weight_prefix'] ?? '+') === '+' ? 'selected' : '' }}>+</option>
                                            <option value="-" {{ ($saved['weight_prefix'] ?? '') === '-' ? 'selected' : '' }}>-</option>
                                        </select>
                                        <input type="number" step="0.00000001" class="form-control" name="product_option[{{ $idx }}][product_option_value][{{ $vIdx }}][weight]" value="{{ $saved['weight'] ?? 0 }}">
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
