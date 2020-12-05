<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;

class Home extends gEditorial\Module
{

	protected $textdomain_frontend = FALSE;

	private $featured = [];

	public static function module()
	{
		return [
			'name'  => 'home',
			'title' => _x( 'Home', 'Modules: Home', 'geditorial' ),
			'desc'  => _x( 'Customized Homepage', 'Modules: Home', 'geditorial' ),
			'icon'  => 'admin-home',
		];
	}

	protected function settings_help_tabs( $context = 'settings' )
	{
		$tabs = [
			[
				'id'      => $this->classs( 'featured-content' ),
				'title'   => _x( 'Featured Content', 'Help Tab Title', 'geditorial-home' ),
				'content' => '<div class="-info"><p>Featured Content allows users to spotlight their posts and have them uniquely displayed by a theme. The content is intended to be displayed on a blog’s front page; by using the module consistently in this manner, users are given a reliable Featured Content experience on which they can rely even when switching themes.</p>
<code><pre>
add_theme_support( \'featured-content\', [
	\'filter\'     => \'mytheme_get_featured_posts\',
	\'max_posts\'  => 20,
	\'post_types\' => [ \'post\', \'page\' ),
) );
</pre></code>
<p class="-from">Adopted from: <a href="https://jetpack.com/support/featured-content/" target="_blank">Jetpack Featured Content</a> by <a href="https://automattic.com/" target="_blank">Automattic</a></p></div>',
			],
		];

