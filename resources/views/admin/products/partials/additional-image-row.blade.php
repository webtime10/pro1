<tr id="image-row{{ $idx }}">
    <td>
        @include('admin.partials.image-picker', [
            'name' => 'product_image['.$idx.'][image]',
            'value' => $row['image'] ?? '',
            'inputId' => 'additional-image-'.$idx,
            'thumbId' => 'additional-thumb-'.$idx,
            'size' => 'md',
        ])
    </td>
    <td class="text-right align-middle">
        <input type="number" name="product_image[{{ $idx }}][sort_order]"
               value="{{ $row['sort_order'] ?? 0 }}" min="0"
               placeholder="Порядок сортировки"
               class="form-control" style="max-width:160px;margin-left:auto;">
    </td>
    <td class="align-middle">
        <button type="button" class="oc-btn oc-btn-red oc-btn-sm remove-product-image" title="Удалить">
            <i class="fas fa-minus"></i>
        </button>
    </td>
</tr>
