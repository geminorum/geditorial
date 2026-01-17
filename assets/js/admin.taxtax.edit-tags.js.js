(function ($, plugin, mainkey, context) {
  if (plugin === 'undefined') return;

  const s = {
    table: '#the-list',
    single: 'singleselect', // data prop
    raw: 'raw-value', // data prop
    hidden: 'div.hidden[data-' + mainkey + ']',
    checkbox: ':input[type=checkbox]'
  };

  const app = {
    init: function () {
      $(s.table).on('click', '.editinline', app.clicked);
    },

    clicked: function (event) {
      const tag = $(this).parents('tr').attr('id');
      $(s.hidden, '#' + tag).each(function () {
        const name = $(this).data('name');
        if ($(this).data(s.single)) {
          const input = $(':input[data-quickedit="' + name + '"]', '.inline-edit-row');
          const value = $(this).text().trim().split(',')[0];
          input.val(value);
          input.find('option').attr('selected', false);
          input.find('option[value="' + (value || '0') + '"]').attr('selected', true);
          input.trigger('change');
        } else {
          const wrap = $('div[data-quickedit="' + name + '"]', '.inline-edit-row');
          const values = $(this).text().trim().split(',');
          $(s.checkbox, wrap).each(function () {
            const value = $(this).data(s.raw);
            // avoid `0`
            $(this).prop('checked', (value && values.includes(value.toString())));
          });
        }
      });
    }
  };

  $(function () {
    // $(document).trigger('gEditorialReady', [mainkey, app]);
    $(document).trigger('gEditorial:Module:Loaded', [
      mainkey,
      context,
      app,
      app.init()
    ]);
  });
}(jQuery, gEditorial, 'taxtax', 'edit'));
