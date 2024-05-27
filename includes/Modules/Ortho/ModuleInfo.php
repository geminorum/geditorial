<?php namespace geminorum\gEditorial\Modules\Ortho;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;

class ModuleInfo extends gEditorial\Info
{

	const MODULE = 'ortho';

	const VIRASTAR_VERSION     = '0.21.0';
	const PERSIANTOOLS_VERSION = '0.1.0';

	public static function getHelpTabs( $context = NULL )
	{
		return [
			[
				'title'    => _x( 'Virastar', 'Help Tab Title', 'geditorial-ortho' ),
				'id'      => self::classs( 'virastar' ),
				'content' => self::buffer( [ __CLASS__, 'renderrenderHelpTab_virastar' ] ),
			],
			[
				'title'   => _x( 'PersianTools', 'Help Tab Title', 'geditorial-ortho' ),
				'id'      => self::classs( 'persiantools' ),
				'content' => self::buffer( [ __CLASS__, 'renderrenderHelpTab_persiantools' ] ),
			],
		];
	}

	public static function renderrenderHelpTab_virastar()
	{
		printf( '<div class="-info"><p>Virastar is a Persian text cleaner.</p><p class="-from">Virastar v%s installed. For more information, Please see Virastar <a href="%s" target="_blank">home page</a> or <a href="%s" target="_blank">live demo</a>.</p></div>',
			static::VIRASTAR_VERSION, 'https://github.com/brothersincode/virastar', 'https://virastar.brothersincode.ir' );
	}

	public static function renderrenderHelpTab_persiantools()
	{
		printf( '<div class="-info"><p>PersianTools is a Persian text library.</p><p class="-from">PersianTools v%s installed. For more information, Please see PersianTools <a href="%s" target="_blank">home page</a>.</p></div>',
			static::PERSIANTOOLS_VERSION, 'https://github.com/Bersam/persiantools' );
	}
}
