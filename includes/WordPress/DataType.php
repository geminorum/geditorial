<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class DataType extends Core\Base
{

	public static function is( $data ) {}
	public static function sanitize( $input ) {}
	public static function validate( $data ) {}
	public static function extract( $data ) {}
	public static function prep( $value, $args = [], $context = 'display', $icon = NULL ) {}
	public static function discovery( $criteria ) {}
	// public static function getHTMLPattern() {}
	public static function pattern() {}
}
