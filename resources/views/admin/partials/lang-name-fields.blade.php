@php
    $entity = $entity ?? null;
    $fieldPrefix = $fieldPrefix ?? 'name_';
@endphp
@if($languages->isEmpty())
    <p class="text-muted">Добавьте языки в разделе <a href="{{ route('admin.languages.index') }}">Languages</a>.</p>
@else
    <ul class="nav nav-tabs mb-3">
        @foreach($languages as $i => $language)
            <li class="nav-item">
                <a class="nav-link {{ $i === 0 ? 'active' : '' }}" data-toggle="tab" href="#langpane-{{ $fieldPrefix }}{{ $language->code }}">{{ $language->name }}</a>
            </li>
        @endforeach
    </ul>
    <div class="tab-content border p-3 mb-3">
        @foreach($languages as $i => $language)
            @php
                $c = $language->code;
                $desc = $entity?->descriptions?->firstWhere('language_id', $language->id);
            @endphp
            <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}" id="langpane-{{ $fieldPrefix }}{{ $c }}">
                <div class="form-group mb-0">
                    <label>Название @if($language->is_default)<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="{{ $fieldPrefix }}{{ $c }}" class="form-control"
                           value="{{ old($fieldPrefix.$c, $desc->name ?? '') }}"
                           maxlength="{{ $maxLength ?? 64 }}"
                           {{ $language->is_default ? 'required' : '' }}>
                </div>
            </div>
        @endforeach
    </div>
@endif
