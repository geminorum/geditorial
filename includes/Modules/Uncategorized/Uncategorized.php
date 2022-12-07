<?php namespace geminorum\gEditorial\Modules\Uncategorized;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;

class Uncategorized extends gEditorial\Module
{

	protected $disable_no_taxonomies = TRUE;
	protected $textdomain_frontend   = FALSE;

	public static function module()
	{
		return [
			'name'  => 'uncategorized',
			'title' => _x( 'Uncategorized', 'Modules: Uncategorized', 'geditorial' ),
			'desc'  => _x( 'Term Leftover Management', 'Modules: Uncategorized', 'geditorial' ),
			'icon'  => 'hammer',
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles( [ 'administrator', 'subscriber' ] );

		return [
			'taxonomies_option' => 'taxonomies_option',
			'_roles' => [
				[
					'field'       => 'reports_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Reports Roles', 'Setting Title', 'geditorial-uncategorized' ),
					'description' => _x( 'Roles that can see Uncategorized Reports.', 'Setting Description', 'geditorial-uncategorized' ),
					'values'      => $roles,
				],
			],
		];
	}

	protected function taxonomies_excluded()
	{
		return $this->filters( 'taxonomies_excluded', Settings::taxonomiesExcluded( [
			'system_tags',
			'nav_menu',
			'post_format',
			'link_category',
			'bp_member_type',
			'bp_group_type',
			'bp-email-type',
			'ef_editorial_meta',
			'following_users',
			'ef_usergroup',
			'post_status',
			'rel_people',
			'rel_post',
			'affiliation',
			'specs',
		] ) );
	}

	public function current_screen( $screen )
	{
		if ( 'dashboard' == $screen->base && current_user_can( 'edit_others_posts' ) )
			$this->filter( 'dashboard_pointers', 1, 10, FALSE, 'gnetwork' );
	}

	// override
	public function cuc( $context = 'settings', $fallback = '' )
	{
		return 'reports' == $context ? $this->role_can( 'reports' ) : parent::cuc( $context, $fallback );
	}

	// already cap checked!
	public function dashboard_pointers( $items )
	{
		if ( ! $count = $this->_get_post_count() )
			return $items;

		/* translators: %s: posts count */
		$noopd = _nx_noop( '%s Uncategorized Post', '%s Uncategorized Posts', 'Noop', 'geditorial-uncategorized' );
		$can   = $this->role_can( 'reports' );

		$items[] = HTML::tag( $can ? 'a' : 'span', [
			'href'  => $can ? $this->get_module_url( 'reports' ) : FALSE,
			'title' => _x( 'You need to assign categories to some posts!', 'Title Attr', 'geditorial-uncategorized' ),
			'class' => '-uncategorized-count',
		], sprintf( Helper::noopedCount( $count, $noopd ), Number::format( $count ) ) );

		return $items;
	}

	public function reports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'reports' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'reports', $sub );

				$count = 0;

				if ( Tablelist::isAction( 'clean_uncategorized', TRUE ) ) {

					foreach ( $_POST['_cb'] as $post_id ) {

						if ( ! $post = PostType::getPost( $post_id ) )
							continue;

						$taxonomies = get_object_taxonomies( $post );

						foreach ( $this->taxonomies() as $taxonomy ) {

							if ( ! in_array( $taxonomy, $taxonomies ) )
								continue;

							if ( ! $default = Taxonomy::getDefaultTermID( $taxonomy ) )
								continue;

							$terms = wp_get_object_terms( $post->ID, $taxonomy, [ 'fields' => 'ids' ] );
							$diff  = Arraay::prepNumeral( array_diff( $terms, [ $default ] ) );

							// keep default if empty
							if ( empty( $diff ) )
								continue;

							$results = wp_set_object_terms( $post->ID, $diff, $taxonomy );

							if ( ! self::isError( $results ) )
								$count++;
						}
					}

					if ( $count ) {

						// delete pointer's cache
						delete_transient( $this->_get_count_cache_key() );

						WordPress::redirectReferer( [
							'message' => 'cleaned',
							'count'   => $count,
						] );
					}
				}

				WordPress::redirectReferer( 'nochange' );
			}
		}
	}

	protected function render_reports_html( $uri, $sub )
	{
		$query = [ 'tax_query' => $this->_get_uncategorized_tax_query() ];

		list( $posts, $pagination ) = Tablelist::getPosts( $query, [], 'any', $this->get_sub_limit_option( $sub ) );

		$pagination['actions']['clean_uncategorized'] = _x( 'Clean Uncategorized', 'Table Action', 'geditorial-uncategorized' );

		$pagination['before'][] = Tablelist::filterPostTypes();
		$pagination['before'][] = Tablelist::filterAuthors();
		$pagination['before'][] = Tablelist::filterSearch();

		HTML::tableList( [
			'_cb'   => 'ID',
			'ID'    => Tablelist::columnPostID(),
			'date'  => Tablelist::columnPostDate(),
			'type'  => Tablelist::columnPostType(),
			'title' => Tablelist::columnPostTitle(),
			'terms' => Tablelist::columnPostTerms(),
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of Posts in Uncategorized Terms', 'Header', 'geditorial-uncategorized' ) ),
			'empty'      => $this->get_posttype_label( 'post', 'not_found' ),
			'pagination' => $pagination,
		] );
	}

	private function _get_uncategorized_tax_query( $taxonomies = NULL )
	{
		if ( is_null ( $taxonomies ) )
			$taxonomies = $this->taxonomies();

		$tax_query = [ 'relation' => 'OR' ];

		foreach ( $taxonomies as $taxonomy )
			if ( $default = Taxonomy::getDefaultTermID( $taxonomy ) )
				$tax_query[] = [
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => [ (int) $default ],
				];

		return $tax_query;
	}

	private function _get_count_cache_key( $taxonomies = NULL )
	{
		if ( is_null ( $taxonomies ) )
			$taxonomies = $this->taxonomies();

		return $this->hash( 'uncategorizedcount', $taxonomies );
	}

	private function _get_post_count()
	{
		$taxonomies = $this->taxonomies();
		$cache_key  = $this->_get_count_cache_key( $taxonomies );

		if ( WordPress::isFlush( 'edit_others_posts' ) )
			delete_transient( $cache_key );

		if ( FALSE === ( $count = get_transient( $cache_key ) ) ) {

			$args = [
				'tax_query'      => $this->_get_uncategorized_tax_query( $taxonomies ),
				'fields'         => 'ids',
				'post_type'      => 'any',
				'posts_per_page' => -1,

				'ignore_sticky_posts'    => TRUE,
				'suppress_filters'       => TRUE,
				'no_found_rows'          => TRUE,
				'update_post_meta_cache' => FALSE,
				'update_post_term_cache' => FALSE,
				'lazy_load_term_meta'    => FALSE,
			];

			$query = new \WP_Query();
			$count = count( $query->query( $args ) );

			set_transient( $cache_key, $count, 12 * HOUR_IN_SECONDS );
		}

		return $count;
	}
}
