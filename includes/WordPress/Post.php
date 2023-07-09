<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class Post extends Core\Base
{

	/**
	 * Retrieves post data given a post ID or post object.
	 *
	 * simplified `get_post()`
	 * @old `PostType::getPost()`
	 *
	 * @param  null|int|object $post
	 * @param  string $output
	 * @param  string $filter
	 * @return object $post
	 */
	public static function get( $post = NULL, $output = OBJECT, $filter = 'raw' )
	{
		if ( $post instanceof \WP_Post )
			return $post;

		// handling dummy posts!
		if ( '-9999' == $post )
			$post = NULL;

		if ( $_post = get_post( $post, $output, $filter ) )
			return $_post;

		if ( is_null( $post ) && is_admin() && ( $query = self::req( 'post' ) ) )
			return get_post( $query, $output, $filter );

		return NULL;
	}

	/**
	 * Retrieves post type given a post ID or post object.
	 *
	 * @source `get_post_type()`
	 *
	 * @param  null|int|object $post
	 * @return string $posttype
	 */
	public static function type( $post = NULL )
	{
		if ( $post = self::get( $post ) )
			return $post->post_type;

		return FALSE;
	}

	/**
	 * Determines whether a post is publicly viewable.
	 *
	 * @source `is_post_publicly_viewable()` @since WP5.7.0
	 *
	 * @param  int|WP_Post|null $post
	 * @return bool $viewable
	 */
	public static function viewable( $post = NULL )
	{
		if ( ! $post = self::get( $post ) )
			return FALSE;

		return PostType::viewable( $post->post_type )
			&& Status::viewable( get_post_status( $post ) );
	}

	/**
	 * Retrieves post title given a post ID or post object.
	 *
	 * @old `PostType::getPostTitle()`
	 *
	 * @param  null|int|object $post
	 * @param  null|string $fallback
	 * @param  bool   $filter
	 * @return string $title
	 */
	public static function title( $post, $fallback = NULL, $filter = TRUE )
	{
		if ( ! $post = self::get( $post ) )
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

	/**
	 * Retrieves post link given a post ID or post object.
	 *
	 * @old `PostType::getPostLink()`
	 *
	 * @param  null|int|object $post
	 * @param  null|string $fallback
	 * @param  null|string|array $statuses
	 * @return string $link
	 */
	public static function link( $post, $fallback = NULL, $statuses = NULL )
	{
		if ( ! $post = self::get( $post ) )
			return FALSE;

		$status = get_post_status( $post );

		if ( is_null( $statuses ) && ! Status::viewable( $status ) )
			return $fallback;

		if ( $statuses && ! in_array( $status, (array) $statuses, TRUE ) )
			return $fallback;

		return apply_filters( 'the_permalink', get_permalink( $post ), $post );
	}

	/**
	 * Retrieves a post given its title.
	 *
	 * @see `get_page_by_title()`
	 * @source https://make.wordpress.org/core/2023/03/06/get_page_by_title-deprecated/
	 *
	 * @param  string $title
	 * @param  string|array $posttype
	 * @return array $posts
	 */
	public static function getByTitle( $title, $posttype = 'any', $fields = 'ids' )
	{
		if ( ! $title = trim( $title ) )
			return [];

		$args = [
			'title'          => $title,
			'fields'         => $fields,
			'post_type'      => $posttype,
			'post_status'    => 'all',
			'orderby'        => 'post_date ID',
			'order'          => 'ASC',
			'posts_per_page' => -1,

			'no_found_rows'          => TRUE,
			'ignore_sticky_posts'    => TRUE,
			'update_post_term_cache' => FALSE,
			'update_post_meta_cache' => FALSE,
		];

		$query = new \WP_Query();
		return $query->query( $args );
	}

	/**
	 * Updates the posttype for the post.
	 *
	 * also accepts post and posttype objects
	 * and checks if its a different posttype
	 *
	 * @source `set_post_type()`
	 *
	 * @param  int|object $post
	 * @param  string|object $posttype
	 * @return bool $success
	 */
	public static function setPostType( $post, $posttype )
	{
		global $wpdb;

		if ( ! $posttype = PostType::object( $posttype ) )
			return FALSE;

		if ( ! $post = self::get( $post ) )
			return FALSE;

		if ( $posttype->name === $post->post_type )
			return TRUE;

		$success = $wpdb->update( $wpdb->posts,
			[ 'post_type' => sanitize_post_field( 'post_type', $posttype->name, $post->ID, 'db' ) ],
			[ 'ID'        => $post->ID ]
		);

		clean_post_cache( $post );

		return $success;
	}

	/**
	 * Retrieves a post object default properties.
	 *
	 * @return array $props
	 */
	public static function props()
	{
		return [
			'ID'                    => NULL,
			'post_author'           => 0,
			'post_date'             => '0000-00-00 00:00:00',
			'post_date_gmt'         => '0000-00-00 00:00:00',
			'post_content'          => '',
			'post_title'            => '',
			'post_excerpt'          => '',
			'post_status'           => 'publish',
			'comment_status'        => 'open',
			'ping_status'           => 'open',
			'post_password'         => '',
			'post_name'             => '',
			'to_ping'               => '',
			'pinged'                => '',
			'post_modified'         => '0000-00-00 00:00:00',
			'post_modified_gmt'     => '0000-00-00 00:00:00',
			'post_content_filtered' => '',
			'post_parent'           => 0,
			'guid'                  => '',
			'menu_order'            => 0,
			'post_type'             => 'post',
			'post_mime_type'        => '',
			'comment_count'         => 0,
		];
	}

	/**
	 * Retrieves post rest route given a post ID or post object.
	 *
	 * @param  null|int|object $post
	 * @return false|string $route
	 */
	public static function getRestRoute( $post = NULL )
	{
		if ( ! $post = self::get( $post ) )
			return FALSE;

		if ( ! $object = PostType::object( $post ) )
			return FALSE;

		if ( ! $object->show_in_rest )
			return FALSE;

		return sprintf( '/%s/%s/%d', $object->rest_namespace, $object->rest_base, $post->ID );
	}

	/**
	 * Retrieves post full title given a post ID or post object.
	 *
	 * @param  null|int|object $post
	 * @param  bool   $linked
	 * @param  null|string $separator
	 * @return string $title
	 */
	public static function fullTitle( $post, $linked = FALSE, $separator = NULL )
	{
		if ( ! $post = self::get( $post ) )
			return '';

		$title = self::title( $post );

		if ( $linked )
			$title = Core\HTML::link( $title, self::link( $post ) );

		return self::getParentTitles( $post, $title, $linked, $separator );
	}

	/**
	 * Retrieves post parent titles given a post ID or post object.
	 * NOTE: parent post type can be diffrenet
	 *
	 * @param  null|int|object $post
	 * @param  string $suffix
	 * @param  bool   $linked
	 * @param  null|string $separator
	 * @return string $titles
	 */
	public static function getParentTitles( $post, $suffix = '', $linked = FALSE, $separator = NULL )
	{
		if ( ! $post = self::get( $post ) )
			return $suffix;

		if ( is_null( $separator ) )
			$separator = Core\HTML::rtl() ? ' &rsaquo; ' : ' &lsaquo; ';

		$current = $post->ID;
		$parents = [];
		$parent  = TRUE;

		while ( $parent ) {

			$object = self::get( (int) $current );
			$link   = self::link( $object );

			if ( $object && $object->post_parent )
				$parents[] = $linked && $link
					? Core\HTML::link( self::title( $object->post_parent ), $link )
					: self::title( $object->post_parent );

			else
				$parent = FALSE;

			if ( $object )
				$current = $object->post_parent;
		}

		if ( empty( $parents ) )
			return $suffix;

		return Strings::getJoined( array_reverse( $parents ), '', $suffix ? $separator.$suffix : '', '', $separator );
	}
}
