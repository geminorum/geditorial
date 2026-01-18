/* global jQuery, gEditorial */

(function ($, plugin, mainkey, context) {
  const s = {
    // action: [plugin._base, mainkey].join('_'),
    // classs: [plugin._base, mainkey].join('-'),
    heart: '#' + ['wp-admin-bar', plugin._base, mainkey].join('-') + ' .ab-icon',
    beat: 'beat' // CSS class
  };

  const app = {

    // strings: $.extend({}, {
    //   modal_title: 'Choose an Image',
    //   modal_button: 'Set as image'
    // }, plugin[mainkey].strings || {}),

    config: $.extend({}, {
      value: 'alive',
      send: 'heartbeat-send',
      tick: 'heartbeat-tick'
    // }, plugin[mainkey].config || {}),
    }), // not sending the config yet!

    init: function () {
      // Hook into heartbeat-send
      $(document).on(app.config.send, function (e, data) {
        data[mainkey] = app.config.value;
      });

      // Listen for heartbeat-tick
      $(document).on(app.config.tick, function (e, data) {
        // Bail if not heart throb
        if (!data[mainkey]) return;

        const $heart = $(s.heart);

        // Toggle the beat
        $heart.toggleClass(s.beat);

        // Toggle the beat
        setTimeout(function () {
          $heart.toggleClass(s.beat);
        }, 1000);
      });
    }
  };

  $(function () {
    // $(document).trigger('gEditorialReady', [mainkey, app]);
    $(document).trigger('gEditorial:Module:Loaded', [
      mainkey,
      context,
      app,
      app.init()
    ]);
  });
}(jQuery, gEditorial, 'systemheartbeat', 'all'));
