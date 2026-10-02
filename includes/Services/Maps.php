<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Maps extends gEditorial\Service
{
	// WTF: initial version!
	public static function renderSingleMarker( mixed $data, ?string $context = NULL ): bool
	{
		$latlng = Core\LatLng::is( $data )
			? Core\LatLng::extract( $data )
			: Core\LatLng::sanitize( $data );

		if ( ! $latlng )
			return FALSE;

		if ( ! is_array( $latlng ) )
			$latlng = Core\LatLng::extract( $latlng );

		echo Core\HTML::tag( 'div', [
			'id'    => 'osmmapdiv',
			'style' => 'width:100%;height:400px',
			'data'  => [
				'raw'    => Core\Text::force( $data ),
				'lat'    => $latlng[0],
				'lng'    => $latlng[1],
				'latlng' => vsprintf( '%1$s,%2$s', $latlng ),
				'lonlat' => vsprintf( '%2$s,%1$s', $latlng ),
			],
		], NULL );

		?><script src="https://cdn.rawgit.com/openlayers/openlayers.github.io/master/en/v5.3.0/build/ol.js"></script>
			<script>
				jQuery( document ).ready( function(){

					var iconStyle = new ol.style.Style({
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

					var iconFeature = new ol.Feature({
						geometry: new ol.geom.Point(ol.proj.fromLonLat([ <?php esc_attr_e( $latlng[1] ); ?>, <?php esc_attr_e( $latlng[0] ); ?> ]))
					});
					iconFeature.setStyle( iconStyle );

					var vectorLayer = new ol.layer.Vector({
						source: new ol.source.Vector({
							features: [ iconFeature ]
						})
					});

					var tileLayer = new ol.layer.Tile({
						source: new ol.source.OSM()
					});

					var map = new ol.Map({
						target: 'osmmapdiv',
						layers: [
							tileLayer,
							vectorLayer
						],
						view: new ol.View({
							center: ol.proj.fromLonLat( [ <?php esc_attr_e( $latlng[1] ); ?>, <?php esc_attr_e( $latlng[0] ); ?> ] ),
							zoom: 16
						})
					});
				});
			</script><?php

		return TRUE;
	}
}
