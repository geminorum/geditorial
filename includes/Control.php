<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

#[\AllowDynamicProperties]
class Control extends \WP_Customize_Control
{
	const BASE    = 'geditorial';
	const MODULE  = FALSE;
	const CONTROL = FALSE;

	public static function factory()
	{
		return gEditorial();
	}

	protected static function constant( $key, $default = FALSE, $module = NULL )
	{
		return static::factory()->constant( $module ?? static::MODULE, $key, $default );
	}

	protected static function filters( $hook, ...$args )
	{
		return apply_filters( sprintf( '%s_%s_%s',
			static::BASE,
			static::CONTROL, // NOTE: usually the control name also contain the module name
			$hook
		), ...$args );
	}

	protected static function actions( $hook, ...$args )
	{
		return do_action( sprintf( '%s_%s_%s',
			static::BASE,
			static::CONTROL, // NOTE: usually the control name also contain the module name
			$hook
		), ...$args );
	}

	public function __construct( $manager, $id, $args = [] )
	{
		parent::__construct( $manager, $id, $args );
	}
}
