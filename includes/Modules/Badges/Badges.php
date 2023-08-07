<?php namespace geminorum\gEditorial\Modules\Badges;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Template;
use geminorum\gEditorial\WordPress;

class Badges extends gEditorial\Module
{
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\DashboardSummary;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'   => 'badges',
			'title'  => _x( 'Badges', 'Modules: Badges', 'geditorial' ),
			'desc'   => _x( 'Editorial Content Badges', 'Modules: Badges', 'geditorial' ),
			'icon'   => 'superhero',
			'access' => 'beta',
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
				'summary_excludes' => [ NULL, $terms, $empty ],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_frontend' => [
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

		if ( ! is_admin() )
			return $strings;

		$strings['dashboard'] = [
			'current' => [ 'widget_title' => _x( 'Your Content Badges Summary', 'Dashboard Widget Title', 'geditorial-badges' ), ],
			'all'     => [ 'widget_title' => _x( 'Editorial Badges Summary', 'Dashboard Widget Title', 'geditorial-badges' ), ],
		];

		return $strings;
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
			'show_in_menu'       => FALSE,
			'meta_box_cb'        => '__checklist_restricted_terms_callback',
		], NULL, TRUE );

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

			$this->filter_string( 'parent_file', 'options-general.php' );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit' == $screen->base ) {
				$this->corerestrictposts__hook_screen_taxonomies( 'main_taxonomy', 'reports' );
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

	// override
	public function cuc( $context = 'settings', $fallback = '' )
	{
		return 'reports' == $context
			? $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' )
			: parent::cuc( $context, $fallback );
	}

	protected function dashboard_widgets()
	{
		if ( ! $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) )
			return;

		$this->add_dashboard_widget( 'term-summary', NULL, 'refresh' );
	}

	public function render_widget_term_summary( $object, $box )
	{
		$this->do_dashboard_term_summary( 'main_taxonomy', $box );
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
			$args['before'].= $this->get_rendred_badges( $post );

		return $args;
	}

	public function get_rendred_badges( $post = NULL, $badges = NULL, $fallback = '' )
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
			$image = WordPress\Media::htmlAttachmentSrc( WordPress\Taxonomy::getThumbnailID( $badge->term_id ) );

			$tokens = [
				'term_name'    => $name,
				'term_name_br' => str_replace( ' ', '<br>', $name ),
				'term_slug'    => $slug,
				'term_link'    => get_term_link( $badge ),
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
		$this->check_settings( $sub, 'reports' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		Core\HTML::h3( _x( 'Badge Reports', 'Header', 'geditorial-badges' ) );

		$taxonomy = $this->constant( 'main_taxonomy' );

		if ( ! WordPress\Taxonomy::hasTerms( $taxonomy ) )
			return Core\HTML::desc( _x( 'There are no badges available!', 'Setting', 'geditorial-badges' ), TRUE, '-empty' );

		echo Template::getSpanTiles( [
			'taxonomy' => $taxonomy,
			'posttype' => $this->posttypes(),
		], $this->key );
	}
}
