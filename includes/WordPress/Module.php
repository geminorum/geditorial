<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class Module extends Core\Base
{

	protected $base = NULL;
	protected $key  = NULL;
	protected $path = NULL;
	protected $blog = NULL;

	public static function module() { return array(); }

	protected function setup( $args = array() ) {}
	protected function init_options( $args = array() ) {}
	protected function default_options() { return array(); }

	public function base() { return $this->base; }

	protected function options_key()
	{
		return $this->base.'_'.$this->key;
	}

	protected static function sanitize_hook( $hook )
	{
		return trim( str_ireplace( [ '-', '.' ], '_', $hook ) );
	}

	protected static function sanitize_base( $hook )
	{
		return trim( str_ireplace( [ '_', '.' ], '-', $hook ) );
	}

	protected function hook()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			$suffix.= '_'.strtolower( self::sanitize_hook( $arg ) );

		return $this->base.'_'.$this->key.$suffix;
	}

	protected function classs()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			$suffix.= '-'.strtolower( self::sanitize_base( $arg ) );

		return $this->base.'-'.self::sanitize_base( $this->key ).$suffix;
	}

	protected function hash()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			$suffix.= maybe_serialize( $arg );

		return md5( $this->base.$this->key.$suffix );
	}

	protected function hashwithsalt()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			$suffix.= maybe_serialize( $arg );

		return wp_hash( $this->base.$this->key.$suffix );
	}

	protected function action( $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $hook.'_'.$suffix : $hook ) ) )
			add_action( $hook, array( $this, $method ), $priority, $args );
	}

	protected function filter( $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $hook.'_'.$suffix : $hook ) ) )
			add_filter( $hook, array( $this, $method ), $priority, $args );
	}

	// USAGE: $this->action_module( 'importer', 'saved', 5 );
	protected function action_module( $module, $hook = 'init', $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $module.'_'.$hook.'_'.$suffix : $module.'_'.$hook ) ) )
			add_action( $this->base.'_'.$module.'_'.$hook, array( $this, $method ), $priority, $args );
	}

	// USAGE: $this->filter_module( 'importer', 'prepare', 4 );
	protected function filter_module( $module, $hook = 'init', $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $module.'_'.$hook.'_'.$suffix : $module.'_'.$hook ) ) )
			add_filter( $this->base.'_'.$module.'_'.$hook, array( $this, $method ), $priority, $args );
	}

	// @REF: https://gist.github.com/markjaquith/b752e3aa93d2421285757ada2a4869b1
	protected function filter_once( $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $hook.'_'.$suffix : $hook ) ) )
			add_filter( $hook, function( $first ) use( $method ) {
				static $ran = FALSE;
				if ( $ran ) return $first;
				$ran = TRUE;
				return call_user_func_array( [ $this, $method ], func_get_args() );
			}, $priority, $args );
	}

	// USAGE: $this->filter_true( 'disable_months_dropdown' );
	protected function filter_true( $hook, $priority = 10 )
	{
		add_filter( $hook, function( $first ){
			return TRUE;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_false( 'disable_months_dropdown' );
	protected function filter_false( $hook, $priority = 10 )
	{
		add_filter( $hook, function( $first ){
			return FALSE;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_append( 'body_class', 'foo' );
	protected function filter_append( $hook, $items, $priority = 10 )
	{
		add_filter( $hook, function( $first ) use( $items ){
			foreach ( (array) $items as $value )
				$first[] = $value;
			return $first;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_set( 'shortcode_atts_gallery', [ 'columns' => 4 ] );
	protected function filter_set( $hook, $items, $priority = 10 )
	{
		add_filter( $hook, function( $first ) use( $items ){
			foreach ( $items as $key => $value )
				$first[$key] = $value;
			return $first;
		}, $priority, 1 );
	}

	protected function actions()
	{
		$args = func_get_args();

		if ( count( $args ) < 1 )
			return FALSE;

		$args[0] = $this->hook( $args[0] );

		call_user_func_array( 'do_action', $args );

		return has_action( $args[0] );
	}

	protected function filters()
	{
		$args = func_get_args();

		if ( count( $args ) < 2 )
			return FALSE;

		$args[0] = $this->hook( $args[0] );

		return call_user_func_array( 'apply_filters', $args );
	}

	// USAGE: add_filter( 'body_class', self::__array_append( 'foo' ) );
	protected static function __array_append( $item )
	{
		return function( $array ) use ( $item ) {
			$array[] = $item;
			return $array;
		};
	}

	// USAGE: add_filter( 'shortcode_atts_gallery', self::__array_set( 'columns', 4 ) );
	protected static function __array_set( $key, $value )
	{
		return function( $array ) use ( $key, $value ) {
			$array[$key] = $value;
			return $array;
		};
	}

	public function cuc( $context = 'settings', $fallback = '' )
	{
		if ( ! empty( $this->caps[$context] ) )
			return current_user_can( $this->caps[$context] );

		if ( ! empty( $this->caps['default'] ) )
			return current_user_can( $this->caps['default'] );

		else if ( $fallback )
			return current_user_can( $fallback );

		return FALSE;
	}

	protected function nonce_create( $context = 'settings', $key = NULL )
	{
		if ( is_null( $key ) )
			$key = $this->key;

		return wp_create_nonce( $this->base.'-'.$key.'-'.$context );
	}

	protected function nonce_verify( $context = 'settings', $nonce = NULL, $key = NULL )
	{
		if ( is_null( $key ) )
			$key = $this->key;

		if ( is_null( $nonce ) )
			$nonce = @$_REQUEST['_'.$this->base.'-'.$key.'-'.$context]; // OLD: $_REQUEST['_wpnonce']

		return wp_verify_nonce( $nonce, $this->base.'-'.$key.'-'.$context );
	}

	protected function nonce_field( $context = 'settings', $key = NULL, $name = NULL )
	{
		if ( is_null( $key ) )
			$key = $this->key;

		if ( is_null( $name ) )
			$name = '_'.$this->base.'-'.$key.'-'.$context; // OLD: '_wpnonce'

		return wp_nonce_field( $this->base.'-'.$key.'-'.$context, $name, FALSE, TRUE );
	}

	protected function nonce_check( $context = 'settings', $key = NULL, $name = NULL )
	{
		if ( is_null( $key ) )
			$key = $this->key;

		if ( is_null( $name ) )
			$name = '_'.$this->base.'-'.$key.'-'.$context; // OLD: '_wpnonce'

		return check_admin_referer( $this->base.'-'.$key.'-'.$context, $name );
	}

	protected function wrap( $html, $class = '', $block = TRUE, $id = FALSE, $hide = FALSE )
	{
		return $block
			? '<div class="-wrap '.$this->base.'-wrap -'.$this->key.' '.$class.'"'.( $id ? ' id="'.$id.'"' : '' ).( $hide ? ' style="display:none"' : '' ).'>'.$html.'</div>'
			: '<span class="-wrap '.$this->base.'-wrap -'.$this->key.' '.$class.'"'.( $id ? ' id="'.$id.'"' : '' ).( $hide ? ' style="display:none"' : '' ).'>'.$html.'</span>';
	}

	protected function wrap_open( $class = '', $block = TRUE, $id = FALSE, $hide = FALSE )
	{
		return $block
			? '<div class="-wrap '.$this->base.'-wrap -'.$this->key.' '.$class.'"'.( $id ? ' id="'.$id.'"' : '' ).( $hide ? ' style="display:none"' : '' ).'>'
			: '<span class="-wrap '.$this->base.'-wrap -'.$this->key.' '.$class.'"'.( $id ? ' id="'.$id.'"' : '' ).( $hide ? ' style="display:none"' : '' ).'>';
	}

	protected function wrap_open_buttons( $class = '', $block = TRUE, $id = FALSE, $hide = FALSE )
	{
		return $block
			? '<p class="submit '.$this->base.'-wrap -wrap-buttons -'.$this->key.' '.$class.'"'.( $id ? ' id="'.$id.'"' : '' ).( $hide ? ' style="display:none"' : '' ).'>'
			: '<span class="submit '.$this->base.'-wrap -wrap-buttons -'.$this->key.' '.$class.'"'.( $id ? ' id="'.$id.'"' : '' ).( $hide ? ' style="display:none"' : '' ).'>';
	}
}
