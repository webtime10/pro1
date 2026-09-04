@php
    $title = $title ?? ($pageTitle ?? 'Admin');
    $breadcrumbs = $breadcrumbs ?? [];
    $createUrl = $createUrl ?? null;
    $createLabel = $createLabel ?? 'Add';
    $listFormId = $listFormId ?? null;
    $bulkDeleteConfirm = $bulkDeleteConfirm ?? 'Delete selected records?';
@endphp
<section class="content-header oc-page-header">
    <div class="container-fluid">
        <div class="oc-page-header-row">
            <div class="oc-page-header-main">
                @if(count($breadcrumbs))
                    <nav class="oc-breadcrumb" aria-label="breadcrumb">
                        <a href="{{ route('admin.index') }}">Home</a>
                        @foreach($breadcrumbs as $crumb)
                            <span class="oc-breadcrumb-sep">/</span>
                            @if($loop->last)
                                <span class="oc-breadcrumb-current">{{ $crumb['label'] }}</span>
                            @else
                                <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                            @endif
                        @endforeach
                    </nav>
                @endif
                <h1>{{ $title }}</h1>
            </div>
            @if($createUrl || $listFormId)
                <div class="oc-page-header-actions">
                    @if($createUrl)
                        <a href="{{ $createUrl }}" class="btn btn-pro-primary">
                            <i class="fas fa-plus"></i> {{ $createLabel }}
                        </a>
                    @endif
                    @if($listFormId)
                        <button type="submit" form="{{ $listFormId }}" class="btn btn-pro-danger"
                                onclick="if (!$('#{{ $listFormId }} input[name=\'selected[]\']:checked').length) { alert('Select at least one record'); return false; } return confirm(@json($bulkDeleteConfirm));">
                            Delete Selected
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</section>
