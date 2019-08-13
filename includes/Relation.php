<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\WordPress\Database;
use geminorum\gEditorial\O2O;

class Relation extends Core\Base
{

	const BASE   = 'geditorial';
	const MODULE = FALSE;

	protected static function constant( $key, $default = FALSE )
	{
		return gEditorial()->constant( static::MODULE, $key, $default );
	}

	protected static function getString( $string, $posttype = 'post', $group = 'titles', $fallback = FALSE )
	{
		return gEditorial()->{static::MODULE}->get_string( $string, $posttype, $group, $fallback );
	}

	protected static function getPostMeta( $post_id, $field = FALSE, $default = '', $key = NULL )
	{
		return gEditorial()->{static::MODULE}->get_postmeta( $post_id, $field, $default, $key );
	}

	public static function setup()
	{
		$class = __NAMESPACE__.'\\Relation';

		Database::registerTable( 'o2o' );
		Database::registerTable( 'o2ometa' );

		// `P2P_Query_Post::init()`
		add_action( 'parse_query', [ $class, 'parse_query' ], 20 );
		add_filter( 'posts_clauses', [ $class, 'posts_clauses' ], 20, 2 );
		add_filter( 'posts_request', [ $class, 'capture' ], 999, 2 );
		add_filter( 'the_posts', [ $class, 'cache_o2o_meta' ], 20, 2 );

		// `P2P_Query_User::init()`
		add_action( 'pre_user_query', [ $class, 'pre_user_query' ], 20 );

		// `P2P_URL_Query::init()`
		add_filter( 'query_vars', [ $class, 'query_vars' ] );

		// working but disabled
		// register_uninstall_hook( GEDITORIAL_FILE, [ $class, 'uninstallStorage' ] );

		add_action( 'rest_api_init', [ $class, 'rest_api_init' ] );

		if ( ! is_admin() )
			return;

		// define( 'O2O_BOX_NONCE', 'o2o-box' );

		// new P2P_Box_Factory;
		// new P2P_Column_Factory;
		// new P2P_Dropdown_Factory;

		// `P2P_Tools_Page::setup()`
		add_action( 'admin_notices', [ $class, 'maybeInstall' ] );
	}

	public static function parse_query( $wp_query )
	{
		$result = O2O\Query::create_from_qv( $wp_query->query_vars, 'post' );

		if ( is_wp_error( $result ) ) {
			$wp_query->_o2o_error = $result;
			$wp_query->set( 'year', 2525 );
			return;
		}

		if ( NULL === $result )
			return;

		list( $wp_query->_o2o_query, $wp_query->query_vars ) = $result;

		$wp_query->is_home    = FALSE;
		$wp_query->is_archive = TRUE;
	}

	public static function posts_clauses( $clauses, $wp_query )
	{
		global $wpdb;

		if ( ! isset( $wp_query->_o2o_query ) )
			return $clauses;

		return $wp_query->_o2o_query->alter_clauses( $clauses, "$wpdb->posts.ID" );
	}

	public static function capture( $request, $wp_query )
	{
		global $wpdb;

		if ( ! isset( $wp_query->_o2o_capture ) )
			return $request;

		$wp_query->_o2o_sql = $request;

		return '';
	}

	// pre-populates the o2o meta cache to decrease the number of queries
	public static function cache_o2o_meta( $the_posts, $wp_query )
	{
		if ( isset( $wp_query->_o2o_query ) && ! empty( $the_posts ) )
			update_meta_cache( 'o2o', wp_list_pluck( $the_posts, 'o2o_id' ) );

		return $the_posts;
	}

	public static function pre_user_query( $query )
	{
		global $wpdb;

		$result = O2O\Query::create_from_qv( $query->query_vars, 'user' );

		if ( is_wp_error( $result ) ) {
			$query->_o2o_error  = $result;
			$query->query_where = " AND 1=0";
			return;
		}

		if ( NULL === $result )
			return;

		list( $o2o_q, $query->query_vars ) = $result;

		$map = [
			'fields'  => 'query_fields',
			'join'    => 'query_from',
			'where'   => 'query_where',
			'orderby' => 'query_orderby',
		];

		$clauses = [];

		foreach ( $map as $clause => $key )
			$clauses[$clause] = $query->$key;

		$clauses = $o2o_q->alter_clauses( $clauses, "{$wpdb->users}.ID" );

		if ( 0 !== strpos( $clauses['orderby'], 'ORDER BY ' ) )
			$clauses['orderby'] = 'ORDER BY '.$clauses['orderby'];

		foreach ( $map as $clause => $key )
			$query->$key = $clauses[ $clause ];
	}

