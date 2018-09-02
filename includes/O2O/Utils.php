<?php namespace geminorum\gEditorial\O2O;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class Utils extends Core\Base
{

	// @SOURCE: `_p2p_normalize()`
	public static function normalize( $items )
	{
		if ( ! is_array( $items ) )
			$items = array( $items );

		foreach ( $items as &$item ) {

			if ( is_a( $item, __NAMESPACE__.'\\Item' ) )
				$item = $item->get_id();

			else if ( is_object( $item ) )
				$item = $item->ID;
		}

		return $items;
	}

	// @SOURCE: `_p2p_expand_direction()`
	public static function expandDirection( $direction )
	{
		if ( ! $direction )
			return [];

		if ( 'any' == $direction )
			return [ 'from', 'to' ];

		else
			return [ $direction ];
	}

	// @SOURCE: `_p2p_flip_direction()`
	public static function flipDirection( $direction )
	{
		$map = [
			'from' => 'to',
			'to'   => 'from',
			'any'  => 'any',
		];

		return $map[$direction];
	}

	// @SOURCE: `_p2p_pluck()`
	public static function pluck( &$list, $key )
	{
		$value = $list[$key];
		unset( $list[$key] );
		return $value;
	}

	// @SOURCE: `_p2p_append()`
	public static function append( &$arr, $values )
	{
		$arr = array_merge( $arr, $values );
	}

	// `_p2p_first()`
	public static function first( $args )
	{
		if ( empty( $args ) )
			return FALSE;

		return reset( $args );
	}

	// `_p2p_get_other_id()`
	public static function getOtherID( $item )
	{
		if ( $item->ID == $item->o2o_from )
			return $item->o2o_to;

		if ( $item->ID == $item->o2o_to )
			return $item->o2o_from;

		trigger_error( "Corrupted data for item {$item->ID}", E_USER_WARNING );
	}

	// @SOURCE: `_p2p_wrap()`
	public static function wrap( $items, $class )
	{
		foreach ( $items as &$item )
			$item = new $class( $item );

		return $items;
	}

	// @SOURCE: `_p2p_compress_direction()`
	public static function compressDirection( $directions )
	{
		if ( empty( $directions ) )
			return FALSE;

		if ( count( $directions ) > 1 )
			return 'any';

		return reset( $directions );
	}
}
