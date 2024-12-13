<?php namespace geminorum\gEditorial\Modules\Isbn;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE  = 'isbn';
	const BARCODE = 'ean13';

	public static function barcode( $data )
	{
		return Services\Barcodes::getBWIPPjs( static::BARCODE, $data );
	}
}
