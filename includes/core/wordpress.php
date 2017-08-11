<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class WordPress extends Base
{

	public static function mustRegisterUI( $check_admin = TRUE )
	{
		if ( self::isAJAX()
			|| self::isCLI()
			|| self::isCRON()
			|| self::isXMLRPC()
			|| self::isREST()
			|| self::isIFrame() )
				return FALSE;

		if ( $check_admin && ! is_admin() )
			return FALSE;

		return TRUE;
	}

	public static function isDebug()
	{
		if ( WP_DEBUG && WP_DEBUG_DISPLAY && ! self::isDev() )
			return TRUE;

		return FALSE;
	}

	public static function isDev()
	{
		if ( defined( 'WP_STAGE' )
			&& 'development' == constant( 'WP_STAGE' ) )
				return TRUE;

		return FALSE;
	}

	public static function isFlush()
	{
		if ( isset( $_GET['flush'] ) )
			return did_action( 'init' ) && current_user_can( 'publish_posts' );

		return FALSE;
	}

	// @SEE: `wp_doing_ajax()` since 4.7.0
	public static function isAJAX()
	{
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	// @SEE: `wp_doing_cron()` since 4.8.0
	public static function isCRON()
	{
		return defined( 'DOING_CRON' ) && DOING_CRON;
	}

	public static function isCLI()
	{
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	public static function isXMLRPC()
	{
		return defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST;
	}

	public static function isREST()
	{
		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	public static function isIFrame()
	{
		return defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST;
	}

	public static function doNotCache()
	{
		defined( 'DONOTCACHEPAGE' ) or define( 'DONOTCACHEPAGE', TRUE );
	}

	// TODO: use db query
	public static function getLastPostOrder( $post_type = 'post', $exclude = '', $key = 'menu_order', $status = array( 'publish', 'future', 'draft' ) )
	{
		$post = get_posts( array(
			'posts_per_page' => 1,
			'orderby'        => 'menu_order',
			'exclude'        => $exclude,
			'post_type'      => $post_type,
			'post_status'    => $status,
		) );

		if ( ! count( $post ) )
			return 0;

		if ( 'menu_order' == $key )
			return intval( $post[0]->menu_order );

		return $post[0]->{$key};
	}

	// TODO: use db query
	public static function getRandomPostID( $post_type, $has_thumbnail = FALSE, $object = FALSE, $status = 'publish' )
	{
		$args = array(
			'post_type'              => $post_type,
			'post_status'            => $status,
			'posts_per_page'         => 1,
			'orderby'                => 'rand',
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

		if ( ! count( $posts ) )
			return FALSE;

		return $posts[0];
	}

	public static function getParentPostID( $post_id = NULL, $object = FALSE )
	{
		if ( ! $post = get_post( $post_id ) )
			return FALSE;

		if ( empty( $post->post_parent ) )
			return FALSE;

		if ( $object )
			return get_post( $post->post_parent );

		return intval( $post->post_parent );
	}

	public static function getFeaturedImage( $post_id, $size = 'thumbnail', $default = FALSE )
	{
		if ( ! $post_thumbnail_id = get_post_thumbnail_id( $post_id ) )
			return $default;

		$post_thumbnail_img = wp_get_attachment_image_src( $post_thumbnail_id, $size );
		return $post_thumbnail_img[0];
	}

	public static function newPostFromTerm( $term, $taxonomy = 'category', $post_type = 'post', $user_id = 0 )
	{
		if ( ! is_object( $term ) && ! is_array( $term ) )
			$term = get_term( $term, $taxonomy );

		$new_post = array(
			'post_title'   => $term->name,
			'post_name'    => $term->slug,
			'post_content' => $term->description,
			'post_status'  => 'draft',
			'post_author'  => $user_id ? $user_id : get_current_user_id(),
			'post_type'    => $post_type,
		);

		return wp_insert_post( $new_post );
	}

	public static function currentPostType( $default = NULL )
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

	public static function redirect( $location = NULL, $status = 302 )
	{
		if ( is_null( $location ) )
			$location = add_query_arg( wp_get_referer() );

		wp_redirect( $location, $status );
		exit();
	}

	public static function redirectReferer( $message = 'updated', $key = 'message' )
	{
		if ( is_array( $message ) )
			$url = add_query_arg( $message, wp_get_referer() );
		else
			$url = add_query_arg( $key, $message, wp_get_referer() );

		self::redirect( $url );
	}

	public static function redirectLogin( $location = '', $status = 302 )
	{
		self::redirect( wp_login_url( $location, TRUE ), $status );
	}

	public static function getAdminPostLink( $action, $extra = array() )
	{
		return add_query_arg( array_merge( array(
			'action' => $action,
		), $extra ), admin_url( 'admin-post.php' ) );
	}

	public static function getAdminPageLink( $page, $extra = array(), $base = 'admin.php' )
	{
		return add_query_arg( array_merge( array(
			'page' => $page,
		), $extra ), admin_url( $base ) );
	}

	// @SEE: get_search_link()
	public static function getSearchLink( $query = FALSE )
	{
		if ( defined( 'GNETWORK_SEARCH_REDIRECT' ) && GNETWORK_SEARCH_REDIRECT )
			return $query ? add_query_arg( GNETWORK_SEARCH_QUERYID, urlencode( $query ), GNETWORK_SEARCH_URL ) : GNETWORK_SEARCH_URL;

		return $query ? add_query_arg( 's', urlencode( $query ), get_option( 'home' ) ) : get_option( 'home' );
	}

	// @REF: get_edit_term_link()
	public static function getEditTaxLink( $taxonomy, $term_id = FALSE, $extra = array() )
	{
		if ( $term_id ) {

			if ( current_user_can( 'edit_term', $term_id ) )
				return add_query_arg( array_merge( array(
					'taxonomy' => $taxonomy,
					'tag_ID'   => $term_id,
				), $extra ), admin_url( 'term.php' ) );

		} else {

			$object = get_taxonomy( $taxonomy );

			if ( current_user_can( $object->cap->manage_terms ) )
				return add_query_arg( array_merge( array(
					'taxonomy' => $taxonomy,
				), $extra ), admin_url( 'edit-tags.php' ) );
		}

		return FALSE;
	}

	public static function getPostTypeEditLink( $post_type, $user_id = 0, $extra = array() )
	{
		$query = array( 'post_type' => $post_type );

		if ( $user_id )
			$query['author'] = $user_id;

		return add_query_arg( array_merge( $query, $extra ), admin_url( 'edit.php' ) );
	}

	// @SEE: `get_edit_post_link()`
	public static function getPostEditLink( $post_id, $extra = array() )
	{
		return add_query_arg( array_merge( array(
			'action' => 'edit',
			'post'   => $post_id,
		), $extra ), admin_url( 'post.php' ) );
	}

	public static function getPostShortLink( $post_id, $extra = array() )
	{
		return add_query_arg( array_merge( array(
			'p' => $post_id,
		), $extra ), get_bloginfo( 'url' ) );
	}

	public static function getPostNewLink( $post_type, $extra = array() )
	{
		return add_query_arg( array_merge( array(
			'post_type' => $post_type,
		), $extra ), admin_url( 'post-new.php' ) );
	}

	public static function getPostAttachmentsLink( $post_id, $extra = array() )
	{
		return add_query_arg( array_merge( array(
			'post_parent' => $post_id,
		), $extra ), admin_url( 'upload.php' ) );
	}

	public static function getAuthorEditHTML( $post_type, $author, $extra = array() )
	{
		if ( $author_data = get_user_by( 'id', $author ) )
			return HTML::tag( 'a', array(
				'href' => add_query_arg( array_merge( array(
					'post_type' => $post_type,
					'author'    => $author,
				), $extra ), admin_url( 'edit.php' ) ),
				'title' => $author_data->user_login,
				'class' => '-author',
			), esc_html( $author_data->display_name ) );

		return FALSE;
	}

	public static function getUserEditLink( $user_id, $extra = array() )
	{
		return add_query_arg( array_merge( array(
			'user_id' => $user_id,
		), $extra ), admin_url( 'user-edit.php' ) );
	}

	// @SOURCE: `wp-load.php`
	public static function getConfigPHP( $path = ABSPATH )
	{
		// The config file resides in ABSPATH
		if ( file_exists( $path.'wp-config.php' ) )
			return $path.'wp-config.php';

		// The config file resides one level above ABSPATH but is not part of another install
		if ( @file_exists( dirname( $path ).'/wp-config.php' )
			&& ! @file_exists( dirname( $path ).'/wp-settings.php' ) )
				return dirname( $path ).'/wp-config.php';

		return FALSE;
	}

	public static function definedConfigPHP( $constant = 'WP_DEBUG' )
	{
		if ( ! $file = self::getConfigPHP() )
			return FALSE;

		$contents = file_get_contents( $file );
		$pattern = "define\( ?'".$constant."'";
		$pattern = "/^$pattern.*/m";

		if ( preg_match_all( $pattern, $contents, $matches ) )
			return TRUE;

		return FALSE;
	}

	// flush rewrite rules when it's necessary.
	// this could be put in an init hook or the like and ensures that
	// the rewrite rules option is only rewritten when the generated rules
	// don't match up with the option
	// @REF: https://gist.github.com/tott/9548734
	public static function maybeFlushRules( $flush = FALSE )
	{
		global $wp_rewrite;

		$list    = [];
		$missing = FALSE;

		foreach ( get_option( 'rewrite_rules' ) as $rule => $rewrite )
			$list[$rule]['rewrite'] = $rewrite;

		$list = array_reverse( $list, TRUE );

		foreach ( $wp_rewrite->rewrite_rules() as $rule => $rewrite ) {
			if ( ! array_key_exists( $rule, $list ) ) {
				$missing = TRUE;
				break;
			}
		}

		if ( $missing && $flush )
			flush_rewrite_rules();

		return $missing;
	}
}
