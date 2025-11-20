<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ObjectToObject extends gEditorial\Service
{
	public static function setup()
	{
		// FIXME: WTF: not compatible with original p2p!
		if ( defined( 'P2P_PLUGIN_VERSION' ) )
			return;

		WordPress\Database::registerTable( 'o2o' );
		WordPress\Database::registerTable( 'o2ometa' );

		add_action( 'wp_loaded', [ __CLASS__, 'wp_loaded' ] );

		// `P2P_Query_Post::init()`
		add_action( 'parse_query', [ __CLASS__, 'parse_query' ], 20 );
		add_filter( 'posts_clauses', [ __CLASS__, 'posts_clauses' ], 20, 2 );
		add_filter( 'posts_request', [ __CLASS__, 'capture' ], 999, 2 );
		add_filter( 'the_posts', [ __CLASS__, 'cache_o2o_meta' ], 20, 2 );

		// `P2P_Query_User::init()`
		add_action( 'pre_user_query', [ __CLASS__, 'pre_user_query' ], 20 );

		// `P2P_URL_Query::init()`
		add_filter( 'query_vars', [ __CLASS__, 'query_vars' ] );

		// working but disabled
		// register_uninstall_hook( GEDITORIAL_FILE, [ __CLASS__, 'uninstallStorage' ] );

		add_action( 'rest_api_init', [ __CLASS__, 'rest_api_init' ] );

		if ( ! is_admin() )
			return;

		define( 'GEDITORIAL_O2O_BOX_NONCE', 'o2o-box' );

		// O2O\Admin\Mustache::init();

		new O2O\Admin\BoxFactory;
		new O2O\Admin\ColumnFactory;
		new O2O\Admin\DropdownFactory;

		// O2O\Admin\ToolsPage::setup();
		add_action( 'admin_notices', [ __CLASS__, 'maybeInstall' ] );
	}

	public static function wp_loaded()
	{
		do_action( 'o2o_init' ); // avoid unnecessary registers
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

		return $wp_query->_o2o_query->alter_clauses( $clauses, "{$wpdb->posts}.ID" );
	}

	public static function capture( $request, $wp_query )
	{
		if ( ! isset( $wp_query->_o2o_capture ) )
			return $request;

		$wp_query->_o2o_sql = $request;

		return '';
	}

	// pre-populates the o2o meta cache to decrease the number of queries
	public static function cache_o2o_meta( $the_posts, $wp_query )
	{
		if ( isset( $wp_query->_o2o_query ) && ! empty( $the_posts ) )
			update_meta_cache( 'o2o', Core\Arraay::pluck( $the_posts, 'o2o_id' ) );

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
		return array_merge( $public_query_vars, self::get_custom_query_vars() );
	}

	public static function get_custom_query_vars()
	{
		return [
			'connected_type',
			'connected_items',
			'connected_direction',
		];
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
		WordPress\Database::installTable( 'o2o', "
			o2o_id bigint(20) unsigned NOT NULL auto_increment,
			o2o_from bigint(20) unsigned NOT NULL,
			o2o_to bigint(20) unsigned NOT NULL,
			o2o_type varchar(44) NOT NULL default '',
			PRIMARY KEY (o2o_id),
			KEY o2o_from (o2o_from),
			KEY o2o_to (o2o_to),
			KEY o2o_type (o2o_type)
		" );

		WordPress\Database::installTable( 'o2ometa', "
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
		WordPress\Database::uninstallTable( 'o2o' );
		WordPress\Database::uninstallTable( 'o2ometa' );

		delete_option( 'o2o_storage' );
	}

	const REST_ENDPOINT_SUFFIX  = 'o2o';
	const REST_ENDPOINT_VERSION = 'v1';

	public static function namespace()
	{
		return sprintf( '%s-%s/%s',
			static::BASE,
			static::REST_ENDPOINT_SUFFIX,
			static::REST_ENDPOINT_VERSION
		);
	}

	// @REF: https://github.com/JiveDig/restful-p2p
	// @REF: https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	public static function rest_api_init()
	{
		register_rest_route( self::namespace(), '/connect/(?P<name>[a-zA-Z0-9-_]+)/(?P<from>[\d]+)/(?P<to>[\d]+)', [
			'methods'  => \WP_REST_Server::CREATABLE,
			'callback' => [ __CLASS__, 'rest_connect' ],
			'args'     => [
				'name' => [
					'validate_callback' => static function ( $param, $request, $key ) {
						return (bool) O2O\API::type( $param );
					}
				],
				'from' => [
					'validate_callback' => static function ( $param, $request, $key ) {
						return (bool) WordPress\Post::get( (int) $param );
					}
				],
				'to' => [
					'validate_callback' => static function ( $param, $request, $key ) {
						return (bool) WordPress\Post::get( (int) $param );
					}
				],
			],
			'permission_callback' => static function ( $request ) {
				return current_user_can( 'edit_post', (int) $request['to'] );
			},
		] );

		register_rest_route( self::namespace(), '/disconnect/(?P<name>[a-zA-Z0-9-_]+)/(?P<from>[\d]+)/(?P<to>[\d]+)', [
			'methods'  => \WP_REST_Server::CREATABLE,
			'callback' => [ __CLASS__, 'rest_disconnect' ],
			'args'     => [
				'name' => [
					'validate_callback' => static function ( $param, $request, $key ) {
						return (bool) O2O\API::type( $param );
					}
				],
				'from' => [
					'validate_callback' => static function ( $param, $request, $key ) {
						return (bool) WordPress\Post::get( (int) $param );
					}
				],
				'to' => [
					'validate_callback' => static function ( $param, $request, $key ) {
						return (bool) WordPress\Post::get( (int) $param );
					}
				],
			],
			'permission_callback' => static function ( $request ) {
				return current_user_can( 'edit_post', (int) $request['to'] );
			},
		] );
	}

	public static function rest_connect( $request )
	{
		if ( ! $type = O2O\API::type( $request['name'] ) )
			return new \WP_Error( 'no_connection_type', _x( 'There are no connection types available!', 'Relation: REST', 'geditorial' ), [ 'status' => 404 ] );

		// $meta = [ 'date' => current_time( 'mysql' ) ];
		$o2o = $type->connect( $request['from'], $request['to'] );

		return is_wp_error( $o2o ) ? $o2o : TRUE;
	}

	public static function rest_disconnect( $request )
	{
		if ( ! $type = O2O\API::type( $request['name'] ) )
			return new \WP_Error( 'no_connection_type', _x( 'There are no connection types available!', 'Relation: REST', 'geditorial' ), [ 'status' => 404 ] );

		$o2o = $type->disconnect( $request['from'], $request['to'] );

		return is_wp_error( $o2o ) ? $o2o : TRUE;
	}

	// OLD: `Box::init_scripts()`
	public static function enqueueBox( $extra = [] )
	{
		static $enqueued = FALSE;

		if ( $enqueued )
			return $enqueued;

		$args = self::recursiveParseArgs( $extra, [
			'strings' => [
				'confirm' => _x( 'Are you sure you want to delete all connections?', 'O2O', 'geditorial-admin' ),
			],
		] );

		if ( ! array_key_exists( '_rest', $args ) )
			$args['_rest'] = sprintf( '/%s', self::namespace() );

		if ( ! array_key_exists( '_nonce', $args ) )
			$args['_nonce'] = wp_create_nonce( GEDITORIAL_O2O_BOX_NONCE );

		if ( ! array_key_exists( '_spinner', $args ) )
			$args['_spinner'] = admin_url( 'images/wpspin_light.gif' );

		gEditorial()->enqueue_asset_config( $args, 'o2obox' );

		add_action( 'admin_footer', [ __NAMESPACE__.'\\O2O\\Admin\\Box', 'add_templates' ] );

		return $enqueued = gEditorial\Scripts::enqueue( 'admin.o2obox', [
			'jquery',
			'backbone',
			gEditorial\Scripts::pkgMustache(),
		] );
	}
}
