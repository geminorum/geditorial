<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Base
{

	public static function req( $key, $default = '' )
	{
		return isset( $_REQUEST[$key] ) ? $_REQUEST[$key] : $default;
	}

	public static function limit( $default = 25, $key = 'limit' )
	{
		return intval( self::req( $key, $default ) );
	}

	public static function paged( $default = 1, $key = 'paged' )
	{
		return intval( self::req( $key, $default ) );
	}

	public static function orderby( $default = 'title', $key = 'orderby' )
	{
		return self::req( $key, $default );
	}

	public static function order( $default = 'desc', $key = 'order' )
	{
		$req = strtoupper( self::req( $key, $default ) );
		return ( 'ASC' === $req || 'DESC' === $req ) ? $req : $default;
	}

	public static function dump( $var, $safe = TRUE, $echo = TRUE )
	{
		$export = var_export( $var, TRUE );
		if ( $safe ) $export = htmlspecialchars( $export );
		$export = '<pre dir="ltr" style="text-align:left;direction:ltr;">'.$export.'</pre>';
		if ( ! $echo ) return $export;
		echo $export;
	}

	public static function kill()
	{
		foreach ( func_get_args() as $arg )
			self::dump( $arg );
		echo self::stat();
		die();
	}

	public static function stat( $format = NULL )
	{
		if ( is_null( $format ) )
			$format = '%d queries in %.3f seconds, using %.2fMB memory.';

		return sprintf( $format,
			@$GLOBALS['wpdb']->num_queries,
			self::timerStop( FALSE, 3 ),
			memory_get_peak_usage() / 1024 / 1024
		);
	}

	// WP core function without number_format_i18n
	public static function timerStop( $echo = FALSE, $precision = 3 )
	{
		global $timestart;
		$total = number_format( ( microtime( TRUE ) - $timestart ), $precision );
		if ( $echo ) echo $total;
		return $total;
	}

	public static function cheatin( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = __( 'Cheatin&#8217; uh?' );

		wp_die( $message, 403 );
	}

	public static function __log_req()
	{
		self::__log( $_REQUEST );
	}

	// INTERNAL
	public static function __log( $log )
	{
		if ( defined( 'WP_DEBUG_LOG' ) && ! WP_DEBUG_LOG )
			return;

		if ( is_array( $log ) || is_object( $log ) )
			error_log( print_r( $log, TRUE ) );
		else
			error_log( $log );
	}

	// INTERNAL: used on anything deprecated
	protected static function __dep( $note = '', $prefix = 'DEP: ', $offset = 1 )
	{
		if ( defined( 'WP_DEBUG_LOG' ) && ! WP_DEBUG_LOG )
			return;

		$trace = debug_backtrace();

		$log = $prefix;

		if ( isset( $trace[$offset]['object'] ) )
			$log .= get_class( $trace[$offset]['object'] ).'::';
		else if ( isset( $trace[$offset]['class'] ) )
			$log .= $trace[$offset]['class'].'::';

		$log .= $trace[$offset]['function'].'()';

		$offset++;

		if ( isset( $trace[$offset]['function'] ) ) {
			$log .= '|FROM: ';
			if ( isset( $trace[$offset]['object'] ) )
				$log .= get_class( $trace[$offset]['object'] ).'::';
			else if ( isset( $trace[$offset]['class'] ) )
				$log .= $trace[$offset]['class'].'::';
			$log .= $trace[$offset]['function'].'()';
		}

		if ( $note )
			$log .= '|'.$note;

		error_log( $log );
	}

	// INTERNAL: used on anything deprecated : only on dev mode
	protected static function __dev_dep( $note = '', $prefix = 'DEP: ', $offset = 2 )
	{
		if ( WordPress::isDev() )
			self::__dep( $note, $prefix, $offset );
	}

	// INTERNAL: used on functions deprecated
	public static function __dev_func( $function, $version, $replacement = NULL )
	{
		if ( is_null( $replacement ) )
			self::__log( sprintf( 'DEP: \'%1$s\' function, since %2$s with no alternative', $function, $version ) );
		else
			self::__log( sprintf( 'DEP: \'%1$s\' function, since %2$s, Use \'%3$s\'', $function, $version, $replacement ) );
	}

	public static function atts( $pairs, $atts )
	{
		$atts = (array) $atts;
		$out  = array();

		foreach ( $pairs as $name => $default ) {
			if ( array_key_exists( $name, $atts ) )
				$out[$name] = $atts[$name];
			else
				$out[$name] = $default;
		}

		return $out;
	}

	/**
	 * recursive argument parsing
	 * @link: https://gist.github.com/boonebgorges/5510970
	 *
	 * Values from $a override those from $b; keys in $b that don't exist
	 * in $a are passed through.
	 *
	 * This is different from array_merge_recursive(), both because of the
	 * order of preference ($a overrides $b) and because of the fact that
	 * array_merge_recursive() combines arrays deep in the tree, rather
	 * than overwriting the b array with the a array.
	*/
	public static function recursiveParseArgs( &$a, $b )
	{
		$a = (array) $a;
		$b = (array) $b;
		$r = $b;

		foreach ( $a as $k => &$v )
			if ( is_array( $v ) && isset( $r[$k] ) )
				$r[$k] = self::recursiveParseArgs( $v, $r[$k] );
			else
				$r[$k] = $v;

		return $r;
	}

	// maps a function to all non-iterable elements of an array or an object
	// this is similar to `array_walk_recursive()` but acts upon objects too
	// @REF: `map_deep()`
	public static function mapDeep( $data, $callback )
	{
		if ( is_array( $data ) )
			foreach ( $data as $index => $item )
				$data[$index] = self::mapDeep( $item, $callback );

		else if ( is_object( $data ) )
			foreach ( get_object_vars( $data ) as $name => $value )
				$data->$name = self::mapDeep( $value, $callback );

		else
			$data = call_user_func( $callback, $data );

		return $data;
	}

	// remove slashes from a string or array of strings
	// @REF: `wp_unslash()`
	// @REF: `stripslashes_deep()`
	public static function unslash( $array )
	{
		return self::mapDeep( $array, function( $value ){
			return is_string( $value ) ? stripslashes( $value ) : $value;
		} );
	}

	// @REF: `wp_validate_boolean()`
	public static function validateBoolean( $var )
	{
		if ( is_bool( $var ) )
			return $var;

		if ( is_string( $var ) && 'false' === strtolower( $var ) )
			return FALSE;

		return (bool) $var;
	}
}
