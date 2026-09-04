(function ($) {
  var filemanagerUrl = (window.ocFilemanager && window.ocFilemanager.index)
    ? window.ocFilemanager.index
    : '/admin/filemanager';
  var activeThumb = null;

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  function pickerInput($thumb) {
    return $thumb.closest('.oc-image-picker').find('input[type="hidden"]').first();
  }

  function ensureModal() {
    var $modal = $('#modal-image');
    if (!$modal.length) {
      $modal = $('<div id="modal-image" class="modal fade" tabindex="-1" role="dialog"></div>');
      $('body').append($modal);
    }
    return $modal;
  }

  function openFilemanager($thumb) {
    var $input = pickerInput($thumb);
    var $modal = ensureModal();

    $('[data-toggle="image"]').popover('hide');

    $.ajax({
      url: filemanagerUrl,
      type: 'get',
      dataType: 'html',
      data: {
        target: $input.attr('id') || '',
        thumb: $thumb.attr('id') || ''
      },
      success: function (html) {
        $modal.html(html);
        $modal.modal('show');
      },
      error: function (xhr) {
        alert('Не удалось открыть менеджер изображений (' + (xhr.status || 0) + ')');
      }
    });
  }

  $(document).on('click', '[data-toggle="image"]', function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $element = $(this);
    activeThumb = $element;

    $('[data-toggle="image"]').not($element).popover('hide');

    if ($element.data('bs.popover')) {
      $element.popover('toggle');
      return;
    }

    $element.popover({
      html: true,
      sanitize: false,
      placement: 'right',
      trigger: 'manual',
      container: 'body',
      content: function () {
        return '<div class="btn-group">' +
          '<button type="button" class="btn btn-primary btn-sm js-oc-fm-edit" title="Изменить"><i class="fas fa-pencil-alt"></i></button>' +
          '<button type="button" class="btn btn-danger btn-sm js-oc-fm-clear" title="Удалить"><i class="fas fa-trash-alt"></i></button>' +
          '</div>';
      }
    });

    $element.popover('show');
  });

  $(document).on('click', '.js-oc-fm-edit', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var $thumb = activeThumb && activeThumb.length ? activeThumb : $('[data-toggle="image"][aria-describedby]');
    if (!$thumb || !$thumb.length) {
      return;
    }
    openFilemanager($thumb);
  });

  $(document).on('click', '.js-oc-fm-clear', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var $thumb = activeThumb && activeThumb.length ? activeThumb : $('[data-toggle="image"][aria-describedby]');
    if (!$thumb || !$thumb.length) {
      return;
    }
    var placeholder = $thumb.find('img').attr('data-placeholder');
    $thumb.find('img').attr('src', placeholder);
    pickerInput($thumb).val('');
    $thumb.popover('hide');
  });

  $(document).on('click', function (e) {
    var $t = $(e.target);
    if ($t.closest('[data-toggle="image"], .popover, #modal-image, .js-oc-fm-edit, .js-oc-fm-clear').length) {
      return;
    }
    $('[data-toggle="image"]').popover('hide');
  });
})(jQuery);
