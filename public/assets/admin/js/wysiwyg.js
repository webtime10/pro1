/**
 * Summernote (MIT) for admin WYSIWYG fields (.js-wysiwyg).
 * Image upload → admin.filemanager.editor-upload
 */
(function ($) {
  function csrfToken() {
    return $('meta[name="csrf-token"]').attr('content') || '';
  }

  function uploadUrl() {
    return (window.ocFilemanager && window.ocFilemanager.editorUpload)
      ? window.ocFilemanager.editorUpload
      : '/admin/filemanager/editor-upload';
  }

  function initEditor($el) {
    if (!$el.length || $el.data('summernote')) {
      return;
    }

    $el.summernote({
      height: parseInt($el.attr('data-wysiwyg-height'), 10) || 1280,
      minHeight: 320,
      placeholder: $el.attr('placeholder') || 'Текст…',
      toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline', 'clear']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['insert', ['link', 'picture']],
        ['view', ['codeview']]
      ],
      callbacks: {
        onPaste: function (e) {
          var clipboard = (e.originalEvent || e).clipboardData;
          if (!clipboard) {
            return;
          }
          var html = clipboard.getData('text/html');
          var text = clipboard.getData('text/plain');
          if (!html && !text) {
            return;
          }
          e.preventDefault();
          var cleaned = window.ocCleanPastedHtml
            ? window.ocCleanPastedHtml(html || text, !!html)
            : (html || text);
          $el.summernote('pasteHTML', cleaned);
        },
        onImageUpload: function (files) {
          var $note = $(this);
          Array.prototype.forEach.call(files, function (file) {
            var data = new FormData();
            data.append('file', file);

            fetch(uploadUrl(), {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              },
              body: data,
              credentials: 'same-origin'
            })
              .then(function (res) {
                return res.json().then(function (json) {
                  if (!res.ok) {
                    throw new Error((json && json.error) || ('HTTP ' + res.status));
                  }
                  return json;
                });
              })
              .then(function (json) {
                if (json.url) {
                  $note.summernote('insertImage', json.url, function ($image) {
                    $image.css('max-width', '100%');
                  });
                }
              })
              .catch(function (err) {
                console.error(err);
                alert('Не удалось загрузить изображение: ' + (err.message || err));
              });
          });
        }
      }
    });
  }

  /** Лёгкая очистка вставки из Gemini / Word / браузера. */
  window.ocCleanPastedHtml = function (raw, isHtml) {
    var s = String(raw || '');
    s = s.replace(/^\uFEFF/, '').replace(/[\u200B-\u200D\uFEFF\u00AD]/g, '');
    s = s.replace(/^```(?:html|markdown|md|text)?\s*/i, '').replace(/\s*```$/, '');

    if (!isHtml) {
      s = s
        .replace(/^#{1,4}\s+(.+)$/gm, '<h3>$1</h3>')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/__(.+?)__/g, '<strong>$1</strong>')
        .replace(/(^|[^*])\*([^*\n]+)\*(?!\*)/g, '$1<em>$2</em>')
        .replace(/^[-*+]\s+(.+)$/gm, '<li>$1</li>')
        .replace(/(?:^|\n)((?:<li>.*<\/li>\n?)+)/g, '<ul>$1</ul>')
        .replace(/\n{2,}/g, '</p><p>')
        .replace(/\n/g, '<br>');
      if (s.indexOf('<') === -1) {
        s = '<p>' + s + '</p>';
      } else if (s.indexOf('<p>') === -1 && s.indexOf('<h') === -1 && s.indexOf('<ul>') === -1) {
        s = '<p>' + s + '</p>';
      }
    }

    var tmp = document.createElement('div');
    tmp.innerHTML = s;
    tmp.querySelectorAll('script,style').forEach(function (n) { n.remove(); });
    tmp.querySelectorAll('*').forEach(function (el) {
      Array.prototype.slice.call(el.attributes).forEach(function (attr) {
        var name = attr.name.toLowerCase();
        if (name === 'href' && el.tagName === 'A') {
          return;
        }
        if (name === 'src' && el.tagName === 'IMG') {
          return;
        }
        el.removeAttribute(attr.name);
      });
      var tag = el.tagName.toLowerCase();
      var allow = { p:1, br:1, strong:1, b:1, em:1, i:1, u:1, ul:1, ol:1, li:1, h2:1, h3:1, h4:1, a:1, blockquote:1 };
      if (!allow[tag]) {
        var parent = el.parentNode;
        while (el.firstChild) {
          parent.insertBefore(el.firstChild, el);
        }
        parent.removeChild(el);
      }
    });

    return tmp.innerHTML
      .replace(/&nbsp;/gi, ' ')
      .replace(/[“”„«»]/g, '"')
      .replace(/[‘’]/g, "'")
      .replace(/[–—]/g, '-')
      .replace(/(<p>\s*<br\s*\/?>\s*<\/p>\s*){2,}/gi, '<p><br></p>');
  };

  function initAll(root) {
    $(root || document).find('textarea.js-wysiwyg').each(function () {
      initEditor($(this));
    });
  }

  $(function () {
    initAll();

    // Language tabs: re-init when pane becomes visible (hidden editors can break size)
    $(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function () {
      var href = $(this).attr('href');
      if (href && href.charAt(0) === '#') {
        initAll($(href));
        $(href).find('textarea.js-wysiwyg').each(function () {
          var $el = $(this);
          if (!$el.next('.note-editor').length) {
            return;
          }
          // textarea — источник истины (перевод пишет сюда); не затирать пустым summernote
          var fromTextarea = $el.val() || '';
          try {
            $el.summernote('code', fromTextarea);
          } catch (e) {}
        });
      }
    });
  });

  window.ocInitWysiwyg = initAll;
  window.ocSetWysiwygHtml = function (el, html) {
    var $el = $(el);
    if (!$el.length) {
      return;
    }
    html = html == null ? '' : String(html);
    $el.val(html);
    if (!$el.hasClass('js-wysiwyg')) {
      return;
    }
    if (!$el.next('.note-editor').length) {
      initEditor($el);
    }
    if ($el.next('.note-editor').length) {
      try {
        $el.summernote('code', html);
      } catch (e) {
        $el.val(html);
      }
    }
  };
})(jQuery);
