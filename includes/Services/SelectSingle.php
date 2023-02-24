<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Query;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\WordPress\Main;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;

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
		$query = self::atts( [
			'search'   => '',
			'target'   => '',
			'posttype' => '',
			'taxonomy' => '',
			'page'     => '1',
			'per'      => '10',
		], $request->get_query_params() );

		switch( $query['target'] ) {

			case 'post':

				if ( empty( $query['posttype'] ) )
					return new \WP_Error( 'no_correct_settings', esc_html_x( 'Something\'s wrong!', 'Service: SelectSingle: Error', 'geditorial' ) );

				if ( ! PostType::can( $query['posttype'], 'read_post' ) )
					return new \WP_Error( 'not_authorized', esc_html_x( 'Something\'s wrong!', 'Service: SelectSingle: Error', 'geditorial' ) );

				$response = self::_get_select2_posts( $query );
				break;

			case 'term':

				if ( empty( $query['taxonomy'] ) )
					return new \WP_Error( 'no_correct_settings', esc_html_x( 'Something\'s wrong!', 'Service: SelectSingle: Error', 'geditorial' ) );

				if ( ! Taxonomy::can( $query['taxonomy'], 'assign_terms' ) )
					return new \WP_Error( 'not_authorized', esc_html_x( 'Something\'s wrong!', 'Service: SelectSingle: Error', 'geditorial' ) );

				$response = self::_get_select2_terms( $query );
				break;

			default:

				return new \WP_Error( 'no_correct_settings', esc_html_x( 'Something\'s wrong!', 'Service: SelectSingle: Error', 'geditorial' ) );
		}

		return new \WP_REST_Response( $response, 200 );
	}

	// TODO: include/exclude by taxonomy terms
	private static function _get_select2_posts( $atts )
	{
		$args  = [
			'post_type'              => $atts['posttype'],
			'posts_per_page'         => 10,
			// 'no_found_rows'          => TRUE, // needs for pagination
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'update_menu_item_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
			'fields'                 => 'ids',
		];

		if ( ! empty( $atts['search'] ) )
			$args['s'] = trim( $atts['search'] );

		if ( ! empty( $atts['per'] ) )
			$args['posts_per_page'] = trim( $atts['per'] );

		if ( ! empty( $atts['status'] ) )
			$args['post_status'] = trim( $atts['status'] );
		else
			// use only with persistent cache
			// $args['post_status'] = Posttype::getAvailableStatuses( $args['post_type'] );
			$args['post_status'] = [ 'publish', 'future', 'draft' ];

		$results = [];

		Query::hookSearchPostTitleOnly();

		$query = new \WP_Query();
		$posts = $query->query( $args );

		foreach ( $posts as $post )
			$results[] = (object) [
				'id'   => $post,
				'text' => PostType::getPostTitle( $post ),
			];

		return [
			'results'    => $results,
			'pagination' => [
				'more' => ( $query->found_posts - $args['posts_per_page'] ) > 0
			],
		];
	}

	private static function _get_select2_terms( $atts )
	{
		$args = [
			'taxonomy' => $atts['taxonomy'],
			'number'   => $atts['per'],
			'offset'   => ( $atts['page'] - 1 ) * $atts['per'],
			'orderby'  => 'name',
			'fields'   => 'id=>name',

			'hide_empty'             => FALSE,
			'update_term_meta_cache' => FALSE,
			'suppress_filters'       => TRUE,
		];

		if ( ! empty( $atts['search'] ) )
			$args['name__like'] = trim( $atts['search'] );

		$results = [];

		$query = new \WP_Term_Query();
		$terms = $query->query( $args );

		foreach ( $terms as $term_id => $term_name )
			$results[] = (object) [
				'id'   => $term_id,
				'text' => $term_name,
			];

		return [
			'results'    => $results,
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
			$args['_rest'] = rest_url( self::namespace() );

		if ( ! array_key_exists( '_nonce', $args ) && is_user_logged_in() )
			$args['_nonce'] = wp_create_nonce( 'selectsingle' );

		gEditorial()->enqueue_asset_config( $args, '_selectsingle' );

		return $enqueued = Scripts::enqueue( 'all.selectsingle', [ 'jquery', Scripts::pkgSelect2() ] );
	}
}
