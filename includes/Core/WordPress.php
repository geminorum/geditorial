<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class WordPress extends Base
{

	// @REF: https://wordpress.org/support/topic/how-to-change-plugins-load-order/
	// @USAGE: add_action( 'activated_plugin', function () {} );
	public static function pluginFirst( $plugin )
	{
		if ( empty( $plugin ) )
			return;

		// ensure path to this file is via main wp plugin path
		// $wp_path_to_this_file = preg_replace( '/(.*)plugins\/(.*)$/', WP_PLUGIN_DIR."/$2", __FILE__ );
		// $plugin = plugin_basename( trim( $wp_path_to_this_file ) );

		$active = get_option( 'active_plugins' );

		// if it's 0 it's the first plugin already, no need to continue
		if ( $key = array_search( $plugin, $active ) ) {
			array_splice( $active, $key, 1 );
			array_unshift( $active, $plugin );
			update_option( 'active_plugins', $active );
		}
	}

	// checks compatibility with the current WordPress version
	// @REF: `is_wp_version_compatible()`
	public static function isWPcompatible( $required )
	{
		return empty( $required ) || version_compare( get_bloginfo( 'version' ), $required, '>=' );
	}

	// checks compatibility with the current php version
	// @REF: `wp_is_php_compatible()`
	public static function isPHPcompatible( $required )
	{
		return empty( $required ) || version_compare( PHP_VERSION, $required, '>=' );
	}

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

	// @REF: `vars.php`
	public static function pageNow( $page = NULL )
	{
		$now = 'index.php';

		if ( preg_match( '#([^/]+\.php)([?/].*?)?$#i', $_SERVER['PHP_SELF'], $matches ) )
			$now = strtolower( $matches[1] );

		if ( is_null( $page ) )
			return $now;

		return in_array( $now, (array) $page );
	}

	// @SEE: `is_login()` @since WP 6.1.0
	// @REF: https://make.wordpress.org/core/2022/09/11/new-is_login-function-for-determining-if-a-page-is-the-login-screen/
	// @REF: https://core.trac.wordpress.org/ticket/19898
	public static function isLogin()
	{
		return Text::has( self::loginURL(), $_SERVER['SCRIPT_NAME'] );
	}

	// @REF: https://make.wordpress.org/core/2019/04/17/block-editor-detection-improvements-in-5-2/
	public static function isBlockEditor()
	{
		if ( ! function_exists( 'get_current_screen' ) )
			return FALSE;

		if ( ! $screen = get_current_screen() )
			return FALSE;

		if ( ! is_callable( [ $screen, 'is_block_editor' ] ) )
			return FALSE;

		return (bool) $screen->is_block_editor();
	}

	// including `WP_CACHE` with a value of `true` loads `advanced-cache.php`.
	// `Object-cache.php` is loaded and used automatically.
	public static function isAdvancedCache()
	{
		return defined( 'WP_CACHE' ) && WP_CACHE;
	}

	public static function isDebug()
	{
		if ( WP_DEBUG && WP_DEBUG_DISPLAY && ! self::isDev() )
			return TRUE;

		return FALSE;
	}

	// @REF: https://make.wordpress.org/core/2020/08/27/wordpress-environment-types/
	// @REF: https://make.wordpress.org/core/2023/07/14/configuring-development-mode-in-6-3/
	// NOTE: `wp_get_environment_type()` @since WP 5.5.0
	// NOTE: `wp_is_development_mode()` @since WP 6.3.0
	public static function isDev()
	{
		if ( defined( 'WP_STAGE' )
			&& 'development' == constant( 'WP_STAGE' ) )
				return TRUE;

		if ( function_exists( 'wp_get_environment_type' ) )
			return 'development' === wp_get_environment_type();

		// if ( function_exists( 'wp_is_development_mode' ) )
		// 	return wp_is_development_mode( 'all' );

		return FALSE;
	}

	public static function isFlush( $cap = 'publish_posts', $key = 'flush' )
	{
		if ( $cap && isset( $_GET[$key] ) )
			return did_action( 'init' ) && ( TRUE === $cap || current_user_can( $cap ) );

		return FALSE;
	}

	public static function isAdminAJAX()
	{
		return self::isAJAX() && Text::has( wp_get_raw_referer(), '/wp-admin/' );
	}

	public static function isAJAX()
	{
		// return defined( 'DOING_AJAX' ) && DOING_AJAX;
		return wp_doing_ajax(); // @since WP 4.7.0
	}

	public static function isCRON()
	{
		// return defined( 'DOING_CRON' ) && DOING_CRON;
		return wp_doing_cron(); // @since WP 4.8.0
	}

	// support if behind web proxy/balancer
	// @REF: https://developer.wordpress.org/reference/functions/is_ssl/#comment-4265
	public static function isSSL()
	{
		// cloudflare
		if ( ! empty( $_SERVER['HTTP_CF_VISITOR'] ) ) {

			$visitor = json_decode( $_SERVER['HTTP_CF_VISITOR'] );

			if ( isset( $visitor->scheme )
				&& 'https' === $visitor->scheme )
					return TRUE;
		}

		// other proxy
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] )
			&& 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] )
				return TRUE;


		return function_exists( 'is_ssl' ) ? is_ssl() : FALSE;
	}

	// @REF: `wc_site_is_https()`
	// @SEE: `wp_is_using_https()` @since WP 5.7.0
	public static function siteIsHTTPS()
	{
		return FALSE !== strstr( get_option( 'home' ), 'https:' );
	}

	public static function isImporting()
	{
		return defined( 'WP_IMPORTING' ) && WP_IMPORTING;
	}

	public static function isCLI()
	{
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	public static function isXMLRPC()
	{
		return defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST;
	}

	// @SEE: `wp_is_serving_rest_request()`/`wp_is_rest_endpoint()`
	public static function isREST()
	{
		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	public static function isIFrame()
	{
		return defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST;
	}

	public static function isXML()
	{
		if ( function_exists( 'wp_is_xml_request' ) && wp_is_xml_request() )
			return TRUE;

		if ( ! isset( $GLOBALS['wp_query'] ) )
			return FALSE;

		if ( function_exists( 'is_feed' ) && is_feed() )
			return TRUE;

		if ( function_exists( 'is_comment_feed' ) && is_comment_feed() )
			return TRUE;

		if ( function_exists( 'is_trackback' ) && is_trackback() )
			return TRUE;

		return FALSE;
	}

	public static function doNotCache()
	{
		defined( 'DONOTCACHEPAGE' ) || define( 'DONOTCACHEPAGE', TRUE );
	}

	// FIXME: DEPRECATED: use `PostType::htmlFeaturedImage()`
	public static function getFeaturedImage( $post_id, $size = 'thumbnail', $default = FALSE )
	{
		self::_dep( 'PostType::htmlFeaturedImage()' );

		if ( ! $post_thumbnail_id = get_post_thumbnail_id( $post_id ) )
			return $default;

		$post_thumbnail_img = wp_get_attachment_image_src( $post_thumbnail_id, $size );
		return $post_thumbnail_img[0];
	}

	// @REF: `wp_referer_field()`
	public static function fieldReferer()
	{
		HTML::inputHidden( '_wp_http_referer', self::unslash( remove_query_arg( [
			'_wp_http_referer',
			'message',
			'action',
			'paged',
			'count',
		] ) ) );
	}

	// wrapper for `wp_get_referer()`
	public static function getReferer()
	{
		return remove_query_arg( [
			'_wp_http_referer',
			'message',
			'action',
			'paged',
			'count',
		], wp_get_referer() );
	}

	public static function redirectJS( $location = NULL, $timeout = 3000 )
	{
		?><script type="text/javascript">
function nextpage() {
	location.href = "<?php echo ( $location ?? self::getReferer() ); ?>";
}
setTimeout( "nextpage()", <?php echo $timeout; ?> );
</script><?php
	}

	public static function redirect( $location = NULL, $status = 302 )
	{
		if ( wp_redirect( $location ?? self::getReferer(), $status ) )
			exit;

		wp_die(); // something's wrong!
	}

	public static function redirectReferer( $message = 'updated', $key = 'message' )
	{
		if ( is_array( $message ) )
			$url = add_query_arg( $message, self::getReferer() );
		else
			$url = add_query_arg( $key, $message, self::getReferer() );

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

	public static function getSearchLink( $query = FALSE, $url = FALSE, $query_id = 's' )
	{
		if ( $url )
			return $query ? add_query_arg( $query_id, urlencode( $query ), $url ) : $url;

		if ( defined( 'GNETWORK_SEARCH_REDIRECT' ) && GNETWORK_SEARCH_REDIRECT )
			return $query ? add_query_arg( GNETWORK_SEARCH_QUERYID, urlencode( $query ), GNETWORK_SEARCH_URL ) : GNETWORK_SEARCH_URL;

		return get_search_link( $query );
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

	// FIXME: move to `PostType`
	public static function getPostTypeEditLink( $posttype, $user_id = 0, $extra = array() )
	{
		$query = array( 'post_type' => $posttype );

		if ( $user_id )
			$query['author'] = $user_id;

		return add_query_arg( array_merge( $query, $extra ), admin_url( 'edit.php' ) );
	}

	// FIXME: move to `PostType`
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

	public static function getTermShortLink( $term_id, $extra = array() )
	{
		return add_query_arg( array_merge( array(
			't' => $term_id,
		), $extra ), get_bloginfo( 'url' ) );
	}

	public static function getPostNewLink( $posttype, $extra = array() )
	{
		$args = 'post' == $posttype ? array() : array( 'post_type' => $posttype );

		return add_query_arg( array_merge( $args, $extra ), admin_url( 'post-new.php' ) );
	}

	public static function getPostAttachmentsLink( $post_id, $extra = array() )
	{
		return add_query_arg( array_merge( array(
			'post_parent' => $post_id,
		), $extra ), admin_url( 'upload.php' ) );
	}

	public static function getAuthorEditHTML( $posttype, $author, $extra = array() )
	{
		if ( $author_data = get_user_by( 'id', $author ) )
			return HTML::tag( 'a', array(
				'href' => add_query_arg( array_merge( array(
					'post_type' => $posttype,
					'author'    => $author,
				), $extra ), admin_url( 'edit.php' ) ),
				'title' => $author_data->user_login,
				'class' => '-author',
			), HTML::escape( $author_data->display_name ) );

		return FALSE;
	}

	public static function getUserEditLink( $user_id, $extra = array(), $network = FALSE, $check = TRUE )
	{
		if ( ! $user_id )
			return FALSE;

		if ( $check && ! current_user_can( 'edit_user', $user_id ) )
			return FALSE;

		return add_query_arg( array_merge( array(
			'user_id' => $user_id,
		), $extra ), $network
			? network_admin_url( 'user-edit.php' )
			: admin_url( 'user-edit.php' ) );

		return FALSE;
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

		foreach ( get_option( 'rewrite_rules', [] ) as $rule => $rewrite )
			$list[$rule]['rewrite'] = $rewrite;

		$list = array_reverse( $list, TRUE );

		foreach ( $wp_rewrite->rewrite_rules() as $rule => $rewrite ) {
			if ( ! array_key_exists( $rule, $list ) ) {
				$missing = TRUE;
				break;
			}
		}

		if ( $missing && $flush ) {
			flush_rewrite_rules();
			wp_cache_delete( 'rewrite_rules', 'options' );
		}

		return $missing;
	}

	// @REF: `is_plugin_active()`
	public static function isPluginActive( $plugin, $network_check = TRUE )
	{
		if ( in_array( $plugin, (array) apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), TRUE ) )
			return TRUE;

		if ( $network_check && self::isPluginActiveForNetwork( $plugin ) )
			return TRUE;

		return FALSE;
	}

	// @REF: `is_plugin_active_for_network()`
	public static function isPluginActiveForNetwork( $plugin, $network = NULL )
	{
		if ( is_multisite() )
			return (bool) in_array( $plugin, (array) get_network_option( $network, 'active_sitewide_plugins' ), TRUE );

		return FALSE;
	}

	public static function currentSiteName( $slash = TRUE )
	{
		return URL::prepTitle( get_option( 'home' ), $slash );
	}
}
