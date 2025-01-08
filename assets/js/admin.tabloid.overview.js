(function ($, plugin, module, section) {
  if (typeof plugin === 'undefined') return;

  const s = {
    // action: plugin._base + '_' + module,
    // classs: plugin._base + '-' + module,
    wrap: 'div#' + plugin._base + '-' + module + '-wrap',
    raw: 'div#' + plugin._base + '-' + module + '-raw',
    data: plugin._base + '_' + module + '_data',
    edit: 'a[data-action=edit]',
    debug: 'a[data-action=debug]',
    export: 'a[data-action=export]',
    print: 'a[data-action=print]'
  };

  const app = {
    rtl: $('html').attr('dir') === 'rtl',
    config: $.extend({}, {
      printtitle: '',
      printstyles: '../' + plugin._base + '/assets/css/print.general.css'
    }, plugin[module].config || {}),

    // @REF: https://stackoverflow.com/a/26414528
    export: function (el, data, name) {
      const enc = 'text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(data));
      el.setAttribute('href', 'data:' + enc);
      el.setAttribute('download', name + '.json');
    }
  };

  $(function () {
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
        // debug: true,
        loadCSS: app.config.printstyles,
        pageTitle: app.config.printtitle
      });
    });

    $(s.debug).on('click', function (e) {
      e.preventDefault();
      $(s.raw).toggle();
    });

    $(document).trigger('gEditorialReady', [module, app]);
  });
}(jQuery, gEditorial, 'tabloid', 'overview'));
