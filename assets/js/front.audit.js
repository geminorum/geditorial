/* global jQuery, gEditorial */

(function ($, plugin, module) {
  var s = {
    action: plugin._base + '_' + module,
    wrap: '#wpadminbar .' + plugin._base + '-' + module + '.-wrap',
    button: '#wpadminbar .' + plugin._base + '-' + module + '.-action a.ab-item',
    spinner: '.geditorial-spinner'
  };

  var app = {
    empty: true,

    watch: function () {
      $(':input', s.wrap).change(utils.debounce(function () {
        app.store();
      }, 1000));
    },

    store: function () {
      var data = $(':input', s.wrap).serialize();
      var spinner = $(s.button).find(s.spinner);

      $.ajax({
        url: plugin._url,
        method: 'POST',
        data: {
          action: s.action,
          what: 'store',
          post_id: plugin[module].post_id,
          nonce: plugin[module]._nonce,
          data: data
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

      var spinner = $(s.button).find(s.spinner);

      $.ajax({
        url: plugin._url,
        method: 'POST',
        data: {
          action: s.action,
          what: 'list',
          post_id: plugin[module].post_id,
          nonce: plugin[module]._nonce
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

  var utils = {
    // @REF: https://remysharp.com/2010/07/21/throttling-function-calls
    // @OLD: https://stackoverflow.com/a/9424784
    debounce: function (fn, delay) {
      var timer = null;
      return function () {
        // var context = this;
        var context = app;
        var args = arguments;
        clearTimeout(timer);
        timer = setTimeout(function () {
          fn.apply(context, args);
        }, delay);
      };
    }
  };

  $(function () {
    $(s.button).click(function (event) {
      event.preventDefault();
      app.populate();
    });

    $(document).trigger('gEditorialReady', [ module, app ]);
  });
}(jQuery, gEditorial, 'audit'));
