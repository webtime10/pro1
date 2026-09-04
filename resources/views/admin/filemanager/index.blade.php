<div id="filemanager" class="modal-dialog modal-lg">
  <div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Менеджер изображений</h5>
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    </div>
    <div class="modal-body">
      <div class="row">
        <div class="col-sm-5 mb-2">
          <a href="{{ $parentUrl }}" title="Наверх" id="button-parent" class="btn btn-default"><i class="fas fa-level-up-alt"></i></a>
          <a href="{{ $refreshUrl }}" title="Обновить" id="button-refresh" class="btn btn-default"><i class="fas fa-sync-alt"></i></a>
          <button type="button" title="Загрузить" id="button-upload" class="btn btn-primary"><i class="fas fa-upload"></i></button>
          <button type="button" title="Папка" id="button-folder" class="btn btn-default"><i class="fas fa-folder"></i></button>
          <button type="button" title="Удалить" id="button-delete" class="btn btn-danger"><i class="fas fa-trash-alt"></i></button>
        </div>
        <div class="col-sm-7 mb-2">
          <div class="input-group">
            <input type="text" name="search" value="{{ $filterName }}" placeholder="Поиск" class="form-control">
            <div class="input-group-append">
              <button type="button" title="Искать" id="button-search" class="btn btn-primary"><i class="fas fa-search"></i></button>
            </div>
          </div>
        </div>
      </div>
      <hr>
      @forelse($images->chunk(4) as $chunk)
        <div class="row">
          @foreach($chunk as $image)
            <div class="col-sm-3 col-6 text-center mb-3">
              @if($image['type'] === 'directory')
                <div>
                  <a href="{{ route('admin.filemanager.index', array_filter([
                      'directory' => $image['directory'] ?: null,
                      'target' => $target ?: null,
                      'thumb' => $thumb ?: null,
                  ]), false) }}" class="directory oc-fm-folder"><i class="fas fa-folder fa-4x"></i></a>
                </div>
                <label class="oc-fm-label">
                  <input type="checkbox" name="path[]" value="{{ $image['path'] }}">
                  {{ $image['name'] }}
                </label>
              @else
                <a href="{{ $image['href'] }}" class="thumbnail oc-fm-thumb">
                  <img src="{{ $image['thumb'] }}" alt="{{ $image['name'] }}" title="{{ $image['name'] }}">
                </a>
                <label class="oc-fm-label">
                  <input type="checkbox" name="path[]" value="{{ $image['path'] }}">
                  {{ $image['name'] }}
                </label>
              @endif
            </div>
          @endforeach
        </div>
      @empty
        <p class="text-center text-muted mb-0">Нет изображений</p>
      @endforelse
    </div>
    <div class="modal-footer justify-content-center">
      {{ $images->links('pagination::bootstrap-4') }}
    </div>
  </div>
</div>
<script>
(function ($) {
  var target = @json($target);
  var thumb = @json($thumb);
  var directory = @json($directory);
  var uploadUrl = @json($uploadUrl);
  var folderUrl = @json($folderUrl);
  var deleteUrl = @json($deleteUrl);

  if (target) {
    $('#filemanager a.oc-fm-thumb').on('click', function (e) {
      e.preventDefault();
      if (thumb) {
        $('#' + thumb).find('img').attr('src', $(this).find('img').attr('src'));
      }
      $('#' + target).val($(this).parent().find('input').val());
      $('#modal-image').modal('hide');
    });
  }

  $('#filemanager a.directory, #filemanager .pagination a, #button-parent, #button-refresh').on('click', function (e) {
    e.preventDefault();
    var href = $(this).attr('href');
    if (!href || href === '#') return;
    $('#modal-image').load(href);
  });

  $('#filemanager input[name="search"]').on('keydown', function (e) {
    if (e.which === 13) {
      e.preventDefault();
      $('#button-search').trigger('click');
    }
  });

  $('#button-search').on('click', function () {
    var url = @json(route('admin.filemanager.index', [], false)) + '?directory=' + encodeURIComponent(directory);
    var filterName = $('#filemanager input[name="search"]').val();
    if (filterName) url += '&filter_name=' + encodeURIComponent(filterName);
    if (thumb) url += '&thumb=' + encodeURIComponent(thumb);
    if (target) url += '&target=' + encodeURIComponent(target);
    $('#modal-image').load(url);
  });

  $('#button-upload').on('click', function () {
    $('#form-upload').remove();
    $('body').prepend('<form enctype="multipart/form-data" id="form-upload" style="display:none;"><input type="file" name="file[]" multiple accept="image/*"></form>');
    $('#form-upload input[name="file[]"]').trigger('click');

    if (typeof window._ocFmTimer !== 'undefined') {
      clearInterval(window._ocFmTimer);
    }

    window._ocFmTimer = setInterval(function () {
      if ($('#form-upload input[name="file[]"]').val() === '') {
        return;
      }
      clearInterval(window._ocFmTimer);

      $.ajax({
        url: uploadUrl,
        type: 'post',
        dataType: 'json',
        data: new FormData($('#form-upload')[0]),
        cache: false,
        contentType: false,
        processData: false,
        beforeSend: function () {
          $('#button-upload').prop('disabled', true).find('i').attr('class', 'fas fa-spinner fa-spin');
        },
        complete: function () {
          $('#button-upload').prop('disabled', false).find('i').attr('class', 'fas fa-upload');
        },
        success: function (json) {
          if (json.error) alert(json.error);
          if (json.success) $('#button-refresh').trigger('click');
        },
        error: function (xhr) {
          var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Ошибка загрузки';
          alert(msg);
        }
      });
    }, 400);
  });

  $('#button-folder').popover({
    html: true,
    sanitize: false,
    placement: 'bottom',
    trigger: 'click',
    title: 'Имя папки',
    content: function () {
      return '<div class="input-group input-group-sm">' +
        '<input type="text" name="folder" value="" placeholder="Папка" class="form-control">' +
        '<div class="input-group-append"><button type="button" id="button-create" class="btn btn-primary"><i class="fas fa-plus"></i></button></div>' +
        '</div>';
    }
  });

  $('#button-folder').on('shown.bs.popover', function () {
    $('#button-create').off('click').on('click', function () {
      $.ajax({
        url: folderUrl,
        type: 'post',
        dataType: 'json',
        data: { folder: $('input[name="folder"]').val() },
        beforeSend: function () { $('#button-create').prop('disabled', true); },
        complete: function () { $('#button-create').prop('disabled', false); },
        success: function (json) {
          if (json.error) alert(json.error);
          if (json.success) $('#button-refresh').trigger('click');
        },
        error: function (xhr) {
          var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Ошибка';
          alert(msg);
        }
      });
    });
  });

  $('#button-delete').on('click', function () {
    if (!confirm('Удалить выбранные файлы и папки?')) return;

    $.ajax({
      url: deleteUrl,
      type: 'post',
      dataType: 'json',
      data: $('#filemanager input[name="path[]"]:checked'),
      beforeSend: function () { $('#button-delete').prop('disabled', true); },
      complete: function () { $('#button-delete').prop('disabled', false); },
      success: function (json) {
        if (json.error) alert(json.error);
        if (json.success) $('#button-refresh').trigger('click');
      },
      error: function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Ошибка удаления';
        alert(msg);
      }
    });
  });
})(jQuery);
</script>
