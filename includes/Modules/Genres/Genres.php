<?php namespace geminorum\gEditorial\Modules\Genres;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Template;
use geminorum\gEditorial\WordPress;

class Genres extends gEditorial\Module
{
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\DashboardSummary;

	public static function module()
	{
		return [
			'name'     => 'genres',
			'title'    => _x( 'Genres', 'Modules: Genres', 'geditorial-admin' ),
			'desc'     => _x( 'Stylistic Categories', 'Modules: Genres', 'geditorial-admin' ),
			'icon'     => 'category',
			'access'   => 'beta',
			'keywords' => [
				'taxmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		$terms = WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ) );
		$empty = $this->get_taxonomy_label( 'main_taxonomy', 'no_items_available', NULL, 'no_terms' );

		return [
			'posttypes_option' => 'posttypes_option',
			'_roles'    => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy', FALSE, FALSE, $terms, $empty ),
			'_editpost' => [
				'metabox_advanced',
			],
			'_editlist' => [
				'auto_term_parents',
			],
			'_supports' => [
				'shortcode_support',
			],
			'_dashboard' => [
				'dashboard_widgets',
				'summary_parents',
				'summary_excludes' => [ NULL, $terms, $empty ],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy'  => 'genre',
			'main_shortcode' => 'genres',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'main_taxonomy' => NULL,
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Genre', 'Genres', 'geditorial-genres' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'extended_label'       => _x( 'Content Genres', 'Label: `extended_label`', 'geditorial-genres' ),
					'menu_name'            => _x( 'Content Genres', 'Label: `menu_name`', 'geditorial-genres' ),
					'show_option_all'      => _x( 'Genres', 'Label: `show_option_all`', 'geditorial-genres' ),
					'show_option_no_items' => _x( '(UnGenred)', 'Label: `show_option_no_items`', 'geditorial-genres' ),
				],
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'main_taxonomy' => [
				// @REF: https://en.wikipedia.org/wiki/List_of_writing_genres
				'children'   => _x( 'Children', 'Main Taxonomy: Default Term', 'geditorial-genres' ),
				'classic'    => _x( 'Classic', 'Main Taxonomy: Default Term', 'geditorial-genres' ),
				'comedy'     => _x( 'Comedy', 'Main Taxonomy: Default Term', 'geditorial-genres' ),
				'crime'      => _x( 'Crime', 'Main Taxonomy: Default Term', 'geditorial-genres' ),
				'fantasy'    => _x( 'Fantasy', 'Main Taxonomy: Default Term', 'geditorial-genres' ),
				'historical' => _x( 'Historical', 'Main Taxonomy: Default Term', 'geditorial-genres' ),
				'horror'     => _x( 'Horror', 'Main Taxonomy: Default Term', 'geditorial-genres' ),
				'mystery'    => _x( 'Mystery', 'Main Taxonomy: Default Term', 'geditorial-genres' ),
				'romance'    => _x( 'Romance', 'Main Taxonomy: Default Term', 'geditorial-genres' ),
				'sci-fi'     => _x( 'Science Fiction', 'Main Taxonomy: Default Term', 'geditorial-genres' ),
				'thriller'   => _x( 'Thriller', 'Main Taxonomy: Default Term', 'geditorial-genres' ),
			],
		];
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical' => TRUE,
			'show_in_menu' => FALSE,
			'meta_box_cb'  => $this->get_setting( 'metabox_advanced' ) ? NULL : '__checklist_terms_callback',
		], NULL, [
			'auto_parents' => $this->get_setting( 'auto_term_parents', TRUE ),
		] );

		$this->register_shortcode( 'main_shortcode' );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );

		if ( is_admin() )
			return;

		$this->filter( 'post_class', 3, 12 );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'options-general.php' );
			$this->modulelinks__register_headerbuttons();

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit' == $screen->base ) {

				if ( $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) )
					$this->corerestrictposts__hook_screen_taxonomies( 'main_taxonomy' );
			}
		}
	}

	public function admin_menu()
	{
		$this->_hook_menu_taxonomy( 'main_taxonomy', 'options-general.php' );
	}

	public function template_redirect()
	{
		if ( is_embed() )
			return;

		if ( ! is_singular( $this->posttypes() ) )
			return;

		$this->current_queried = get_queried_object_id();

		$this->enqueue_styles();
	}

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return $this->_override_module_cuc_by_taxonomy( 'main_taxonomy', $context, $fallback );
	}

	public function dashboard_widgets()
	{
		$this->add_dashboard_term_summary( 'main_taxonomy' );
	}

	public function post_class( $classes, $css_class, $post_id )
	{
		if ( $this->posttype_supported( WordPress\Post::type( $post_id ) ) )
			$classes[] = $this->classs( 'supported' );

		return $classes;
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return Shortcode::listPosts( 'assigned',
			'post',
			$this->constant( 'main_taxonomy' ),
			array_merge( [
				'post_id'   => NULL,
				'posttypes' => $this->posttypes(),
			], (array) $atts ),
			$content,
			$this->constant( 'main_shortcode' )
		);
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		Core\HTML::h3( _x( 'Genre Reports', 'Header', 'geditorial-genres' ) );

		$taxonomy = $this->constant( 'main_taxonomy' );

		if ( ! WordPress\Taxonomy::hasTerms( $taxonomy ) )
			return Core\HTML::desc( $this->get_taxonomy_label( 'main_taxonomy', 'no_items_available', NULL, 'no_terms' ), TRUE, '-empty' );

		echo Template::getSpanTiles( [
			'taxonomy' => $taxonomy,
			'posttype' => $this->posttypes(),
		], $this->key );
	}
}
