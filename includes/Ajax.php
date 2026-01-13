<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Ajax extends WordPress\Main
{
	const BASE = 'geditorial';

	public static function factory()
	{
		return gEditorial();
	}

	public static function checkReferer( $action = NULL, $key = 'nonce' )
	{
		return check_ajax_referer( ( is_null( $action ) ? static::BASE : $action ), $key );
	}

	public static function success( $data = NULL, $status_code = NULL )
	{
		wp_send_json_success( $data, $status_code );
	}

	public static function error( $data = NULL, $status_code = NULL )
	{
		wp_send_json_error( $data, $status_code );
	}

	public static function successHTML( $html, $status_code = NULL )
	{
		self::success( [ 'html' => $html ], $status_code );
	}

	public static function successMessage( $message = NULL )
	{
		$message = $message ?? _x( 'Successful!', 'Ajax: Ajax Notice', 'geditorial' );

		if ( $message )
			self::success( Core\HTML::success( $message ) );
		else
			self::success();
	}

	public static function errorHTML( $html, $status_code = NULL )
	{
		self::error( [ 'html' => $html ], $status_code );
	}

	public static function errorMessage( $message = NULL )
	{
		$message = $message ?? _x( 'Error!', 'Ajax: Ajax Notice', 'geditorial' );

		if ( $message )
			self::error( Core\HTML::error( $message ) );
		else
			self::error();
	}

	public static function errorUserCant()
	{
		self::errorMessage( _x( 'You\'re not authorized!', 'Ajax: Ajax Notice', 'geditorial' ) );
	}

	public static function errorWhat()
	{
		self::errorMessage( _x( 'What?!', 'Ajax: Ajax Notice', 'geditorial' ) );
	}

	// @REF: https://make.wordpress.org/core/?p=12799
	// @REF: https://austin.passy.co/2014/native-wordpress-loading-gifs/
	public static function spinner( $admin = NULL, $data = NULL )
	{
		return ( $admin ?? is_admin() )
			? '<span class="-loading spinner"'.Core\HTML::propData( $data ).'></span>'
			: '<span class="-loading '.static::BASE.'-spinner"'.Core\HTML::propData( $data ).'></span>';
	}
}
