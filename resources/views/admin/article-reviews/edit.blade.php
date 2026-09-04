@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Отзыв',
    'breadcrumbs' => [
        ['label' => 'Отзывы', 'url' => route('admin.article-reviews.index')],
        ['label' => 'Редактирование'],
    ],
])
<section class="content pt-0">
    @include('admin.partials.oc-form-toolbar', [
        'formId' => 'reviewForm',
        'backUrl' => route('admin.article-reviews.index'),
        'destroyUrl' => route('admin.article-reviews.destroy', $review->id),
    ])
    <div class="container-fluid">
        <div class="oc-panel oc-form-panel">
            <div class="oc-panel-body">
                <form id="reviewForm" action="{{ route('admin.article-reviews.update', $review->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <p class="text-muted">
                        Статья:
                        @php $d = $defaultLanguage ? optional($review->article)->descriptions->firstWhere('language_id', $defaultLanguage->id) : null; @endphp
                        {{ $d->name ?? '—' }}
                    </p>
                    <div class="form-group">
                        <label>Автор</label>
                        <input type="text" name="author" class="form-control" value="{{ old('author', $review->author) }}">
                    </div>
                    <div class="form-group">
                        <label>Текст</label>
                        <textarea name="text" class="form-control" rows="6">{{ old('text', $review->text) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Оценка</label>
                        <select name="rating" class="form-control" style="max-width:8rem;">
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" {{ (int) old('rating', $review->rating) === $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="status" value="0">
                        <label><input type="checkbox" name="status" value="1" {{ old('status', $review->status) ? 'checked' : '' }}> Одобрен</label>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
