(function ($, plugin, module) {
  const s = {
    // action: plugin._base + '_' + module,
    // classs: plugin._base + '-' + module,
    form: 'div#' + plugin._base + '-newpost form.-form',
    wrap: 'div.-wrap.geditorial-wrap.-newpost-layout',
    recent: 'div.-recents ul li a.-row-link-custom',
    save: 'a.button.-save-draft'
  };

  const app = {
    request: null,

    strings: $.extend({}, {
      noparent: 'This page has no parent window!',
      notarget: 'Cannot handle the target window!',
      closeme: 'New post has been saved you may close this!'
    }, plugin[module].strings || {}),

    init: function () {
      const that = this;

      if (self === top) {
        $('.-message', s.wrap)
          .html(that.strings.noparent)
          .addClass('-error');
      } else {
        $(s.recent, s.wrap).on('click', function (event) {
          event.preventDefault();
          that.handleRecent($(this).data('post'), $(this).data('type'));
        });

        $(s.save, s.wrap)
          .removeClass('disabled')
          .on('click', function (event) {
            event.preventDefault();
            that.doSaveDraft($(this).data('endpoint'), $(this).data('target'));
          });

        $(s.form).submit(function (event) {
          event.preventDefault();
          if (that.request && that.request.state() === 'pending') return;
          $(s.save, s.wrap).trigger('click');
        });
      }
    },

    handleRecent: function (post, type) {
      const select = $('select.' + plugin._base + '-paired-to-post-dropdown[data-type="' + type + '"]', window.parent.document);
      select.val(post).change();
      self.parent.tb_remove();
    },

    handlePaired: function (response) {
      const select = $('select.' + plugin._base + '-paired-to-post-dropdown[data-type="' + response.type + '"]', window.parent.document);

      select
        .removeClass('hidden') // maybe it is the first post ever!
        .append('<option value="' + response.id + '">' + response.title.rendered + '</option>')
        .val(response.id)
        .change();

      // @REF: https://stackoverflow.com/a/4601362
      // @REF: https://www.codeproject.com/Tips/1166607/Events-in-JavaScript-IFrame-vs-Parent-Window
      window.parent.jQuery(select).trigger('chosen:updated');

      self.parent.tb_remove();
    },

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

    doSaveDraft: function (endpoint, target) {
      const that = this;
      const data = that.getFormData(s.form);
      const $button = $(s.save, s.wrap);
      const $message = $('.-message', s.wrap);
      const $loading = $('.-loading', s.wrap);

      console.log(data);

      that.request = wp.apiRequest({
        url: endpoint,
        data: data,
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
            that.handlePaired(response);
          } else if (target === 'none') {
            $message.html(that.strings.closeme).addClass('-success');
            // TODO: clear the form
          } else {
            $message.html(that.strings.notarget).addClass('-error');
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
    $(document).trigger('gEditorialReady', [module, app]);
  });
}(jQuery, gEditorial, '_newpost'));
