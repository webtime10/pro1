<script>
(function ($) {
    var translateUrl = @json(route('admin.articles.translate'));
    var sourceCode = @json(strtolower((string) (optional($defaultLanguage)->code ?? 'ru')));
    var articleId = @json(isset($article) && $article ? (int) $article->id : null);
    var targetCodes = @json(
        $languages->filter(fn ($l) => strtolower((string) $l->code) !== strtolower((string) (optional($defaultLanguage)->code ?? 'ru')))
            ->pluck('code')
            ->map(fn ($c) => strtolower((string) $c))
            ->values()
            ->all()
    );

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    function $field(code, name) {
        var id = name + '_' + code;
        var el = document.getElementById(id);
        if (el) {
            return $(el);
        }
        return $('[name="' + id + '"]').first();
    }

    function syncWysiwyg($el) {
        if (!$el.length || !$el.hasClass('js-wysiwyg')) {
            return;
        }
        // Если открыт codeview — выходим, иначе summernote('code') врёт
        if ($el.next('.note-editor').find('.note-codable').is(':visible')) {
            try { $el.summernote('codeview.deactivate'); } catch (e) {}
        }
        if (!$el.next('.note-editor').length && window.ocInitWysiwyg) {
            window.ocInitWysiwyg($el.parent());
        }
        if ($el.next('.note-editor').length) {
            try {
                var code = $el.summernote('code');
                if (typeof code === 'string') {
                    $el.val(code);
                }
            } catch (e) {}
        }
    }

    function fieldVal(code, name) {
        var $el = $field(code, name);
        if (!$el.length) {
            return '';
        }
        if ($el.hasClass('js-wysiwyg')) {
            syncWysiwyg($el);
            if ($el.next('.note-editor').length) {
                try {
                    var html = $el.summernote('code');
                    if (html && html.replace(/<[^>]+>/g, '').trim() !== '') {
                        return html;
                    }
                } catch (e) {}
            }
            return $el.val() || '';
        }
        return $el.val() || '';
    }

    function destroyWysiwyg($el) {
        if (!$el.length || !$el.hasClass('js-wysiwyg')) {
            return;
        }
        if ($el.next('.note-editor').length || $el.data('summernote')) {
            try {
                if ($el.next('.note-editor').find('.note-codable').is(':visible')) {
                    $el.summernote('codeview.deactivate');
                }
                $el.summernote('destroy');
            } catch (e) {}
        }
    }

    function setField(code, name, value) {
        var $el = $field(code, name);
        if (!$el.length) {
            console.warn('[translate] field not found', name + '_' + code);
            return false;
        }
        value = value == null ? '' : String(value);

        if ($el.hasClass('js-wysiwyg') || name === 'description') {
            destroyWysiwyg($el);
            $el.val(value);
            if (window.ocInitWysiwyg) {
                window.ocInitWysiwyg($el.parent());
            }
            if ($el.next('.note-editor').length) {
                try {
                    $el.summernote('code', value);
                } catch (e) {}
            }
            return value.replace(/<[^>]+>/g, '').trim().length > 0 || value.indexOf('<img') !== -1;
        }

        var node = $el.get(0);
        node.value = value;
        $el.attr('value', value);
        if (name === 'slug') {
            $el.attr('data-slug-locked', value ? '1' : '0');
        }
        return value.trim().length > 0;
    }

    function activateLangTab(code, afterShow) {
        var $pane = $field(code, 'name').closest('.tab-pane');
        if (!$pane.length) {
            if (typeof afterShow === 'function') afterShow();
            return;
        }
        var id = $pane.attr('id');
        var $tab = id ? $('a[data-toggle="tab"][href="#' + id + '"]') : $();
        var done = false;
        var runAfter = function () {
            if (done) return;
            done = true;
            if (typeof afterShow === 'function') {
                setTimeout(afterShow, 30);
            }
        };

        if ($pane.hasClass('active') || $pane.hasClass('show')) {
            runAfter();
            return;
        }
        if ($tab.length) {
            $tab.one('shown.bs.tab', runAfter);
            $tab.tab('show');
            setTimeout(runAfter, 500);
            return;
        }
        runAfter();
    }

    function targetsHaveContent() {
        return targetCodes.some(function (code) {
            var name = (fieldVal(code, 'name') || '').trim();
            var desc = $('<div>').html(fieldVal(code, 'description') || '').text().trim();
            return name.length > 0 || desc.length > 0;
        });
    }

    function setBusy(busy) {
        $('.js-article-translate').prop('disabled', busy);
        $('.js-article-translate span').text(busy ? 'Перевод…' : 'Перевод');
        $('.js-article-translate i').each(function () {
            var $i = $(this);
            if (busy) {
                $i.removeClass('fa-language').addClass('fa-spinner fa-spin');
            } else {
                $i.removeClass('fa-spinner fa-spin').addClass('fa-language');
            }
        });
    }

    var progressTimer = null;

    function setStatus(state, title, text) {
        var $box = $('#article-translate-status');
        if (!$box.length) return;
        $box.removeClass('d-none is-running is-done is-error');
        if (state) $box.addClass('is-' + state);
        $box.find('.article-translate-status__title').text(title || 'Перевод');
        $box.find('.article-translate-status__text').text(text || '');
        var $icon = $box.find('.article-translate-status__icon');
        $icon.removeClass('fa-language fa-spinner fa-spin fa-check-circle fa-exclamation-circle');
        if (state === 'running') $icon.addClass('fa-spinner fa-spin');
        else if (state === 'done') $icon.addClass('fa-check-circle');
        else if (state === 'error') $icon.addClass('fa-exclamation-circle');
        else $icon.addClass('fa-language');
    }

    function startProgressMessages() {
        clearInterval(progressTimer);
        var steps = [
            'Перевод начат…',
            'Отправляю текст в Gemini…',
            'Перевожу на English…',
            'Перевожу на Українська…',
            'Сохраняю перевод в базу…',
            'Ещё немного…'
        ];
        var i = 0;
        setStatus('running', 'Идёт перевод', steps[0]);
        progressTimer = setInterval(function () {
            i = Math.min(i + 1, steps.length - 1);
            setStatus('running', 'Идёт перевод', steps[i]);
        }, 3500);
    }

    function stopProgressMessages() {
        clearInterval(progressTimer);
        progressTimer = null;
    }

    function applyTranslations(json) {
        var translations = json.translations || {};
        var codes = Object.keys(translations);
        var filled = 0;

        codes.forEach(function (code) {
            var row = translations[code] || {};
            ['name', 'slug', 'description', 'meta_h1', 'meta_title', 'meta_description', 'meta_keyword', 'tag'].forEach(function (field) {
                if (Object.prototype.hasOwnProperty.call(row, field) && setField(code, field, row[field])) {
                    filled += 1;
                }
            });
        });

        if (json.source) {
            Object.keys(json.source).forEach(function (field) {
                setField(sourceCode, field, json.source[field]);
            });
        }

        var firstTarget = targetCodes[0] || codes[0] || 'en';
        activateLangTab(firstTarget, function () {
            codes.forEach(function (code) {
                var row = translations[code] || {};
                if (row.description != null) setField(code, 'description', row.description);
                if (row.name != null) setField(code, 'name', row.name);
            });
        });

        return filled;
    }

    $(document).on('click', '.js-article-translate', function () {
        if ($(this).prop('disabled')) return;

        $('textarea.js-wysiwyg').each(function () {
            syncWysiwyg($(this));
        });

        var payload = {
            source_code: sourceCode,
            article_id: articleId,
            name: fieldVal(sourceCode, 'name'),
            description: fieldVal(sourceCode, 'description'),
            meta_h1: fieldVal(sourceCode, 'meta_h1'),
            meta_title: fieldVal(sourceCode, 'meta_title'),
            meta_description: fieldVal(sourceCode, 'meta_description'),
            meta_keyword: fieldVal(sourceCode, 'meta_keyword'),
            tag: fieldVal(sourceCode, 'tag')
        };

        var plain = $('<div>').html(payload.description || '').text().trim();
        if (!(payload.name || '').trim() && !plain) {
            activateLangTab(sourceCode);
            setStatus('error', 'Нет текста', 'Откройте вкладку «Русский» и заполните название или описание.');
            return;
        }

        if (!articleId && targetsHaveContent() && !confirm('В English / Українська уже есть текст. Перезаписать переводом?')) {
            return;
        }

        setBusy(true);
        startProgressMessages();

        fetch(translateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        })
            .then(function (res) {
                return res.text().then(function (text) {
                    var json = null;
                    try {
                        json = text ? JSON.parse(text) : null;
                    } catch (e) {
                        throw new Error(res.ok
                            ? 'Сервер вернул не JSON'
                            : ('Ошибка ' + res.status + (text ? ': ' + text.replace(/<[^>]+>/g, ' ').trim().slice(0, 180) : '')));
                    }
                    if (!res.ok || !json || !json.ok) {
                        throw new Error((json && json.message) || ('Ошибка ' + res.status));
                    }
                    return json;
                });
            })
            .then(function (json) {
                stopProgressMessages();

                // Уже сохранённая статья: пишем в БД и перезагружаем страницу
                if (articleId && json.saved) {
                    setStatus('done', 'Перевод сохранён', 'Обновляю страницу — откройте вкладку English.');
                    window.location.href = window.location.pathname + '?lang=en&_=' + Date.now();
                    return;
                }

                var filled = applyTranslations(json);
                var enName = (json.translations && json.translations.en && json.translations.en.name) || '';
                if (!filled) {
                    setStatus('error', 'Перевод не записан в форму', 'Сохраните статью и повторите перевод — тогда он запишется в базу.');
                    setBusy(false);
                    return;
                }
                setStatus('done', 'Перевод завершён', 'Заполнено полей: ' + filled + (enName ? ('. EN: ' + enName) : ''));
                setBusy(false);
            })
            .catch(function (err) {
                stopProgressMessages();
                setStatus('error', 'Перевод не выполнен', err.message || String(err));
                setBusy(false);
            });
    });

    // После reload с ?lang=en открыть English
    $(function () {
        var params = new URLSearchParams(window.location.search);
        if (params.get('lang') === 'en') {
            activateLangTab('en');
        }
    });

    function bindSuggest(inputId, listId, url, name) {
        var $input = $('#' + inputId);
        var $list = $('#' + listId);
        var timer = null;
        $input.on('input', function () {
            clearTimeout(timer);
            var q = this.value.trim();
            if (q.length < 2) return;
            timer = setTimeout(function () {
                $.getJSON(url, { q: q }).done(function (data) {
                    var items = data.items || [];
                    $input.next('.js-suggest-box').remove();
                    if (!items.length) return;
                    var $box = $('<div class="js-suggest-box list-group" style="max-width:28rem;position:absolute;z-index:20;"></div>');
                    items.forEach(function (item) {
                        $('<a href="#" class="list-group-item list-group-item-action"></a>')
                            .text(item.name)
                            .on('click', function (e) {
                                e.preventDefault();
                                if ($list.find('input[value="' + item.id + '"]').length) return;
                                $list.append(
                                    '<div class="mb-1"><input type="hidden" name="' + name + '" value="' + item.id + '">' +
                                    $('<div>').text(item.name).html() +
                                    ' <button type="button" class="btn btn-link btn-sm text-danger js-remove-related">&times;</button></div>'
                                );
                                $box.remove();
                                $input.val('');
                            })
                            .appendTo($box);
                    });
                    $input.after($box);
                });
            }, 200);
        });
    }

    $(document).on('click', '.js-remove-related', function () {
        $(this).closest('div').remove();
    });

    bindSuggest('related-article-q', 'related-article-list', @json(route('admin.articles.suggest')), 'related_article_ids[]');
    bindSuggest('related-product-q', 'related-product-list', @json(route('admin.articles.suggest-products')), 'related_product_ids[]');

    var imgIdx = $('#articleAdditionalImages tbody tr').length;
    $('#addArticleImage').on('click', function () {
        imgIdx += 1;
        var row = '<tr><td><input type="text" class="form-control" name="article_image[' + imgIdx + '][image]" placeholder="путь в filemanager"></td>' +
            '<td><input type="number" class="form-control" name="article_image[' + imgIdx + '][sort_order]" value="0"></td>' +
            '<td><button type="button" class="oc-btn oc-btn-red oc-btn-sm js-remove-img">&minus;</button></td></tr>';
        $('#articleAdditionalImages tbody').append(row);
    });
    $(document).on('click', '.js-remove-img', function () {
        $(this).closest('tr').remove();
    });
})(jQuery);
</script>
