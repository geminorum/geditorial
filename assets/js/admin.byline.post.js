(function ($, plugin, module, section) {
  if (typeof plugin === 'undefined') return;

  const s = {
    // action: plugin._base + '_' + module,
    // classs: plugin._base + '-' + module,
    target: '#' + plugin._base + '-' + module + '-rendered'
  };

  const u = {
    inOut: (s, h) => {
      $(s).fadeOut('fast', function () {
        $(this).html(h).fadeIn();
      });
    }
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
        url: plugin._restBase + plugin[module].route,
        type: 'GET',
        dataType: 'json'
      })
        .done(app.requestDone)
        .fail(app.requestFailed);
    },

    requestDone: function (data) {
      // $(s.target).html(data[plugin[module].attr]);
      u.inOut(s.target, data[plugin[module].attr]);
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
}(jQuery, gEditorial, 'byline', 'post'));
