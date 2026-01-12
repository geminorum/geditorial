<?php namespace geminorum\gEditorial\Modules\NationalLibrary;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class ModuleSettings extends gEditorial\Settings
{
	const MODULE = 'national_library';

	const ACTION_SCRAPE_POOL = 'do_tool_scrape_pool';
	const INPUT_SCRAPE_POOL  = 'scrape_pool_raw_data';

	public static function handleTool_scrape_pool()
	{
		if ( ! $pool = self::req( static::INPUT_SCRAPE_POOL ) )
			return FALSE;

		$scraped = [];

		foreach ( Core\Text::splitLines( $pool ) as $row )
			$scraped[] = ModuleHelper::getFipaRow( $row );

		if ( ! $scraped = array_values( array_filter( $scraped ) ) )
			return FALSE;

		$headers = array_keys( $scraped[0] );

		if ( FALSE !== ( $data = Core\Text::toCSV( array_merge( [ $headers ], $scraped ) ) ) )
			Core\Text::download( $data, Core\File::prepName( 'scraped-pool.csv' ) );

		return TRUE;
	}

	public static function renderCard_scrape_pool()
	{
		echo self::toolboxCardOpen( _x( 'ISBN Scraper', 'Card Title', 'geditorial-national-library' ), FALSE );

		echo Core\HTML::wrap( Core\HTML::tag( 'textarea', [
			'name'         => static::INPUT_SCRAPE_POOL,
			'rows'         => 5,
			'class'        => 'textarea-autosize',
			'style'        => 'width:100%;',
			'dir'          => 'ltr',
			'autocomplete' => 'off',
			'placeholder'  => _x( 'One ISBN per line', 'Placeholder', 'geditorial-national-library' ),
		], NULL ), 'field-wrap -textarea' );

		echo '<div class="-wrap -wrap-button-row">';
			self::submitButton( static::ACTION_SCRAPE_POOL,
				_x( 'Scrape Lines', 'Button', 'geditorial-personage' ) );

			Core\HTML::desc( sprintf(
				/* translators: `%s`: file ext placeholder */
				_x( 'Generates a %s file with scraped parts of each book.', 'Message', 'geditorial-national-library' ),
				Core\HTML::code( 'csv' )
			), FALSE );
		echo '</div></div>';
	}
}
