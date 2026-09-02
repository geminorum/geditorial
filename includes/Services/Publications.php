<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Publications extends gEditorial\Service
{
	public static function prepISBN( mixed $input, ?string $context = NULL, null|false|string $fallback = '' ): null|false|string
	{
		if ( ! $data = Core\Text::force( $input ) )
			return $fallback;

		if ( 'export' === $context )
			return Core\Number::translate( $data );

		if ( 'admin' === $context )
			return sprintf( '<span class="-isbn %s do-clicktoclip" data-clipboard-text="%s">%s</span>',
				Core\ISBN::validate( $data ) ? '-is-valid' : '-not-valid',
				$data,
				$data,
			);

		return Lookup::htmlISBN( $data );
	}

	public static function prepBiblio( mixed $input, ?string $context = NULL, null|false|string $fallback = '' ): null|false|string
	{
		if ( ! $data = Core\Text::force( $input ) )
			return $fallback;

		if ( 'export' === $context )
			return Core\Number::translate( $data );

		if ( ! Core\Validation::isBibliographic( $data ) )
			return sprintf( '<span class="-biblio %s do-clicktoclip" data-clipboard-text="%s">%s</span>',
				'-not-valid',
				$data,
				$data,
			);

		return Core\HTML::tag( 'a', [
			'href'   => sprintf( 'https://opac.nlai.ir/opac-prod/bibliographic/%s', $data ),
			'title'  => _x( 'See the page about this on National Library website.', 'Service: Publications', 'geditorial' ),
			'class'  => '-is-valid',
			'target' => '_blank',
		], Core\Number::localize( $data ) );
	}
}
