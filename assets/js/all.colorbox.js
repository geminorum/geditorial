(function ($, plugin, module, section) {
  const s = {
    // action: plugin._base + '_' + module,
    // classs: plugin._base + '-' + module,
    before: 'do-' + module + '-iframe',
    after: 'hooked-' + module + '-iframe'
  };

  const app = {
    hook: function () {
      $('a.' + s.before).each(function () {
        const $instance = $(this);

        $instance.on('click', function (event) {
          event.preventDefault();

          const options = {
            href: $instance.attr('href'),
            title: $instance.attr('title'),
            iframe: true,
            fastIframe: false,
            // closeButton: false,
            // preloading: false,
            transition: 'none',
            width: '95%',
            height: '85%',
            maxWidth: $instance.data('max-width') || '980',
            maxHeight: $instance.data('max-height') || '640',
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

          $(window).on('resize', function () {
            $.colorbox.resize({
              width: window.innerWidth > parseInt(options.maxWidth) ? options.maxWidth : options.width,
              height: window.innerHeight > parseInt(options.maxHeight) ? options.maxHeight : options.height
            });
          });
        });

        $instance.removeClass(s.before);
        $instance.addClass(s.after);

        // $(document).bind('cbox_closed', function () {
        //   console.log($.colorbox.title);
        // });

        // $.colorbox.close();
      });
    }
  };

  $(window).load(function () {
    app.hook();

    $(document).on('gEditorial:ColorBox:Hook', function () { app.hook(); });
    $(document).trigger('gEditorial:Module:Loaded', [module, app]);
  });
}(jQuery, gEditorial, 'colorbox', 'all'));
