(function ($, plugin, module, section) {
  if (typeof plugin === 'undefined') return;

  const s = {
    overlay: '#cboxOverlay', // colorbox.js selector
    before: 'do-' + module + '-iframe',
    after: 'hooked-' + module + '-iframe'
  };

  const app = {
    mobile: function () {
      return ($(window).width() <= 782); // wp-core media query
    },

    hook: function (mobile) {
      $('a.' + s.before).each(function () {
        const $instance = $(this);

        $instance.on('click', function (event) {
          event.preventDefault();

          const options = {
            href: $instance.attr('href'),
            title: mobile ? false : $instance.attr('title'),
            escKey: true,
            iframe: true,
            fastIframe: false,
            // closeButton: false,
            // preloading: false,
            transition: 'none',
            width: mobile ? '100%' : ($instance.data('width') || '95%'),
            height: mobile ? '100%' : ($instance.data('width') || '85%'),
            maxWidth: mobile ? '100%' : ($instance.data('max-width') || '980'),
            maxHeight: mobile ? '100%' : ($instance.data('max-height') || '640'),
            onClosed: function () {
              // @REF: https://www.sitepoint.com/jquery-custom-events/
              $.event.trigger({
                type: 'gEditorial:ColorBox:Closed',
                // @REF: https://stackoverflow.com/questions/4187032/get-list-of-data-attributes-using-javascript-jquery
                passedData: $instance.data(),
                passedLink: $instance.attr('href'),
                passedTitle: $instance.attr('title'),
                colorboxType: 'iframe'
              });
            }
          };

          $.colorbox(options);

          // @REF: https://github.com/jackmoore/colorbox/issues/183#issuecomment-41087237
          $(window).on('resize', function () {
            $.colorbox.resize({
              width: window.innerWidth > parseInt(options.maxWidth) ? options.maxWidth : options.width,
              height: window.innerHeight > parseInt(options.maxHeight) ? options.maxHeight : options.height
            });
          });
        });

        $instance.removeClass(s.before);
        $instance.addClass(s.after);
      });
    }
  };

  $(window).load(function () {
    app.hook(app.mobile());

    $(document).on('gEditorial:ColorBox:Hook', function () { app.hook(); });
    $(document).trigger('gEditorial:Module:Loaded', [module, app]);
  });
}(jQuery, gEditorial, 'colorbox', 'all'));
