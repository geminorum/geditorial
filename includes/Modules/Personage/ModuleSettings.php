<?php namespace geminorum\gEditorial\Modules\Personage;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleSettings extends gEditorial\Settings
{

	const MODULE = 'personage';

	const ACTION_PARSE_POOL = 'do_tool_parse_pool';
	const INPUT_PARSE_POOL  = 'parse_pool_raw_data';

	public static function handleTool_parse_pool()
	{
		if ( ! $pool = self::req( static::INPUT_PARSE_POOL ) )
			return FALSE;

		$parsed = [];

		foreach ( Core\Text::splitLines( $pool ) as $row )
			$parsed[] = ModuleHelper::parseFullname( $row );

		if ( ! $parsed = array_values( array_filter( $parsed ) ) )
			return FALSE;

		$headers = array_keys( $parsed[0] );

		if ( FALSE !== ( $data = Core\Text::toCSV( array_merge( [ $headers ], $parsed ) ) ) )
			Core\Text::download( $data, Core\File::prepName( 'parsed-pool.csv' ) );

		return TRUE;
	}

	public static function renderCard_parse_pool()
	{
		echo self::toolboxCardOpen( _x( 'People Parser', 'Card Title', 'geditorial-personage' ), FALSE );

		echo Core\HTML::wrap( Core\HTML::tag( 'textarea', [
			'name'         => static::INPUT_PARSE_POOL,
			'rows'         => 5,
			'class'        => 'textarea-autosize',
			'style'        => 'width:100%;',
			'autocomplete' => 'off',
			'placeholder'  => _x( 'One person per line', 'Placeholder', 'geditorial-personage' ),
		], NULL ), 'textarea-wrap' );

		echo '<div class="-wrap -wrap-button-row">';
			self::submitButton( static::ACTION_PARSE_POOL,
				_x( 'Parse Lines', 'Button', 'geditorial-personage' ) );

			Core\HTML::desc( sprintf(
				/* translators: %s: file ext placeholder */
				_x( 'Generates a %s file with parsed parts of each name.', 'Message', 'geditorial-personage' ),
				Core\HTML::code( 'csv' )
			), FALSE );
		echo '</div></div>';
	}
}
