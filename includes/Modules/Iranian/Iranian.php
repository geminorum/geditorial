<?php namespace geminorum\gEditorial\Modules\Iranian;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class Iranian extends gEditorial\Module
{
	use Internals\RawImports;
	use Internals\RestAPI;

	protected $imports_datafile = 'identity-locations-20230711.json';

	public static function module()
	{
		return [
			'name'     => 'iranian',
			'title'    => _x( 'Iranian', 'Modules: Iranian', 'geditorial' ),
			'desc'     => _x( 'Tools for Iranian Editorial', 'Modules: Iranian', 'geditorial' ),
			'icon'     => [ 'misc-1000', 'ir-map' ],
			'i18n'     => 'adminonly',
			'access'   => 'beta',
			'disabled' => Helper::moduleCheckLocale( 'fa_IR' ),
		];
	}

	protected function get_global_settings()
	{
		$settings  = [];
		$posttypes = $this->get_settings_posttypes_parents();

		$settings['_general']['parent_posttypes'] = [ NULL, $posttypes ];

		foreach ( $this->get_setting_posttypes( 'parent' ) as $posttype_name ) {

			$default_identity_metakey = $this->filters( 'default_posttype_identity_metakey', '', $posttype_name );
			$default_location_metakey = $this->filters( 'default_posttype_location_metakey', '', $posttype_name );

			$settings['_posttypes'][] = [
				'field'       => $posttype_name.'_posttype_identity_metakey',
				'type'        => 'text',
				/* translators: %s: supported object label */
				'title'       => sprintf( _x( 'Identity Meta-key for %s', 'Setting Title', 'geditorial-iranian' ), '<i>'.$posttypes[$posttype_name].'</i>' ),
				'description' => _x( 'Defines identity meta-key for the post-type.', 'Setting Description', 'geditorial-iranian' ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'after'       => Settings::fieldAfterText( $default_identity_metakey, 'code' ),
				'placeholder' => $default_identity_metakey,
				'default'     => $default_identity_metakey,
			];

			$settings['_posttypes'][] = [
				'field'       => $posttype_name.'_posttype_location_metakey',
				'type'        => 'text',
				/* translators: %s: supported object label */
				'title'       => sprintf( _x( 'Location Meta-key for %s', 'Setting Title', 'geditorial-iranian' ), '<i>'.$posttypes[$posttype_name].'</i>' ),
				'description' => _x( 'Defines location meta-key for the post-type.', 'Setting Description', 'geditorial-iranian' ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'after'       => Settings::fieldAfterText( $default_location_metakey, 'code' ),
				'placeholder' => $default_identity_metakey,
				'default'     => $default_location_metakey,
			];
		}

		$settings['posttypes_option'] = 'posttypes_option';

		return $settings;
	}

	protected function get_global_constants()
	{
		return [
			'restapi_namespace'         => 'iranian',
			'metakey_identity_posttype' => '_meta_identity_number',
			'metakey_location_posttype' => '_meta_place_of_birth',
		];
	}

	protected function get_global_strings()
	{
		$strings = [];

		if ( ! is_admin() )
			return $strings;

		$strings['settings'] = [
			'post_types_after' => _x( 'Supports meta fields for the selected post-types.', 'Settings Description', 'geditorial-iranian' ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'_supported' => [
				'birth_certificate_number' => [
					'title'       => _x( 'Birth Certificate', 'Field Title', 'geditorial-iranian' ),
					'description' => _x( 'Iranian Birth Certificate Number', 'Field Description', 'geditorial-iranian' ),
					'type'        => 'code',
					'order'       => 100,
					'sanitize'    => [ $this, 'sanitize_birth_certificate_number' ],
				],
			],
		];
	}

	public function meta_init()
	{
		$this->add_posttype_fields_supported();
	}

	public function setup_restapi()
	{
		$this->restapi_register_route( 'identity', 'get', '(?P<code>.+)' );
	}

	public function restapi_identity_get_arguments()
	{
		return [
			'code' => [
				'required'    => TRUE,
				'description' => esc_html_x( 'The National Code to process.', 'RestAPI: Arg Description', 'geditorial-iranian' ),

				'validate_callback' => [ $this, 'restapi_identity_get_code_validate_callback' ],
			],
		];
	}

	public function restapi_identity_get_code_validate_callback( $param, $request, $key )
	{
		if ( empty( $param ) )
			return Services\RestAPI::getErrorArgNotEmpty( $key );

		return TRUE;
	}

	public function restapi_identity_get_callback( $request )
	{
		return rest_ensure_response( $this->get_identity_summary( urldecode( $request['code'] ) ) );
	}

	// @REF: `Core\Validation::sanitizeIdentityNumber()`
	public function get_identity_summary( $identity )
	{
		$queried   = $identity  ? Core\Text::stripNonNumeric( trim( $identity ) ) : $identity;
		$sanitized = $queried   ? Core\Number::zeroise( Core\Number::intval( $queried, FALSE ), 10 ) : '';
		$validated = $sanitized ? Core\Validation::isIdentityNumber( $sanitized ) : FALSE;
		$location  = $validated ? $this->get_location_from_identity( $sanitized, [] ) : [];

		return compact( [
			'queried',
			'sanitized',
			'validated',
			'location',
		] );
	}

	// NOTE: assumes the identity is “sanitized”!
	public function get_location_from_identity( $identity, $fallback = FALSE )
	{
		if ( empty( $identity ) )
			return $fallback;

		if ( ! $data = $this->get_imports_raw_data( 'json' ) )
			return $fallback;

		$prefix = substr( $identity, 0, 3 );

		if ( ! array_key_exists( $prefix, $data ) )
			return $fallback;

		if ( $this->_same_city_province( $prefix, $identity ) )
			return [
				'province' => $data[$prefix]['province'],
				'city'     => $data[$prefix]['province'],
			];

		return $data[$prefix];
	}

	private function _same_city_province( $prefix, $identity = NULL )
	{
		return in_array( $prefix, [
			'001',  // تهران مرکزی
			'002',  // تهران مرکزی
			'003',  // تهران مرکزی
			'004',  // تهران مرکزی
			'005',  // تهران مرکزی
			'006',  // تهران مرکزی
			'007',  // تهران مرکزی
			'008',  // تهران مرکزی
			'011',  // تهران جنوب
			'015',  // تهران غرب
			'020',  // تهران شرق
			'025',  // تهران شمال
		], TRUE );
	}

	private function _get_posttype_identity_metakey( $posttype )
	{
		if ( $setting = $this->get_setting( $posttype.'_posttype_identity_metakey' ) )
			return $setting;

		if ( $default = $this->filters( 'default_posttype_identity_metakey', '', $posttype ) )
			return $default;

		return $this->constant( 'metakey_identity_posttype' );
	}

	private function _get_posttype_location_metakey( $posttype )
	{
		if ( $setting = $this->get_setting( $posttype.'_posttype_location_metakey' ) )
			return $setting;

		if ( $default = $this->filters( 'default_posttype_location_metakey', '', $posttype ) )
			return $default;

		return $this->constant( 'metakey_location_posttype' );
	}

	public function imports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'imports' ) ) {
			$this->add_sub_screen_option( $sub );
		}
	}

	protected function render_imports_html( $uri, $sub )
	{
		Core\HTML::h3( _x( 'Iranian Imports', 'Imports: Header', 'geditorial-iranian' ) );

		if ( $this->_do_import_location_from_identity( $sub ) )
			return;

		$posttypes = $this->get_setting_posttypes( 'parent' );

		if ( ! count( $posttypes ) )
			return Info::renderNoImportsAvailable();

		$this->_render_imports_card_sync_locations( $posttypes );
	}

	private function _render_imports_card_sync_locations( $posttypes = NULL )
	{
		echo $this->wrap_open( [ 'card', '-toolbox-card' ] );
		Core\HTML::h2( _x( 'Location by Identity', 'Card Title', 'geditorial-iranian' ), 'title' );

		echo $this->wrap_open( '-wrap-button-row -import_location_from_identity' );

			$all = $this->get_settings_posttypes_parents();

			// TODO: display empty count for each posttype
			foreach ( $posttypes as $posttype )
				Settings::submitButton( add_query_arg( [
					'action' => 'do_import_location_from_identity',
					'type'   => $posttype,
				/* translators: %s: posttype label */
				] ), sprintf( _x( 'Sync Location for %s', 'Imports: Button', 'geditorial-iranian' ), $all[$posttype] ), 'link' );

			echo '<br />';
			Core\HTML::desc( _x( 'Tries to set the location based on identity data.', 'Imports: Button Description', 'geditorial-iranian' ) );
		echo '</div></div>';
	}

	private function _do_import_location_from_identity( $sub )
	{
		if ( 'do_import_location_from_identity' !== self::req( 'action' ) )
			return FALSE;

		if ( ! $posttype = self::req( 'type' ) )
			return Info::renderEmptyPosttype();

		if ( ! $this->in_setting( $posttype, 'parent_posttypes' ) )
			return Info::renderNotSupportedPosttype();

		$identity_metakey = $this->_get_posttype_identity_metakey( $posttype );
		$location_metakey = $this->_get_posttype_location_metakey( $posttype );

		$query = [
			'meta_query' => [
				[
					'key'     => $location_metakey,
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => $identity_metakey,
					'compare' => 'EXISTS',
				],
			],
		];

		list( $posts, $pagination ) = Tablelist::getPosts( $query, [], $posttype, $this->get_sub_limit_option( $sub ) );

		if ( empty( $posts ) )
			return FALSE;

		echo '<ul>';
		foreach ( $posts as $post )
			$this->_post_set_location_from_identity( $post, $identity_metakey, $location_metakey, TRUE );

		echo '</ul>';

		Core\WordPress::redirectJS( add_query_arg( [
			'action' => 'do_import_location_from_identity',
			'type'   => $posttype,
			'paged'  => self::paged() + 1,
		] ) );

		return TRUE;
	}

	private function _post_set_location_from_identity( $post, $identity_metakey, $location_metakey, $verbose = FALSE )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		// TODO: add setting for override
		if ( $location = get_post_meta( $post->ID, $location_metakey, TRUE ) )
			return FALSE;

		if ( ! $identity = get_post_meta( $post->ID, $identity_metakey, TRUE ) )
			return FALSE;

		$sanitized = Core\Number::zeroise( Core\Number::intval( trim( $identity ), FALSE ), 10 );

		if ( ! $data = $this->get_location_from_identity( $sanitized ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: identity code */
				_x( 'No location data available for %s', 'Notice', 'geditorial-iranian' ) ),
				Core\HTML::code( $sanitized ) ) : TRUE ) && FALSE;

		if ( ! isset( $data['city'] ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: identity code */
				_x( 'No city data available for %s', 'Notice', 'geditorial-iranian' ) ),
				Core\HTML::code( $sanitized ) ) : TRUE ) && FALSE;

		if ( WordPress\Strings::isEmpty( $data['city'] ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %1$s: city data, %2$s: identity code */
				_x( 'City data is empty for %1$s: %2$s', 'Notice', 'geditorial-iranian' ) ),
				Core\HTML::code( $sanitized ), Core\HTML::code( $data['city'] ) ) : TRUE ) && FALSE;

		if ( ! update_post_meta( $post->ID, $location_metakey, $data['city'] ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: post title */
				_x( 'There is problem updating location for &ldquo;%s&rdquo;', 'Notice', 'geditorial-iranian' ) ),
				WordPress\Post::title( $post ) ) : TRUE ) && FALSE;

		if ( $verbose )
			echo Core\HTML::tag( 'li',
				/* translators: %1$s: city data, %2$s: identity code, %3$s: post title */
				sprintf( _x( '&ldquo;%1$s&rdquo; city is set by %2$s on &ldquo;%3$s&rdquo;', 'Notice', 'geditorial-iranian' ),
				Core\HTML::escape( $data['city'] ),
				Core\HTML::code( $identity ),
				WordPress\Post::title( $post )
			) );

		return TRUE;
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {
			$this->add_sub_screen_option( $sub );
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		Core\HTML::h3( _x( 'Iranian Tools', 'Tools: Header', 'geditorial-iranian' ) );

		if ( $this->_do_tool_compare_identity_certificate( $sub ) )
			return;

		$posttypes = $this->get_setting_posttypes( 'parent' );

		if ( ! count( $posttypes ) )
			return Info::renderNoToolsAvailable();

		$supported = Services\PostTypeFields::getSupported( 'birth_certificate_number' );

		if ( ! count( $supported ) )
			return Info::renderNoToolsAvailable();

		$intersect = array_intersect( $posttypes, $supported );

		if ( ! count( $intersect ) )
			return Info::renderNoToolsAvailable();

		$this->_render_tools_card_purge_duplicates( $intersect );
	}

	private function _render_tools_card_purge_duplicates( $posttypes = NULL )
	{
		echo $this->wrap_open( [ 'card', '-toolbox-card' ] );
		Core\HTML::h2( _x( 'Compare Identity to Birth Certificate', 'Card Title', 'geditorial-iranian' ), 'title' );

		echo $this->wrap_open( '-wrap-button-row -tool_compare_identity_certificate' );

			$all = $this->get_settings_posttypes_parents();

			// TODO: display empty count for each posttype
			foreach ( $posttypes as $posttype )
				Settings::submitButton( add_query_arg( [
					'action' => 'do_tool_compare_identity_certificate',
					'type'   => $posttype,
				/* translators: %s: posttype label */
				] ), sprintf( _x( 'Compare Identity for %s', 'Tools: Button', 'geditorial-iranian' ), $all[$posttype] ), 'link' );

			echo '<br />';
			Core\HTML::desc( _x( 'Tries to un-set the certificate duplicated from identity data.', 'Tools: Button Description', 'geditorial-iranian' ) );
		echo '</div></div>';
	}

	private function _do_tool_compare_identity_certificate( $sub )
	{
		if ( 'do_tool_compare_identity_certificate' !== self::req( 'action' ) )
			return FALSE;

		if ( ! $posttype = self::req( 'type' ) )
			return Info::renderEmptyPosttype();

		if ( ! $this->in_setting( $posttype, 'parent_posttypes' ) )
			return Info::renderNotSupportedPosttype();

		$identity_metakey    = $this->_get_posttype_identity_metakey( $posttype );
		$certificate_metakey = gEditorial()->module( 'meta' )->get_postmeta_key( 'birth_certificate_number' );

		$query = [
			'meta_query' => [
				'relation' => 'AND',
				[
					'key'     => $identity_metakey,
					'compare' => 'EXISTS',
				],
				[
					'key'     => $certificate_metakey,
					'compare' => 'EXISTS',
				],
			],
		];

		list( $posts, $pagination ) = Tablelist::getPosts( $query, [], $posttype, $this->get_sub_limit_option( $sub ) );

		if ( empty( $posts ) )
			return FALSE;

		echo '<ul>';
		foreach ( $posts as $post )
			$this->_post_compare_identity_certificate( $post, $identity_metakey, $certificate_metakey, TRUE );

		echo '</ul>';

		Core\WordPress::redirectJS( add_query_arg( [
			'action' => 'do_tool_compare_identity_certificate',
			'type'   => $posttype,
			'paged'  => self::paged() + 1,
		] ) );

		return TRUE;
	}

	private function _post_compare_identity_certificate( $post, $identity_metakey, $certificate_metakey, $verbose = FALSE )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $certificate = get_post_meta( $post->ID, $certificate_metakey, TRUE ) )
			return FALSE;

		if ( ! $identity = get_post_meta( $post->ID, $identity_metakey, TRUE ) )
			return FALSE;

		if ( $identity != $certificate )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %1$s: identity code, %2$s: birth certificate number */
				_x( 'Identiry (%1$s) and Birth Certificate Number (%2$s) are diffrent', 'Notice', 'geditorial-iranian' ) ),
				Core\HTML::code( $identity ), Core\HTML::code( $certificate ) ) : TRUE ) && FALSE;

		if ( ! delete_post_meta( $post->ID, $certificate_metakey ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: post title */
				_x( 'There is problem removing Birth Certificate Number for &ldquo;%s&rdquo;', 'Notice', 'geditorial-iranian' ) ),
				WordPress\Post::title( $post ) ) : TRUE ) && FALSE;

		if ( $verbose )
			echo Core\HTML::tag( 'li',
				/* translators: %1$s: birth certificate number, %2$s: post title */
				sprintf( _x( 'Birth Certificate Number %1$s removed for &ldquo;%2$s&rdquo;', 'Notice', 'geditorial-iranian' ),
				Core\HTML::code( $certificate ),
				WordPress\Post::title( $post )
			) );

		return TRUE;
	}

	public function sanitize_birth_certificate_number( $data, $field, $post )
	{
		$sanitized = Core\Number::intval( trim( $data ), FALSE );

		if ( empty( $sanitized ) )
			return '';

		if ( ! $this->posttype_supported( $post->post_type ) )
			return $sanitized;

		$metakey  = $this->_get_posttype_identity_metakey( $post->post_type );
		$identity = get_post_meta( $post->ID, $metakey, TRUE );

		if ( $identity == $sanitized )
			return '';

		return $sanitized;
	}
}