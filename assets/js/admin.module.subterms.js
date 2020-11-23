(function ($, plugin) {
  $('select.' + plugin._base + '-assoc-post-dropdown').change(function () {
    const linked = $(this).data('linked');
    const target = $('select.' + plugin._base + '-assoc-post-subterms[data-linked=' + linked + ']');

    if (linked == $(this).val()) { // eslint-disable-line eqeqeq
      target.parent('.-wrap').slideDown();
    } else {
      target.parent('.-wrap').slideUp();
    }
  });
}(jQuery, gEditorial));
