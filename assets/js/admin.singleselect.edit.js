/* global jQuery, gEditorial, inlineEditPost */

(function ($, plugin, module, section) {
  if (plugin === 'undefined') return;

  const s = {
    // action: plugin._base + '_' + module,
    // classs: plugin._base + '-' + module
    table: '#the-list',
    wrap: '#' + plugin._base + '-' + module + '-wrap',
    select: 'select#' + plugin._base + '-' + module + '-select-',
    value: 'div.' + plugin._base + '-' + module + '-value-'
  };

  const app = {

    clicked: function () {
      inlineEditPost.revert(); // revert Quick Edit menu so that it refreshes properly

      const tagID = $(this).parents('tr').attr('id');
      const editColCenter = $('fieldset.inline-edit-categories', '.inline-edit-row');
      const wrap = $(s.wrap, '.inline-edit-row');
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
    $(s.table).on('click', '.editinline', app.clicked);

    // $(document).trigger('gEditorialReady', [module, app]);
    $(document).trigger('gEditorial:Module:Loaded', [module, app]);
  });
}(jQuery, gEditorial, 'singleselect', 'edit'));
