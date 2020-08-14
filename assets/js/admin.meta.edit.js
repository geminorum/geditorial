/* global inlineEditPost */

(function ($) {
  var fields = $.extend({}, {
    over_title: false,
    sub_title: false,
    byline: false
  }, gEditorial.meta.fields);

  $('#the-list').on('click', '.editinline', function () {
    inlineEditPost.revert(); // revert Quick Edit menu so that it refreshes properly

    var tagID = $(this).parents('tr').attr('id');
    var postTitleLabel = $(':input[name="post_title"]', '.inline-edit-row').parents('label');
    var postNameLabel = $(':input[name="post_name"]', '.inline-edit-row').parents('label');

    if (fields.over_title) {
      var overTitle = $('#' + tagID)
        .find('div.geditorial-meta-over_title-value')
        .text();

      $('.inline-edit-row')
        .find('input.geditorial-meta-over_title')
        .val(overTitle)
        .parents('label')
        .insertBefore(postTitleLabel);
    }

    if (fields.sub_title) {
      var subTitle = $('#' + tagID)
        .find('div.geditorial-meta-sub_title-value')
        .text();

      $('.inline-edit-row')
        .find('input.geditorial-meta-sub_title')
        .val(subTitle)
        .parents('label')
        .insertAfter(postTitleLabel);
    }

    if (fields.byline) {
      var byline = $('#' + tagID)
        .find('div.geditorial-meta-byline-value')
        .text();

      $('.inline-edit-row')
        .find('input.geditorial-meta-byline')
        .val(byline)
        .parents('label')
        .insertAfter(postNameLabel);
    }
  });
}(jQuery));
