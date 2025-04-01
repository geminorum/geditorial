<?php namespace geminorum\gEditorial\Modules\Attachments;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;

class ModuleInfo extends gEditorial\Info
{
	const MODULE = 'attachments';

	public static function getHelpTabs( $context = NULL )
	{
		return [
			[
				'title'   => _x( 'Shortcodes', 'Help Tab Title', 'geditorial-attachments' ),
				'id'      => static::classs( 'shortcodes' ),
				'content' => self::buffer( [ __CLASS__, 'renderHelpTabList' ], [
					[
						Core\HTML::code( '[attachments mime_type="application/pdf" title=0 wrap=0 /]' ),
					]
				] ),
			],
		];
	}
}
