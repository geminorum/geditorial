<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

// @SEE: https://johnbeales.com/2018/changing-the-auto-suggest-behaviour-in-woocommerce/

class SearchSelect extends gEditorial\Service
{
	const REST_ENDPOINT_SUFFIX     = 'searchselect';
	const REST_ENDPOINT_VERSION    = 'v1';
	const REST_ENDPOINT_MAIN_ROUTE = 'query';

	public static function setup()
	{
		add_action( 'rest_api_init', [ __CLASS__, 'rest_api_init' ] );
	}

	public static function namespace()
	{
		return sprintf( '%s-%s/%s',
			static::BASE,
			static::REST_ENDPOINT_SUFFIX,
			static::REST_ENDPOINT_VERSION
		);
	}

	public static function rest_api_init()
	{
		register_rest_route( self::namespace(), '/'.static::REST_ENDPOINT_MAIN_ROUTE, [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ __CLASS__, 'main_route_callback' ],
			'permission_callback' => '__return_true', // NOTE: later we check for access
		] );
	}

	// @REF: https://select2.org/data-sources/formats
	public static function main_route_callback( $request )
	{
		$queried = self::atts( [
			'context'  => NULL,   // TODO / default is `select2` compatible
			'search'   => '',
			'target'   => '',
			'include'  => '',
			'exclude'  => '',
			'posttype' => '',
			'taxonomy' => '',
			'role'     => '',
			'page'     => '1',
			'per'      => '10',
		], $request->get_query_params() );

		switch ( $queried['target'] ) {

			case 'post':

				if ( empty( $queried['posttype'] ) )
					return RestAPI::getErrorSomethingIsWrong();

				if ( ! is_array( $queried['posttype'] ) )
					$queried['posttype'] = explode( ',', $queried['posttype'] );

				foreach ( $queried['posttype'] as $index => $posttype )
					if ( ! WordPress\PostType::can( $posttype, 'read' ) )
						unset( $queried['posttype'][$index] );

				// again check if any left!
				if ( empty( $queried['posttype'] ) )
					return RestAPI::getErrorForbidden();

				$response = self::_get_select2_posts( $queried );
				break;

			case 'term':

				if ( empty( $queried['taxonomy'] ) )
					return RestAPI::getErrorSomethingIsWrong();

				if ( ! is_array( $queried['taxonomy'] ) )
					$queried['taxonomy'] = explode( ',', $queried['taxonomy'] );

				foreach ( $queried['taxonomy'] as $index => $taxonomy )
					if ( ! WordPress\Taxonomy::can( $taxonomy, 'assign_terms' ) )
						unset( $queried['taxonomy'][$index] );

				// again check if any left!
				if ( empty( $queried['taxonomy'] ) )
					return RestAPI::getErrorForbidden();

				$response = self::_get_select2_terms( $queried );
				break;

			case 'user':

				if ( ! current_user_can( 'list_users' ) )
					return RestAPI::getErrorForbidden();

				$response = self::_get_select2_users( $queried );
				break;

			default:

				return RestAPI::getErrorSomethingIsWrong();
		}

		return is_wp_error( $response ) ? $response : new \WP_REST_Response( $response, 200 );
	}

	private static function _get_select2_posts( $queried )
	{
		$args  = [
			'post_type'      => $queried['posttype'],
			'posts_per_page' => $queried['per'],
			'offset'         => ( $queried['page'] - 1 ) * $queried['per'],

			// 'no_found_rows'          => TRUE, // needed for pagination
			'suppress_filters'       => TRUE,
			'ignore_sticky_posts'    => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'update_menu_item_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
			'fields'                 => 'ids',
			'search_columns'         => [ 'post_title' ], // @since WP 6.2.0
		];

		if ( ! empty( $queried['search'] ) )
			$args['s'] = trim( $queried['search'] );

		if ( ! empty( $queried['include'] ) )
			$args['post__in'] = self::ids( $queried['include'] );

		if ( ! empty( $queried['exclude'] ) )
			$args['post__not_in'] = self::ids( $queried['exclude'] );

		if ( ! empty( $queried['status'] ) )
			$args['post_status'] = trim( $queried['status'] );

		else
			$args['post_status'] = WordPress\Status::available( $args['post_type'] );

		// NOTE: must return single or array of post ids
		$pre = apply_filters( sprintf( '%s_searchselect_pre_query_posts', static::BASE ), NULL, $args, $queried );

		if ( is_wp_error( $pre ) )
			return $pre;

		if ( FALSE === $pre )
			return RestAPI::getErrorSomethingIsWrong();

		if ( is_null( $pre ) ) {

			if ( ! empty( $args['s'] ) && ! WordPress\IsIt::compatWP( '6.2.0' ) ) // @since WP 6.2.0
				AdvancedQueries::hookSearchPostTitleOnly();

			$query = new \WP_Query();
			$posts = $query->query( $args );
			$found = $query->found_posts;

			AdvancedQueries::hookSearchPostTitleOnly( TRUE );

		} else if ( is_numeric( $pre ) ) {

			$posts = [ $pre ];
			$found = 1;

		} else if ( is_array( $pre ) ) {

			$posts = $pre;
			$found = count( $pre );
		}

		$results = [];

		foreach ( $posts as $post )
			$results[] = (object) [
				'id'    => $post,
				'text'  => WordPress\Post::title( $post ),
				'extra' => self::getExtraForPost( $post, $queried ),
				'image' => self::getImageForPost( $post, $queried ),
			];

		return [
			'results'    => $results,
			'pagination' => [
				'more' => ( $found - $args['posts_per_page'] ) > 0
			],
		];
	}

	// NOTE: also used by others!
	public static function getExtraForPost( $post, $queried = [], $default = [] )
	{
		return apply_filters( sprintf( '%s_searchselect_result_extra_for_post', static::BASE ), $default, $post, $queried );
	}

	// NOTE: also used by others!
	public static function getImageForPost( $post, $queried = [], $default = '' )
	{
		return apply_filters( sprintf( '%s_searchselect_result_image_for_post', static::BASE ), $default, $post, $queried );
	}

	private static function _get_select2_terms( $queried )
	{
		$args = [
			'taxonomy' => $queried['taxonomy'],
			'number'   => $queried['per'],
			'offset'   => ( $queried['page'] - 1 ) * $queried['per'],
			'orderby'  => 'term_id', // NOTE: latest created first
			'order'    => 'DESC',
			'fields'   => 'all', // 'id=>name',

			'hide_empty'             => FALSE,
			'update_term_meta_cache' => FALSE,
			'suppress_filters'       => TRUE,
		];

		if ( ! empty( $queried['search'] ) )
			$args['name__like'] = trim( $queried['search'] );

		if ( ! empty( $queried['include'] ) )
			$args['include'] = self::ids( $queried['include'] );

		if ( ! empty( $queried['exclude'] ) )
			$args['exclude'] = self::ids( $queried['exclude'] );

		// NOTE: Must return single or array of term objects/ids.
		// NOTE: If it's array, will handle duplicates.
		$pre = apply_filters( sprintf( '%s_searchselect_pre_query_terms', static::BASE ), NULL, $args, $queried );

		if ( is_wp_error( $pre ) )
			return $pre;

		if ( FALSE === $pre )
			return RestAPI::getErrorSomethingIsWrong();

		if ( is_null( $pre ) ) {

			$query   = new \WP_Term_Query();
			$terms   = $query->query( $args );
			$found   = count( $terms );
			$results = [];

			foreach ( $terms as $term )
				$results[] = (object) [
					'id'    => $term->term_id,
					'text'  => WordPress\Term::title( $term ),
					'extra' => self::getExtraForTerm( $term, $queried ),
					'image' => self::getImageForTerm( $term, $queried ),
				];

		} else if ( empty( $pre ) ) {

			$results = [];
			$found   = 0;

		} else if ( is_numeric( $pre ) || $pre instanceof \WP_Term ) {

			$found   = 1;
			$results = [ (object) [
				'id'    => is_object( $pre ) ? $pre->term_id : $pre,
				'text'  => WordPress\Term::title( $pre ),
				'extra' => self::getExtraForTerm( $pre, $queried ),
				'image' => self::getImageForTerm( $pre, $queried ),
			] ];

		} else if ( is_array( $pre ) ) {

			// TODO: slice results
			// TODO: apply `page` on results

			$results = [];
			$added   = [];

			foreach ( $pre as $term ) {

				$term_id = is_object( $term ) ? $term->term_id : $term;

				// avoid duplicates
				if ( in_array( $term_id, $added, TRUE ) )
					continue;

				$result = [
					'id'    => $term_id,
					'text'  => WordPress\Term::title( $term ),
					'extra' => self::getExtraForTerm( $term, $queried ),
					'image' => self::getImageForTerm( $term, $queried ),
				];

				$added[]   = $term_id;
				$results[] = (object) $result;
			}

			// WTF?!: count must be all not paged results.
			$found = count( $added );

		} else {

			// WTF?!: `$pre` is `string`/`object`

			$results = [];
			$found   = 0;
		}

		return [
			'results'    => $results,
			'pagination' => [
				// 'more' => ( $found - $args['number'] ) > 0
				'more' => $found >= $args['number']
			],
		];
	}

	// NOTE: also used by others!
	public static function getExtraForTerm( $term, $queried = [], $default = [] )
	{
		return apply_filters( sprintf( '%s_searchselect_result_extra_for_term', static::BASE ), $default, $term, $queried );
	}

	// NOTE: also used by others!
	public static function getImageForTerm( $term, $queried = [], $default = '' )
	{
		return apply_filters( sprintf( '%s_searchselect_result_image_for_term', static::BASE ), $default, $term, $queried );
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

		if ( ! empty( $queried['exclude'] ) )
			$args['exclude'] = self::ids( $queried['exclude'] );

		if ( ! empty( $queried['role'] ) && 'all' !== trim( $queried['role'] ) )
			$args['role__in'] = array_diff( explode( ',', $queried['role'] ), $args['role__not_in'] );

		if ( ! empty( $queried['search'] ) )
			$args['search'] = trim( $queried['search'] );

		// NOTE: must return single or array of user ids
		$pre = apply_filters( sprintf( '%s_searchselect_pre_query_users', static::BASE ), NULL, $args, $queried );

		if ( is_wp_error( $pre ) )
			return $pre;

		if ( FALSE === $pre )
			return RestAPI::getErrorSomethingIsWrong();

		if ( is_null( $pre ) ) {

			$query   = new \WP_User_Query( $args );
			$results = [];

			foreach ( (array) $query->get_results() as $user )
				$results[] = (object) [
					'id'   => $user->ID,
					'text' => WordPress\User::getTitleRow( $user ),
				];

			$found = $query->found_posts;

		} else if ( is_numeric( $pre ) ) {

			$found   = 1;
			$results = [ (object) [
				'id'   => $pre,
				'text' => WordPress\User::getTitleRow( $pre ),
			] ];

		} else if ( is_array( $pre ) ) {

			$results = [];
			$found   = count( $pre );

			foreach ( $pre as $user )
				$results[] = (object) [
					'id'   => $user,
					'text' => WordPress\User::getTitleRow( $user ),
				];
		}

		return [
			'results'    => $results,
			'pagination' => [
				'more' => ( $found - $args['number'] ) > 0
			],
		];
	}

	public static function enqueueSelect2( $extra = [] )
	{
		static $enqueued = FALSE;

		if ( $enqueued )
			return $enqueued;

		$args = self::recursiveParseArgs( $extra, [
			// 'settings' => [],
			'strings' => [
				'placeholder'     => _x( 'Select an item …', 'Service: SearchSelect', 'geditorial' ),
				'loadingmore'     => _x( 'Loading more results …', 'Service: SearchSelect', 'geditorial' ),
				'searching'       => _x( 'Searching …', 'Service: SearchSelect', 'geditorial' ),
				'noresults'       => _x( 'No results found', 'Service: SearchSelect', 'geditorial' ),
				'removeallitems'  => _x( 'Remove all items', 'Service: SearchSelect', 'geditorial' ),
				'removeitem'      => _x( 'Remove item', 'Service: SearchSelect', 'geditorial' ),
				'search'          => _x( 'Search', 'Service: SearchSelect', 'geditorial' ),
				'errorloading'    => _x( 'The results could not be loaded.', 'Service: SearchSelect', 'geditorial' ),
				/* translators: `%s`: number of characters */
				'inputtooshort'   => _x( 'Please enter %s or more characters', 'Service: SearchSelect', 'geditorial' ),
				/* translators: `%s`: number of characters */
				'inputtoolong'    => _x( 'Please delete %s character(s)', 'Service: SearchSelect', 'geditorial' ),
				/* translators: `%s`: number of items */
				'maximumselected' => _x( 'You can only select %s item(s)', 'Service: SearchSelect', 'geditorial' ),
			],
		] );

		if ( ! array_key_exists( '_rest', $args ) )
			$args['_rest'] = sprintf( '/%s', self::namespace() );

		if ( ! array_key_exists( '_nonce', $args ) && is_user_logged_in() )
			$args['_nonce'] = wp_create_nonce( 'searchselect' );

		gEditorial()->enqueue_asset_config( $args, 'searchselect' );

		return $enqueued = gEditorial\Scripts::enqueue( 'all.searchselect.select2', [
			'jquery',
			gEditorial\Scripts::pkgSelect2( TRUE ),
		] );
	}
}
