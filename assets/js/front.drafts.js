(function ($, plugin, module) {
  if (typeof plugin === 'undefined') return;

  const s = {
    action: plugin._base + '_' + module,
    wrap: '#' + plugin._base + '-' + module + '-wrap',
    button: '#wpadminbar .' + plugin._base + '-' + module + ' a.ab-item',
    spinner: '.' + plugin._base + '-spinner'
  };

  const app = {
    empty: true,
    wrap: '<div id="' + plugin._base + '-' + module + '-wrap" class="geditorial-wrap -drafts" style="display:none;"><div class="-content"></div></div>',

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
          nonce: plugin[module]._nonce
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
    $(s.button).on('click', function (e) {
      e.preventDefault();
      app.populate();
    });
    $(document).trigger('gEditorialReady', [module, app]);
  });
}(jQuery, gEditorial, 'drafts'));
