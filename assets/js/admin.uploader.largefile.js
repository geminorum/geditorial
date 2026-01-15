/* global FileReader */

(function ($, plugin, mainkey, context) {
  const sliceSize = 1000 * 1024;
  let reader = {};
  let file = {};

  const s = {
    action: [plugin._base, mainkey].join('_'),
    classs: [plugin._base, mainkey].join('-'),
    input: '#' + [plugin._base, mainkey, context, 'input'].join('-'),
    name: '#' + [plugin._base, mainkey, context, 'name'].join('-'),
    submit: '#' + [plugin._base, mainkey, context, 'submit'].join('-'),
    progress: '#' + [plugin._base, mainkey, context, 'progress'].join('-')
  };

  const app = {
    init: function () {
      $(s.input).on('change', function () {
        const filename = $(this).val();
        if (filename !== '') {
          $(s.name).html(u.baseName(filename) + ' (' + u.formatBytes(this.files[0].size) + ')').show();
          $(s.submit).prop('disabled', false);
        } else {
          $(s.name).html('').hide();
          $(s.submit).prop('disabled', true);
        }
      });

      $(s.submit).on('click', function (event) {
        event.preventDefault();
        $(this).prop('disabled', true);

        reader = new FileReader();
        file = document.querySelector(s.input).files[0];

        app.check();
      });
    },

    check: function () {
      const $submit = $(s.submit);
      const $spinner = $(s.progress).prev('.spinner');

      $.ajax({
        url: plugin._url,
        type: 'POST',
        dataType: 'json',
        cache: false,
        data: {
          action: s.action,
          what: 'upload_check',
          file: file.name,
          mime: file.type,
          nonce: $submit.data('nonce')
        },
        beforeSend: function (xhr) {
          $spinner.addClass('is-active');
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.log(jqXHR, textStatus, errorThrown);
        },
        success: function (response) {
          $spinner.removeClass('is-active');
          if (response.success) {
            app.upload(0);
          } else {
            u.io(s.progress, response.data);
            $submit.prop('disabled', true);
            console.log(response);
          }
        }
      });
    },

    upload: function (start) {
      const sliceNext = start + sliceSize + 1;
      const blob = file.slice(start, sliceNext);

      reader.onloadend = function (event) {
        if (event.target.readyState !== FileReader.DONE) {
          return;
        }

        const $submit = $(s.submit);

        $.ajax({
          url: plugin._url,
          type: 'POST',
          dataType: 'json',
          cache: false,
          data: {
            action: s.action,
            what: 'upload_chaunk',
            file_data: event.target.result,
            file: file.name,
            mime: file.type,
            chunk: start,
            nonce: $submit.data('nonce')
          },
          error: function (jqXHR, textStatus, errorThrown) {
            console.log(jqXHR, textStatus, errorThrown);
          },
          success: function (response) {
            if (response.success) {
              const doneSize = start + sliceSize;
              let donePercent = Math.floor((doneSize / file.size) * 100);
              if (sliceNext < file.size) {
                if ($submit.data('locale') === 'fa_IR') {
                  donePercent = u.tP(donePercent);
                }
                u.io(s.progress, u.sp($submit.data('progress'), donePercent));
                app.upload(sliceNext);
              } else {
                $(s.name).html('').hide();
                $(s.submit).prop('disabled', true);
                u.io(s.progress, $submit.data('complete'));
                app.complete(file.name);
              }
            } else {
              u.io(s.progress, response.data);
              console.log(response);
            }
          }
        });
      };

      reader.readAsDataURL(blob);
    },

    complete: function (filename) {
      const $submit = $(s.submit);
      const $spinner = $(s.progress).prev('.spinner');

      $.ajax({
        url: plugin._url,
        type: 'GET',
        data: {
          action: s.action,
          what: 'upload_complete',
          file: filename,
          nonce: $submit.data('nonce')
        },
        beforeSend: function (xhr) {
          $spinner.addClass('is-active');
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.log(jqXHR, textStatus, errorThrown);
        },
        success: function (response) {
          $spinner.removeClass('is-active');
          if (response.success) {
            u.io(s.progress, response.data);
          } else {
            u.io(s.progress, response.data);
            console.log(response);
          }
        }
      });
    }
  };

  const u = {
    baseName: function (path) {
      return path.split(/[\\/]/).pop();
    },

    formatBytes: function (bytes, decimals) {
      if (bytes === 0) return '0 Bytes';
      const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
      const i = Math.floor(Math.log(bytes) / Math.log(1024));
      return parseFloat((bytes / Math.pow(1024, i)).toFixed(decimals || 2)) + ' ' + sizes[i];
    },

    // @REF: https://gist.github.com/geminorum/ebb48ff0c0df3876e58610dbb5a60f0f
    sp: function (f) {
      const a = Array.prototype.slice.call(arguments, 1);
      let i = 0;
      return f.replace(/%s/g, function () {
        return a[i++];
      });
    },

    tP: function (n) {
      const p = '۰'.charCodeAt(0);
      return n.toString().replace(/\d+/g, function (m) {
        return m.split('').map(function (n) {
          return String.fromCharCode(p + parseInt(n));
        }).join('');
      });
    },

    io: function (s, h) {
      $(s).fadeOut('fast', function () {
        $(this).html(h).fadeIn();
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
}(jQuery, gEditorial, 'uploader', 'largefile'));
