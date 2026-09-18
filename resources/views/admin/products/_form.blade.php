@php
    $isEdit = isset($product);
    $formAction = $isEdit ? route('admin.products.update', $product->id) : route('admin.products.store');
    $formMethod = $isEdit ? 'PUT' : 'POST';
    $selectedCategories = old('category_ids', $isEdit ? $product->categories->pluck('id')->all() : []);
    $oldAttributes = old('product_attribute');
    $oldOptions = old('product_option');
@endphp

<div class="oc-panel oc-form-panel">
<form action="{{ $formAction }}" method="post" id="productForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="card card-outline card-outline-tabs mb-0 border-0">
        <div class="card-header p-0 border-bottom-0 bg-white">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-general">Общие</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-data">Данные</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-links">Связи</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-wt-filter">Опции фильтра</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-attribute">Атрибуты</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-option">Опции</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-image">Изображение</a></li>
            </ul>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <div class="tab-content">
                {{-- General --}}
                <div class="tab-pane fade show active" id="tab-general">
                    <ul class="nav nav-tabs mb-2" role="tablist">
                        @foreach($languages as $i => $language)
                            <li class="nav-item">
                                <a class="nav-link {{ $i === 0 ? 'active' : '' }}" data-toggle="tab" href="#plang{{ $language->id }}">{{ $language->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="tab-content border">
                        @foreach($languages as $i => $language)
                            @php
                                $c = $language->code;
                                $desc = $isEdit ? $product->descriptions->firstWhere('language_id', $language->id) : null;
                            @endphp
                            <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}" id="plang{{ $language->id }}">
                                <div class="form-group">
                                    <label>Название <span class="text-danger">*</span></label>
                                    <input type="text" name="name_{{ $c }}" id="name_{{ $c }}"
                                           class="form-control @error('name_'.$c) is-invalid @enderror"
                                           value="{{ old('name_'.$c, $desc->name ?? '') }}">
                                    @error('name_'.$c)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label>Slug <span class="text-danger">*</span></label>
                                    <input type="text" name="slug_{{ $c }}" id="slug_{{ $c }}"
                                           class="form-control @error('slug_'.$c) is-invalid @enderror"
                                           value="{{ old('slug_'.$c, $desc->slug ?? '') }}"
                                           data-slug-locked="{{ ($desc && ($desc->slug ?? '') !== '') ? '1' : '0' }}"
                                           autocomplete="off">
                                    @error('slug_'.$c)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label>Описание</label>
                                    <textarea name="description_{{ $c }}" class="form-control" rows="5">{{ old('description_'.$c, $desc->description ?? '') }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Теги</label>
                                    <textarea name="tag_{{ $c }}" class="form-control" rows="2">{{ old('tag_'.$c, $desc->tag ?? '') }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Meta Title</label>
                                    <input type="text" name="meta_title_{{ $c }}" class="form-control" value="{{ old('meta_title_'.$c, $desc->meta_title ?? '') }}">
                                </div>
                                <div class="form-group">
                                    <label>Meta Description</label>
                                    <textarea name="meta_description_{{ $c }}" class="form-control" rows="2">{{ old('meta_description_'.$c, $desc->meta_description ?? '') }}</textarea>
                                </div>
                                <div class="form-group mb-0">
                                    <label>Meta Keyword</label>
                                    <textarea name="meta_keyword_{{ $c }}" class="form-control" rows="2">{{ old('meta_keyword_'.$c, $desc->meta_keyword ?? '') }}</textarea>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Data --}}
                <div class="tab-pane fade" id="tab-data">
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Model <span class="text-danger">*</span></label>
                            <input type="text" name="model" class="form-control" value="{{ old('model', $product->model ?? '') }}" required maxlength="64">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>SKU</label>
                            <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku ?? '') }}" maxlength="64">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Location</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location', $product->location ?? '') }}" maxlength="128">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 form-group"><label>UPC</label><input type="text" name="upc" class="form-control" value="{{ old('upc', $product->upc ?? '') }}" maxlength="12"></div>
                        <div class="col-md-3 form-group"><label>EAN</label><input type="text" name="ean" class="form-control" value="{{ old('ean', $product->ean ?? '') }}" maxlength="14"></div>
                        <div class="col-md-3 form-group"><label>JAN</label><input type="text" name="jan" class="form-control" value="{{ old('jan', $product->jan ?? '') }}" maxlength="13"></div>
                        <div class="col-md-3 form-group"><label>ISBN</label><input type="text" name="isbn" class="form-control" value="{{ old('isbn', $product->isbn ?? '') }}" maxlength="17"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group"><label>MPN</label><input type="text" name="mpn" class="form-control" value="{{ old('mpn', $product->mpn ?? '') }}" maxlength="64"></div>
                        <div class="col-md-4 form-group">
                            <label>Цена</label>
                            <input type="number" step="0.0001" min="0" name="price" class="form-control" value="{{ old('price', $product->price ?? 0) }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Количество</label>
                            <input type="number" min="0" name="quantity" class="form-control" value="{{ old('quantity', $product->quantity ?? 0) }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 form-group"><label>Мин. заказ</label><input type="number" min="1" name="minimum" class="form-control" value="{{ old('minimum', $product->minimum ?? 1) }}"></div>
                        <div class="col-md-3 form-group"><label>Баллы</label><input type="number" min="0" name="points" class="form-control" value="{{ old('points', $product->points ?? 0) }}"></div>
                        <div class="col-md-3 form-group"><label>Порядок</label><input type="number" min="0" name="sort_order" class="form-control" value="{{ old('sort_order', $product->sort_order ?? 0) }}"></div>
                        <div class="col-md-3 form-group">
                            <label>Дата поступления</label>
                            <input type="date" name="date_available" class="form-control" value="{{ old('date_available', isset($product->date_available) ? $product->date_available?->format('Y-m-d') : '') }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 form-group"><label>Вес</label><input type="number" step="0.00000001" min="0" name="weight" class="form-control" value="{{ old('weight', $product->weight ?? 0) }}"></div>
                        <div class="col-md-3 form-group"><label>Длина</label><input type="number" step="0.00000001" min="0" name="length" class="form-control" value="{{ old('length', $product->length ?? 0) }}"></div>
                        <div class="col-md-3 form-group"><label>Ширина</label><input type="number" step="0.00000001" min="0" name="width" class="form-control" value="{{ old('width', $product->width ?? 0) }}"></div>
                        <div class="col-md-3 form-group"><label>Высота</label><input type="number" step="0.00000001" min="0" name="height" class="form-control" value="{{ old('height', $product->height ?? 0) }}"></div>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="subtract" value="0">
                        <label class="mr-3"><input type="checkbox" name="subtract" value="1" {{ old('subtract', $product->subtract ?? true) ? 'checked' : '' }}> Вычитать со склада</label>
                        <input type="hidden" name="shipping" value="0">
                        <label class="mr-3"><input type="checkbox" name="shipping" value="1" {{ old('shipping', $product->shipping ?? true) ? 'checked' : '' }}> Требуется доставка</label>
                        <input type="hidden" name="status" value="0">
                        <label><input type="checkbox" name="status" value="1" {{ old('status', $product->status ?? true) ? 'checked' : '' }}> Статус</label>
                    </div>
                </div>

                {{-- Links --}}
                <div class="tab-pane fade" id="tab-links">
                    <div class="form-group">
                        <label>Производитель</label>
                        <select name="manufacturer_id" class="form-control">
                            <option value="">—</option>
                            @foreach($manufacturers as $m)
                                <option value="{{ $m->id }}" {{ (string) old('manufacturer_id', $product->manufacturer_id ?? '') === (string) $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Категории <span class="text-danger">*</span></label>
                        <div style="max-height:280px;overflow:auto;border:1px solid #ddd;padding:10px;border-radius:4px;">
                            @foreach($categoryOptions as $opt)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="category_ids[]" value="{{ $opt['id'] }}" id="cat{{ $opt['id'] }}"
                                        {{ in_array($opt['id'], $selectedCategories) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="cat{{ $opt['id'] }}">{{ $opt['label'] }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- WT Filter — опции фильтра (как вкладка в OpenCart) --}}
                <div class="tab-pane fade" id="tab-wt-filter">
                    <p class="text-muted mb-0" id="wt-filter-tab-placeholder">Сначала выберите категорию для этого товара (вкладка «Связи»).</p>
                </div>

                {{-- Attributes --}}
                <div class="tab-pane fade" id="tab-attribute">
                    <div class="mb-2">
                        <button type="button" class="oc-btn oc-btn-green oc-btn-sm" id="addProductAttribute" title="Добавить атрибут"><i class="fas fa-plus"></i></button>
                    </div>
                    <table class="table table-bordered" id="productAttributeTable">
                        <thead>
                            <tr>
                                <th style="width:35%">Атрибут</th>
                                @foreach($languages as $language)
                                    <th>{{ $language->code }}</th>
                                @endforeach
                                <th style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $attributeRows = $oldAttributes;
                                if ($attributeRows === null && $isEdit) {
                                    $attributeRows = $product->productAttributes->groupBy('attribute_id')->map(function ($items, $attrId) use ($languages) {
                                        $texts = [];
                                        foreach ($languages as $lang) {
                                            $row = $items->firstWhere('language_id', $lang->id);
                                            $texts[$lang->id] = $row->text ?? '';
                                        }
                                        return ['attribute_id' => $attrId, 'text' => $texts];
                                    })->values()->all();
                                }
                                $attributeRows = $attributeRows ?: [];
                            @endphp
                            @foreach($attributeRows as $idx => $row)
                                @include('admin.products.partials.attribute-row', ['idx' => $idx, 'row' => $row])
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Options --}}
                <div class="tab-pane fade" id="tab-option">
                    <div class="mb-2">
                        <button type="button" class="oc-btn oc-btn-green oc-btn-sm" id="addProductOption" title="Добавить опцию"><i class="fas fa-plus"></i></button>
                    </div>
                    <div id="productOptionsContainer">
                        @php
                            $optionRows = $oldOptions;
                            if ($optionRows === null && $isEdit) {
                                $optionRows = $product->productOptions->map(function ($po) {
                                    return [
                                        'option_id' => $po->option_id,
                                        'value' => $po->value,
                                        'required' => $po->required,
                                        'product_option_value' => $po->values->map(fn ($v) => [
                                            'option_value_id' => $v->option_value_id,
                                            'quantity' => $v->quantity,
                                            'subtract' => $v->subtract,
                                            'price' => $v->price,
                                            'price_prefix' => $v->price_prefix,
                                            'points' => $v->points,
                                            'points_prefix' => $v->points_prefix,
                                            'weight' => $v->weight,
                                            'weight_prefix' => $v->weight_prefix,
                                        ])->all(),
                                    ];
                                })->all();
                            }
                            $optionRows = $optionRows ?: [];
                        @endphp
                        @foreach($optionRows as $idx => $row)
                            @include('admin.products.partials.option-row', ['idx' => $idx, 'row' => $row])
                        @endforeach
                    </div>
                </div>

                {{-- Image --}}
                <div class="tab-pane fade" id="tab-image">
                    <div class="form-group">
                        <label>Изображение</label>
                        @include('admin.partials.image-picker', [
                            'name' => 'image',
                            'value' => old('image', $product->image ?? ''),
                            'inputId' => 'input-image',
                            'thumbId' => 'thumb-image',
                        ])
                    </div>

                    @php
                        $additionalImageRows = old('product_image');
                        if ($additionalImageRows === null && isset($product)) {
                            $additionalImageRows = $product->images->map(fn ($img) => [
                                'image' => $img->image,
                                'sort_order' => $img->sort_order,
                            ])->all();
                        }
                        $additionalImageRows = $additionalImageRows ?: [];
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0" id="productAdditionalImages">
                            <thead>
                                <tr>
                                    <th>Дополнительные изображения</th>
                                    <th class="text-right" style="width:180px;">Порядок сортировки</th>
                                    <th style="width:56px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($additionalImageRows as $idx => $row)
                                    @include('admin.products.partials.additional-image-row', ['idx' => $idx, 'row' => $row])
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2"></td>
                                    <td>
                                        <button type="button" class="oc-btn oc-btn-green oc-btn-sm" id="addProductImage" title="Добавить">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
</div>

@include('admin.partials.slug-auto-sync')
@include('admin.products.partials.form-scripts')
