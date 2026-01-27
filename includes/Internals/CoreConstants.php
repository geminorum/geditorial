<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait CoreConstants
{
	public function constant( $key, $default = FALSE )
	{
		if ( ! $key || ! is_string( $key ) )
			return $default;

		if ( isset( $this->constants[$key] ) )
			return $this->constants[$key];

		if ( 'post_cpt' === $key || 'post_posttype' === $key )
			return 'post';

		if ( 'page_cpt' === $key || 'page_posttype' === $key )
			return 'page';

		return $default;
	}

	public function constants( $keys, $pre = [] )
	{
		foreach ( (array) $keys as $key )
			if ( $constant = $this->constant( $key ) )
				$pre[] = $constant;

		return Core\Arraay::prepString( $pre );
	}

	public function constant_plural( $key, $default = FALSE )
	{
		if ( ! $key )
			return $default;

		if ( ! $singular = $this->constant( $key ) )
			return $default;

		if ( is_array( $singular ) )
			return $singular; // already defined

		if ( ! $plural = $this->constant( sprintf( '%s_plural', $key ) ) )
			return [ $singular, Core\L10n::pluralize( $singular ) ];

		return [ $singular, $plural ];
	}

	public function constant_in( $constant, $array )
	{
		if ( ! $constant )
			return FALSE;

		if ( ! $key = $this->constant( $constant ) )
			return FALSE;

		return in_array( $key, $array, TRUE );
	}

	protected function _get_global_constants()
	{
		$list = [];

		foreach ( $this->get_global_constants() as $key => $value )
			$list[$key] = $this->get_setting_fallback( sprintf( '%s_constant', $key ), $value, $value );

		return $list;
	}
}