		return array_merge( $tabs, parent::settings_help_tabs( $context ) );
	}

	public function settings_intro()
	{
		if ( get_theme_support( 'featured-content' ) )
			echo HTML::info( _x( 'Current theme supports Featured Contents', 'Setting Section Notice', 'geditorial-home' ), FALSE );
		else
			echo HTML::warning( _x( 'Current theme does not support Featured Contents', 'Setting Section Notice', 'geditorial-home' ), FALSE );
	}

	public function settings_section_featured()
	{
		Settings::fieldSection(
			_x( 'Featured Content', 'Setting Section Title', 'geditorial-home' )
		);
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
				[
					'field'       => 'posttypes_feed',
					'title'       => _x( 'Posttypes on Feeds', 'Setting Title', 'geditorial-home' ),
					'description' => _x( 'Whether to appear supported posttypes on the main feeds of the site.', 'Setting Description', 'geditorial-home' ),
				],
				[
					'field'       => 'exclude_search',
					'type'        => 'posttypes',
					'title'       => _x( 'Exclude from Search', 'Setting Title', 'geditorial-home' ),
					'description' => _x( 'Excludes selected posttypes from the search results.', 'Setting Description', 'geditorial-home' ),
					'default'     => get_post_types( [ 'exclude_from_search' => TRUE ] ),
				],
			],
			'_featured' => [
				[
					'field'       => 'featured_term',
					'type'        => 'text',
					'title'       => _x( 'Featured Term', 'Setting Title', 'geditorial-home' ),
					'description' => _x( 'Specify a term slug to use for theme-designated featured content area.', 'Setting Description', 'geditorial-home' ),
					'field_class' => [ 'medium-text', 'code' ],
					'placeholder' => 'featured-slug',
					'dir'         => 'ltr',
				],
				[
					'field'       => 'featured_max',
					'type'        => 'number',
					'title'       => _x( 'Featured Max Count', 'Setting Title', 'geditorial-home' ),
					'description' => _x( 'The maximum number of posts that a Featured Content area can contain.', 'Setting Description', 'geditorial-home' ),
					'default'     => 15,
				],
				[
					'field'       => 'featured_exclude',
					'title'       => _x( 'Exclude Featured Posts', 'Setting Title', 'geditorial-home' ),
					'description' => _x( 'Exclude featured contents on the main query.', 'Setting Description', 'geditorial-home' ),
				],
				[
					'field'       => 'featured_hide',
					'title'       => _x( 'Hide Featured Term', 'Setting Title', 'geditorial-home' ),
					'description' => _x( 'Hide the term on the front-end.', 'Setting Description', 'geditorial-home' ),
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'featured_tax' => 'post_tag',
		];
	}

	protected function get_global_strings()
	{
		return [
			'settings' => [
				'post_types_title' => _x( 'Front-end Posttypes', 'Setting Info', 'geditorial-home' ),
				'post_types_after' => _x( 'Will include in front-end main query.', 'Setting Info', 'geditorial-home' ),
			],
		];
	}

	public function init()
	{
		parent::init();

		$posttypes = $this->posttypes();
		$featured  = $this->constant( 'featured_tax' );

		if ( is_admin() ) {

			if ( count( $posttypes ) )
				$this->filter( 'dashboard_recent_posts_query_args' );

		} else {

			$this->action( 'pre_get_posts', 1, 9 );

			if ( count( $posttypes ) ) {
				add_filter( 'gpersiandate_calendar_posttypes', [ $this, 'calendar_posttypes' ] );
				add_filter( 'gnetwork_search_404_posttypes', [ $this, 'search_404_posttypes' ] );

				$this->filter( 'widget_posts_args' );
				$this->filter( 'widget_comments_args' );
			}
		}

		if ( $this->setup_featured( $posttypes, $featured ) ) {

			add_filter( $this->featured['filter'], [ $this, 'get_featured_posts' ] );

			add_action( 'save_post', [ $this, 'delete_transient' ] );
			add_action( 'switch_theme', [ $this, 'delete_transient' ] );
			add_action( 'delete_'.$featured, [ $this, 'delete_featured_tax' ], 10, 4 );

			if ( ! is_admin() && $this->get_setting( 'featured_hide', FALSE ) ) {
				add_filter( 'get_terms', [ $this, 'hide_featured_term' ], 10, 3 );
				add_filter( 'get_the_terms', [ $this, 'hide_the_featured_term' ], 10, 3 );
			}
		}
	}

	private function setup_featured( $posttypes = [], $tax = 'post_tag' )
	{
		if ( ! $this->get_setting( 'featured_term', '' ) )
			return FALSE;

		if ( ! $support = get_theme_support( 'featured-content' ) )
			return FALSE;

		// an array of args must be passed as the second parameter of add_theme_support()
		if ( ! isset( $support[0] ) )
			return FALSE;

		if ( isset( $support[0]['featured_content_filter'] ) ) {
			$support[0]['filter'] = $support[0]['featured_content_filter'];
			unset( $support[0]['featured_content_filter'] );
		}

		if ( ! isset( $support[0]['filter'] ) )
			return FALSE;

		if ( ! isset( $support[0]['max_posts'] ) )
			$support[0]['max_posts'] = absint( $this->get_setting( 'featured_max', 15 ) );

		if ( isset( $support[0]['additional_post_types'] ) ) {
			$support[0]['post_types'] = array_merge( [ 'post' ], (array) $support[0]['additional_post_types'] );
			unset( $support[0]['additional_post_types'] );
		}

		if ( ! isset( $support[0]['post_types'] ) )
			$support[0]['post_types'] = $posttypes;

		unset( $support[0]['description'] );

		foreach ( $support[0]['post_types'] as $posttype )
			register_taxonomy_for_object_type( $tax, $posttype );

		return $this->featured = $support[0];
	}

	// @SEE: https://developer.wordpress.org/reference/hooks/posts_where/#comment-3491
	public function pre_get_posts( &$wp_query )
	{
		if ( ! $wp_query->is_main_query()
			|| ( $wp_query->is_feed() && ! $this->get_setting( 'posttypes_feed', FALSE ) ) )
			return;

		$posttypes = $this->posttypes();
		$excluded  = $this->get_setting( 'exclude_search', [] );

		if ( count( $posttypes )
			&& ( $wp_query->is_home()
				|| ( empty( $wp_query->query_vars['post_type'] )
					&& ( $wp_query->is_archive() || $wp_query->is_feed() ) ) ) ) {

			$wp_query->set( 'post_type', $posttypes );
		}

		// @REF: https://stackoverflow.com/a/46373132
		if ( count( $excluded ) && $wp_query->is_search() && empty( $wp_query->query_vars['post_type'] ) ) {

			$diff = array_diff( get_post_types( [ 'exclude_from_search' => FALSE ] ), $excluded );

			if ( count( $diff ) )
				$wp_query->set( 'post_type', $diff );
		}

		if ( $wp_query->is_home()
			&& $this->get_setting( 'featured_exclude', FALSE )
			&& 'posts' === get_option( 'show_on_front' ) ) {

			$ids = $this->get_featured_post_ids();

			if ( count( $ids ) ) {

				if ( $not = $wp_query->get( 'post__not_in' ) )
					$ids = array_unique( array_merge( (array) $not, $ids ) );

				$wp_query->set( 'post__not_in', $ids );
			}
		}
	}

	public function get_featured_posts()
	{
		$ids = $this->get_featured_post_ids();

		if ( empty( $ids ) )
			return [];

		return get_posts( [
			'post_type'      => $this->featured['post_types'],
			'posts_per_page' => count( $ids ),
			'include'        => $ids,
		] );
	}

	public function get_featured_post_ids()
	{
		$featured_ids = get_transient( 'featured_content_ids' );

		if ( ! empty( $featured_ids ) )
			return array_map( 'absint',
				apply_filters( 'featured_content_post_ids', (array) $featured_ids ) );

		if ( ! $term = get_term_by( 'slug',
			$this->get_setting( 'featured_term', 'featured' ),
				$this->constant( 'featured_tax' ) ) )
					return apply_filters( 'featured_content_post_ids', [] );

		$featured = get_posts( [
			'post_type'   => $this->featured['post_types'],
			'numberposts' => $this->featured['max_posts'],
			'tax_query'   => [ [
				'field'    => 'term_id',
				'taxonomy' => $this->constant( 'featured_tax' ),
				'terms'    => $term->term_id,
			] ],
		] );

		if ( ! $featured )
			return apply_filters( 'featured_content_post_ids', [] );

		$featured_ids = wp_list_pluck( (array) $featured, 'ID' );
		$featured_ids = array_map( 'absint', $featured_ids );

		set_transient( 'featured_content_ids', $featured_ids );

		return apply_filters( 'featured_content_post_ids', $featured_ids );
	}

	public function delete_transient()
	{
		delete_transient( 'featured_content_ids' );
	}

	public function delete_featured_tax( $term, $tt_id, $deleted_term, $object_ids )
	{
		if ( is_wp_error( $deleted_term ) )
			return;

		if ( $deleted_term->slug == $this->get_setting( 'featured_term', '' ) )
			$this->update_option( 'featured_term', '' );
	}

	public function hide_featured_term( $terms, $taxonomies, $args )
	{
		if ( empty( $terms )
			|| 'all' != $args['fields']
			|| ! in_array( $this->constant( 'featured_tax' ), $taxonomies ) )
				return $terms;

		$slug = $this->get_setting( 'featured_term', 'featured' );

		foreach ( $terms as $order => $term )
			if ( is_object( $term ) && $term->slug == $slug )
				unset( $terms[$order] );

		return $terms;
	}

	public function hide_the_featured_term( $terms, $id, $taxonomy )
	{
		if ( empty( $terms )
			|| $taxonomy != $this->constant( 'featured_tax' ) )
				return $terms;

		$slug = $this->get_setting( 'featured_term', 'featured' );

		foreach ( $terms as $order => $term )
			if ( is_object( $term ) && $term->slug == $slug )
				unset( $terms[$order] );

		return $terms;
	}

	public function dashboard_recent_posts_query_args( $query_args )
	{
		if ( isset( $query_args['post_type'] ) && 'post' == $query_args['post_type'] )
			$query_args['post_type'] = $this->posttypes();

		return $query_args;
	}

	public function calendar_posttypes( $posttypes )
	{
		return $posttypes === [ 'post' ] ? $this->posttypes() : $posttypes;
	}

	public function search_404_posttypes( $posttypes )
	{
		return $this->posttypes();
	}

	public function widget_posts_args( $args )
	{
		if ( ! isset( $args['post_type'] ) )
			$args['post_type'] = $this->posttypes();

		return $args;
	}

	public function widget_comments_args( $args )
	{
		if ( ! isset( $args['post_type'] ) )
			$args['post_type'] = $this->posttypes();

		return $args;
	}
}
