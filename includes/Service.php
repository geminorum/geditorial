<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Service extends WordPress\Main
{

	const BASE = 'geditorial';

	public static function factory()
	{
		return gEditorial();
	}
}
