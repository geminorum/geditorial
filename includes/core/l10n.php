<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialL10n extends gEditorialBaseCore
{

	public static function getNooped( $singular, $plural )
	{
		return array( 'singular' => $singular, 'plural' => $plural );
	}

	public static function sprintfNooped( $nooped, $count )
	{
		return sprintf( translate_nooped_plural( $nooped, $count ), gEditorialNumber::format( $count ) );
	}
}
