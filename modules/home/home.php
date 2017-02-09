<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialHome extends gEditorialModuleCore
{

	private $featured = array();

	public static function module()
	{
		return array(
			'name'  => 'home',
			'title' => _x( 'Home', 'Home Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Home Page Customized', 'Home Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'admin-home',
		);
	}

	protected function settings_help_tabs()
	{
		$tabs = gEditorialSettingsCore::settingsHelpContent( $this->module );

		$tabs[] = array(
			'id'       => 'geditorial-home-featured_content',
			'title'    => _x( 'Featured Content', 'Home Module: Help Tab Title', GEDITORIAL_TEXTDOMAIN ),
			'content'  => '<div class="-info"><p>Featured Content allows users to spotlight their posts and have them uniquely displayed by a theme. The content is intended to be displayed on a blog’s front page; by using the module consistently in this manner, users are given a reliable Featured Content experience on which they can rely even when switching themes.</p>
<pre>
add_theme_support( \'featured-content\', array(
	\'filter\'     => \'mytheme_get_featured_posts\',
	\'max_posts\'  => 20,
	\'post_types\' => array( \'post\', \'page\' ),
) );
</pre>
<p class="-from">Adopted from: <a href="https://jetpack.com/support/featured-content/" target="_blank">Jetpack Featured Content</a> by <a href="https://automattic.com/" target="_blank">Automattic</a></p></div>',
		);

		return $tabs;
	}

	public function settings_intro_after( $module )
	{
		if ( get_theme_support( 'featured-content' ) )
			gEditorialHTML::info( _x( 'Current theme supports Featured Contents', 'Home Module: Setting Section Notice', GEDITORIAL_TEXTDOMAIN ), TRUE );
		else
			gEditorialHTML::warning( _x( 'Current theme does not support Featured Contents', 'Home Module: Setting Section Notice', GEDITORIAL_TEXTDOMAIN ), TRUE );
	}

	public function settings_section_featured()
	{
		gEditorialSettingsCore::fieldSection(
			_x( 'Featured Content', 'Home Module: Setting Section Title', GEDITORIAL_TEXTDOMAIN )
		);
	}

	protected function get_global_settings()
	{
		return array(
			'posttypes_option' => 'posttypes_option',
			'_featured' => array(
				array(
					'field'       => 'featured_term',
					'type'        => 'text',
					'title'       => _x( 'Featured Term', 'Home Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Specify a term slug to use for theme-designated featured content area.', 'Home Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'placeholder' => 'featured-slug',
					'dir'         => 'ltr',
				),
				array(
					'field'       => 'featured_max',
					'type'        => 'number',
					'title'       => _x( 'Featured Max Count', 'Home Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'The maximum number of posts that a Featured Content area can contain.', 'Home Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => 15,
				),
				array(
					'field'       => 'featured_exclude',
					'title'       => _x( 'Exclude Featured Posts', 'Home Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Exclude featured contents on the main query.', 'Home Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'featured_hide',
					'title'       => _x( 'Hide Featured Term', 'Home Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Hide the term on the front-end.', 'Home Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
			),
		);
	}

	protected function get_global_constants()
	{
		return array(
			'featured_tax' => 'post_tag',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'settings' => array(
				'post_types_title' => _x( 'Front-end Posttypes', 'Home Module: Setting Info', GEDITORIAL_TEXTDOMAIN ),
				'post_types_after' => _x( 'Will include in front-end main query.', 'Home Module: Setting Info', GEDITORIAL_TEXTDOMAIN ),
			),
		);
	}

	public function init()
	{
		parent::init();

		$post_types   = $this->post_types();
		$featured_tax = $this->constant( 'featured_tax' );

		if ( ! is_admin() )
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 9 );

		if ( $this->setup_featured( $post_types, $featured_tax ) ) {

			add_filter( $this->featured['filter'], array( $this, 'get_featured_posts' ) );

			add_action( 'save_post', array( $this, 'delete_transient' ) );
			add_action( 'switch_theme', array( $this, 'delete_transient' ) );
			add_action( 'delete_'.$featured_tax, array( $this, 'delete_featured_tax' ), 10, 4 );

			if ( ! is_admin() && $this->get_setting( 'featured_hide', FALSE ) ) {
				add_filter( 'get_terms', array( $this, 'hide_featured_term' ), 10, 3 );
				add_filter( 'get_the_terms', array( $this, 'hide_the_featured_term' ), 10, 3 );
			}
		}
	}

	private function setup_featured( $post_types = array(), $tax = 'post_tag' )
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
			$support[0]['post_types'] = array_merge( array( 'post' ), (array) $support[0]['additional_post_types'] );
			unset( $support[0]['additional_post_types'] );
		}

		if ( ! isset( $support[0]['post_types'] ) )
			$support[0]['post_types'] = $post_types;

		unset( $support[0]['description'] );

		foreach ( $support[0]['post_types'] as $post_type )
			register_taxonomy_for_object_type( $tax, $post_type );

		return $this->featured = $support[0];
	}

	public function pre_get_posts( &$query )
	{
		if ( ! $query->is_main_query() )
			return;

		$post_types = $this->post_types();

		if ( count( $post_types ) ) {

			if ( $query->is_home() )
				$query->set( 'post_type', $post_types );

			else if ( $query->is_search() && empty( $query->query_vars['post_type'] ) )
				$query->set( 'post_type', $post_types );

			else if ( $query->is_archive() && empty( $query->query_vars['post_type'] ) )
				$query->set( 'post_type', $post_types );

			else if ( $query->is_feed() && empty( $query->query_vars['post_type'] ) )
				$query->set( 'post_type', $post_types );
		}

		if ( $query->is_home()
			&& $this->get_setting( 'featured_exclude', FALSE )
			&& 'posts' === get_option( 'show_on_front' ) ) {

			$ids = $this->get_featured_post_ids();

			if ( count( $ids ) ) {

				if ( $not = $query->get( 'post__not_in' ) )
					$ids = array_unique( array_merge( (array) $not, $ids ) );

				$query->set( 'post__not_in', $ids );
			}
		}
	}

	public function get_featured_posts()
	{
		$ids = $this->get_featured_post_ids();

		if ( empty( $ids ) )
			return array();

		return get_posts( array(
			'post_type'      => $this->featured['post_types'],
			'posts_per_page' => count( $ids ),
			'include'        => $ids,
		) );
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
					return apply_filters( 'featured_content_post_ids', array() );

		$featured = get_posts( array(
			'post_type'   => $this->featured['post_types'],
			'numberposts' => $this->featured['max_posts'],
			'tax_query'   => array(
				array(
					'field'    => 'term_id',
					'taxonomy' => $this->constant( 'featured_tax' ),
					'terms'    => $term->term_id,
				),
			),
		) );

		if ( ! $featured )
			return apply_filters( 'featured_content_post_ids', array() );

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
}
