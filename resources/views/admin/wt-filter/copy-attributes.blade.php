@extends('admin.layouts.layout')

@section('content')
@include('admin.partials.oc-page-header', [
    'title' => 'Копирование фильтров',
    'breadcrumbs' => [
        ['label' => 'WT Filter', 'url' => route('admin.wt-filter.copy-attributes')],
        ['label' => 'Копирование фильтров'],
    ],
])
<section class="content pt-0 wt-copy-page">
    <div class="container-fluid">
        <div class="oc-panel">
            <div class="oc-panel-heading"><i class="fas fa-copy"></i> Копирование в wt_filter</div>
            <div class="oc-panel-body">
                <p class="text-muted mb-4">
                    Отметьте источники и нажмите <strong>Применить</strong> (первый раз) или <strong>Обновить</strong>
                    (подтянуть новое без очистки, типы/картинки сохраняются).
                </p>

                <div id="copy-alerts"></div>

                <form id="form-copy-filters">
                    @csrf

                    <div class="form-group row">
                        <label for="input-copy-type" class="col-sm-3 col-form-label">Тип фильтра</label>
                        <div class="col-sm-6">
                            <select name="copy_type" id="input-copy-type" class="form-control">
                                @foreach($types as $type)
                                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="input-attribute-separator" class="col-sm-3 col-form-label">Разделитель атрибутов</label>
                        <div class="col-sm-6">
                            <input type="text" name="attribute_separator" id="input-attribute-separator" value="," class="form-control" maxlength="16" placeholder=",">
                            <small class="form-text text-muted">Разбить текст атрибута на несколько значений. По умолчанию: запятая (<code>,</code>). Пустое — не разбивать.</small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Очистить существующие данные</label>
                        <div class="col-sm-6">
                            <div class="form-check mt-2">
                                <input type="checkbox" name="copy_truncate" value="1" id="input-copy-truncate" class="form-check-input">
                                <label for="input-copy-truncate" class="form-check-label">Да (TRUNCATE всех wt_filter_*)</label>
                            </div>
                            <small class="form-text text-muted">Только для полной пересборки с нуля. Для «Обновить» не используется.</small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Копировать атрибуты</label>
                        <div class="col-sm-6">
                            <div class="form-check mt-2">
                                <input type="checkbox" name="copy_attribute" value="1" id="input-copy-attribute" class="form-check-input" checked>
                                <label for="input-copy-attribute" class="form-check-label">Да</label>
                            </div>
                            <small class="form-text text-muted">attributes → wt_filter (option_id = attribute_id + 30000)</small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Копировать опции товаров</label>
                        <div class="col-sm-6">
                            <div class="form-check mt-2">
                                <input type="checkbox" name="copy_option" value="1" id="input-copy-option" class="form-check-input" checked>
                                <label for="input-copy-option" class="form-check-label">Да</label>
                            </div>
                            <small class="form-text text-muted">options / option_values → wt_filter (option_id без смещения)</small>
                        </div>
                    </div>

                    <input type="hidden" name="copy_store[]" value="0">

                    <div class="form-group row" id="copy-progress-wrap" hidden>
                        <div class="col-sm-6 offset-sm-3">
                            <div class="progress mb-2" style="height: 24px;">
                                <div id="copy-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%">0%</div>
                            </div>
                            <p id="copy-progress-text" class="text-muted mb-0">Загрузка...</p>
                        </div>
                    </div>

                    <div class="form-group row mb-0">
                        <div class="col-sm-9 offset-sm-3">
                            <button type="button" id="button-copy-filter" class="btn btn-primary btn-lg mr-2">
                                <i class="fas fa-check"></i> Применить
                            </button>
                            <button type="button" id="button-update-filter" class="btn btn-success btn-lg">
                                <i class="fas fa-sync-alt"></i> Обновить
                            </button>
                            <p class="help-block text-muted mt-2 mb-0">
                                <small>«Применить» — по отмеченным источникам (можно с очисткой). «Обновить» — оба источника без очистки.</small>
                            </p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@push('admin_scripts')
