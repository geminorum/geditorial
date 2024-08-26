<?php namespace geminorum\gEditorial\Modules\Personage;

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
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class Personage extends gEditorial\Module
{
	use Internals\BulkExports;
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreRestrictPosts;
	use Internals\CoreRowActions;
	use Internals\DashboardSummary;
	use Internals\LateChores;
	use Internals\MetaBoxCustom;
	use Internals\MetaBoxMain;
	use Internals\PostMeta;
	use Internals\PostTypeFields;
	use Internals\PostTypeOverview;

	// https://github.com/washingtonstateuniversity/WSU-People-Directory

	protected $positions     = [ 'primary_posttype' => 2 ];
	protected $priority_init = 9;

	public static function module()
	{
		return [
			'name'     => 'personage',
			'title'    => _x( 'Personage', 'Modules: Personage', 'geditorial-admin' ),
			'desc'     => _x( 'Human Resource Management for Editorial', 'Modules: Personage', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'person-bounding-box' ],
			'access'   => 'beta',
			'keywords' => [
				'human',
				'people',
				'cptmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();
		$terms = WordPress\Taxonomy::listTerms( $this->constant( 'status_taxonomy' ) );
		$empty = $this->get_taxonomy_label( 'status_taxonomy', 'no_items_available', NULL, 'no_terms' );

		return [
			'_roles' => [
				'custom_captype',
				'reports_roles' => [ NULL, $roles ],
			],
			'_dashboard' => [
				'dashboard_widgets',
				'summary_parents',
				'summary_excludes' => [
					NULL,
					WordPress\Taxonomy::listTerms( $this->constant( 'status_taxonomy' ) ),
					$this->get_taxonomy_label( 'status_taxonomy', 'no_items_available', NULL, 'no_terms' ),
				],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_supports' => [
				'public_statuses' => [ NULL, $terms, $empty ],
				'assign_default_term',
				'thumbnail_support',
				$this->settings_supports_option( 'primary_posttype', [
					'excerpt',
					'thumbnail',
					'comments',
					'date-picker',
				], [
					'title',
					'author',
				] ),
			],
			'_editlist' => [
				'admin_bulkactions',
				'admin_displaystates',
				'show_in_quickedit' => [ $this->get_taxonomy_show_in_quickedit_desc( 'status_taxonomy' ), '1' ],
			],
			'_frontend' => [
				'contents_viewable' => [ NULL, FALSE ],
				'insert_content',
				'insert_cover',
				'insert_priority',
			],
			'_reports' => [
				'overview_taxonomies' => [ NULL, $this->get_posttype_taxonomies_list( 'primary_posttype' ) ],
				'overview_fields'     => [ NULL, $this->get_posttype_fields_list( 'primary_posttype', 'meta' ) ],
				'overview_units'      => [ NULL, $this->get_posttype_fields_list( 'primary_posttype', 'units' ) ],
			],
			'_importer' => [
				[
					'field'       => 'import_check_identity_number',
					'title'       => _x( 'Check Identity Number', 'Setting Title', 'geditorial-personage' ),
					'description' => _x( 'Validates Identity Number prior to importing data.', 'Setting Description', 'geditorial-personage' ),
				],
				[
					'field'       => 'import_fill_post_title',
					'title'       => _x( 'Fill Post Title', 'Setting Title', 'geditorial-personage' ),
					'description' => _x( 'Tries to fill the post title with meta-data prior to importing data.', 'Setting Description', 'geditorial-personage' ),
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype' => 'human',
			'primary_taxonomy' => 'human_group',
			'status_taxonomy'  => 'human_status',

			'term_empty_identity_number' => 'identity-number-empty',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'primary_taxonomy' => NULL,
				'status_taxonomy'  => 'post-status',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'primary_posttype'   => _n_noop( 'Human', 'Humans', 'geditorial-personage' ),
				'primary_taxonomy'   => _n_noop( 'Human Group', 'Humans Groups', 'geditorial-personage' ),
				'status_taxonomy'    => _n_noop( 'Human Status', 'Human Statuses', 'geditorial-personage' ),
			],
			'labels' => [
				'primary_posttype' => [
					'featured_image' => _x( 'Profile Picture', 'Label: Featured Image', 'geditorial-personage' ),
					'metabox_title'  => _x( 'Personage', 'Label: MetaBox Title', 'geditorial-personage' ),
					'excerpt_label'  => _x( 'Biography', 'Label: `excerpt_label`', 'geditorial-personage' ),
				],
				'primary_taxonomy' => [
					'menu_name'            => _x( 'Groups', 'Label: Menu Name', 'geditorial-personage' ),
					'show_option_all'      => _x( 'Humans Groups', 'Label: Show Option All', 'geditorial-personage' ),
					'show_option_no_items' => _x( '(Un-Grouped)', 'Label: Show Option No Terms', 'geditorial-personage' ),
				],
				'status_taxonomy' => [
					'menu_name'            => _x( 'Statuses', 'Label: Menu Name', 'geditorial-personage' ),
					'show_option_all'      => _x( 'Statuses', 'Label: Show Option All', 'geditorial-personage' ),
					'show_option_no_items' => _x( '(Unknown)', 'Label: Show Option No Terms', 'geditorial-personage' ),
				],
			],
			'defaults' => [
				'primary_taxonomy' => [
					'name'        => _x( '[Ungrouped]', 'Default Term: Name', 'geditorial-personage' ),
					'description' => _x( 'Ungrouped Humans', 'Default Term: Description', 'geditorial-personage' ),
					'slug'        => 'ungrouped',
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['dashboard'] = [
			'current' => [ 'widget_title' => _x( 'Your Team Human Status Summary', 'Dashboard Widget Title', 'geditorial-personage' ), ],
			'all'     => [ 'widget_title' => _x( 'Editorial Human Status Summary', 'Dashboard Widget Title', 'geditorial-personage' ), ],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'status_taxonomy' => [
				'active'      => _x( 'Active', 'Default Term: `Status`', 'geditorial-personage' ),
				'inactive'    => _x( 'Inactive', 'Default Term: `Status`', 'geditorial-personage' ),
				'deceased'    => _x( 'Deceased', 'Default Term: `Status`', 'geditorial-personage' ),
				'martyred'    => _x( 'Martyred', 'Default Term: `Status`', 'geditorial-personage' ),
				'expelled'    => _x( 'Expelled', 'Default Term: `Status`', 'geditorial-personage' ),
				'dismissed'   => _x( 'Dismissed', 'Default Term: `Status`', 'geditorial-personage' ),
				'hushed'      => _x( 'Hushed', 'Default Term: `Status`', 'geditorial-personage' ),
				'recruit'     => _x( 'Recruit', 'Default Term: `Status`', 'geditorial-personage' ),
				'concurrency' => _x( 'Concurrency', 'Default Term: `Status`', 'geditorial-personage' ),
			],
		];
	}

	public function get_global_fields()
	{
		$primary = $this->constant( 'primary_posttype' );

		return [ 'meta' => [
			$primary => [
				'first_name' => [
					'title'          => _x( 'First Name', 'Field Title', 'geditorial-personage' ),
					'description'    => _x( 'Given Name of the Person', 'Field Description', 'geditorial-personage' ),
					'data_length'    => 25,
					'quickedit'      => TRUE,
					'import_ignored' => TRUE,
					'order'          => 10,
				],
				'middle_name' => [
					'title'       => _x( 'Middle Name', 'Field Title', 'geditorial-personage' ),
					'description' => _x( 'Middle Name of the Person', 'Field Description', 'geditorial-personage' ),
					'order'       => 10,
				],
				'last_name' => [
					'title'          => _x( 'Last Name', 'Field Title', 'geditorial-personage' ),
					'description'    => _x( 'Family Name of the Person', 'Field Description', 'geditorial-personage' ),
					'data_length'    => 25,
					'quickedit'      => TRUE,
					'import_ignored' => TRUE,
					'order'          => 10,
				],
				'fullname' => [
					'title'          => _x( 'Full Name', 'Field Title', 'geditorial-personage' ),
					'description'    => _x( 'Full Name of the Person', 'Field Description', 'geditorial-personage' ),
					'data_length'    => 45,
					'quickedit'      => TRUE,
					'import_ignored' => TRUE,
					'order'          => 11,
				],
				'pseudonym' => [
					'title'          => _x( 'Pseudonym', 'Field Title', 'geditorial-personage' ),
					'description'    => _x( 'Alias Name of the Person', 'Field Description', 'geditorial-personage' ),
					'quickedit'      => TRUE,
					'import_ignored' => TRUE,
					'order'          => 11,
				],
				'father_name' => [
					'title'          => _x( 'Father Name', 'Field Title', 'geditorial-personage' ),
					'description'    => _x( 'Name of the Father of the Person', 'Field Description', 'geditorial-personage' ),
					'quickedit'      => TRUE,
					'import_ignored' => TRUE,
					'order'          => 12,
				],
				'father_postid' => [
					'title'       => _x( 'Father Profile', 'Field Title', 'geditorial-personage' ),
					'description' => _x( 'Profile of the Father of the Person', 'Field Description', 'geditorial-personage' ),
					'type'        => 'post',
					'posttype'    => $primary,
					'order'       => 13,
				],
				'mother_name' => [
					'title'       => _x( 'Mother Name', 'Field Title', 'geditorial-personage' ),
					'description' => _x( 'Name of the Mother of the Person', 'Field Description', 'geditorial-personage' ),
					'order'       => 14,
				],
				'mother_postid' => [
					'title'       => _x( 'Mother Profile', 'Field Title', 'geditorial-personage' ),
					'description' => _x( 'Profile of the Mother of the Person', 'Field Description', 'geditorial-personage' ),
					'type'        => 'post',
					'posttype'    => $primary,
					'order'       => 15,
				],
				'spouse_name' => [
					'title'       => _x( 'Spouse Name', 'Field Title', 'geditorial-personage' ),
					'description' => _x( 'Name of the Spouse of the Person', 'Field Description', 'geditorial-personage' ),
					'order'       => 16,
				],
				'spouse_postid' => [
					'title'       => _x( 'Spouse Profile', 'Field Title', 'geditorial-personage' ),
					'description' => _x( 'Profile of the Spouse of the Person', 'Field Description', 'geditorial-personage' ),
					'type'        => 'post',
					'posttype'    => $primary,
					'order'       => 17,
				],
				'identity_number' => [
					'title'       => _x( 'Identity Number', 'Field Title', 'geditorial-personage' ),
					'description' => _x( 'Unique National Identity Number', 'Field Description', 'geditorial-personage' ),
					'icon'        => [ 'misc-512', 'gorbeh-fingerprint' ],
					'type'        => 'identity',
					'quickedit'   => TRUE,
					'order'       => 1,
				],
				'passport_number' => [
					'title'       => _x( 'Passport Number', 'Field Title', 'geditorial-personage' ),
					'description' => _x( 'Personagel Passport Number', 'Field Description', 'geditorial-personage' ),
					'type'        => 'code',
					'order'       => 200,
					'sanitize'    => [ $this, 'sanitize_passport_number' ],
				],

				'website_url'    => [ 'type' => 'link',    'order' => 610 ],
				'wiki_url'       => [ 'type' => 'link',    'order' => 620 ],
				'email_address'  => [ 'type' => 'email',   'order' => 630 ],
				'contact_string' => [ 'type' => 'contact', 'order' => 640 ],   // url/email/phone
			],
		] ];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'primary_posttype' );
		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		$viewable = $this->get_setting( 'contents_viewable', FALSE );
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

		$this->register_taxonomy( 'status_taxonomy', [
			'public'             => FALSE,
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit', TRUE ),
		], 'primary_posttype', [
			'is_viewable'     => $viewable,
			'custom_captype'  => $captype,
			'admin_managed'   => TRUE,
			'single_selected' => TRUE,
		] );

		$this->register_posttype( 'primary_posttype', [
			'hierarchical' => FALSE,

			MetaBox::POSTTYPE_MAINBOX_PROP => TRUE,
		], [
			'is_viewable'      => $viewable,
			'custom_captype'   => $captype,
			'primary_taxonomy' => TRUE,
			'status_taxonomy'  => TRUE,
		] );

		$this->filter( 'the_title', 2, 8 );

		if ( $this->get_setting( 'public_statuses' ) )
			$this->filter( 'paired_all_connected_to_args', 4, 12, 'status', $this->base );

		$this->filter_module( 'audit', 'auto_audit_save_post', 5 );

		$this->add_posttype_support( $this->constant( 'primary_posttype' ), 'date', FALSE );
		$this->hook_taxonomy_tabloid_exclude_rendered( 'status_taxonomy' );

		if ( is_admin() )
			$this->filter( 'prep_individual', 3, 8, 'admin', $this->base );
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'primary_posttype' ) );

		$this->filter( 'meta_field', 7, 9, FALSE, $this->base );
		$this->filter( 'meta_field_empty', 7, 9, FALSE, $this->base );

		$this->filter_module( 'iranian', 'default_posttype_identity_metakey', 2 );
		$this->filter_module( 'identified', 'default_posttype_identifier_metakey', 2 );
		$this->filter_module( 'identified', 'default_posttype_identifier_type', 2 );
		$this->filter_module( 'identified', 'possible_keys_for_identifier', 2 );
		$this->filter_module( 'static_covers', 'default_posttype_reference_metakey', 2 );
		$this->filter_module( 'tabloid', 'view_data', 3, 60 );
		$this->filter_module( 'papered', 'view_data', 4 );
		$this->filter_module( 'papered', 'view_list_item', 7 );

		$this->filter( 'linediscovery_search_for_post', 5, 12, FALSE, $this->base );
		$this->filter( 'paired_all_connected_to_args', 4, 18, 'clause', $this->base );
		$this->filter( 'searchselect_result_extra_for_post', 3, 22, FALSE, $this->base );

		$this->latechores__init_post_aftercare( $this->constant( 'primary_posttype' ) );
	}

	public function importer_init()
	{
		$this->filter_module( 'importer', 'source_id', 3 );
		$this->filter_module( 'importer', 'matched', 4 );
		$this->filter_module( 'importer', 'insert', 8 );
	}

	public function setup_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'primary_posttype' ) ) {
			$this->coreadmin__unset_columns( $posttype );
			$this->coreadmin__hook_taxonomy_display_states( 'status_taxonomy' );
		}
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'primary_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'get_default_comment_status', 3 );

				$this->_hook_editform_readonly_title();
				$this->_hook_editform_meta_summary( [
					'first_name'      => NULL,
					'last_name'       => NULL,
					'father_name'     => NULL,
					'identity_number' => NULL,
				] );

				$this->posttype__media_register_headerbutton( 'primary_posttype' );
				$this->_hook_post_updated_messages( 'primary_posttype' );
				$this->_hook_general_mainbox( $screen, 'primary_posttype' );

				if ( post_type_supports( $screen->post_type, 'excerpt' ) )
					$this->metaboxcustom_add_metabox_excerpt( 'primary_posttype' );

				// FIXME: WTF: disable the meta-box
				$this->filter_false_module( 'tweaks', 'metabox_author' );
				$this->filter_false_module( 'tweaks', 'metabox_slug' );

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->_hook_bulk_post_updated_messages( 'primary_posttype' );
				$this->posttype_overview_register_headerbutton( 'reports' );
				$this->coreadmin__hook_taxonomy_display_states( 'status_taxonomy' );
				$this->latechores__hook_admin_bulkactions( $screen );
				$this->corerestrictposts__hook_screen_taxonomies( [
					'status_taxonomy',
					'primary_taxonomy',
				] );

				// $this->postmeta__hook_meta_column_row( $screen->post_type, TRUE );
			}
		}
	}

	public function template_redirect()
	{
		if ( is_singular( $this->constant( 'primary_posttype' ) ) ) {

			if ( $this->get_setting( 'insert_cover' ) )
				add_action( $this->hook_base( 'content', 'before' ),
					[ $this, 'insert_cover' ],
					$this->get_setting( 'insert_priority', -50 )
				);

			$this->hook_insert_content();
		}
	}

	public function insert_cover( $content )
	{
		if ( ! $this->is_content_insert( FALSE ) )
			return;

		ModuleTemplate::postImage( [
			'size' => WordPress\Media::getAttachmentImageDefaultSize( $this->constant( 'primary_posttype' ), NULL, 'medium' ),
			'link' => 'attachment',
		] );
	}

	public function insert_content( $content )
	{
		if ( ! $this->is_content_insert( FALSE ) )
			return;

		echo $this->wrap( ModuleTemplate::summary( [ 'echo' => FALSE ] ) );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'primary_posttype', [ 'reports' ] ) )
			$items[] = $glance;

		return $items;
	}

	public function dashboard_widgets()
	{
		if ( $this->role_can( [ 'reports' ] ) )
			$this->add_dashboard_widget( 'term-summary', NULL, 'refresh' );
	}

	public function render_widget_term_summary( $object, $box )
	{
		$this->do_dashboard_term_summary( 'status_taxonomy', $box, [ $this->constant( 'primary_posttype' ) ] );
	}

	protected function _render_mainbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		MetaBox::singleselectTerms( $object->ID, [
			'taxonomy'   => $this->constant( 'status_taxonomy' ),
			'posttype'   => $object->post_type,
			'empty_link' => FALSE,
		] );
	}

	public function the_title( $title, $post_id = NULL )
	{
		return $this->make_human_title( $post_id, 'display', $title, NULL, TRUE );
	}

	protected function latechores_post_aftercare( $post )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		// NOTE: we use `export` context to store formal full-name as post-title
		if ( ! $posttitle = $this->make_human_title( $post, 'export', $post->post_title ) )
			return FALSE;

		$identity = ModuleTemplate::getMetaFieldRaw( 'identity_number', $post->ID );

		return [
			'post_title' => $posttitle,
			'post_name'  => $identity ?: Core\Text::formatSlug( $posttitle ),
		];
	}

	public function audit_get_default_terms( $terms, $taxonomy )
	{
		return Helper::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_empty_identity_number' ) => _x( 'Empty Identity Number', 'Default Term: Audit', 'geditorial-personage' ),
		] ) : $terms;
	}

	public function prep_individual_admin( $individual, $raw, $value )
	{
		if ( $link = Core\WordPress::getAdminSearchLink( $individual, $this->constant( 'primary_posttype' ) ) )
			return Core\HTML::link( $individual, $link, TRUE );

		return $individual;
	}

	public function audit_auto_audit_save_post( $terms, $post, $taxonomy, $currents, $update )
	{
		if ( ! $this->is_posttype( 'primary_posttype', $post ) )
			return $terms;

		if ( $exists = term_exists( $this->constant( 'term_empty_identity_number' ), $taxonomy ) ) {

			if ( ModuleTemplate::getMetaFieldRaw( 'identity_number', $post->ID ) )
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

			else
				$terms[] = $exists['term_id'];
		}

		return $terms;
	}

	public function meta_field_empty( $meta, $field, $post, $args, $raw, $field_args, $context )
	{
		if ( ! empty( $meta ) )
			return $meta;

		switch ( $field ) {
			case 'fullname': return $this->make_human_title( $post, $context, FALSE );
		}

		return $meta;
	}

	// @REF: `Template::getMetaField()`
	public function meta_field( $meta, $field, $post, $args, $raw, $field_args, $context )
	{
		switch ( $field ) {

			case 'first_name':
			case 'middle_name':
			case 'last_name':
			case 'fullname':
			case 'pseudonym':
			case 'father_name':
			case 'mother_name':
			case 'spouse_name':

				// in all contexts!
				return WordPress\Strings::cleanupChars( $meta );
		}

		return $meta;
	}

	public function iranian_default_posttype_identity_metakey( $default, $posttype )
	{
		if ( $posttype == $this->constant( 'primary_posttype' ) )
			return Services\PostTypeFields::getPostMetaKey( 'identity_number' );

		return $default;
	}

	public function identified_default_posttype_identifier_metakey( $default, $posttype )
	{
		if ( $posttype == $this->constant( 'primary_posttype' ) )
			return Services\PostTypeFields::getPostMetaKey( 'identity_number' );

		return $default;
	}

	public function identified_default_posttype_identifier_type( $default, $posttype )
	{
		if ( $posttype == $this->constant( 'primary_posttype' ) )
			return 'identity';

		return $default;
	}

	// TODO: move the list into ModuleHelper
	public function identified_possible_keys_for_identifier( $keys, $posttype )
	{
		if ( $posttype == $this->constant( 'primary_posttype' ) )
			return array_merge( $keys, [
				'identity_number' => 'identity',
				'identity'        => 'identity',

				_x( 'Identity', 'Possible Identifier Key', 'geditorial-personage' ) => 'identity',

				'کد ملی' => 'identity',
				'کدملی'  => 'identity',
				'کد'     => 'identity',
				'كد'     => 'identity',
			] );

		return $keys;
	}

	public function static_covers_default_posttype_reference_metakey( $default, $posttype )
	{
		if ( $posttype == $this->constant( 'primary_posttype' ) )
			return Services\PostTypeFields::getPostMetaKey( 'identity_number' );

		return $default;
	}

	public function tabloid_view_data( $data, $post, $context )
	{
		if ( $post->post_type !== $this->constant( 'primary_posttype' ) )
			return $data;

		if ( ! WordPress\Post::can( $post, 'read_post' ) )
			return $data;

		if ( $vcard = ModuleTemplate::vcard( [ 'id' => $post, 'echo' => FALSE, 'default' => '' ] ) ) {
			$data['___sides']['meta'].= Core\HTML::wrap( Scripts::markupQRCodeSVG( $vcard ), '-qrcode-vcard' );
			$data['___flags'][] = 'needs-qrcode';
		}

		return $data;
	}

	public function papered_view_data( $data, $profile, $source, $context )
	{
		if ( ! $post = WordPress\Post::get( $source ) )
			return $data;

		if ( $post->post_type !== $this->constant( 'primary_posttype' ) )
			return $data;

		if ( $fullname = $this->make_human_title( $post, 'print' ) )
			$data['source']['rendered']['posttitle'] = $fullname;

		if ( $familyfirst = $this->make_human_title( $post, 'familyfirst' ) )
			$data['source']['rendered']['familyfirst'] = $familyfirst;

		// NOTE: must be raw, the filtred is available on `metadata.identity_number`
		if ( $identity = ModuleTemplate::getMetaFieldRaw( 'identity_number', $post->ID ) )
			$data['source']['rendered']['identity'] = $identity;

		if ( $vcard = ModuleTemplate::vcard( [ 'id' => $post, 'echo' => FALSE, 'default' => '' ] ) )
			$data['source']['rendered']['vcarddata'] = $vcard;

		return $data;
	}

	public function papered_view_list_item( $row, $item, $index, $source, $profile, $context, $list )
	{
		if ( ! $post = WordPress\Post::get( $item ) )
			return $row;

		if ( $post->post_type !== $this->constant( 'primary_posttype' ) )
			return $row;

		if ( $fullname = $this->make_human_title( $post, 'print' ) )
			$row['rendered']['posttitle'] = $fullname;

		if ( $familyfirst = $this->make_human_title( $post, 'familyfirst' ) )
			$row['rendered']['familyfirst'] = $familyfirst;

		// NOTE: must be raw, the filtred is available on `metadata.identity_number`
		if ( $identity = ModuleTemplate::getMetaFieldRaw( 'identity_number', $post->ID ) )
			$row['rendered']['identity'] = $identity;

		if ( $vcard = ModuleTemplate::vcard( [ 'id' => $post, 'echo' => FALSE, 'default' => '' ] ) )
			$row['rendered']['vcarddata'] = $vcard;

		return $row;
	}

	// FIXME: add setting for using source id as identity
	public function importer_source_id( $source_id, $posttype, $raw )
	{
		if ( empty( $source_id ) )
			return NULL;

		if ( $posttype !== $this->constant( 'primary_posttype' ) )
			return $source_id;

		return Core\Validation::sanitizeIdentityNumber( $source_id );
	}

	public function importer_matched( $matched, $source_id, $posttype, $raw )
	{
		if ( ! empty( $matched ) )
			return $matched;

		if ( $posttype !== $this->constant( 'primary_posttype' ) )
			return $matched;

		if ( $post_id = Services\PostTypeFields::getPostByField( 'identity_number', $source_id, $posttype, TRUE ) )
			return $post_id;

		return $matched;
	}

	public function importer_insert( $data, $prepared, $taxonomies, $posttype, $source_id, $attach_id, $raw, $override )
	{
		// already found
		if ( ! empty( $data['ID'] ) )
			return $data;

		if ( $posttype !== $this->constant( 'primary_posttype' ) )
			return $data;

		// meta fields not supported
		if ( ! $this->has_posttype_fields_support( 'primary_posttype', 'meta' ) )
			return $data;

		if ( ! empty( $prepared['meta__identity_number'] ) ) {

			if ( $existing = Services\PostTypeFields::getPostByField( 'identity_number', $prepared['meta__identity_number'], $posttype, TRUE ) )
				$data['ID'] = $existing;
		}

		if ( ! empty( $data['ID'] ) )
			return $data;

		// no source id present
		if ( ! $source_id && $this->get_setting( 'import_check_identity_number' ) )
			return FALSE;

		// generate title by meta fields
		if ( ! empty( $data['post_title'] ) || ! $this->get_setting( 'import_fill_post_title' ) )
			return $data;

		$names = [];

		foreach ( $this->_get_human_name_metakeys( FALSE ) as $key ) {

			$metakey = sprintf( 'meta__%s', $key );

			$names[$key] = \array_key_exists( $metakey, $prepared )
				? $prepared[$metakey] : '';
		}

		$data['post_title'] = $this->make_human_title( FALSE, 'import', '', $names );

		return $data;
	}

	// NOTE: `$post` may not be available!
	public function make_human_title( $post = NULL, $context = NULL, $fallback = FALSE, $names = NULL, $checks = FALSE )
	{
		if ( $checks ) {

			if ( ! gEditorial()->enabled( 'meta' ) )
				return $fallback;

			if ( ! $post = WordPress\Post::get( $post ) )
				return $fallback;

			if ( ! $this->is_posttype( 'primary_posttype', $post ) )
				return $fallback;
		}

		$context = $context ?? 'display';

		if ( $post && ! empty( $this->cache['fullnames'][$context][$post->ID] ) )
			return $this->cache['fullnames'][$context][$post->ID];

		if ( empty( $this->cache['fullnames'] ) )
			$this->cache['fullnames'] = [];

		if ( empty( $this->cache['fullnames'][$context] ) )
			$this->cache['fullnames'][$context] = [];

		if ( is_null( $names ) )
			$names = $this->_get_human_names( $post );

		$fullname = $this->filters( 'make_human_title',
			ModuleHelper::makeFullname( $names, $context, $fallback ),
			$context,
			$post,
			$names,
			$fallback,
			$checks
		);

		if ( ! $post )
			return $fullname;

		return $this->cache['fullnames'][$context][$post->ID] = $fullname;
	}

	private function _get_human_names( $post, $checks = FALSE )
	{
		static $cache = [];

		if ( $checks ) {

			if ( ! gEditorial()->enabled( 'meta' ) )
				return FALSE;

			if ( ! $post = WordPress\Post::get( $post ) )
				return FALSE;

			if ( ! $this->is_posttype( 'primary_posttype', $post ) )
				return FALSE;
		}

		if ( empty( $post ) )
			return [];

		if ( ! empty( $cache[$post->ID] ) )
			return $cache[$post->ID];

		$names = [];

		foreach ( $this->_get_human_name_metakeys( $post ) as $key )
			$names[$key] = ModuleTemplate::getMetaFieldRaw( $key, $post->ID ) ?: '';

		return $cache[$post->ID] = $names;
	}

	private function _get_human_name_metakeys( $post = NULL, $filtered = TRUE )
	{
		$keys  = [
			'first_name',
			'middle_name',
			'last_name',
			'fullname',
			'father_name',
			'mother_name',
		];

		return $filtered
			? array_filter( $this->filters( 'human_name_metakeys', $keys, $post ) )
			: $keys;
	}

	public function linediscovery_search_for_post( $discovered, $row, $posttypes, $insert, $raw )
	{
		if ( ! is_null( $discovered ) )
			return $discovered;

		if ( ! $this->constant_in( 'primary_posttype', $posttypes ) )
			return $discovered;

		$key = 'title'; // NOTE: only support titles

		if ( ! array_key_exists( $key, $row ) )
			return $discovered;

		$type   = $this->constant( 'primary_posttype' );
		$search = WordPress\Post::getByTitle(
			Core\Text::trim( $row[$key] ),   // FIXME: sanaitze!
			$type,
			'ids',
			WordPress\Status::acceptable( $type, 'search' ),
		);

		if ( count( $search ) )
			return reset( $search );

		return $discovered;
	}

	public function paired_all_connected_to_args_status( $args, $post, $posttypes, $context )
	{
		if ( in_array( $context, [
			'restapi',
			'reports',
			'columns',
			'pointers',
			'overview',
			'counts',
		], TRUE ) )
			return $args;

		if ( count( $posttypes ) > 1 && $this->constant( 'primary_posttype' ) !== $posttypes[0] )
			return $args;

		if ( empty( $args['tax_query'] ) )
			$args['tax_query'] = [];

		$args['tax_query']['relation'] = 'AND';
		$args['tax_query'][] = [
			'taxonomy' => $this->constant( 'status_taxonomy' ),
			'terms'    => $this->get_setting( 'public_statuses', [] ),
			'field'    => 'id',
		];

		return $args;
	}

	public function paired_all_connected_to_args_clause( $args, $post, $posttypes, $context )
	{
		if ( count( $posttypes ) > 1 && $this->constant( 'primary_posttype' ) !== $posttypes[0] )
			return $args;

		if ( empty( $args['meta_query'] ) )
			$args['meta_query'] = [];

		$args['meta_query']['relation'] = 'AND';

		$args['meta_query']['last_name_clause'] = [
			'key'     => Services\PostTypeFields::getPostMetaKey( 'last_name' ),
			'compare' => 'EXISTS',
		];

		$args['meta_query']['first_name_clause'] = [
			'key'     => Services\PostTypeFields::getPostMetaKey( 'first_name' ),
			'compare' => 'EXISTS',
		];

		$args['orderby'] = [
			'last_name_clause'  => 'ASC',
			'first_name_clause' => 'ASC',
		];

		return $args;
	}

	// NOTE: late overrides of the fields values and keys
	public function searchselect_result_extra_for_post( $data, $post, $queried )
	{
		if ( empty( $queried['context'] )
			|| in_array( $queried['context'], [ 'select2', 'pairedimports' ], TRUE ) )
			return $data;

		if ( ! $post = WordPress\Post::get( $post ) )
			return $data;

		if ( $this->constant( 'primary_posttype' ) !== $post->post_type )
			return $data;

		if ( $identity = ModuleTemplate::getMetaFieldRaw( 'identity_number', $post->ID ) )
			$data['identity'] = $identity;

		if ( $fullname = $this->make_human_title( $post, 'export' ) )
			$data['fullname'] = $fullname;

		return $data;
	}

	public function sanitize_passport_number( $data, $field, $post )
	{
		$sanitized = Core\Number::translate( trim( $data ) );
		$sanitized = Core\Text::stripAllSpaces( strtoupper( $sanitized ) );

		return $sanitized ?: '';
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( Tablelist::isAction( 'parse_people_pool' ) ) {

					if ( ! $pool = $_REQUEST[$this->hook()]['tools']['pool'] )
						Core\WordPress::redirectReferer( 'huh' );

					$parsed = [];

					foreach ( Core\Text::splitLines( $pool ) as $row )
						$parsed[] = ModuleHelper::parseFullname( $row );

					if ( ! $parsed = array_values( array_filter( $parsed ) ) )
						Core\WordPress::redirectReferer( 'huh' );

					$headers = array_keys( $parsed[0] );

					if ( FALSE !== ( $data = Core\Text::toCSV( array_merge( [ $headers ], $parsed ) ) ) )
						Core\Text::download( $data, Core\File::prepName( 'parsed-pool.csv' ) );

				} else {

					Core\WordPress::redirectReferer( 'huh' );
				}
			}
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		Core\HTML::h3( _x( 'People Parser', 'Header', 'geditorial-personage' ) );

		$this->do_settings_field( [
			'type'         => 'textarea',
			'field'        => 'pool',
			'dir'          => 'rtl',
			'field_class'  => 'textarea-autosize',
			'option_group' => 'tools',
		] );

		echo $this->wrap_open_buttons();
			Settings::submitButton( 'parse_people_pool', _x( 'Parse Lines', 'Button', 'geditorial-personage' ) );
		echo '</p>';
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->posttype_overview_render_table( 'primary_posttype', $uri, $sub ) )
			return Info::renderNoReportsAvailable();
	}
}
