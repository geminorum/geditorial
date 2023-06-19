<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\WordPress\Main;
use geminorum\gEditorial\WordPress\Post;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\User;

class SelectSingle extends Main
{

	const BASE = 'geditorial';

	public static function setup()
	{
		add_action( 'rest_api_init', [ __CLASS__, 'rest_api_init' ] );
	}

	public static function namespace()
	{
		return sprintf( '%s-select2/v1', static::BASE );
	}

	public static function rest_api_init()
	{
		register_rest_route( self::namespace(), '/query', [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ __CLASS__, 'query_callback' ],
			'permission_callback' => [ __CLASS__, 'permission_callback' ],
		] );
	}

	public static function permission_callback( $request )
	{
		return TRUE; // later we check for access
	}

	// @REF: https://select2.org/data-sources/formats
	public static function query_callback( $request )
	{
		$queried = self::atts( [
			'search'   => '',
			'target'   => '',
			'posttype' => '',
			'taxonomy' => '',
			'role'     => '',
			'page'     => '1',
			'per'      => '10',
		], $request->get_query_params() );

		switch ( $queried['target'] ) {

			case 'post':

				if ( empty( $queried['posttype'] ) )
					return new \WP_Error( 'no_correct_settings', gEditorial\Plugin::wrong() );

				$queried['posttype'] = explode( ',', $queried['posttype'] );

				foreach ( $queried['posttype'] as $index => $posttype )
					if ( ! PostType::can( $posttype, 'read' ) )
						unset( $queried['posttype'][$index] );

				// again check if any left!
				if ( empty( $queried['posttype'] ) )
					return new \WP_Error( 'not_authorized', gEditorial\Plugin::wrong() );

				$response = self::_get_select2_posts( $queried );
				break;

			case 'term':

				if ( empty( $queried['taxonomy'] ) )
					return new \WP_Error( 'no_correct_settings', gEditorial\Plugin::wrong() );

				$queried['taxonomy'] = explode( ',', $queried['taxonomy'] );

				foreach ( $queried['taxonomy'] as $index => $taxonomy )
					if ( ! Taxonomy::can( $taxonomy, 'assign_terms' ) )
						unset( $queried['taxonomy'][$index] );

				// again check if any left!
				if ( empty( $queried['taxonomy'] ) )
					return new \WP_Error( 'not_authorized', gEditorial\Plugin::wrong() );

				$response = self::_get_select2_terms( $queried );
				break;

			case 'user':

				if ( ! User::cuc( 'list_users' ) )
					return new \WP_Error( 'not_authorized', gEditorial\Plugin::wrong() );

				$response = self::_get_select2_users( $queried );
				break;

			default:

				return new \WP_Error( 'no_correct_settings', gEditorial\Plugin::wrong() );
		}

		return new \WP_REST_Response( $response, 200 );
	}

	// TODO: include/exclude by taxonomy terms
	private static function _get_select2_posts( $queried )
	{
		$args  = [
			'post_type'              => $queried['posttype'],
			'posts_per_page'         => 10,
			// 'no_found_rows'          => TRUE, // needs for pagination
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'update_menu_item_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
			'fields'                 => 'ids',
		];

		if ( ! empty( $queried['search'] ) )
			$args['s'] = trim( $queried['search'] );

		if ( ! empty( $queried['per'] ) )
			$args['posts_per_page'] = trim( $queried['per'] );

		if ( ! empty( $queried['status'] ) )
			$args['post_status'] = trim( $queried['status'] );
		else
			// use only with persistent cache
			// $args['post_status'] = Posttype::getAvailableStatuses( $args['post_type'] );
			$args['post_status'] = [ 'publish', 'future', 'draft' ];

		AdvancedQueries::hookSearchPostTitleOnly();

		$query = new \WP_Query();
		$posts = [];

		foreach ( $query->query( $args ) as $post )
			$posts[] = (object) [
				'id'   => $post,
				'text' => Post::title( $post ),
			];

		return [
			'results'    => $posts,
			'pagination' => [
				'more' => ( $query->found_posts - $args['posts_per_page'] ) > 0
			],
		];
	}

	private static function _get_select2_terms( $queried )
	{
		$args = [
			'taxonomy' => $queried['taxonomy'],
			'number'   => $queried['per'],
			'offset'   => ( $queried['page'] - 1 ) * $queried['per'],
			'orderby'  => 'name',
			'fields'   => 'id=>name',

			'hide_empty'             => FALSE,
			'update_term_meta_cache' => FALSE,
			'suppress_filters'       => TRUE,
		];

		if ( ! empty( $queried['search'] ) )
			$args['name__like'] = trim( $queried['search'] );

		$query = new \WP_Term_Query();
		$terms = [];

		foreach ( $query->query( $args ) as $term_id => $term_name )
			$terms[] = (object) [
				'id'   => $term_id,
				'text' => $term_name,
			];

		return [
			'results'    => $terms,
			'pagination' => [
				'more' => ( $query->found_posts - $args['number'] ) > 0
			],
		];
	}

	private static function _get_select2_users( $queried )
	{
		$args = [
			'login__not_in'  => get_super_admins(),
			'role__not_in '  => [ 'administrator', 'subscriber' ],
			'search_columns' => [
				'user_login',
				'user_email',
				'user_nicename',
				'display_name',
			],

			'number'  => $queried['per'],
			'offset'  => ( $queried['page'] - 1 ) * $queried['per'],
			'orderby' => 'name',
			'fields'  => 'all',

			'update_term_meta_cache' => FALSE,
			'suppress_filters'       => TRUE,
		];

		if ( ! empty( $queried['role'] ) && 'all' !== trim( $queried['role'] ) )
			$args['role__in'] = array_diff( explode( ',', $queried['role'] ), $args['role__not_in'] );

		if ( ! empty( $queried['search'] ) )
			$args['search'] = trim( $queried['search'] );

		$query = new \WP_User_Query( $args );
		$users = [];

		foreach ( (array) $query->get_results() as $user )
			$users[] = (object) [
				'id'   => $user->ID,
				'text' => User::getTitleRow( $user ),
			];

		return [
			'results'    => $users,
			'pagination' => [
				'more' => ( $query->found_posts - $args['number'] ) > 0
			],
		];
	}

	// TODO: better styling
	public static function enqueue( $extra = [] )
	{
		static $enqueued = FALSE;

		if ( $enqueued )
			return $enqueued;

		$args = self::recursiveParseArgs( $extra, [
			// 'settings' => [],
			'strings' => [
				'placeholder'     => _x( 'Select an item …', 'Service: SelectSingle', 'geditorial' ),
				'loadingmore'     => _x( 'Loading more results …', 'Service: SelectSingle', 'geditorial' ),
				'searching'       => _x( 'Searching …', 'Service: SelectSingle', 'geditorial' ),
				'noresults'       => _x( 'No results found', 'Service: SelectSingle', 'geditorial' ),
				'removeallitems'  => _x( 'Remove all items', 'Service: SelectSingle', 'geditorial' ),
				'removeitem'      => _x( 'Remove item', 'Service: SelectSingle', 'geditorial' ),
				'search'          => _x( 'Search', 'Service: SelectSingle', 'geditorial' ),
				'errorloading'    => _x( 'The results could not be loaded.', 'Service: SelectSingle', 'geditorial' ),
				/* translators: %s: number of characters */
				'inputtooshort'   => _x( 'Please enter %s or more characters', 'Service: SelectSingle', 'geditorial' ),
				/* translators: %s: number of characters */
				'inputtoolong'    => _x( 'Please delete %s character(s)', 'Service: SelectSingle', 'geditorial' ),
				/* translators: %s: number of items */
				'maximumselected' => _x( 'You can only select %s item(s)', 'Service: SelectSingle', 'geditorial' ),
			],
		] );

		if ( ! array_key_exists( '_rest', $args ) )
			$args['_rest'] = self::namespace();

		if ( ! array_key_exists( '_nonce', $args ) && is_user_logged_in() )
			$args['_nonce'] = wp_create_nonce( 'selectsingle' );

		gEditorial()->enqueue_asset_config( $args, '_selectsingle' );

		return $enqueued = Scripts::enqueue( 'all.selectsingle', [ 'jquery', Scripts::pkgSelect2() ] );
	}
}
