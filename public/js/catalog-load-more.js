(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-load-more]');
        if (!btn || btn.disabled) {
            return;
        }

        var grid = document.querySelector('[data-product-grid]');
        if (!grid) {
            return;
        }

        var nextPage = parseInt(btn.getAttribute('data-next-page'), 10);
        var lastPage = parseInt(btn.getAttribute('data-last-page'), 10);
        var url = btn.getAttribute('data-url');
        if (!nextPage || !url || nextPage > lastPage) {
            btn.hidden = true;
            return;
        }

        var params = new URLSearchParams(btn.getAttribute('data-query') || '');
        params.set('page', String(nextPage));
        params.set('ajax', '1');

        btn.disabled = true;
        var label = btn.textContent;
        btn.textContent = 'Загрузка…';

        fetch(url + '?' + params.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('HTTP ' + res.status);
                }
                return res.json();
            })
            .then(function (data) {
                if (data.html) {
                    grid.insertAdjacentHTML('beforeend', data.html);
                }

                if (data.next_page) {
                    btn.setAttribute('data-next-page', String(data.next_page));
                    btn.disabled = false;
                    btn.textContent = label;
                } else {
                    btn.hidden = true;
                }

                if (data.last_page) {
                    btn.setAttribute('data-last-page', String(data.last_page));
                }
            })
            .catch(function () {
                btn.disabled = false;
                btn.textContent = label;
            });
    });
})();
