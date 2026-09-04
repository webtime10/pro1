@php
    $currentPath = trim(request()->path(), '/');
    $locale = request()->route('locale');
    if ($locale && str_starts_with($currentPath, $locale.'/')) {
        $currentPath = substr($currentPath, strlen($locale) + 1);
    } elseif ($locale && $currentPath === $locale) {
        $currentPath = '';
    }
@endphp
@if(($items ?? collect())->isEmpty())
    <p class="muted" style="padding:0 1rem;">Нет категорий</p>
@else
    <ul>
        @foreach($items as $item)
            @php $href = $item['href'] ?? catalog_category_url($item['id']); @endphp
            <li>
                <a href="{{ $href }}"
                   class="{{ str_ends_with($href, '/'.$item['slug']) || str_ends_with($href, $item['slug']) ? 'is-active' : '' }}">
                    {{ $item['name'] }}
                </a>
                @if(!empty($item['children']) && count($item['children']))
                    <ul class="sub">
                        @foreach($item['children'] as $child)
                            @php $childHref = $child['href'] ?? catalog_category_url($child['id']); @endphp
                            <li>
                                <a href="{{ $childHref }}"
                                   class="{{ str_contains($currentPath, $child['slug']) ? 'is-active' : '' }}">
                                    {{ $child['name'] }}
                                </a>
                                @if(!empty($child['children']) && count($child['children']))
                                    <ul class="sub">
                                        @foreach($child['children'] as $grand)
                                            <li>
                                                <a href="{{ $grand['href'] ?? catalog_category_url($grand['id']) }}"
                                                   class="{{ str_contains($currentPath, $grand['slug']) ? 'is-active' : '' }}">
                                                    {{ $grand['name'] }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
@endif
