<?php namespace geminorum\gEditorial\Modules\Chronicles;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Chronicles extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\CoreAdmin;
	use Internals\CoreRowActions;
	use Internals\FramePage;
	use Internals\MetaBoxSupported;
	use Internals\PostMeta;
	use Internals\RestAPI;
	use Internals\SubContents;

	public static function module()
	{
		return [
			'name'     => 'chronicles',
			'title'    => _x( 'Chronicles', 'Modules: Chronicles', 'geditorial-admin' ),
			'desc'     => _x( 'Timeline for Contents', 'Modules: Chronicles', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'calendar3' ],
			'access'   => 'beta',
			'keywords' => [
				'timeline',
				'subcontent',
				'tabmodule',
				'crm-feature',
			],
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'_subcontent' => [
				'subcontent_posttypes' => [ NULL, $this->get_settings_posttypes_parents() ],
				'subcontent_fields'    => [ NULL, $this->subcontent_get_fields_for_settings() ],
			],
			'_roles' => [
				'reports_roles' => [ NULL, $roles ],
				'assign_roles'  => [ NULL, $roles ],
			],
			'_editpost' => [
				'admin_rowactions',
			],
			'_frontend' => [
				'tabs_support',
				'tab_title'    => [ NULL, $this->strings['frontend']['tab_title'] ],
				'tab_priority' => [ NULL, 80 ],
			],
			'_supports' => [
				'shortcode_support',
			],
			'_constants' => [
				'main_shortcode_constant' => [ NULL, 'content-timeline' ],
			],
			'posttypes_option' => 'posttypes_option',
		];
	}

	protected function get_global_constants()
	{
		return [
			'restapi_namespace' => 'content-timelines',
			'subcontent_type'   => 'content_timeline',
			'subcontent_status' => 'private',
			'main_shortcode'    => 'content-timeline',

			'term_empty_subcontent_data' => 'timeline-data-empty',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'fields' => [
				'subcontent' => [
					'label'    => _x( 'Significance', 'Field Label: `label`', 'geditorial-chronicles' ),
					'date'     => _x( 'Date', 'Field Label: `date`', 'geditorial-chronicles' ),
					'time'     => _x( 'Time', 'Field Label: `time`', 'geditorial-chronicles' ),
					'location' => _x( 'Location', 'Field Label: `location`', 'geditorial-chronicles' ),
					'duration' => _x( 'Duration', 'Field Label: `duration`', 'geditorial-chronicles' ),
					'desc'     => _x( 'Description', 'Field Label', 'geditorial-chronicles' ),
				],
			],
		];

		$strings['frontend'] = [
			'tab_title' => _x( 'Timeline', 'Tab Title', 'geditorial-chronicles' ),
		];

		$strings['notices'] = [
			'empty'    => _x( 'There is no timeline information available!', 'Notice', 'geditorial-chronicles' ),
			'noaccess' => _x( 'You do not have the necessary permission to manage the timeline data.', 'Notice', 'geditorial-chronicles' ),
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'supportedbox_title'  => _x( 'Timeline', 'MetaBox Title', 'geditorial-chronicles' ),
			// 'metabox_action' => _x( 'Timeline', 'MetaBox Action', 'geditorial-chronicles' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'mainbutton_title' => _x( 'Timeline of %1$s', 'Button Title', 'geditorial-chronicles' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'mainbutton_text'  => _x( '%1$s Manage the Timeline of %2$s', 'Button Text', 'geditorial-chronicles' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'rowaction_title' => _x( 'Timeline of %1$s', 'Action Title', 'geditorial-chronicles' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'rowaction_text'  => _x( 'Timeline', 'Action Text', 'geditorial-chronicles' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'columnrow_title' => _x( 'Timeline of %1$s', 'Row Title', 'geditorial-chronicles' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'columnrow_text'  => _x( 'Timeline', 'Row Text', 'geditorial-chronicles' ),
		];

		return $strings;
	}

	// TODO: founded date/dismantled dates
	// TODO: incorporation/dissolution dates
	protected function get_global_fields()
	{
		return [
			'meta' => [
				'_supported' => [
					'establish_date' => [
						// @REF: https://www.archives.gov/research/catalog/lcdrg/elements/establish.html
						'title'       => _x( 'Establish Date', 'Field Title', 'geditorial-chronicles' ),
						'description' => _x( 'Defines the date on which the entity was established.', 'Field Description', 'geditorial-chronicles' ),
						'icon'        => 'controls-skipback',
						'type'        => 'date',
						'quickedit'   => TRUE,
						'bulkedit'    => FALSE,
						'order'       => 40,
					],
					'abolish_date' => [
						// @REF: https://www.archives.gov/research/catalog/lcdrg/elements/abolish.html
						'title'       => _x( 'Abolish Date', 'Field Title', 'geditorial-chronicles' ),
						'description' => _x( 'Defines the date on which the entity was terminated, disbanded, inactivated, or superseded.', 'Field Description', 'geditorial-chronicles' ),
						'icon'        => 'controls-skipforward',
						'type'        => 'date',
						'quickedit'   => FALSE,
						'bulkedit'    => FALSE,
						'order'       => 45,
					],
					'purchase_date' => [
						'title'       => _x( 'Purchase Date', 'Field Title', 'geditorial-chronicles' ),
						'description' => _x( 'Defines the date on which the good was purchased.', 'Field Description', 'geditorial-chronicles' ),
						'icon'        => 'cart',
						'type'        => 'date',
						'quickedit'   => TRUE,
						'bulkedit'    => FALSE,
						'order'       => 45,
					],
					'release_date' => [
						'title'       => _x( 'Release Date', 'Field Title', 'geditorial-chronicles' ),
						'description' => _x( 'Defines the date on which the good will released.', 'Field Description', 'geditorial-chronicles' ),
						'icon'        => 'controls-skipback',
						'type'        => 'date',
						'quickedit'   => TRUE,
						'bulkedit'    => FALSE,
						'order'       => 45,
					],
					'expire_date' => [
						'title'       => _x( 'Expire Date', 'Field Title', 'geditorial-chronicles' ),
						'description' => _x( 'Defines the date on which the good Will expired.', 'Field Description', 'geditorial-chronicles' ),
						'icon'        => 'controls-skipforward',
						'type'        => 'date',
						'quickedit'   => TRUE,
						'bulkedit'    => FALSE,
						'order'       => 45,
					],
					'date_of_birth' => [
						// @REF: https://www.archives.gov/research/catalog/lcdrg/elements/birth.html
						'title'       => _x( 'Date of Birth', 'Field Title', 'geditorial-chronicles' ),
						'description' => _x( 'Defines the date on which the person was born.', 'Field Description', 'geditorial-chronicles' ),
						'icon'        => 'controls-skipback',
						'type'        => 'date',
						'quickedit'   => TRUE,
						'bulkedit'    => FALSE,
						'order'       => 40,
					],
					'date_of_death' => [
						// @REF: https://www.archives.gov/research/catalog/lcdrg/elements/death.html
						'title'       => _x( 'Date of Death', 'Field Title', 'geditorial-chronicles' ),
						'description' => _x( 'Defines the date on which the person has died.', 'Field Description', 'geditorial-chronicles' ),
						'icon'        => 'controls-skipforward',
						'type'        => 'date',
						'quickedit'   => FALSE,
						'bulkedit'    => FALSE,
						'order'       => 45,
					],
					'place_of_birth' => [
						'title'       => _x( 'Place of Birth', 'Field Title', 'geditorial-chronicles' ),
						'description' => _x( 'Defines the place where the person was born.', 'Field Description', 'geditorial-chronicles' ),
						'type'        => 'venue',
						'data_length' => 15,
						'order'       => 50,
					],
					'place_of_death' => [
						'title'       => _x( 'Place of Death', 'Field Title', 'geditorial-chronicles' ),
						'description' => _x( 'Defines the place where the person has died.', 'Field Description', 'geditorial-chronicles' ),
						'type'        => 'venue',
						'data_length' => 15,
						'order'       => 55,
					],
				],
			],
		];
	}

	protected function subcontent_get_data_mapping()
	{
		return array_merge( $this->subcontent_base_data_mapping(), [
			'comment_content' => 'desc',    // `text`
			'comment_agent'   => 'label',   // `varchar(255)`
			'comment_karma'   => 'order',   // `int(11)`

			'comment_author'       => 'location',   // `tinytext`
			'comment_author_url'   => 'duration',   // `varchar(200)`
			'comment_author_email' => 'date',       // `varchar(100)`
			'comment_author_IP'    => 'time',       // `varchar(100)`
		] );
	}

	protected function subcontent_define_required_fields()
	{
		return [
			'label',
			'date',
		];
	}

	public function after_setup_theme()
	{
		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		$this->filter_module( 'audit', 'auto_audit_save_post', 5, 12, 'subcontent' );
		$this->register_shortcode( 'main_shortcode' );
		$this->subcontent_hook__post_tabs();

		if ( ! is_admin() )
			return;

		$this->filter_module( 'tabloid', 'post_summaries', 4, 40, 'subcontent' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields_supported();

		$this->filter_module( 'personage', 'editform_meta_summary', 2, 20 );
		$this->filter_module( 'book', 'editform_meta_summary', 2, 20 );
		$this->filter_module( 'was_born', 'default_posttype_dob_metakey', 2 );
		$this->filter_module( 'iranian', 'default_posttype_location_metakey', 2 );

		$this->filter( 'searchselect_result_extra_for_post', 3, 22, FALSE, $this->base );
	}

	public function importer_init()
	{
		$this->subcontent__hook_importer_init();
	}

	public function current_screen( $screen )
	{
		if ( $this->in_setting_posttypes( $screen->post_type, 'subcontent' ) ) {

			if ( 'post' == $screen->base ) {

				if ( $this->role_can( [ 'reports', 'assign' ] ) )
					$this->_hook_general_supportedbox( $screen, NULL, 'advanced', 'low', '-subcontent-grid-metabox' );

				$this->subcontent_do_enqueue_asset_js( $screen );

			} else if ( 'edit' == $screen->base ) {

				if ( $this->role_can( [ 'reports', 'assign' ] ) ) {

					if ( ! $this->rowactions__hook_mainlink_for_post( $screen->post_type, 18, 'subcontent' ) )
						$this->coreadmin__hook_tweaks_column_row( $screen->post_type, 18, 'subcontent' );

					gEditorial\Scripts::enqueueColorBox();
				}
			}
		}

		if ( $this->screen_posttype_supported( $screen, 'edit' ) ) {

			$this->postmeta__hook_meta_column_row( $screen->post_type, [
				'place_of_birth',
				'place_of_death',
			] );
		}
	}

	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		$this->subcontent_do_render_supportedbox_content( $object, $context ?? 'supportedbox' );
	}

	public function admin_menu()
	{
		if ( $this->role_can( [ 'assign', 'reports' ] ) )
			$this->_hook_submenu_adminpage( 'overview', 'exist' );
	}

	public function load_submenu_adminpage()
	{
		$this->_load_submenu_adminpage( 'overview' );
		$this->subcontent_do_enqueue_app();
	}

	public function render_submenu_adminpage()
	{
		$this->subcontent_do_render_iframe_content(
			'overview',
			/* translators: `%s`: post title */
			_x( 'Timeline Grid for %s', 'Page Title', 'geditorial-chronicles' ),
			/* translators: `%s`: post title */
			_x( 'Timeline Overview for %s', 'Page Title', 'geditorial-chronicles' )
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
		return Services\Modulation::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_empty_subcontent_data' ) => _x( 'Empty Timeline Data', 'Default Term: Audit', 'geditorial-chronicles' ),
		] ) : $terms;
	}

	public function personage_editform_meta_summary( $fields, $post )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $fields;

		$fields['date_of_birth']  = NULL;
		$fields['date_of_death']  = NULL;
		$fields['place_of_birth'] = NULL;

		return $fields;
	}

	public function book_editform_meta_summary( $fields, $post )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $fields;

		$fields['release_date'] = NULL;

		return $fields;
	}

	public function was_born_default_posttype_dob_metakey( $default, $posttype )
	{
		if ( $this->posttype_supported( $posttype ) )
			return Services\PostTypeFields::getPostMetaKey( 'date_of_birth' );

		return $default;
	}

	public function iranian_default_posttype_location_metakey( $default, $posttype )
	{
		if ( $this->posttype_supported( $posttype ) )
			return Services\PostTypeFields::getPostMetaKey( 'place_of_birth' );

		return $default;
	}

	// NOTE: late overrides of the fields values and keys
	public function searchselect_result_extra_for_post( $data, $post, $queried )
	{
		if ( empty( $queried['context'] )
			|| in_array( $queried['context'], [ 'select2', 'pairedimports' ], TRUE ) )
			return $data;

		if ( ! $post = WordPress\Post::get( $post ) )
			return $data;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return $data;

		if ( empty( $data['dob'] ) && ! empty( $data['date_of_birth'] ) )
			$data['dob'] = $data['date_of_birth'];

		if ( empty( $data['dod'] ) && ! empty( $data['date_of_death'] ) )
			$data['dod'] = $data['date_of_death'];

		return $data;
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports', TRUE );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->subcontent_reports_render_table( $uri, $sub, 'reports', _x( 'Overview of the Timelines', 'Header', 'geditorial-chronicles' ) ) )
			return gEditorial\Info::renderNoReportsAvailable();
	}
}
