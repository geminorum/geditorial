<?php namespace geminorum\gEditorial\Modules\Ranked;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Ranked extends gEditorial\Module
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
			'name'     => 'ranked',
			'title'    => _x( 'Ranked', 'Modules: Ranked', 'geditorial' ),
			'desc'     => _x( 'Ranking for Editorial Content', 'Modules: Ranked', 'geditorial' ),
			'icon'     => 'shield-alt',
			'access'   => 'beta',
			'keywords' => [
				'ranking',
			],
		];
	}

	protected function get_global_settings()
	{
		$terms = WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ) );
		$empty = $this->get_taxonomy_label( 'main_taxonomy', 'no_items_available', NULL, 'no_terms' );

		return [
			'posttypes_option' => 'posttypes_option',
			'_roles'           => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy', TRUE ),
			'_dashboard'       => [
				'dashboard_widgets',
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
			'main_taxonomy' => 'ranking',
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
				'main_taxonomy' => _n_noop( 'Ranking Definition', 'Ranking Definitions', 'geditorial-ranked' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'menu_name'            => _x( 'Content Rankings', 'Label: Menu Name', 'geditorial-ranked' ),
					'show_option_all'      => _x( 'Rankings', 'Label: Show Option All', 'geditorial-ranked' ),
					'show_option_no_items' => _x( '(Unranked)', 'Label: Show Option No Terms', 'geditorial-ranked' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['dashboard'] = [
			'current' => [ 'widget_title' => _x( 'Your Ranking Summary', 'Dashboard Widget Title', 'geditorial-ranked' ), ],
			'all'     => [ 'widget_title' => _x( 'Editorial Ranking Summary', 'Dashboard Widget Title', 'geditorial-ranked' ), ],
		];

		$strings['default_terms'] = [
			'main_taxonomy' => [
				'Publisher'          => _x( 'Publisher', 'Default Term', 'geditorial-ranked' ),
				'Editor-in-Chief'    => _x( 'Editor in Chief', 'Default Term', 'geditorial-ranked' ),
				'Editorial-Director' => _x( 'Editorial Director', 'Default Term', 'geditorial-ranked' ),
				'Managing-Editor'    => _x( 'Managing Editor', 'Default Term', 'geditorial-ranked' ),
				'Senior-Editor'      => _x( 'Senior Editor', 'Default Term', 'geditorial-ranked' ),
				'digital-producer'   => _x( 'Digital Producer', 'Default Term', 'geditorial-ranked' ),
				'copy-editor'        => _x( 'Copy Editor', 'Default Term', 'geditorial-ranked' ),
				'proofreader'        => _x( 'Proofreader', 'Default Term', 'geditorial-ranked' ),
				'executive'          => _x( 'Executive', 'Default Term', 'geditorial-ranked' ),
			],
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
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'options-general.php' );

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

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return 'reports' == $context
			? $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports', NULL, $fallback )
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
}
