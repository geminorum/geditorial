<?php namespace geminorum\gEditorial\Modules\Persona;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Persona extends gEditorial\Module
{
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
	use Internals\TemplateTaxonomy;

	public static function module()
	{
		return [
			'name'     => 'persona',
			'title'    => _x( 'Persona', 'Modules: Persona', 'geditorial-admin' ),
			'desc'     => _x( 'Human Resource Management for Editorial', 'Modules: Persona', 'geditorial-admin' ),
			'icon'     => 'id-alt',
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

		return [
			'_general' => [
				'admin_bulkactions',
			],
			'_roles' => [
				'custom_captype',
				'reports_roles' => [ NULL, $roles ],
			],
			'_dashboard' => [
				'dashboard_widgets',
				'summary_excludes' => [
					NULL,
					WordPress\Taxonomy::listTerms( $this->constant( 'status_taxonomy' ) ),
					$this->get_taxonomy_label( 'status_taxonomy', 'no_terms' ),
				],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_supports' => [
				'assign_default_term',
				'thumbnail_support',
				$this->settings_supports_option( 'primary_posttype', [
					// 'title',
					'excerpt',
					'thumbnail',
					// 'author',
					'comments',
					'date-picker',
					'editorial-roles'
				] ),
			],
			'_editlist' => [
				'show_in_quickedit' => [ sprintf(
					/* translators: %s: primary taxonomy name */
					_x( 'Whether to show the <strong>%s</strong> in the quick/bulk edit panel.', 'Settings', 'geditorial-persona' ),
					$this->get_taxonomy_label( 'status_taxonomy' )
				), '1' ],
			],
			'_frontend' => [
				'contents_viewable' => [ NULL, FALSE ],
				'insert_content',
				'insert_cover',
				'insert_priority',
			],
			'_importer' => [
				[
					'field'       => 'import_check_identity_number',
					'title'       => _x( 'Check Identity Number', 'Setting Title', 'geditorial-persona' ),
					'description' => _x( 'Validates Identity Number prior to importing data.', 'Setting Description', 'geditorial-persona' ),
				],
				[
					'field'       => 'import_fill_post_title',
					'title'       => _x( 'Fill Post Title', 'Setting Title', 'geditorial-persona' ),
					'description' => _x( 'Tries to fill the post title with meta-data prior to importing data.', 'Setting Description', 'geditorial-persona' ),
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype'    => 'human',
			'primary_taxonomy'    => 'human_group',
			'job_title_taxonomy'  => 'job_title',      // FIXME: move to `Employed` / OR: `Jobbed`
			'blood_type_taxonomy' => 'blood_type',     // MAYBE ANOTHER MODULE: `Medic`
			'status_taxonomy'     => 'human_status',

			'term_empty_identity_number' => 'identity-number-empty',
			'term_empty_mobile_number'   => 'mobile-number-empty',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'primary_taxonomy'    => NULL,
				'job_title_taxonomy'  => 'businessperson',
				'blood_type_taxonomy' => 'heart',
				'status_taxonomy'     => 'post-status',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'primary_posttype'    => _n_noop( 'Human', 'Humans', 'geditorial-persona' ),
				'primary_taxonomy'    => _n_noop( 'Human Group', 'Humans Groups', 'geditorial-persona' ),
				'job_title_taxonomy'  => _n_noop( 'Job Title', 'Job Titles', 'geditorial-persona' ),
				'blood_type_taxonomy' => _n_noop( 'Blood Type', 'Blood Types', 'geditorial-persona' ),
				'status_taxonomy'     => _n_noop( 'Human Status', 'Human Statuses', 'geditorial-persona' ),
			],
			'labels' => [
				'primary_posttype' => [
					'featured_image' => _x( 'Profile Picture', 'Label: Featured Image', 'geditorial-persona' ),
					'metabox_title'  => _x( 'Persona', 'Label: MetaBox Title', 'geditorial-persona' ),
					'excerpt_label'  => _x( 'Biography', 'Label: `excerpt_label`', 'geditorial-persona' ),
				],
				'primary_taxonomy' => [
					'menu_name'            => _x( 'Groups', 'Label: Menu Name', 'geditorial-persona' ),
					'show_option_all'      => _x( 'Humans Groups', 'Label: Show Option All', 'geditorial-persona' ),
					'show_option_no_items' => _x( '(Un-Grouped)', 'Label: Show Option No Terms', 'geditorial-persona' ),
				],
				'status_taxonomy' => [
					'menu_name' => _x( 'Statuses', 'Label: Menu Name', 'geditorial-persona' ),
				],
				'job_title_taxonomy' => [
					'show_option_all'      => _x( 'Job Titles', 'Label: Show Option All', 'geditorial-persona' ),
					'show_option_no_items' => _x( '(Unknown)', 'Label: Show Option No Terms', 'geditorial-persona' ),
				],
				'blood_type_taxonomy' => [
					'show_option_all'      => _x( 'Blood Type', 'Label: Show Option All', 'geditorial-persona' ),
					'show_option_no_items' => _x( '(Unknown)', 'Label: Show Option No Terms', 'geditorial-persona' ),
				],
				'status_taxonomy' => [
					'show_option_all'      => _x( 'Statuses', 'Label: Show Option All', 'geditorial-persona' ),
					'show_option_no_items' => _x( '(Unknown)', 'Label: Show Option No Terms', 'geditorial-persona' ),
				],
			],
			'defaults' => [
				'primary_taxonomy' => [
					'name'        => _x( '[Ungrouped]', 'Default Term: Name', 'geditorial-persona' ),
					'description' => _x( 'Ungrouped Humans', 'Default Term: Description', 'geditorial-persona' ),
					'slug'        => 'ungrouped',
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['dashboard'] = [
			'current' => [ 'widget_title' => _x( 'Your Team Human Status Summary', 'Dashboard Widget Title', 'geditorial-persona' ), ],
			'all'     => [ 'widget_title' => _x( 'Editorial Human Status Summary', 'Dashboard Widget Title', 'geditorial-persona' ), ],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'blood_type_taxonomy' => [
				// @REF: https://www.redcrossblood.org/donate-blood/blood-types.html
				'a-positive'  => _x( 'A&plus;', 'Default Term', 'geditorial-persona' ),
				'a-negative'  => _x( 'A&minus;', 'Default Term', 'geditorial-persona' ),
				'b-positive'  => _x( 'B&plus;', 'Default Term', 'geditorial-persona' ),
				'b-negative'  => _x( 'B&minus;', 'Default Term', 'geditorial-persona' ),
				'o-positive'  => _x( 'O&plus;', 'Default Term', 'geditorial-persona' ),
				'o-negative'  => _x( 'O&minus;', 'Default Term', 'geditorial-persona' ),
				'ab-positive' => _x( 'AB&plus;', 'Default Term', 'geditorial-persona' ),
				'ab-negative' => _x( 'AB&minus;', 'Default Term', 'geditorial-persona' ),
			],
		];
	}

	public function get_global_fields()
	{
		$primary = $this->constant( 'primary_posttype' );

		return [ 'meta' => [
			$primary => [
				'first_name' => [
					'title'          => _x( 'First Name', 'Field Title', 'geditorial-persona' ),
					'description'    => _x( 'Given Name of the Person', 'Field Description', 'geditorial-persona' ),
					'quickedit'      => TRUE,
					'import_ignored' => TRUE,
					'order'          => 10,
				],
				'middle_name' => [
					'title'       => _x( 'Middle Name', 'Field Title', 'geditorial-persona' ),
					'description' => _x( 'Middle Name of the Person', 'Field Description', 'geditorial-persona' ),
					'order'       => 10,
				],
				'last_name' => [
					'title'          => _x( 'Last Name', 'Field Title', 'geditorial-persona' ),
					'description'    => _x( 'Family Name of the Person', 'Field Description', 'geditorial-persona' ),
					'quickedit'      => TRUE,
					'import_ignored' => TRUE,
					'order'          => 10,
				],
				'fullname' => [
					'title'          => _x( 'Full Name', 'Field Title', 'geditorial-persona' ),
					'description'    => _x( 'Full Name of the Person', 'Field Description', 'geditorial-persona' ),
					'quickedit'      => TRUE,
					'import_ignored' => TRUE,
					'order'          => 11,
				],
				'father_name' => [
					'title'          => _x( 'Father Name', 'Field Title', 'geditorial-persona' ),
					'description'    => _x( 'Name of the Father of the Person', 'Field Description', 'geditorial-persona' ),
					'quickedit'      => TRUE,
					'import_ignored' => TRUE,
					'order'          => 12,
				],
				'father_postid' => [
					'title'       => _x( 'Father Profile', 'Field Title', 'geditorial-persona' ),
					'description' => _x( 'Profile of the Father of the Person', 'Field Description', 'geditorial-persona' ),
					'type'        => 'post',
					'posttype'    => $primary,
					'order'       => 13,
				],
				'mother_name' => [
					'title'       => _x( 'Mother Name', 'Field Title', 'geditorial-persona' ),
					'description' => _x( 'Name of the Mother of the Person', 'Field Description', 'geditorial-persona' ),
					'order'       => 14,
				],
				'mother_postid' => [
					'title'       => _x( 'Mother Profile', 'Field Title', 'geditorial-persona' ),
					'description' => _x( 'Profile of the Mother of the Person', 'Field Description', 'geditorial-persona' ),
					'type'        => 'post',
					'posttype'    => $primary,
					'order'       => 15,
				],
				'spouse_name' => [
					'title'       => _x( 'Spouse Name', 'Field Title', 'geditorial-persona' ),
					'description' => _x( 'Name of the Spouse of the Person', 'Field Description', 'geditorial-persona' ),
					'order'       => 16,
				],
				'spouse_postid' => [
					'title'       => _x( 'Spouse Profile', 'Field Title', 'geditorial-persona' ),
					'description' => _x( 'Profile of the Spouse of the Person', 'Field Description', 'geditorial-persona' ),
					'type'        => 'post',
					'posttype'    => $primary,
					'order'       => 17,
				],
				// TODO: move to `ContactCards`
				'mobile_number' => [
					'description' => _x( 'Primary Mobile Contact Number of the Person', 'Field Description', 'geditorial-persona' ),
					'type'        => 'mobile',
					'quickedit'   => TRUE,
					'order'       => 21,
				],
				// TODO: move to `ContactCards`
				'mobile_secondary' => [
					'title'       => _x( 'Secondary Mobile', 'Field Title', 'geditorial-persona' ),
					'description' => _x( 'Secondary Mobile Contact Number of the Person', 'Field Description', 'geditorial-persona' ),
					'type'        => 'mobile',
					'order'       => 21,
				],
				// TODO: move to `ContactCards`
				'phone_number'  => [
					'description' => _x( 'Primary Phone Contact Number of the Person', 'Field Description', 'geditorial-persona' ),
					'type'        => 'phone',
					'order'       => 22,
				],
				// TODO: move to `ContactCards`
				'phone_secondary'  => [
					'title'       => _x( 'Secondary Phone', 'Field Title', 'geditorial-persona' ),
					'description' => _x( 'Secondary Phone Contact Number of the Person', 'Field Description', 'geditorial-persona' ),
					'type'        => 'phone',
					'order'       => 22,
				],
				'identity_number' => [
					'title'       => _x( 'Identity Number', 'Field Title', 'geditorial-persona' ),
					'description' => _x( 'Unique National Identity Number', 'Field Description', 'geditorial-persona' ),
					'type'        => 'identity',
					'quickedit'   => TRUE,
					'order'       => 1,
				],
				'passport_number' => [
					'title'       => _x( 'Passport Number', 'Field Title', 'geditorial-persona' ),
					'description' => _x( 'Personal Passport Number', 'Field Description', 'geditorial-persona' ),
					'type'        => 'code',
					'order'       => 200,
					'sanitize'    => [ $this, 'sanitize_passport_number' ],
				],
				// TODO: move to WasBorn
				'date_of_birth' => [
					'title'       => _x( 'Date of Birth', 'Field Title', 'geditorial-persona' ),
					'description' => _x( 'Birthday of the Person', 'Field Description', 'geditorial-persona' ),
					'type'        => 'date',
					'quickedit'   => TRUE,
					'order'       => 18,
				],
				// TODO: move to WasBorn
				// 'date_of_death' => [],
				// TODO: move to WasBorn
				'place_of_birth' => [
					'title'       => _x( 'Place of Birth', 'Field Title', 'geditorial-persona' ),
					'description' => _x( 'Place Where the Person was Born', 'Field Description', 'geditorial-persona' ),
					'type'        => 'venue',
					'order'       => 18,
				],
				'postal_code' => [
					'type' => 'postcode',
				],
				// TODO: move to `ContactCards`
				'home_address' => [
					'title'       => _x( 'Home Address', 'Field Title', 'geditorial-persona' ),
					'description' => _x( 'Full home address, including city, state etc.', 'Field Description', 'geditorial-persona' ),
					'type'        => 'address',
				],
				// TODO: move to `ContactCards`
				'work_address' => [
					'title'       => _x( 'Work Address', 'Field Title', 'geditorial-persona' ),
					'description' => _x( 'Full work address, including city, state etc.', 'Field Description', 'geditorial-persona' ),
					'type'        => 'address',
				],
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

		$this->register_taxonomy( 'job_title_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL,
		], 'primary_posttype', [
			'is_viewable'    => $viewable,
			'custom_captype' => $captype,
		] );

		$this->register_taxonomy( 'blood_type_taxonomy', [
			'hierarchical' => TRUE,
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

		$this->register_posttype( 'primary_posttype', [
			'hierarchical' => FALSE,

			Metabox::POSTTYPE_MAINBOX_PROP            => TRUE,
			WordPress\PostType::PRIMARY_TAXONOMY_PROP => $this->constant( 'primary_taxonomy' ),
		], [
			'is_viewable'    => $viewable,
			'custom_captype' => $captype,
		] );

		$this->filter( 'the_title', 2, 8 );

		$this->filter_module( 'audit', 'auto_audit_save_post', 5 );

		$this->add_posttype_support( $this->constant( 'primary_posttype' ), 'date', FALSE );
		$this->latechores__init_post_aftercare( $this->constant( 'primary_posttype' ) );
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'primary_posttype' ) );

		$this->filter( 'meta_field', 7, 9, FALSE, $this->base );

		$this->filter_module( 'was_born', 'default_posttype_dob_metakey', 2 );
		$this->filter_module( 'iranian', 'default_posttype_identity_metakey', 2 );
		$this->filter_module( 'iranian', 'default_posttype_location_metakey', 2 );
		$this->filter_module( 'identified', 'default_posttype_identifier_metakey', 2 );
		$this->filter_module( 'identified', 'default_posttype_identifier_type', 2 );
		$this->filter_module( 'identified', 'possible_keys_for_identifier', 2 );
		$this->filter_module( 'static_covers', 'default_posttype_reference_metakey', 2 );
		$this->filter_module( 'papered', 'view_data', 4 );

		$this->filter( 'linediscovery_search_for_post', 5, 12, FALSE, $this->base );
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
					// 'phone_number'    => NULL,
					'mobile_number'   => NULL,
					'identity_number' => NULL,
					'date_of_birth'   => NULL,

					$this->constant( 'blood_type_taxonomy' )   => NULL,
				] );

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
				$this->latechores__hook_admin_bulkactions( $screen );
				$this->corerestrictposts__hook_screen_taxonomies( [
					'status_taxonomy',
					'primary_taxonomy',
					'job_title_taxonomy',
					'blood_type_taxonomy',
				] );

				$this->postmeta__hook_meta_column_row( $screen->post_type );
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

	public function template_include( $template )
	{
		if ( ! $this->get_setting( 'contents_viewable', FALSE ) )
			return $template;

		return $this->templatetaxonomy__include( $template, [
			$this->constant( 'job_title_taxonomy' ),
		] );
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

	protected function dashboard_widgets()
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
			'taxonomy'   => $this->constant( 'blood_type_taxonomy' ),
			'posttype'   => $object->post_type,
			'empty_link' => FALSE,
		] );

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
			$this->constant( 'term_empty_identity_number' ) => _x( 'Empty Identity Number', 'Default Term: Audit', 'geditorial-persona' ),
			$this->constant( 'term_empty_mobile_number' )   => _x( 'Empty Mobile Number', 'Default Term: Audit', 'geditorial-persona' ),
		] ) : $terms;
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

		if ( $exists = term_exists( $this->constant( 'term_empty_mobile_number' ), $taxonomy ) ) {

			if ( ModuleTemplate::getMetaFieldRaw( 'mobile_number', $post->ID ) )
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

			else
				$terms[] = $exists['term_id'];
		}

		return $terms;
	}

	// @REF: `Template::getMetaField()`
	public function meta_field( $meta, $field, $post, $args, $raw, $field_args, $context )
	{
		switch ( $field ) {

			case 'first_name':
			case 'middle_name':
			case 'last_name':
			case 'fullname':
			case 'father_name':
			case 'mother_name':
			case 'spouse_name':
			case 'place_of_birth':
			case 'home_address':
			case 'work_address':

				// in all contexts!
				return ModuleHelper::cleanupChars( $meta );
		}

		return $meta;
	}

	public function was_born_default_posttype_dob_metakey( $default, $posttype )
	{
		if ( $posttype == $this->constant( 'primary_posttype' ) )
			return Services\PostTypeFields::getPostMetaKey( 'date_of_birth' );

		return $default;
	}

	public function iranian_default_posttype_identity_metakey( $default, $posttype )
	{
		if ( $posttype == $this->constant( 'primary_posttype' ) )
			return Services\PostTypeFields::getPostMetaKey( 'identity_number' );

		return $default;
	}

	public function iranian_default_posttype_location_metakey( $default, $posttype )
	{
		if ( $posttype == $this->constant( 'primary_posttype' ) )
			return Services\PostTypeFields::getPostMetaKey( 'place_of_birth' );

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

	// TODO: move the list into ModuleInfo
	public function identified_possible_keys_for_identifier( $keys, $posttype )
	{
		if ( $posttype == $this->constant( 'primary_posttype' ) )
			return array_merge( $keys, [
				'identity_number' => 'identity',
				'identity'        => 'identity',

				_x( 'Identity', 'Possible Identifier Key', 'geditorial-persona' ) => 'identity',

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

	public function papered_view_data( $data, $profile, $source, $context )
	{
		if ( ! $post = WordPress\Post::get( $source ) )
			return $data;

		if ( $post->post_type !== $this->constant( 'primary_posttype' ) )
			return $data;

		if ( $fullname = $this->make_human_title( $post, 'print' ) )
			$data['source']['rendered']['posttitle'] = $fullname;

		if ( $identity = ModuleTemplate::getMetaFieldRaw( 'identity_number', $post->ID ) )
			$data['source']['rendered']['identity'] = $identity;

		if ( $vcard = ModuleTemplate::vcard( [ 'id' => $post, 'echo' => FALSE, 'default' => '' ] ) )
			$data['source']['rendered']['vcarddata'] = $vcard;

		return $data;
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

		if ( $post_id = $this->posttypefields_get_post_by( 'identity_number', $source_id, 'primary_posttype' ) )
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

			if ( $existing = $this->get_postid_by_field( $prepared['meta__identity_number'], 'identity_number' ) )
				$data['ID'] = $existing;

		} else if ( ! empty( $prepared['meta__mobile_number'] ) ) {

			if ( $existing = $this->get_postid_by_field( $prepared['meta__mobile_number'], 'mobile_number' ) )
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

	public function make_human_title( $post = NULL, $context = 'display', $fallback = FALSE, $names = NULL, $checks = FALSE )
	{
		if ( $checks ) {

			if ( ! gEditorial()->enabled( 'meta' ) )
				return $fallback;

			if ( ! $post = WordPress\Post::get( $post ) )
				return $fallback;

			if ( ! $this->is_posttype( 'primary_posttype', $post ) )
				return $fallback;
		}

		if ( is_null( $names ) )
			$names = $this->_get_human_names( $post );

		$fullname = ModuleHelper::makeFullname( $names, $context, $fallback );

		return $this->filters( 'make_human_title', $fullname, $context, $post, $names, $fallback, $checks );
	}

	private function _get_human_names( $post = NULL, $checks = FALSE )
	{
		if ( $checks ) {

			if ( ! gEditorial()->enabled( 'meta' ) )
				return FALSE;

			if ( ! $post = WordPress\Post::get( $post ) )
				return FALSE;

			if ( ! $this->is_posttype( 'primary_posttype', $post ) )
				return FALSE;
		}

		$names = [];

		foreach ( $this->_get_human_name_metakeys( $post ) as $key )
			$names[$key] = ModuleTemplate::getMetaFieldRaw( $key, $post->ID ) ?: '';

		return $names;
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

		$search = WordPress\Post::getByTitle(
			Core\Text::trim( $row[$key] ),   // FIXME: sanaitze!
			$this->constant( 'primary_posttype' ),
			'ids',
			[ 'publish', 'future', 'draft', 'pending' ]
		);

		if ( count( $search ) )
			return reset( $search );

		return $discovered;
	}

	public function sanitize_passport_number( $data, $field, $post )
	{
		$sanitized = Core\Number::intval( trim( $data ), FALSE );
		$sanitized = Core\Text::stripAllSpaces( strtoupper( $sanitized ) );

		return $sanitized ?: '';
	}
}
