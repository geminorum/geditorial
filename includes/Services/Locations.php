<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Misc;
use geminorum\gEditorial\WordPress;

class Locations extends gEditorial\Service
{
	public static function setup()
	{
		if ( is_admin() )
			return;

		add_filter( static::BASE.'_prep_location', [ __CLASS__, 'filter_prep_location_front' ], 5, 3 );
	}

	public static function isParserAvailable()
	{
		return in_array(
			Core\L10n::locale( TRUE ),
			Misc\AddressInPersian::SUPPORTED_LOCALE,
			TRUE
		);
	}

	// OLD: `WordPress\Strings::prepAddress()`
	public static function prepAddress( $data, $context = 'display', $fallback = FALSE )
	{
		if ( self::empty( $data ) )
			return $fallback;

		if ( ! $data = Core\Text::normalizeWhitespace( self::cleanupChars( $data ) ) )
			return $fallback;

		$data = trim( $data, '.-|…' );
		$data = str_ireplace( [ '_', '|', '–', '—'  ], '-', $data );
		$data = sprintf( ' %s ', $data ); // padding with space

		if ( self::isParserAvailable() )
			$data = Misc\AddressInPersian::prepExtra( $data, $context, '' );

		$data = preg_replace( '/\s+([\,\،])/mu', '$1', $data );
		$data = preg_replace( '/\s+([\-])/mu', '$1', $data );
		$data = preg_replace( '/([\-])\s+/mu', '$1', $data );

		return Core\Text::normalizeWhitespace( $data );
	}

	public static function prepVenue( $value, $empty = '', $separator = NULL )
	{
		if ( self::empty( $value ) )
			return $empty;

		$list = [];

		foreach ( Markup::getSeparated( $value ) as $location )
			if ( $prepared = apply_filters( static::BASE.'_prep_location', $location, $location, $value ) )
				$list[] = $prepared;

		return WordPress\Strings::getJoined( $list, '', '', $empty, $separator );
	}

	public static function filter_prep_location_front( $location, $raw, $value )
	{
		if ( $link = WordPress\URL::search( $location ) )
			return Core\HTML::link( $location, $link );

		return $location;
	}

	public static function getTermLocation( $term = NULL, $context = NULL )
	{
		if ( ! gEditorial()->enabled( 'terms' ) )
			return FALSE;

		if ( ! $term = WordPress\Term::get( $term ) )
			return FALSE;

		if ( $address = TaxonomyFields::getFieldRaw( 'address', $term->term_id ) ) {

			return [
				'address' => $address,
				'title'   => TaxonomyFields::getFieldRaw( 'venue', $term->term_id ) ?: '',
				'latlng'  => TaxonomyFields::getFieldRaw( 'latlng', $term->term_id ) ?: '',
			];
		}

		return FALSE;
	}

