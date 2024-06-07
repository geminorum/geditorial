<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Crypto extends Base
{

	/**
	 * Generates combinations from given options
	 * @source https://codereview.stackexchange.com/a/62198
	 *
	 * @param  array $options
	 * @param  int   $count
	 * @return array $combinations
	 */
	public static function getCombinations( $options, $count )
	{
		$results = [];

		self::genCombinations( $results, [], $options, $count, 0 );

		return $results;
	}

	private static function genCombinations( &$solutions, $solution, $options, $count, $call )
	{
		if ( $count === count( $solution ) )
			$solutions[] = $solution;

		if ( count( $solution ) < $count ) {

			for ( $i = $call; $i < count( $options ); $i++ ) {
				$solution[] = $options[$i];
				self::genCombinations( $solutions, $solution, $options, $count, $i + 1 );
				array_pop( $solution );
			}
		}
	}

	// @REF: https://github.com/kasparsd/numeric-shortlinks
	const BIJECTION_DIC  = 'abcdefghijklmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ123456789';
	const BIJECTION_BASE = 57; // strlen( BIJECTION_DIC )

	public static function encodeBijection( $id )
	{
		$slug = [];
		$dic  = static::BIJECTION_DIC;

		while ( $id > 0 ) {

			$key = $id % static::BIJECTION_BASE;

			$slug[] = $dic[$key];

			$id = floor( $id / static::BIJECTION_BASE );
		}

		return implode( '', array_reverse( $slug ) );
	}

	public static function decodeBijection( $slug )
	{
		$id  = 0;
		$dic = static::BIJECTION_DIC;

		foreach ( Text::strSplit( trim( $slug ) ) as $char ) {

			$pos = strpos( $dic, $char );

			if ( FALSE === $pos )
				return $slug;

			$id = $id * static::BIJECTION_BASE + $pos;
		}

		return $id ?: $slug;
	}
}
