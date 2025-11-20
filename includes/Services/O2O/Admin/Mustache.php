<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

abstract class Mustache
{
	private static $mustache;

	public static function init()
	{
		$path   = dirname( __FILE__ ).'/templates';
		$loader = new \Mustache\Loader\FilesystemLoader( $path, [ 'extension' => 'html' ] );

		self::$mustache = new \Mustache\Engine( [
			'loader'          => $loader,
			'partials_loader' => $loader,
		] );
	}

	public static function render( $template, $data )
	{
		if ( empty( self::$mustache ) )
			self::init();

		return self::$mustache->render( $template, $data );
	}
}
