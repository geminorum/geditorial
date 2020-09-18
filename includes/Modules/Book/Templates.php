<?php namespace geminorum\gEditorial\Templates;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Helpers\Book as ModuleHelper;

class Book extends gEditorial\Template
{

	const MODULE = 'book';

	public static function summary( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = NULL;

		if ( ! array_key_exists( 'type', $atts ) )
			$atts['type'] = self::constant( 'publication_cpt', 'publication' );

		return self::metaSummary( $atts );
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

	public static function barcodeISBN( $atts = [] )
	{
		$args = self::atts( [
			'id'       => isset( $atts['post'] ) ? $atts['post'] : NULL,
			'filter'   => FALSE,
			'default'  => FALSE,
			'validate' => TRUE,
		], $atts );

		if ( ! $isbn = self::getMetaFieldRaw( 'publication_isbn', $args['id'], 'meta', TRUE ) )
			return $args['default'];

		$isbn = ModuleHelper::sanitizeISBN( $isbn, TRUE );

		if ( $args['validate'] && ! ModuleHelper::validateISBN( $isbn ) )
			return $args['default'];

		$args = self::atts( [
			'link'   => NULL,
			'before' => '',
			'after'  => '',
			'echo'   => TRUE,
		], $atts );

		$html = HTML::img( ModuleHelper::barcodeISBN( $isbn ), '-book-barcode-isbn', $isbn );

		if ( is_null( $args['link'] ) )
			$html = HTML::link( $html, ModuleHelper::lookupISBN( $isbn ) );

		else if ( $args['link'] )
			$html = HTML::link( $html, $args['link'] );

		$html = $args['before'].$html.$args['after'];

		if ( ! $args['before'] )
			echo $html;

		echo $html;
		return TRUE;
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
