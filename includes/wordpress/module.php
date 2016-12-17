<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialWPModule extends gEditorialBaseCore
{

	protected $base = NULL;
	protected $key  = NULL;
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

	protected function hook()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			$suffix .= '_'.$arg;

		return $this->base.'_'.$this->key.$suffix;
	}

	protected function classs()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			$suffix .= '-'.$arg;

		return $this->base.'-'.$this->key.$suffix;
	}

	protected function hash()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			$suffix .= maybe_serialize( $arg );

		return md5( $this->base.$this->key.$suffix );
	}

	protected function hashwithsalt()
	{
		$suffix = '';

		foreach ( func_get_args() as $arg )
			$suffix .= maybe_serialize( $arg );

		return wp_hash( $this->base.$this->key.$suffix );
	}

	protected function action( $hook, $args = 1, $priority = 10, $method = FALSE )
	{
		add_action( $hook, array( $this, ( $method ? $method : $hook ) ), $priority, $args );
	}

	protected function filter( $hook, $args = 1, $priority = 10, $method = FALSE )
	{
		add_filter( $hook, array( $this, ( $method ? $method : $hook ) ), $priority, $args );
	}

	protected function actions()
	{
		$args = func_get_args();

		if ( count( $args ) < 1 )
			return FALSE;

		$args[0] = $this->hook( $args[0] );

		return call_user_func_array( 'do_action', $args );
	}

	protected function filters()
	{
		$args = func_get_args();

		if ( count( $args ) < 2 )
			return FALSE;

		$args[0] = $this->hook( $args[0] );

		return call_user_func_array( 'apply_filters', $args );
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

	protected function nonce_verify( $nonce = NULL, $context = 'settings', $key = NULL )
	{
		if ( is_null( $nonce ) )
			$nonce = $_REQUEST['_wpnonce'];

		if ( is_null( $key ) )
			$key = $this->key;

		return wp_verify_nonce( $nonce, $this->base.'-'.$key.'-'.$context );
	}

	protected function nonce_field( $context = 'settings', $key = NULL )
	{
		if ( is_null( $key ) )
			$key = $this->key;

		return wp_nonce_field( $this->base.'-'.$key.'-'.$context );
	}

	protected function nonce_check( $context = 'settings', $key = NULL )
	{
		if ( is_null( $key ) )
			$key = $this->key;

		return check_admin_referer( $this->base.'-'.$key.'-'.$context );
	}
}
