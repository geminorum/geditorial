<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Fiscal extends gEditorial\Service
{
	public static function prepIBAN( mixed $input, ?string $context = NULL, null|false|string $fallback = '' ): null|false|string
	{
		if ( self::empty( $input ) )
			return $fallback;

		if ( ! $data = Core\Text::force( $input ) )
			return $fallback;

		if ( 'export' === $context )
			return Core\Number::translate( $data );

		if ( FALSE === ( $info = gEditorial\Info::fromIBAN( $data ) ) )
			return sprintf( '<span class="-iban %s">%s</span>', '-not-valid', $data );

		return sprintf( '<span class="-iban %s" title="%s">%s</span>',
			'-is-valid',
			$info['bankname'] ?? gEditorial\Plugin::na( FALSE ),
			Core\HTML::wrapLTR( $info['formatted'] ?? $data ),
		);
	}

	public static function prepBankCard( mixed $input, ?string $context = NULL, null|false|string $fallback = '' ): null|false|string
	{
		if ( self::empty( $input ) )
			return $fallback;

		if ( ! $data = Core\Text::force( $input ) )
			return $fallback;

		if ( 'export' === $context )
			return Core\Number::translate( $data );

		if ( FALSE === ( $info = gEditorial\Info::fromCardNumber( $data ) ) )
			return sprintf( '<span class="-bankcard %s">%s</span>', '-not-valid', $data );

		return sprintf( '<span class="-bankcard %s" title="%s">%s</span>',
			'-is-valid',
			$info['bankname'] ?? gEditorial\Plugin::na( FALSE ),
			Core\HTML::wrapLTR( $info['formatted'] ?? $data ),
		);
	}
}
