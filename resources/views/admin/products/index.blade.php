@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Products Management (1M+)',
    'breadcrumbs' => [
        ['label' => 'Catalog', 'url' => route('admin.categories.index')],
        ['label' => 'Products', 'url' => route('admin.products.index')],
    ],
    'createUrl' => route('admin.products.create'),
    'createLabel' => 'Add Product',
    'listFormId' => 'form-products',
    'bulkDeleteConfirm' => 'Delete selected products?',
])
<section class="content pt-0">
    <div class="container-fluid">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 pl-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="oc-panel oc-filter-panel">
            <div class="oc-panel-heading" data-toggle="collapse" data-target="#oc-filter-body" aria-expanded="true">
                <i class="fas fa-filter"></i> Filter Panel
                <span class="oc-panel-toggle"><i class="fas fa-chevron-up"></i></span>
            </div>
            <div id="oc-filter-body" class="collapse show">
                <div class="oc-panel-body">
                    <form method="get" action="{{ route('admin.products.index') }}" class="form-row align-items-end">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="admin-products-filter-q">Search (SKU / model / name)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                </div>
                                <input type="text" name="search" id="admin-products-filter-q"
                                       value="{{ $filters['search'] }}"
                                       class="form-control" placeholder="Search…"
                                       autocomplete="off">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-6 mb-3">
                            <label for="filter-status">Status</label>
                            <select name="status" id="filter-status" class="form-control">
                                <option value="">All statuses</option>
                                <option value="1" @selected((string) $filters['status'] === '1')>Active</option>
                                <option value="0" @selected((string) $filters['status'] === '0')>Hidden</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6 mb-3">
                            <label for="filter-qty">Quantity</label>
                            <select name="quantity_filter" id="filter-qty" class="form-control">
                                <option value="">All</option>
                                <option value="out_of_stock" @selected($filters['quantity_filter'] === 'out_of_stock')>Out of stock</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label for="filter-category">Category</label>
                            <select name="category_id" id="filter-category" class="form-control">
                                <option value="">All categories</option>
                                @foreach($categoryOptions as $opt)
                                    <option value="{{ $opt['id'] }}" @selected((string) $filters['category_id'] === (string) $opt['id'])>
                                        {{ $opt['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6 mb-3">
                            <div class="oc-filter-actions">
                                <button type="submit" class="btn btn-pro-primary btn-block">Apply Filters</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="oc-panel">
            <div class="oc-panel-body table-responsive p-0">
                <form id="form-products" method="post" action="{{ route('admin.products.destroy-selected') }}">
                    @csrf
                    <table class="table oc-table oc-table-catalog mb-0">
                        <thead>
                            <tr>
                                <th class="oc-col-check">
                                    <input type="checkbox" title="Select all"
                                           onclick="document.querySelectorAll('#form-products .product-select').forEach(cb => cb.checked = this.checked);">
                                </th>
                                <th style="width:70px">ID</th>
                                <th style="width:56px">Photo</th>
                                <th>Name</th>
                                <th>SKU</th>
                                <th style="width:100px">Quantity</th>
                                <th style="width:80px">Active</th>
                                <th class="oc-col-action">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                @php
                                    $name = $product->list_name ?: ($product->model ?: '#'.$product->id);
                                    $img = $product->image
                                        ? (str_starts_with($product->image, 'http')
                                            ? $product->image
                                            : asset('storage/'.ltrim($product->image, '/')))
                                        : null;
                                    $formId = 'product-quick-'.$product->id;
                                    $viewUrl = $product->list_slug ? url('/'.$product->list_slug) : null;
                                @endphp
                                <tr>
                                    <td class="oc-col-check">
                                        <input type="checkbox" class="product-select" name="selected[]" value="{{ $product->id }}">
                                    </td>
                                    <td>{{ $product->id }}</td>
                                    <td>
                                        @if($img)
                                            <img src="{{ $img }}" alt="" class="oc-thumb">
                                        @else
                                            <span class="oc-thumb-placeholder" aria-hidden="true"></span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.products.edit', $product->id) }}">{{ $name }}</a>
                                    </td>
                                    <td><span class="oc-sku">{{ $product->sku ?: '—' }}</span></td>
                                    <td>
                                        <input form="{{ $formId }}" type="number" min="0" name="quantity"
                                               class="oc-qty-input"
                                               value="{{ $product->quantity }}">
                                    </td>
                                    <td class="text-center">
                                        <input form="{{ $formId }}" type="checkbox" name="status" value="1" @checked($product->status)>
                                    </td>
                                    <td class="oc-col-action">
                                        @include('admin.partials.oc-row-actions', [
                                            'saveFormId' => $formId,
                                            'editUrl' => route('admin.products.edit', $product->id),
                                            'viewUrl' => $viewUrl,
                                            'destroyUrl' => route('admin.products.destroy', $product->id),
                                            'destroyConfirm' => 'Delete this product?',
                                        ])
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No products match the filter</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </form>
                @foreach($products as $product)
                    <form id="product-quick-{{ $product->id }}" method="post"
                          action="{{ route('admin.products.quick-update', $product->id) }}" class="d-none">
                        @csrf
                        <input type="hidden" name="price" value="{{ $product->price }}">
                    </form>
                @endforeach
            </div>
            <div class="oc-panel-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="small text-muted">
                    @if($products->total())
                        {{ $products->firstItem() }}–{{ $products->lastItem() }}
                        of {{ number_format($products->total(), 0, '.', ' ') }}
                    @else
                        0 products
                    @endif
                </span>
                <div>
                    {{ $products->onEachSide(2)->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('admin_scripts')
<script>
document.querySelectorAll('.oc-filter-panel .oc-panel-heading').forEach(function (heading) {
    heading.addEventListener('click', function () {
        var icon = heading.querySelector('.oc-panel-toggle i');
        if (!icon) return;
        setTimeout(function () {
            var open = document.getElementById('oc-filter-body').classList.contains('show');
            icon.className = open ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
        }, 50);
    });
});
</script>
@endpush
