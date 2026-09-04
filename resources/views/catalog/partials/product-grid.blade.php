@if($products->isEmpty())
    <p class="muted">Товары не найдены.</p>
@else
    <div class="product-grid" data-product-grid>
        @include('catalog.partials.product-tiles', ['products' => $products, 'lang' => $lang])
    </div>

    <div class="catalog-pager" id="wt-filter-pagination" data-catalog-pager>
        <div class="pagination-meta muted">
            {{ $products->firstItem() }}–{{ $products->lastItem() }}
            из {{ number_format($products->total(), 0, '.', ' ') }}
            (стр. {{ $products->currentPage() }} / {{ $products->lastPage() }})
        </div>
        <div class="pagination-wrap">
            {{ $products->onEachSide(2)->withQueryString()->links('pagination::bootstrap-4') }}
        </div>
        @if($products->hasMorePages())
            <div class="load-more-wrap">
                <button
                    type="button"
                    class="btn-load-more"
                    data-load-more
                    data-url="{{ url()->current() }}"
                    data-next-page="{{ $products->currentPage() + 1 }}"
                    data-last-page="{{ $products->lastPage() }}"
                    data-query="{{ http_build_query(request()->except('page', 'ajax')) }}"
                >
                    Показать ещё
                </button>
            </div>
        @endif
    </div>
@endif
