(function ($, plugin, module) {
  const s = {
    action: plugin._base + '_' + module,
    button: '#wpadminbar .' + plugin._base + '-' + module + '.-action a',
    spinner: '.' + plugin._base + '-spinner'
  };

  const app = {
    action: function (el) {
      const action = $(el).attr('rel');
      const spinner = $(el).find(s.spinner);

      $.ajax({
        url: plugin._url,
        method: 'POST',
        data: {
          action: s.action,
          what: action,
          post_id: plugin[module].post_id,
          nonce: plugin[module]._nonce
        },
        beforeSend: function (xhr) {
          spinner.addClass('is-active');
        },
        success: function (response, textStatus, xhr) {
          spinner.removeClass('is-active');
          $(el).parent().html(response.data);
        }
      });
    }
  };

  $(function () {
    $(s.button).on('click', function (event) {
      event.preventDefault();
      app.action(this);
    });

    $(document).trigger('gEditorialReady', [module, app]);
  });
}(jQuery, gEditorial, 'markdown'));
