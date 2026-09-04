<tr class="product-attribute-row">
    <td>
        <select name="product_attribute[{{ $idx }}][attribute_id]" class="form-control form-control-sm" required>
            <option value="">—</option>
            @foreach($allAttributes as $attr)
                @php
                    $attrName = $attr->descriptions->firstWhere('language_id', $defaultLanguage?->id)?->name ?? '#'.$attr->id;
                    $groupName = $attr->attributeGroup?->descriptions->firstWhere('language_id', $defaultLanguage?->id)?->name ?? '';
                @endphp
                <option value="{{ $attr->id }}" {{ (string)($row['attribute_id'] ?? '') === (string)$attr->id ? 'selected' : '' }}>
                    {{ $groupName ? $groupName.' > ' : '' }}{{ $attrName }}
                </option>
            @endforeach
        </select>
    </td>
    @foreach($languages as $language)
        <td>
            <input type="text" name="product_attribute[{{ $idx }}][text][{{ $language->id }}]" class="form-control form-control-sm"
                   value="{{ $row['text'][$language->id] ?? '' }}">
        </td>
    @endforeach
    <td class="text-center">
    <button type="button" class="oc-btn oc-btn-red oc-btn-sm remove-product-attribute" title="Удалить"><i class="fas fa-minus"></i></button>
    </td>
</tr>
