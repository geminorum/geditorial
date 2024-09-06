/* global inlineEditPost */

(function ($, p, module) {
  // bail if no fields with quickedit support
  if (!Object.keys(p[module].fields).length) return;

  const prefix = p._base + '-' + module + '-';

  $('#the-list').on('click', '.editinline', function () {
    inlineEditPost.revert(); // revert Quick Edit menu so that it refreshes properly

    const tagID = $(this).parents('tr').attr('id');
    const postTitleLabel = $(':input[name="post_title"]', '.inline-edit-row').parents('label');
    // const postNameLabel = $(':input[name="post_name"]', '.inline-edit-row').parents('label');
    const postEditDate = $(':input[name="jj"]', '.inline-edit-row').parents('fieldset.inline-edit-date');

    for (const field in p[module].fields) {
      const hidden = $('#' + tagID).find('div.' + prefix + field + '-value');
      const disabled = hidden.data('disabled') === true;

      switch (p[module].fields[field]) {
        case 'title_before':

          $('.inline-edit-row')
            .find('input.' + prefix + field)
            .val(hidden.text())
            .prop('disabled', disabled)
            .parents('label')
            .insertBefore(postTitleLabel);

          break;
        case 'title_after':

          $('.inline-edit-row')
            .find('input.' + prefix + field)
            .val(hidden.text())
            .prop('disabled', disabled)
            .parents('label')
            .insertAfter(postTitleLabel);

          break;
        default:

          $('.inline-edit-row')
            .find('input.' + prefix + field)
            .val(hidden.text())
            .prop('disabled', disabled)
            .parents('label')
            // .insertAfter(postNameLabel); // post_title maybe disabled for this posttype!
            .insertBefore(postEditDate);
      }
    }
  });
}(jQuery, gEditorial, 'units'));
