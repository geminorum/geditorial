<?php namespace geminorum\gEditorial\Modules\Badges;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Badges extends gEditorial\Module
{
	use Internals\BulkExports;
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\DashboardSummary;
	use Internals\TaxonomyOverview;
	use Internals\TemplateTaxonomy;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'   => 'badges',
			'title'  => _x( 'Badges', 'Modules: Badges', 'geditorial-admin' ),
			'desc'   => _x( 'Editorial Content Badges', 'Modules: Badges', 'geditorial-admin' ),
			'icon'   => [ 'misc-16', 'patch-question' ],
			'access' => 'beta',
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
			'_roles'           => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy', TRUE, TRUE, $terms, $empty ),
			'_dashboard'       => [
				'dashboard_widgets',
				'summary_parents',
				'summary_excludes' => [ NULL, $terms, $empty ],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_editpost' => [
				'selectmultiple_term' => [ NULL, TRUE ],
			],
			'_editlist' => [
				'show_in_quickedit',
			],
			'_frontend' => [
				'show_in_navmenus',
				'insert_content_enabled',
				'adminbar_summary',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'badge',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Badge', 'Badges', 'geditorial-badges' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'menu_name'            => _x( 'Content Badges', 'Label: Menu Name', 'geditorial-badges' ),
					'show_option_all'      => _x( 'Badges', 'Label: Show Option All', 'geditorial-badges' ),
					'show_option_no_items' => _x( '(Unbadged)', 'Label: Show Option No Terms', 'geditorial-badges' ),
				],
			],
		];

		return $strings;
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_menu'       => FALSE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit' ),
			'show_in_nav_menus'  => (bool) $this->get_setting( 'show_in_navmenus' ),
		], NULL, [
			'custom_captype'  => TRUE,
			'single_selected' => ! $this->get_setting( 'selectmultiple_term', TRUE ),
		] );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );

		if ( is_admin() )
			return;

		$this->filter( 'post_class', 3, 12 );

		if ( $this->get_setting( 'insert_content' ) )
			$this->filter( 'template_post_image_args', 4, 9, FALSE, $this->base );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->_hook_parentfile_for_optionsgeneralphp();
			$this->modulelinks__register_headerbuttons();

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit' == $screen->base ) {

				if ( $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) )
					$this->corerestrictposts__hook_screen_taxonomies( 'main_taxonomy' );

			} else if ( 'post' === $screen->base ) {

				$this->hook_taxonomy_metabox_mainbox(
					'main_taxonomy',
					$screen->post_type,
					$this->get_setting( 'selectmultiple_term', TRUE )
						? '__checklist_restricted_terms_callback'
						: '__singleselect_restricted_terms_callback'
				);
			}
		}
	}

	public function admin_menu()
	{
		$this->_hook_menu_taxonomy( 'main_taxonomy', 'options-general.php' );
	}

	public function template_redirect()
	{
		if ( is_robots() || is_favicon() || is_feed() )
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

	public function template_include( $template )
	{
		return $this->templatetaxonomy__include( $template, $this->constant( 'main_taxonomy' ) );
	}

	public function post_class( $classes, $css_class, $post_id )
	{
		if ( $this->posttype_supported( WordPress\Post::type( $post_id ) ) )
			$classes[] = $this->classs( 'supported' );

		return $classes;
	}

	public function template_post_image_args( $args, $post, $module, $title )
	{
		if ( empty( $args['before'] ) )
			$args['before'] = '';

		if ( $this->posttype_supported( $post->post_type ) )
			$args['before'].= $this->get_rendered_badges( $post );

		return $args;
	}

	public function get_rendered_badges( $post = NULL, $badges = NULL, $fallback = '' )
	{
		if ( is_null( $badges ) )
			$badges = $this->get_badges( $post );

		if ( ! $badges )
			return $fallback;

		$html     = '';
		$default  = '<span class="{{main_class}} {{term_class}}"><a href="{{term_link}}">{{{term_name_br}}}</a></span>';
		$template = $this->filters( 'term_template', $default, $post, $badges );

		foreach ( $badges as $badge ) {

			if ( empty( $badge ) )
				continue;

			$name  = sanitize_term_field( 'name', $badge->name, $badge->term_id, $badge->taxonomy, 'display' );
			$slug  = sanitize_term_field( 'slug', $badge->slug, $badge->term_id, $badge->taxonomy, 'display' );
			$image = WordPress\Media::getAttachmentSrc( WordPress\Taxonomy::getThumbnailID( $badge->term_id ) );

			$tokens = [
				'term_name'    => $name,
				'term_name_br' => str_replace( ' ', '<br>', $name ),
				'term_slug'    => $slug,
				'term_link'    => WordPress\Term::link( $badge ),
				'term_image'   => $image,
				'term_class'   => sprintf( '-badge-%s', $slug ),
				'main_class'   => '-badge',
			];

			$html.= Core\Text::replaceTokens( $template, $tokens );
		}

		return Core\HTML::wrap( $html, $this->classs() );
	}

	public function get_badges( $post = NULL )
	{
		return $this->filters( 'badges', WordPress\Taxonomy::getPostTerms( $this->constant( 'main_taxonomy' ), $post ), $post );
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports', 'per_page' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->taxonomy_overview_render_table( 'main_taxonomy', $uri, $sub ) )
			return gEditorial\Info::renderNoReportsAvailable();
	}
}