	public static function getPostLocation( $post = NULL, $context = NULL )
	{
		if ( ! gEditorial()->enabled( 'meta' ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( gEditorial()->enabled( 'venue' ) ) {

			if ( $items = gEditorial()->module( 'venue' )->paired_all_connected_from( $post, $context ) ) {
				foreach ( $items as $item )
					return [
						'title'   => WordPress\Post::fullTitle( $item ),
						'address' => PostTypeFields::getFieldRaw( 'street_address', $item->ID ) ?: '',
						'latlng'  => PostTypeFields::getFieldRaw( 'latlng', $item->ID ) ?: '',
					];
			}

		} else if ( $address = PostTypeFields::getFieldRaw( 'street_address', $post->ID ) ) {

			return [
				'address' => $address,
				'title'   => PostTypeFields::getFieldRaw( 'venue_string', $post->ID ) ?: '',
				'latlng'  => PostTypeFields::getFieldRaw( 'latlng', $post->ID ) ?: '',
			];
		}

		return FALSE;
	}

	public static function baseCountry( $fallback = NULL, $filtered = TRUE )
	{
		if ( WordPress\WooCommerce::available() )
			return $filtered ? self::filters( 'locations_base_country',
				WordPress\WooCommerce::getBaseCountry(),
				'woocommerce',
				$fallback
			) : WordPress\WooCommerce::getBaseCountry();

		if ( FALSE !== ( $country = Core\Base::const( 'GCORE_DEFAULT_COUNTRY_CODE', FALSE ) ) )
			return $filtered ? self::filters( 'locations_base_country',
				$country,
				'gnetwork',
				$fallback
			) : $country;

		return $filtered ? self::filters( 'locations_base_country',
			$fallback,
			'fallback',
			$fallback
		) : $fallback;
	}

	// TODO: apply `Divided` module data
	public static function nameCountry( $country, $fallback = NULL )
	{
		static $data;

		if ( empty( $data ) )
			$data = self::filters( 'locations_name_countries', [
				'IR' => _x( 'Iran', 'Country', 'geditorial' ),
			] );

		if ( FALSE === $country )
			return $data;

		return empty( $data[$country] )
			? $fallback ?? $country  // WTF?!
			: $data[$country];
	}

	public static function baseState( $fallback = NULL, $filtered = TRUE )
	{
		if ( WordPress\WooCommerce::available() )
			return $filtered ? self::filters( 'locations_base_state',
				WordPress\WooCommerce::getBaseState(),
				'woocommerce',
				$fallback
			) : WordPress\WooCommerce::getBaseState();

		if ( FALSE !== ( $state = Core\Base::const( 'GCORE_DEFAULT_PROVINCE_CODE', FALSE ) ) )
			return $filtered ? self::filters( 'locations_base_state',
				$state,
				'gnetwork',
				$fallback
			) : $state;

		return $filtered ? self::filters( 'locations_base_state',
			$fallback,
			'fallback',
			$fallback
		) : $fallback;
	}

	// TODO: add `IR` states by default
	// TODO: apply `Iranian` module data
	// TODO: apply `Districted` module data
	// TODO: take advantage of WooCommerce Data!
	public static function nameState( $state, $country, $fallback = NULL )
	{
		static $data = [];

		if ( empty( $country ) )
			return $fallback;

		if ( empty( $data[$country] ) )
			$data = self::filters( 'locations_name_states', [], $country );

		if ( FALSE === $state )
			return $data[$country];

		return empty( $data[$country][$state] )
			? $fallback ?? $state  // WTF?!
			: $data[$country][$state];
	}

	/**
	 * Retrieves address formats by country.
	 * NOTE: WooCommerce uses single mustaches!
	 *
	 * These define how addresses are formatted for display in various countries.
	 * @source `WC_Countries::get_address_formats()`
	 *
	 * @return string|array
	 */
	public static function addressFormats( $country = FALSE )
	{
		static $data;

		if ( empty( $data ) )
			$data = self::filters( 'locations_address_formats', [
				'default' => "{{name}}\n{{company}}\n{{address_1}}\n{{address_2}}\n{{city}}\n{{state}}\n{{postcode}}\n{{country}}",
				'IR'      => "{{name}}\n{{company}}\n{{address_1}}\n{{address_2}}\n{{country}}، {{state}}، {{city}}\n{{postcode}}",
			] );

		if ( FALSE === $country )
			return $data;

		return empty( $data[$country] )
			? $data['default']
			: $data[$country];
	}

	/**
	 * Generates a formatted address.
	 * @source `WC_Countries::get_formatted_address()`
	 * TODO: add extra tokens to WooCommerce filter: `fullname`/`phone`/`fax`/`mobile`
	 * TODO: support linked: country/state/city
	 *
	 * @param array $data
	 * @param array $atts
	 * @return string
	 */
	public static function formatAddress( $data = [], $atts = [] )
	{
		$args = self::atts( [
			'format'    => NULL,
			'separator' => NULL,
			'context'   => NULL,
		], $atts );

		$parsed = Core\Arraay::trimText( self::atts( [
			'first_name' => '',
			'last_name'  => '',
			'company'    => '',
			'address_1'  => '',
			'address_2'  => '',
			'city'       => '',
			'state'      => '',
			'postcode'   => '',
			'country'    => '',
		], $data ), FALSE );

		$name    = Individuals::makeFullname( $parsed, $args['context'] ?? 'address' );
		$state   = self::nameState( $parsed['state'], $parsed['country'], '' );
		$country = self::nameCountry( $parsed['country'], '' );
		$format  = $args['format'] ?? self::addressFormats( $parsed['country'] );

		// Country is not needed if the same as base.
		if ( $parsed['country'] === self::baseCountry() )
			$format = str_replace( [
				'{{{country}}}',
				'{{country}}',
				'{{{country_upper}}}',
				'{{country_upper}}',
			], '', $format );

		// State is not needed if the same as city.
		if ( $parsed['city'] === $state )
			$format = str_replace( [
				'{{{state}}}',
				'{{state}}',
				'{{{state_upper}}}',
				'{{state_upper}}',
			], '', $format );

		$replacements = self::filters( 'locations_address_replacements', [
			'fullname'         => $name,
			'name'             => $name,
			'first_name'       => $parsed['first_name'],
			'last_name'        => $parsed['last_name'],
			'company'          => $parsed['company'],
			'address_1'        => $parsed['address_1'],
			'address_2'        => $parsed['address_2'],
			'city'             => $parsed['city'],
			'state'            => $state,
			'postcode'         => $parsed['postcode'],
			'country'          => $country,
			'fullname_upper'   => Core\Text::strToUpper( $name ),
			'name_upper'       => Core\Text::strToUpper( $name ),
			'first_name_upper' => Core\Text::strToUpper( $parsed['first_name'] ),
			'last_name_upper'  => Core\Text::strToUpper( $parsed['last_name'] ),
			'company_upper'    => Core\Text::strToUpper( $parsed['company'] ),
			'address_1_upper'  => Core\Text::strToUpper( $parsed['address_1'] ),
			'address_2_upper'  => Core\Text::strToUpper( $parsed['address_2'] ),
			'city_upper'       => Core\Text::strToUpper( $parsed['city'] ),
			'state_upper'      => Core\Text::strToUpper( $state ),
			'state_code'       => Core\Text::strToUpper( $parsed['state'] ),
			'postcode_upper'   => Core\Text::strToUpper( $parsed['postcode'] ),
			'country_upper'    => Core\Text::strToUpper( $country ),
		], $parsed );

		$formatted = Core\Text::replaceTokens( $format, $replacements );
		$formatted = Core\Text::normalizeWhitespace( $formatted );

		return implode(
			$args['separator'] ?? '<br/>',
			// Break newlines apart and remove empty lines/trim commas and white space.
			Core\Arraay::trimTextQuotes( explode( "\n", $formatted ) )
		);
	}

	public static function addressTokens( $context = NULL, $simplied = FALSE )
	{
		$tokens = self::filters( 'locations_address_tokens', [
			'fullname',
			'name',
			'first_name',
			'last_name',
			'company',
			'address_1',
			'address_2',
			'city',
			'state',
			'postcode',
			'country',
			'fullname_upper',
			'name_upper',
			'first_name_upper',
			'last_name_upper',
			'company_upper',
			'address_1_upper',
			'address_2_upper',
			'city_upper',
			'state_upper',
			'state_code',
			'postcode_upper',
			'country_upper',
		], $context );

		if ( ! $simplied )
			return $tokens;

		return array_diff( $tokens, [
			'name',
			'fullname_upper',
			'name_upper',
			'first_name_upper',
			'last_name_upper',
			'company_upper',
			'address_1_upper',
			'address_2_upper',
			'city_upper',
			'state_upper',
			'state_code',
			'postcode_upper',
			'country_upper',
		] );
	}
}
