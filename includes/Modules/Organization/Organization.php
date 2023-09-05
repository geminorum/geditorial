<?php namespace geminorum\gEditorial\Modules\Organization;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\WordPress;

class Organization extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\BulkExports;
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\CoreTemplate;
	use Internals\PairedAdmin;
	use Internals\PairedCore;
	use Internals\PairedImports; // FIXME: commit the internal
	use Internals\PairedRest; // FIXME: commit the internal
	use Internals\PairedTools;
	use Internals\PostMeta;
	use Internals\PostTypeFields;

	public static function module()
	{
		return [
			'name'   => 'organization',
			'title'  => _x( 'Organization', 'Modules: Organization', 'geditorial' ),
			'desc'   => _x( 'Departments of Editorial', 'Modules: Organization', 'geditorial' ),
			'icon'   => 'bank',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'multiple_instances',
				'paired_force_parents',
				[
					'field'       => 'subterms_support',
					'title'       => _x( 'Sub-departments', 'Settings', 'geditorial-organization' ),
					'description' => _x( 'Substitute taxonomy for the departments and supported post-types.', 'Settings', 'geditorial-organization' ),
				],
				'comment_status',
				'paired_exclude_terms' => [
					NULL,
					$this->constant( 'primary_taxonomy' ),
					$this->get_taxonomy_label( 'primary_taxonomy', 'no_terms' ),
				],
			],
			'_editlist' => [
				'admin_ordering',
			],
			'_editpost' => [
				'assign_default_term',
			],
			'_frontend' => [
				'posttype_viewable',
				[
					'field'       => 'redirect_archives',
					'type'        => 'url',
					'title'       => _x( 'Redirect Archives', 'Settings', 'geditorial-organization' ),
					'description' => _x( 'Redirects department archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-organization' ),
					'placeholder' => Core\URL::home( 'archives' ),
				],
			],
			'_content' => [
				'archive_override',
				'archive_title' => [ NULL, $this->get_posttype_label( 'primary_posttype', 'all_items' ) ],
				'archive_content',
				'archive_template',
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'comment_status',
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'primary_posttype', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype' => 'department',
			'primary_paired'   => 'departments',
			'primary_taxonomy' => 'department_category',
			'primary_subterm'  => 'subdepartment',
			'type_taxonomy'    => 'department_type',
			'status_taxonomy'  => 'department_status',

			'subterm_shortcode' => 'organization-subdepartment',
		];
	}

	protected function get_module_icons()
	{
		return [
			'post_types' => [
				'primary_posttype' => NULL,
			],
			'taxonomies' => [
				'type_taxonomy'   => 'admin-media',
				'primary_subterm' => NULL,
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'primary_posttype' => _n_noop( 'Department', 'Departments', 'geditorial-organization' ),
				'primary_paired'   => _n_noop( 'Department', 'Departments', 'geditorial-organization' ),
				'primary_taxonomy' => _n_noop( 'Department Category', 'Department Categories', 'geditorial-organization' ),
				'primary_subterm'  => _n_noop( 'Sub-department', 'Sub-departments', 'geditorial-organization' ),
				'type_taxonomy'    => _n_noop( 'Department Type', 'Department Types', 'geditorial-organization' ),
				'status_taxonomy'  => _n_noop( 'Department Status', 'Department Statuses', 'geditorial-organization' ),
			],
			'labels' => [
				'primary_posttype' => [
					'featured_image' => _x( 'Department Badge', 'Label: Featured Image', 'geditorial-organization' ),
					'metabox_title'  => _x( 'The Department', 'Label: MetaBox Title', 'geditorial-organization' ),
				],
				'primary_paired' => [
					'metabox_title' => _x( 'In This Department', 'Label: MetaBox Title', 'geditorial-organization' ),
				],
				'primary_taxonomy' => [
					'menu_name' => _x( 'Categories', 'Label: Menu Name', 'geditorial-organization' ),
				],
				'type_taxonomy' => [
					'menu_name' => _x( 'Types', 'Label: Menu Name', 'geditorial-organization' ),
				],
				'status_taxonomy' => [
					'menu_name' => _x( 'Statuses', 'Label: Menu Name', 'geditorial-organization' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'column_icon_title' => _x( 'Department', 'Misc: `column_icon_title`', 'geditorial-organization' ),
		];

		$strings['metabox'] = [
			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'listbox_title' => _x( '%2$s Members of &ldquo;%1$s&rdquo;', 'Metabox: `listbox_title`', 'geditorial-organization' ),
		];

		$strings['default_terms'] = [
			'status_taxonomy' => [
				'working'  => _x( 'Working', 'Default Term', 'geditorial-organization' ),
				'inactive' => _x( 'Inactive', 'Default Term', 'geditorial-organization' ),
				'resolved' => _x( 'Resolved', 'Default Term', 'geditorial-organization' ),
				'planned'  => _x( 'Planned', 'Default Term', 'geditorial-organization' ),
				'pending'  => _x( 'Pending', 'Default Term', 'geditorial-organization' ),
			],
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

				'venue_string'   => [ 'type' => 'venue' ],
				'contact_string' => [ 'type' => 'contact' ], // url/email/phone
				'phone_number'   => [ 'type' => 'phone' ],
				'mobile_number'  => [ 'type' => 'mobile' ],

				'website_url'    => [ 'type' => 'link' ],
				'email_address'  => [ 'type' => 'email' ],
				'postal_address' => [ 'type' => 'address' ],
				'postal_code'    => [ 'type' => 'postcode' ],

				'organization_code' => [
					'title'       => _x( 'Organization Code', 'Field Title', 'geditorial-organization' ),
					'description' => _x( 'Unique Organization Code', 'Field Description', 'geditorial-organization' ),
					'type'        => 'code',
					'quickedit'   => TRUE,
					'icon'        => 'nametag',
					'order'       => 100,
				],
			],
			'_supported' => [
				'organization_number' => [
					'title'       => _x( 'Organization Number', 'Field Title', 'geditorial-organization' ),
					'description' => _x( 'Unique Organization Number', 'Field Description', 'geditorial-organization' ),
					'type'        => 'code',
					'order'       => 100,
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
			'primary_taxonomy'
		];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'primary_posttype' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'primary_posttype' ) );
		$this->add_posttype_fields_supported();

		$this->filter_module( 'identified', 'default_posttype_identifier_metakey', 2 );
		// $this->filter_module( 'identified', 'default_posttype_identifier_type', 2 ); // NOTE: no need: default is `code`
		$this->filter_module( 'static_covers', 'default_posttype_reference_metakey', 2 );

		$this->filter( 'pairedimports_import_types', 4, 20, FALSE, $this->base );
		$this->action( 'posttypefields_import_raw_data', 5, 9, FALSE, $this->base );

		if ( ! is_admin() )
			return;

		$this->filter_module( 'importer', 'fields', 2 );
		$this->filter_module( 'importer', 'prepare', 7 );
		$this->action_module( 'importer', 'saved', 2 );
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

		$this->register_taxonomy( 'type_taxonomy', [
			'hierarchical'       => TRUE,
			// 'meta_box_cb'        => '__singleselect_terms_callback',
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'primary_posttype' );

		$this->register_taxonomy( 'status_taxonomy', [
			'public'             => FALSE,
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__singleselect_terms_callback',
		], 'primary_posttype' );

		$this->paired_register_objects( 'primary_posttype', 'primary_paired', 'primary_subterm' );

		$this->action_module( 'pointers', 'post', 5, 201, 'paired_posttype' );
		$this->action_module( 'pointers', 'post', 5, 202, 'paired_supported' );
		$this->filter_module( 'tabloid', 'view_data', 3, 9, 'paired_supported' );

		if ( $this->get_setting( 'subterms_support' ) )
			$this->register_shortcode( 'subterm_shortcode' );

		$this->_hook_posttype_viewable( $this->constant( 'primary_posttype' ) );

		if ( is_admin() )
			return;

		$this->_hook_paired_exclude_from_subterm();
		$this->_hook_paired_override_term_link();
	}

	// public function setup_ajax()
	// {
	// 	if ( $posttype = $this->is_inline_save_posttype( $this->posttypes() ) )
	// 		$this->pairedadmin__hook_tweaks_column_connected();
	// }

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
				$this->_hook_paired_listbox( $screen );
				$this->pairedcore__hook_sync_paired();

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->action_module( 'meta', 'column_row', 3 );

				$this->coreadmin__unset_columns( $screen->post_type );
				$this->coreadmin__hook_admin_ordering( $screen->post_type, 'menu_order', 'ASC' );
				$this->_hook_bulk_post_updated_messages( 'primary_posttype' );
				$this->pairedadmin__hook_tweaks_column_connected();
				$this->pairedcore__hook_sync_paired();
				$this->corerestrictposts__hook_screen_taxonomies( [
					'primary_subterm',
					'primary_taxonomy',
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
				$this->_hook_paired_pairedbox( $screen );
				$this->_hook_paired_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_paired_store_metabox( $screen->post_type );
				$this->paired__hook_tweaks_column( $screen->post_type, 8 );
				$this->paired__hook_screen_restrictposts( FALSE, 9 );

				$this->action_module( 'meta', 'column_row', 3 );
			}
		}

		// only for supported posttypes
		$this->remove_taxonomy_submenu( $subterms );

		if ( Settings::isDashboard( $screen ) )
			$this->filter_module( 'calendar', 'post_row_title', 4, 12 );
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

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'primary_posttype' ) ) ) {

			if ( $post_id = $this->paired_get_to_post_id( get_queried_object(), 'primary_posttype', 'primary_paired' ) )
				Core\WordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_post_type_archive( $this->constant( 'primary_posttype' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				Core\WordPress::redirect( $redirect, 301 );
		}
	}

	public function template_include( $template )
	{
		return $this->coretemplate__include_for_posttype( $template, $this->constant( 'primary_posttype' ), FALSE );
	}

	protected function _render_mainbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		parent::_render_mainbox_content( $object, $box, $context, $screen );

		MetaBox::singleselectTerms( $object->ID, [
			'taxonomy' => $this->constant( 'type_taxonomy' ),
			'posttype' => $object->post_type,
		] );
	}

	public function subterm_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listPosts( 'assigned',
			$this->constant( 'primary_posttype' ),
			$this->constant( 'primary_subterm' ),
			$atts,
			$content,
			$this->constant( 'subterm_shortcode', $tag ),
			$this->key
		);
	}

	public function identified_default_posttype_identifier_metakey( $default, $posttype )
	{
		if ( $posttype == $this->constant( 'primary_posttype' ) )
			return gEditorial()->module( 'meta' )->get_postmeta_key( 'organization_code' );

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
			return gEditorial()->module( 'meta' )->get_postmeta_key( 'organization_code' );

		return $default;
	}

	// NOTE: only returns selected supported crossing fields
	public function pairedimports_import_types( $types, $linked, $posttypes, $module_key )
	{
		if ( ! array_intersect( $this->posttypes(), $posttypes ) )
			return $types;

		$field = gEditorial()->module( 'meta' )->get_posttype_field_args( 'organization_code', $this->constant( 'primary_posttype' ) );

		return $field ? array_merge( $types, [
			$field['name'] => $field['title'],
		] ) : $types;
	}

	public function posttypefields_import_raw_data( $post, $data, $override, $check_access, $module )
	{
		if ( empty( $data ) || empty( $data['organization_code'] ) || $module !== 'meta' )
			return;

		if ( ! $post = WordPress\Post::get( $post ) )
			return;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return;

		$this->posttypefields_connect_paired_by( 'organization_code', $data['organization_code'], $post );
	}

	private function get_importer_fields( $posttype = NULL )
	{
		$field = gEditorial()->module( 'meta' )->get_posttype_field_args( 'organization_code', $this->constant( 'primary_posttype' ) );

		return $field ? [ sprintf( '%s__%s', $this->key, $field['name'] ) => $field['title'] ] : [];
	}

	public function importer_fields( $fields, $posttype )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return $fields;

		return array_merge( $fields, $this->get_importer_fields( $posttype ) );
	}

	public function importer_prepare( $value, $posttype, $field, $header, $raw, $source_id, $all_taxonomies )
	{
		if ( ! $this->posttype_supported( $posttype ) || empty( $value ) )
			return $value;

		if ( ! in_array( $field, array_keys( $this->get_importer_fields( $posttype ) ) ) )
			return $value;

		if ( 'organization_code' !== Core\Text::stripPrefix( $field, sprintf( '%s__', $this->key ) ) )
			return $value;

		$codes = Helper::getSeparated( $value );
		$list  = [];

		foreach ( $codes as $code )
			if ( $parent = $this->posttypefields_get_post_by( 'organization_code', $code, 'primary_posttype', TRUE ) )
				$list[] = WordPress\Post::fullTitle( $parent, TRUE );

		return WordPress\Strings::getJoined( $list, '', '', $value );
	}

	public function importer_saved( $post, $atts = [] )
	{
		if ( ! $post || ! $this->posttype_supported( $post->post_type ) )
			return;

		$fields = $this->get_importer_fields( $post->post_type );

		foreach ( $atts['map'] as $offset => $field ) {

			if ( ! in_array( $field, $fields ) )
				continue;

			if ( 'organization_code' === Core\Text::stripPrefix( $field, sprintf( '%s__', $this->key ) ) ) {

				$codes = Helper::getSeparated( $atts['raw'][$offset] );
				$list  = [];

				foreach ( $codes as $code )
					if ( $parent = $this->posttypefields_get_post_by( 'organization_code', $code, 'primary_posttype', TRUE ) )
						$list[] = $parent;

				if ( count( $list ) )
					$this->paired_do_connection( 'store',
						$post,
						$list,
						'primary_posttype',
						'primary_paired',
						$this->get_setting( 'multiple_instances' ) ? $atts['override'] : FALSE,
					);

				break;
			}
		}
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );
				$this->paired_tools_handle_tablelist( 'primary_posttype', 'primary_paired' );
			}
		}

		Scripts::enqueueThickBox();
	}

	protected function render_tools_html( $uri, $sub )
	{
		return $this->paired_tools_render_tablelist( 'primary_posttype', 'primary_paired', NULL,
			_x( 'Organization Tools', 'Header', 'geditorial-organization' ) );
	}

	protected function render_tools_html_after( $uri, $sub )
	{
		$this->paired_tools_render_card( 'primary_posttype', 'primary_paired' );
	}
}
