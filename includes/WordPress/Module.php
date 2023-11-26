<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class Module extends Core\Base
{

	protected $base = NULL;
	protected $key  = NULL;
	protected $path = NULL;
	protected $site = NULL;
	protected $caps = [];

	public static function module() { return []; }

	protected function setup( $args = [] ) {}
	protected function init_options( $args = [] ) {}
	protected function default_options() { return []; }

	public function base() { return $this->base; }

	protected function options_key()
	{
		return $this->base.'_'.$this->key;
	}

	protected static function sanitize_hook( $hook )
	{
		return Core\Text::sanitizeHook( $hook );
	}

	protected static function sanitize_base( $base )
	{
		return Core\Text::sanitizeBase( $base );
	}

	protected function dotted()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '.'.strtolower( self::sanitize_base( $arg ) );

		return $this->key.$suffix;
	}

	protected function hook()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '_'.strtolower( self::sanitize_hook( $arg ) );

		return $this->base.'_'.$this->key.$suffix;
	}

	// NOTE: same as `hook()` without the `$key`
	protected function hook_base()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '_'.strtolower( self::sanitize_hook( $arg ) );

		return $this->base.$suffix;
	}

	protected function classs()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$suffix.= '-'.strtolower( self::sanitize_base( $arg ) );

		return $this->base.'-'.self::sanitize_base( $this->key ).$suffix;
	}

	protected function hash()
	{
		$string = '';

		foreach ( func_get_args() as $arg )
			$string.= maybe_serialize( $arg );

		return md5( $this->base.$this->key.$string );
	}

	protected function hashwithsalt()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			$suffix.= maybe_serialize( $arg );

		return wp_hash( $this->base.$this->key.$suffix );
	}

	protected function stripprefix( $string, $key = NULL, $template = '_%s_' )
	{
		return Core\Text::stripPrefix( $string, sprintf( $template, is_null( $key ) ? $this->key : $key ) );
	}

	protected function action( $hooks, $args = 1, $priority = 10, $suffix = FALSE, $base = FALSE )
	{
		$hooks = (array) $hooks;

		if ( $method = self::sanitize_hook( ( $suffix ? $hooks[0].'_'.$suffix : $hooks[0] ) ) )
			foreach ( $hooks as $hook )
				add_action( ( $base ? $base.'_'.$hook : $hook ), [ $this, $method ], $priority, $args );
	}

	protected function filter( $hooks, $args = 1, $priority = 10, $suffix = FALSE, $base = FALSE )
	{
		$hooks = (array) $hooks;

		if ( $method = self::sanitize_hook( ( $suffix ? $hooks[0].'_'.$suffix : $hooks[0] ) ) )
			foreach ( $hooks as $hook )
				add_filter( ( $base ? $base.'_'.$hook : $hook ), [ $this, $method ], $priority, $args );
	}

	/**
	 * hooks an action for an internal module
	 *
	 * @example `$this->action_module( 'importer', 'saved', 8 );`
	 *
	 * @param string $module
	 * @param string $hook
	 * @param integer $args
	 * @param integer $priority
	 * @param string $suffix
	 * @return void
	 */
	protected function action_module( $module, $hook, $args = 1, $priority = 10, $suffix = '' )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $module.'_'.$hook.'_'.$suffix : $module.'_'.$hook ) ) )
			add_action( $this->hook_base( $module, $hook ), [ $this, $method ], $priority, $args );
	}

	/**
	 * hooks a filter for an internal module
	 *
	 * @example `$this->filter_module( 'importer', 'prepare', 7 );`
	 *
	 * @param string $module
	 * @param string $hook
	 * @param integer $args
	 * @param integer $priority
	 * @param string $suffix
	 * @return void
	 */
	protected function filter_module( $module, $hook, $args = 1, $priority = 10, $suffix = '' )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $module.'_'.$hook.'_'.$suffix : $module.'_'.$hook ) ) )
			add_filter( $this->hook_base( $module, $hook ), [ $this, $method ], $priority, $args );
	}

	// USAGE: $this->action_self( 'saved', 8 );
	protected function action_self( $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $hook.'_'.$suffix : $hook ) ) )
			add_action( $this->hook_base( $this->key, $hook ), [ $this, $method ], $priority, $args );
	}

	// USAGE: $this->filter_self( 'prepare', 7 );
	protected function filter_self( $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $hook.'_'.$suffix : $hook ) ) )
			add_filter( $this->hook_base( $this->key, $hook ), [ $this, $method ], $priority, $args );
	}

	// @REF: https://gist.github.com/markjaquith/b752e3aa93d2421285757ada2a4869b1
	protected function filter_once( $hook, $args = 1, $priority = 10, $suffix = FALSE )
	{
		if ( $method = self::sanitize_hook( ( $suffix ? $hook.'_'.$suffix : $hook ) ) )
			add_filter( $hook, function() use ( $method ) {
				static $ran = FALSE;

				$params = func_get_args();

				if ( $ran )
					return $params[0];

				$ran = TRUE;

				return call_user_func_array( [ $this, $method ], $params );
			}, $priority, $args );
	}

	// USAGE: $this->filter_true( 'disable_months_dropdown' );
	protected function filter_true( $hook, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) {
			return TRUE;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_false( 'disable_months_dropdown' );
	protected function filter_false( $hook, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) {
			return FALSE;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_true_module( 'meta', 'mainbox_callback' );
	protected function filter_true_module( $module, $hook, $priority = 10 )
	{
		add_filter( $this->hook_base( $module, $hook ), static function( $first ) {
			return TRUE;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_false_module( 'meta', 'mainbox_callback' );
	protected function filter_false_module( $module, $hook, $priority = 10 )
	{
		add_filter( $this->hook_base( $module, $hook ), static function( $first ) {
			return FALSE;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_zero( 'option_blog_public' );
	protected function filter_zero( $hook, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) {
			return 0;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_empty_string( 'option_blog_public' );
	protected function filter_empty_string( $hook, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) {
			return '';
		}, $priority, 1 );
	}

	// USAGE: $this->filter_empty_array( 'option_blog_public' );
	protected function filter_empty_array( $hook, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) {
			return [];
		}, $priority, 1 );
	}

	// USAGE: $this->filter_append( 'body_class', 'foo' );
	protected function filter_append( $hook, $items, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) use ( $items ) {
			foreach ( (array) $items as $value )
				$first[] = $value;
			return $first;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_set( 'shortcode_atts_gallery', [ 'columns' => 4 ] );
	protected function filter_set( $hook, $items, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) use ( $items ) {
			foreach ( $items as $key => $value )
				$first[$key] = $value;
			return $first;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_unset( 'shortcode_atts_gallery', [ 'columns' ] );
	protected function filter_unset( $hook, $items, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) use ( $items ) {
			foreach ( (array) $items as $key )
				unset( $first[$key] );
			return $first;
		}, $priority, 1 );
	}

	// USAGE: $this->filter_string( 'parent_file', 'options-general.php' );
	protected function filter_string( $hook, $string, $priority = 10 )
	{
		add_filter( $hook, static function( $first ) use ( $string ) {
			return $string;
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

	// `has_filter()` / `has_action()`
	protected function hooked( $hook, $suffix = FALSE, $function_to_check = FALSE )
	{
		if ( $tag = $this->hook( $hook, $suffix ) )
			return has_filter( $tag, $function_to_check );

		return FALSE;
	}

	public function _return_string_yes() { return 'yes'; }
	public function _return_string_no()  { return 'no'; }

	// USAGE: add_filter( 'body_class', self::_array_append( 'foo' ) );
	public static function _array_append( $item )
	{
		return function( $array ) use ( $item ) {
			$array[] = $item;
			return $array;
		};
	}

	// USAGE: add_filter( 'shortcode_atts_gallery', self::_array_set( 'columns', 4 ) );
	public static function _array_set( $key, $value )
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
			$nonce = self::req( '_'.$this->base.'-'.$key.'-'.$context, NULL ); // OLD: $_REQUEST['_wpnonce']

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

	protected function get_sub_limit_option( $sub = NULL, $default = 25, $key = 'limit', $option = 'per_page' )
	{
		if ( is_null( $sub ) )
			$sub = $this->key;

		$per_page = (int) get_user_option( $this->hook_base( $sub, $option ) );

		if ( empty( $per_page ) || $per_page < 1 )
			$per_page = $default;

		return (int) self::req( $key, $per_page );
	}

	// NOTE: `add_screen_option()` only accept 2 methods: `per_page` and `layout_columns`
	protected function add_sub_screen_option( $sub = NULL, $option = 'per_page', $default = NULL, $label = NULL )
	{
		if ( is_null( $sub ) )
			$sub = $this->key;

		if ( is_null( $default ) )
			$default = 'per_page' == $option ? 25 : 2;

		add_screen_option( $option, [
			'default' => $default,
			'option'  => $this->hook_base( $sub, $option ),
			'label'   => $label,
		] );
	}

	protected function wrap( $html, $class = '', $block = TRUE, $id = FALSE, $hide = FALSE )
	{
		return $block
			? '<div class="'.Core\HTML::prepClass( '-wrap', $this->base.'-wrap', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' )
				.'>'.$html.'</div>'

			: '<span class="'.Core\HTML::prepClass( '-wrap', $this->base.'-wrap', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' )
				.'>'.$html.'</span>';
	}

	protected function wrap_open( $class = '', $block = TRUE, $id = FALSE, $hide = FALSE )
	{
		return $block
			? '<div class="'.Core\HTML::prepClass( '-wrap', $this->base.'-wrap', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' ).'>'

			: '<span class="'.Core\HTML::prepClass( '-wrap', $this->base.'-wrap', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' ).'>';
	}

	protected function wrap_open_buttons( $class = '', $block = TRUE, $id = FALSE, $hide = FALSE )
	{
		return $block
			? '<p class="'.Core\HTML::prepClass( 'submit', $this->base.'-wrap', '-wrap-buttons', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' ).'>'

			: '<span class="'.Core\HTML::prepClass( 'submit', $this->base.'-wrap', '-wrap-buttons', '-'.$this->key, $class ).'"'
				.( $id ? ' id="'.$id.'"' : '' )
				.( $hide ? ' style="display:none"' : '' ).'>';
	}

	protected function wrap_open_row( $name = '', $extra = '', $id = FALSE, $hide = FALSE, $tag = 'li' )
	{
		return '<'.Core\HTML::sanitizeTag( $tag ).' class="'.Core\HTML::prepClass(
			'-row',
			'-wrap-row',
			'-'.$this->key,
			$name ? ( '-'.$name ) : '',
			$extra
		).'"'.( $id ? ' id="'.$id.'"' : '' )
		.( $hide ? ' style="display:none"' : '' ).'>';
	}
}
