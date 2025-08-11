(function ($, plugin, module, section) {
  const s = {
    overlay: '#cboxOverlay', // colorbox.js selector
    trigger: 'a.do-' + module + '-iframe, .do-' + module + '-iframe-for-child > a',
    parent: 'do-' + module + '-iframe-for-child',
    before: 'do-' + module + '-iframe',
    after: 'hooked-' + module + '-iframe',
    pot: '.' + plugin._base + '-' + module + '-pot'
  };

  const u = {
    inOut: (s, h) => {
      $(s).fadeOut('fast', function () {
        $(this).html(h).fadeIn();
      });
    },

    mobile: () => ($(window).width() <= 782), // wp-core media query

    // Access object child properties using a dot notation string
    // @REF: https://stackoverflow.com/a/33397682
    prop: (data, key) => key.split('.').reduce((a, b) => a[b], data)
  };

  const app = {
    request: null,

    refresh: function (instance) {
      const prop = instance.data('refresh'); // `terms_rendered.{$taxonomy}.rendered`
      const route = instance.data('route');
      if (!prop || !route) return;

      app.request = wp.apiRequest({
        url: plugin._restBase + route,
        type: 'GET',
        dataType: 'json'
      })
        .done((data) => {
          u.inOut(instance.data('pot') || instance.closest(s.pot), u.prop(data, prop));
        })

        .fail((data) => {
          console.log(data);
        });
    },

    hook: function (mobile) {
      $(s.trigger).each(function () {
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
              app.refresh($instance);

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

        $instance.removeClass(s.before).parent(s.parent);
        $instance.addClass(s.after);
      });
    }
  };

  $(window).load(function () {
    app.hook(u.mobile());

    $(document).on('gEditorial:ColorBox:Hook', function () { app.hook(); });
    $(document).trigger('gEditorial:Module:Loaded', [module, app]);
  });
}(jQuery, gEditorial, 'colorbox', 'all'));
