/* global jQuery, inlineEditPost, gEditorial */

(function ($) {
  $('#the-list').on('click', '.editinline', function () {
    inlineEditPost.revert(); // revert Quick Edit menu so that it refreshes properly

    var fields = $.extend({}, {
      ot: false,
      st: false,
      as: false
    }, gEditorial.meta.fields);

    var tagID = $(this).parents('tr').attr('id');
    var postTitleLabel = $(':input[name="post_title"]', '.inline-edit-row').parents('label');
    var postNameLabel = $(':input[name="post_name"]', '.inline-edit-row').parents('label');

    if (fields.ot) {
      var ot = $('#' + tagID)
        .find('div.geditorial-meta-ot-value')
        .text();

      $('.inline-edit-row')
        .find('input.geditorial-meta-ot')
        .val(ot)
        .parents('label')
        .insertBefore(postTitleLabel);
    }

    if (fields.st) {
      var st = $('#' + tagID)
        .find('div.geditorial-meta-st-value')
        .text();

      $('.inline-edit-row')
        .find('input.geditorial-meta-st')
        .val(st)
        .parents('label')
        .insertAfter(postTitleLabel);
    }

    if (fields.as) {
      var as = $('#' + tagID)
        .find('div.geditorial-meta-as-value')
        .text();

      $('.inline-edit-row')
        .find('input.geditorial-meta-as')
        .val(as)
        .parents('label')
        .insertAfter(postNameLabel);
    }
  });
}(jQuery));
