/* global inlineEditPost */

(function ($, plugin, mainkey, context) {
  const s = {
    table: '#the-list',
    quickedit: '#' + plugin._base + '-' + mainkey + '-quickedit-wrap',
    bulkedit: '#' + plugin._base + '-' + mainkey + '-bulkedit-wrap',
    select: 'select#' + plugin._base + '-' + mainkey + '-quickedit-',
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

        // const editColLeft = $('fieldset.inline-edit-col-left', 'div.inline-edit-wrapper');
        const editColCenter = $('fieldset.inline-edit-col-center', 'div.inline-edit-wrapper');
        const wrap = $(s.bulkedit, '.inline-edit-row');
        const list = wrap.data('taxonomies');

        if (!list) return;

        // wrap.appendTo(editColCenter[0]);
        wrap.prependTo(editColCenter[0]);
      };
    },

    clicked: function () {
      inlineEditPost.revert(); // revert Quick Edit menu so that it refreshes properly

      const tagID = $(this).parents('tr').attr('id');
      const editColCenter = $('fieldset.inline-edit-categories', '.inline-edit-row');
      const wrap = $(s.quickedit, '.inline-edit-row');
      const list = wrap.data('taxonomies');

      if (!list) return;

      list.forEach((tax) => {
        const val = $('#' + tagID).find(s.value + tax).text();
        const el = $(s.select + tax, wrap);
        // console.log(val);
        el.val(val);
        el.find('option').attr('selected', false);
        el.find('option[value="' + (val || '0') + '"]').attr('selected', true);
        el.trigger('change');
      });

      // wrap.appendTo(editColCenter[0]);
      wrap.prependTo(editColCenter[0]);
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
}(jQuery, gEditorial, 'singleselect', 'edit'));
