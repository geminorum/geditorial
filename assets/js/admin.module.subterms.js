(function ($, plugin) {
  var $subterm = $('select.' + plugin._base + '-assoc-post-subterms');
  if (!$subterm.length) return;

  $('select.' + plugin._base + '-assoc-post-dropdown').change(function () {
    if ($subterm.data('linked') == $(this).val()) { // eslint-disable-line eqeqeq
      $subterm.parent('.-wrap').slideDown();
    } else {
      $subterm.parent('.-wrap').slideUp();
    }
  });
}(jQuery, gEditorial));
