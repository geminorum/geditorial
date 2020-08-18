/* global inlineEditPost */

(function ($, p, module) {
  // bail if no fields with quickedit support
  if (!Object.keys(p[module].fields).length) return;

  var prefix = p._base + '-' + module + '-';

  $('#the-list').on('click', '.editinline', function () {
    inlineEditPost.revert(); // revert Quick Edit menu so that it refreshes properly

    var tagID = $(this).parents('tr').attr('id');
    var postTitleLabel = $(':input[name="post_title"]', '.inline-edit-row').parents('label');
    var postNameLabel = $(':input[name="post_name"]', '.inline-edit-row').parents('label');

    for (var field in p[module].fields) {
      switch (p[module].fields[field]) {
        case 'title_before':

          $('.inline-edit-row')
            .find('input.' + prefix + field)
            .val($('#' + tagID)
              .find('div.' + prefix + field + '-value')
              .text())
            .parents('label')
            .insertBefore(postTitleLabel);

          break;
        case 'title_after':

          $('.inline-edit-row')
            .find('input.' + prefix + field)
            .val($('#' + tagID)
              .find('div.' + prefix + field + '-value')
              .text())
            .parents('label')
            .insertAfter(postTitleLabel);

          break;
        default:

          $('.inline-edit-row')
            .find('input.' + prefix + field)
            .val($('#' + tagID)
              .find('div.' + prefix + field + '-value')
              .text())
            .parents('label')
            .insertAfter(postNameLabel);
      }
    }
  });
}(jQuery, gEditorial, 'meta'));
