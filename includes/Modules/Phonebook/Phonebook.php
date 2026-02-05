<?php namespace geminorum\gEditorial\Modules\Phonebook;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Phonebook extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\CoreAdmin;
	use Internals\CoreRowActions;
	use Internals\FramePage;
	use Internals\MetaBoxSupported;
	use Internals\PostTypeFields;
	use Internals\RestAPI;
	use Internals\SubContents;

	// @SEE `ContactCards` module

	public static function module()
	{
		return [
			'name'     => 'phonebook',
			'title'    => _x( 'Phonebook', 'Modules: Phonebook', 'geditorial-admin' ),
			'desc'     => _x( 'Contact Information for Contents', 'Modules: Phonebook', 'geditorial-admin' ),
			'icon'     => 'id-alt',
			'access'   => 'beta',
			'keywords' => [
				'contact',
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
				'reports_roles' => [ _x( 'Roles that can view contact information.', 'Setting Description', 'geditorial-phonebook' ), $roles ],
				'assign_roles'  => [ _x( 'Roles that can assign contact information.', 'Setting Description', 'geditorial-phonebook' ), $roles ],
			],
			'_editpost' => [
				'admin_rowactions',
			],
			'_frontend' => [
				'tabs_support',
				'tab_title'       => [ NULL, $this->strings['frontend']['tab_title'] ],
				'tab_priority'    => [ NULL, 80 ],
				'frontend_search' => [ _x( 'Adds results by Phone and Mobile information on front-end search.', 'Setting Description', 'geditorial-phonebook' ) ],
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
			'term_empty_mobile_number'   => 'mobile-number-empty',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'fields' => [
				'subcontent' => [
					'label'    => _x( 'Label', 'Field Label: `label`', 'geditorial-phonebook' ),
					'phone'    => _x( 'Contact', 'Field Label: `phone`', 'geditorial-phonebook' ),
					'fullname' => _x( 'Fullname', 'Field Label: `fullname`', 'geditorial-phonebook' ),
					'relation' => _x( 'Relation', 'Field Label: `relation`', 'geditorial-phonebook' ),
					'identity' => _x( 'Identity', 'Field Label: `identity`', 'geditorial-phonebook' ),
					'address'  => _x( 'Address', 'Field Label: `address`', 'geditorial-phonebook' ),
				],
			],
		];

		$strings['frontend'] = [
			'tab_title' => _x( 'Contacts', 'Tab Title', 'geditorial-phonebook' ),
		];

		$strings['notices'] = [
			'empty'    => _x( 'There is no contact information available!', 'Notice', 'geditorial-phonebook' ),
			'noaccess' => _x( 'You do not have the necessary permission to manage the contact data.', 'Notice', 'geditorial-phonebook' ),
		];

		if ( ! is_admin() )
			return $strings;

		$strings['settings'] = [
			'post_types_title' => _x( 'Meta Field Posttypes', 'Setting Info', 'geditorial-phonebook' ),
			'post_types_after' => _x( 'Applies Post-type fields on selected post-types.', 'Setting Info', 'geditorial-phonebook' ),
		];

		$strings['metabox'] = [
			'supportedbox_title'  => _x( 'Contacts', 'MetaBox Title', 'geditorial-phonebook' ),
			// 'metabox_action' => _x( 'Directory', 'MetaBox Action', 'geditorial-phonebook' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'mainbutton_title' => _x( 'Contacts of %1$s', 'Button Title', 'geditorial-phonebook' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'mainbutton_text'  => _x( '%1$s Manage the Contacts of %2$s', 'Button Text', 'geditorial-phonebook' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'rowaction_title' => _x( 'Contacts of %1$s', 'Action Title', 'geditorial-phonebook' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'rowaction_text'  => _x( 'Contacts', 'Action Text', 'geditorial-phonebook' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'columnrow_title' => _x( 'Contacts of %1$s', 'Row Title', 'geditorial-phonebook' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'columnrow_text'  => _x( 'Contacts', 'Row Text', 'geditorial-phonebook' ),
		];

		return $strings;
	}

	// @SEE: [213 Secondary Address Unit Designators | Postal Explorer](https://pe.usps.com/text/pub28/28c2_003.htm)
	protected function get_global_fields()
	{
		return [
			'meta' => [
				'_supported' => [
					'mobile_number' => [
						'title'       => _x( 'Mobile Number', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Primary Mobile Contact Number of the Person', 'Field Description', 'geditorial-phonebook' ),
						'type'        => 'mobile',
						'quickedit'   => TRUE,
						'bulkedit'    => FALSE,
						'order'       => 500,
					],
					'mobile_secondary' => [
						'title'       => _x( 'Secondary Mobile', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Secondary Mobile Contact Number of the Person', 'Field Description', 'geditorial-phonebook' ),
						'type'        => 'mobile',
						'order'       => 500,
					],
					'mobile_parent' => [
						'title'       => _x( 'Parent\'s Mobile', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Parent Mobile Contact Number of the Person', 'Field Description', 'geditorial-phonebook' ),
						'type'        => 'mobile',
						'order'       => 500,
					],
					'phone_number'  => [
						'title'       => _x( 'Phone Number', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Primary Phone Contact Number of the Person', 'Field Description', 'geditorial-phonebook' ),
						'type'        => 'phone',
						'order'       => 500,
					],
					'phone_secondary'  => [
						'title'       => _x( 'Secondary Phone', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Secondary Phone Contact Number of the Person', 'Field Description', 'geditorial-phonebook' ),
						'type'        => 'phone',
						'order'       => 500,
					],
					'phone_parent'  => [
						'title'       => _x( 'Parent\'s Phone', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Parent Phone Contact Number of the Person', 'Field Description', 'geditorial-phonebook' ),
						'type'        => 'phone',
						'order'       => 500,
					],
					/**
					 * In a standard address format, the building name, if applicable,
					 * is typically included on Address Line 1 or Address Line 2,
					 * depending on the specific location and format. It often appears
					 * after the street address and number, and before any apartment
					 * or unit information.
					 *
					 * Placement: The building name usually follows the street
					 * address and house number. For example, an address
					 * might look like this: "123 Main St, Building B, Apt 4"
					 * or "Building X, 45 Elm Ave".
					 */
					'building_name' => [
						'title'       => _x( 'Building Name', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Address Unit for the Building', 'Field Description', 'geditorial-phonebook' ),
						'order'       => 550,
					],
					'room_number' => [
						'title'       => _x( 'Room Number', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Address Unit for the Room', 'Field Description', 'geditorial-phonebook' ),
						'order'       => 550,
					],
					'postal_address' => [
						'title'       => _x( 'Postal Address', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Full Postal Address about the Content, including city, state etc.', 'Field Description', 'geditorial-phonebook' ),
						'type'        => 'address',
						'order'       => 600,
					],
					'postal_code' => [
						'title'       => _x( 'Postal Code', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Postal Code about the Content.', 'Field Description', 'geditorial-phonebook' ),
						'type'        => 'postcode',
						'order'       => 600,
					],
					'home_address' => [
						'title'       => _x( 'Home Address', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Full home address, including city, state etc.', 'Field Description', 'geditorial-phonebook' ),
						'type'        => 'address',
						'order'       => 600,
					],
					'work_address' => [
						'title'       => _x( 'Work Address', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Full work address, including city, state etc.', 'Field Description', 'geditorial-phonebook' ),
						'type'        => 'address',
						'order'       => 600,
					],
					'family_address' => [
						'title'       => _x( 'Parent\'s Address', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Full family address, including city, state etc.', 'Field Description', 'geditorial-phonebook' ),
						'type'        => 'address',
						'order'       => 600,
					],
					'emergency_mobile' => [
						'title'       => _x( 'Emergency Contact', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Mobile Contact Number of the Person Who Will Be Contacted on Emergency', 'Field Description', 'geditorial-phonebook' ),
						'type'        => 'mobile',
						'order'       => 700,
					],
					'emergency_person' => [
						'title'       => _x( 'Emergency Person', 'Field Title', 'geditorial-phonebook' ),
						'description' => _x( 'Full Name of the Person Who Will Be Contacted on Emergency', 'Field Description', 'geditorial-phonebook' ),
						'type'        => 'people',
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
			'comment_content' => 'address',   // `text`
			'comment_agent'   => 'label',     // `varchar(255)`
			'comment_karma'   => 'order',     // `int(11)`

			'comment_author'       => 'fullname',   // `tinytext`
			'comment_author_url'   => 'phone',      // `varchar(200)`
			'comment_author_email' => 'identity',   // `varchar(100)`
			'comment_author_IP'    => 'relation',   // `varchar(100)`
		] );
	}

	protected function subcontent_define_searchable_fields()
	{
		if ( $human = gEditorial()->constant( 'personage', 'primary_posttype' ) )
			return [ 'fullname' => [ $human ] ];

		return [];
	}

	protected function subcontent_define_importable_fields()
	{
		return [
			'phone'   => 'label',
			'address' => 'label',
		];
	}

	protected function subcontent_define_unique_fields()
	{
		return [
			'phone',
		];
	}

	public function after_setup_theme()
	{
		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		$this->filter_module( 'audit', 'auto_audit_save_post', 5, 11 );
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

		$this->filter( 'searchselect_result_extra_for_post', 3, 32, FALSE, $this->base );
		$this->filter( 'searchselect_pre_query_posts', 3, 12, FALSE, $this->base );
		$this->filter( 'linediscovery_data_for_post', 5, 12, FALSE, $this->base );

		if ( $this->get_setting( 'frontend_search' ) )
			$this->filter( 'posts_search_append_meta_frontend', 3, 8, FALSE, $this->base );

		if ( ! is_admin() )
			return;

		$this->action( 'pre_get_posts', 1, 5, 'sanitized' );
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
			_x( 'Contact Grid for %s', 'Page Title', 'geditorial-phonebook' ),
			/* translators: `%s`: post title */
			_x( 'Contacts Overview for %s', 'Page Title', 'geditorial-phonebook' )
		);
	}

	public function setup_restapi()
	{
		$this->subcontent_restapi_register_routes();
	}

	// NOTE: admin-only
	public function pre_get_posts_sanitized( $query )
	{
		if ( ! $query->is_main_query() || ! $query->is_search )
			return;

		if ( ! $search = $query->get( 's' ) )
			return;

		if ( Core\Text::has( $search, Services\AdvancedQueries::SEARCH_OPERATOR_OR ) )
			return;

		$search = Core\Text::stripAllSpaces( $search );

		// only numbers
		if ( ! Core\Number::is( $search ) )
			return;

		if ( ! $sanitized = Core\Phone::sanitize( $search ) )
			return;

		if ( $search === $sanitized )
			return;

		// TODO: make wp-query support `OR` operator on search-terms
		// $query->set( 's', sprintf( '%s|%s', $sanitized, $search ) );
		$query->set( 's', $sanitized );
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return $this->subcontent_do_main_shortcode( $atts, $content, $tag );
	}

	public function audit_get_default_terms( $terms, $taxonomy )
	{
		return Services\Modulation::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_empty_subcontent_data' ) => _x( 'Empty Contact Data', 'Default Term: Audit', 'geditorial-phonebook' ),
			$this->constant( 'term_empty_mobile_number' )   => _x( 'Empty Mobile Number', 'Default Term: Audit', 'geditorial-phonebook' ),
		] ) : $terms;
	}

	public function audit_auto_audit_save_post( $terms, $post, $taxonomy, $currents, $update )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $terms;

		if ( ! Services\PostTypeFields::isAvailable( 'mobile_number', $post->post_type ) )
			return $terms;

		if ( $exists = term_exists( $this->constant( 'term_empty_mobile_number' ), $taxonomy ) ) {

			if ( gEditorial\Template::getMetaFieldRaw( 'mobile_number', $post->ID ) )
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

		$fields['mobile_number']    = NULL;
		// $fields['phone_number']     = NULL;
		$fields['home_address']     = NULL;
		// $fields['work_address']     = NULL;
		$fields['emergency_person'] = NULL;
		$fields['emergency_mobile'] = NULL;
		// $fields['emergency_address'] = NULL;

		return $fields;
	}

	public function posts_search_append_meta_frontend( $meta, $search, $posttypes )
	{
		if ( empty( $posttypes ) )
			return $meta;

		if ( ! $discovery = Core\Phone::discovery( $search ) )
			return $meta; // criteria is not Phone Number

		if ( 'any' === $posttypes )
			$posttypes = $this->posttypes();

		$input  = Core\Number::translate( Core\Text::trim( $search ) );
		$fields = [
			'mobile_number',
			'mobile_secondary',
			'phone_number',
			'phone_secondary',
		];

		foreach ( (array) $posttypes as $posttype ) {

			if ( ! $this->posttype_supported( $posttype ) )
				continue;

			if ( ! WordPress\PostType::viewable( $posttype ) )
				continue;

			foreach ( $fields as $field ) {

				if ( $metakey = Services\PostTypeFields::getPostMetaKey( $field, 'meta', FALSE ) ) {

					$meta[] = [ $metakey, $discovery ];

					// also search for raw input
					if ( $input !== $discovery )
						$meta[] = [ $metakey, $input ];
				}
			}
		}

		return $meta;
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

		if ( array_key_exists( 'home_address', $data ) )
			$data['address'] = WordPress\Strings::prepAddress( $data['home_address'], 'export', '' );

		if ( empty( $data['phone'] ) ) {

			if ( ! empty( $data['mobile_number'] ) )
				$data['phone'] = $data['mobile_number'];

			else if ( ! empty( $data['phone_number'] ) )
				$data['phone'] = $data['phone_number'];

			else if ( ! empty( $data['mobile_secondary'] ) )
				$data['phone'] = $data['mobile_secondary'];

			else if ( ! empty( $data['phone_secondary'] ) )
				$data['phone'] = $data['phone_secondary'];
		}

		return $data;
	}

	public function searchselect_pre_query_posts( $null, $args, $queried )
	{
		if ( ! is_null( $null ) )
			return $null;

		if ( empty( $queried['posttype'] ) || empty( $args['s'] ) )
			return $null;

		if ( ! $phone = $this->sanitize_phone( $args['s'] ) )
			return $null;

		$supported = $this->posttypes();

		if ( ! Core\Arraay::exists( $supported, (array) $queried['posttype'] ) )
			return $null;

		foreach ( (array) $queried['posttype'] as $posttype ) {

			if ( ! in_array( $posttype, $supported, TRUE ) )
				continue;

			// meta fields not supported
			if ( ! $this->has_posttype_fields_support( $posttype, 'meta' ) )
				continue;

			foreach ( $this->_get_phone_fields( $posttype ) as $field => $metakey )
				if ( $matches = WordPress\PostType::getIDbyMeta( $metakey, $phone, FALSE ) )
					foreach ( $matches as $match )
						if ( $posttype === get_post_type( intval( $match ) ) )
							return intval( $match );
		}

		return $null;
	}

	public function linediscovery_data_for_post( $discovered, $row, $posttypes, $insert, $raw )
	{
		if ( ! is_null( $discovered ) )
			return $discovered;

		$supported = $this->posttypes();

		if ( ! Core\Arraay::exists( $supported, (array) $posttypes ) )
			return $discovered;

		$phone  = FALSE;
		$fields = [
			'mobile_number',
			'mobile_secondary',
			'phone_number',
			'phone_secondary',
		];

		foreach ( (array) $posttypes as $posttype ) {

			if ( ! in_array( $posttype, $supported, TRUE ) )
				continue;

			// meta fields not supported
			if ( ! $this->has_posttype_fields_support( $posttype, 'meta' ) )
				continue;

			$keys = $this->_get_posttype_phone_possible_keys( $posttype );

			foreach ( $keys as $key => $key_type ) {

				if ( ! array_key_exists( $key, $row ) )
					continue;

				if ( $phone = $this->sanitize_phone( $row[$key], $key_type ) )
					break 2;
			}
		}

		if ( ! $phone )
			return NULL;

		foreach ( $fields as $field )
			if ( $post_id = Services\PostTypeFields::getPostByField( $field, $phone, $posttype, FALSE ) )
				return $post_id;

		return NULL;
	}

	private function _get_posttype_phone_possible_keys( $posttype, $extra = [] )
	{
		$keys = [
			'mobile_number'    => 'mobile',
			'mobile_secondary' => 'mobile',
			'phone_number'     => 'phone',
			'phone_secondary'  => 'phone',
			'mobile'           => 'mobile',
			'phone'            => 'phone',

			_x( 'Mobile', 'Possible Phone Key', 'geditorial-phonebook' )       => 'mobile',
			_x( 'Mobile Phone', 'Possible Phone Key', 'geditorial-phonebook' ) => 'mobile',
			_x( 'Phone', 'Possible Phone Key', 'geditorial-phonebook' )        => 'phone',
			_x( 'Phone Number', 'Possible Phone Key', 'geditorial-phonebook' ) => 'phone',
		];

		$list = $this->filters( 'possible_keys_for_phone',
			array_merge( $keys, $extra ),
			$posttype
		);

		return array_change_key_case( $list, CASE_LOWER );
	}

	public function sanitize_phone( $value, $type = 'phone', $post = FALSE )
	{
		if ( WordPress\Strings::isEmpty( $value ) )
			return FALSE;

		switch ( $type ) {

			case 'mobile':
				$sanitized = Core\Mobile::sanitize( $value );
				break;

			case 'phone':
			default:
				$sanitized = Core\Phone::sanitize( $value );
		}

		return $this->filters( 'sanitize_phone', $sanitized, $value, $type, $post );
	}

	private function _get_phone_fields( $posttype, $extra = [] )
	{
		$list = [
			'mobile_number'    => Services\PostTypeFields::getPostMetaKey( 'mobile_number' ),
			'mobile_secondary' => Services\PostTypeFields::getPostMetaKey( 'mobile_secondary' ),
			'phone_number'     => Services\PostTypeFields::getPostMetaKey( 'phone_number' ),
			'phone_secondary'  => Services\PostTypeFields::getPostMetaKey( 'phone_secondary' ),
		];

		return $this->filters( 'get_phone_fields',
			array_merge( $list, $extra ),
			$posttype
		);
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports', TRUE );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->subcontent_reports_render_table( $uri, $sub, 'reports', _x( 'Overview of the Contacts', 'Header', 'geditorial-phonebook' ) ) )
			return gEditorial\Info::renderNoReportsAvailable();
	}
}
