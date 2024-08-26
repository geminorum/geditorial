<?php namespace geminorum\gEditorial\Modules\Athlete;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Template;
use geminorum\gEditorial\WordPress;

class Athlete extends gEditorial\Module
{
	use Internals\AdminPage;
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
	use Internals\TemplateTaxonomy;

	public static function module()
	{
		return [
			'name'     => 'athlete',
			'title'    => _x( 'Athlete', 'Modules: Athlete', 'geditorial-admin' ),
			'desc'     => _x( 'Physical Skills Data', 'Modules: Athlete', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'lungs-fill' ],
			'access'   => 'beta',
			'keywords' => [
				'grade',
				'sport',
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
			'_roles'           => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy' ),
			'_dashboard'       => [
				'dashboard_widgets',
				'summary_parents',
				'summary_excludes' => [ NULL, $terms, $empty ],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_editlist' => [
				'admin_restrict',
				'auto_term_parents',
				'show_in_quickedit' => [ $this->get_taxonomy_show_in_quickedit_desc( 'main_taxonomy' ) ],
			],
			'_editpost' => [
				'admin_rowactions',
				'metabox_advanced',
				'selectmultiple_term' => [ NULL, TRUE ],
			],
			'_frontend' => [
				'contents_viewable',
				'show_in_navmenus',
			],
			'_supports' => [
				'shortcode_support',
			],
			'_units' => [
				'units_posttypes' => [ NULL, $this->get_settings_posttypes_parents() ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'sports_field',

			'restapi_namespace' => 'athletics-data',
			'subcontent_type'   => 'athletics_data',
			'subcontent_status' => 'private',
			'main_shortcode'    => 'athletics-data',

			'term_empty_subcontent_data' => 'athletics-data-empty',
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
				'main_taxonomy' => _n_noop( 'Sports Field', 'Sports Fields', 'geditorial-athlete' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'menu_name'            => _x( 'Sports', 'Label: Menu Name', 'geditorial-athlete' ),
					'show_option_all'      => _x( 'Sports Field', 'Label: Show Option All', 'geditorial-athlete' ),
					'show_option_no_items' => _x( '(Unknown)', 'Label: Show Option No Terms', 'geditorial-athlete' ),
				],
			],
			'fields' => [
				'subcontent' => [
					'label'    => _x( 'Event', 'Field Label: `label`', 'geditorial-athlete' ),
					'grade'    => _x( 'Grade', 'Field Label: `grade`', 'geditorial-athlete' ),
					'age'      => _x( 'Age', 'Field Label: `age`', 'geditorial-athlete' ),
					'stature'  => _x( 'Stature', 'Field Label: `stature`', 'geditorial-athlete' ),
					'mass'     => _x( 'Mass', 'Field Label: `mass`', 'geditorial-athlete' ),
					'date'     => _x( 'Date', 'Field Label: `date`', 'geditorial-athlete' ),
					'location' => _x( 'Venue', 'Field Label: `location`', 'geditorial-athlete' ),
					'people'   => _x( 'Instructors', 'Field Label: `location`', 'geditorial-athlete' ),
					'desc'     => _x( 'Description', 'Field Label: `desc`', 'geditorial-athlete' ),
				],
			],
		];

		$strings['notices'] = [
			'empty'    => _x( 'There is no athletic information available!', 'Notice', 'geditorial-athlete' ),
			'noaccess' => _x( 'You have not necessary permission to manage this athletic data.', 'Notice', 'geditorial-athlete' ),
		];

		if ( ! is_admin() )
			return $strings;

		$strings['settings'] = [
			'post_types_after' => _x( 'Supports sports fields for the selected post-types.', 'Settings Description', 'geditorial-athlete' ),
		];

		$strings['metabox'] = [
			'supportedbox_title'  => _x( 'Athletics', 'MetaBox Title', 'geditorial-athlete' ),
			// 'metabox_action' => _x( 'Directory', 'MetaBox Action', 'geditorial-athlete' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'mainbutton_title' => _x( 'Athletics of %1$s', 'Button Title', 'geditorial-athlete' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'mainbutton_text'  => _x( '%1$s Manage the Athletics of %2$s', 'Button Text', 'geditorial-athlete' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'rowaction_title' => _x( 'Athletics of %1$s', 'Action Title', 'geditorial-athlete' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'rowaction_text'  => _x( 'Athletics', 'Action Text', 'geditorial-athlete' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'columnrow_title' => _x( 'Athletics of %1$s', 'Row Title', 'geditorial-athlete' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'columnrow_text'  => _x( 'Athletics', 'Row Text', 'geditorial-athlete' ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'units' => [
				'_supported' => [
					'mass_in_kg' => [
						'title'       => _x( 'Mass', 'Field Title', 'geditorial-athlete' ),
						'description' => _x( 'Body Mass in Kilogram', 'Field Description', 'geditorial-athlete' ),
						'type'        => 'kilogram',
						'data_unit'   => 'kilogram',
						'icon'        => 'image-filter',
						'order'       => 100,
					],
					'stature_in_cm' => [
						'title'       => _x( 'Stature', 'Field Title', 'geditorial-athlete' ),
						'description' => _x( 'Body Stature in Centimeter', 'Field Description', 'geditorial-athlete' ),
						'type'        => 'centimeter',
						'data_unit'   => 'centimeter',
						'icon'        => 'sort',
						'order'       => 100,
					],
				],
			]
		];
	}

	protected function define_default_terms()
	{
		return [
			// @SEE: https://en.wikipedia.org/wiki/List_of_sports
			// @SEE: https://samva.net/majors/
			'main_taxonomy' => [
				'acrobatic'    => _x( 'Acrobatic', 'Default Term: Sports Field', 'geditorial-athlete' ),      // حرکتی
				'air'          => _x( 'Air', 'Default Term: Sports Field', 'geditorial-athlete' ),            // هوایی
				'performance ' => _x( 'Performance', 'Default Term: Sports Field', 'geditorial-athlete' ),    // نمایشی
				'martial-arts' => _x( 'Martial Arts', 'Default Term: Sports Field', 'geditorial-athlete' ),   // رزمی
				'strength'     => _x( 'Strength', 'Default Term: Sports Field', 'geditorial-athlete' ),       // قدرتی
				'adventure'    => _x( 'Adventure', 'Default Term: Sports Field', 'geditorial-athlete' ),      // ماجراجویانه
				'riding'       => _x( 'Riding', 'Default Term: Sports Field', 'geditorial-athlete' ),         // سواری
				'shooting'     => _x( 'Shooting', 'Default Term: Sports Field', 'geditorial-athlete' ),       // نشانه‌روی
				'group'        => _x( 'Group', 'Default Term: Sports Field', 'geditorial-athlete' ),          // گروهی
				'net-and-wall' => _x( 'Net and Wall', 'Default Term: Sports Field', 'geditorial-athlete' ),   // راکتی
				'mind'         => _x( 'Mind', 'Default Term: Sports Field', 'geditorial-athlete' ),           // فکری
				'beach'        => _x( 'Beach', 'Default Term: Sports Field', 'geditorial-athlete' ),          // ساحلی
				'water'        => _x( 'Water', 'Default Term: Sports Field', 'geditorial-athlete' ),          // آبی
				'Snow'         => _x( 'Snow', 'Default Term: Sports Field', 'geditorial-athlete' ),           // برفی
			],
		];
	}

	protected function subcontent_get_data_mapping()
	{
		return array_merge( $this->subcontent_base_data_mapping(), [
			'comment_content' => 'desc',    // `text`
			'comment_agent'   => 'label',   // `varchar(255)`
			'comment_karma'   => 'order',   // `int(11)`

			'comment_author'       => 'location',   // `tinytext`
			'comment_author_url'   => 'mass',    // `varchar(200)`
			'comment_author_email' => 'grade',   // `varchar(100)`
			'comment_author_IP'    => 'date',    // `varchar(100)`
		] );
	}

	protected function subcontent_get_meta_mapping()
	{
		return [
			'age'     => 'age',
			'stature' => 'stature',
			'people'  => 'people',
			'postid'  => '_post_ref',
		];
	}

	protected function subcontent_define_searchable_fields()
	{
		$posttypes = Core\Arraay::prepString( [
			gEditorial()->constant( 'trained', 'primary_posttype' ),
			gEditorial()->constant( 'ranged', 'primary_posttype' ),
			gEditorial()->constant( 'listed', 'primary_posttype' ),
			gEditorial()->constant( 'programmed', 'primary_posttype' ),
		] );

		if ( count( $posttypes ) )
			return [ 'label' => $posttypes ];

		return [];
	}

	protected function subcontent_define_unique_fields()
	{
		return [
			'date',
		];
	}

	protected function subcontent_define_required_fields()
	{
		return [
			'grade',
			'label',
		];
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
			'is_viewable'    => $this->get_setting( 'contents_viewable', TRUE ),
			'auto_parents'   => $this->get_setting( 'auto_term_parents', TRUE ),
			'custom_captype' => TRUE,
		] );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );

		$this->filter_module( 'audit', 'auto_audit_save_post', 5, 12, 'subcontent' );
		$this->register_shortcode( 'main_shortcode' );

		if ( ! is_admin() )
			return;

		$this->filter_module( 'tabloid', 'post_summaries', 4, 40, 'subcontent' );
	}

	public function units_init()
	{
		$this->add_posttype_fields_supported( $this->get_setting_posttypes( 'units' ), NULL, 'TRUE', 'units' );

		$this->action_module( 'pointers', 'post', 5, 500 );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'options-general.php' );
			$this->modulelinks__register_headerbuttons();

		} else if ( in_array( $screen->base, [ 'edit', 'post' ], TRUE ) ) {

			if ( $this->posttype_supported( $screen->post_type ) ) {

				if ( 'edit' === $screen->base ) {

					if ( $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) )
						$this->corerestrictposts__hook_screen_taxonomies( 'main_taxonomy', FALSE, 90 );

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

	public function admin_menu()
	{
		if ( $this->role_can( [ 'assign', 'reports' ] ) )
			$this->_hook_submenu_adminpage( 'framepage', 'read' );

		$this->_hook_menu_taxonomy( 'main_taxonomy', 'options-general.php' );
	}

	public function dashboard_widgets()
	{
		if ( ! $this->role_can( 'reports' ) )
			return;

		$this->add_dashboard_widget( 'term-summary', NULL, 'refresh' );
	}

	public function render_widget_term_summary( $object, $box )
	{
		$this->do_dashboard_term_summary( 'main_taxonomy', $box );
	}

	public function load_framepage_adminpage( $context = 'framepage' )
	{
		$this->_load_submenu_adminpage( $context );
		$this->subcontent_do_enqueue_app( TRUE );
	}

	public function render_framepage_adminpage()
	{
		$this->subcontent_do_render_iframe_content(
			TRUE,
			'framepage',
			/* translators: %s: post title */
			_x( 'Athletics Grid for %s', 'Page Title', 'geditorial-athlete' ),
			/* translators: %s: post title */
			_x( 'Athletics Overview for %s', 'Page Title', 'geditorial-athlete' )
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
			$this->constant( 'term_empty_subcontent_data' ) => _x( 'Empty Athletics Data', 'Default Term: Audit', 'geditorial-athlete' ),
		] ) : $terms;
	}

	// TODO: append data to `Papered`
	public function pointers_post( $post, $before, $after, $context, $screen )
	{
		if ( ! $this->in_setting_posttypes( $post->post_type, 'units' ) )
			return;

		$fields = Services\PostTypeFields::getEnabled( $post->post_type, 'units' );

		if ( ! array_key_exists( 'mass_in_kg', $fields ) || ! array_key_exists( 'stature_in_cm', $fields ) )
			return;

		if ( ! $mass = Template::getMetaFieldRaw( 'mass_in_kg', $post->ID, 'units' ) )
			return;

		if ( ! $stature = Template::getMetaFieldRaw( 'stature_in_cm', $post->ID, 'units' ) )
			return;

		if ( ! $bmi = ModuleHelper::calculateBMI( $mass, $stature ) )
			return;

		echo $before;
			// TODO: hint the data!
			printf( '%s <span class="-bmi -bmi-%s -text-%s" title="%s">%s</span>',
				$this->get_column_icon(),
				$bmi['result'],
				$bmi['state'],
				$bmi['message'],
				$bmi['report']
			);
		echo $after;

		$this->actions( 'pointers_post_after', $post, $bmi, $mass, $stature, $before, $after );
	}
}
