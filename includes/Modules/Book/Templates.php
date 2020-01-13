<?php namespace geminorum\gEditorial\Templates;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;

class Book extends gEditorial\Template
{

	const MODULE = 'book';

	// FIXME: DRAFT / NOT USED
	public static function summary( $atts = [] )
	{
		if ( ! gEditorial()->enabled( 'meta' ) )
			return;

		$posttype = self::constant( 'publication_cpt', 'publication' );
		$fields   = gEditorial()->meta->posttype_fields_all( $posttype );

		$rows = [];

		foreach ( $fields as $field => $args )
			if ( $meta = self::getMetaField( $field, [ 'id' => 116 ] ) )
				$rows[$args['title']] = $meta;

		echo HTML::tableCode( $rows );
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
			$atts['id'] = 'assoc';

		if ( ! array_key_exists( 'type', $atts ) )
			$atts['type'] = self::constant( 'publication_cpt', 'publication' );

		return parent::postImage( $atts, static::MODULE );
	}

	// FIXME: DRAFT
	// @SOURCE: http://wordpress.stackexchange.com/a/126928
	function get_by_order()
	{
		$wp_query = new \WP_Query( [
			'post_type'      => 'resource',
			'meta_key'       => 'publication_date',
			'orderby'        => 'meta_value title',
			'order'          => 'ASC',
			'paged'          => $paged,
			'posts_per_page' => '10',

			'tax_query' => [ [
				'taxonomy' => 'resource_types',
				'field'    => 'slug',
				'terms'    => get_queried_object()->name,
			] ],

			'meta_query' => [
				'relation' => 'OR',
				[ // check to see if date has been filled out
					'key'     => 'publication_date',
					'compare' => '=',
					'value'   => date('Y-m-d')
				],
				[ // if no date has been added show these posts too
					'key'     => 'publication_date',
					'value'   => date('Y-m-d'),
					'compare' => 'NOT EXISTS'
				],
			],
		] );
	}
}
