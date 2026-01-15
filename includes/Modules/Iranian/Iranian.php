<?php namespace geminorum\gEditorial\Modules\Iranian;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Iranian extends gEditorial\Module
{
	use Internals\RawImports;
	use Internals\RestAPI;

	protected $imports_datafiles = [
		'identity-locations' => 'identity-locations.json',   // 2023-10-03 23:32:42
		'postcode-ranges'    => 'postcode-ranges.json',      // 2025-08-13
		'province-phones'    => 'province-phones.json',      // 2025-08-13
	];

	public static function module()
	{
		return [
			'name'     => 'iranian',
			'title'    => _x( 'Iranian', 'Modules: Iranian', 'geditorial-admin' ),
			'desc'     => _x( 'Tools for Iranian Editorial', 'Modules: Iranian', 'geditorial-admin' ),
			'icon'     => [ 'misc-1000', 'ir-map' ],
			'i18n'     => 'adminonly',
			'access'   => 'beta',
			'disabled' => Services\Modulation::moduleCheckLocale( 'fa_IR' ),
			'keywords' => [
				'has-public-api',
				'persian',
			],
		];
	}

	protected function get_global_settings()
	{
		$settings  = [];
		$posttypes = $this->get_settings_posttypes_parents();
		$roles     = $this->get_settings_default_roles();

		$settings['_general']['parent_posttypes'] = [ NULL, $posttypes ];

		foreach ( $this->get_setting_posttypes( 'parent' ) as $posttype_name ) {

			$default_identity_metakey = $this->filters( 'default_posttype_identity_metakey', '', $posttype_name );
			$default_location_metakey = $this->filters( 'default_posttype_location_metakey', '', $posttype_name );

			$settings['_posttypes'][] = [
				'field' => $posttype_name.'_posttype_identity_metakey',
				'type'  => 'text',
				'title' => sprintf(
					/* translators: `%s`: supported object label */
					_x( 'Identity Meta-key for %s', 'Setting Title', 'geditorial-iranian' ),
					Core\HTML::tag( 'i', $posttypes[$posttype_name] )
				),
				'description' => _x( 'Defines identity meta-key for the post-type.', 'Setting Description', 'geditorial-iranian' ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'after'       => gEditorial\Settings::fieldAfterText( $default_identity_metakey, 'code' ),
				'placeholder' => $default_identity_metakey,
				'default'     => $default_identity_metakey,
			];

			$settings['_posttypes'][] = [
				'field' => $posttype_name.'_posttype_location_metakey',
				'type'  => 'text',
				'title' => sprintf(
					/* translators: `%s`: supported object label */
					_x( 'Location Meta-key for %s', 'Setting Title', 'geditorial-iranian' ),
					Core\HTML::tag( 'i', $posttypes[$posttype_name] )
				),
				'description' => _x( 'Defines location meta-key for the post-type.', 'Setting Description', 'geditorial-iranian' ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'after'       => gEditorial\Settings::fieldAfterText( $default_location_metakey, 'code' ),
				'placeholder' => $default_identity_metakey,
				'default'     => $default_location_metakey,
			];
		}

		$settings['posttypes_option'] = 'posttypes_option';
		$settings['_supports'][]      = 'restapi_restricted';

		$settings['_roles']['reports_roles'] = [ NULL, $roles ];
		$settings['_roles']['tools_roles']   = [ NULL, $roles ];

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
			'post_types_after' => _x( 'Supports meta fields for the selected post-types.', 'Setting Description', 'geditorial-iranian' ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'meta' => [
				'_supported' => [
					'birth_certificate_number' => [
						'title'       => _x( 'Birth Certificate', 'Field Title', 'geditorial-iranian' ),
						'description' => _x( 'Iranian Birth Certificate Number', 'Field Description', 'geditorial-iranian' ),
						'type'        => 'code',
						'order'       => 100,
						'sanitize'    => [ $this, 'sanitize_birth_certificate_number' ],
					],
				],
			],
		];
	}

	public function init()
	{
		parent::init();

		$this->filter( 'info_from_iban', 4, 8, FALSE, $this->base );
		$this->filter( 'info_from_card_number', 4, 8, FALSE, $this->base );
		$this->filter( 'info_from_postcode', 4, 8, FALSE, $this->base );
		$this->filter_module( 'banking', 'subcontent_pre_prep_data', 5, 8 );
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
		$data      = $this->get_imports_raw_data( 'identity-locations', 'json' );
		$queried   = $identity  ? Core\Text::stripNonNumeric( trim( $identity ) ) : $identity;
		$sanitized = $queried   ? Core\Number::zeroise( Core\Number::translate( $queried ), 10 ) : '';
		$validated = $sanitized ? Core\Validation::isIdentityNumber( $sanitized ) : FALSE;
		$location  = $validated ? ModuleHelper::getLocationFromIdentity( $sanitized, $data, [] ) : [];

		return compact( [
			'queried',
			'sanitized',
			'validated',
			'location',
		] );
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

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return $this->_override_module_cuc( $context, $fallback, [ 'reports', 'tools' ] );
	}

	public function imports_settings( $sub )
	{
		$this->check_settings( $sub, 'imports', 'per_page' );
	}

	protected function render_imports_html( $uri, $sub )
	{
		echo ModuleSettings::toolboxColumnOpen( _x( 'Iranian Imports', 'Header', 'geditorial-iranian' ) );

		$available = FALSE;
		$parents   = $this->get_setting_posttypes( 'parent' );
		$all       = $this->get_settings_posttypes_parents();
		$posttypes = Core\Arraay::keepByKeys( $all, $parents );

		if ( ModuleSettings::renderCard_location_by_identity( $posttypes ) )
			$available = TRUE;

		if ( ! $available )
			gEditorial\Info::renderNoToolsAvailable();

		echo '</div>';
	}

	protected function render_imports_html_before( $uri, $sub )
	{
		if ( $this->_do_import_location_by_identity( $sub ) )
			return FALSE; // avoid further UI
	}

	private function _do_import_location_by_identity( $sub )
	{
		if ( ! self::do( ModuleSettings::ACTION_LOCATION_BY_IDENTITY ) )
			return FALSE;

		if ( ! $posttype = self::req( 'type' ) )
			return ! gEditorial\Info::renderEmptyPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $this->in_setting( $posttype, 'parent_posttypes' ) )
			return ! gEditorial\Info::renderNotSupportedPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $data = $this->get_imports_raw_data( 'identity-locations', 'json' ) )
			return ! gEditorial\Info::renderNoDataAvailable(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		$this->raise_resources();

		return ModuleSettings::handleImport_location_by_identity(
			$posttype,
			$data,
			$this->_get_posttype_identity_metakey( $posttype ),
			$this->_get_posttype_location_metakey( $posttype ),
			$this->get_sub_limit_option( $sub, 'imports' )
		);
	}

	public function tools_settings( $sub )
	{
		$this->check_settings( $sub, 'tools', 'per_page' );
	}

	protected function render_tools_html( $uri, $sub )
	{
		echo ModuleSettings::toolboxColumnOpen( _x( 'Iranian Tools', 'Header', 'geditorial-iranian' ) );

		$available = FALSE;
		$parents   = $this->get_setting_posttypes( 'parent' );
		$supported = Services\PostTypeFields::getSupported( 'birth_certificate_number' );
		$intersect = array_intersect( $parents, $supported );
		$all       = $this->get_settings_posttypes_parents();
		$posttypes = Core\Arraay::keepByKeys( $all, $intersect );

		if ( ModuleSettings::renderCard_identity_certificate( $posttypes ) )
			$available = TRUE;

		if ( ! $available )
			gEditorial\Info::renderNoToolsAvailable();

		echo '</div>';
	}

	protected function render_tools_html_before( $uri, $sub )
	{
		if ( $this->_do_tool_identity_certificate( $sub ) )
			return FALSE; // avoid further UI
	}

	private function _do_tool_identity_certificate( $sub )
	{
		if ( ! self::do( ModuleSettings::ACTION_IDENTITY_CERTIFICATE ) )
			return FALSE;

		if ( ! $posttype = self::req( 'type' ) )
			return ! gEditorial\Info::renderEmptyPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $this->in_setting( $posttype, 'parent_posttypes' ) )
			return ! gEditorial\Info::renderNotSupportedPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		$this->raise_resources();

		return ModuleSettings::handleTool_identity_certificate(
			$posttype,
			$this->_get_posttype_identity_metakey( $posttype ),
			Services\PostTypeFields::isAvailable( 'birth_certificate_number', $posttype, 'meta' ),
			$this->get_sub_limit_option( $sub, 'tools' )
		);
	}

	public function sanitize_birth_certificate_number( $data, $field, $post )
	{
		$sanitized = Core\Number::translate( trim( $data ) );

		if ( empty( $sanitized ) )
			return '';

		if ( FALSE === $post || ( ! $post = WordPress\Post::get( $post ) ) )
			return $sanitized;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return $sanitized;

		$metakey  = $this->_get_posttype_identity_metakey( $post->post_type );
		$identity = get_post_meta( $post->ID, $metakey, TRUE );

		if ( $identity == $sanitized )
			return '';

		return $sanitized;
	}

	public function info_from_iban( $info, $raw, $input, $pre )
	{
		if ( empty( $info ) )
			return $info;

		if ( FALSE !== ( $data = ModuleHelper::infoFromIBAN( $raw, FALSE ) ) )
			return \array_merge( $info, $data );

		return $info;
	}

	public function info_from_card_number( $info, $raw, $input, $pre )
	{
		if ( empty( $info ) )
			return $info;

		if ( FALSE !== ( $data = ModuleHelper::infoFromCardNumber( $raw, FALSE ) ) )
			return \array_merge( $info, $data );

		return $info;
	}

	/**
	 * Tries to extract information based on given postcode.
	 * NOTE: On main module because of the data-file.
	 * @source https://github.com/persian-tools/persian-tools/pull/403/files
	 *
	 * @param array $info
	 * @param string $raw
	 * @param string $input
	 * @param array $pre
	 * @return array
	 */
	public function info_from_postcode( $info, $raw, $input, $pre )
	{
		if ( empty( $info ) || self::empty( $raw ) )
			return $info;

		if ( ! $ranges = $this->get_imports_raw_data( 'postcode-ranges', 'json' ) )
			return $info;

		if ( ! empty( $info['country'] ) && 'IR' !== $info['country'] )
			return $info;

		$prefix = substr( Core\Text::trim( $raw ), 0, 5 );

		foreach ( $ranges as $range )
			if ( $prefix >= $range['start'] && $prefix <= $range['end'] )
				return array_merge( $info, [
					'state' => $range['state'],
					'city'  => $range['city'],
				] );

		return $info;
	}

	public function banking_subcontent_pre_prep_data( $raw, $context, $post, $mapping, $metas )
	{
		$data             = $raw;
		$data['bank']     = ModuleHelper::sanitizeBank( $raw['bank'] ?? '', $raw['bankname'] ?? '' );
		$data['bankname'] = ModuleHelper::sanitizeBankName( $raw['bankname'] ?? '', $raw['bank'] ?? '' );

		return $data;
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports', 'per_page' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		echo ModuleSettings::toolboxColumnOpen( _x( 'Iranian Reports', 'Header', 'geditorial-iranian' ) );

		$available = FALSE;
		$posttypes = $this->list_posttypes();

		if ( ModuleSettings::renderCard_country_summary( $posttypes ) )
			$available = TRUE;

		if ( ModuleSettings::renderCard_city_summary( $posttypes ) )
			$available = TRUE;

		if ( ! $available )
			gEditorial\Info::renderNoReportsAvailable();

		echo '</div>';
	}

	protected function render_reports_html_before( $uri, $sub )
	{
		if ( $this->_do_report_country_summary( $sub ) )
			return FALSE; // avoid further UI

		else if ( $this->_do_report_city_summary( $sub ) )
			return FALSE; // avoid further UI
	}

	private function _do_report_country_summary( $sub )
	{
		if ( ! self::do( ModuleSettings::ACTION_COUNTRY_SUMMARY ) )
			return FALSE;

		if ( ! $posttype = self::req( 'type' ) )
			return ! gEditorial\Info::renderEmptyPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $this->posttype_supported( $posttype ) )
			return ! gEditorial\Info::renderNotSupportedPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		$this->raise_resources();

		return ModuleSettings::handleReport_country_summary(
			$posttype,
			$this->_get_posttype_identity_metakey( $posttype ),
			$this->_get_posttype_location_metakey( $posttype ),
			$this->get_imports_raw_data( 'identity-locations', 'json' )
		);
	}

	private function _do_report_city_summary( $sub )
	{
		if ( ! self::do( ModuleSettings::ACTION_CITY_SUMMARY ) )
			return FALSE;

		if ( ! $posttype = self::req( 'type' ) )
			return ! gEditorial\Info::renderEmptyPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $this->posttype_supported( $posttype ) )
			return ! gEditorial\Info::renderNotSupportedPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		$this->raise_resources();

		return ModuleSettings::handleReport_city_summary(
			$posttype,
			$this->_get_posttype_identity_metakey( $posttype ),
			$this->_get_posttype_location_metakey( $posttype ),
			$this->get_imports_raw_data( 'identity-locations', 'json' )
		);
	}
}
