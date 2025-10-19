<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\WordPress\PostType;

class WordPress extends Base
{

	/**
	 * Checks compatibility with the current WordPress version.
	 * @source `is_wp_version_compatible()`
	 *
	 * @param string $required
	 * @return bool
	 */
	public static function isWPcompatible( $required )
	{
		return empty( $required ) || version_compare( get_bloginfo( 'version' ), $required, '>=' );
	}

	/**
	 * Checks compatibility with the current PHP version.
	 * @source `is_php_version_compatible()`
	 *
	 * @param string $required
	 * @return bool
	 */
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

		return in_array( $now, (array) $page, TRUE );
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
		// Cloudflare
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

	public static function siteIsHTTPS()
	{
		if ( function_exists( 'wp_is_using_https' ) )
			return wp_is_using_https(); // @since WP 5.7.0

		return 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME );  // @source: `wp_is_home_url_using_https()`
		// return FALSE   !== strstr( get_option( 'home' ), 'https:' );    // @source: `wc_site_is_https()`
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

	/**
	 * Determines whether WordPress is currently serving a REST API request.
	 * @source `wp_is_serving_rest_request()`
	 *
	 * @return bool
	 */
	public static function isREST()
	{
		if ( function_exists( 'wp_is_serving_rest_request' ) )
			return wp_is_serving_rest_request(); // @since WP 6.5.0

		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	/**
	 * Checks whether a REST API endpoint request is currently being handled.
	 *
	 * This maybe a standalone REST API request, or an internal request
	 * dispatched from within a regular page load.
	 *
	 * @source `wp_is_rest_endpoint()`
	 *
	 * @return bool
	 */
	public static function isEndpointREST()
	{
		if ( function_exists( 'wp_is_rest_endpoint' ) )
			return wp_is_rest_endpoint(); // @since WP 6.5.0

		return self::isREST();
	}

	public static function isIFrame()
	{
		return defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST;
	}

	public static function isXML()
	{
		// NOTE: known conflict with caching plugins
		// if ( function_exists( 'wp_is_xml_request' ) && wp_is_xml_request() )
		// 	return TRUE;

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

	public static function isExport()
	{
		if ( defined( 'GNETWORK_IS_WP_EXPORT' ) && GNETWORK_IS_WP_EXPORT )
			return TRUE;

		return FALSE;
	}

	public static function doNotCache()
	{
		self::define( 'DONOTCACHEPAGE', TRUE );
	}

	public static function getAdminPostLink( $action, $extra = [] )
	{
		return add_query_arg( array_merge( [ 'action' => $action ], $extra ), admin_url( 'admin-post.php' ) );
	}

	public static function getAdminPageLink( $page, $extra = [], $base = 'admin.php' )
	{
		return add_query_arg( array_merge( [ 'page' => $page ], $extra ), admin_url( $base ) );
	}

	public static function getAdminSearchLink( $criteria = FALSE, $posttype = NULL, $extra = [] )
	{
		$query = [ 's' => $criteria ];

		if ( PostType::can( $posttype, 'read' ) )
			$query['post_type'] = $posttype;

		return add_query_arg( array_merge( $query, $extra ), admin_url( 'edit.php' ) );
	}

	public static function getSearchLink( $query = FALSE, $url = FALSE, $query_id = 's' )
	{
		if ( $url )
			return $query ? add_query_arg( $query_id, urlencode( $query ), $url ) : $url;

		if ( defined( 'GNETWORK_SEARCH_REDIRECT' ) && GNETWORK_SEARCH_REDIRECT )
			return $query ? add_query_arg( GNETWORK_SEARCH_QUERYID, urlencode( $query ), GNETWORK_SEARCH_URL ) : GNETWORK_SEARCH_URL;

		return get_search_link( $query );
	}

	// @SOURCE: `wp-load.php`
	public static function getConfigPHP( $path = ABSPATH )
	{
		// The config file resides in `ABSPATH`
		if ( file_exists( $path.'wp-config.php' ) )
			return $path.'wp-config.php';

		// The config file resides one level above `ABSPATH` but is not part of another install
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

	/**
	 * Flushes rewrite rules when it's necessary.
	 * This could be put in an init hook or the like and ensures that
	 * the rewrite rules option is only rewritten when the generated rules
	 * don't match up with the option.
	 * @source https://gist.github.com/tott/9548734
	 *
	 * @param bool $flush
	 * @return bool
	 */
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

	public static function currentSiteName( $slash = TRUE )
	{
		return URL::prepTitle( get_option( 'home' ), $slash );
	}
}
