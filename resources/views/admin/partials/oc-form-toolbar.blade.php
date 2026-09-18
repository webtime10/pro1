@php
    $formId = $formId ?? 'adminForm';
    $backUrl = $backUrl ?? url()->previous();
    $saveTitle = $saveTitle ?? 'Сохранить';
    $destroyUrl = $destroyUrl ?? null;
    $extraButtons = $extraButtons ?? '';
@endphp
<div class="container-fluid">
    <div class="oc-toolbar">
        <div class="oc-toolbar-left">
            <span class="text-muted small d-none d-md-inline">Действия</span>
        </div>
        {!! $extraButtons !!}
        <button type="submit" form="{{ $formId }}" class="oc-btn oc-btn-green" title="{{ $saveTitle }}"><i class="fas fa-save"></i></button>
        <a href="{{ $backUrl }}" class="oc-btn oc-btn-muted" title="Назад"><i class="fas fa-reply"></i></a>
        @if($destroyUrl)
            <form action="{{ $destroyUrl }}" method="post" class="d-inline mb-0" onsubmit="return confirm('Удалить запись?');">
                @csrf @method('DELETE')
                <button type="submit" class="oc-btn oc-btn-red" title="Удалить"><i class="fas fa-trash-alt"></i></button>
            </form>
        @endif
    </div>
</div>
