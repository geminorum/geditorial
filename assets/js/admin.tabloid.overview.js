(function ($, plugin, mainkey, context) {
  const s = {
    wrap: 'div#' + plugin._base + '-' + mainkey + '-wrap',
    raw: 'div#' + plugin._base + '-' + mainkey + '-raw',
    data: plugin._base + '_' + mainkey + '_data',
    edit: 'a[data-action=edit]',
    debug: 'a[data-action=debug]',
    export: 'a[data-action=export]',
    print: 'a[data-action=print]'
  };

  const app = {
    config: $.extend({}, {
      printtitle: '',
      printstyles: '../' + plugin._base + '/assets/css/print.general.css'
    }, plugin[mainkey].config || {}),

    // @REF: https://stackoverflow.com/a/26414528
    export: function (el, data, name) {
      const enc = 'text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(data));
      el.setAttribute('href', 'data:' + enc);
      el.setAttribute('download', name + '.json');
      if (plugin._debug) console.log(enc);
    },

    init: function () {
      $(s.edit).on('click', function (e) {
        e.preventDefault();
        window.open($(this).attr('href'), '_blank');
      });

      $(s.export).on('click', function (e) {
        // e.preventDefault();
        app.export(this, window[s.data], 'post');
      });

      $(s.print).on('click', function (e) {
        e.preventDefault();
        $(s.wrap).printThis({
          // debug: plugin._debug,
          loadCSS: app.config.printstyles,
          pageTitle: app.config.printtitle
        });
      });

      $(s.debug).on('click', function (e) {
        e.preventDefault();
        $(s.raw).toggle();
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
}(jQuery, gEditorial, 'tabloid', 'overview'));
