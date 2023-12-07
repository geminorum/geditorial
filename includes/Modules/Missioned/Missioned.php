<?php namespace geminorum\gEditorial\Modules\Missioned;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;

class Missioned extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\BulkExports;
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreRestrictPosts;
	use Internals\LateChores;
	use Internals\PairedAdmin;
	use Internals\PairedCore;
	use Internals\PairedImports;
	// use Internals\PairedMetaBox;
	use Internals\PairedRest;
	use Internals\PairedRest;
	use Internals\PairedTools;
	use Internals\PostDate;
	use Internals\PostMeta;

	protected $deafults = [ 'multiple_instances' => TRUE ];

	public static function module()
	{
		return [
			'name'   => 'missioned',
			'title'  => _x( 'Missioned', 'Modules: Missioned', 'geditorial-admin' ),
			'desc'   => _x( 'Editorial Mission Management', 'Modules: Missioned', 'geditorial-admin' ),
			'icon'   => 'fullscreen-exit-alt',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'paired_force_parents',
				'paired_manage_restricted',
				'comment_status',
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
				$this->settings_supports_option( 'primary_posttype', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype' => 'mission',
			'primary_taxonomy' => 'mission_category',
			'primary_paired'   => 'missions',
			'span_taxonomy'    => 'mission_span',
			'type_taxonomy'    => 'mission_type',
			'status_taxonomy'  => 'mission_status',
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
				'primary_posttype' => _n_noop( 'Mission', 'Missions', 'geditorial-missioned' ),
				'primary_paired'   => _n_noop( 'Mission', 'Missions', 'geditorial-missioned' ),
				'primary_taxonomy' => _n_noop( 'Mission Category', 'Mission Categories', 'geditorial-missioned' ),
				'span_taxonomy'    => _n_noop( 'Mission Span', 'Mission Spans', 'geditorial-missioned' ),
				'type_taxonomy'    => _n_noop( 'Mission Type', 'Mission Types', 'geditorial-missioned' ),
				'status_taxonomy'  => _n_noop( 'Mission Status', 'Mission Statuses', 'geditorial-missioned' ),
			],
			'labels' => [
				'primary_posttype' => [
					'menu_name'      => _x( 'Missions', 'Label: `menu_name`', 'geditorial-missioned' ),
					'featured_image' => _x( 'Mission Poster', 'Label: Featured Image', 'geditorial-missioned' ),
					'metabox_title'  => _x( 'The Mission', 'Label: MetaBox Title', 'geditorial-missioned' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'column_icon_title' => _x( 'Missions', 'Misc: `column_icon_title`', 'geditorial-missioned' ),
		];

		$strings['metabox'] = [
			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'megabox_title' => _x( 'Participants on &ldquo;%1$s&rdquo;', 'Metabox: `megabox_title`', 'geditorial-missioned' ),
		];

		$strings['default_terms'] = [
			// 'type_taxonomy' => [
			// 	'' => _x( '', 'Type Taxonomy: Default Term', 'geditorial-missioned' ),
			// ],
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			$this->constant( 'primary_posttype' ) => [
				'over_title' => [ 'type' => 'title_before' ],
				'sub_title'  => [ 'type' => 'title_after' ],
				'lead'       => [ 'type' => 'postbox_html' ],

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

				'mission_code' => [
					'title'       => _x( 'Mission Code', 'Field Title', 'geditorial-missioned' ),
					'description' => _x( 'Unique Mission Code', 'Field Description', 'geditorial-missioned' ),
					'type'        => 'code',
					'quickedit'   => TRUE,
					'icon'        => 'nametag',
					'order'       => 100,
				],
			],
			// '_supported' => [],
		];
	}

	protected function paired_get_paired_constants()
	{
		return [
			'primary_posttype',
			'primary_paired',
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
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'primary_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
		], 'primary_posttype' );

		$this->register_taxonomy( 'span_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => '__checklist_reverse_terms_callback',
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'primary_posttype' );

		$this->register_taxonomy( 'type_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'primary_posttype' );

		$this->register_taxonomy( 'status_taxonomy', [
			'public'             => FALSE,
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'primary_posttype' );

		$this->paired_register_objects( 'primary_posttype', 'primary_paired' );

		if ( $this->get_setting( 'override_dates', TRUE ) )
			$this->latechores__init_post_aftercare( $this->constant( 'primary_posttype' ) );

		$this->action_module( 'pointers', 'post', 5, 201, 'paired_posttype' );
		// $this->action_module( 'pointers', 'post', 5, 202, 'paired_supported' );
		$this->filter_module( 'tabloid', 'view_data', 3, 9, 'paired_supported' );

		if ( is_admin() )
			return;

		$this->_hook_paired_override_term_link();
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'primary_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'get_default_comment_status', 3 );

				$this->_hook_post_updated_messages( 'primary_posttype' );
				$this->_hook_paired_mainbox( $screen );
				// $this->_hook_paired_listbox( $screen );
				// $this->pairedmetabox__hook_megabox( $screen ); // FIXME
				$this->pairedcore__hook_sync_paired();

			} else if ( 'edit' == $screen->base ) {

				$this->action_module( 'meta', 'column_row', 3 );

				$this->coreadmin__unset_columns( $screen->post_type );
				$this->coreadmin__hook_admin_ordering( $screen->post_type );
				$this->_hook_bulk_post_updated_messages( 'primary_posttype' );
				$this->pairedadmin__hook_tweaks_column_connected();
				$this->pairedcore__hook_sync_paired();
				$this->corerestrictposts__hook_screen_taxonomies( [
					'primary_taxonomy',
					'span_taxonomy',
					'type_taxonomy',
					'status_taxonomy',
				] );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit-tags' == $screen->base ) {

				$this->_hook_paired_taxonomy_bulk_actions( $screen->post_type, $screen->taxonomy );

			} else if ( 'post' == $screen->base ) {

				// TODO: add summary metabox
				// $this->_hook_paired_pairedbox( $screen );
				// $this->_hook_paired_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_paired_store_metabox( $screen->post_type );
				// $this->paired__hook_tweaks_column( $screen->post_type, 12 );
				// $this->paired__hook_screen_restrictposts();

				// $this->action_module( 'meta', 'column_row', 3 );
			}
		}
	}

	public function admin_menu()
	{
		if ( $this->role_can( 'import', NULL, TRUE ) )
			$this->_hook_submenu_adminpage( 'importitems' );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'primary_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	protected function _render_mainbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		MetaBox::singleselectTerms( $object->ID, [
			'taxonomy' => $this->constant( 'type_taxonomy' ),
			'posttype' => $object->post_type,
		] );

		MetaBox::singleselectTerms( $object->ID, [
			'taxonomy' => $this->constant( 'status_taxonomy' ),
			'posttype' => $object->post_type,
		] );

		parent::_render_mainbox_content( $object, $box, $context, $screen );
	}

	public function identified_default_posttype_identifier_metakey( $default, $posttype )
	{
		if ( $posttype == $this->constant( 'primary_posttype' ) )
			return Services\PostTypeFields::getPostMetaKey( 'mission_code' );

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
			return Services\PostTypeFields::getPostMetaKey( 'mission_code' );

		return $default;
	}

	// NOTE: only returns selected supported crossing fields
	public function pairedimports_import_types( $types, $linked, $posttypes, $module_key )
	{
		if ( ! \array_intersect( $this->posttypes(), $posttypes ) )
			return $types;

		if ( $field = Services\PostTypeFields::isAvailable( 'mission_code', $this->constant( 'primary_posttype' ), 'meta' ) )
			return array_merge( $types, [
				$field['name'] => $field['title'],
			] );

		return $types;
	}

	public function posttypefields_import_raw_data( $post, $data, $override, $check_access, $module )
	{
		if ( empty( $data ) || empty( $data['mission_code'] ) || $module !== 'meta' )
			return;

		if ( ! $post = WordPress\Post::get( $post ) )
			return;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return;

		$this->posttypefields_connect_paired_by( 'mission_code', $data['mission_code'], $post );
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
		return $this->paired_tools_render_tablelist( $uri, $sub, NULL,
			_x( 'Mission Tools', 'Header', 'geditorial-missioned' ) );
	}

	protected function render_tools_html_before( $uri, $sub )
	{
		return $this->paired_tools_render_before( $uri, $sub );
	}

	protected function render_tools_html_after( $uri, $sub )
	{
		return $this->paired_tools_render_card( $uri, $sub );
	}

	public function imports_settings( $sub )
	{
		$this->check_settings( $sub, 'imports', 'per_page' );
	}

	protected function render_imports_html( $uri, $sub )
	{
		echo Settings::toolboxColumnOpen( _x( 'Mission Imports', 'Header', 'geditorial-missioned' ) );

		if ( $this->get_setting( 'override_dates', TRUE ) )
			$this->postdate__render_card_override_dates(
				$uri,
				$sub,
				$this->constant( 'primary_posttype' ),
				_x( 'Mission Date from Meta-data', 'Card', 'geditorial-missioned' )
			);

		else
			return Info::renderNoImportsAvailable();

		echo '</div>';
	}

	protected function render_imports_html_before( $uri, $sub )
	{
		return $this->postdate__render_before_override_dates(
			$this->constant( 'primary_posttype' ),
			$this->get_postdate_metakeys(),
			$uri,
			$sub
		);
	}
}
