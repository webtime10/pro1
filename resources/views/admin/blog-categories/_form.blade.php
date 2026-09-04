@php
    $category = $category ?? null;
    $isEdit = $category !== null;
    $cVal = function ($field, $language, $default = '') use ($isEdit, $category) {
        $desc = $isEdit ? $category->descriptions->firstWhere('language_id', $language->id) : null;
        return old($field.'_'.$language->code, $desc->{$field} ?? $default);
    };
@endphp

@if($languages->isEmpty())
    <p class="text-muted small mb-3">
        Языков пока нет — добавьте их в разделе <a href="{{ route('admin.languages.index') }}">«Языки»</a>.
    </p>
@else
    <ul class="nav nav-tabs" role="tablist">
        @foreach($languages as $index => $language)
            <li class="nav-item">
                <a class="nav-link @if($index === 0) active @endif" data-toggle="tab" href="#pane-lang-{{ $language->code }}">
                    {{ strtolower($language->code) }}
                </a>
            </li>
        @endforeach
    </ul>
    <div class="tab-content border border-top-0 rounded-bottom p-3 mb-4 bg-light">
        @foreach($languages as $index => $language)
            @php $c = $language->code; @endphp
            <div class="tab-pane fade @if($index === 0) show active @endif" id="pane-lang-{{ $c }}">
                <div class="form-group">
                    <label for="name_{{ $c }}">Название <span class="text-danger">*</span></label>
                    <input type="text" name="name_{{ $c }}" id="name_{{ $c }}" class="form-control form-control-lg"
                           value="{{ $cVal('name', $language) }}">
                </div>
                <div class="form-group">
                    <label for="slug_{{ $c }}">Slug (ЧПУ) <span class="text-danger">*</span></label>
                    <input type="text" name="slug_{{ $c }}" id="slug_{{ $c }}" class="form-control"
                           value="{{ $cVal('slug', $language) }}"
                           data-slug-locked="{{ $cVal('slug', $language) !== '' ? '1' : '0' }}" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>H1</label>
                    <input type="text" name="meta_h1_{{ $c }}" class="form-control" value="{{ $cVal('meta_h1', $language) }}">
                </div>
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description_{{ $c }}" class="form-control" rows="4">{{ $cVal('description', $language) }}</textarea>
                </div>
                <div class="form-group">
                    <label>Meta title</label>
                    <input type="text" name="meta_title_{{ $c }}" class="form-control" value="{{ $cVal('meta_title', $language) }}">
                </div>
                <div class="form-group">
                    <label>Meta description</label>
                    <input type="text" name="meta_description_{{ $c }}" class="form-control" value="{{ $cVal('meta_description', $language) }}">
                </div>
                <div class="form-group mb-0">
                    <label>Meta keyword</label>
                    <input type="text" name="meta_keyword_{{ $c }}" class="form-control" value="{{ $cVal('meta_keyword', $language) }}">
                </div>
            </div>
        @endforeach
    </div>
@endif

<div class="form-group">
    <label>Изображение</label>
    @include('admin.partials.image-picker', [
        'name' => 'image',
        'value' => old('image', $category->image ?? ''),
        'inputId' => 'input-blog-category-image',
        'thumbId' => 'thumb-blog-category-image',
    ])
</div>

<div class="form-group">
    <label for="parent_id">Родитель</label>
    <select name="parent_id" id="parent_id" class="form-control">
        <option value="">— Корень блога —</option>
        @foreach($parentOptions as $opt)
            <option value="{{ $opt['id'] }}" {{ (string) old('parent_id', $category->parent_id ?? '') === (string) $opt['id'] ? 'selected' : '' }}>
                {{ $opt['label'] }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="sort_order">Порядок</label>
    <input type="number" name="sort_order" id="sort_order" class="form-control" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0" style="max-width:12rem;">
</div>

<div class="form-group">
    <input type="hidden" name="top" value="0">
    <label class="mr-3"><input type="checkbox" name="top" value="1" {{ old('top', $category->top ?? false) ? 'checked' : '' }}> В меню блога (top)</label>
    <input type="hidden" name="status" value="0">
    <label><input type="checkbox" name="status" value="1" {{ old('status', $category->status ?? true) ? 'checked' : '' }}> Активна</label>
</div>
