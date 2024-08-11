(function ($, plugin, module, section) {
  const s = {
    // action: plugin._base + '_' + module.replace(/_/g, '-'),
    // classs: plugin._base + '-' + module.replace(/_/g, '-'),
    grid: '#' + plugin._base + '-' + module.replace(/_/g, '-') + '-data-grid'
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
}(jQuery, gEditorial, 'next_of_kin', 'post'));
