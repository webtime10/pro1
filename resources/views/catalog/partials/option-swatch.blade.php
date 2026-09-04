@if(!empty($color))
    <span class="opt-swatch" style="background:{{ $color }}"></span>
@elseif(!empty($image))
    <span class="opt-swatch opt-swatch--img"><img src="{{ $image }}" alt="" loading="lazy" width="20" height="20"></span>
@endif
