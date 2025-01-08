(function ($, plugin, module, section) {
  if (typeof plugin === 'undefined') return;

  const s = {
    // action: plugin._base + '_' + module,
    // classs: plugin._base + '-' + module,
    wrap: '.' + plugin._base + '-wrap.-' + module + '.-admin-metabox',
    dropdown: '#' + plugin._base + '-' + module + '-printprofile',
    iframe: plugin._base + '-' + module + '-printiframe',
    preview: plugin._base + '-' + module + '-printpreview',
    print: plugin._base + '-' + module + '-printprint'
  };

  const app = {
    // rtl: $('html').attr('dir') === 'rtl',
    // strings: $.extend({}, {
    //   button_title: 'Import Items',
    //   button_text: 'Import'
    // }, plugin[module].strings || {}),

    init: function () {
      const $preview = $('#' + s.preview, s.wrap);
      const $print = $('#' + s.print, s.wrap);
      const $iframe = $('#' + s.iframe, s.wrap);

      $(s.dropdown, s.wrap).on('change', function () {
        const profile = $(this).val();
        if (profile !== '0') {
          $preview.attr('disabled', false);
          $print.attr('disabled', false);
          $preview.attr('href', $preview.attr('rel') + '&profile=' + profile);
          $iframe.attr('src', $print.attr('rel') + '&profile=' + profile);
        } else {
          $preview.attr('disabled', true);
          $print.attr('disabled', true);
          $preview.attr('href', '');
          $iframe.attr('src', '');
        }
      });

      // @REF: https://hdtuto.com/article/print-iframe-content-using-jquery-example
      $print.on('click', function () {
        const frame = document.getElementById(s.iframe).contentWindow;
        const temp = frame.parent.document.title;
        frame.parent.document.title = frame.document.title; // copy iframe title into current window
        frame.focus();
        frame.print();
        frame.parent.document.title = temp;
        return false;
      });
    }
  };

  $(function () {
    app.init();

    $(document).trigger('gEditorialReady', [module, app]);
  });
}(jQuery, gEditorial, 'papered', 'post'));
