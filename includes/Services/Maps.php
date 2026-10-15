<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Maps extends gEditorial\Service
{
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
			'style' => 'width:100%;height:400px',
			'data'  => [
				'context' => Core\Text::force( $context ),
				'raw'     => Core\Text::force( $data ),
				'lat'     => $latlng[0],
				'lng'     => $latlng[1],
				'latlng'  => vsprintf( '%1$s,%2$s', $latlng ),
				'lonlat'  => vsprintf( '%2$s,%1$s', $latlng ),
			],
			'class' => [
				self::dsh( static::BASE, 'maps', 'singlemarker' ),
				'osm-map-container',
				'map-single-marker',
			],
		], NULL );

		return (bool) self::enqueueSingleMarker();
	}

	public static function enqueueSingleMarker(): string
	{
		static $enqueued;

		if ( $enqueued )
			return $enqueued;

		$mainkey = 'singlemarker';
		$asset   = [
			'settings' => [
				'default_zoom' => 16,
			],
		];

		gEditorial()->enqueue_asset_config( $asset, $mainkey );

		return $enqueued = gEditorial\Scripts::enqueue( self::dot( 'maps', $mainkey ), [
			'jquery',
			gEditorial\Scripts::pkgOpenLayers(),
		] );
	}
}
