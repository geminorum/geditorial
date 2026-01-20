<?php namespace geminorum\gEditorial\Modules\People;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'people';

	// TODO: must returns array of options
	// TODO: must filter to add/remove honorifics
	// TODO: must query database directly!
	public static function getCriteria( $string )
	{
		$criteria = FALSE;  // Means no need the search again!

		$sanitized   = Core\Text::trimQuotes( $string );
		$familyfirst = Core\Text::nameFamilyFirst( $sanitized );
		$familylast  = Core\Text::nameFamilyLast( $sanitized );

		// only if different
		if ( $familylast === $familyfirst )
			return $criteria;

		else if ( $sanitized === $familyfirst )
			$criteria = $familylast;

		else if ( $sanitized === $familylast )
			$criteria = $familyfirst;

		return $criteria;
	}
}
