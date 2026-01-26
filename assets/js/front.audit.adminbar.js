(function ($, plugin, mainkey, context) {
  const s = {
    action: plugin._base + '_' + mainkey,
    button: '#wp-admin-bar-' + plugin._base + '-' + mainkey + '-assignbox > a.ab-item',
    wrap: '#wp-admin-bar-' + plugin._base + '-' + mainkey + '-assignbox-content',
    spinner: '.geditorial-spinner'
  };

  const utils = {
    // @REF: https://remysharp.com/2010/07/21/throttling-function-calls
    // @OLD: https://stackoverflow.com/a/9424784
    debounce: function (fn, delay) {
      let timer = null;
      return function () {
        // const context = this;
        const context = app;
        const args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function () {
          fn.apply(context, args);
        }, delay);
      };
    }
  };

  const app = {
    empty: true,

    init: function () {
      $(s.button).on('click', function (event) {
        event.preventDefault();
        app.populate();
      });
    },

    watch: function () {
      $(':input', s.wrap).on('change', utils.debounce(function () {
        app.store();
      }, 1000));
    },

    store: function () {
      const form = $(':input', s.wrap).serialize();
      const spinner = $(s.button).find(s.spinner);

      $.ajax({
        url: plugin._url,
        method: 'POST',
        data: {
          action: s.action,
          what: 'store',
          post_id: plugin[mainkey].post_id,
          nonce: plugin[mainkey]._nonce,
          data: form
        },
        beforeSend: function (xhr) {
          spinner.addClass('is-active');
        },
        success: function (response, textStatus, xhr) {
          spinner.removeClass('is-active');

          if (response.success) {
            $(s.wrap).html(response.data); // utils.io will not work
            app.watch();
          }
        }
      });
    },

    populate: function () {
      if (!app.empty) return;

      const spinner = $(s.button).find(s.spinner);

      $.ajax({
        url: plugin._url,
        method: 'POST',
        data: {
          action: s.action,
          what: 'list',
          post_id: plugin[mainkey].post_id,
          nonce: plugin[mainkey]._nonce
        },
        beforeSend: function (xhr) {
          spinner.addClass('is-active');
        },
        success: function (response, textStatus, xhr) {
          spinner.removeClass('is-active');

          if (response.success) {
            $(s.wrap).html(response.data); // utils.io will not work
            app.empty = false;
            app.watch();
          }
        }
      });
    }
  };

  $(function () {
    // $(document).trigger('gEditorialReady', [mainkey, app]);
    $(document).trigger('gEditorial:Module:Loaded', [
      mainkey,
      context,
      app,
      app.init()
    ]);
  });
}(jQuery, gEditorial, 'audit', 'adminbar'));
