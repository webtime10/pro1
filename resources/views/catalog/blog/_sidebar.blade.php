<div class="cat-menu">
    <h2>Блог</h2>
    <ul>
        <li>
            <a href="{{ catalog_blog_url() }}" class="{{ request()->routeIs('catalog.blog.index') || request()->routeIs('localized.catalog.blog.index') ? 'is-active' : '' }}">Все статьи</a>
        </li>
        @foreach($categories as $item)
            @php $cd = $item->descriptions->firstWhere('language_id', $lang->id); @endphp
            @if($cd)
                <li>
                    <a href="{{ catalog_blog_category_url($item, $lang) }}"
                       class="{{ isset($category) && (int) $category->id === (int) $item->id ? 'is-active' : '' }}">{{ $cd->name }}</a>
                </li>
            @endif
        @endforeach
    </ul>
</div>
