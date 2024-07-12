<?php namespace geminorum\gEditorial\Modules\Phonebook;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

class Phonebook extends gEditorial\Module
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
			'name'     => 'phonebook',
			'title'    => _x( 'Phonebook', 'Modules: Phonebook', 'geditorial-admin' ),
			'desc'     => _x( 'Contact Information for Contents', 'Modules: Phonebook', 'geditorial-admin' ),
			'icon'     => 'id-alt',
			'access'   => 'alpha',
			'keywords' => [
				'subcontent',
			],
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'_subcontent' => [
				'subcontent_posttypes' => [ NULL, $this->get_settings_posttypes_parents() ],
				'subcontent_fields'    => [ NULL, Core\Arraay::stripByKeys(
					$this->subcontent_define_fields(),
					$this->subcontent_get_required_fields( 'settings' )
				) ],
			],
			'_roles' => [
				'reports_roles' => [ _x( 'Roles that can view contact information.', 'Setting Description', 'geditorial-phonebook' ), $roles ],
				'assign_roles'  => [ _x( 'Roles that can assign contact information.', 'Setting Description', 'geditorial-phonebook' ), $roles ],
			],
			'_editpost' => [
				'admin_rowactions',
			],
			'_supports' => [
				'shortcode_support',
			],
			'posttypes_option' => 'posttypes_option',
		];
	}

	protected function get_global_constants()
	{
		return [
			'restapi_namespace' => 'content-contacts',
			'subcontent_type'   => 'content_contact',
			'subcontent_status' => 'private',
			'main_shortcode'    => 'content-contacts',

			'term_empty_subcontent_data' => 'contact-data-empty',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'fields' => [
				'subcontent' => [
					'fullname' => _x( 'Fullname', 'Field Label', 'geditorial-phonebook' ),
					'relation' => _x( 'Relation', 'Field Label', 'geditorial-phonebook' ),
					'identity' => _x( 'Identity', 'Field Label', 'geditorial-phonebook' ),
					'contact'  => _x( 'Contact', 'Field Label', 'geditorial-phonebook' ),
					'type'     => _x( 'Type', 'Field Label', 'geditorial-phonebook' ),
					'status'   => _x( 'Status', 'Field Label', 'geditorial-phonebook' ),
					'address'  => _x( 'Address', 'Field Label', 'geditorial-phonebook' ),
					'desc'     => _x( 'Description', 'Field Label', 'geditorial-phonebook' ),
				],
			],
		];

		$strings['notices'] = [
			'empty'    => _x( 'There is no contact information available!', 'Notice', 'geditorial-next-of-kin' ),
			'noaccess' => _x( 'You have not necessary permission to manage the contact data.', 'Notice', 'geditorial-next-of-kin' ),
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'supportedbox_title'  => _x( 'Contacts', 'MetaBox Title', 'geditorial-phonebook' ),
			// 'metabox_action' => _x( 'Directory', 'MetaBox Action', 'geditorial-phonebook' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'mainbutton_title' => _x( 'Contacts of %1$s', 'Button Title', 'geditorial-phonebook' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'mainbutton_text'  => _x( '%1$s Manage the Contacts of %2$s', 'Button Text', 'geditorial-phonebook' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'rowaction_title' => _x( 'Contacts of %1$s', 'Action Title', 'geditorial-phonebook' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'rowaction_text'  => _x( 'Contacts', 'Action Text', 'geditorial-phonebook' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'columnrow_title' => _x( 'Contacts of %1$s', 'Row Title', 'geditorial-phonebook' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'columnrow_text'  => _x( 'Contacts', 'Row Text', 'geditorial-phonebook' ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'meta' => [
				'_supported' => [
					'emergency_mobile' => [
						'title'       => _x( 'Emergency Contact', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Mobile Contact Number of the Person Who Will Be Contacted on Emergency', 'Field Description', 'geditorial-phonebook' ),
						'type'        => 'mobile',
						'order'       => 700,
					],
					'emergency_person' => [
						'title'       => _x( 'Emergency Person', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Full Name of the Person Who Will Be Contacted on Emergency', 'Field Description', 'geditorial-phonebook' ),
						// 'type'        => 'person', // FIXME: support the field-type
						'order'       => 700,
					],
					'emergency_address' => [
						'title'       => _x( 'Emergency Address', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Full address to Be reached on Emergency', 'Field Description', 'geditorial-phonebook' ),
						'type'        => 'address',
						'order'       => 700,
					],
				],
			],
		];
	}

	protected function subcontent_get_data_mapping()
	{
		return array_merge( $this->subcontent_base_data_mapping(), [
			'comment_content'      => 'desc',       // `text`
			'comment_author'       => 'fullname',   // `tinytext`
			'comment_author_url'   => 'relation',   // `varchar(200)`
			'comment_author_email' => 'contact',    // `varchar(100)`
			// 'comment_author_IP'    => '',           // `varchar(100)`
			'comment_agent'        => 'source',     // `varchar(255)`
			'comment_karma'        => 'ref',        // `int(11)`
		] );
	}

	protected function subcontent_get_meta_mapping()
	{
		return [
			'identity' => 'identity',
			'type'     => 'type',
			'source'   => 'source',
			'status'   => 'status',
			'address'  => 'address',
		];
	}

	protected function subcontent_define_hidden_fields()
	{
		return [
			'type',
			'ref',
			'order',
		];
	}

	protected function subcontent_define_unique_fields()
	{
		return [
			'identity',
		];
	}

	protected function subcontent_define_required_fields()
	{
		return [
			'contact',
		];
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
		$this->add_posttype_fields_supported();
		$this->filter_module( 'personage', 'editform_meta_summary', 2, 20 );
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

					if ( ! $this->rowactions__hook_mainlink_for_post( $screen->post_type ) )
						$this->coreadmin__hook_tweaks_column_row( $screen->post_type, 18 );

					Scripts::enqueueColorBox();
				}
			}
		}
	}

	public function tweaks_column_row( $post, $before, $after )
	{
		printf( $before, '-contact-grid' );

			echo $this->get_column_icon( FALSE, NULL, NULL, $post->post_type );

			echo $this->framepage_get_mainlink_for_post( $post, [
				'context' => 'columnrow',
			] );

			if ( $count = $this->subcontent_get_data_count( $post ) )
				printf( ' <span class="-counted">(%s)</span>', $this->nooped_count( 'entry', $count ) );

		echo $after;
	}

	protected function rowaction_get_mainlink_for_post( $post )
	{
		return [
			$this->classs().' hide-if-no-js' => $this->framepage_get_mainlink_for_post( $post, [
				'context' => 'rowaction',
			] ),
		];
	}

	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'supportedbox';

		$this->subcontent_render_metabox_data_grid( $object, $context );

		if ( $this->role_can( 'assign' ) )
			echo Core\HTML::wrap( $this->framepage_get_mainlink_for_post( $object, [
				'context' => 'mainbutton',
				'target'  => 'grid',
			] ), 'field-wrap -buttons' );

		else
			echo $this->subcontent_get_noaccess_notice();
	}

	public function admin_menu()
	{
		if ( $this->role_can( [ 'assign', 'reports' ] ) )
			$this->_hook_submenu_adminpage( 'framepage', 'read' );
	}

	public function load_submenu_adminpage( $context = 'framepage' )
	{
		$this->_load_submenu_adminpage( $context );
		$this->subcontent_do_enqueue_app( 'contact-grid' );
	}

	public function render_framepage_adminpage()
	{
		if ( ! $post = self::req( 'linked' ) )
			return Info::renderNoPostsAvailable();

		if ( ! $post = WordPress\Post::get( $post ) )
			return Info::renderNoPostsAvailable();

		if ( $this->role_can( 'assign' ) ) {

			/* translators: %s: post title */
			$title = sprintf( _x( 'Contact Grid for %s', 'Page Title', 'geditorial-phonebook' ), WordPress\Post::title( $post ) );

			Settings::wrapOpen( $this->key, 'framepage', $title );

				Scripts::renderAppMounter( 'contact-grid', $this->key );
				Scripts::noScriptMessage();

			Settings::wrapClose();

		} else if ( $this->role_can( 'reports' ) ) {

			/* translators: %s: post title */
			$title = sprintf( _x( 'Contacts Overview for %s', 'Page Title', 'geditorial-phonebook' ), WordPress\Post::title( $post ) );

			Settings::wrapOpen( $this->key, 'framepage', $title );

				echo $this->main_shortcode( [
					'id'      => $post,
					'context' => 'framepage',
					'class'   => '-table-content',
				], $this->subcontent_get_empty_notice( 'framepage' ) );

			Settings::wrapClose();

		} else {

			Core\HTML::desc( gEditorial\Plugin::denied( FALSE ), TRUE, '-denied' );
		}
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
			$this->constant( 'term_empty_subcontent_data' ) => _x( 'Empty Contact Data', 'Default Term: Audit', 'geditorial-phonebook' ),
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

	public function personage_editform_meta_summary( $fields, $post )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $fields;

		$fields['emergency_person']  = NULL;
		$fields['emergency_mobile']  = NULL;
		// $fields['emergency_address'] = NULL;

		return $fields;
	}
}