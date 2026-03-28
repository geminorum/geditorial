(function ($, plugin, mainkey, context) {
  const s = {
    action: [plugin._base, mainkey].join('_'),
    classs: [plugin._base, mainkey, context, 'wrap'].join('-'),
    wrap: '#' + [plugin._base, mainkey, context, 'wrap'].join('-'),
    button: '#' + ['wp-admin-bar', plugin._base, mainkey].join('-') + ' > .ab-item',
    spinner: '.' + plugin._base + '-spinner'
  };

  const app = {
    empty: true,
    wrap: '<div id="' + s.classs + '" style="display:none"><div class="-content"></div></div>',

    init: function () {
      $(s.button).on('click', function (e) {
        e.preventDefault();
        app.populate();
      });
    },

    toggle: function () {
      if ($(s.wrap).is(':visible')) {
        $(s.wrap).slideUp(function () {
          $(this).hide();
        });
      } else {
        $(s.wrap).css({ height: 'auto' }).slideDown();
      }
    },

    populate: function () {
      if (!app.empty) {
        app.toggle();
        return;
      }

      $('body').append(app.wrap);

      const spinner = $(s.button).find(s.spinner);

      $.ajax({
        url: plugin._url,
        method: 'POST',
        data: {
          action: s.action,
          what: 'list',
          nonce: plugin[mainkey]._nonce
        },
        beforeSend: function (xhr) {
          spinner.addClass('is-active');
        },
        success: function (response, textStatus, xhr) {
          spinner.removeClass('is-active');

          if (response.success) {
            $(s.wrap).find('.-content').html(response.data);
            app.empty = false;
            app.toggle();
          }
        }
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
}(jQuery, gEditorial, 'drafts', 'adminbar'));
