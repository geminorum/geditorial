/* global inlineEditPost */

(function ($, plugin, module, section) {
  if (plugin === 'undefined') return;
  if (!Object.keys(plugin[module].fields).length) return;

  const s = {
    // action: plugin._base + '_' + module,
    classs: plugin._base + '-' + module,
    table: '#the-list',
    bulkedit: '.' + plugin._base + '-' + module + '-bulkedit-',
    quickedit: '.' + plugin._base + '-' + module + '-quickedit-',
    value: 'div.' + plugin._base + '-' + module + '-value-'
  };

  const app = {

    // @REF: https://rudrastyh.com/wordpress/quick-edit-tutorial.html
    initBulk: function () {
      const inlineEditPostSetBulk = inlineEditPost.setBulk;

      // we overwrite the it with our own
      inlineEditPost.setBulk = function () {
        inlineEditPostSetBulk.apply(this); // let's merge arguments of the original function

        const editColLeft = $('fieldset.inline-edit-col-left', 'div.inline-edit-wrapper');
        // const editColCenter = $('fieldset.inline-edit-col-center', '.inline-edit-col');

        for (const field in plugin[module].fields) {
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

      for (const field in plugin[module].fields) {
        const hidden = $('#' + tagID).find(s.value + field);
        const disabled = hidden.data('disabled') === true;

        switch (plugin[module].fields[field]) {
          case 'title_before':

            $('.inline-edit-row')
              .find(s.quickedit + field)
              .val(hidden.text())
              .prop('disabled', disabled)
              .parents('label')
              .insertBefore(postTitleLabel);

            break;
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
    app.initBulk();
    $(s.table).on('click', '.editinline', app.clicked);

    // $(document).trigger('gEditorialReady', [module, app]);
    $(document).trigger('gEditorial:Module:Loaded', [module, app]);
  });
}(jQuery, gEditorial, 'meta', 'edit'));
