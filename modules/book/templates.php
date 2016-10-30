<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialBookTemplates extends gEditorialTemplateCore
{

	const MODULE = 'book';

	public static function cover( $atts = array() )
	{
		return parent::postImage( $atts, self::MODULE );
	}

	// FIXME: DRAFT
	// @SOURCE: http://wordpress.stackexchange.com/a/126928
	function get_by_order()
	{
		$wp_query = new WP_Query( array(
			'post_type'      => 'resource',
			'meta_key'       => 'publication_date',
			'orderby'        => 'meta_value title',
			'order'          => 'ASC',
			'paged'          => $paged,
			'posts_per_page' => '10',

			'tax_query' => array( array(
				'taxonomy' => 'resource_types',
				'field'    => 'slug',
				'terms'    => get_queried_object()->name,
			) ),

			'meta_query' => array(
				'relation' => 'OR',
				array( // check to see if date has been filled out
					'key'     => 'publication_date',
					'compare' => '=',
					'value'   => date('Y-m-d')
				),
				array( // if no date has been added show these posts too
					'key'     => 'publication_date',
					'value'   => date('Y-m-d'),
					'compare' => 'NOT EXISTS'
				),
			),
		) );
	}
}
