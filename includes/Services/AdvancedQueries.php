<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class AdvancedQueries extends WordPress\Main
{

	// TODO: support pipe (|) as `or` operator in search criteria in admin/front

	const BASE = 'geditorial';

	public static function setup()
	{
		// WORKING BUT DISABLED
		// add_filter( 'posts_where', [ __CLASS__, 'posts_where_metakey_like' ] );

		// add_action( 'pre_get_posts', [ __CLASS__, 'pre_get_posts_empty_compare' ] );
	}

	// @REF: https://stackoverflow.com/a/64184587
	// @REF: https://regex101.com/r/drMN1X/2
	// 'meta_query' => [
    //     'relation' => 'AND',
    //     [
    //         'key' => 'course_{GEDITORIAL_METAKEY_LIKE_POSITION}_access_from',
    //         'key' => 'course_{GEDITORIAL_METAKEY_LIKE_POSITION}',
    //         'key' => '{GEDITORIAL_METAKEY_LIKE_POSITION}_access_from',
    //         'value' => [ $first_day, $last_day ],
    //         'type' => 'numeric',
    //         'compare' => 'BETWEEN'
    //     ],
    // ],
	public static function posts_where_metakey_like( $where )
	{
		return preg_replace(
			'/meta_key = \'([a-zA-Z1-9_]+)?{GEDITORIAL_METAKEY_LIKE_POSITION}([a-zA-Z1-9_]+)?\'/',
			'meta_key LIKE \'$1%$2\'',
			$where
		);
	}

	// @SEE: https://core.trac.wordpress.org/ticket/43867
	// NOTE: @since WP 6.2.3 we can use `'search_columns' => 'post_title'` on query args
	public static function hookSearchPostTitleOnly( $unhook = FALSE )
	{
		if ( $unhook )
			remove_filter( 'posts_search', [ __CLASS__, 'posts_search_posttitle_only' ], 10, 2 );

		else
			add_filter( 'posts_search', [ __CLASS__, 'posts_search_posttitle_only' ], 10, 2 );
	}

	/**
	 * Search SQL filter for matching against post title only.
	 *
	 * @source https://wordpress.stackexchange.com/a/11826
	 *
	 *
	 * @param   string      $search
	 * @param   WP_Query    $wp_query
	 */
	public static function posts_search_posttitle_only( $search, $wp_query )
	{
		global $wpdb;

		if ( ! empty( $search ) && ! empty( $wp_query->query_vars['search_terms'] ) ) {

			$q = $wp_query->query_vars;
			$n = ! empty( $q['exact'] ) ? '' : '%';

			$search = [];

			foreach ( (array) $q['search_terms'] as $term )
				$search[] = $wpdb->prepare( "$wpdb->posts.post_title LIKE %s", $n.$wpdb->esc_like( $term ).$n );

			if ( ! is_user_logged_in() )
				$search[] = "$wpdb->posts.post_password = ''";

			$search = ' AND '.implode( ' AND ', $search );
		}

		return $search;
	}

	/**
	 * Sets empty compare with `IN` on Meta Query
	 * This will set the value of the meta-query to [-1], if the value is empty.
	 * @source https://core.trac.wordpress.org/ticket/33341#comment:5
	 *
	 * @param  object $query
	 * @return void
	 */
	public static function pre_get_posts_empty_compare( &$query )
	{
		$the_meta_query = $query->get( 'meta_query' );

		if ( is_array( $the_meta_query ) ) {

			foreach ( $the_meta_query as $id => $meta_query ) {

				if ( isset( $meta_query['compare'] )
					&& isset( $meta_query ['value'] ) ) {

					if ( 'IN' === $meta_query['compare'] ) {

						if ( empty( $meta_query['value'] ) ) {

							$the_meta_query[$id]['value'] = [ -1 ];

							$query->set( 'meta_query', $the_meta_query );
						}
					}
				}
			}
		}
	}

	/**
	 * Complex WordPress meta query by start and end date (custom meta fields)
	 * Intended for use on the `pre_get_posts` hook.
	 * Caution; this makes the query very slow - several seconds - so should be
	 * implemented with some form of caching.
	 *
	 * `add_action( 'pre_get_posts', [ __CLASS__, 'onlyFutureEntries' ] );`
	 *
	 * @author mark@sayhello.ch 22.10.2019, based on code from 201 onwards
	 * @source https://gist.github.com/markhowellsmead/2bb4abdc8b718ee3e20c7cc4cf58be6b
	 */
	public static function onlyFutureEntries( $query )
	{
		if ( is_admin() )
			return;

		if ( isset( $query->query['post_type'] ) && $query->query['post_type'] === 'event' ) {

			$query->set( 'orderby', 'meta_value' );
			$query->set( 'order', 'ASC' );
			$query->set( 'meta_key', 'start_date' );

			$meta_query = (array) $query->get( 'meta_query' );

			$meta_query[] = [
				'relation' => 'OR',
				[
					// Start date and end date are empty
					'relation' => 'AND',
					[
						'relation' => 'OR',
						[
							'key'     => 'start_date',
							'value'   => '',
							'compare' => '='
						],
						[
							'key'     => 'start_date',
							'compare' => 'NOT EXISTS'
						]
					],
					[
						'relation' => 'OR',
						[
							'key'     => 'end_date',
							'value'   => '',
							'compare' => '='
						],
						[
							'key'     => 'end_date',
							'compare' => 'NOT EXISTS',
						]
					]
				],
				[
					// Start date >= today and end date empty
					'relation' => 'AND',
					[
						'key'     => 'start_date',
						'value'   => date( 'Y-m-d' ),
						'compare' => '>=',
						'type'    => 'DATE'
					],
					[
						'key'     => 'end_date',
						'value'   => '',
						'compare' => '='
					]
				],
				[
					// Start date <= today and end date >= today
					'relation' => 'AND',
					[
						'key'     => 'start_date',
						'value'   => date( 'Y-m-d' ),
						'compare' => '<=',
						'type'    => 'DATE'
					],
					[
						'key'     => 'end_date',
						'value'   => date( 'Y-m-d' ),
						'compare' => '>=',
						'type'    => 'DATE'
					]
				]
			];

			$query->set( 'meta_query', $meta_query );
		}
	}
}
