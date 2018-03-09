<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Core\HTML;

class PostType extends Core\Base
{

	// EDITED: 12/25/2016, 1:27:21 PM
	public static function get( $mod = 0, $args = array( 'public' => TRUE ) )
	{
		$list = array();

		foreach ( get_post_types( $args, 'objects' ) as $posttype => $posttype_obj ) {

			// label
			if ( 0 === $mod )
				$list[$posttype] = $posttype_obj->label ? $posttype_obj->label : $posttype_obj->name;

			// plural
			else if ( 1 === $mod )
				$list[$posttype] = $posttype_obj->labels->name;

			// singular
			else if ( 2 === $mod )
				$list[$posttype] = $posttype_obj->labels->singular_name;

			// nooped
			else if ( 3 === $mod )
				$list[$posttype] = array(
					0          => $posttype_obj->labels->singular_name,
					1          => $posttype_obj->labels->name,
					'singular' => $posttype_obj->labels->singular_name,
					'plural'   => $posttype_obj->labels->name,
					'context'  => NULL,
					'domain'   => NULL,
				);

			// object
			else if ( 4 === $mod )
				$list[$posttype] = $posttype_obj;
		}

		return $list;
	}

	// * 'publish' - a published post or page
	// * 'pending' - post is pending review
	// * 'draft' - a post in draft status
	// * 'auto-draft' - a newly created post, with no content
	// * 'future' - a post to publish in the future
	// * 'private' - not visible to users who are not logged in
	// * 'inherit' - a revision. see get_children.
	// * 'trash' - post is in trashbin. added with Version 2.9.
	public static function getStatuses()
	{
		global $wp_post_statuses;

		$statuses = array();

		foreach ( $wp_post_statuses as $status )
			$statuses[$status->name] = $status->label;

		return $statuses;
	}

	public static function getIDsByTitle( $title, $atts = [] )
	{
		$args = array_merge( [
			'title'                  => $title,
			'fields'                 => 'ids',
			'post_type'              => 'any',
			'post_status'            => 'any',
			'posts_per_page'         => -1,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		], $atts );

		$query = new \WP_Query;

		return (array) $query->query( $args );
	}

	public static function getIDbySlug( $slug, $posttype, $url = FALSE )
	{
		static $strings = array();

		if ( $url ) {
			$slug = rawurlencode( urldecode( $slug ) );
			$slug = sanitize_title( basename( $slug ) );
		}

		$slug = trim( $slug );

		if ( isset( $strings[$posttype][$slug] ) )
			return $strings[$posttype][$slug];

		global $wpdb;

		$post_id = $wpdb->get_var(
			$wpdb->prepare( "
				SELECT ID
				FROM {$wpdb->posts}
				WHERE post_name = %s
				AND post_type = %s
			", $slug, $posttype )
		);

		if ( is_array( $post_id ) )
			return $strings[$posttype][$slug] = $post_id[0];

		else if ( ! empty( $post_id ) )
			return $post_id;

		return $strings[$posttype][$slug] = FALSE;
	}

	public static function getLastRevisionID( $post )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare( "
				SELECT ID
				FROM {$wpdb->posts}
				WHERE post_parent = %s
				AND post_type = 'revision'
				AND post_status = 'inherit'
				ORDER BY post_date DESC
			", $post->ID )
		);
	}

	// like WP core but returns the actual array!
	// @REF: `post_type_supports()`
	public static function supports( $posttype, $feature )
	{
		$all = get_all_post_type_supports( $posttype );

		if ( isset( $all[$feature][0] ) && is_array( $all[$feature][0] ) )
			return $all[$feature][0];

		return array();
	}

	// must add `add_thickbox()` for thickbox
	public static function htmlFeaturedImage( $post_id, $size = 'thumbnail', $link = TRUE )
	{
		if ( ! $post_thumbnail_id = get_post_thumbnail_id( $post_id ) )
			return '';

		if ( ! $post_thumbnail_img = wp_get_attachment_image_src( $post_thumbnail_id, $size ) )
			return '';

		$image = HTML::tag( 'img', array(
			'src'   => $post_thumbnail_img[0],
			'class' => '-featured',
			'alt'   => '',
			'data'  => array(
				'post'       => $post_id,
				'attachment' => $post_thumbnail_id,
			),
		) );

		if ( ! $link )
			return $image;

		return HTML::tag( 'a', array(
			'href'   => wp_get_attachment_url( $post_thumbnail_id ),
			'title'  => get_the_title( $post_thumbnail_id ),
			'class'  => 'thickbox',
			'target' => '_blank',
			'data'   => array(
				'post'       => $post_id,
				'attachment' => $post_thumbnail_id,
			),
		), $image );
	}
}
