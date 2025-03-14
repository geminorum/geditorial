<?php namespace geminorum\gEditorial\Modules\Execution;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\WordPress;

class Execution extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\BulkExports;
	use Internals\CoreAdmin;
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\CoreRowActions;
	use Internals\DashboardSummary;
	use Internals\FramePage;
	use Internals\MetaBoxSupported;
	use Internals\RestAPI;
	use Internals\SubContents;
	use Internals\TaxonomyOverview;
	use Internals\TemplateTaxonomy;

	protected $disable_no_posttypes = TRUE;

	// NOTE: `Executed` wording is not acceptable in some server environments
	public static function module()
	{
		return [
			'name'     => 'execution',
			'title'    => _x( 'Execution', 'Modules: Execution', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Executives', 'Modules: Execution', 'geditorial-admin' ),
			'icon'     => 'hammer',
			'access'   => 'beta',
			'keywords' => [
				'executive',
				'execution',
				'taxmodule',
				'subcontent',
			],
		];
	}

	protected function get_global_settings()
	{
		$terms = WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ) );
		$roles = $this->get_settings_default_roles();
		$empty = $this->get_taxonomy_label( 'main_taxonomy', 'no_items_available', NULL, 'no_terms' );

		return [
			'_subcontent' => [
				'subcontent_posttypes' => [ NULL, $this->get_settings_posttypes_parents() ],
				'subcontent_fields'    => [ NULL, $this->subcontent_get_fields_for_settings() ],
				'reports_roles'        => [ NULL, $roles ],
				'assign_roles'         => [ NULL, $roles ],
			],
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
				'metabox_advanced',
				'selectmultiple_term' => [ NULL, TRUE ],
			],
			'_editlist' => [
				'admin_restrict',
				'admin_rowactions',
				'auto_term_parents',
				'show_in_quickedit' => [ $this->get_taxonomy_show_in_quickedit_desc( 'main_taxonomy' ) ],
			],
			'_frontend' => [
				'contents_viewable',
				'show_in_navmenus',
				'tabs_support',
			],
			'_supports' => [
				'shortcode_support',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'executive',

			'restapi_namespace' => 'execution-data',
			'subcontent_type'   => 'execution',
			'subcontent_status' => 'private',
			'main_shortcode'    => 'execution-data',

			'term_empty_subcontent_data' => 'execution-data-empty',
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
				'main_taxonomy' => _n_noop( 'Executive', 'Executives', 'geditorial-execution' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'show_option_all'      => _x( 'Executives', 'Label: Show Option All', 'geditorial-execution' ),
					'show_option_no_items' => _x( '(No-executive)', 'Label: Show Option No Terms', 'geditorial-execution' ),
				],
			],
			'fields' => [
				'subcontent' => [
					'label'      => _x( 'Role', 'Field Label: `label`', 'geditorial-execution' ),
					'fullname'   => _x( 'Fullname', 'Field Label: `fullname`', 'geditorial-execution' ),
					'identity'   => _x( 'Identity', 'Field Label: `identity`', 'geditorial-execution' ),
					'phone'      => _x( 'Contact', 'Field Label: `phone`', 'geditorial-execution' ),
					'evaluation' => _x( 'Evaluation', 'Field Label: `evaluation`', 'geditorial-execution' ),
					'days'       => _x( 'Days', 'Field Label: `days`', 'geditorial-execution' ),
					'hours'      => _x( 'Hours', 'Field Label: `hours`', 'geditorial-execution' ),
					'desc'       => _x( 'Description', 'Field Label: `desc`', 'geditorial-execution' ),
				],
			],
		];

		$strings['frontend'] = [
			'tab_title' => _x( 'Executives', 'Tab Title', 'geditorial-execution' ),
		];

		$strings['notices'] = [
			'empty'    => _x( 'There is no execution information available!', 'Notice', 'geditorial-execution' ),
			'noaccess' => _x( 'You have not necessary permission to manage this execution data.', 'Notice', 'geditorial-execution' ),
		];

		if ( ! is_admin() )
			return $strings;

		$strings['settings'] = [
			'post_types_after' => _x( 'Supports executives for the selected post-types.', 'Setting Description', 'geditorial-execution' ),
		];

		$strings['metabox'] = [
			'supportedbox_title'  => _x( 'Executives', 'MetaBox Title', 'geditorial-execution' ),
			// 'metabox_action' => _x( 'Directory', 'MetaBox Action', 'geditorial-execution' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'mainbutton_title' => _x( 'Executives of %1$s', 'Button Title', 'geditorial-execution' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'mainbutton_text'  => _x( '%1$s Manage the Executives of %2$s', 'Button Text', 'geditorial-execution' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'rowaction_title' => _x( 'Executives of %1$s', 'Action Title', 'geditorial-execution' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'rowaction_text'  => _x( 'Executives', 'Action Text', 'geditorial-execution' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'columnrow_title' => _x( 'Executives of %1$s', 'Row Title', 'geditorial-execution' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'columnrow_text'  => _x( 'Executives', 'Row Text', 'geditorial-execution' ),
		];

		return $strings;
	}

	protected function subcontent_get_data_mapping()
	{
		return array_merge( $this->subcontent_base_data_mapping(), [
			'comment_content' => 'desc',    // `text`
			'comment_agent'   => 'label',   // `varchar(255)`
			'comment_karma'   => 'order',   // `int(11)`

			'comment_author'       => 'fullname',     // `tinytext`
			'comment_author_url'   => 'phone',        // `varchar(200)`
			'comment_author_email' => 'identity',     // `varchar(100)`
			'comment_author_IP'    => 'evaluation',   // `varchar(100)`
		] );
	}

	protected function subcontent_get_meta_mapping()
	{
		return [
			'days'   => 'days',
			'hours'  => 'hours',
			'postid' => '_post_ref',
		];
	}

	protected function subcontent_define_searchable_fields()
	{
		if ( $human = gEditorial()->constant( 'personage', 'primary_posttype' ) )
			return [ 'fullname' => [ $human ] ];

		return [];
	}

	protected function subcontent_define_unique_fields()
	{
		return [
			'identity',
		];
	}

	protected function subcontent_define_required_fields()
	{
		return [
			'fullname',
			'label',
		];
	}

	protected function posttypes_parents( $extra = [] )
	{
		return $this->filters( 'posttypes_parents', [
			'event',
			'course',
			'session'         ,   // `Symposium` Module
			'mission'         ,   // `Missioned` Module
			'program'         ,   // `Programmed` Module
			'meeting'         ,   // `Meeted` Module
			'listing'         ,   // `Listed` Module
			'training_course' ,   // `Trained` Module
			'shooting_session',   // `Ranged` Module
		] );
	}

	public function after_setup_theme()
	{
		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_menu'       => FALSE,
			'meta_box_cb'        => $this->get_setting( 'metabox_advanced' ) ? NULL : FALSE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit' ),
			'show_in_nav_menus'  => (bool) $this->get_setting( 'show_in_navmenus' ),
		], NULL, [
			'is_viewable'     => $this->get_setting( 'contents_viewable', TRUE ),
			'auto_parents'    => $this->get_setting( 'auto_term_parents', TRUE ),
			'single_selected' => ! $this->get_setting( 'selectmultiple_term', TRUE ),
			'custom_captype'  => TRUE,
		] );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );
		$this->bulkexports__hook_tabloid_term_assigned( 'main_taxonomy' );

		$this->filter_module( 'audit', 'auto_audit_save_post', 5, 12, 'subcontent' );
		$this->register_shortcode( 'main_shortcode' );
		$this->subcontent_hook__post_tabs();

		if ( ! is_admin() )
			return;

		$this->filter_module( 'tabloid', 'post_summaries', 4, 40, 'subcontent' );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'options-general.php' );
			$this->modulelinks__register_headerbuttons();
			$this->bulkexports__hook_supportedbox_for_term( 'main_taxonomy', $screen );
			$this->coreadmin__hook_taxonomy_multiple_supported_column( $screen );

		} else if ( in_array( $screen->base, [ 'edit', 'post' ], TRUE ) ) {

			if ( $this->posttype_supported( $screen->post_type ) ) {

				if ( 'edit' === $screen->base ) {

					if ( $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) )
						$this->corerestrictposts__hook_screen_taxonomies( 'main_taxonomy' );

				} else if ( 'post' === $screen->base ) {

					if ( ! $this->get_setting( 'metabox_advanced' ) )
						$this->hook_taxonomy_metabox_mainbox(
							'main_taxonomy',
							$screen->post_type,
							$this->get_setting( 'selectmultiple_term', TRUE )
								? '__checklist_restricted_terms_callback'
								: '__singleselect_restricted_terms_callback'
						);
				}

				if ( $this->in_setting_posttypes( $screen->post_type, 'subcontent' ) ) {

					if ( 'post' == $screen->base ) {

						if ( $this->role_can( [ 'reports', 'assign' ] ) )
							$this->_hook_general_supportedbox( $screen, NULL, 'advanced', 'low', '-subcontent-grid-metabox' );

						$this->subcontent_do_enqueue_asset_js( $screen );

					} else if ( 'edit' == $screen->base ) {

						if ( $this->role_can( [ 'reports', 'assign' ] ) ) {

							if ( ! $this->rowactions__hook_mainlink_for_post( $screen->post_type, 18, 'subcontent' ) )
								$this->coreadmin__hook_tweaks_column_row( $screen->post_type, 18, 'subcontent' );

							Scripts::enqueueColorBox();
						}
					}
				}
			}
		}
	}

	public function admin_menu()
	{
		if ( $this->role_can( [ 'assign', 'reports' ] ) )
			$this->_hook_submenu_adminpage( 'framepage', 'exist' );

		$this->_hook_menu_taxonomy( 'main_taxonomy', 'options-general.php' );
	}

	public function dashboard_widgets()
	{
		$this->add_dashboard_term_summary( 'main_taxonomy' );
	}

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return $this->_override_module_cuc_by_taxonomy( 'main_taxonomy', $context, $fallback );
	}

	public function load_framepage_adminpage( $context = 'framepage' )
	{
		$this->_load_submenu_adminpage( $context );
		$this->subcontent_do_enqueue_app();
	}

	public function render_framepage_adminpage()
	{
		$this->subcontent_do_render_iframe_content(
			'framepage',
			/* translators: `%s`: post title */
			_x( 'Executives Grid for %s', 'Page Title', 'geditorial-execution' ),
			/* translators: `%s`: post title */
			_x( 'Executives Overview for %s', 'Page Title', 'geditorial-execution' )
		);
	}

	public function setup_restapi()
	{
		$this->subcontent_restapi_register_routes();
	}

	public function template_include( $template )
	{
		return $this->get_setting( 'contents_viewable', TRUE )
			? $this->templatetaxonomy__include( $template, $this->constant( 'main_taxonomy' ) )
			: $template;
	}

	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		$this->subcontent_do_render_supportedbox_content( $object, $context ?? 'supportedbox' );
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return $this->subcontent_do_main_shortcode( $atts, $content, $tag );
	}

	public function audit_get_default_terms( $terms, $taxonomy )
	{
		return Helper::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_empty_subcontent_data' ) => _x( 'Empty Executives Data', 'Default Term: Audit', 'geditorial-execution' ),
		] ) : $terms;
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports', 'per_page' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->taxonomy_overview_render_table( 'main_taxonomy', $uri, $sub ) )
			return Info::renderNoReportsAvailable();
	}
}
