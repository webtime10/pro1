@php
    $createUrl = $createUrl ?? null;
    $createTitle = $createTitle ?? 'Add';
    $createLabel = $createLabel ?? $createTitle;
    $listFormId = $listFormId ?? null;
    $bulkDeleteConfirm = $bulkDeleteConfirm ?? 'Delete selected records?';
@endphp
@if($createUrl || $listFormId)
<div class="container-fluid">
    <div class="oc-toolbar">
        @isset($toolbarLeft)
            <div class="oc-toolbar-left">{!! $toolbarLeft !!}</div>
        @endisset
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
</div>
@endif
