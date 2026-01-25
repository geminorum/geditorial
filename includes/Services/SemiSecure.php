<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class SemiSecure extends gEditorial\Service
{
	/**
	 * Generates a Security Token for authorization.
	 *
	 * @param string $context
	 * @param string $subject
	 * @param string $fullname
	 * @param int $expires
	 * @param mixed $fallback
	 * @return string
	 */
	public static function generateToken( $context, $subject, $fullname, $expires = NULL, $fallback = FALSE )
	{
		if ( ! $subject || ! $fullname )
			return $fallback;

		$algo = apply_filters( static::BASE.'_securitytoken_algorithm', 'RS256', $context );
		$rsa  = apply_filters( static::BASE.'_securitytoken_rsakey_path', NULL, $context );
		$age  = apply_filters( static::BASE.'_securitytoken_expires', $expires ?? Core\Date::YEAR_IN_SECONDS, $context );

		if ( ! $algo || ! $rsa )
			return $fallback;

		/**
		 * @package `adhocore/jwt`
		 * @link https://github.com/adhocore/php-jwt
		 */
		$jwt = new \Ahc\Jwt\JWT(
			$rsa,
			$algo,
			$age ?: Core\Date::YEAR_IN_SECONDS
		);

		return $jwt->encode( [
			'name' => $fullname,
			'sub'  => $subject,
		] );
	}
}
