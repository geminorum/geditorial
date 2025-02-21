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
		if ( FALSE === $post )
			return $post;

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
	 * Retrieves the user capability for a given post.
	 * NOTE: caches the result
	 *
	 * @param  int|object      $post
	 * @param  null|string     $capability
	 * @param  null|int|object $user_id
	 * @param  bool            $fallback
	 * @return bool            $can
	 */
	public static function can( $post, $capability, $user_id = NULL, $fallback = FALSE )
	{
		static $cache = [];

		if ( is_null( $capability ) )
			return TRUE;

		else if ( ! $capability )
			return $fallback;

		if ( ! $post = self::get( $post ) )
			return $fallback;

		// handling dummy posts!
		if ( '-9999' == $post->ID )
			return $fallback;

		/**
		 * The post-type is not registered, so it may not be reliable
		 * to check the capability against an unregistered post-type.
		 */
		if ( ! PostType::exists( $post ) )
			return $fallback;

		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		else if ( is_object( $user_id ) )
			$user_id = $user_id->ID;

		if ( ! $user_id )
			return user_can( $user_id, $capability, $post->ID );

		if ( isset( $cache[$user_id][$post->ID][$capability] ) )
			return $cache[$user_id][$post->ID][$capability];

		$can = user_can( $user_id, $capability, $post->ID );

		return $cache[$user_id][$post->ID][$capability] = $can;
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
	public static function title( $post = NULL, $fallback = NULL, $filter = TRUE )
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
	 * @param  null|int|object   $post
	 * @param  null|string       $fallback
	 * @param  null|string|array $statuses
	 * @return false|string      $link
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
	 * Retrieves the URL for editing a given post.
	 * @ref `get_edit_post_link()`
	 * @old `WordPress::getPostEditLink()`
	 *
	 * @param  int|object $post
	 * @param  array      $extra
	 * @param  mixed      $fallback
	 * @return string     $link
	 */
	public static function edit( $post, $extra = [], $fallback = FALSE )
	{
		if ( ! $post = self::get( $post ) )
			return $fallback;

		if ( ! self::can( $post, 'edit_post' ) )
			return $fallback;

		$link = add_query_arg( array_merge( [
			'action' => 'edit',
			'post'   => $post->ID,
		], $extra ), admin_url( 'post.php' ) );

		return apply_filters( 'get_edit_post_link', $link, $post->ID, 'display' );
	}

	// public static function shortlink( $post ) {}

	/**
	 * Retrieves a contextual link given a post ID or post object.
	 *
	 * @param  null|int|object $post
	 * @param  null|string     $context
	 * @return false|string    $link
	 */
	public static function overview( $post, $context = NULL )
	{
		if ( ! $post = self::get( $post ) )
			return FALSE;

		$filtered = apply_filters( 'geditorial_post_overview_pre_link', NULL, $post, $context );

		if ( ! is_null( $filtered ) )
			return $filtered;

		if ( is_admin() && ( $edit = self::edit( $post ) ) )
			return $edit;

		if ( PostType::viewable( $post->post_type ) )
			return self::link( $post, FALSE );

		return FALSE;
	}

	/**
	 * Retrieves a contextual summary given a post ID or post object.
	 *
	 * @param  null|int|object $post
	 * @param  null|string     $context
	 * @return false|array     $summary
	 */
	public static function summary( $post, $context = NULL )
	{
		if ( ! $post = self::get( $post ) )
			return FALSE;

		$posttype  = PostType::object( $post );
		$timestamp = get_post_timestamp( $post );

		return [
			'_id'         => $post->ID,
			'_type'       => $post->post_type,
			'_rest'       => PostType::getRestRoute( $posttype ),
			'_base'       => $posttype->rest_base,
			'type'        => $posttype->label,
			'viewable'    => PostType::viewable( $post->post_type ),
			'author'      => User::getTitleRow( $post->post_author ),
			'title'       => self::fullTitle( $post ),
			'link'        => self::overview( $post, $context ),
			'date'        => wp_date( get_option( 'date_format' ), $timestamp ),
			'time'        => wp_date( get_option( 'time_format' ), $timestamp ),
			'ago'         => $timestamp ? human_time_diff( $timestamp ) : FALSE,
			'image'       => self::image( $post, $context ),
			'description' => wpautop( apply_filters( 'html_format_i18n', $post->post_excerpt ) ),
		];
	}

	/**
	 * Retrieves a post given its title.
	 *
	 * @see `get_page_by_title()`
	 * @source https://make.wordpress.org/core/2023/03/06/get_page_by_title-deprecated/
	 *
	 * @param  string       $title
	 * @param  string|array $posttype
	 * @param  string|array $status
	 * @return array        $posts
	 */
	public static function getByTitle( $title, $posttype = 'any', $fields = 'ids', $status = 'all' )
	{
		if ( ! $title = trim( $title ) )
			return [];

		$args = [
			'title'          => $title,
			'fields'         => $fields,
			'post_type'      => $posttype,
			'post_status'    => $status,
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
	 * Updates the posttype for the given post.
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

		return sprintf( '/%s/%s/%d',
			$object->rest_namespace,
			$object->rest_base ?: $object->name,
			$post->ID
		);
	}

	/**
	 * Retrieves post full title given a post ID or post object.
	 *
	 * @param  null|int|object $post
	 * @param  bool|string     $linked
	 * @param  null|string     $separator
	 * @return string          $title
	 */
	public static function fullTitle( $post, $linked = FALSE, $separator = NULL )
	{
		if ( ! $post = self::get( $post ) )
			return '';

		$title = self::title( $post );

		if ( 'overview' === $linked )
			$title = Core\HTML::tag( 'a', [
				'href'  => self::overview( $post, 'overview' ),
				'class' => [ '-overview', 'do-colorbox-iframe' ],
			], $title );

		else if ( 'edit' === $linked )
			$title = Core\HTML::link( $title, get_edit_post_link( $post, 'edit' ) );

		else if ( $linked )
			$title = Core\HTML::link( $title, self::link( $post ) );

		return self::getParentTitles( $post, $title, $linked, $separator );
	}

	/**
	 * Retrieves post parent titles given a post ID or post object.
	 * NOTE: parent post type can be diffrenet
	 *
	 * @param  null|int|object $post
	 * @param  string          $suffix
	 * @param  string|bool     $linked
	 * @param  null|string     $separator
	 * @return string          $titles
	 */
	public static function getParentTitles( $post, $suffix = '', $linked = FALSE, $separator = NULL )
	{
		if ( ! $post = self::get( $post ) )
			return $suffix;

		if ( ! $post->post_parent )
			return $suffix;

		if ( is_null( $separator ) )
			$separator = Core\HTML::rtl() ? ' &rsaquo; ' : ' &lsaquo; ';

		$current = $post->ID;
		$parents = [];
		$parent  = TRUE;

		while ( $parent ) {

			$object = self::get( (int) $current );
			$link   = 'edit' === $linked ? get_edit_post_link( $object, 'edit' ) : self::link( $object );

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

	/**
	 * Retrieves meta-data for a given post.
	 *
	 * @param  object|int $post
	 * @param  bool|array $keys `false` for all meta
	 * @param  bool $single
	 * @return array $metadata
	 */
	public static function getMeta( $post, $keys = FALSE, $single = TRUE )
	{
		if ( ! $post = self::get( $post ) )
			return FALSE;

		$list = [];

		if ( FALSE === $keys ) {

			if ( $single ) {

				foreach ( (array) get_metadata( 'post', $post->ID ) as $key => $meta )
					$list[$key] = maybe_unserialize( $meta[0] );

			} else {

				foreach ( (array) get_metadata( 'post', $post->ID ) as $key => $meta )
					foreach ( $meta as $offset => $value )
						$list[$key][$offset] = maybe_unserialize( $value );
			}

		} else {

			foreach ( $keys as $key => $default )
				$list[$key] = get_metadata( 'post', $post->ID, $key, $single ) ?: $default;
		}

		return $list;
	}

	/**
	 * Updates the parnt for the given post.
	 * NOTE: directly updates db to avoid `wp_update_post()`
	 *
	 * @param  int  $post_id
	 * @param  int  $parent_id
	 * @param  bool $checks
	 * @return bool $updated
	 */
	public static function setParent( $post_id, $parent_id, $checks = TRUE )
	{
		global $wpdb;

		if ( $checks ) {

			if ( ! $post = self::get( $post_id ) )
				return FALSE;

			if ( ! $parent = self::get( $parent_id ) )
				return FALSE;

			$post_id   = $post->ID;
			$parent_id = $parent->ID;
		}

		if ( ! $wpdb->update( $wpdb->posts, [ 'post_parent' => $parent_id ], [ 'ID' => $post_id ] ) )
			return FALSE;

		clean_post_cache( $post_id );

		return TRUE;
	}

	public static function image( $post, $context = NULL, $size = NULL, $thumbnail_id = NULL )
	{
		if ( ! $post = self::get( $post ) )
			return FALSE;

		$filtered = apply_filters( 'geditorial_post_image_pre_src', NULL, $post, $context, $size, $thumbnail_id );

		if ( ! is_null( $filtered ) )
			return $filtered;

		if ( is_null( $thumbnail_id ) )
			$thumbnail_id = PostType::getThumbnailID( $post->ID );

		if ( ! $thumbnail_id )
			return FALSE;

		if ( is_null( $size ) )
			$size = Media::getAttachmentImageDefaultSize( $post->post_type );

		if ( ! $image = image_downsize( $thumbnail_id, $size ) )
			return FALSE;

		if ( isset( $image[0] ) )
			return $image[0];

		return FALSE;
	}

	/**
	 * Returns default post information to use when populating the “Write Post” form.
	 * @source `get_default_post_to_edit()`
	 *
	 * @param  string $posttype
	 * @return object $post
	 */
	public static function defaultToEdit( $posttype )
	{
		$post                 = new \stdClass();
		$post->ID             = 0;
		$post->post_author    = '';
		$post->post_date      = '';
		$post->post_date_gmt  = '';
		$post->post_password  = '';
		$post->post_name      = '';
		$post->post_type      = $posttype;
		$post->post_status    = 'draft';
		$post->to_ping        = '';
		$post->pinged         = '';
		$post->comment_status = get_default_comment_status( $posttype );
		$post->ping_status    = get_default_comment_status( $posttype, 'pingback' );
		$post->post_pingback  = get_option( 'default_pingback_flag' );
		$post->post_category  = 0; // get_option( 'default_category' );
		$post->page_template  = 'default';
		$post->post_parent    = 0;
		$post->menu_order     = 0;
		$post                 = new \WP_Post( $post );

		$post->post_title   = (string) apply_filters( 'default_title', esc_html( self::unslash( self::req( 'post_title' ) ) ), $post );
		$post->post_content = (string) apply_filters( 'default_content', esc_html( self::unslash( self::req( 'content' ) ) ), $post );
		$post->post_excerpt = (string) apply_filters( 'default_excerpt', esc_html( self::unslash( self::req( 'excerpt' ) ) ), $post );

		return $post;
	}
}
