<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Ajax extends WordPress\Main
{
	const BASE = 'geditorial';

	public static function factory()
	{
		return gEditorial();
	}

	#[\Deprecated( 'USE `Services\Ajax::checkReferer()`' )]
	public static function checkReferer( ?string $action = NULL, ?string $key = NULL ): int|false
	{
		return Services\Ajax::checkReferer( $action, $key );
	}

	#[\Deprecated( 'USE `Services\Ajax::success()`' )]
	public static function success( mixed $data = NULL, ?int $status_code = NULL ): void
	{
		Services\Ajax::success( $data, $status_code );
	}

	#[\Deprecated( 'USE `Services\Ajax::error()`' )]
	public static function error( mixed $data = NULL, ?int $status_code = NULL ): void
	{
		Services\Ajax::error( $data, $status_code );
	}

	#[\Deprecated( 'USE `Services\Ajax::successHTML()`' )]
	public static function successHTML( string $html, ?int $status_code = NULL ): void
	{
		Services\Ajax::successHTML( $html, $status_code );
	}

	#[\Deprecated( 'USE `Services\Ajax::successMessage()`' )]
	public static function successMessage( ?string $message = NULL ): void
	{
		Services\Ajax::successMessage( $message );
	}

	#[\Deprecated( 'USE `Services\Ajax::errorHTML()`' )]
	public static function errorHTML( string $html, ?int $status_code = NULL ): void
	{
		Services\Ajax::errorHTML( $html, $status_code );
	}

	#[\Deprecated( 'USE `Services\Ajax::errorMessage()`' )]
	public static function errorMessage( null|string|object $message = NULL ): void
	{
		Services\Ajax::errorMessage( $message );
	}

	#[\Deprecated( 'USE `Services\Ajax::errorInvalid()`' )]
	public static function errorInvalid(): void
	{
		Services\Ajax::errorInvalid();
	}

	#[\Deprecated( 'USE `Services\Ajax::errorUserCant()`' )]
	public static function errorUserCant(): void
	{
		Services\Ajax::errorUserCant();
	}

	#[\Deprecated( 'USE `Services\Ajax::errorWhat()`' )]
	public static function errorWhat(): void
	{
		Services\Ajax::errorWhat();
	}

	#[\Deprecated( 'USE `Services\Ajax::spinner()`' )]
	public static function spinner( ?bool $admin = NULL, mixed $data = NULL ): string
	{
		return Services\Ajax::spinner( $admin, $data );
	}
}
