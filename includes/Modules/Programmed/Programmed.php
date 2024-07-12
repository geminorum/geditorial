<?php namespace geminorum\gEditorial\Modules\Programmed;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

class Programmed extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\BulkExports;
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\LateChores;
	use Internals\PairedAdmin;
	use Internals\PairedCore;
	use Internals\PairedFront;
	use Internals\PairedImports;
	use Internals\PairedMetaBox;
	use Internals\PairedReports;
	use Internals\PairedRest;
	use Internals\PairedRowActions;
	use Internals\PairedTools;
	use Internals\PostDate;
	use Internals\PostMeta;
	use Internals\PostTypeFields;
	use Internals\TemplatePostType;

	protected $deafults  = [ 'multiple_instances' => TRUE ];
	protected $positions = [ 'primary_posttype' => 3 ];

	public static function module()
	{
		return [
			'name'     => 'programmed',
			'title'    => _x( 'Programmed', 'Modules: Programmed', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Program Management', 'Modules: Programmed', 'geditorial-admin' ),
			'icon'     => 'fullscreen-exit-alt',
			'access'   => 'beta',
			'keywords' => [
				'multipaired',
			],
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'_general' => [
				'paired_force_parents',
				'paired_manage_restricted',
				[
					'field'       => 'subterms_support',
					'title'       => _x( 'Program Levels', 'Settings', 'geditorial-programmed' ),
					'description' => _x( 'Substitute taxonomy for the programs and supported post-types.', 'Settings', 'geditorial-programmed' ),
				],
				'paired_exclude_terms' => [
					NULL,
					$this->constant( 'primary_taxonomy' ),
					$this->get_taxonomy_label( 'primary_taxonomy', 'no_terms' ),
				],
			],
			'posttypes_option' => 'posttypes_option',
			'_supports'        => [
				'override_dates',
				'assign_default_term',
				'comment_status',
				'thumbnail_support',
				$this->settings_supports_option( 'primary_posttype', [
					'title',
					// 'editor',
					'excerpt',
					'author',
					'thumbnail',
					'comments',
					'custom-fields',
					'page-attributes',
					'editorial-geo',
					'editorial-units',
				] ),
			],
			'_roles' => [
				'custom_captype',
				'reports_roles' => [ NULL, $roles ],
				'exports_roles' => [ NULL, $roles ],
			],
			'_editlist' => [
				'admin_bulkactions',
				'show_in_quickedit' => [ $this->get_taxonomy_show_in_quickedit_desc( 'status_taxonomy' ), '1' ],
			],
			'_frontend' => [
				'contents_viewable',
			],
			'_reports' => [
				'append_identifier_code',
				'overview_taxonomies' => [ NULL, $this->get_posttype_taxonomies_list( 'primary_posttype' ) ],
				'overview_fields'     => [ NULL, $this->get_posttype_fields_list( 'primary_posttype', 'meta' ) ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype' => 'program',
			'primary_taxonomy' => 'program_category',
			'primary_paired'   => 'programs',
			'primary_subterm'  => 'program_level',
			'program_taxonomy' => 'program_project',
			'span_taxonomy'    => 'program_span',
			'type_taxonomy'    => 'program_type',
			'status_taxonomy'  => 'program_status',
		];
	}

	protected function get_module_icons()
	{
		return [
			'post_types' => [
				'primary_posttype' => NULL,
			],
			'taxonomies' => [
				'primary_taxonomy' => NULL,
				'primary_subterm'  => 'performance',
				'span_taxonomy'    => 'backup',
				'type_taxonomy'    => 'screenoptions',
				'status_taxonomy'  => 'post-status',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'primary_posttype' => _n_noop( 'Program', 'Programs', 'geditorial-programmed' ),
				'primary_paired'   => _n_noop( 'Program', 'Programs', 'geditorial-programmed' ),
				'primary_taxonomy' => _n_noop( 'Program Category', 'Program Categories', 'geditorial-programmed' ),
				'primary_subterm'  => _n_noop( 'Program Level', 'Program Levels', 'geditorial-programmed' ),
				'program_taxonomy' => _n_noop( 'Program Project', 'Program Projects', 'geditorial-programmed' ),
				'span_taxonomy'    => _n_noop( 'Program Span', 'Program Spans', 'geditorial-programmed' ),
				'type_taxonomy'    => _n_noop( 'Program Type', 'Program Types', 'geditorial-programmed' ),
				'status_taxonomy'  => _n_noop( 'Program Status', 'Program Statuses', 'geditorial-programmed' ),
			],
			'labels' => [
				'primary_posttype' => [
					'menu_name'      => _x( 'Programs', 'Label: `menu_name`', 'geditorial-programmed' ),
					'featured_image' => _x( 'Program Poster', 'Label: Featured Image', 'geditorial-programmed' ),
					'metabox_title'  => _x( 'The Program', 'Label: MetaBox Title', 'geditorial-programmed' ),
				],
				'primary_paired' => [
					'metabox_title' => _x( 'In This Program', 'Label: MetaBox Title', 'geditorial-programmed' ),
				],
				'primary_taxonomy' => [
					'menu_name' => _x( 'Categories', 'Label: Menu Name', 'geditorial-programmed' ),
				],
				'program_taxonomy' => [
					'menu_name' => _x( 'Projects', 'Label: Menu Name', 'geditorial-programmed' ),
				],
				'span_taxonomy' => [
					'menu_name' => _x( 'Spans', 'Label: Menu Name', 'geditorial-programmed' ),
				],
				'type_taxonomy' => [
					'menu_name' => _x( 'Types', 'Label: Menu Name', 'geditorial-programmed' ),
				],
				'status_taxonomy' => [
					'menu_name' => _x( 'Statuses', 'Label: Menu Name', 'geditorial-programmed' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			/* translators: %s: item count */
			'tabloid_paired_posttype'  => _x( 'Program Participants (%s)', 'Misc: `tabloid_paired_posttype`', 'geditorial-programmed' ),
			/* translators: %s: item count */
			'tabloid_paired_supported' => _x( 'Program Participations (%s)', 'Misc: `tabloid_paired_supported`', 'geditorial-programmed' ),
			'column_icon_title'        => _x( 'Programs', 'Misc: `column_icon_title`', 'geditorial-programmed' ),
		];

		$strings['metabox'] = [
			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'listbox_title' => _x( 'Participants on &ldquo;%1$s&rdquo;', 'MetaBox: `listbox_title`', 'geditorial-programmed' ),
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'status_taxonomy' => [
				// TODO: finish the list
				'planned' => _x( 'Planned', 'Status Taxonomy: Default Term', 'geditorial-programmed' ),
				'held'    => _x( 'Held', 'Status Taxonomy: Default Term', 'geditorial-programmed' ),
			],
			'span_taxonomy' => Datetime::getYears( '-5 years' ),
		];
	}

	protected function get_global_fields()
	{
		return [ 'meta' => [
			$this->constant( 'primary_posttype' ) => [
				'over_title' => [ 'type' => 'title_before' ],
				'sub_title'  => [ 'type' => 'title_after' ],
				'lead'       => [ 'type' => 'postbox_html' ],

				'print_title' => [ 'type' => 'text' ],
				'print_date'  => [ 'type' => 'date' ],

				'content_embed_url' => [ 'type' => 'embed' ],
				'text_source_url'   => [ 'type' => 'text_source' ],
				'audio_source_url'  => [ 'type' => 'audio_source' ],
				'video_source_url'  => [ 'type' => 'video_source' ],
				'image_source_url'  => [ 'type' => 'image_source' ],

				'date'      => [ 'type' => 'date', 'quickedit' => TRUE ],
				'datetime'  => [ 'type' => 'datetime', 'quickedit' => TRUE ],
				'datestart' => [ 'type' => 'datetime', 'quickedit' => TRUE ],
				'dateend'   => [ 'type' => 'datetime', 'quickedit' => TRUE ],
				'days'      => [ 'type' => 'number', 'quickedit' => TRUE ],
				'hours'     => [ 'type' => 'number', 'quickedit' => TRUE ],

				'venue_string'   => [ 'type' => 'venue' ],
				'contact_string' => [ 'type' => 'contact' ], // url/email/phone
				'phone_number'   => [ 'type' => 'phone' ],
				'mobile_number'  => [ 'type' => 'mobile' ],

				'website_url'    => [ 'type' => 'link' ],
				'email_address'  => [ 'type' => 'email' ],
				'postal_address' => [ 'type' => 'address' ],
				'postal_code'    => [ 'type' => 'postcode' ],

				'featured_people' => [
					'title'       => _x( 'Organizers', 'Field Title', 'geditorial-programmed' ),
					'description' => _x( 'People Who Participate as Organizers in This Program', 'Field Description', 'geditorial-programmed' ),
					'type'        => 'people',
					'icon'        => 'groups',
					'quickedit'   => TRUE,
					'order'       => 90,
				],

				'program_code' => [
					'title'       => _x( 'Program Code', 'Field Title', 'geditorial-programmed' ),
					'description' => _x( 'Unique Program Code', 'Field Description', 'geditorial-programmed' ),
					'type'        => 'code',
					'quickedit'   => TRUE,
					'icon'        => 'nametag',
					'order'       => 100,
				],
			],
			// '_supported' => [],
		] ];
	}

	protected function paired_get_paired_constants()
	{
		return [
			'primary_posttype',
			'primary_paired',
			'primary_subterm',
			'primary_taxonomy',
		];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'primary_posttype' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'primary_posttype' ) );
		// $this->add_posttype_fields_supported();

		$this->filter_module( 'identified', 'default_posttype_identifier_metakey', 2 );
		$this->filter_module( 'identified', 'default_posttype_identifier_type', 2 );
		$this->filter_module( 'static_covers', 'default_posttype_reference_metakey', 2 );

		$this->filter( 'pairedimports_import_types', 4, 20, FALSE, $this->base );
		$this->action( 'posttypefields_import_raw_data', 5, 9, FALSE, $this->base );

		$this->pairedcore__hook_append_identifier_code( 'program_code' );
	}

	public function init()
	{
		parent::init();

		$viewable = $this->get_setting( 'contents_viewable', TRUE );
		$captype  = $this->get_setting( 'custom_captype', FALSE )
			? $this->constant_plural( 'primary_posttype' )
			: FALSE;

		$this->register_taxonomy( 'primary_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
		], 'primary_posttype', [
			'is_viewable'    => $viewable,
			'custom_captype' => $captype,
		] );

		$this->register_taxonomy( 'program_taxonomy', [
			'hierarchical'      => TRUE,
			'meta_box_cb'       => NULL,
			'show_admin_column' => TRUE,
		], 'primary_posttype', [
			'is_viewable'    => $viewable,
			'custom_captype' => $captype,
		] );

		$this->register_taxonomy( 'span_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => '__checklist_reverse_terms_callback',
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'primary_posttype', [
			'is_viewable'    => $viewable,
			'custom_captype' => $captype,
			'admin_managed'  => TRUE,
		] );

		$this->register_taxonomy( 'type_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'primary_posttype', [
			'is_viewable'    => $viewable,
			'custom_captype' => $captype,
			'admin_managed'  => TRUE,
		] );

		$this->register_taxonomy( 'status_taxonomy', [
			'public'             => FALSE,
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit', TRUE ),
		], 'primary_posttype', [
			'is_viewable'    => $viewable,
			'custom_captype' => $captype,
			'admin_managed'  => TRUE,
		] );

		$this->paired_register( [], [
			'is_viewable'    => $viewable,
			'custom_captype' => $captype,
		], [
			'is_viewable'    => $viewable,
			'custom_captype' => $captype,
		] );

		if ( $this->get_setting( 'override_dates', TRUE ) )
			$this->latechores__init_post_aftercare( $this->constant( 'primary_posttype' ) );

		$this->action_module( 'pointers', 'post', 5, 201, 'paired_posttype' );
		$this->filter_module( 'tabloid', 'post_summaries', 4, 120, 'paired_exports' );
		$this->filter_module( 'tabloid', 'post_summaries', 4, 90, 'paired_supported' );
		$this->filter_module( 'tabloid', 'post_summaries', 4, 90, 'paired_posttype' );
		$this->filter_module( 'tabloid', 'view_data', 3, 9, 'paired_supported' );

		if ( is_admin() )
			return;

		$this->_hook_paired_override_term_link();
	}

	public function setup_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'primary_posttype' ) ) {
			$this->coreadmin__unset_columns( $posttype );
			$this->pairedadmin__hook_tweaks_column_connected( $posttype );
		}
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'primary_subterm' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'primary_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'get_default_comment_status', 3 );

				$this->_hook_post_updated_messages( 'primary_posttype' );
				$this->_hook_paired_mainbox( $screen );
				$this->pairedmetabox__hook_megabox( $screen );
				$this->pairedcore__hook_sync_paired();

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->latechores__hook_admin_bulkactions( $screen );
				$this->postmeta__hook_meta_column_row( $screen->post_type );
				$this->coreadmin__unset_columns( $screen->post_type );
				$this->coreadmin__unset_views( $screen->post_type );
				$this->coreadmin__hook_admin_ordering( $screen->post_type, 'date' );
				$this->_hook_bulk_post_updated_messages( 'primary_posttype' );
				$this->pairedcore__hook_sync_paired();
				$this->pairedadmin__hook_tweaks_column_connected( $screen->post_type );
				$this->corerestrictposts__hook_screen_taxonomies( [
					'primary_taxonomy',
					'primary_subterm',
					'program_taxonomy',
					'span_taxonomy',
					'type_taxonomy',
					'status_taxonomy',
				] );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( $subterms && $subterms === $screen->taxonomy )
				$this->filter_string( 'parent_file', sprintf( 'edit.php?post_type=%s', $this->constant( 'primary_posttype' ) ) );

			if ( 'edit-tags' == $screen->base ) {

				$this->_hook_paired_taxonomy_bulk_actions( $screen->post_type, $screen->taxonomy );

			} else if ( 'post' == $screen->base ) {

				$this->_metabox_remove_subterm( $screen, $subterms );
				$this->_hook_paired_overviewbox( $screen );

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_paired_store_metabox( $screen->post_type );
				// $this->paired__hook_tweaks_column( $screen->post_type, 8 );
				// $this->paired__hook_screen_restrictposts();
				$this->postmeta__hook_meta_column_row( $screen->post_type );
			}
		}

		// only for supported posttypes
		$this->remove_taxonomy_submenu( $subterms );

		if ( Settings::isDashboard( $screen ) )
			$this->filter_module( 'calendar', 'post_row_title', 4, 12 );
	}

	public function admin_menu()
	{
		$this->_hook_submenu_adminpage( 'importitems', 'read' );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'primary_posttype', [ 'reports' ] ) )
			$items[] = $glance;

		return $items;
	}

	protected function _render_mainbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		MetaBox::fieldPostParent( $object );

		MetaBox::singleselectTerms( $object->ID, [
			'taxonomy'   => $this->constant( 'type_taxonomy' ),
			'posttype'   => $object->post_type,
			'empty_link' => FALSE,
		] );

		MetaBox::singleselectTerms( $object->ID, [
			'taxonomy'   => $this->constant( 'status_taxonomy' ),
			'posttype'   => $object->post_type,
			'empty_link' => FALSE,
		] );

		MetaBox::fieldPostMenuOrder( $object );
	}

	public function identified_default_posttype_identifier_metakey( $default, $posttype )
	{
		if ( $posttype == $this->constant( 'primary_posttype' ) )
			return Services\PostTypeFields::getPostMetaKey( 'program_code' );

		return $default;
	}

	public function identified_default_posttype_identifier_type( $default, $posttype )
	{
		if ( $posttype == $this->constant( 'primary_posttype' ) )
			return 'code';

		return $default;
	}

	public function static_covers_default_posttype_reference_metakey( $default, $posttype )
	{
		if ( $posttype == $this->constant( 'primary_posttype' ) )
			return Services\PostTypeFields::getPostMetaKey( 'program_code' );

		return $default;
	}

	// NOTE: only returns selected supported crossing fields
	public function pairedimports_import_types( $types, $linked, $posttypes, $module_key )
	{
		if ( ! \array_intersect( $this->posttypes(), $posttypes ) )
			return $types;

		if ( $field = Services\PostTypeFields::isAvailable( 'program_code', $this->constant( 'primary_posttype' ), 'meta' ) )
			return array_merge( $types, [
				$field['name'] => $field['title'],
			] );

		return $types;
	}

	public function posttypefields_import_raw_data( $post, $data, $override, $check_access, $module )
	{
		if ( empty( $data ) || empty( $data['program_code'] ) || $module !== 'meta' )
			return;

		if ( ! $post = WordPress\Post::get( $post ) )
			return;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return;

		$this->posttypefields_connect_paired_by( 'program_code', $data['program_code'], $post );
	}

	private function get_postdate_metakeys()
	{
		return [
			Services\PostTypeFields::getPostMetaKey( 'date' ),
			Services\PostTypeFields::getPostMetaKey( 'datetime' ),
			Services\PostTypeFields::getPostMetaKey( 'datestart' ),
			Services\PostTypeFields::getPostMetaKey( 'dateend' ),
		];
	}

	protected function latechores_post_aftercare( $post )
	{
		return $this->postdate__get_post_data_for_latechores(
			$post,
			$this->get_postdate_metakeys()
		);
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );
				$this->paired_tools_handle_tablelist( $sub );
			}

			Scripts::enqueueThickBox();
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		echo Settings::toolboxColumnOpen(
			_x( 'Program Tools', 'Header', 'geditorial-programmed' ) );

			$this->paired_tools_render_card( $uri, $sub );

			if ( $this->get_setting( 'override_dates', TRUE ) )
				$this->postdate__render_card_override_dates(
					$uri,
					$sub,
					$this->constant( 'primary_posttype' ),
					_x( 'Program Date from Meta-data', 'Card', 'geditorial-programmed' )
				);

		echo '</div>';
	}

	protected function render_tools_html_before( $uri, $sub )
	{
		if ( FALSE === $this->postdate__render_before_override_dates(
			$this->constant( 'primary_posttype' ),
			$this->get_postdate_metakeys(),
			$uri,
			$sub
		) )
			return FALSE;

		return $this->paired_tools_render_before( $uri, $sub );
	}

	public function imports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'imports', 'per_page' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'imports', $sub );
				$this->paired_imports_handle_tablelist( $sub );
			}

			Scripts::enqueueThickBox();
		}
	}

	protected function render_imports_html( $uri, $sub )
	{
		if ( ! $this->paired_imports_render_tablelist( $uri, $sub ) )
			return Info::renderNoImportsAvailable();
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->paired_reports_render_overview_table( $uri, $sub ) )
			return Info::renderNoReportsAvailable();
	}
}
