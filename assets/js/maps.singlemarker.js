/* global ol */

(function ($, plugin, mainkey, context) {
  const s = {
    classs: 'div.osm-map-container.' + [plugin._base, context, mainkey].join('-'),
    single: 'div.osm-map-container.map-single-marker'
  };

  const app = {
    settings: $.extend({}, {
      default_zoom: 16
    }, plugin[mainkey].settings ?? {}),

    init: function () {
      $(s.classs).each(function () {
        const el = $(this);
        app.singleMarker(
          el.get(0),
          el.data('lng'),
          el.data('lat'),
          el.data('zoom') || app.settings.default_zoom
        );
      });
    },

    singleMarker: function (target, lng, lat, zoom) {
      const iconStyle = new ol.style.Style({
        image: new ol.style.Circle({
          radius: 10,
          snapToPixel: false,
          fill: new ol.style.Fill({
            color: [66, 113, 174, 0.7]
          }),
          stroke: new ol.style.Stroke({
            color: [0, 0, 0, 1],
            width: 2
          })
        })
      });

      const iconFeature = new ol.Feature({
        geometry: new ol.geom.Point(ol.proj.fromLonLat([lng, lat]))
      });

      iconFeature.setStyle(iconStyle);

      const vectorLayer = new ol.layer.Vector({
        source: new ol.source.Vector({
          features: [iconFeature]
        })
      });

      const tileLayer = new ol.layer.Tile({
        source: new ol.source.OSM()
      });

      // https://openlayers.org/en/latest/apidoc/module-ol_Map-Map.html
      const map = new ol.Map({
        target,
        layers: [
          tileLayer,
          vectorLayer
        ],
        view: new ol.View({
          center: ol.proj.fromLonLat([lng, lat]),
          zoom
        })
      });

      console.log(map);
    }
  };

  $(function () {
    window[plugin._base] = window[plugin._base] || {};
    window[plugin._base][mainkey] = app;

    $(document).trigger('gEditorial:Module:Loaded', [
      mainkey,
      context,
      app,
      app.init()
    ]);
  });
}(jQuery, gEditorial, 'singlemarker', 'maps'));
