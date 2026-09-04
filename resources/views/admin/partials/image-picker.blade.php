@php
    $inputId = $inputId ?? 'input-image';
    $thumbId = $thumbId ?? 'thumb-image';
    $name = $name ?? 'image';
    $value = $value ?? '';
    $size = $size ?? 'md';
    $placeholder = $placeholder ?? asset('assets/admin/img/no_image.svg');
    $src = $value ? catalog_image_url($value) : $placeholder;
@endphp
<div class="oc-image-picker oc-image-picker--{{ $size }}">
    <a href="#" id="{{ $thumbId }}" data-toggle="image" class="img-thumbnail">
        <img src="{{ $src }}" alt="" data-placeholder="{{ $placeholder }}">
    </a>
    <input type="hidden" name="{{ $name }}" id="{{ $inputId }}" value="{{ $value }}">
</div>
