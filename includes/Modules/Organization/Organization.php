<?php namespace geminorum\gEditorial\Modules\Organization;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\WordPress\Taxonomy;

class Organization extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'  => 'organization',
			'title' => _x( 'Organization', 'Modules: Organization', 'geditorial' ),
			'desc'  => _x( 'Departments of Editorial', 'Modules: Organization', 'geditorial' ),
			'icon'  => 'bank',
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
				'archive_title',
				'archive_content',
				'archive_template',
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'assign_default_term',
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
			'primary_posttype' => [
				'featured' => _x( 'Department Badge', 'Posttype Featured', 'geditorial-organization' ),
			],
			'type_taxonomy' => [
				'meta_box_title'      => _x( 'Department Types', 'MetaBox Title', 'geditorial-organization' ),
				'tweaks_column_title' => _x( 'Department Types', 'Column Title', 'geditorial-organization' ),
			],
			'tweaks_column_title' => _x( 'Departments', 'Column Title', 'geditorial-organization' ),
			'meta_box_title'      => _x( 'Organization', 'MetaBox Title', 'geditorial-organization' ),
		];

		$strings['terms'] = [
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

				'content_embed_url' => [ 'type' => 'embed' ],
				'text_source_url'   => [ 'type' => 'text_source' ],
				'audio_source_url'  => [ 'type' => 'audio_source' ],
				'video_source_url'  => [ 'type' => 'video_source' ],
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
		return [ 'primary_posttype', 'primary_paired', 'primary_subterm', 'primary_taxonomy' ];
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
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__singleselect_terms_callback',
		], 'primary_posttype' );

		$this->paired_register_objects( 'primary_posttype', 'primary_paired', 'primary_subterm', [
			'show_in_nav_menus' => TRUE,
		] );

		if ( is_admin() ) {

			$this->register_default_terms( 'status_taxonomy' );

		} else {

			$this->filter( 'term_link', 3 );
		}
	}

	public function init_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'primary_posttype' ) )
			$this->_hook_paired_to( $posttype );
	}

	public function setup_restapi()
	{
		$this->_hook_paired_to( $this->constant( 'primary_posttype' ) );
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'primary_subterm' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'primary_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'post_updated_messages' );
				$this->filter( 'get_default_comment_status', 3 );

				$this->filter_false_module( 'meta', 'mainbox_callback', 12 );
				$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
				$this->filter_false_module( 'tweaks', 'metabox_parent' );
				remove_meta_box( 'pageparentdiv', $screen, 'side' );

				$this->class_metabox( $screen, 'mainbox' );
				add_meta_box( $this->classs( 'mainbox' ),
					$this->get_meta_box_title( 'primary_posttype', FALSE ),
					[ $this, 'render_mainbox_metabox' ],
					$screen,
					'side',
					'high'
				);

				$this->class_metabox( $screen, 'listbox' );
				add_meta_box( $this->classs( 'listbox' ),
					$this->get_meta_box_title_taxonomy( 'primary_paired', $screen->post_type, FALSE ),
					[ $this, 'render_listbox_metabox' ],
					$screen,
					'advanced',
					'low'
				);

				$this->_hook_paired_to( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );
				$this->filter( 'bulk_post_updated_messages', 2 );

				$this->_hook_screen_restrict_taxonomies();

				if ( $this->get_setting( 'admin_ordering', TRUE ) )
					$this->action( 'pre_get_posts' );

				$this->action_module( 'meta', 'column_row', 3 );
				$this->action_module( 'tweaks', 'column_attr' );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );

				$this->_hook_paired_to( $screen->post_type );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit-tags' == $screen->base ) {

				$this->_hook_paired_taxonomy_bulk_actions( $screen->post_type, $screen->taxonomy );

			} else if ( 'post' == $screen->base ) {

				if ( $subterms )
					remove_meta_box( $subterms.'div', $screen->post_type, 'side' );

				$this->class_metabox( $screen, 'pairedbox' );
				add_meta_box( $this->classs( 'pairedbox' ),
					$this->get_meta_box_title_posttype( 'primary_posttype' ),
					[ $this, 'render_pairedbox_metabox' ],
					$screen,
					'side'
				);

				add_action( $this->hook( 'render_pairedbox_metabox' ), [ $this, 'render_metabox' ], 10, 4 );

				$this->_hook_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_screen_restrict_paired();

				$this->action_module( 'meta', 'column_row', 3 );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );

				$this->_hook_store_metabox( $screen->post_type );
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

	public function term_link( $link, $term, $taxonomy )
	{
		if ( $this->constant( 'primary_paired' ) != $taxonomy )
			return $link;

		if ( $post_id = $this->paired_get_to_post_id( $term, 'primary_posttype', 'primary_paired' ) )
			return get_permalink( $post_id );

		return $link;
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

	// TODO: template helper for `postTiles` using badges
	// public function template_get_archive_content_default()
	// {
	// 	return ModuleTemplate::postTiles();
	// }

	public function post_updated( $post_id, $post_after, $post_before )
	{
		$this->paired_do_save_to_post_update( $post_after, $post_before, 'primary_posttype', 'primary_paired' );
	}

	public function save_post( $post_id, $post, $update )
	{
		// we handle updates on another action, @SEE: `post_updated()`
		if ( ! $update )
			$this->paired_do_save_to_post_new( $post, 'primary_posttype', 'primary_paired' );
	}

	public function wp_trash_post( $post_id )
	{
		$this->paired_do_trash_to_post( $post_id, 'primary_posttype', 'primary_paired' );
	}

	public function untrash_post( $post_id )
	{
		$this->paired_do_untrash_to_post( $post_id, 'primary_posttype', 'primary_paired' );
	}

	public function before_delete_post( $post_id )
	{
		$this->paired_do_before_delete_to_post( $post_id, 'primary_posttype', 'primary_paired' );
	}

	public function pre_get_posts( &$wp_query )
	{
		if ( $this->constant( 'primary_posttype' ) == $wp_query->get( 'post_type' ) ) {

			if ( $wp_query->is_admin ) {

				if ( ! isset( $_GET['orderby'] ) )
					$wp_query->set( 'orderby', 'menu_order' );

				if ( ! isset( $_GET['order'] ) )
					$wp_query->set( 'order', 'DESC' );
			}
		}
	}

	public function render_pairedbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );

		if ( $this->get_setting( 'quick_newpost' ) ) {

			$this->actions( 'render_pairedbox_metabox', $post, $box, NULL, 'pairedbox_issue' );

		} else {

			if ( ! Taxonomy::hasTerms( $this->constant( 'primary_paired' ) ) )
				MetaBox::fieldEmptyPostType( $this->constant( 'primary_posttype' ) );

			else
				$this->actions( 'render_pairedbox_metabox', $post, $box, NULL, 'pairedbox_issue' );
		}

		do_action( $this->base.'_meta_render_metabox', $post, $box, NULL, 'pairedbox_issue' );

		echo '</div>';
	}

	public function render_metabox( $post, $box, $fields = NULL, $context = NULL )
	{
		if ( $newpost = $this->get_setting( 'quick_newpost' ) )
			$this->do_render_thickbox_newpostbutton( $post, 'primary_posttype', 'newpost', [ 'target' => 'paired' ] );

		$this->paired_do_render_metabox( $post, 'primary_posttype', 'primary_paired', 'section_tax', $newpost );
	}

	public function store_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		$this->paired_do_store_metabox( $post, 'primary_posttype', 'primary_paired', 'section_tax' );
	}

	public function render_mainbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			$this->actions( 'render_metabox', $post, $box, NULL, 'mainbox' );

			do_action( 'geditorial_meta_render_metabox', $post, $box, NULL );

			MetaBox::fieldPostMenuOrder( $post );
			MetaBox::fieldPostParent( $post );
			MetaBox::singleselectTerms( $post->ID, [
				'taxonomy' => $this->constant( 'type_taxonomy' ),
				'posttype' => $post->post_type,
			] );

		echo '</div>';
	}

	public function render_listbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$this->paired_render_listbox_metabox( $post, $box, 'primary_posttype', 'primary_paired' );
	}

	public function get_linked_to_posts( $post = NULL, $single = FALSE, $published = TRUE )
	{
		return $this->paired_do_get_to_posts( 'primary_posttype', 'primary_paired', $post, $single, $published );
	}

	public function tweaks_column_attr( $post )
	{
		$this->paired_tweaks_column_attr( $post, 'primary_posttype', 'primary_paired' );
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'primary_posttype' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'primary_posttype', $counts ) );
	}
}
