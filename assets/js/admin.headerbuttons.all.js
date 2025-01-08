(function ($, plugin, module, section) {
  if (typeof plugin === 'undefined') return;

  const s = {
    headerEnd: '#wpbody-content div.wrap hr.wp-header-end',
    headerLegacy: 'body.term-php #wpbody-content .wrap > h1' // currently on: `term.php`
  };

  const app = {
    rtl: $('html').attr('dir') === 'rtl',

    appendButton: function (to) {
      if (!to.length) return false;
      const list = plugin[module].buttons || [];
      if (!list) return false;

      list.forEach((button) => {
        $(' ' + button).insertBefore(to);
      });
    },

    prependButton: function (to) {
      if (!to.length) return false;
      const list = plugin[module].buttons || [];
      if (!list) return false;

      // TODO: maybe append css class!
      to.css('display', 'inline-block');

      if (app.rtl) {
        to.css('margin-left', '5px'); // default by core styles
      } else {
        to.css('margin-right', '5px'); // default by core styles
      }

      list.forEach((button) => {
        $(' ' + button).insertAfter(to);
      });
    }
  };

  $(function () {
    app.appendButton($(s.headerEnd));
    app.prependButton($(s.headerLegacy));

    $(document).trigger('gEditorialReady', [module, app]);
  });
}(jQuery, gEditorial, 'headerbuttons', 'all'));
