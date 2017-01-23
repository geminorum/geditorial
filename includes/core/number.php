<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialNumber extends gEditorialBaseCore
{

	// FIXME: use our own
	public static function format( $number, $decimals = 0, $locale = NULL )
	{
		return apply_filters( 'number_format_i18n', $number );
	}

	// FIXME: use our own
	// converts back number chars into english
	public static function intval( $text, $intval = TRUE )
	{
		$number = apply_filters( 'number_format_i18n_back', $text );

		return $intval ? intval( $number ) : $number;
	}

	// @SOURCE: WP's `zeroise()`
	public static function zeroise( $number, $threshold, $locale = NULL )
	{
		return sprintf( '%0'.$threshold.'s', $number );
	}
}
