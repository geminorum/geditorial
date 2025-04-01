(function ($, plugin, module, section) {
  if (plugin === 'undefined') return;

  const s = {
    // action: plugin._base + '_' + module,
    // classs: plugin._base + '-' + module
    table: '#the-list',
    single: 'singleselect', // data prop
    raw: 'raw-value', // data prop
    hidden: 'div.hidden[data-' + module + ']',
    checkbox: ':input[type=checkbox]'
  };

  const app = {
    clicked: function (event) {
      $(s.hidden).each(function () {
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
            if (value && values.includes(value.toString())) {
              $(this).prop('checked', true);
            }
          });
        }
      });
    }
  };

  $(function () {
    $(s.table).on('click', '.editinline', app.clicked);

    // $(document).trigger('gEditorialReady', [module, app]);
    $(document).trigger('gEditorial:Module:Loaded', [module, app]);
  });
}(jQuery, gEditorial, 'taxtax', 'edit'));
