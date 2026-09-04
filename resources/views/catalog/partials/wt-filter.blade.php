@php($wt_limit = $wt_limit ?? 8)
<style id="wt-filter-theme">
:root {
  --wt-accent: {{ $accent_color ?? '#229ac8' }};
  --wt-accent-rgb: {{ $accent_rgb ?? '34, 154, 200' }};
}
</style>
<link rel="stylesheet" href="{{ asset('assets/wt_filter/wt_filter.css') }}?v=7">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<button type="button" id="wt-filter-fab" class="wt-filter-fab" data-wt-mobile-open aria-controls="wt-filter" aria-expanded="false">
  <i class="fa-solid fa-sliders"></i>
  <span>{{ $text_filter_short }}</span>
</button>

<div id="wt-filter" class="wt-filter"
     style="--wt-accent: {{ $accent_color ?? '#229ac8' }}; --wt-accent-rgb: {{ $accent_rgb ?? '34, 154, 200' }};"
     data-callback="{{ $callback }}"
     data-refresh="{{ $refresh }}"
     data-search-manufacturers="{{ $search_manufacturers }}"
     data-path="{{ $path }}"
     data-category-id="{{ $category_id }}"
     data-wt-inline="1"
     data-manufacturer-id="{{ $manufacturer_id ?? 0 }}"
     data-special="{{ $special ?? 0 }}"
     data-url-key="{{ $url_key }}"
     data-category-url="{{ $category_url }}"
     data-text-more="{{ e($text_show_more) }}"
     data-text-less="{{ e($text_show_less) }}"
     data-text-brand-empty="{{ e($text_search_manufacturer_empty) }}"
     data-show-counts="{{ !empty($show_counts) ? '1' : '0' }}"
     data-values-limit="{{ $wt_limit }}"
     data-currency="{{ e($currency_symbol ?? '') }}">
  <div class="wt-filter__backdrop" data-wt-mobile-close></div>

  <div class="wt-filter__drawer">
    <div class="wt-filter__m-header">
      <button type="button" class="wt-filter__m-icon-btn" data-wt-mobile-close aria-label="{{ $text_cancel }}">
        <i class="fa-solid fa-angle-left"></i>
      </button>
      <div class="wt-filter__m-heading">{{ $text_filters_title }}</div>
      <button type="button" class="wt-filter__m-icon-btn" data-wt-mobile-close aria-label="{{ $text_cancel }}">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="wt-filter__heading wt-filter__heading--desktop"><i class="fa-solid fa-filter"></i> {{ $heading_title }}</div>

    <div class="wt-filter__scroll">
      <div class="wt-filter__body">
        <form id="wt-filter-form" onsubmit="return false;">
          <div class="wt-filter__status wt-filter__status--desktop" data-role="status">{{ sprintf($text_show_total, $total) }}</div>

          @if(empty($options) && empty($manufacturers) && empty($stock) && ($price['max'] ?? 0) <= ($price['min'] ?? 0))
            <p class="wt-filter__empty">{{ $text_empty_filter }}</p>
          @endif

          @if(($price['max'] ?? 0) > ($price['min'] ?? 0))
          <div class="wt-filter__group is-expanded" data-option="p" data-type="price" data-title="{{ e($text_price) }}"
               data-expanded-desktop="{{ $group_expanded['p']['desktop'] ?? 1 }}"
               data-expanded-mobile="{{ $group_expanded['p']['mobile'] ?? 0 }}">
            <div class="wt-filter__title">{{ $text_price }}</div>
            <div class="wt-filter__panel">
            <div class="wt-filter__price">
              <input type="number" class="wt-filter__input" data-price="from" value="{{ $price['from'] }}" min="{{ $price['min'] }}" max="{{ $price['max'] }}" step="1">
              <span class="wt-filter__price-sep">—</span>
              <input type="number" class="wt-filter__input" data-price="to" value="{{ $price['to'] }}" min="{{ $price['min'] }}" max="{{ $price['max'] }}" step="1">
            </div>
            <div class="wt-filter__dual-slider" data-min="{{ $price['min'] }}" data-max="{{ $price['max'] }}">
              <div class="wt-filter__dual-track">
                <div class="wt-filter__dual-fill" data-role="fill"></div>
              </div>
              <input type="range" class="wt-filter__range wt-filter__range--from" data-price-range="from" min="{{ $price['min'] }}" max="{{ $price['max'] }}" value="{{ $price['from'] }}" step="1">
              <input type="range" class="wt-filter__range wt-filter__range--to" data-price-range="to" min="{{ $price['min'] }}" max="{{ $price['max'] }}" value="{{ $price['to'] }}" step="1">
            </div>
            </div>
          </div>
          @endif

          @if(!empty($manufacturers))
          <div class="wt-filter__group is-expanded" data-option="m" data-type="checkbox" data-title="{{ e($text_manufacturer) }}"
               data-expanded-desktop="{{ $group_expanded['m']['desktop'] ?? 1 }}"
               data-expanded-mobile="{{ $group_expanded['m']['mobile'] ?? 0 }}">
            <div class="wt-filter__title">{{ $text_manufacturer }}</div>
            <div class="wt-filter__panel">
            <div class="wt-filter__values">
              @foreach($manufacturers as $value)
              <label class="wt-filter__value{{ !empty($value['disabled']) ? ' is-disabled' : '' }}{{ !empty($value['selected']) ? ' is-selected' : '' }}{{ $loop->iteration > $wt_limit && empty($value['selected']) ? ' is-collapsed' : '' }}">
                <input type="checkbox" data-value-id="m{{ $value['value_id'] }}" value="{{ $value['value_id'] }}"
                       @checked(!empty($value['selected'])) @disabled(!empty($value['disabled']))>
                <span class="wt-filter__name">{{ $value['name'] }}</span>
                @if(!empty($show_counts))
                  <span class="wt-filter__count" data-count-key="m{{ $value['value_id'] }}">{{ $value['count'] }}</span>
                @endif
              </label>
              @endforeach
            </div>
            @if(count($manufacturers) > $wt_limit)
              <button type="button" class="wt-filter__more" aria-expanded="false">{{ sprintf($text_show_more, count($manufacturers) - $wt_limit) }} <i class="fa-solid fa-angle-down"></i></button>
            @endif
            </div>
          </div>
          @endif

          @if(!empty($stock))
          <div class="wt-filter__group is-expanded" data-option="s" data-type="checkbox" data-title="{{ e($text_stock) }}"
               data-expanded-desktop="{{ $group_expanded['s']['desktop'] ?? 1 }}"
               data-expanded-mobile="{{ $group_expanded['s']['mobile'] ?? 0 }}">
            <div class="wt-filter__title">{{ $text_stock }}</div>
            <div class="wt-filter__panel">
            <div class="wt-filter__values">
              @foreach($stock as $value)
              <label class="wt-filter__value{{ !empty($value['disabled']) ? ' is-disabled' : '' }}{{ !empty($value['selected']) ? ' is-selected' : '' }}">
                <input type="checkbox" data-value-id="s{{ $value['value_id'] }}" value="{{ $value['value_id'] }}"
                       @checked(!empty($value['selected'])) @disabled(!empty($value['disabled']))>
                <span class="wt-filter__name">{{ $value['name'] }}</span>
                @if(!empty($show_counts))
                  <span class="wt-filter__count" data-count-key="s{{ $value['value_id'] }}">{{ $value['count'] }}</span>
                @endif
              </label>
              @endforeach
            </div>
            </div>
          </div>
          @endif

          @foreach($options as $option)
          <div class="wt-filter__group is-expanded"
               data-option="{{ $option['option_id'] }}"
               data-type="{{ $option['type'] }}"
               data-title="{{ e($option['name']) }}"
               data-expanded-desktop="{{ $option['expanded_desktop'] ?? 1 }}"
               data-expanded-mobile="{{ $option['expanded_mobile'] ?? 0 }}">
            <div class="wt-filter__title">{{ $option['name'] }}</div>
            <div class="wt-filter__panel">

            @if(in_array($option['type'], ['checkbox_image', 'radio_image'], true))
              <div class="wt-filter__values wt-filter__values--color">
                @foreach($option['values'] as $value)
                <label class="wt-filter__value wt-filter__value--color{{ !empty($value['disabled']) ? ' is-disabled' : '' }}{{ !empty($value['selected']) ? ' is-selected' : '' }}{{ $loop->iteration > $wt_limit && empty($value['selected']) ? ' is-collapsed' : '' }}" title="{{ $value['name'] }}">
                  <input type="{{ str_starts_with($option['type'], 'radio') ? 'radio' : 'checkbox' }}"
                         name="wt_opt_{{ $option['option_id'] }}"
                         data-value-id="{{ $option['option_id'] }}{{ $value['value_id'] }}"
                         value="{{ $value['value_id'] }}"
                         @checked(!empty($value['selected'])) @disabled(!empty($value['disabled']))>
                  @if(!empty($value['color']))
                    <span class="wt-filter__swatch" style="background:{{ $value['color'] }}"></span>
                  @elseif(!empty($value['image']))
                    <span class="wt-filter__swatch wt-filter__swatch--img"><img src="{{ $value['image'] }}" alt="" loading="lazy" width="20" height="20"></span>
                  @else
                    <span class="wt-filter__swatch wt-filter__swatch--empty"></span>
                  @endif
                  <span class="wt-filter__name">{{ $value['name'] }}</span>
                  @if(!empty($show_counts))
                    <span class="wt-filter__count" data-count-key="{{ $option['option_id'] }}{{ $value['value_id'] }}">{{ $value['count'] }}</span>
                  @endif
                </label>
                @endforeach
              </div>
            @else
              <div class="wt-filter__values">
                @foreach($option['values'] as $value)
                <label class="wt-filter__value{{ !empty($value['disabled']) ? ' is-disabled' : '' }}{{ !empty($value['selected']) ? ' is-selected' : '' }}{{ $loop->iteration > $wt_limit && empty($value['selected']) ? ' is-collapsed' : '' }}">
                  <input type="{{ $option['type'] === 'radio' ? 'radio' : 'checkbox' }}"
                         name="wt_opt_{{ $option['option_id'] }}"
                         data-value-id="{{ $option['option_id'] }}{{ $value['value_id'] }}"
                         value="{{ $value['value_id'] }}"
                         @checked(!empty($value['selected'])) @disabled(!empty($value['disabled']))>
                  @if(!empty($value['color']))
                    <span class="wt-filter__swatch" style="background:{{ $value['color'] }}"></span>
                  @elseif(!empty($value['image']))
                    <span class="wt-filter__swatch wt-filter__swatch--img"><img src="{{ $value['image'] }}" alt="" loading="lazy" width="20" height="20"></span>
                  @endif
                  <span class="wt-filter__name">{{ $value['name'] }}</span>
                  @if(!empty($show_counts))
                    <span class="wt-filter__count" data-count-key="{{ $option['option_id'] }}{{ $value['value_id'] }}">{{ $value['count'] }}</span>
                  @endif
                </label>
                @endforeach
              </div>
            @endif

            @if(count($option['values']) > $wt_limit)
              <button type="button" class="wt-filter__more" aria-expanded="false">{{ sprintf($text_show_more, count($option['values']) - $wt_limit) }} <i class="fa-solid fa-angle-down"></i></button>
            @endif
            </div>
          </div>
          @endforeach
        </form>
      </div>
    </div>

    <div class="wt-filter__m-footer" data-role="mobile-footer">
      <button type="button" class="wt-filter__m-reset" data-wt-reset>{{ $text_reset }}</button>
      <button type="button" class="wt-filter__m-apply" data-wt-mobile-apply>{{ $text_apply_filter }}</button>
    </div>

    <div class="wt-filter__actions wt-filter__actions--desktop">
      <button type="button" class="wt-filter__reset" data-wt-reset>{{ $text_reset }}</button>
    </div>
  </div>
</div>

@once
@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });
</script>
<script src="{{ asset('assets/wt_filter/wt_filter.js') }}?v=7"></script>
@endpush
@endonce
