<?php namespace geminorum\gEditorial\Modules\Organization;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\WordPress\Taxonomy;

class Organization extends gEditorial\Module
{

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
				[
					'field'       => 'redirect_archives',
					'type'        => 'url',
					'title'       => _x( 'Redirect Archives', 'Settings', 'geditorial-organization' ),
					'description' => _x( 'Redirects department archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-organization' ),
					'placeholder' => URL::home( 'archives' ),
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
				'primary_paired' => NULL,
				'type_taxonomy'  => 'admin-media',
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

	protected function get_taxonomies_for_restrict_manage_posts()
	{
		return [
			'primary_subterm',
			'primary_taxonomy',
			'type_taxonomy',
			'status_taxonomy',
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

		if ( $this->get_setting( 'subterms_support' ) )
			$this->register_shortcode( 'subterm_shortcode' );

		if ( is_admin() )
			return;

		$this->_hook_paired_exclude_from_subterm();
		$this->_hook_paired_override_term_link();
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save_posttype( 'primary_posttype' ) )
			$this->_hook_paired_sync_primary_posttype();
	}

	public function setup_restapi()
	{
		$this->_hook_paired_sync_primary_posttype();
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
				$this->_hook_paired_listbox( $screen );
				$this->_hook_paired_sync_primary_posttype();

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->action_module( 'meta', 'column_row', 3 );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );

				$this->_hook_admin_ordering( $screen->post_type );
				$this->_hook_screen_restrict_taxonomies();
				$this->_hook_bulk_post_updated_messages( 'primary_posttype' );
				$this->_hook_paired_sync_primary_posttype();
				$this->_hook_paired_tweaks_column_attr();
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

				$this->_hook_screen_restrict_paired();
				$this->_hook_paired_store_metabox( $screen->post_type );

				$this->action_module( 'meta', 'column_row', 3 );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}
		}

		// only for supported posttypes
		$this->remove_taxonomy_submenu( $subterms );

		if ( Settings::isDashboard( $screen ) )
			$this->filter_module( 'calendar', 'post_row_title', 4, 12 );
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

			$term = get_queried_object();

			if ( $post_id = $this->paired_get_to_post_id( $term, 'primary_posttype', 'primary_paired' ) )
				WordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_post_type_archive( $this->constant( 'primary_posttype' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				WordPress::redirect( $redirect, 301 );
		}
	}

	public function template_include( $template )
	{
		return $this->do_template_include( $template, 'primary_posttype', NULL, FALSE );
	}

	protected function _render_mainbox_extra( $post, $box, $context = 'mainbox' )
	{
		parent::_render_mainbox_extra( $post, $box );

		MetaBox::singleselectTerms( $post->ID, [
			'taxonomy' => $this->constant( 'type_taxonomy' ),
			'posttype' => $post->post_type,
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
}
