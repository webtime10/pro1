@php
    $article = $article ?? null;
    $isEdit = $article !== null;
    $formAction = $isEdit ? route('admin.articles.update', $article->id) : route('admin.articles.store');
    $selectedCats = old('blog_category_ids', $isEdit ? $article->blogCategories->pluck('id')->all() : []);
    $mainCat = old('main_blog_category_id', $isEdit ? optional($article->blogCategories->firstWhere('pivot.main', true))->id : null);
    $relatedArticles = old('related_article_ids', $isEdit ? $article->relatedArticles->pluck('id')->all() : []);
    $relatedProducts = old('related_product_ids', $isEdit ? $article->products->pluck('id')->all() : []);
    $relatedArticleMap = $isEdit ? $article->relatedArticles->keyBy('id') : collect();
    $relatedProductMap = $isEdit ? $article->products->keyBy('id') : collect();
@endphp

<div class="card card-outline card-outline-tabs mb-0 border-0">
    <div class="card-header p-0 border-bottom-0 bg-white">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-general">Общие</a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-data">Данные</a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-links">Связи</a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-image">Изображение</a></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-general">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                    <p class="text-muted small mb-0 mr-2">
                        Вставьте текст на вкладке <strong>Русский</strong> → «Перевод»: EN/UK + SEO (meta title / description) через Gemini.
                    </p>
                    <button type="button" class="oc-btn oc-btn-primary oc-btn-wide js-article-translate" title="Перевести с русского на EN и UK">
                        <i class="fas fa-language"></i>
                        <span class="ml-1">Перевод</span>
                    </button>
                </div>
                <div id="article-translate-status" class="article-translate-status d-none" aria-live="polite">
                    <div class="article-translate-status__row">
                        <i class="fas fa-language article-translate-status__icon"></i>
                        <div class="article-translate-status__body">
                            <div class="article-translate-status__title">Перевод</div>
                            <div class="article-translate-status__text">—</div>
                            <div class="article-translate-status__bar"><span></span></div>
                        </div>
                    </div>
                </div>
                <ul class="nav nav-tabs mb-2" role="tablist">
                    @foreach($languages as $i => $language)
                        <li class="nav-item">
                            <a class="nav-link {{ $i === 0 ? 'active' : '' }}" data-toggle="tab" href="#alang{{ $language->id }}">{{ $language->name }}</a>
                        </li>
                    @endforeach
                </ul>
                <div class="tab-content border">
                    @foreach($languages as $i => $language)
                        @php
                            $c = $language->code;
                            $desc = $isEdit ? $article->descriptions->firstWhere('language_id', $language->id) : null;
                        @endphp
                        <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}" id="alang{{ $language->id }}">
                            <div class="form-group">
                                <label>Название <span class="text-danger">*</span></label>
                                <input type="text" name="name_{{ $c }}" id="name_{{ $c }}" class="form-control"
                                       value="{{ old('name_'.$c, $desc->name ?? '') }}">
                            </div>
                            <div class="form-group">
                                <label>Slug <span class="text-danger">*</span></label>
                                <input type="text" name="slug_{{ $c }}" id="slug_{{ $c }}" class="form-control"
                                       value="{{ old('slug_'.$c, $desc->slug ?? '') }}"
                                       data-slug-locked="{{ ($desc && $desc->slug) ? '1' : '0' }}" autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label>H1</label>
                                <input type="text" name="meta_h1_{{ $c }}" class="form-control" value="{{ old('meta_h1_'.$c, $desc->meta_h1 ?? '') }}">
                            </div>
                            <div class="form-group">
                                <label>Описание</label>
                                <textarea name="description_{{ $c }}" id="description_{{ $c }}"
                                          class="form-control js-wysiwyg" rows="12"
                                          data-wysiwyg-height="1280"
                                          placeholder="Текст статьи…">{{ old('description_'.$c, $desc->description ?? '') }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>Теги</label>
                                <input type="text" name="tag_{{ $c }}" class="form-control" value="{{ old('tag_'.$c, $desc->tag ?? '') }}">
                            </div>
                            <div class="form-group">
                                <label>Meta title</label>
                                <input type="text" name="meta_title_{{ $c }}" class="form-control" value="{{ old('meta_title_'.$c, $desc->meta_title ?? '') }}">
                            </div>
                            <div class="form-group">
                                <label>Meta description</label>
                                <input type="text" name="meta_description_{{ $c }}" class="form-control" value="{{ old('meta_description_'.$c, $desc->meta_description ?? '') }}">
                            </div>
                            <div class="form-group mb-0">
                                <label>Meta keyword</label>
                                <input type="text" name="meta_keyword_{{ $c }}" class="form-control" value="{{ old('meta_keyword_'.$c, $desc->meta_keyword ?? '') }}">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="tab-pane fade" id="tab-data">
                <div class="form-group">
                    <label>Дата публикации</label>
                    <input type="date" name="date_available" class="form-control" style="max-width:16rem;"
                           value="{{ old('date_available', optional($article->date_available ?? now())->format('Y-m-d')) }}">
                </div>
                <div class="form-group">
                    <label>Порядок</label>
                    <input type="number" name="sort_order" class="form-control" min="0" style="max-width:12rem;"
                           value="{{ old('sort_order', $article->sort_order ?? 0) }}">
                </div>
                <div class="form-group">
                    <input type="hidden" name="status" value="0">
                    <label class="mr-3"><input type="checkbox" name="status" value="1" {{ old('status', $article->status ?? true) ? 'checked' : '' }}> Опубликована</label>
                    <input type="hidden" name="reviews_enabled" value="0">
                    <label><input type="checkbox" name="reviews_enabled" value="1" {{ old('reviews_enabled', $article->reviews_enabled ?? true) ? 'checked' : '' }}> Отзывы</label>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-links">
                <h5>Категории блога</h5>
                <p class="text-muted small">Галочка — статья в категории. Радио — основная (для ЧПУ, как main_blog_category).</p>
                @forelse($blogCategories as $cat)
                    @php $cd = $defaultLanguage ? $cat->descriptions->firstWhere('language_id', $defaultLanguage->id) : null; @endphp
                    <div class="form-group mb-1">
                        <label class="mr-3">
                            <input type="checkbox" name="blog_category_ids[]" value="{{ $cat->id }}"
                                   {{ in_array($cat->id, array_map('intval', $selectedCats), true) ? 'checked' : '' }}>
                            {{ $cd->name ?? ('#'.$cat->id) }}
                        </label>
                        <label class="text-muted">
                            <input type="radio" name="main_blog_category_id" value="{{ $cat->id }}"
                                   {{ (string) $mainCat === (string) $cat->id ? 'checked' : '' }}> основная
                        </label>
                    </div>
                @empty
                    <p class="text-muted">Сначала создайте категорию блога.</p>
                @endforelse

                <hr>
                <h5>Похожие статьи</h5>
                <input type="text" id="related-article-q" class="form-control mb-2" placeholder="Поиск статьи (от 2 букв)…" style="max-width:28rem;">
                <div id="related-article-list">
                    @foreach($relatedArticles as $rid)
                        @php
                            $rel = $relatedArticleMap->get($rid);
                            $rd = $rel && $defaultLanguage ? $rel->descriptions->firstWhere('language_id', $defaultLanguage->id) : null;
                        @endphp
                        <div class="mb-1">
                            <input type="hidden" name="related_article_ids[]" value="{{ $rid }}">
                            {{ $rd->name ?? ('#'.$rid) }}
                            <button type="button" class="btn btn-link btn-sm text-danger js-remove-related">&times;</button>
                        </div>
                    @endforeach
                </div>

                <hr>
                <h5>Товары на странице статьи</h5>
                <input type="text" id="related-product-q" class="form-control mb-2" placeholder="Поиск товара (от 2 букв)…" style="max-width:28rem;">
                <div id="related-product-list">
                    @foreach($relatedProducts as $pid)
                        @php
                            $prod = $relatedProductMap->get($pid);
                            $pd = $prod && $defaultLanguage ? $prod->descriptions->firstWhere('language_id', $defaultLanguage->id) : null;
                        @endphp
                        <div class="mb-1">
                            <input type="hidden" name="related_product_ids[]" value="{{ $pid }}">
                            {{ $pd->name ?? ('#'.$pid) }}
                            <button type="button" class="btn btn-link btn-sm text-danger js-remove-related">&times;</button>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="tab-pane fade" id="tab-image">
                <div class="form-group">
                    <label>Главное изображение</label>
                    @include('admin.partials.image-picker', [
                        'name' => 'image',
                        'value' => old('image', $article->image ?? ''),
                        'inputId' => 'input-article-image',
                        'thumbId' => 'thumb-article-image',
                    ])
                </div>
                @php
                    $additionalImageRows = old('article_image');
                    if ($additionalImageRows === null && $isEdit) {
                        $additionalImageRows = $article->images->map(fn ($img) => [
                            'image' => $img->image,
                            'sort_order' => $img->sort_order,
                        ])->all();
                    }
                    $additionalImageRows = $additionalImageRows ?: [];
                @endphp
                <table class="table table-bordered" id="articleAdditionalImages">
                    <thead><tr><th>Дополнительно</th><th style="width:160px;">Порядок</th><th style="width:56px;"></th></tr></thead>
                    <tbody>
                        @foreach($additionalImageRows as $idx => $row)
                            <tr>
                                <td>
                                    @include('admin.partials.image-picker', [
                                        'name' => 'article_image['.$idx.'][image]',
                                        'value' => $row['image'] ?? '',
                                        'inputId' => 'article-add-image-'.$idx,
                                        'thumbId' => 'article-add-thumb-'.$idx,
                                        'size' => 'md',
                                    ])
                                </td>
                                <td><input type="number" name="article_image[{{ $idx }}][sort_order]" value="{{ $row['sort_order'] ?? 0 }}" class="form-control"></td>
                                <td><button type="button" class="oc-btn oc-btn-red oc-btn-sm js-remove-img">&minus;</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <button type="button" class="oc-btn oc-btn-green oc-btn-sm" id="addArticleImage">+ изображение</button>
            </div>
        </div>
    </div>
</div>
