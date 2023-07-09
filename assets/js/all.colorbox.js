(function ($) {
  $('a.do-colorbox-iframe').each(function () {
    const instance = $(this);

    instance.on('click', function (event) {
      event.preventDefault();

      const options = {
        href: instance.attr('href'),
        title: instance.attr('title'),
        iframe: true,
        fastIframe: false,
        closeButton: false,
        // preloading: false,
        transition: 'none',
        width: '95%',
        height: '85%',
        maxWidth: instance.data('max-width') || '980px',
        maxHeight: instance.data('max-height') || '640px',
        onClosed: function () {
          // @REF: https://www.sitepoint.com/jquery-custom-events/
          $.event.trigger({
            type: 'gEditorial:ColorBox:Closed',
            // @REF: https://stackoverflow.com/questions/4187032/get-list-of-data-attributes-using-javascript-jquery
            passedData: instance.data(),
            passedLink: instance.attr('href'),
            passedTitle: instance.attr('title'),
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

    // $(document).bind('cbox_closed', function () {
    //   console.log($.colorbox.title);
    // });

    // $.colorbox.close();
  });
})(jQuery);
