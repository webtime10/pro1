(function () {
    'use strict';

    var root = document.querySelector('[data-cart-widget]');
    if (!root) {
        return;
    }

    var infoUrl = root.getAttribute('data-info-url');
    var addUrl = root.getAttribute('data-add-url');
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var token = csrf ? csrf.getAttribute('content') : '';

    var countEl = root.querySelector('[data-cart-count]');
    var totalEl = root.querySelector('[data-cart-total]');
    var dropdown = root.querySelector('[data-cart-dropdown]');
    var listEl = root.querySelector('[data-cart-list]');
    var totalsEl = root.querySelector('[data-cart-totals]');
    var emptyEl = root.querySelector('[data-cart-empty]');

    function money(text) {
        return text || '0.00 ₽';
    }

    function render(data) {
        if (countEl) {
            countEl.textContent = String(data.count || 0);
            countEl.hidden = !(data.count > 0);
        }
        if (totalEl) {
            totalEl.textContent = money(data.total);
        }

        if (!listEl || !totalsEl || !emptyEl) {
            return;
        }

        listEl.innerHTML = '';
        totalsEl.innerHTML = '';

        var products = data.products || [];
        if (!products.length) {
            emptyEl.hidden = false;
            listEl.hidden = true;
            totalsEl.hidden = true;
            return;
        }

        emptyEl.hidden = true;
        listEl.hidden = false;
        totalsEl.hidden = false;

        products.forEach(function (p) {
            var row = document.createElement('div');
            row.className = 'cart-dd-item';

            var left = document.createElement('div');
            left.className = 'cart-dd-main';
            if (p.image) {
                var img = document.createElement('img');
                img.src = p.image;
                img.alt = '';
                img.width = 44;
                img.height = 44;
                left.appendChild(img);
            }
            var meta = document.createElement('div');
            var title = document.createElement(p.url ? 'a' : 'div');
            if (p.url) {
                title.href = p.url;
            }
            title.textContent = p.name;
            meta.appendChild(title);
            var qty = document.createElement('div');
            qty.className = 'muted';
            qty.textContent = '× ' + p.quantity + ' · ' + p.total;
            meta.appendChild(qty);
            if (p.option && p.option.length) {
                p.option.forEach(function (o) {
                    var opt = document.createElement('div');
                    opt.className = 'muted';
                    opt.style.fontSize = '.78rem';
                    opt.textContent = o.name + ': ' + o.value;
                    meta.appendChild(opt);
                });
            }
            left.appendChild(meta);
            row.appendChild(left);

            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'cart-dd-remove';
            removeBtn.setAttribute('data-remove-id', String(p.cart_id));
            removeBtn.setAttribute('aria-label', 'Удалить');
            removeBtn.textContent = '✕';
            row.appendChild(removeBtn);

            listEl.appendChild(row);
        });

        (data.totals || []).forEach(function (t) {
            var line = document.createElement('div');
            line.className = 'cart-dd-total-line';
            line.innerHTML = '<span>' + t.title + '</span><strong>' + t.text + '</strong>';
            totalsEl.appendChild(line);
        });
    }

    function refresh() {
        return fetch(infoUrl, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (r) { return r.json(); })
            .then(render)
            .catch(function () {});
    }

    function collectOptions(form) {
        var option = {};
        var elements = form.querySelectorAll('[name^="option["]');
        elements.forEach(function (el) {
            var match = el.name.match(/^option\[(\d+)\](\[\])?$/);
            if (!match) {
                return;
            }
            var id = match[1];
            if (el.type === 'checkbox') {
                if (!el.checked) {
                    return;
                }
                if (!option[id]) {
                    option[id] = [];
                }
                option[id].push(el.value);
            } else if (el.type === 'radio') {
                if (el.checked) {
                    option[id] = el.value;
                }
            } else if (el.tagName === 'SELECT' || el.type === 'text' || el.tagName === 'TEXTAREA' || el.type === 'number') {
                if (el.value !== '') {
                    option[id] = el.value;
                }
            }
        });
        return option;
    }

    document.addEventListener('click', function (e) {
        var removeBtn = e.target.closest('[data-remove-id]');
        if (removeBtn && root.contains(removeBtn)) {
            e.preventDefault();
            var id = removeBtn.getAttribute('data-remove-id');
            fetch(root.getAttribute('data-remove-url-template').replace('__ID__', id), {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token,
                },
                credentials: 'same-origin',
            }).then(function () { return refresh(); });
            return;
        }

        var toggle = e.target.closest('[data-cart-toggle]');
        if (toggle && root.contains(toggle)) {
            e.preventDefault();
            root.classList.toggle('is-open');
            if (root.classList.contains('is-open')) {
                refresh();
            }
            return;
        }

        if (!root.contains(e.target)) {
            root.classList.remove('is-open');
        }
    });

    document.addEventListener('submit', function (e) {
        var form = e.target.closest('[data-add-to-cart]');
        if (!form) {
            return;
        }
        e.preventDefault();

        var productId = form.getAttribute('data-product-id');
        var qtyInput = form.querySelector('[name="quantity"]');
        var quantity = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;
        var body = {
            product_id: parseInt(productId, 10),
            quantity: quantity,
            option: collectOptions(form),
        };

        var btn = form.querySelector('.btn-cart');
        if (btn) {
            btn.disabled = true;
        }

        fetch(addUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        })
            .then(function (r) {
                return r.json().then(function (data) {
                    if (!r.ok) {
                        var msg = data.message || 'Не удалось добавить товар';
                        if (data.errors) {
                            msg = Object.values(data.errors).flat().join('\n');
                        }
                        throw new Error(msg);
                    }
                    return data;
                });
            })
            .then(function (data) {
                if (countEl) {
                    countEl.textContent = String(data.count || 0);
                    countEl.hidden = !(data.count > 0);
                }
                if (totalEl) {
                    totalEl.textContent = money(data.total);
                }
                root.classList.add('is-open');
                refresh();
                var toast = document.getElementById('cart-toast');
                if (toast) {
                    toast.textContent = data.success || 'Товар добавлен в корзину';
                    toast.classList.add('is-show');
                    setTimeout(function () { toast.classList.remove('is-show'); }, 2200);
                }
            })
            .catch(function (err) {
                alert(err.message || 'Ошибка добавления в корзину');
            })
            .finally(function () {
                if (btn) {
                    btn.disabled = false;
                }
            });
    });

    refresh();
})();
