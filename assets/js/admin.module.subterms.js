(function ($, plugin) {
  $('select.' + plugin._base + '-paired-to-post-dropdown').on('change', function () {
    const paired = $(this).data('paired');
    const target = $('select.' + plugin._base + '-paired-subterms[data-paired=' + paired + ']');

    if (paired == $(this).val()) { // eslint-disable-line eqeqeq
      target.parent('.-wrap').slideDown();
    } else {
      target.parent('.-wrap').slideUp();
    }
  });
}(jQuery, gEditorial));
