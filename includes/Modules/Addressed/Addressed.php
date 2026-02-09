<?php namespace geminorum\gEditorial\Modules\Addressed;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Addressed extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'addressed',
			'title'    => _x( 'Addressed', 'Modules: Addressed', 'geditorial-admin' ),
			'desc'     => _x( 'Bearing the Intended Recipient', 'Modules: Addressed', 'geditorial-admin' ),
			'icon'     => 'location',
			'i18n'     => 'adminonly',
			'access'   => 'beta',
			'keywords' => [
				'geo',
				'social-network',
				'site-identity',
				'has-customizer',
			],
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'_general' => [
				[
					'field'       => 'base_country',
					'type'        => 'text',
					'title'       => _x( 'Base Country', 'Setting Title', 'geditorial-addressed' ),
					'description' => _x( 'Defines the base country of site for supported contents.', 'Setting Description', 'geditorial-addressed' ),
					'placeholder' => Services\Locations::baseCountry( 'IR', FALSE ),
					'field_class' => [ 'small-text', 'code-text' ],
				],
			],
			'_formats' => [
				[
					'field'       => 'default_format',
					'type'        => 'textarea-quicktags-tokens',
					'title'       => _x( 'Default Format', 'Setting Title', 'geditorial-addressed' ),
					'description' => _x( 'Defines the default address format for supported contents.', 'Setting Description', 'geditorial-addressed' ),
					'field_class' => [ 'regular-text', 'textarea-autosize' ],
					'values'      => Services\Locations::addressTokens( 'settings', TRUE ),
				],
				[
					'field'       => 'override_base_country',
					'title'       => _x( 'Override Base Country', 'Setting Title', 'geditorial-addressed' ),
					'description' => _x( 'Also overrides the format of the base country of site.', 'Setting Description', 'geditorial-addressed' ),
					'default'     => '1',
				],
				[
					'field'       => 'override_woocommerce',
					'title'       => _x( 'Override Woo-Commerce', 'Setting Title', 'geditorial-addressed' ),
					'description' => _x( 'Also overrides the default format of Woo-Commerce adressess.', 'Setting Description', 'geditorial-addressed' ),
					'default'     => '1',
				],
			],
			'_types' => [
				[
					'field'  => 'address_types',
					'type'   => 'object',
					'title'  => _x( 'Address Types', 'Setting Title', 'geditorial-addressed' ),
					'values' => [
						[
							'field'       => 'title',
							'type'        => 'text',
							'title'       => _x( 'Title', 'Setting Title', 'geditorial-addressed' ),
							'description' => _x( 'Defines the title on the address type.', 'Setting Description', 'geditorial-addressed' ),
						],
						[
							'field'       => 'description',
							'type'        => 'textarea',
							'title'       => _x( 'Description', 'Setting Title', 'geditorial-addressed' ),
							'description' => _x( 'Defines the description on the address type.', 'Setting Description', 'geditorial-addressed' ),
						],
						[
							'field'       => 'hook',
							'type'        => 'text',
							'title'       => _x( 'Identifier', 'Setting Title', 'geditorial-addressed' ),
							'description' => _x( 'Defines the unique identifier on the address type.', 'Setting Description', 'geditorial-addressed' ),
							'field_class' => [ 'regular-text', 'code-text' ],
							'ortho'       => 'hook',
						],
						[
							'field'       => 'priority',
							'type'        => 'number',
							'title'       => _x( 'Priority', 'Setting Title', 'geditorial-addressed' ),
							'description' => _x( 'Sets as the sort priority where the address display on the list.', 'Setting Description', 'geditorial-addressed' ),
						],
					],
				],
			],
			'_fields' => [
				[
					'field'        => 'supported_fields',
					'type'         => 'checkboxes-values',
					'title'        => _x( 'Supported Fields', 'Setting Title', 'geditorial-addressed' ),
					'description'  => _x( 'Defines supported address fields for all type.', 'Setting Description', 'geditorial-addressed' ),
					'string_empty' => _x( 'There are no address fields available!', 'Setting Empty String', 'geditorial-addressed' ),
					'values'       => Core\Arraay::pluck( $this->_get_address_type_fields( 'settings' ), 'label', 'key' ),
					'default'      => TRUE,
				],
			],
			'_supports' => [
				'shortcode_support',
			],
			'_roles' => [
				'reports_roles' => [ NULL, $roles ],
				'assign_roles'  => [ NULL, $roles ],
			],
			'_constants' => [
				'main_shortcode_constant' => [ NULL, 'addressed' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_shortcode' => 'addressed',
		];
	}

	// TODO: support `phone`/`fax`/`mobile`/`postcode` by controls
	private function _get_address_type_fields( $context = NULL )
	{
		return $this->filters( 'type_fields', [
			[
				'key'         => 'address_1', // NOTE: WooCommerce standard
				'label'       => _x( 'Address line 1', 'Control label', 'geditorial-addressed' ),
				'description' => _x( 'The street address for the location.', 'Setting Description', 'geditorial-addressed' ),
				'type'        => 'text',
			],
			[
				'key'         => 'address_2', // NOTE: WooCommerce standard
				'label'       => _x( 'Address Line 2', 'Control label', 'geditorial-addressed' ),
				'description' => _x( 'An additional, optional address line for the location.', 'Setting Description', 'geditorial-addressed' ),
				'type'        => 'text',
			],
			[
				'key'         => 'company',
				'label'       => _x( 'Company', 'Control label', 'geditorial-addressed' ),
				'description' => _x( 'The organization in which this address is located.', 'Setting Description', 'geditorial-addressed' ),
				'type'        => 'text',
			],
			[
				'key'         => 'city',
				'label'       => _x( 'City', 'Control label', 'geditorial-addressed' ),
				'description' => _x( 'The city in which this address is located.', 'Setting Description', 'geditorial-addressed' ),
				'type'        => 'text',
			],
			[
				'key'         => 'state', // NOTE: WooCommerce standard
				'label'       => _x( 'Province', 'Control label', 'geditorial-addressed' ),
				'description' => _x( 'The state or province in which this address is located.', 'Setting Description', 'geditorial-addressed' ),
				'type'        => 'text',
			],
			[
				'key'         => 'country',
				'label'       => _x( 'Country', 'Control label', 'geditorial-addressed' ),
				'description' => _x( 'The country in which this address is located.', 'Setting Description', 'geditorial-addressed' ),
				'type'        => 'text',
			],
			[
				'key'         => 'latlng',
				'label'       => _x( 'Coordinates', 'Control label', 'geditorial-addressed' ),
				'description' => _x( 'The Latitude and Longitude to this address.', 'Setting Description', 'geditorial-addressed' ),
				'type'        => 'text',
			],
			[
				'key'         => 'site',
				'label'       => _x( 'Site URL', 'Control label', 'geditorial-addressed' ),
				'description' => _x( 'The site URL of this address.', 'Setting Description', 'geditorial-addressed' ),
				'type'        => 'url',
			],
			[
				'key'         => 'embed',
				'label'       => _x( 'Embed URL', 'Control label', 'geditorial-addressed' ),
				'description' => _x( 'An embeddable resource URL about this address.', 'Setting Description', 'geditorial-addressed' ),
				'type'        => 'url',
			],
		], $context );
	}

	public function init()
	{
		parent::init();

		if ( $this->get_setting( 'base_country' ) )
			$this->filter( 'locations_base_country', 3, 9, FALSE, $this->base );

		if ( $this->get_setting( 'default_format' ) ) {
			$this->filter( 'locations_address_formats', 1, 9, FALSE, $this->base );

			if ( $this->get_setting( 'override_woocommerce', TRUE ) )
				$this->filter( 'localisation_address_formats', 1, 9, FALSE, 'woocommerce' );
		}

		$this->register_shortcode( 'main_shortcode' );
	}

	public function customize_setup( $manager )
	{
		if ( ! $data = $this->get_setting( 'address_types', [] ) )
			return;

		$fields    = $this->_get_address_type_fields( 'customize' );
		$supported = $this->get_setting( 'supported_fields', Core\Arraay::pluck( $fields, 'key' ) );

		foreach ( Core\Arraay::sortByPriority( $data, 'priority' ) as $offset => $type ) {

			$capability = 'edit_theme_options'; // TODO: get from dropdown setting/roles API
			$section    = $this->hook( 'address_type', $offset );

			$manager->add_section( $section, [
				'panel' => Services\FrontSettings::MAIN_PANEL,
				'title' => sprintf(
					/* translators: `%s`: address type title */
					_x( 'Address: %s', 'Customizer: Section Title', 'geditorial-addressed' ),
					$type['title']
				),
				'description'        => $type['description'] ?? '',
				'description_hidden' => FALSE,
				'priority'           => 51 + $offset,
			] );

			$option = $this->_get_address_type_option( $type['hook'] );

			foreach ( $fields as $raw ) {

				$field = self::atts( [
					'key'         => FALSE,
					'label'       => '',
					'description' => '',
					'type'        => 'text',
				], $raw );

				if ( empty( $field['key'] ) || ! in_array( $field['key'], $supported, TRUE ) )
					continue;

				$setting = sprintf( '%s[%s]', $option, $field['key'] );
				$manager->add_setting( $setting, [
					'type'       => 'option',
					'capability' => $capability,
				] );

				$manager->add_control( $this->hook( $type['hook'], $field['key'] ), [
					'label'       => $field['label'] ?: $field['key'],
					'description' => $field['description'],
					'section'     => $section,
					'settings'    => $setting,
					'type'        => 'text',
				] );
			}
		}
	}

	private function _get_address_type_option( $type_hook )
	{
		return $this->hook( 'type', Core\Text::sanitizeHook( $type_hook ) );
	}

	private function _determine_address_type( $input, $fallback = FALSE )
	{
		if ( FALSE === $input )
			return $fallback;

		if ( ! is_null( $input ) )
			return Core\Text::sanitizeHook( $input );

		if ( ! $data = $this->get_setting( 'address_types', [] ) )
			return $fallback;

		foreach ( Core\Arraay::sortByPriority( $data, 'priority' ) as $type )
			if ( ! empty( $type['hook'] ) )
				return $type['hook'];

		return $fallback;
	}

	// @hook: `geditorial_locations_base_country`
	public function locations_base_country( $country, $source, $fallback )
	{
		// TODO: move up the sanitization!
		return Core\Text::strToUpper( Core\Text::trim( $this->setting( 'base_country' ) ) ) ?: $country;
	}

	// @hook: `geditorial_locations_address_formats`
	public function locations_address_formats( $data, $format = NULL )
	{
		$data['default'] = $format ?? $this->get_setting( 'default_format' );

		if ( ! $country = gEditorial()->base_country() )
			return $data;

		if ( $this->get_setting( 'override_base_country', TRUE ) )
			$data[$country] = $data['default'];

		return $data;
	}

	// @hook: `woocommerce_localisation_address_formats`
	public function localisation_address_formats( $formats )
	{
		return $this->locations_address_formats(
			$formats,
			str_ireplace(
				// TODO: move up the sanitization!
				// NOTE: WooCommerce uses single mustaches!
				[ '{{{', '}}}', '{{', '}}' ],
				[   '{', '}',    '{', '}'  ],
				$this->get_setting( 'default_format' )
			)
		);
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'type'      => NULL,
			'format'    => NULL,
			'separator' => NULL,
			'context'   => NULL,
			'wrap'      => TRUE,
			'class'     => '',
			'before'    => '',
			'after'     => '',
		], $atts, $tag ?: $this->constant( 'main_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $type = $this->_determine_address_type( $args['type'] ) )
			return $content;

		if ( ! $data = get_option( $this->_get_address_type_option( $type ) ) )
			return $content;

		if ( ! $html = Services\Locations::formatAddress( $data, $args ) )
			return $content;

		return gEditorial\ShortCode::wrap(
			$html,
			$this->constant( 'main_shortcode' ),
			$args
		);
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports', 'per_page' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		echo ModuleSettings::toolboxColumnOpen( _x( 'Address Reports', 'Header', 'geditorial-addressed' ) );

		$available = FALSE;

		if ( $data = $this->get_setting( 'address_types', [] ) ) {

			foreach ( Core\Arraay::sortByPriority( $data, 'priority' ) as $type ) {

				$data = empty( $type['hook'] )
					? FALSE
					: get_option( $this->_get_address_type_option( $type['hook'] ) );

				if ( ! ModuleSettings::renderCard_address_type_report( $type, $data ) )
					continue;

				$available = TRUE;
			}
		}

		if ( ! $available )
			gEditorial\Info::renderNoReportsAvailable();

		echo '</div>';
	}
}
