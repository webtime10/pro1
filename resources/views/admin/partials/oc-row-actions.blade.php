@php
    $editUrl = $editUrl ?? null;
    $viewUrl = $viewUrl ?? null;
    $saveFormId = $saveFormId ?? null;
    $destroyUrl = $destroyUrl ?? null;
    $destroyConfirm = $destroyConfirm ?? 'Delete?';
@endphp
@if($saveFormId)
    <button type="submit" form="{{ $saveFormId }}" class="oc-btn oc-btn-muted oc-btn-sm" title="Save">
        <i class="fas fa-lock"></i>
    </button>
@endif
@if($editUrl)
    <a href="{{ $editUrl }}" class="oc-btn oc-btn-primary oc-btn-sm" title="Edit">
        <i class="fas fa-pencil-alt"></i>
    </a>
@endif
@if($viewUrl)
    <a href="{{ $viewUrl }}" class="oc-btn oc-btn-primary oc-btn-sm" title="View" target="_blank" rel="noopener">
        <i class="fas fa-eye"></i>
    </a>
@endif
@if($destroyUrl)
    <form action="{{ $destroyUrl }}" method="post" class="d-inline" onsubmit="return confirm(@json($destroyConfirm));">
        @csrf @method('DELETE')
        <button type="submit" class="oc-btn oc-btn-danger oc-btn-sm" title="Delete">
            <i class="fas fa-trash-alt"></i>
        </button>
    </form>
@endif
