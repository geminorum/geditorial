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
			'_supports' => [
				'shortcode_support',
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
					'label'     => _x( 'Label', 'Field Label: `label`', 'geditorial-agenda' ),
					'date'      => _x( 'Date', 'Field Label: `date`', 'geditorial-agenda' ),
					'time'      => _x( 'Time', 'Field Label: `time`', 'geditorial-agenda' ),
					'datestart' => _x( 'Date Start', 'Field Label: `datestart`', 'geditorial-agenda' ),
					'dateend'   => _x( 'Date End', 'Field Label: `dateend`', 'geditorial-agenda' ),
					'topic'     => _x( 'Topic', 'Field Label: `topic`', 'geditorial-agenda' ),
					'count'     => _x( 'Participants', 'Field Label: `count`', 'geditorial-agenda' ),
					'location'  => _x( 'Venue', 'Field Label: `location`', 'geditorial-agenda' ),
					'duration'  => _x( 'Duration', 'Field Label: `duration`', 'geditorial-agenda' ),
					'desc'      => _x( 'Description', 'Field Label: `desc`', 'geditorial-agenda' ),
				],
			],
		];

		$strings['notices'] = [
			'empty'    => _x( 'There is no itinerary information available!', 'Notice', 'geditorial-agenda' ),
			'noaccess' => _x( 'You have not necessary permission to manage the itinerary data.', 'Notice', 'geditorial-agenda' ),
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'supportedbox_title'  => _x( 'Itineraries', 'MetaBox Title', 'geditorial-agenda' ),
			// 'metabox_action' => _x( 'Itineraries', 'MetaBox Action', 'geditorial-agenda' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'mainbutton_title' => _x( 'Itineraries of %1$s', 'Button Title', 'geditorial-agenda' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'mainbutton_text'  => _x( '%1$s Manage the Itineraries of %2$s', 'Button Text', 'geditorial-agenda' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'rowaction_title' => _x( 'Itineraries of %1$s', 'Action Title', 'geditorial-agenda' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'rowaction_text'  => _x( 'Itineraries', 'Action Text', 'geditorial-agenda' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'columnrow_title' => _x( 'Itineraries of %1$s', 'Row Title', 'geditorial-agenda' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
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

			'comment_author'       => 'location',   // `tinytext`
			'comment_author_url'   => 'duration',   // `varchar(200)`
			'comment_author_email' => 'date',       // `varchar(100)`
			'comment_author_IP'    => 'time',       // `varchar(100)`
		] );
	}

	protected function subcontent_get_meta_mapping()
	{
		return [
			'datestart' => 'datestart',
			'dateend'   => 'dateend',
			'topic'     => 'topic',
			'count'     => 'count',
			'postid'    => '_post_ref',
		];
	}

	protected function subcontent_define_required_fields()
	{
		return [
			'label',
			'date',
			'datestart',
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

		$this->filter_module( 'audit', 'auto_audit_save_post', 5 );
		$this->register_shortcode( 'main_shortcode' );

		if ( ! is_admin() )
			return;

		$this->filter_module( 'tabloid', 'post_summaries', 4, 40, 'subcontent' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields_supported( $this->get_setting( 'subcontent_posttypes', [] ) );
	}

	public function current_screen( $screen )
	{
		if ( $this->in_setting( $screen->post_type, 'subcontent_posttypes' ) ) {

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
			$this->_hook_submenu_adminpage( 'framepage', 'read' );
	}

	public function load_submenu_adminpage( $context = 'framepage' )
	{
		$this->_load_submenu_adminpage( $context );
		$this->subcontent_do_enqueue_app( TRUE );
	}

	public function render_framepage_adminpage()
	{
		$this->subcontent_do_render_iframe_content(
			TRUE,
			'framepage',
			/* translators: %s: post title */
			_x( 'Itinerary Grid for %s', 'Page Title', 'geditorial-agenda' ),
			/* translators: %s: post title */
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

	public function audit_auto_audit_save_post( $terms, $post, $taxonomy, $currents, $update )
	{
		if ( ! $this->in_setting( $post->post_type, 'subcontent_posttypes' ) )
			return $terms;

		if ( $exists = term_exists( $this->constant( 'term_empty_subcontent_data' ), $taxonomy ) ) {

			if ( $this->subcontent_get_data_count( $post ) )
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

			else
				$terms[] = $exists['term_id'];
		}

		return $terms;
	}
}
