/* Admin product search suggestions (navbar + products filter) */
(function () {
    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function bindSuggest(input, opts) {
        if (!input) return;

        var box = document.createElement('div');
        box.className = 'admin-search-suggest';
        box.setAttribute('role', 'listbox');
        document.body.appendChild(box);

        var suggestUrl = opts.url || '/admin/products/suggest';
        var timer = null;
        var seq = 0;
        var active = -1;

        function hide() {
            box.classList.remove('is-open');
            box.innerHTML = '';
            box.style.display = 'none';
            active = -1;
        }

        function place() {
            var r = input.getBoundingClientRect();
            box.style.position = 'fixed';
            box.style.left = Math.round(r.left) + 'px';
            box.style.top = Math.round(r.bottom + 4) + 'px';
            box.style.width = Math.max(Math.round(r.width), 280) + 'px';
            box.style.zIndex = '99999';
        }

        function show(html) {
            place();
            box.innerHTML = html;
            box.style.display = 'block';
            box.classList.add('is-open');
            active = -1;
        }

        function links() {
            return Array.prototype.slice.call(box.querySelectorAll('a[data-sg]'));
        }

        function setActive(i) {
            var items = links();
            items.forEach(function (el, idx) {
                el.classList.toggle('is-active', idx === i);
            });
            active = i;
        }

        function fetchSuggest(q) {
            var my = ++seq;
            fetch(suggestUrl + '?q=' + encodeURIComponent(q), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(function (data) {
                    if (my !== seq) return;
                    var items = data.items || [];
                    if (!items.length) {
                        show('<div class="sg-empty">Ничего не найдено</div>');
                        return;
                    }
                    show(
                        items
                            .map(function (item) {
                                var meta = (item.sku ? escapeHtml(item.sku) + ' · ' : '') + escapeHtml(item.price);
                                return (
                                    '<a href="' +
                                    escapeHtml(item.url) +
                                    '" data-sg role="option">' +
                                    '<span class="sg-name">' +
                                    escapeHtml(item.name) +
                                    '</span>' +
                                    '<span class="sg-meta">' +
                                    meta +
                                    '</span>' +
                                    '</a>'
                                );
                            })
                            .join('')
                    );
                })
                .catch(function () {
                    if (my === seq) {
                        show('<div class="sg-empty">Ошибка поиска</div>');
                    }
                });
        }

        function onType() {
            var q = (input.value || '').trim();
            clearTimeout(timer);
            if (q.length < 2) {
                hide();
                return;
            }
            timer = setTimeout(function () {
                fetchSuggest(q);
            }, 150);
        }

        input.addEventListener('input', onType);
        input.addEventListener('keyup', onType);
        input.addEventListener('compositionend', onType);
        window.addEventListener('resize', function () {
            if (box.classList.contains('is-open')) place();
        });
        window.addEventListener(
            'scroll',
            function () {
                if (box.classList.contains('is-open')) place();
            },
            true
        );

        input.addEventListener('keydown', function (e) {
            if (!box.classList.contains('is-open')) return;
            var items = links();
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (items.length) setActive(Math.min(active + 1, items.length - 1));
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (items.length) setActive(Math.max(active - 1, 0));
            } else if (e.key === 'Enter' && active >= 0 && items[active]) {
                e.preventDefault();
                items[active].click();
            } else if (e.key === 'Escape') {
                hide();
            }
        });

        document.addEventListener('click', function (e) {
            if (e.target === input || box.contains(e.target)) return;
            hide();
        });
    }

    ready(function () {
        bindSuggest(document.getElementById('admin-search-q'), {});
        bindSuggest(document.getElementById('admin-products-filter-q'), {});
    });
})();
