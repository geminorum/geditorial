(function ($, plugin, mainkey, context) {
  if (typeof plugin === 'undefined') return;

  const s = {
    headerEnd: '#wpbody-content div.wrap hr.wp-header-end',
    headerLegacy: 'body.term-php #wpbody-content .wrap > h1' // currently on: `term.php`
  };

  const app = {
    rtl: $('html').attr('dir') === 'rtl',

    init: function () {
      app.appendButton($(s.headerEnd));
      app.prependButton($(s.headerLegacy));
    },

    appendButton: function (to) {
      if (!to.length) return false;
      const list = plugin[mainkey].buttons || [];
      if (!list) return false;

      list.forEach((button) => {
        $(' ' + button).insertBefore(to);
      });
    },

    prependButton: function (to) {
      if (!to.length) return false;
      const list = plugin[mainkey].buttons || [];
      if (!list) return false;

      // TODO: maybe append CSS class!
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
    $(document).trigger('gEditorial:Module:Loaded', [
      mainkey,
      context,
      app,
      app.init()
    ]);
  });
}(jQuery, gEditorial, 'headerbuttons', 'all'));
