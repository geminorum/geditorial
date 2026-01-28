(function ($, plugin, mainkey, context) {
  const s = {
    action: plugin._base + '_' + mainkey,
    button: '#wp-admin-bar-' + plugin._base + '-' + mainkey + '-toolbox .' + plugin._base + '-' + context + '-node.-do-action .ab-item',
    pot: '#wp-admin-bar-' + plugin._base + '-' + mainkey + '-pot .ab-item',
    spinner: '.' + plugin._base + '-spinner'
  };

  const utils = {
    io: (s, h) => {
      $(s).fadeOut('fast', function () {
        $(this).html(h || '').fadeIn();
      });
    },
    reload: (deley) => {
      setTimeout(() => {
        window.location.reload();
      }, deley || 2000);
    }
  };

  const app = {
    init: function () {
      $(s.button).on('click', function (event) {
        event.preventDefault();
        app.action(this);
      });
    },

    action: function (el) {
      const $spinner = $(el).find(s.spinner);
      const what = $(el).attr('rel');

      $.ajax({
        url: plugin._url,
        method: 'POST',
        data: {
          what,
          action: s.action,
          post_id: plugin[mainkey].post_id,
          nonce: plugin[mainkey]._nonce
        },
        beforeSend: function (xhr) {
          $spinner.addClass('is-active');
        },
        success: function (response, textStatus, xhr) {
          $spinner.removeClass('is-active');
          if (response.success) {
            utils.io(s.pot, response.data);
            utils.reload();
            console.log(response);
          } else {
            utils.io(s.pot, response.data);
            console.log(response);
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
}(jQuery, gEditorial, 'markdown', 'adminbar'));
