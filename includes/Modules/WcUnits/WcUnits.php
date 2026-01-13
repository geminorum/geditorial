<?php namespace geminorum\gEditorial\Modules\WcUnits;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class WcUnits extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'wc_units',
			'title'    => _x( 'WC Units', 'Modules: WC Units', 'geditorial-admin' ),
			'desc'     => _x( 'Weight and Dimension Enhancements for WooCommerce', 'Modules: WC Units', 'geditorial-admin' ),
			'icon'     => 'image-crop',
			'i18n'     => 'adminonly',
			'access'   => 'beta',
			'disabled' => Services\Modulation::moduleCheckWooCommerce(),
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'non_admin_only',
					'title'       => _x( 'Non-Admin Only', 'Setting Title', 'geditorial-wc-units' ),
					'description' => _x( 'Modifies filters only on non-administration areas of your site.', 'Setting Description', 'geditorial-wc-units' ),
					'default'     => '1',
				],
				[
					'field'       => 'decimal_point',
					'type'        => 'text',
					'title'       => _x( 'Decimal Point', 'Setting Title', 'geditorial-wc-units' ),
					'description' => _x( 'Formats decimal point in units with a custom character.', 'Setting Description', 'geditorial-wc-units' ),
					'default'     => _x( '.', 'Setting Default: Decimal Point', 'geditorial-wc-units' ),
					'placeholder' => Core\L10n::localeconv( 'decimal_point', '.' ),
					'field_class' => [ 'small-text', 'code-text' ],
				],
				[
					'field'       => 'multiplication_sign',
					'type'        => 'text',
					'title'       => _x( 'Multiplication Sign', 'Setting Title', 'geditorial-wc-units' ),
					'description' => _x( 'Joins dimension units with a custom string.', 'Setting Description', 'geditorial-wc-units' ),
					'default'     => _x( '&nbsp;&times;&nbsp;', 'Setting Default: Multiplication Sign', 'geditorial-wc-units' ),
					'field_class' => [ 'medium-text', 'code-text' ],
				],
			],
			'_weight' => [
				[
					'field'       => 'fallback_empty_weight',
					'type'        => 'number',
					'title'       => _x( 'Weight Empty Fallback', 'Setting Title', 'geditorial-wc-units' ),
					'description' => _x( 'Sets a fallback value on products with empty <b>weight</b> field. Leave empty to disable.', 'Setting Description', 'geditorial-wc-units' ),
				],
				[
					'field'       => 'weight_attr_bottom',
					'title'       => _x( 'After All Attributes', 'Setting Title', 'geditorial-wc-units' ),
					'description' => _x( 'Moves the weight to the bottom of the product attributes table.', 'Setting Description', 'geditorial-wc-units' ),
				],
				[
					'field'       => 'weight_custom_na',
					'type'        => 'text',
					'title'       => _x( 'Not Available on Weights', 'Setting Title', 'geditorial-wc-units' ),
					'description' => _x( 'Used as not available string upon display of weights. Leave empty to disable.', 'Setting Description', 'geditorial-wc-units' ),
					'default'     => gEditorial()->na( FALSE ),
					'field_class' => [ 'medium-text' ],
				],
				[
					'field' => 'weight_template__kg',
					'type'  => 'text',
					'title' => sprintf(
						/* translators: `%s`: unit name */
						_x( 'Template for %s', 'Setting Title', 'geditorial-wc-units' ),
						Core\HTML::code( 'kg' )
					),
					'description' => _x( 'Used as template upon display of weights in “Kilograms”.', 'Setting Description', 'geditorial-wc-units' ),
					/* translators: `%s`: unit amount */
					'default'     => _x( '%s kg', 'Setting Default', 'geditorial-wc-units' ),
					'field_class' => [ 'medium-text' ],
				],
				[
					'field' => 'weight_template__g',
					'type'  => 'text',
					'title' => sprintf(
						/* translators: `%s`: unit name */
						_x( 'Template for %s', 'Setting Title', 'geditorial-wc-units' ),
						Core\HTML::code( 'g' )
					),
					'description' => _x( 'Used as template upon display of weight in “Grams”.', 'Setting Description', 'geditorial-wc-units' ),
					/* translators: `%s`: unit amount */
					'default'     => _x( '%s g', 'Setting Default', 'geditorial-wc-units' ),
					'field_class' => [ 'medium-text' ],
				],
				[
					'field' => 'weight_template__lbs',
					'type'  => 'text',
					'title' => sprintf(
						/* translators: `%s`: unit name */
						_x( 'Template for %s', 'Setting Title', 'geditorial-wc-units' ),
						Core\HTML::code( 'lbs' )
					),
					'description' => _x( 'Used as template upon display of weights in “Pounds”.', 'Setting Description', 'geditorial-wc-units' ),
					/* translators: `%s`: unit amount */
					'default'     => _x( '%s lbs', 'Setting Default', 'geditorial-wc-units' ),
					'field_class' => [ 'medium-text' ],
				],
				[
					'field' => 'weight_template__oz',
					'type'  => 'text',
					'title' => sprintf(
						/* translators: `%s`: unit name */
						_x( 'Template for %s', 'Setting Title', 'geditorial-wc-units' ),
						Core\HTML::code( 'oz' )
					),
					'description' => _x( 'Used as template upon display of weight in “Ounces”.', 'Setting Description', 'geditorial-wc-units' ),
					/* translators: `%s`: unit amount */
					'default'     => _x( '%s oz', 'Setting Default', 'geditorial-wc-units' ),
					'field_class' => [ 'medium-text' ],
				],
			],
			'_dimensions' => [
				[
					'field'       => 'fallback_empty_length',
					'type'        => 'number',
					'title'       => _x( 'Length Empty Fallback', 'Setting Title', 'geditorial-wc-units' ),
					'description' => _x( 'Sets a fallback value on products with empty <b>length</b> field. Leave empty to disable.', 'Setting Description', 'geditorial-wc-units' ),
				],
				[
					'field'       => 'fallback_empty_width',
					'type'        => 'number',
					'title'       => _x( 'Width Empty Fallback', 'Setting Title', 'geditorial-wc-units' ),
					'description' => _x( 'Sets a fallback value on products with empty <b>width</b> field. Leave empty to disable.', 'Setting Description', 'geditorial-wc-units' ),
				],
				[
					'field'       => 'fallback_empty_height',
					'type'        => 'number',
					'title'       => _x( 'Height Empty Fallback', 'Setting Title', 'geditorial-wc-units' ),
					'description' => _x( 'Sets a fallback value on products with empty <b>height</b> field. Leave empty to disable.', 'Setting Description', 'geditorial-wc-units' ),
				],
				[
					'field'       => 'dimensions_attr_bottom',
					'title'       => _x( 'After All Attributes', 'Setting Title', 'geditorial-wc-units' ),
					'description' => _x( 'Moves the dimensions to the bottom of the product attributes table.', 'Setting Description', 'geditorial-wc-units' ),
				],
				[
					'field'       => 'dimensions_custom_na',
					'type'        => 'text',
					'title'       => _x( 'Not Available on Dimensions', 'Setting Title', 'geditorial-wc-units' ),
					'description' => _x( 'Used as not available string upon display of dimensions. Leave empty to disable.', 'Setting Description', 'geditorial-wc-units' ),
					'default'     => gEditorial()->na( FALSE ),
					'field_class' => [ 'medium-text' ],
				],
				[
					'field' => 'dimensions_template__m',
					'type'  => 'text',
					'title' => sprintf(
						/* translators: `%s`: unit name */
						_x( 'Template for %s', 'Setting Title', 'geditorial-wc-units' ),
						Core\HTML::code( 'm' )
					),
					'description' => _x( 'Used as template upon display of dimensions in “Metres”.', 'Setting Description', 'geditorial-wc-units' ),
					/* translators: `%s`: unit amount */
					'default'     => _x( '%s m', 'Setting Default', 'geditorial-wc-units' ),
					'field_class' => [ 'medium-text' ],
				],
				[
					'field' => 'dimensions_template__cm',
					'type'  => 'text',
					'title' => sprintf(
						/* translators: `%s`: unit name */
						_x( 'Template for %s', 'Setting Title', 'geditorial-wc-units' ),
						Core\HTML::code( 'cm' )
					),
					'description' => _x( 'Used as template upon display of dimensions in “Centimetres”.', 'Setting Description', 'geditorial-wc-units' ),
					/* translators: `%s`: unit amount */
					'default'     => _x( '%s cm', 'Setting Default', 'geditorial-wc-units' ),
					'field_class' => [ 'medium-text' ],
				],
				[
					'field' => 'dimensions_template__mm',
					'type'  => 'text',
					'title' => sprintf(
						/* translators: `%s`: unit name */
						_x( 'Template for %s', 'Setting Title', 'geditorial-wc-units' ),
						Core\HTML::code( 'mm' )
					),
					'description' => _x( 'Used as template upon display of dimensions in “Millimetres”.', 'Setting Description', 'geditorial-wc-units' ),
					/* translators: `%s`: unit amount */
					'default'     => _x( '%s mm', 'Setting Default', 'geditorial-wc-units' ),
					'field_class' => [ 'medium-text' ],
				],
				[
					'field' => 'dimensions_template__in',
					'type'  => 'text',
					'title' => sprintf(
						/* translators: `%s`: unit name */
						_x( 'Template for %s', 'Setting Title', 'geditorial-wc-units' ),
						Core\HTML::code( 'in' )
					),
					'description' => _x( 'Used as template upon display of dimensions in “Inchs”.', 'Setting Description', 'geditorial-wc-units' ),
					/* translators: `%s`: unit amount */
					'default'     => _x( '%s in', 'Setting Default', 'geditorial-wc-units' ),
					'field_class' => [ 'medium-text' ],
				],
				[
					'field' => 'dimensions_template__yd',
					'type'  => 'text',
					'title' => sprintf(
						/* translators: `%s`: unit name */
						_x( 'Template for %s', 'Setting Title', 'geditorial-wc-units' ),
						Core\HTML::code( 'yd' )
					),
					'description' => _x( 'Used as template upon display of dimensions in “Yards”.', 'Setting Description', 'geditorial-wc-units' ),
					/* translators: `%s`: unit amount */
					'default'     => _x( '%s yd', 'Setting Default', 'geditorial-wc-units' ),
					'field_class' => [ 'medium-text' ],
				],
			],
		];
	}

	protected function settings_section_titles( $suffix )
	{
		switch ( $suffix ) {

			case '_weight': return [ _x( 'Weight', 'Setting Section Title', 'geditorial-wc-units' ),
				_x( 'Format a weight for display.', 'Setting Section Description', 'geditorial-wc-units' ) ];

			case '_dimensions': return [ _x( 'Dimensions', 'Setting Section Title', 'geditorial-wc-units' ),
				_x( 'Format dimensions for display.', 'Setting Section Description', 'geditorial-wc-units' ) ];
		}

		return FALSE;
	}

	public function init()
	{
		parent::init();

		$admin = is_admin();

		// @REF: https://wallydavid.com/set-a-default-length-width-height-weight-in-woocommerce/
		foreach ( [ 'weight', 'length', 'width', 'height' ] as $measurement )
			$this->filter( [ 'product_get_'.$measurement, 'product_variation_get_'.$measurement ], 2, 8, FALSE, 'woocommerce' );

		if ( $admin )
			$this->filter( 'products_general_settings', 1, 99, FALSE, 'woocommerce' );

		if ( $admin && ! WordPress\IsIt::ajax() && $this->get_setting( 'non_admin_only', TRUE ) )
			return;

		$this->filter( 'format_weight', 2, 12, FALSE, 'woocommerce' );
		$this->filter( 'format_dimensions', 2, 12, FALSE, 'woocommerce' );

		if ( $this->get_setting( 'decimal_point' ) )
			$this->filter( 'format_localized_decimal', 1, 12, FALSE, 'woocommerce' );

		if ( $this->get_setting( 'weight_attr_bottom' ) || $this->get_setting( 'dimensions_attr_bottom' ) )
			$this->filter( 'display_product_attributes', 2, 999, FALSE, 'woocommerce' );
	}

	public function product_get_weight( $value, $product )
	{
		return $value ?: $this->get_setting_fallback( 'fallback_empty_weight', $value );
	}

	public function product_get_length( $value, $product )
	{
		return $value ?: $this->get_setting_fallback( 'fallback_empty_length', $value );
	}

	public function product_get_width( $value, $product )
	{
		return $value ?: $this->get_setting_fallback( 'fallback_empty_width', $value );
	}

	public function product_get_height( $value, $product )
	{
		return $value ?: $this->get_setting_fallback( 'fallback_empty_height', $value );
	}

	public function format_weight( $string, $weight, $unit = NULL )
	{
		$formatted = wc_format_localized_decimal( $weight );

		if ( empty( $formatted ) )
			return $this->get_setting( 'weight_custom_na' ) ?: '';

		$unit = $unit ?? get_option( 'woocommerce_weight_unit' );

		$template = $this->get_setting(
			sprintf( 'weight_template__%s', $unit ),
			sprintf( '%s %s', '%s', $unit )
		);

		return sprintf( $template, Core\Number::localize( $formatted ) );
	}

	public function format_dimensions( $string, $dimensions, $unit = NULL )
	{
		$formatted = implode( $this->get_setting( 'multiplication_sign', '&nbsp;&times;&nbsp;' ),
			array_filter( array_map( 'wc_format_localized_decimal', $dimensions ) ) );

		if ( empty( $formatted ) )
			return $this->get_setting( 'dimensions_custom_na' ) ?: '';

		$unit = $unit ?? get_option( 'woocommerce_dimension_unit' );

		$template = $this->get_setting(
			sprintf( 'dimensions_template__%s', $unit ),
			sprintf( '%s %s', '%s', $unit )
		);

		return sprintf( $template, Core\Number::localize( $formatted ) );
	}

	// Arabic Decimal Separator: https://unicode-table.com/en/066B/
	// Arabic Thousands Separator: https://unicode-table.com/en/066C/
	public function format_localized_decimal( $value )
	{
		return str_replace( '.', $this->get_setting( 'decimal_point', '.' ), (string) $value );
	}

	public function display_product_attributes( $attributes, $product )
	{
		$after = [];

		if ( $this->get_setting( 'dimensions_attr_bottom' )
			&& array_key_exists( 'dimensions', $attributes ) ) {

			$after['dimensions'] = $attributes['dimensions'];
			unset( $attributes['dimensions'] );
		}

		if ( $this->get_setting( 'weight_attr_bottom' )
			&& array_key_exists( 'weight', $attributes ) ) {

			$after['weight'] = $attributes['weight'];
			unset( $attributes['weight'] );
		}

		return $attributes + $after;
	}

	// Hints the formatting on WooCommerce settings page
	public function products_general_settings( $settings )
	{
		foreach ( $settings as &$setting ) {

			if ( 'woocommerce_weight_unit' === $setting['id'] ) {

				foreach ( [ 'kg', 'g', 'lbs', 'oz' ] as $weight_unit )
					if ( array_key_exists( $weight_unit, $setting['options'] ) )
						$setting['options'][$weight_unit] = sprintf(
							'%s &mdash; (%s)',
							$setting['options'][$weight_unit],
							$this->format_weight( NULL, 1, $weight_unit )
						);

			} else if ( 'woocommerce_dimension_unit' === $setting['id'] ) {

				foreach ( [ 'm', 'cm', 'mm', 'in', 'yd' ] as $dimension_unit )
					if ( array_key_exists( $dimension_unit, $setting['options'] ) )
						$setting['options'][$dimension_unit] = sprintf(
							'%s &mdash; (%s)',
							$setting['options'][$dimension_unit],
							$this->format_dimensions( NULL, [ 1, 1, 1 ], $dimension_unit )
						);
			}
		}

		return $settings;
	}
}