	// make the query vars public
	public static function query_vars( $public_query_vars )
	{
		return array_merge( $public_query_vars, [
			'connected_type',
			'connected_items',
			'connected_direction',
		] );
	}

	public static function maybeInstall()
	{
		if ( ! current_user_can( 'manage_options' ) )
			return;

		$current_ver = get_option( 'o2o_storage' );

		if ( $current_ver == static::STORAGE )
			return;

		self::installStorage();

		update_option( 'o2o_storage', static::STORAGE );
	}

	const STORAGE = 4; // same as p2p

	public static function installStorage()
	{
		Database::installTable( 'o2o', "
			o2o_id bigint(20) unsigned NOT NULL auto_increment,
			o2o_from bigint(20) unsigned NOT NULL,
			o2o_to bigint(20) unsigned NOT NULL,
			o2o_type varchar(44) NOT NULL default '',
			PRIMARY KEY (o2o_id),
			KEY o2o_from (o2o_from),
			KEY o2o_to (o2o_to),
			KEY o2o_type (o2o_type)
		" );

		Database::installTable( 'o2ometa', "
			meta_id bigint(20) unsigned NOT NULL auto_increment,
			o2o_id bigint(20) unsigned NOT NULL default '0',
			meta_key varchar(255) default NULL,
			meta_value longtext,
			PRIMARY KEY (meta_id),
			KEY o2o_id (o2o_id),
			KEY meta_key (meta_key)
		" );
	}

	public static function uninstallStorage()
	{
		Database::uninstallTable( 'o2o' );
		Database::uninstallTable( 'o2ometa' );

		delete_option( 'o2o_storage' );
	}

	// @REF: https://github.com/JiveDig/restful-p2p
	// @REF: https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	public static function rest_api_init()
	{
		register_rest_route( static::BASE.'-o2o/v1', '/connect/(?P<name>[a-zA-Z0-9-_]+)/(?P<from>\d+)/(?P<to>\d+)', [
			'methods'  => 'POST',
			'callback' => [ __CLASS__, 'rest_connect' ],
			'args'     => [
				'name' => [
					'validate_callback' => function( $param, $request, $key ) {
						return TRUE; // FIXME
					}
				],
				'from' => [
					'validate_callback' => function( $param, $request, $key ) {
						return is_numeric( $param );
					}
				],
				'to' => [
					'validate_callback' => function( $param, $request, $key ) {
						return is_numeric( $param );
					}
				],
			],
			'permission_callback' => function ( $request ) {
				return current_user_can( 'edit_post', intval( $request['to'] ) );
			},
		] );

		register_rest_route( static::BASE.'-o2o/v1', '/disconnect/(?P<name>[a-zA-Z0-9-_]+)/(?P<from>\d+)/(?P<to>\d+)', [
			'methods'  => 'POST',
			'callback' => [ __CLASS__, 'rest_disconnect' ],
			'args'     => [
				'name' => [
					'validate_callback' => function( $param, $request, $key ) {
						return TRUE; // FIXME
					}
				],
				'from' => [
					'validate_callback' => function( $param, $request, $key ) {
						return is_numeric( $param );
					}
				],
				'to' => [
					'validate_callback' => function( $param, $request, $key ) {
						return is_numeric( $param );
					}
				],
			],
			'permission_callback' => function ( $request ) {
				return current_user_can( 'edit_post', intval( $request['to'] ) );
			},
		] );
	}

	public static function rest_connect( $request )
	{
		if ( ! $type = O2O\API::type( $request['name'] ) )
			return new \WP_Error( 'no_connection_type', _x( 'No connection type found!', 'Relation: REST', GEDITORIAL_TEXTDOMAIN ), [ 'status' => 404 ] );

		// $meta = [ 'date' => current_time( 'mysql' ) ];
		$o2o = $type->connect( $request['from'], $request['to'] );

		return is_wp_error( $o2o ) ? $o2o : TRUE;
	}

	public static function rest_disconnect( $request )
	{
		if ( ! $type = O2O\API::type( $request['name'] ) )
			return new \WP_Error( 'no_connection_type', _x( 'No connection type found!', 'Relation: REST', GEDITORIAL_TEXTDOMAIN ), [ 'status' => 404 ] );

		$o2o = $type->disconnect( $request['from'], $request['to'] );

		return is_wp_error( $o2o ) ? $o2o : TRUE;
	}
}
