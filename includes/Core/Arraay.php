<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Arraay extends Base
{

	// deep array_filter()
	public static function filterArray( $input, $callback = NULL )
	{
		foreach ( $input as &$value )
			if ( is_array( $value ) )
				$value = self::filterArray( $value, $callback );

		return $callback ? array_filter( $input, $callback ) : array_filter( $input );
	}

	public static function roundArray( $array, $precision = -3, $mode = PHP_ROUND_HALF_UP )
	{
		$rounded = array();

		foreach ( (array) $array as $key => $value )
			$rounded[$key] = round( (float) $value, $precision, $mode );

		return $rounded;
	}

	public static function reKey( $list, $key )
	{
		if ( ! empty( $list ) ) {
			$ids  = wp_list_pluck( $list, $key );
			$list = array_combine( $ids, $list );
		}

		return $list;
	}

	// OR: `array_combine( $array, $array );`
	public static function sameKey( $old )
	{
		$new = array();

		foreach ( (array) $old as $key => $value )
			if ( FALSE !== $value && NULL !== $value )
				$new[$value] = $value;

		return $new;
	}

	// USE: `array_keys()` on posted checkboxes
	public static function getKeys( $options, $if = TRUE )
	{
		$keys = array();

		foreach ( (array) $options as $key => $value )
			if ( $value == $if )
				$keys[] = $key;

		return $keys;
	}

	// @SOURCE: http://stackoverflow.com/a/24436324
	// USEAGE: Arraay::replaceKeys( $array, array( 'old_key_1' => 'new_key_1', 'old_key_2' => 'new_key_2' ) );
	public static function replaceKeys( $array, $keys_map )
	{
		$keys = array_keys( $array );

		foreach ( $keys_map as $old_key => $new_key ) {

			if ( FALSE === $index = array_search( $old_key, $keys ) )
				continue;

			$keys[$index] = $new_key;
		}

		return array_combine( $keys, array_values( $array ) );
	}

	public static function range( $start, $end, $step = 1, $format = TRUE )
	{
		$array = array();

		foreach ( range( $start, $end, $step ) as $number )
			$array[$number] = $format ? Number::format( $number ) : $number;

		return $array;
	}

	// for using with $('form').serializeArray();
	// @REF: http://api.jquery.com/serializeArray/
	// @INPUT: [{name:"a",value:"1"},{name:"b",value:"2"}]
	// @OLD: `parseJSArray()`
	public static function parseSerialized( $array )
	{
		$parsed = array();

		foreach ( $array as $part )
			$parsed[$part['name']] = $part['value'];

		return $parsed;
	}

	public static function strposArray( $needles, $haystack )
	{
		foreach ( (array) $needles as $key => $needle )
			if ( FALSE !== strpos( $haystack, $needle ) )
				return $key;

		return FALSE;
	}

	public static function stripDefaults( $atts, $defaults = array() )
	{
		foreach ( $defaults as $key => $value )
			if ( isset( $atts[$key] ) && $value === $atts[$key] )
				unset( $atts[$key] );

		return $atts;
	}

	// @REF: http://stackoverflow.com/a/11026840
	public static function stripByValue( $array, $value )
	{
		return array_diff_key( $array, array_flip( array_keys( $array, $value ) ) );
	}

	// FIXME: TEST THIS!
	// @REF: http://stackoverflow.com/a/4582659
	// USAGE: Arraay::multiSort( $array, array( 'key_1' => SORT_ASC, 'key_2' => SORT_ASC ) );
	public static function multiSort( $array, $sort )
	{
		if ( empty( $array ) )
			return $array;

		$map = $args = array();

		foreach ( $array as $key => $val )
			foreach ( $sort as $by => $order )
				$map[$by][$key] = $val[$by];

		foreach ( $sort as $by => $order ) {
			$args[] = $map[$by];
			$args[] = $order;
		}

		$args[] = &$array;

		call_user_func_array( 'array_multisort', $args );

		// return array_pop( $args ); // @SEE: http://php.net/manual/en/function.array-multisort.php#100534
		return $array;
	}

	// insert an array into another array before/after a certain key
	// @SOURCE: https://gist.github.com/scribu/588429
	public static function insert( $array, $pairs, $key, $position = 'after', $anyways = TRUE )
	{
		$key_pos = array_search( $key, array_keys( $array ) );

		if ( 'after' == $position )
			$key_pos++;

		if ( FALSE !== $key_pos ) {

			$result = array_slice( $array, 0, $key_pos );
			$result = array_merge( $result, $pairs );
			$result = array_merge( $result, array_slice( $array, $key_pos ) );

		} else if ( $anyways ) {

			$result = 'after' == $position ? array_merge( $array, $pairs ) : array_merge( $pairs, $array );

		} else {

			$result = $array;
		}

		return $result;
	}

	// @REF: http://php.net/manual/en/function.array-splice.php#92651
	public static function keyMoveUp( $input, $index )
	{
		$new = $input;

		if ( ( count( $new ) > $index ) && ( $index > 0 ) ) {
			array_splice( $new, $index - 1, 0, $input[$index] );
			array_splice( $new, $index + 1, 1 );
		}

		return $new;
	}

	// @REF: http://php.net/manual/en/function.array-splice.php#92651
	public static function keyMoveDown( $input, $index )
	{
		$new = $input;

		if ( count( $new ) > $index ) {
			array_splice( $new, $index + 2, 0, $input[$index] );
			array_splice( $new, $index, 1 );
		}

		return $new;
	}

	// `array_key_first()` for php < 7.3.0
	// @REF: http://php.net/manual/en/function.array-key-first.php#123503
	public static function keyFirst( $input )
	{
		if ( function_exists( 'array_key_first' ) )
			return array_key_first( $input ); // phpcs:ignore

		return $input ? array_keys( $input )[0] : NULL;
	}

	// `array_column()` for php < 5.5
	// @SEE: https://github.com/ramsey/array_column/blob/master/src/array_column.php
	// @REF: http://php.net/manual/en/function.array-column.php#118831
	// ALT: `wp_list_pluck()`
	public static function column( $input, $column_key, $index_key = NULL )
	{
		if ( function_exists( 'array_column' ) )
			return array_column( $input, $column_key, $index_key ); // phpcs:ignore

		$arr = array_map( function( $d ) use ( $column_key, $index_key ) {

			if ( ! isset( $d[$column_key] ) )
				return NULL;

			if ( NULL !== $index_key )
				return array( $d[$index_key] => $d[$column_key] );

			return $d[$column_key];

		}, $input );

		if ( NULL !== $index_key ) {

			$tmp = array();

			foreach ( $arr as $ar )
				$tmp[key($ar)] = current( $ar );

			$arr = $tmp;
		}

		return $arr;
	}

	// @REF: http://stackoverflow.com/a/11320508
	public static function find( $needle, &$haystack, $default = NULL )
	{
		$current = array_shift( $needle );

		if ( ! isset( $haystack[$current] ) )
			return $default;

		if ( ! is_array( $haystack[$current] ) )
			return $haystack[$current];

		return self::find( $needle, $haystack[$current], $default );
	}

	// is associative or sequential?
	// @REF: https://stackoverflow.com/a/173479
	public static function isAssoc( $array )
	{
		if ( $array === array() )
			return FALSE;

		return array_keys( $array ) !== range( 0, count( $array ) - 1 );
	}

	// @REF: https://stackoverflow.com/a/4254008
	public static function hasStringKeys( $array )
	{
		return count( array_filter( array_keys( $array ), 'is_string' ) ) > 0;
	}

	// @REF: `wp_array_slice_assoc()`
	public static function sliceKeys( $array, $keys )
	{
		$slice = array();

		foreach ( $keys as $key )
			if ( isset( $array[$key] ) )
				$slice[$key] = $array[$key];

		return $slice;
	}

	// splits a list into sets, grouped by the result of running each value through $callback
	// @SOURCE: `scb_list_group_by()`
	public static function groupBy( $list, $callback )
	{
		$groups = array();

		foreach ( $list as $item ) {
			$key = call_user_func( $callback, $item );

			if ( NULL === $key )
				continue;

			$groups[$key][] = $item;
		}

		return $groups;
	}

	// transform a list of objects into an associative array
	// @SOURCE: `scb_list_fold()`
	public static function listFold( $list, $key, $value )
	{
		$array = array();

		if ( is_array( reset( $list ) ) )

			foreach ( $list as $item )
				$array[$item[$key]] = $item[$value];

		else

			foreach ( $list as $item )
				$array[$item->{$key}] = $item->{$value};

		return $array;
	}
}
