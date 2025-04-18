<?php namespace geminorum\gEditorial\Modules\Directorate;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\WordPress;

// TODO: selected committees as `views` on supported @SEE: `Yearly` Module
// TODO: dashboard summary for status taxonomy

class Directorate extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\BulkExports;
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\PairedAdmin;
	use Internals\PairedCore;
	use Internals\PairedFront;
	use Internals\PairedImports;
	use Internals\PairedMetaBox;
	use Internals\PairedRest;
	use Internals\PairedRowActions;
	use Internals\PairedTools;
	use Internals\PostMeta;
	use Internals\PostTypeFields;
	use Internals\PostTypeOverview;
	use Internals\QuickPosts;
	use Internals\TemplatePostType;

	protected $positions = [ 'primary_posttype' => 3 ];

		public static function module()
	{
		return [
			'name'     => 'directorate',
			'title'    => _x( 'Directorate', 'Modules: Directorate', 'geditorial-admin' ),
			'desc'     => _x( 'The Assembly of Editorial', 'Modules: Directorate', 'geditorial-admin' ),
			'icon'     => 'groups',
			'access'   => 'beta',
			'keywords' => [
				'committee',
				'pairedmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'_general' => [
				'quick_newpost',
				'multiple_instances',
				'paired_force_parents',
				'paired_manage_restricted',
				[
					'field'       => 'subterms_support',
					'title'       => _x( 'Sub-committee Support', 'Settings', 'geditorial-directorate' ),
					'description' => _x( 'Substitute taxonomy for the committees and supported post-types.', 'Settings', 'geditorial-directorate' ),
				],
				'paired_exclude_terms' => [
					NULL,
					$this->constant( 'primary_taxonomy' ),
					$this->get_taxonomy_label( 'primary_taxonomy', 'no_terms' ),
				],
			],
			'_roles' => [
				'custom_captype',
				'reports_roles' => [ NULL, $roles ],
				'imports_roles' => [ NULL, $roles ],
				'tools_roles'   => [ NULL, $roles ],
			],
			'_editlist' => [
				'admin_bulkactions',
				'admin_displaystates',
				'admin_ordering',
				'assign_default_term',
			],
			'_frontend' => [
				'contents_viewable',
				[
					'field'       => 'redirect_archives',
					'type'        => 'url',
					'title'       => _x( 'Redirect Archives', 'Settings', 'geditorial-directorate' ),
					'description' => _x( 'Redirects committee archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-directorate' ),
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
			'_reports' => [
				'append_identifier_code',
				'overview_taxonomies' => [ NULL, $this->get_posttype_taxonomies_list( 'primary_posttype' ) ],
				'overview_fields'     => [ NULL, $this->get_posttype_fields_list( 'primary_posttype', 'meta' ) ],
				'overview_units'      => [ NULL, $this->get_posttype_fields_list( 'primary_posttype', 'units' ) ],
			],
			'_constants' => [
				'subterm_shortcode_constant' => [ NULL, 'directorate-subcommittee' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype' => 'committee',
			'primary_paired'   => 'committees',
			'primary_taxonomy' => 'committee_category',
			'primary_subterm'  => 'subcommittee',
			'type_taxonomy'    => 'committee_type',
			'status_taxonomy'  => 'committee_status',

			'subterm_shortcode' => 'directorate-subcommittee',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'primary_posttype' => _n_noop( 'Committee', 'Committees', 'geditorial-directorate' ),
				'primary_paired'   => _n_noop( 'Committee', 'Committees', 'geditorial-directorate' ),
				'primary_taxonomy' => _n_noop( 'Committee Category', 'Committee Categories', 'geditorial-directorate' ),
				'primary_subterm'  => _n_noop( 'Subcommittee', 'Subcommittees', 'geditorial-directorate' ),
				'type_taxonomy'    => _n_noop( 'Committee Type', 'Committee Types', 'geditorial-directorate' ),
				'status_taxonomy'  => _n_noop( 'Committee Status', 'Committee Statuses', 'geditorial-directorate' ),
			],
			'labels' => [
				'primary_posttype' => [
					/* translators: `%s`: paired item title */
					'paired_connected_to' => _x( 'Member of %s Committee', 'Label: `paired_connected_to`', 'geditorial-directorate' ),
					'featured_image'      => _x( 'Committee Badge', 'Label: Featured Image', 'geditorial-directorate' ),
					'metabox_title'       => _x( 'The Committee', 'Label: `metabox_title`', 'geditorial-directorate' ),
				],
				'primary_paired' => [
					'metabox_title' => _x( 'In This Committee', 'Label: `metabox_title`', 'geditorial-directorate' ),
				],
				'primary_taxonomy' => [
					'menu_name' => _x( 'Categories', 'Label: `menu_name`', 'geditorial-directorate' ),
				],
				'type_taxonomy' => [
					'menu_name' => _x( 'Types', 'Label: Menu Name', 'geditorial-directorate' ),
				],
				'status_taxonomy' => [
					'menu_name' => _x( 'Statuses', 'Label: Menu Name', 'geditorial-directorate' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'column_icon_title' => _x( 'Committee', 'Misc: `column_icon_title`', 'geditorial-directorate' ),
		];

		$strings['metabox'] = [
			/* translators: `%1$s`: current post title, `%2$s`: posttype singular name */
			'listbox_title' => _x( '%2$s Members of &ldquo;%1$s&rdquo;', 'MetaBox: `listbox_title`', 'geditorial-directorate' ),
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'status_taxonomy' => [
				'working'  => _x( 'Working', 'Default Term', 'geditorial-directorate' ),
				'inactive' => _x( 'Inactive', 'Default Term', 'geditorial-directorate' ),
				'resolved' => _x( 'Resolved', 'Default Term', 'geditorial-directorate' ),
				'planned'  => _x( 'Planned', 'Default Term', 'geditorial-directorate' ),
				'pending'  => _x( 'Pending', 'Default Term', 'geditorial-directorate' ),
			],
		];
	}

	protected function get_global_fields()
	{
		return [
			'meta' => [
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
					'contact_string' => [ 'type' => 'contact' ],   // url/email/phone
					'website_url'    => [ 'type' => 'link' ],
					'email_address'  => [ 'type' => 'email' ],

					'featured_people' => [
						'title'       => _x( 'Directors', 'Field Title', 'geditorial-directorate' ),
						'description' => _x( 'People Who are Board Members of This Committee', 'Field Description', 'geditorial-directorate' ),
						'type'        => 'people',
						'quickedit'   => TRUE,
						'order'       => 90,
					],

					'committee_code' => [
						'title'       => _x( 'Committee Code', 'Field Title', 'geditorial-directorate' ),
						'description' => _x( 'Unique Committee Code', 'Field Description', 'geditorial-directorate' ),
						'type'        => 'code',
						'quickedit'   => TRUE,
						'bulkedit'    => FALSE,
						'icon'        => 'nametag',
						'order'       => 100,
					],
				],
				'_supported' => [
					'committee_number' => [
						'title'       => _x( 'Membership Number', 'Field Title', 'geditorial-directorate' ),
						'description' => _x( 'Unique Committee Membership Number', 'Field Description', 'geditorial-directorate' ),
						'type'        => 'code',
						'order'       => 100,
					],
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
		$this->add_posttype_fields_supported();

		$this->filter_module( 'identified', 'default_posttype_identifier_metakey', 2 );
		// $this->filter_module( 'identified', 'default_posttype_identifier_type', 2 ); // NOTE: no need: default is `code`
		$this->filter_module( 'static_covers', 'default_posttype_reference_metakey', 2 );

		$this->filter( 'pairedimports_import_types', 4, 20, FALSE, $this->base );
		$this->action( 'posttypefields_import_raw_data', 5, 9, FALSE, $this->base );

		$this->pairedcore__hook_append_identifier_code( 'committee_code' );
	}

	public function importer_init()
	{
		$this->filter_module( 'importer', 'fields', 2 );
		$this->filter_module( 'importer', 'prepare', 7 );
		$this->action_module( 'importer', 'saved', 2 );

		$this->pairedcore__hook_importer_before_import();
		$this->pairedcore__hook_importer_term_parents();

		$this->action_module( 'importer', 'posttype_taxonomies_after', 6 );
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

		$this->register_taxonomy( 'type_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'primary_posttype', [
			'is_viewable'     => $viewable,
			'custom_icon'     => 'screenoptions',
			'custom_captype'  => $captype,
			'single_selected' => TRUE,
		] );

		$this->register_taxonomy( 'status_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'primary_posttype', [
			'custom_icon'     => 'post-status',
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
			'custom_icon'    => $this->module->icon,
			'custom_captype' => $captype,
		] );

		$this->hook_paired_static_covers_secondaries();
		$this->hook_paired_tabloid_exclude_rendered();
		$this->hook_paired_tabloid_post_summaries_by_paired();
		$this->action_module( 'pointers', 'post', 5, 301, 'paired_posttype' );
		$this->action_module( 'pointers', 'post', 5, 302, 'paired_supported' );
		$this->filter_module( 'tabloid', 'post_summaries', 4, 120, 'paired_exports' );
		$this->filter_module( 'tabloid', 'post_summaries', 4, 220, 'paired_posttype' );
		$this->filter_module( 'papered', 'view_list', 5, 9, 'paired_posttype' );

		if ( $this->get_setting( 'subterms_support' ) )
			$this->register_shortcode( 'subterm_shortcode' );

		if ( is_admin() )
			return;

		if ( ! $viewable )
			return;

		$this->_hook_paired_exclude_from_subterm();
		$this->_hook_paired_override_term_link();
	}

	public function setup_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'primary_posttype' ) ) {
			$this->coreadmin__unset_columns( $posttype );
			$this->coreadmin__hook_taxonomy_display_states( 'status_taxonomy' );
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

				$this->_hook_editform_meta_summary( [
					'featured_people' => NULL,
					'committee_code'  => NULL,
				] );

				$this->posttype__media_register_headerbutton( 'primary_posttype' );
				$this->_hook_post_updated_messages( 'primary_posttype' );
				$this->_hook_paired_mainbox( $screen );
				$this->_hook_paired_listbox( $screen );
				$this->pairedcore__hook_sync_paired();

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->postmeta__hook_meta_column_row( $screen->post_type, TRUE );
				$this->coreadmin__unset_columns( $screen->post_type );
				$this->coreadmin__unset_views( $screen->post_type );
				$this->coreadmin__hook_taxonomy_display_states( 'status_taxonomy' );
				$this->coreadmin__hook_admin_ordering( $screen->post_type, 'menu_order', 'ASC' );
				$this->_hook_bulk_post_updated_messages( 'primary_posttype' );
				$this->pairedadmin__hook_tweaks_column_connected( $screen->post_type );
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
				$this->pairedimports__hook_append_import_button( $screen->post_type );
				$this->pairedrowactions__hook_for_supported_posttypes( $screen );
				$this->paired__hook_tweaks_column( $screen->post_type, 12 );
				$this->paired__hook_screen_restrictposts( 'reports', 13 );
				$this->postmeta__hook_meta_column_row( $screen->post_type, [
					'committee_number',
				] );
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

		if ( $this->get_setting( 'quick_newpost' ) ) {
			$this->_hook_submenu_adminpage( 'newpost' );
			$this->action_self( 'newpost_content', 4, 10, 'menu_order' );
		}
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'primary_posttype', [ 'reports' ] ) )
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
		return $this->templateposttype__include( $template, $this->constant( 'primary_posttype' ), FALSE );
	}

	protected function _render_mainbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		MetaBox::fieldPostMenuOrder( $object );
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
	}

	public function subterm_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listPosts( 'assigned',
			$this->constant( 'primary_posttype' ),
			$this->constant( 'primary_subterm' ),
			array_merge( [
				'post_id' => NULL,
			], (array) $atts ),
			$content,
			$this->constant( 'subterm_shortcode', $tag ),
			$this->key
		);
	}

	public function identified_default_posttype_identifier_metakey( $default, $posttype )
	{
		if ( $posttype == $this->constant( 'primary_posttype' ) )
			return Services\PostTypeFields::getPostMetaKey( 'committee_code' );

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
			return Services\PostTypeFields::getPostMetaKey( 'committee_code' );

		return $default;
	}

	// NOTE: only returns selected supported crossing fields
	public function pairedimports_import_types( $types, $linked, $posttypes, $module_key )
	{
		if ( ! array_intersect( $this->posttypes(), $posttypes ) )
			return $types;

		if ( $field = Services\PostTypeFields::isAvailable( 'committee_code', $this->constant( 'primary_posttype' ), 'meta' ) )
			return array_merge( $types, [
				$field['name'] => $field['title'],
			] );

		return $types;
	}

	public function posttypefields_import_raw_data( $post, $data, $override, $check_access, $module )
	{
		if ( empty( $data ) || empty( $data['committee_code'] ) || $module !== 'meta' )
			return;

		if ( ! $post = WordPress\Post::get( $post ) )
			return;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return;

		$this->posttypefields_connect_paired_by( 'committee_code', $data['committee_code'], $post );
	}

	private function get_importer_fields( $posttype = NULL )
	{
		if ( $field = Services\PostTypeFields::isAvailable( 'committee_code', $this->constant( 'primary_posttype' ), 'meta' ) )
			return [
				sprintf( '%s__%s', $this->key, $field['name'] ) => $field['title'],
			];

		return [];
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

		if ( 'committee_code' !== Core\Text::stripPrefix( $field, sprintf( '%s__', $this->key ) ) )
			return $value;

		$type   = $this->constant( 'primary_posttype' );
		$codes = Helper::getSeparated( $value );
		$list  = [];

		foreach ( $codes as $code )
			if ( $parent = Services\PostTypeFields::getPostByField( 'committee_code', $code, $type, TRUE ) )
				$list[] = WordPress\Post::fullTitle( $parent, TRUE );

		return WordPress\Strings::getJoined( $list, '', '', $value );
	}

	public function importer_saved( $post, $atts = [] )
	{
		if ( ! $post || ! $this->posttype_supported( $post->post_type ) )
			return;

		$fields  = $this->get_importer_fields( $post->post_type );
		$already = FALSE;

		foreach ( $atts['map'] as $offset => $field ) {

			if ( ! in_array( $field, $fields ) )
				continue;

			if ( 'committee_code' === Core\Text::stripPrefix( $field, sprintf( '%s__', $this->key ) ) ) {

				$type  = $this->constant( 'primary_posttype' );
				$codes = Helper::getSeparated( $atts['raw'][$offset] );
				$list  = [];

				foreach ( $codes as $code )
					if ( $parent = Services\PostTypeFields::getPostByField( 'committee_code', $code, $type, TRUE ) )
						$list[] = $parent;

				if ( count( $list ) )
					$this->paired_do_connection( 'store',
						$post,
						$list,
						'primary_posttype',
						'primary_paired',
						$this->get_setting( 'multiple_instances' ) ? $atts['override'] : FALSE,
					);

				$already = TRUE;

				break;
			}
		}

		if ( $already || ! $this->get_setting( 'paired_force_parents' ) )
			return;

		$this->do_force_assign_parents( $post, $this->constant( 'primary_paired' ) );
	}

	public function importer_posttype_taxonomies_after( $posttype, $taxonomies, $name_template, $before, $after, $after_title )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return;

		$taxonomy = WordPress\Taxonomy::object( $this->constant( 'primary_paired' ) );
		$dropdown = wp_dropdown_categories( [
			'taxonomy'          => $taxonomy->name,
			'name'              => sprintf( $name_template, $taxonomy->name ),
			'hierarchical'      => $taxonomy->hierarchical,
			'show_option_none'  => Settings::showOptionNone(),
			'option_none_value' => '0',
			'hide_if_empty'     => TRUE,
			'hide_empty'        => FALSE,
			'echo'              => FALSE,
		] );

		if ( empty( $dropdown ) )
			return;

		echo $before;
		echo Core\HTML::escape( $taxonomy->labels->menu_name );
		echo $after_title;
			echo $dropdown;
		echo $after;
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
			_x( 'Directorate Tools', 'Header', 'geditorial-directorate' ) );

			$this->paired_tools_render_card( $uri, $sub );

		echo '</div>';
	}

	protected function render_tools_html_before( $uri, $sub )
	{
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
