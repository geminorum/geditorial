<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Core\HTML;

class PostType extends Core\Base
{

	public static function object( $posttype_or_post )
	{
		if ( ! $posttype_or_post )
			return FALSE;

		if ( $posttype_or_post instanceof \WP_Post )
			return get_post_type_object( $posttype_or_post->post_type );

		if ( $posttype_or_post instanceof \WP_Post_Type )
			return $posttype_or_post;

		return get_post_type_object( $posttype_or_post );
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

	public static function get( $mod = 0, $args = [ 'public' => TRUE ], $capability = NULL, $user_id = NULL )
	{
		$list = [];

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
				$list[$posttype] = [
					0          => $posttype_obj->labels->singular_name,
					1          => $posttype_obj->labels->name,
					'singular' => $posttype_obj->labels->singular_name,
					'plural'   => $posttype_obj->labels->name,
					'context'  => NULL,
					'domain'   => NULL,
				];

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
	public static function getIDbyMeta( $meta, $value, $single = TRUE )
	{
		global $wpdb, $gEditorialIDbyMeta;

		if ( empty( $meta ) )
			return FALSE;

		if ( empty( $gEditorialIDbyMeta ) )
			$gEditorialIDbyMeta = [];

		if ( empty( $value ) )
			return FALSE;

		$group = $single ? 'single' : 'all';

		if ( isset( $gEditorialIDbyMeta[$meta][$group][$value] ) )
			return $gEditorialIDbyMeta[$meta][$group][$value];

		$query = $wpdb->prepare( "
			SELECT post_id
			FROM {$wpdb->postmeta}
			WHERE meta_key = %s
			AND meta_value = %s
		", $meta, $value );

		$results = $single
			? $wpdb->get_var( $query )
			: $wpdb->get_col( $query );

		return $gEditorialIDbyMeta[$meta][$group][$value] = $results;
	}

	public static function getIDListbyMeta( $meta, $values )
	{
		global $wpdb, $gEditorialIDbyMeta;

		if ( empty( $meta ) )
			return FALSE;

		$filtred = array_filter( (array) $values );

		if ( empty( $filtred ) )
			return FALSE;

		$query = $wpdb->prepare( "
			SELECT post_id, meta_value
			FROM {$wpdb->postmeta}
			WHERE meta_key = %s
			AND meta_value IN ( '".implode( "', '", esc_sql( $filtred ) )."' )
		", $meta );

		$results = $wpdb->get_results( $query, ARRAY_A );

		if ( empty( $results ) )
			return [];

		$list = wp_list_pluck( $results, 'post_id', 'meta_value' );

		if ( empty( $gEditorialIDbyMeta ) )
			$gEditorialIDbyMeta = [];

		// update cache
		foreach ( $filtred as $value )
			$gEditorialIDbyMeta[$meta]['single'][$value] = array_key_exists( $value, $list ) ? $list[$value] : FALSE;

		return $list;
	}


	public static function invalidateIDbyMeta( $meta, $value )
	{
		global $gEditorialIDbyMeta;

		if ( empty( $meta ) )
			return TRUE;

		if ( empty( $gEditorialIDbyMeta ) )
			return TRUE;

		if ( FALSE === $value ) {

			// clear all meta by key
			foreach ( (array) $meta as $key ) {
				unset( $gEditorialIDbyMeta[$key]['all'] );
				unset( $gEditorialIDbyMeta[$key]['single'] );
			}

		} else {

			foreach ( (array) $meta as $key ) {
				unset( $gEditorialIDbyMeta[$key]['all'][$value] );
				unset( $gEditorialIDbyMeta[$key]['single'][$value] );
			}
		}

		return TRUE;
	}

	// WTF: `WP_Query` does not support `id=>name` as fields
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

	// @REF: `wp_dashboard_recent_drafts()`
	public static function getRecent( $posttypes = [ 'post' ], $extra = [], $published = TRUE )
	{
		$args = array_merge( [
			'post_type'      => $posttypes,
			'post_status'    => $published ? 'publish' : [ 'publish', 'future', 'draft', 'pending' ],
			// 'posts_per_page' => 7, // will use default
			'orderby'        => 'modified',
			'order'          => 'DESC',

			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		], $extra );

		$query = new \WP_Query;

		return (array) $query->query( $args );
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

	public static function isThumbnail( $attachment_id, $metakey = '_thumbnail_id' )
	{
		if ( ! $attachment_id )
			return FALSE;

		$query = new \WP_Query( [
			'post_type'   => 'any',
			'post_status' => 'any',
			'orderby'     => 'none',
			'fields'      => 'ids',
			'meta_query'  => [ [
				'value'   => $attachment_id,
				'key'     => $metakey,
				'compare' => '=',
			] ],
			'suppress_filters' => TRUE,
			'posts_per_page'   => -1,
		] );

		return $query->have_posts() ? $query->posts : [];
	}

	// must add `add_thickbox()` for thickbox
	// @SEE: `Scripts::enqueueThickBox()`
	public static function htmlFeaturedImage( $post_id, $size = NULL, $link = TRUE, $metakey = NULL )
	{
		if ( is_null( $size ) )
			$size = Media::getAttachmentImageDefaultSize( get_post_type( $post_id ) );

		return Media::htmlAttachmentImage(
			self::getThumbnailID( $post_id, $metakey ),
			$size,
			$link,
			[ 'post' => $post_id ],
			'-featured'
		);
	}

	public static function getThumbnailID( $post_id, $metakey = NULL )
	{
		if ( is_null( $metakey ) )
			$thumbnail_id = get_post_thumbnail_id( $post_id ); // has filter @since WP 5.9.0

		else if ( $metakey )
			$thumbnail_id = (int) get_post_meta( $post_id, $metakey, TRUE );

		else
			$thumbnail_id = FALSE;

		return apply_filters( 'geditorial_get_post_thumbnail_id', $thumbnail_id, $post_id, $metakey );
	}

	public static function getArchiveLink( $posttype )
	{
		return apply_filters( 'geditorial_posttype_archive_link', get_post_type_archive_link( $posttype ), $posttype );
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

	public static function getEditLinkByTerm( $term_or_id, $posttype, $taxonomy = '' )
	{
		if ( ! self::object( $posttype )->show_ui )
			return FALSE;

		if ( ! $term = Taxonomy::getTerm( $term_or_id, $taxonomy ) )
			return FALSE;

		$object = Taxonomy::object( $term->taxonomy );

		$args = $object->query_var
			? [ $object->query_var => $term->slug ]
			: [ 'taxonomy' => $object->name, 'term' => $term->slug ];

		if ( 'post' !== $posttype )
			$args['post_type'] = $posttype;

		return add_query_arg( $args, ( 'attachment' === $posttype ? 'upload.php' : 'edit.php' ) );
	}

	// simplified `get_post()`
	public static function getPost( $post = NULL, $output = OBJECT, $filter = 'raw' )
	{
		if ( $post instanceof \WP_Post )
			return $post;

		// handling dummy posts!
		if ( '-9999' == $post )
			$post = NULL;

		return get_post( $post, $output, $filter );
	}

	public static function getPostLink( $post, $fallback = NULL, $statuses = NULL )
	{
		if ( ! $post = self::getPost( $post ) )
			return FALSE;

		$status = get_post_status( $post );

		if ( is_null( $statuses ) && ! is_post_status_viewable( $status ) )
			return $fallback;

		if ( $statuses && ! in_array( $status, (array) $statuses, TRUE ) )
			return $fallback;

		return apply_filters( 'the_permalink', get_permalink( $post ), $post );
	}

	public static function getPostTitle( $post, $fallback = NULL, $filter = TRUE )
	{
		if ( ! $post = self::getPost( $post ) )
			return '';

		$title = $filter ? apply_filters( 'the_title', $post->post_title, $post->ID ) : $post->post_title;

		if ( ! empty( $title ) )
			return $title;

		if ( FALSE === $fallback )
			return '';

		if ( is_null( $fallback ) )
			return __( '(Untitled)' );

		return $fallback;
	}
}
