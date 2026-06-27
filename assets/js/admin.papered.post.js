(function ($, plugin, mainkey, context) {
  const s = {
    wrap: '.' + plugin._base + '-wrap.-' + mainkey + '.-admin-metabox',
    dropdown: '#' + plugin._base + '-' + mainkey + '-printprofile',
    iframe: plugin._base + '-' + mainkey + '-printiframe',
    preview: plugin._base + '-' + mainkey + '-printpreview',
    print: plugin._base + '-' + mainkey + '-printprint'
  };

  const app = {
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
    $(document).trigger('gEditorial:Module:Loaded', [
      mainkey,
      context,
      app,
      app.init()
    ]);
  });
}(jQuery, gEditorial, 'papered', 'post'));
