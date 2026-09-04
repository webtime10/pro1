@php
    $formId = $formId ?? 'adminForm';
    $backUrl = $backUrl ?? url()->previous();
    $saveTitle = $saveTitle ?? 'Сохранить';
    $destroyUrl = $destroyUrl ?? null;
@endphp
<div class="container-fluid">
    <div class="oc-toolbar">
        <button type="submit" form="{{ $formId }}" class="oc-btn oc-btn-green" title="{{ $saveTitle }}"><i class="fas fa-save"></i></button>
        <a href="{{ $backUrl }}" class="oc-btn oc-btn-red" title="Назад"><i class="fas fa-reply"></i></a>
        @if($destroyUrl)
            <form action="{{ $destroyUrl }}" method="post" class="d-inline" onsubmit="return confirm('Удалить запись?');">
                @csrf @method('DELETE')
                <button type="submit" class="oc-btn oc-btn-red" title="Удалить"><i class="fas fa-trash-alt"></i></button>
            </form>
        @endif
    </div>
</div>
