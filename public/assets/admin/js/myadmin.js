// My Admin Custom JavaScript
(function ($) {
  function initOceanSelects(root) {
    var $root = root ? $(root) : $(document);
    $root.find('select.form-control').each(function () {
      var $el = $(this);
      if ($el.data('select2') || $el.hasClass('js-no-select2')) {
        return;
      }
      // multiple / huge lists keep search; tiny selects hide search
      var optsCount = $el.find('option').length;
      $el.select2({
        theme: 'bootstrap4',
        width: '100%',
        minimumResultsForSearch: optsCount > 12 ? 0 : Infinity
      });
    });
  }

  $(function () {
    initOceanSelects();
  });

  window.ocInitAdminSelects = initOceanSelects;
})(jQuery);
