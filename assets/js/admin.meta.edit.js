/* global inlineEditPost */

(function ($, plugin, mainkey, context) {
  if (!Object.keys(plugin[mainkey].fields).length) return;

  const s = {
    // action: plugin._base + '_' + mainkey,
    classs: plugin._base + '-' + mainkey,
    table: '#the-list',
    bulkedit: '.' + plugin._base + '-' + mainkey + '-bulkedit-',
    quickedit: '.' + plugin._base + '-' + mainkey + '-quickedit-',
    value: 'div.' + plugin._base + '-' + mainkey + '-value-'
  };

  const app = {
    init: function () {
      app.initBulk();
      $(s.table).on('click', '.editinline', app.clicked);
    },

    // @REF: https://rudrastyh.com/wordpress/quick-edit-tutorial.html
    initBulk: function () {
      const inlineEditPostSetBulk = inlineEditPost.setBulk;

      // we overwrite the it with our own
      inlineEditPost.setBulk = function () {
        inlineEditPostSetBulk.apply(this); // let's merge arguments of the original function

        const editColLeft = $('fieldset.inline-edit-col-left', 'div.inline-edit-wrapper');
        // const editColCenter = $('fieldset.inline-edit-col-center', '.inline-edit-col');

        for (const field in plugin[mainkey].fields) {
          $('div.inline-edit-wrapper')
            .find(s.bulkedit + field)
            .val('') // must be empty
            // .prop('disabled', disabled) // access checks applied on save
            .parents('label')
            .appendTo(editColLeft[0]);
        }
      };
    },

    clicked: function () {
      inlineEditPost.revert(); // revert Quick Edit menu so that it refreshes properly

      const tagID = $(this).parents('tr').attr('id');
      const postTitleLabel = $(':input[name="post_title"]', '.inline-edit-row').parents('label');
      // const postNameLabel = $(':input[name="post_name"]', '.inline-edit-row').parents('label');
      const postEditDate = $(':input[name="jj"]', '.inline-edit-row').parents('fieldset.inline-edit-date');

      for (const field in plugin[mainkey].fields) {
        const hidden = $('#' + tagID).find(s.value + field);
        const disabled = hidden.data('disabled') === true;

        // switch (plugin[mainkey].fields[field]) {
        switch (field) {
          case 'title_before':

            $('.inline-edit-row')
              .find(s.quickedit + field)
              .val(hidden.text())
              .prop('disabled', disabled)
              .parents('label')
              .insertBefore(postTitleLabel);

            break;

          case 'title_link':
          case 'title_after':

            $('.inline-edit-row')
              .find(s.quickedit + field)
              .val(hidden.text())
              .prop('disabled', disabled)
              .parents('label')
              .insertAfter(postTitleLabel);

            break;

          case 'address':
          case 'note':
          case 'textarea':

            $('.inline-edit-row')
              .find(s.quickedit + field)
              .html(hidden.text())
              .prop('disabled', disabled)
              .parents('label')
              .insertBefore(postEditDate);

            break;

          default:

            $('.inline-edit-row')
              .find(s.quickedit + field)
              .val(hidden.text())
              .prop('disabled', disabled)
              .parents('label')
              // .insertAfter(postNameLabel); // post_title maybe disabled for this posttype!
              .insertBefore(postEditDate);
        }
      }
    }
  };

  $(function () {
    $(document).trigger('gEditorial:Module:Loaded', [
      mainkey,
      context,
      app,
      app.init()
    ]);
  });
}(jQuery, gEditorial, 'meta', 'edit'));
