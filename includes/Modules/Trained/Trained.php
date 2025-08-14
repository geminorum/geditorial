<?php namespace geminorum\gEditorial\Modules\Trained;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

class Trained extends gEditorial\Module
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
	use Internals\PairedRest;
	use Internals\PairedRowActions;
	use Internals\PairedTools;
	use Internals\PostDate;
	use Internals\PostMeta;
	use Internals\PostTypeFields;
	use Internals\PostTypeOverview;
	use Internals\TemplatePostType;

	protected $deafults  = [ 'multiple_instances' => TRUE ];
	protected $positions = [ 'primary_posttype' => 3 ];

	public static function module()
	{
		return [
			'name'     => 'trained',
			'title'    => _x( 'Trained', 'Modules: Trained', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Training Management', 'Modules: Trained', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'person-arms-up' ],
			'access'   => 'beta',
			'keywords' => [
				'training',
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
				'paired_globalsummary' => [ NULL, TRUE ],
				'paired_manage_restricted',
				[
					'field'       => 'subterms_support',
					'title'       => _x( 'Training Levels', 'Settings', 'geditorial-trained' ),
					'description' => _x( 'Substitute taxonomy for the trainings and supported post-types.', 'Settings', 'geditorial-trained' ),
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
					'excerpt',
					'author',
					'thumbnail',
					'comments',
					'custom-fields',
					'page-attributes',
				] ),
			],
			'_roles' => [
				'custom_captype',
				'reports_roles' => [ NULL, $roles ],
				'imports_roles' => [ NULL, $roles ],
				'tools_roles'   => [ NULL, $roles ],
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
				'overview_units'      => [ NULL, $this->get_posttype_fields_list( 'primary_posttype', 'units' ) ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype' => 'training_course',
			'primary_taxonomy' => 'training_category',
			'primary_paired'   => 'trainings',
			'primary_subterm'  => 'training_level',
			'program_taxonomy' => 'training_program',
			'span_taxonomy'    => 'training_span',
			'type_taxonomy'    => 'training_type',
			'status_taxonomy'  => 'training_status',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [

				/**
				 * `Training` is both countable and uncountable. Usually,
				 * referring to a process, it is uncountable and has no plural.
				 *
				 * It is sometimes used to mean "a specific training event",
				 * and is then countable, and has the plural `trainings`.
				 * The OED has examples of this use going back to 1578,
				 * with the plural recorded from 1598.
				 *
				 * @source https://english.stackexchange.com/a/354633
				 * @link https://englishbusiness.de/en/blog/training-or-trainings-what-is-the-correct-plural-form/
				 */

				'primary_posttype' => _n_noop( 'Training Course', 'Training Courses', 'geditorial-trained' ),
				'primary_paired'   => _n_noop( 'Training Course', 'Training Courses', 'geditorial-trained' ),
				'primary_taxonomy' => _n_noop( 'Training Category', 'Training Categories', 'geditorial-trained' ),
				'primary_subterm'  => _n_noop( 'Training Level', 'Training Levels', 'geditorial-trained' ),
				'program_taxonomy' => _n_noop( 'Training Program', 'Training Programs', 'geditorial-trained' ),
				'span_taxonomy'    => _n_noop( 'Training Span', 'Training Spans', 'geditorial-trained' ),
				'type_taxonomy'    => _n_noop( 'Training Type', 'Training Types', 'geditorial-trained' ),
				'status_taxonomy'  => _n_noop( 'Training Status', 'Training Statuses', 'geditorial-trained' ),
			],
			'labels' => [
				'primary_posttype' => [
					'menu_name'      => _x( 'Trainings', 'Label: `menu_name`', 'geditorial-trained' ),
					'featured_image' => _x( 'Training Poster', 'Label: Featured Image', 'geditorial-trained' ),
					'metabox_title'  => _x( 'The Training', 'Label: MetaBox Title', 'geditorial-trained' ),
				],
				'primary_paired' => [
					'metabox_title' => _x( 'In This Training', 'Label: MetaBox Title', 'geditorial-trained' ),
				],
				'primary_taxonomy' => [
					'menu_name' => _x( 'Categories', 'Label: Menu Name', 'geditorial-trained' ),
				],
				'program_taxonomy' => [
					'menu_name' => _x( 'Programs', 'Label: Menu Name', 'geditorial-trained' ),
				],
				'span_taxonomy' => [
					'menu_name' => _x( 'Spans', 'Label: Menu Name', 'geditorial-trained' ),
				],
				'type_taxonomy' => [
					'menu_name' => _x( 'Types', 'Label: Menu Name', 'geditorial-trained' ),
				],
				'status_taxonomy' => [
					'menu_name' => _x( 'Statuses', 'Label: Menu Name', 'geditorial-trained' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['notices'] = [
			'empty'    => _x( 'There is no training information available!', 'Notice', 'geditorial-trained' ),
			'noaccess' => _x( 'You do not have the necessary permission to manage the training information.', 'Notice', 'geditorial-trained' ),
		];

		$strings['misc'] = [
			/* translators: `%s`: item count */
			'tabloid_paired_posttype'  => _x( 'Training Attendees (%s)', 'Misc: `tabloid_paired_posttype`', 'geditorial-trained' ),
			/* translators: `%s`: item count */
			'tabloid_paired_supported' => _x( 'Training Courses (%s)', 'Misc: `tabloid_paired_supported`', 'geditorial-trained' ),
			'column_icon_title'        => _x( 'Training Courses', 'Misc: `column_icon_title`', 'geditorial-trained' ),
		];

		$strings['metabox'] = [
			/* translators: `%1$s`: current post title, `%2$s`: posttype singular name */
			'listbox_title' => _x( 'Attendees on &ldquo;%1$s&rdquo;', 'MetaBox: `listbox_title`', 'geditorial-trained' ),
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'primary_subterm' => [
				'preliminary'  => _x( 'Preliminary', 'Subterm Taxonomy: Default Term', 'geditorial-trained' ),
				'intermediate' => _x( 'Intermediate', 'Subterm Taxonomy: Default Term', 'geditorial-trained' ),
				'advanced'     => _x( 'Advanced', 'Subterm Taxonomy: Default Term', 'geditorial-trained' ),
				'special'      => _x( 'Special', 'Subterm Taxonomy: Default Term', 'geditorial-trained' ),
			],
			'status_taxonomy' => [
				// TODO: finish the list
				'planned'  => _x( 'Planned', 'Status Taxonomy: Default Term', 'geditorial-trained' ),
				'canceled' => _x( 'Canceled', 'Status Taxonomy: Default Term', 'geditorial-trained' ),
				'held'     => _x( 'Held', 'Status Taxonomy: Default Term', 'geditorial-trained' ),
			],
			'span_taxonomy' => Datetime::getYears( '-5 years' ),
		];
	}

	protected function get_global_fields()
	{
		$posttype = $this->constant( 'primary_posttype' );

		return [
			'meta' => [
				$posttype => [
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
					'distance'  => [ 'type' => 'distance', 'quickedit' => TRUE ],
					'duration'  => [ 'type' => 'duration', 'quickedit' => TRUE ],

					'venue_string'   => [ 'type' => 'venue', 'quickedit' => TRUE ],
					'contact_string' => [ 'type' => 'contact' ],   // url/email/phone
					'website_url'    => [ 'type' => 'link' ],
					'email_address'  => [ 'type' => 'email' ],

					'notes'       => [ 'type' => 'note' ],
					'itineraries' => [ 'type' => 'note' ],

					'featured_people' => [
						'title'       => _x( 'Instructors', 'Field Title', 'geditorial-trained' ),
						'description' => _x( 'People Who Participate as Instructors in This Training Course', 'Field Description', 'geditorial-trained' ),
						'type'        => 'people',
						'quickedit'   => TRUE,
						'order'       => 90,
					],

					'training_code' => [
						'title'       => _x( 'Training Code', 'Field Title', 'geditorial-trained' ),
						'description' => _x( 'Unique Training Code', 'Field Description', 'geditorial-trained' ),
						'type'        => 'code',
						'quickedit'   => TRUE,
						'bulkedit'    => FALSE,
						'icon'        => 'nametag',
						'order'       => 100,
					],
				],
			],
			'units' => [
				$posttype => [
					'total_days'   => [ 'type' => 'day' ,   'data_unit' => 'day'    ],
					'total_hours'  => [ 'type' => 'hour',   'data_unit' => 'hour'   ],
					'total_people' => [ 'type' => 'person', 'data_unit' => 'person' ],
				],
			],
		];
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

		$this->filter_module( 'identified', 'default_posttype_identifier_metakey', 2 );
		$this->filter_module( 'identified', 'default_posttype_identifier_type', 2 );
		$this->filter_module( 'static_covers', 'default_posttype_reference_metakey', 2 );

		$this->filter( 'pairedimports_import_types', 4, 20, FALSE, $this->base );
		$this->action( 'posttypefields_import_raw_data', 5, 9, FALSE, $this->base );

		$this->pairedcore__hook_append_identifier_code( 'training_code' );

		if ( $this->get_setting( 'override_dates', TRUE ) )
			$this->latechores__init_post_aftercare( $this->constant( 'primary_posttype' ) );
	}

	public function units_init()
	{
		$this->add_posttype_fields( $this->constant( 'primary_posttype' ), NULL, TRUE, 'units' );
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
			'custom_icon'    => $this->module->icon,
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
			'custom_icon'    => 'backup',
			'custom_captype' => $captype,
			'admin_managed'  => TRUE,
		] );

		$this->register_taxonomy( 'type_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'primary_posttype', [
			'is_viewable'     => $viewable,
			'custom_icon'     => 'screenoptions',
			'custom_captype'  => $captype,
			'admin_managed'   => TRUE,
			'single_selected' => TRUE,
		] );

		$this->register_taxonomy( 'status_taxonomy', [
			'public'             => FALSE,
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit', TRUE ),
		], 'primary_posttype', [
			'is_viewable'     => $viewable,
			'custom_icon'     => 'post-status',
			'custom_captype'  => $captype,
			'admin_managed'   => TRUE,
			'single_selected' => TRUE,
		] );

		$this->paired_register( [], [
			'is_viewable'      => $viewable,
			'custom_icon'      => $this->module->icon,
			'custom_captype'   => $captype,
			'primary_taxonomy' => TRUE,
			'status_taxonomy'  => TRUE,
		], [
			'is_viewable'    => $viewable,
			'custom_icon'    => 'performance',
			'custom_captype' => $captype,
		] );

		if ( $this->get_setting( 'paired_globalsummary', TRUE ) )
			$this->filter( 'paired_globalsummary_for_post', 3, 12, FALSE, $this->base );
		else
			$this->filter_module( 'tabloid', 'post_summaries', 4, 90, 'paired_supported' );

		$this->hook_paired_static_covers_secondaries();
		$this->hook_paired_tabloid_exclude_rendered();
		$this->hook_paired_tabloid_post_summaries_by_paired();
		$this->action_module( 'pointers', 'post', 5, 201, 'paired_posttype' );
		$this->filter_module( 'tabloid', 'post_summaries', 4, 120, 'paired_exports' );
		$this->filter_module( 'tabloid', 'post_summaries', 4, 220, 'paired_posttype' );
		$this->filter( 'bulk_exports_post_taxonomies', 7, 9, 'exclude_paired', $this->base );

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

				$this->posttype__media_register_headerbutton( 'primary_posttype' );
				$this->_hook_post_updated_messages( 'primary_posttype' );
				$this->_hook_paired_mainbox( $screen );
				$this->pairedmetabox__hook_megabox( $screen );
				$this->pairedcore__hook_sync_paired();

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->modulelinks__register_headerbuttons();
				$this->latechores__hook_admin_bulkactions( $screen );
				$this->postmeta__hook_meta_column_row( $screen->post_type, TRUE );
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
			}
		}

		// only for supported posttypes
		$this->remove_taxonomy_submenu( $subterms );

		if ( Settings::isDashboard( $screen ) )
			$this->filter_module( 'calendar', 'post_row_title', 4, 12 );
	}

	public function admin_menu()
	{
		$this->_hook_submenu_adminpage( 'importitems', 'exist' );
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
			return Services\PostTypeFields::getPostMetaKey( 'training_code' );

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
			return Services\PostTypeFields::getPostMetaKey( 'training_code' );

		return $default;
	}

	// NOTE: only returns selected supported crossing fields
	public function pairedimports_import_types( $types, $linked, $posttypes, $module_key )
	{
		if ( ! \array_intersect( $this->posttypes(), $posttypes ) )
			return $types;

		if ( $field = Services\PostTypeFields::isAvailable( 'training_code', $this->constant( 'primary_posttype' ), 'meta' ) )
			return array_merge( $types, [
				$field['name'] => $field['title'],
			] );

		return $types;
	}

	public function posttypefields_import_raw_data( $post, $data, $override, $check_access, $module )
	{
		if ( empty( $data ) || empty( $data['training_code'] ) || $module !== 'meta' )
			return;

		if ( ! $post = WordPress\Post::get( $post ) )
			return;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return;

		$this->posttypefields_connect_paired_by( 'training_code', $data['training_code'], $post );
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

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return $this->_override_module_cuc( $context, $fallback, [
			'reports',
			'tools',
			'imports',
		] );
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
			_x( 'Training Course Tools', 'Header', 'geditorial-trained' ) );

			$this->paired_tools_render_card( $uri, $sub );

			if ( $this->get_setting( 'override_dates', TRUE ) )
				$this->postdate__render_card_override_dates(
					$uri,
					$sub,
					$this->constant( 'primary_posttype' ),
					_x( 'Training Course Date from Meta-data', 'Card', 'geditorial-trained' )
				);

		echo '</div>';
	}

	protected function render_tools_html_before( $uri, $sub )
	{
		if ( FALSE === $this->postdate__render_before_override_dates(
			$this->constant( 'primary_posttype' ),
			$this->get_postdate_metakeys(),
			$uri,
			$sub,
			'tools'
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
		$this->check_settings( $sub, 'reports', 'per_page' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->posttype_overview_render_table( 'primary_posttype', $uri, $sub ) )
			return Info::renderNoReportsAvailable();
	}
}
