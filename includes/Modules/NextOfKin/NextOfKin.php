<?php namespace geminorum\gEditorial\Modules\NextOfKin;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\WordPress;

class NextOfKin extends gEditorial\Module
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

	public static function module()
	{
		return [
			'name'     => 'next_of_kin',
			'title'    => _x( 'Next of Kin', 'Modules: Next of Kin', 'geditorial-admin' ),
			'desc'     => _x( 'Familial Relations', 'Modules: Next of Kin', 'geditorial-admin' ),
			'icon'     => 'buddicons-community',
			'access'   => 'beta',
			'keywords' => [
				'family',
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
				'force_sanitize',
				'reports_roles'        => [ NULL, $roles ],
				'assign_roles'         => [ NULL, $roles ],
			],
			'_roles'    => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy' ),
			'_editlist' => [
				'show_in_quickedit' => [ $this->get_taxonomy_show_in_quickedit_desc( 'main_taxonomy' ) ],
			],
			'_editpost' => [
				'admin_rowactions',
			],
			'_supports' => [
				'shortcode_support',
			],
			'posttypes_option' => 'posttypes_option',
			'_dashboard' => [
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
			'main_taxonomy' => 'marital_status',

			'restapi_namespace' => 'family-members',
			'subcontent_type'   => 'family_member',
			'subcontent_status' => 'private',
			'main_shortcode'    => 'family-members',

			'term_empty_subcontent_data' => 'family-data-empty',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'main_taxonomy' => 'smiley',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Marital Status', 'Marital Statuses', 'geditorial-next-of-kin' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'menu_name'            => _x( 'Marital', 'Label: Menu Name', 'geditorial-next-of-kin' ),
					'show_option_all'      => _x( 'Marital Status', 'Label: Show Option All', 'geditorial-next-of-kin' ),
					'show_option_no_items' => _x( '(Unknown)', 'Label: Show Option No Terms', 'geditorial-next-of-kin' ),
				],
			],
			'fields' => [
				'subcontent' => [
					'fullname'   => _x( 'Fullname', 'Field Label', 'geditorial-next-of-kin' ),
					'relation'   => _x( 'Relation', 'Field Label', 'geditorial-next-of-kin' ),
					'identity'   => _x( 'Identity', 'Field Label', 'geditorial-next-of-kin' ),
					'contact'    => _x( 'Contact', 'Field Label', 'geditorial-next-of-kin' ),
					'dob'        => _x( 'Date of Birth', 'Field Label', 'geditorial-next-of-kin' ),
					'education'  => _x( 'Education', 'Field Label', 'geditorial-next-of-kin' ),
					'occupation' => _x( 'Occupation', 'Field Label', 'geditorial-next-of-kin' ),
					'desc'       => _x( 'Description', 'Field Label', 'geditorial-next-of-kin' ),
				],
			],
		];

		$strings['notices'] = [
			'empty'    => _x( 'There is no family information available!', 'Notice', 'geditorial-next-of-kin' ),
			'noaccess' => _x( 'You have not necessary permission to manage this family data.', 'Notice', 'geditorial-next-of-kin' ),
		];

		if ( ! is_admin() )
			return $strings;

		$strings['settings'] = [
			'post_types_after' => _x( 'Supports marital statuses for the selected post-types.', 'Settings Description', 'geditorial-next-of-kin' ),
		];

		$strings['metabox'] = [
			'supportedbox_title'  => _x( 'Family', 'MetaBox Title', 'geditorial-next-of-kin' ),
			// 'metabox_action' => _x( 'Directory', 'MetaBox Action', 'geditorial-next-of-kin' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'mainbutton_title' => _x( 'Family of %1$s', 'Button Title', 'geditorial-next-of-kin' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'mainbutton_text'  => _x( '%1$s Manage the Family of %2$s', 'Button Text', 'geditorial-next-of-kin' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'rowaction_title' => _x( 'Family of %1$s', 'Action Title', 'geditorial-next-of-kin' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'rowaction_text'  => _x( 'Family', 'Action Text', 'geditorial-next-of-kin' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'columnrow_title' => _x( 'Family of %1$s', 'Row Title', 'geditorial-next-of-kin' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'columnrow_text'  => _x( 'Family', 'Row Text', 'geditorial-next-of-kin' ),
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'main_taxonomy' => [
				'single'     => _x( 'Single', 'Default Term: Marital Status', 'geditorial-next-of-kin' ),
				'married'    => _x( 'Married', 'Default Term: Marital Status', 'geditorial-next-of-kin' ),
				'divorced'   => _x( 'Divorced', 'Default Term: Marital Status', 'geditorial-next-of-kin' ),
				'widowed'    => _x( 'Widowed', 'Default Term: Marital Status', 'geditorial-next-of-kin' ),
				'separated'  => _x( 'Separated', 'Default Term: Marital Status', 'geditorial-next-of-kin' ),
				'registered' => _x( 'Registered', 'Default Term: Marital Status', 'geditorial-next-of-kin' ),
			],
		];
	}

	protected function subcontent_get_data_mapping()
	{
		return array_merge( $this->subcontent_base_data_mapping(), [
			'comment_content'      => 'desc',         // `text`
			'comment_author'       => 'fullname',     // `tinytext`
			'comment_author_url'   => 'relation',     // `varchar(200)`
			'comment_author_email' => 'contact',      // `varchar(100)`
			'comment_author_IP'    => 'dob',          // `varchar(100)`
			'comment_agent'        => 'occupation',   // `varchar(255)`
			'comment_karma'        => 'ref',          // `int(11)`
		] );
	}

	protected function subcontent_get_meta_mapping()
	{
		return [
			'identity'  => 'identity',
			'education' => 'education',
		];
	}

	protected function subcontent_define_searchable_fields()
	{
		if ( $human = gEditorial()->constant( 'personage', 'primary_posttype' ) )
			return [ 'fullname' => [ $human ] ];

		return [];
	}

	protected function subcontent_define_hidden_fields()
	{
		return [
			'ref',
			'order',
		];
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
			'relation',
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
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit' ),
			'data_length'        => _x( '10', 'Main Taxonomy Argument: `data_length`', 'geditorial-next-of-kin' ),
		], NULL, [
			'custom_captype' => TRUE,
		] );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );

		$this->filter_self( 'subcontent_pre_prep_data', 4, 10 );
		$this->filter_module( 'audit', 'auto_audit_save_post', 5 );
		$this->register_shortcode( 'main_shortcode' );

		if ( ! is_admin() )
			return;

		$this->filter_module( 'tabloid', 'post_summaries', 4, 40, 'subcontent' );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'options-general.php' );

		} else if ( in_array( $screen->base, [ 'edit', 'post' ], TRUE ) ) {

			if ( $this->posttype_supported( $screen->post_type ) ) {

				if ( 'edit' === $screen->base ) {

					$this->corerestrictposts__hook_screen_taxonomies( 'main_taxonomy', FALSE, 90 );

				} else if ( 'post' === $screen->base ) {

					$this->hook_taxonomy_metabox_mainbox(
						'main_taxonomy',
						$screen->post_type,
						'__singleselect_restricted_terms_callback'
					);
				}
			}

			if ( $this->in_setting( $screen->post_type, 'subcontent_posttypes' ) ) {

				if ( 'post' == $screen->base ) {

					if ( $this->role_can( [ 'reports', 'assign' ] ) )
						$this->_hook_general_supportedbox( $screen, NULL, 'advanced', 'low', '-subcontent-grid-metabox' );

					$this->subcontent_do_enqueue_asset_js( $screen );

				} else if ( 'edit' == $screen->base ) {

					if ( $this->role_can( [ 'reports', 'assign' ] ) ) {

						if ( ! $this->rowactions__hook_mainlink_for_post( $screen->post_type ) )
							$this->coreadmin__hook_tweaks_column_row( $screen->post_type, 18 );

						Scripts::enqueueColorBox();
					}
				}
			}
		}
	}

	public function tweaks_column_row( $post, $before, $after )
	{
		printf( $before, '-family-grid' );

			echo $this->get_column_icon( FALSE, NULL, NULL, $post->post_type );

			echo $this->framepage_get_mainlink_for_post( $post, [
				'context' => 'columnrow',
			] );

			if ( $count = $this->subcontent_get_data_count( $post ) )
				printf( ' <span class="-counted">(%s)</span>', $this->nooped_count( 'family_member', $count ) );

		echo $after;
	}

	protected function rowaction_get_mainlink_for_post( $post )
	{
		return [
			$this->classs().' hide-if-no-js' => $this->framepage_get_mainlink_for_post( $post, [
				'context' => 'rowaction',
			] ),
		];
	}

	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'supportedbox';

		$this->subcontent_render_metabox_data_grid( $object, $context );

		if ( $this->role_can( 'assign' ) )
			echo Core\HTML::wrap( $this->framepage_get_mainlink_for_post( $object, [
				'context' => 'mainbutton',
				'target'  => 'grid',
			] ), 'field-wrap -buttons' );

		else
			echo $this->subcontent_get_noaccess_notice();
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

	public function admin_menu()
	{
		if ( $this->role_can( [ 'assign', 'reports' ] ) )
			$this->_hook_submenu_adminpage( 'framepage', 'read' );

		$this->_hook_menu_taxonomy( 'main_taxonomy', 'options-general.php' );
	}

	public function load_framepage_adminpage( $context = 'framepage' )
	{
		$this->_load_submenu_adminpage( $context );
		$this->subcontent_do_enqueue_app( 'family-grid' );
	}

	public function render_framepage_adminpage()
	{
		$this->subcontent_do_render_iframe_content(
			'family-grid',
			'framepage',
			/* translators: %s: post title */
			_x( 'Family Grid for %s', 'Page Title', 'geditorial-next-of-kin' ),
			/* translators: %s: post title */
			_x( 'Family Overview for %s', 'Page Title', 'geditorial-next-of-kin' )
		);
	}

	public function setup_restapi()
	{
		$this->subcontent_restapi_register_routes();
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return $this->subcontent_do_main_shortcode( $atts, $content, $tag );
	}

	public function audit_get_default_terms( $terms, $taxonomy )
	{
		return Helper::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_empty_subcontent_data' ) => _x( 'Empty Family Data', 'Default Term: Audit', 'geditorial-next-of-kin' ),
		] ) : $terms;
	}

	public function audit_auto_audit_save_post( $terms, $post, $taxonomy, $currents, $update )
	{
		if ( ! $this->in_setting( $post->post_type, 'subcontent_posttypes' ) )
			return $terms;

		if ( $exists = term_exists( $this->constant( 'term_empty_subcontent_data' ), $taxonomy ) ) {

			if ( $this->subcontent_get_data_count( $post ) )
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

			else
				$terms[] = $exists['term_id'];
		}

		return $terms;
	}

	// TODO: force sanitize: `dob`/`contact`/`relation`
	public function subcontent_pre_prep_data( $raw, $post, $mapping, $metas )
	{
		$sanitize = $this->get_setting( 'force_sanitize' );
		$data     = [];

		foreach ( $raw as $key => $value ) {

			$value = Core\Text::trim( $value );

			if ( WordPress\Strings::isEmpty( $value ) ) {

				if ( empty( $data[$key] ) )
					$data[$key] = '';

				continue;
			}

			switch ( $key ) {

				case 'identity':

					$data[$key] = $sanitize
						? Core\Validation::sanitizeIdentityNumber( $value )
						: Core\Number::translate( $value );

					break;

				case 'fullname':
				case 'relation':
				case 'education':
				case 'occupation':
				case 'status':
				case 'type':

					$data[$key] = apply_filters( 'string_format_i18n',
						$sanitize ? WordPress\Strings::cleanupChars( $value ) : $value );

					break;

				case 'desc':

					$data[$key] = apply_filters( 'html_format_i18n',
						$sanitize ? WordPress\Strings::cleanupChars( $value, TRUE ) : $value );

					break;

				case 'dob':
				case 'contact':

					$data[$key] = Core\Number::translate( $value );

					break;

				default:

					$data[$key] = $value;
			}
		}

		return $data;
	}
}
