<?php namespace geminorum\gEditorial\Modules\Happening;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\WordPress;

class ModuleTemplate extends gEditorial\Template
{

	const MODULE = 'happening';

	// FIXME: must get latest from meta date
	public static function getLatestEventID()
	{
		return WordPress\PostType::getLastMenuOrder( self::constant( 'primary_posttype', 'happening' ), '', 'ID', 'publish' );
	}

	public static function theCover( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = NULL;

		return self::cover( $atts );
	}

	public static function cover( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = 'paired';

		if ( ! array_key_exists( 'type', $atts ) )
			$atts['type'] = self::constant( 'primary_posttype', 'happening' );

		return parent::postImage( $atts, static::MODULE );
	}

	// @REF: https://code.tutsplus.com/tutorials/building-a-simple-announcements-plugin-for-wordpress--wp-27661
	public static function currents()
	{
		$today = date( 'Y-m-d' );

		$args = [
			'post_type'      => 'announcements',
			'posts_per_page' => 0,
			'meta_key'       => 'sap_end_date',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
			'meta_query'     => [
				[
					'key' => 'sap_start_date',
					'value' => $today,
					'compare' => '<=',
				],
				[
					'key' => 'sap_end_date',
					'value' => $today,
					'compare' => '>=',
				]
			]
		];

		$query = new \WP_Query( $args );
	}
}
