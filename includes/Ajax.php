<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Ajax extends WordPress\Main
{
	const BASE = 'geditorial';

	public static function factory()
	{
		return gEditorial();
	}

	public static function checkReferer( ?string $action = NULL, ?string $key = NULL ): int|false
	{
		return check_ajax_referer( $action ?? static::BASE, $key ?? 'nonce' );
	}

	public static function success( mixed $data = NULL, ?int $status_code = NULL ): void
	{
		wp_send_json_success( $data, $status_code );
	}

	public static function error( mixed $data = NULL, ?int $status_code = NULL ): void
	{
		wp_send_json_error( $data, $status_code );
	}

	public static function successHTML( string $html, ?int $status_code = NULL ): void
	{
		self::success( [ 'html' => $html ], $status_code );
	}

	public static function successMessage( ?string $message = NULL ): void
	{
		$message = $message ?? Plugin::done( FALSE );

		if ( $message )
			self::success( Core\HTML::success( $message, FALSE, '-via-ajax' ) );

		else
			self::success();
	}

	public static function errorHTML( string $html, ?int $status_code = NULL ): void
	{
		self::error( [ 'html' => $html ], $status_code );
	}

	public static function errorMessage( ?string $message = NULL ): void
	{
		$message = $message ?? Plugin::wrong( FALSE );

		if ( $message )
			self::error( Core\HTML::error( $message, FALSE, '-via-ajax' ) );

		else
			self::error();
	}

	public static function errorUserCant(): void
	{
		self::errorMessage( Plugin::denied( FALSE ) );
	}

	public static function errorWhat(): void
	{
		self::errorMessage( Plugin::what( FALSE ) );
	}

	// @REF: https://make.wordpress.org/core/?p=12799
	// @REF: https://austin.passy.co/2014/native-wordpress-loading-gifs/
	public static function spinner( ?bool $admin = NULL, mixed $data = NULL ): string
	{
		return ( $admin ?? is_admin() )
			? '<span class="-loading spinner"'.Core\HTML::propData( $data ).'></span>'
			: '<span class="-loading '.static::BASE.'-spinner"'.Core\HTML::propData( $data ).'></span>';
	}
}
