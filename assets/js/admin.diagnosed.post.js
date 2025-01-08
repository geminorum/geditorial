(function ($, plugin, module, section) {
  if (typeof plugin === 'undefined') return;

  const s = {
    // action: plugin._base + '_' + module,
    // classs: plugin._base + '-' + module,
    grid: '#' + plugin._base + '-' + module + '-data-grid'
  };

  const app = {
    request: null,
    // init: function () {},

    eventClosed: function (event) {
      if (!event.passedData || !event.colorboxType) return;
      if (event.colorboxType !== 'iframe') return;
      if (event.passedData.module !== module) return;
      app.doRefresh(event.passedData);
    },

    doRefresh: function (data) {
      app.request = wp.apiRequest({
        url: plugin._restBase + plugin[module]._rest + '/markup/' + data.linked,
        type: 'GET',
        dataType: 'json'
      })
        .done(app.requestDone)
        .fail(app.requestFailed);
    },

    requestDone: function (data) {
      $(s.grid).html(data.html);
    },

    requestFailed: function (data) {
      console.log(data);
    }
  };

  $(function () {
    // app.init();

    $(document).on('gEditorial:ColorBox:Closed', app.eventClosed);
    $(document).trigger('gEditorial:Module:Loaded', [module, app]);
  });
}(jQuery, gEditorial, 'diagnosed', 'post'));
