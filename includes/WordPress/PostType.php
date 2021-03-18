<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Core\HTML;

class PostType extends Core\Base
{

	public static function object( $posttype )
	{
		return is_object( $posttype ) ? $posttype : get_post_type_object( $posttype );
	}

	public static function can( $posttype, $capability = 'edit_posts', $user_id = NULL )
	{
		if ( is_null( $capability ) )
			return TRUE;

		$cap = self::object( $posttype )->cap->{$capability};

		return is_null( $user_id )
			? current_user_can( $cap )
			: user_can( $user_id, $cap );
	}

	public static function get( $mod = 0, $args = array( 'public' => TRUE ), $capability = NULL, $user_id = NULL )
	{
		$list = array();

		foreach ( get_post_types( $args, 'objects' ) as $posttype => $posttype_obj ) {

			if ( ! self::can( $posttype_obj, $capability, $user_id ) )
				continue;

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

	// @SEE: https://tommcfarlin.com/get-post-id-by-meta-value/
	public static function getIDbyMeta( $meta, $value )
	{
		static $results = [];

		if ( isset( $results[$meta][$value] ) )
			return $results[$meta][$value];

		global $wpdb;

		$post_id = $wpdb->get_var(
			$wpdb->prepare( "
				SELECT post_id
				FROM {$wpdb->postmeta}
				WHERE meta_key = %s
				AND meta_value = %s
			", $meta, $value )
		);

		return $results[$meta][$value] = $post_id;
	}

	// `WP_Query` does not support `id=>name` as fields
	public static function getIDs( $posttype = 'post', $extra = [], $fields = NULL )
	{
		$args = array_merge( [
			'fields'         => is_null( $fields ) ? 'ids' : $fields, // OR: `id=>parent`
			'post_type'      => $posttype,
			'post_status'    => [ 'publish', 'future', 'draft', 'pending' ],
			'posts_per_page' => -1,

			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		], $extra );

		$query = new \WP_Query;

		return (array) $query->query( $args );
	}

	public static function getIDsBySearch( $string, $atts = [] )
	{
		$args = array_merge( [
			's'              => $string,
			'fields'         => 'ids',
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => -1,

			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		], $atts );

		$query = new \WP_Query;

		return (array) $query->query( $args );
	}

	public static function getIDsByTitle( $title, $atts = [] )
	{
		$args = array_merge( [
			'title'          => $title,
			'fields'         => 'ids',
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => -1,

			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		], $atts );

		$query = new \WP_Query;

		return (array) $query->query( $args );
	}

	public static function getIDbySlug( $slug, $posttype = 'post', $url = FALSE )
	{
		static $cache = [];

		if ( $url ) {
			$slug = rawurlencode( urldecode( $slug ) );
			$slug = sanitize_title( basename( $slug ) );
		}

		$slug = trim( $slug );

		if ( isset( $cache[$posttype] ) && array_key_exists( $slug, $cache[$posttype] ) )
			return $cache[$posttype][$slug];

		global $wpdb;

		$id = $wpdb->get_var( $wpdb->prepare( "
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_name = %s
			AND post_type = %s
		", $slug, $posttype ) );

		return $cache[$posttype][$slug] = $id;
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

	// TODO: use db query
	public static function getLastMenuOrder( $posttype = 'post', $exclude = '', $key = 'menu_order', $status = [ 'publish', 'future', 'draft' ] )
	{
		$post = get_posts( [
			'posts_per_page' => 1,
			'orderby'        => 'menu_order',
			'exclude'        => $exclude,
			'post_type'      => $posttype,
			'post_status'    => $status,

			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		] );

		if ( empty( $post ) )
			return 0;

		if ( 'menu_order' == $key )
			return (int) $post[0]->menu_order;

		return $post[0]->{$key};
	}

	// TODO: use db query
	public static function getRandomPostID( $posttype, $has_thumbnail = FALSE, $object = FALSE, $status = 'publish' )
	{
		$args = array(
			'post_type'      => $posttype,
			'post_status'    => $status,
			'posts_per_page' => 1,
			'orderby'        => 'rand',

			'ignore_sticky_posts'    => TRUE,
			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		);

		if ( ! $object )
			$args['fields'] = 'ids';

		if ( $has_thumbnail )
			$args['meta_query'] = array( array(
				'key'     => '_thumbnail_id',
				'compare' => 'EXISTS'
			) );

		$query = new \WP_Query;
		$posts = $query->query( $args );

		return empty( $posts ) ? FALSE : $posts[0];
	}

	public static function getParentPostID( $post_id = NULL, $object = FALSE )
	{
		if ( ! $post = get_post( $post_id ) )
			return FALSE;

		if ( empty( $post->post_parent ) )
			return FALSE;

		if ( $object )
			return get_post( $post->post_parent );

		return (int) $post->post_parent;
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
	// @SEE: `Scripts::enqueueThickBox()`
	public static function htmlFeaturedImage( $post_id, $size = 'thumbnail', $link = TRUE )
	{
		if ( ! $post_thumbnail_id = get_post_thumbnail_id( $post_id ) )
			return '';

		if ( ! $post_thumbnail_img = wp_get_attachment_image_src( $post_thumbnail_id, $size ) )
			return '';

		$image = HTML::tag( 'img', array(
			'src'     => $post_thumbnail_img[0],
			'alt'     => '',
			'class'   => '-featured',
			'loading' => 'lazy',
			'data'    => array(
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

	public static function supportBlocksByPost( $post )
	{
		if ( ! function_exists( 'use_block_editor_for_post' ) )
			return FALSE;

		return use_block_editor_for_post( $post );
	}

	public static function supportBlocks( $posttype )
	{
		if ( ! function_exists( 'use_block_editor_for_post_type' ) )
			return FALSE;

		return use_block_editor_for_post_type( $posttype );
	}

	public static function newPostFromTerm( $term, $taxonomy = 'category', $posttype = 'post', $user_id = 0 )
	{
		if ( ! is_object( $term ) && ! is_array( $term ) )
			$term = get_term( $term, $taxonomy );

		$new_post = array(
			'post_title'   => $term->name,
			'post_name'    => $term->slug,
			'post_content' => $term->description,
			'post_status'  => 'pending',
			'post_author'  => $user_id ? $user_id : get_current_user_id(),
			'post_type'    => $posttype,
		);

		return wp_insert_post( $new_post );
	}

	public static function current( $default = NULL )
	{
		global $post, $typenow, $pagenow, $current_screen;

		if ( $post && $post->post_type )
			return $post->post_type;

		if ( $typenow )
			return $typenow;

		if ( $current_screen && isset( $current_screen->post_type ) )
			return $current_screen->post_type;

		if ( isset( $_REQUEST['post_type'] ) )
			return sanitize_key( $_REQUEST['post_type'] );

		return $default;
	}
}
