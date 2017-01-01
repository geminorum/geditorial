<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialNumber extends gEditorialBaseCore
{

	// FIXME: use our own
	public static function format( $number, $decimals = 0, $locale = NULL )
	{
		return apply_filters( 'number_format_i18n', $number );
	}
}