<script>
/**
 * truncate? → option? → attribute? → finalize
 * mode=copy  — «Применить» по галочкам
 * mode=update — «Обновить»: оба источника, без truncate
 */
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var runUrl = @json(route('admin.wt-filter.copy-attributes.run'));
    var form = document.getElementById('form-copy-filters');
    var wrap = document.getElementById('copy-progress-wrap');
    var bar = document.getElementById('copy-progress-bar');
    var text = document.getElementById('copy-progress-text');
    var alerts = document.getElementById('copy-alerts');
    var btnCopy = document.getElementById('button-copy-filter');
    var btnUpdate = document.getElementById('button-update-filter');

    function showAlert(type, message) {
        alerts.insertAdjacentHTML(
            'afterbegin',
            '<div class="alert alert-' + type + ' alert-dismissible fade show">' +
            message +
            '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>'
        );
    }

    function setProgress(done, total, message) {
        var percent = Math.round((done / total) * 100);
        bar.style.width = percent + '%';
        bar.textContent = percent + '%';
        if (message) {
            text.textContent = message;
        }
    }

    function unlock() {
        btnCopy.disabled = false;
        btnUpdate.disabled = false;
    }

    function runCopy(mode) {
        var isUpdate = mode === 'update';
        alerts.innerHTML = '';

        var useOption = isUpdate || document.getElementById('input-copy-option').checked;
        var useAttribute = isUpdate || document.getElementById('input-copy-attribute').checked;
        var doTruncate = !isUpdate && document.getElementById('input-copy-truncate').checked;

        if (!useOption && !useAttribute) {
            showAlert('danger', 'Выберите хотя бы один источник: опции или атрибуты.');
            return;
        }

        var steps = [];
        if (doTruncate) {
            steps.push('truncate');
        }
        if (useOption) {
            steps.push('option');
        }
        if (useAttribute) {
            steps.push('attribute');
        }
        steps.push('finalize');

        var index = 0;
        var total = steps.length;

        btnCopy.disabled = true;
        btnUpdate.disabled = true;
        wrap.hidden = false;
        bar.classList.remove('bg-success', 'bg-danger');
        bar.classList.add('progress-bar-animated');
        bar.style.width = '0%';
        bar.textContent = '0%';
        text.textContent = 'Загрузка...';

        function runNext() {
            if (index >= total) {
                return;
            }

            var step = steps[index];
            var body = new FormData(form);
            body.set('step', step);
            body.set('mode', mode);

            if (isUpdate) {
                body.delete('copy_truncate');
                body.set('copy_option', '1');
                body.set('copy_attribute', '1');
            } else {
                if (!doTruncate) {
                    body.delete('copy_truncate');
                }
                if (!useOption) {
                    body.delete('copy_option');
                }
                if (!useAttribute) {
                    body.delete('copy_attribute');
                }
            }

            fetch(runUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body
            }).then(function (response) {
                return response.json().then(function (json) {
                    return { ok: response.ok, json: json };
                });
            }).then(function (result) {
                var json = result.json || {};

                if (!result.ok || json.error) {
                    unlock();
                    bar.classList.remove('progress-bar-animated');
                    bar.classList.add('bg-danger');
                    text.textContent = json.error || json.message || 'Ошибка';
                    showAlert('danger', json.error || json.message || 'Ошибка копирования');
                    return;
                }

                index++;
                setProgress(index, total, json.message || step);

                if (json.complete || index >= total) {
                    unlock();
                    bar.classList.remove('progress-bar-animated');
                    bar.classList.add('bg-success');
                    var doneMsg = isUpdate ? 'Обновление завершено' : (json.message || 'Готово');
                    text.textContent = doneMsg;
                    showAlert('success', doneMsg);
                    return;
                }

                runNext();
            }).catch(function (err) {
                unlock();
                bar.classList.remove('progress-bar-animated');
                bar.classList.add('bg-danger');
                text.textContent = err.message || 'Ошибка сети';
                showAlert('danger', err.message || 'Ошибка сети');
            });
        }

        runNext();
    }

    btnCopy.addEventListener('click', function () {
        runCopy('copy');
    });
    btnUpdate.addEventListener('click', function () {
        runCopy('update');
    });
})();
</script>
@endpush
