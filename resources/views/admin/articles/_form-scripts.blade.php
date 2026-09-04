<script>
(function ($) {
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
