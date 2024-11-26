<?php namespace geminorum\gEditorial\Modules\Equipped;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Equipped extends gEditorial\Module
{
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\DashboardSummary;
	use Internals\TemplateTaxonomy;

	// FIXME: proper way of report on tabloid/edit-posts

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'equipped',
			'title'    => _x( 'Equipped', 'Modules: Equipped', 'geditorial-admin' ),
			'desc'     => _x( 'Items of Equipment', 'Modules: Equipped', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'tools' ],
			'access'   => 'beta',
			'keywords' => [
				'equipment',
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
				'metabox_advanced',
				'selectmultiple_term' => [ NULL, TRUE ],
			],
			'_editlist' => [
				'admin_restrict',
				'auto_term_parents',
				'show_in_quickedit',
			],
			'_frontend' => [
				'contents_viewable',
				'show_in_navmenus',
			],
			'_units' => [
				'units_posttypes' => [ NULL, $this->get_settings_posttypes_parents() ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'equipment',
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
				'main_taxonomy' => _n_noop( 'Equipment Item', 'Equipment Items', 'geditorial-equipped' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'menu_name'            => _x( 'Content Equipments', 'Label: `menu_name`', 'geditorial-equipped' ),
					'extended_label'       => _x( 'Content Equipments', 'Label: `extended_label`', 'geditorial-equipped' ),
					'show_option_all'      => _x( 'Equipments', 'Label: `show_option_all`', 'geditorial-equipped' ),
					'show_option_no_items' => _x( '(Unequipped)', 'Label: `show_option_no_items`', 'geditorial-equipped' ),
				],
			],
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'units' => [
				'_supported' => [
					'shoe_size_eu' => [
						'title'       => _x( 'Shoe', 'Field Title', 'geditorial-equipped' ),
						'description' => _x( 'Size of the Shoe by European standards', 'Field Description', 'geditorial-equipped' ),
						'type'        => 'european_shoe',
						'data_unit'   => 'european',
						'icon'        => 'universal-access-alt',
						'order'       => 200,
					],
					'shirt_size_int' => [
						'title'       => _x( 'Shirt', 'Field Title', 'geditorial-equipped' ),
						'description' => _x( 'Size of the Shirt by International standards', 'Field Description', 'geditorial-equipped' ),
						'type'        => 'international_shirt',
						'data_unit'   => 'international',
						'icon'        => 'universal-access',
						'order'       => 200,
					],
					'pants_size_int' => [
						'title'       => _x( 'Pants', 'Field Title', 'geditorial-equipped' ),
						'description' => _x( 'Size of the Pants by International standards', 'Field Description', 'geditorial-equipped' ),
						'type'        => 'international_pants',
						'data_unit'   => 'international',
						'icon'        => 'universal-access',
						'order'       => 200,
					],
				],
			]
		];
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => $this->get_setting( 'metabox_advanced' ) ? NULL : FALSE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit' ),
			'show_in_nav_menus'  => (bool) $this->get_setting( 'show_in_navmenus' ),
			'show_in_menu'       => FALSE,
		], NULL, [
			'is_viewable'    => $this->get_setting( 'contents_viewable', TRUE ),
			'auto_parents'   => $this->get_setting( 'auto_term_parents', TRUE ),
			'custom_captype' => TRUE,
		] );

		$this->hook_taxonomy_tabloid_exclude_rendered( 'main_taxonomy' );
		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );
	}

	public function units_init()
	{
		$this->add_posttype_fields_supported( $this->get_setting_posttypes( 'units' ), NULL, 'TRUE', 'units' );
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
		}
	}

	public function admin_menu()
	{
		$this->_hook_menu_taxonomy( 'main_taxonomy', 'options-general.php' );
	}

	public function dashboard_widgets()
	{
		if ( ! $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) )
			return;

		$this->add_dashboard_widget(
			'term-summary',
			$this->get_taxonomy_label( 'main_taxonomy', 'extended_label' ),
			'refresh'
		);
	}

	public function render_widget_term_summary( $object, $box )
	{
		$this->do_dashboard_term_summary( 'main_taxonomy', $box );
	}

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return $this->_override_module_cuc_by_taxonomy( 'main_taxonomy', $context, $fallback );
	}

	public function template_include( $template )
	{
		return $this->get_setting( 'contents_viewable', TRUE )
			? $this->templatetaxonomy__include( $template, $this->constant( 'main_taxonomy' ) )
			: $template;
	}
}
