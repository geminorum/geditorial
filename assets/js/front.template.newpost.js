(function ($, plugin, module, section) {
  if (typeof plugin === 'undefined') return;

  const s = {
    // action: plugin._base + '_' + module,
    // classs: plugin._base + '-' + module
    wrap: 'div.' + plugin._base + '-wrap.-newpost-layout',
    form: 'div.' + plugin._base + '-wrap.-newpost-layout form.-form-form',
    save: 'a.button.-form-save-data'
  };

  const app = {
    request: null,

    // rtl: $('html').attr('dir') === 'rtl',
    strings: $.extend({}, {
      notarget: 'Cannot handle the target window!',
      willgo: 'Your Draft save successfully. will redirect you in moments &hellip;'
    }, plugin[module].strings || {}),

    // this is our own version!
    // @REF: https://stackoverflow.com/a/55460579
    getFormData: function (selector) {
      const object = $(selector).serializeArray().reduce(function (obj, item) {
        const matched = /^(\w+)\[(\w+)\]$/.exec(item.name);
        if (matched) {
          if (typeof obj[matched[1]] === 'undefined') obj[matched[1]] = {};
          obj[matched[1]][matched[2]] = item.value;
          return obj;
        }

        const name = item.name.replace('[]', '');
        if (typeof obj[name] !== 'undefined') {
          if (!Array.isArray(obj[name])) {
            obj[name] = [obj[name], item.value];
          } else {
            obj[name].push(item.value);
          }
        } else {
          obj[name] = item.value;
        }

        return obj;
      }, {});

      return object;
    },

    init: function () {
      $(s.save, s.wrap)
        .removeClass('disabled')
        .on('click', function (event) {
          event.preventDefault();
          app.doSaveDraft(
            $(this).data('endpoint'),
            $(this).data('target')
          );
        });

      $(s.form).on('submit', function (event) {
        event.preventDefault();
        if (app.request && app.request.state() === 'pending') return;
        $(s.save, s.wrap).trigger('click');
      });
    },

    doSaveDraft: function (endpoint, target) {
      const form = app.getFormData(s.form);
      const $button = $(s.save, s.wrap);
      const $message = $('.-message', s.wrap);
      const $loading = $('.-loading', s.wrap);

      app.request = wp.apiRequest({
        url: endpoint,
        data: form,
        type: 'POST',
        // async: false,
        dataType: 'json',
        // context: this
        beforeSend: function () {
          $message.html('').removeClass('-error');
          $loading.addClass('is-active');
          $button.addClass('disabled');
        }
      })

        .done(function (response) {
          if (target === 'paired') {
            // app.handlePaired(response);
          } else if (target === 'none') {
            $message.html(app.strings.willgo).addClass('-success');
            // $(s.form).trigger('reset');
            setTimeout(() => {
              // window.location.href = response.link;
              // $(window).attr('location',response.link)
              window.location.replace(response.link);
            }, 5000);
          } else {
            $message.html(app.strings.notarget).addClass('-error');
          }

          console.log(response);
        })

        .fail(function (response) {
          $message.html(response.responseJSON.message).addClass('-error');
          console.log(response.responseJSON);
        })

        .always(function () {
          $loading.removeClass('is-active');
          $button.removeClass('disabled');
        });
    }
  };

  $(function () {
    app.init();

    // $(document).trigger('gEditorialReady', [module, app]);
    $(document).trigger('gEditorial:Module:Loaded', [module, app]);
  });
}(jQuery, gEditorial, '_template', 'newpost'));
