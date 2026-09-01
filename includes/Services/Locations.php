<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Misc;
use geminorum\gEditorial\WordPress;

class Locations extends gEditorial\Service
{
	public static function setup(): void
	{
		if ( is_admin() )
			return;

		add_filter( self::und( static::BASE, 'prep_location' ), [ __CLASS__, 'filter_prep_location_front' ], 5, 4 );
	}

	public static function isParserAvailable()
	{
		return in_array(
			Core\L10n::locale( TRUE ),
			Misc\AddressInPersian::SUPPORTED_LOCALE,
			TRUE
		);
	}

	public static function prepPostCode( mixed $input, ?string $context = NULL, null|false|string $fallback = '' ): null|false|string
	{
		if ( self::empty( $input ) )
			return $fallback;

		if ( ! $data = Core\Text::force( $input ) )
			return $fallback;

		if ( 'export' === $context )
			return Core\Number::translate( $data );

		if ( FALSE === ( $postcode = gEditorial\Info::fromPostCode( $data ) ) )
			return sprintf( '<span class="-postcode %s">%s</span>', '-not-valid', $data );

		return sprintf( '<span class="-postcode %s" title="%s">%s</span>',
			'-is-valid -ltr',
			$postcode['country'] ?? gEditorial\Plugin::na( FALSE ),
			Core\HTML::wrapLTR( $postcode['formatted'] ?? $data )
		);
	}

	// OLD: `WordPress\Strings::prepAddress()`
	public static function prepAddress( mixed $input, ?string $context = NULL, null|false|string $fallback = '' ): null|false|string
	{
		if ( self::empty( $input ) )
			return $fallback;

		if ( ! $data = Core\Text::force( $input ) )
			return $fallback;

		if ( ! $data = Core\Text::normalizeWhitespace( WordPress\Strings::cleanupChars( $data ) ) )
			return $fallback;

		$data = trim( $data, '.-|…' );
		$data = str_ireplace( [ '_', '|', '–', '—' ], '-', $data );
		$data = sprintf( ' %s ', $data ); // padding with space

		if ( self::isParserAvailable() )
			$data = Misc\AddressInPersian::prepExtra( $data, $context, '' );

		$data = preg_replace( '/\s+([\,\،])/mu', '$1', $data );
		$data = preg_replace( '/\s+([\-])/mu', '$1', $data );
		$data = preg_replace( '/([\-])\s+/mu', '$1', $data );

		return Core\Text::normalizeWhitespace( $data );
	}

	public static function prepVenue( mixed $value, ?string $context = NULL, null|false|string $empty = '', ?string $separator = NULL, null|string|array $delimiters = NULL ): null|false|string
	{
		if ( self::empty( $value ) )
			return $empty;

		// @hook `geditorial_prep_location`
		$hook = self::und( static::BASE, 'prep', 'location' );
		$list = [];

		if ( $context && is_null( $separator ) ) {

			if ( in_array( $context, [ 'export' ] ) )
				$separator = '|';
		}

		foreach ( Markup::getSeparated( $value, $delimiters ) as $item )
			if ( $prepared = apply_filters( $hook, $item, $item, $value, $context ) )
				$list[] = $prepared;

		return WordPress\Strings::getJoined( $list, '', '', $empty, $separator );
	}

	public static function filter_prep_location_front( string $item, string $raw, mixed $value, ?string $context ): string
	{
		// late check for REST-API
		if ( WordPress\IsIt::rest() )
			return $item;

		if ( $context && in_array( $context, [ 'print', 'export' ] ) )
			return $item;

		if ( $link = WordPress\URL::search( $item ) )
			return Core\HTML::link( $item, $link );

		return $item;
	}

	public static function getTermLocation( mixed $term = NULL, ?string $context = NULL ): false|array
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

	public static function getPostLocation( mixed $post = NULL, ?string $context = NULL ): false|array
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

	public static function baseCountry( null|false|string $fallback = NULL, bool $filtered = TRUE ): null|false|string
	{
		if ( WordPress\WooCommerce::available() )
			return $filtered ? self::filters( 'locations_base_country',
				WordPress\WooCommerce::getBaseCountry(),
				'woocommerce',
				$fallback
			) : WordPress\WooCommerce::getBaseCountry();

		if ( FALSE !== ( $country = Core\Base::const( 'NUCLEUS_DEFAULT_COUNTRY_CODE', FALSE ) ) )
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
	public static function nameCountry( null|false|string $country, null|false|string $fallback = NULL ): null|false|string|array
	{
		static $data;

		if ( is_null( $country ) )
			return $fallback;

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

	public static function baseState( null|false|string $fallback = NULL, bool $filtered = TRUE ): null|false|string
	{
		if ( WordPress\WooCommerce::available() )
			return $filtered ? self::filters( 'locations_base_state',
				WordPress\WooCommerce::getBaseState(),
				'woocommerce',
				$fallback
			) : WordPress\WooCommerce::getBaseState();

		if ( FALSE !== ( $state = Core\Base::const( 'NUCLEUS_DEFAULT_PROVINCE_CODE', FALSE ) ) )
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
	public static function nameState( null|false|string $state, null|false|string $country, null|false|string $fallback = NULL ): null|false|string|array
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
	 * @param null|false|string $country
	 * @return string|array
	 */
	public static function addressFormats( null|false|string $country = FALSE ): string|array
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
	 * @param array $arguments
	 * @return string
	 */
	public static function formatAddress( array $data = [], array $arguments = [] ): string
	{
		$args = self::parsed( [
			'format'    => NULL,
			'separator' => NULL,
			'context'   => NULL,
		], $arguments );

		$parsed = Core\Arraay::trimText( self::parsed( [
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

	public static function addressTokens( ?string $context = NULL, bool $simplied = FALSE ): array
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
