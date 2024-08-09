/* global jQuery, gEditorial, plupload */

(function ($, plugin, module, section) {
  const s = {
    action: plugin._base + '_' + module,
    classs: plugin._base + '-' + module,
    container: plugin._base + '-' + module + '-container', // vanilla selector
    pickfiles: plugin._base + '-' + module + '-pickfiles', // vanilla selector
    filequeue: '#' + plugin._base + '-' + module + '-filequeue'
  };

  const app = {
    uploader: null,

    rtl: $('html').attr('dir') === 'rtl',
    strings: $.extend({}, {
      wrong: 'Something&#8217;s wrong!'
    }, plugin[module].strings || {}),

    config: $.extend({}, {
      remote: false,
      chunk: '200kb', // '1mb'
      maxsize: '150mb',
      mimetypes: []
    }, plugin[module].config || {}),

    init: function () {
      if (!app.config.remote) {
        $(s.filequeue).html(app.strings.wrong);
        console.log(app.config);
        return false;
      } else {
        app.initUploader();
        return true;
      }
    },

    initUploader: function () {
      this.uploader = new plupload.Uploader({
        runtimes: 'html5',
        container: document.getElementById(s.container),
        browse_button: document.getElementById(s.pickfiles),
        url: app.config.remote,
        chunk_size: app.config.chunk,
        headers: {
          'Access-Control-Allow-Origin': '*'
        },
        filters: {
          prevent_duplicates: true,
          max_file_size: app.config.maxsize,
          mime_types: app.config.mimetypes
        },
        init: {
          PostInit: function () {
            $(s.filequeue).html('');
          },

          FilesAdded: function (up, files) {
            plupload.each(files, function (file) {
              $(`<div id="${file.id}">${file.name} (${plupload.formatSize(file.size)}) <strong></strong></div>`)
                .appendTo(s.filequeue);
            });

            app.uploader.start();
          },

          UploadProgress: function (up, file) {
            $(`#${file.id} strong`).html(`<span>${file.percent}%</span>`);
          },

          // TODO: generate full url with copy button
          FileUploaded: function (up, file, result) {
            // result Object
            // Object with response properties.
            // response String
            // The response body sent by the server.
            // status Number
            // The HTTP status code sent by the server.
            // responseHeaders String
            // All the response headers as a single string.
          },

          UploadComplete: function (up, files) {
            // Called when all files are either uploaded or failed
            console.log('[UploadComplete]');
          },

          Error: function (up, err) {
            console.log(err);
          }
        }
      });

      this.uploader.init();
    }
  };

  $(function () {
    $(document).trigger('gEditorial:Module:Loaded', [
      module,
      app,
      app.init()
    ]);
  });
}(jQuery, gEditorial, 'remoted', 'remote-uploads'));
