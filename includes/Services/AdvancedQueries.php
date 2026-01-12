<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class AdvancedQueries extends gEditorial\Service
{
	const SEARCH_OPERATOR_OR  = '|';
	const SEARCH_OPERATOR_NOT = '!';
	const TAXONOMY_PROP       = 'search_titles';

	public static function setup()
	{
		add_action( 'pre_get_posts', [ __CLASS__, 'pre_get_posts' ], 1, 1 );
		add_filter( 'posts_search', [ __CLASS__, 'posts_search' ], 8, 2 );
		add_filter( 'terms_clauses', [ __CLASS__, 'terms_clauses' ], 8, 3 );

		// WORKING BUT DISABLED
		// add_filter( 'posts_where', [ __CLASS__, 'posts_where_metakey_like' ] );

		// add_action( 'pre_get_posts', [ __CLASS__, 'pre_get_posts_empty_compare' ] );

		if ( is_admin() )
			return;

		add_action( 'init', [ __CLASS__, 'init_late' ], 999 );
	}

	public static function init_late()
	{
		self::_init_term_search();
	}

	public static function pre_get_posts( $query )
	{
		if ( ! $query->is_main_query() )
			return;

		if ( $query->is_search && ( $search = $query->get( 's' ) ) )
			$query->set( 's', Core\Text::trim( $search ) );
	}

	// TODO: filter for search on sub-contents (comments)
	public static function posts_search( $search, $query )
	{
		global $wpdb;

		if ( ! $query->is_main_query() )
			return $search;

		if ( ! $query->is_search() || WordPress\Strings::isEmpty( $query->query_vars['s'] ) )
			return $search;

		// NOTE: filter value iterates on the entire data!
		// @hook `geditorial_posts_search_append_meta_backend`
		// @hook `geditorial_posts_search_append_meta_frontend`
		$filter = sprintf( '%s_posts_search_append_meta_%s', static::BASE, is_admin() ? 'backend' : 'frontend' );
		$meta   = [];

		foreach ( WordPress\Strings::getSeparated( $query->query_vars['s'], static::SEARCH_OPERATOR_OR ) as $criteria )
			if ( ! WordPress\Strings::isEmpty( $criteria ) )
				$meta = apply_filters( $filter,
					$meta,
					Core\Text::trimQuotes( $criteria ),
					$query->query_vars['post_type']
				);

		if ( ! count( $meta ) )
			return $search;

		$query = "SELECT post_id FROM {$wpdb->postmeta} WHERE ";
		$where = [];

		foreach ( $meta as $parts )
			// NOTE: Must check the exact string, if no third part available.
			if ( empty( $parts[2] ) )
				$where[] = $wpdb->prepare(
					"(meta_key = '%s' AND meta_value = '%s')",
					$parts[0],
					$parts[1]
				);

			else
				$where[] = $wpdb->prepare(
					"(meta_key = '%s' AND meta_value LIKE %s)",
					$parts[0],
					'%'.$wpdb->esc_like( $parts[1] ).'%'
				);

		$posts = Core\Arraay::prepNumeral( $wpdb->get_col( $query.implode( ' OR ', $where ) ) );

		if ( ! empty( $posts ) )
			$search = str_replace(
				')))',
				") OR ({$wpdb->posts}.ID IN (".implode( ',', $posts )."))))",
				$search
			);

		return $search;
	}

	public static function terms_clauses( $clauses, $taxonomies, $args )
	{
		global $wpdb;

		if ( empty( $args['search'] ) )
			return $clauses;

		if ( WordPress\Strings::isEmpty( $args['search'] ) )
			return $clauses;

		// NOTE: filter value iterates on the entire data!
		// @hook `geditorial_terms_search_append_meta_backend`
		// @hook `geditorial_terms_search_append_meta_frontend`
		$filter = sprintf( '%s_terms_search_append_meta_%s', static::BASE, is_admin() ? 'backend' : 'frontend' );
		$meta   = [];

		foreach ( WordPress\Strings::getSeparated( $args['search'], static::SEARCH_OPERATOR_OR ) as $criteria )
			if ( ! WordPress\Strings::isEmpty( $criteria ) )
				$meta = apply_filters( $filter,
					$meta,
					Core\Text::trimQuotes( $criteria ),
					$taxonomies,
					$args
				);

		if ( ! count( $meta ) )
			return $clauses;

		$query = "SELECT term_id FROM {$wpdb->termmeta} WHERE ";
		$where = [];

		foreach ( $meta as $parts )
			// NOTE: Must check the exact string, if no third part available.
			if ( empty( $parts[2] ) )
				$where[] = $wpdb->prepare(
					"(meta_key = '%s' AND meta_value = '%s')",
					$parts[0],
					$parts[1]
				);

			else
				$where[] = $wpdb->prepare(
					"(meta_key = '%s' AND meta_value LIKE %s)",
					$parts[0],
					'%'.$wpdb->esc_like( $parts[1] ).'%'
				);

		$terms = Core\Arraay::prepNumeral( $wpdb->get_col( $query.implode( ' OR ', $where ) ) );

		if ( ! empty( $terms ) )
			$clauses['where'] = str_replace(
				'))',
				") OR (t.term_id IN (".implode( ',', $terms ).")))",
				$clauses['where']
			);

		return $clauses;
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
	 * @param string $search
	 * @param \WP_Query $wp_query
	 * @return string
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
	 * @param object $query
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

	private static function _init_term_search()
	{
		if ( ! $taxonomies = self::getTaxonomies() )
			return FALSE;

		add_filter( 'posts_join',
			// @REF: https://stackoverflow.com/a/13493126
			static function ( $join, $wp_query ) use ( $taxonomies ) {

				global $wpdb;

				if ( $wp_query->is_search() ) {
					$join.= " INNER JOIN {$wpdb->term_relationships} ON {$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id ";
					$join.= " INNER JOIN {$wpdb->term_taxonomy} ON {$wpdb->term_taxonomy}.term_taxonomy_id = {$wpdb->term_relationships}.term_taxonomy_id ";
					$join.= " INNER JOIN {$wpdb->terms} ON {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id ";
				}

				return $join;
			}, 99, 2 );

		add_filter( 'posts_where',
			static function ( $where, $wp_query ) use ( $taxonomies ) {

				global $wpdb;

				if ( $wp_query->is_search() ) {

					foreach ( $taxonomies as $taxonomy ) {

						$taxonomy = $wpdb->prepare( '%s', $taxonomy );
						$clause   = $sep = '';

						foreach ( self::parseCriteria( $wp_query ) as $searched ) {
							$escaped = $wpdb->prepare( '%s', empty( $wp_query->query_vars['exact'] ) ? '%'.$searched.'%' : $searched );
							$clause.= $sep."( ( {$wpdb->term_taxonomy}.taxonomy LIKE {$taxonomy} ) AND ( {$wpdb->terms}.name LIKE {$escaped} ) ) ";
							$sep = ' AND ';
						}

						if ( ! empty( $clause ) )
							$where.= " OR ( {$clause} ) ";
					}
				}

				return $where;
			}, 99, 2 );

		add_filter( 'posts_groupby',
			// @REF: https://wordpress.stackexchange.com/a/5404
			static function ( $groupby, $wp_query ) use ( $taxonomies ) {

				global $wpdb;

				$bypostid = "{$wpdb->posts}.ID";

				if ( ! $wp_query->is_search() || Core\Text::has( $groupby, $bypostid ) )
					return $groupby;

				return empty( trim( $groupby ) )
					? $bypostid
					: $groupby.', '.$bypostid;
			}, 99, 2 );

		return TRUE;
	}

	public static function getTaxonomies()
	{
		return WordPress\Taxonomy::get( -1, [
			// 'show_ui'      => TRUE,
			// 'show_in_rest' => TRUE,
			'public'       => TRUE,
			'_builtin'     => FALSE,   // WTF?!

			self::TAXONOMY_PROP => TRUE,
		] );
	}

	/**
	 * Retrieves the list of search keywords from the `s` parameter.
	 * @source `se_get_search_terms()`
	 *
	 * @param object $wp_query
	 * @return array
	 */
	public static function parseCriteria( $wp_query )
	{
		if ( empty( $wp_query->query_vars['s'] ) )
			return [];

		// Added slashes screw with quote grouping when done early, so done later.
		$criteria = stripslashes( $wp_query->query_vars['s'] );

		if ( ! empty( $wp_query->query_vars['sentence'] ) )
			return [ $criteria ];

		// preg_match_all( '/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $criteria, $matches );
		preg_match_all( '/(".*?)("|$)|((?<=[\s",+])|^)[^\s",+]+/', $criteria, $matches );

		return array_filter( array_map(
			static function ( $value ) {
				// return trim( $value, "\"\'\n\r " );
				return Core\Text::trimQuotes( $value );
			},
			$matches[0]
		) );
	}
}
