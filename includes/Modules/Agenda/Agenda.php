<?php namespace geminorum\gEditorial\Modules\Agenda;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

class Agenda extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\CoreAdmin;
	use Internals\CoreRowActions;
	use Internals\FramePage;
	use Internals\MetaBoxSupported;
	use Internals\RestAPI;
	use Internals\SubContents;

	public static function module()
	{
		return [
			'name'     => 'agenda',
			'title'    => _x( 'Agenda', 'Modules: Agenda', 'geditorial-admin' ),
			'desc'     => _x( 'Content Itineraries', 'Modules: Agenda', 'geditorial-admin' ),
			'icon'     => 'calendar',
			'access'   => 'beta',
			'keywords' => [
				'itinerary',
				'subcontent',
			],
		];
	}

	// TODO: custom datetime format for each supported posttype
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
			],
			'_supports' => [
				'shortcode_support',
			],
			'_constants' => [
				'main_shortcode_constant' => [ NULL, 'content-itinerary' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'restapi_namespace' => 'content-itinerary',
			'subcontent_type'   => 'content_itinerary',
			'subcontent_status' => 'private',
			'main_shortcode'    => 'content-itinerary',

			'term_empty_subcontent_data' => 'itinerary-data-empty',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'fields' => [
				'subcontent' => [
					'label'      => _x( 'Label', 'Field Label: `label`', 'geditorial-agenda' ),
					'datestring' => _x( 'Date', 'Field Label: `datestring`', 'geditorial-agenda' ),
					'timestart'  => _x( 'Time Start', 'Field Label: `timestart`', 'geditorial-agenda' ),
					'timeend'    => _x( 'Time End', 'Field Label: `timeend`', 'geditorial-agenda' ),
					'people'     => _x( 'Coordinator', 'Field Label: `people`', 'geditorial-agenda' ),
					'topic'      => _x( 'Topic', 'Field Label: `topic`', 'geditorial-agenda' ),
					'count'      => _x( 'Participants', 'Field Label: `count`', 'geditorial-agenda' ),
					'location'   => _x( 'Venue', 'Field Label: `location`', 'geditorial-agenda' ),
					'duration'   => _x( 'Duration', 'Field Label: `duration`', 'geditorial-agenda' ),
					'desc'       => _x( 'Description', 'Field Label: `desc`', 'geditorial-agenda' ),
				],
			],
		];

		$strings['frontend'] = [
			'tab_title' => _x( 'Itineraries', 'Tab Title', 'geditorial-agenda' ),
		];

		$strings['notices'] = [
			'empty'    => _x( 'There is no itinerary information available!', 'Notice', 'geditorial-agenda' ),
			'noaccess' => _x( 'You do not have the necessary permission to manage the itinerary data.', 'Notice', 'geditorial-agenda' ),
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'supportedbox_title'  => _x( 'Itineraries', 'MetaBox Title', 'geditorial-agenda' ),
			// 'metabox_action' => _x( 'Itineraries', 'MetaBox Action', 'geditorial-agenda' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'mainbutton_title' => _x( 'Itineraries of %1$s', 'Button Title', 'geditorial-agenda' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'mainbutton_text'  => _x( '%1$s Manage the Itineraries of %2$s', 'Button Text', 'geditorial-agenda' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'rowaction_title' => _x( 'Itineraries of %1$s', 'Action Title', 'geditorial-agenda' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'rowaction_text'  => _x( 'Itineraries', 'Action Text', 'geditorial-agenda' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'columnrow_title' => _x( 'Itineraries of %1$s', 'Row Title', 'geditorial-agenda' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'columnrow_text'  => _x( 'Itineraries', 'Row Text', 'geditorial-agenda' ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'meta' => [
				'_supported' => [
					// @EXAMPLE: `Release Schedule` on https://make.wordpress.org/core/6-3/
					'agenda_title' => [
						'title'       => _x( 'Agenda Title', 'Field Title', 'geditorial-agenda' ),
						'description' => _x( 'The Itinerary Table Caption', 'Field Description', 'geditorial-agenda' ),
						'order'       => 400,
					],
				],
			]
		];
	}

	protected function subcontent_get_data_mapping()
	{
		return array_merge( $this->subcontent_base_data_mapping(), [
			'comment_content' => 'desc',    // `text`
			'comment_agent'   => 'label',   // `varchar(255)`
			'comment_karma'   => 'order',   // `int(11)`

			'comment_author'       => 'location',     // `tinytext`
			'comment_author_url'   => 'people',       // `varchar(200)`
			'comment_author_email' => 'datestring',   // `varchar(100)`
			'comment_author_IP'    => 'duration',     // `varchar(100)`
		] );
	}

	protected function subcontent_get_meta_mapping()
	{
		return [
			'timestart' => 'timestart',
			'timeend'   => 'timeend',
			'topic'     => 'topic',
			'count'     => 'count',
			'postid'    => '_post_ref',
		];
	}

	protected function subcontent_define_required_fields()
	{
		return [
			'label',
			'datestring',
		];
	}

	protected function posttypes_parents( $extra = [] )
	{
		return $this->filters( 'posttypes_parents', [
			'event',
			'course',
			'session'         ,   // `Symposium` Module
			'mission'         ,   // `Missioned` Module
			'program'         ,   // `Programmed` Module
			'meeting'         ,   // `Meeted` Module
			'listing'         ,   // `Listed` Module
			'training_course' ,   // `Trained` Module
			'shooting_session',   // `Ranged` Module
		] );
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
		$this->add_posttype_fields_supported( $this->get_setting_posttypes( 'subcontent' ) );
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

					Scripts::enqueueColorBox();
				}
			}
		}
	}

	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		$this->subcontent_do_render_supportedbox_content( $object, $context ?? 'supportedbox' );
	}

	public function admin_menu()
	{
		if ( $this->role_can( [ 'assign', 'reports' ] ) )
			$this->_hook_submenu_adminpage( 'framepage', 'exist' );
	}

	public function load_submenu_adminpage( $context = 'framepage' )
	{
		$this->_load_submenu_adminpage( $context );
		$this->subcontent_do_enqueue_app();
	}

	public function render_framepage_adminpage()
	{
		$this->subcontent_do_render_iframe_content(
			'framepage',
			/* translators: `%s`: post title */
			_x( 'Itinerary Grid for %s', 'Page Title', 'geditorial-agenda' ),
			/* translators: `%s`: post title */
			_x( 'Itinerary Overview for %s', 'Page Title', 'geditorial-agenda' )
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
		return Helper::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_empty_subcontent_data' ) => _x( 'Empty Itinerary Data', 'Default Term: Audit', 'geditorial-agenda' ),
		] ) : $terms;
	}
}
