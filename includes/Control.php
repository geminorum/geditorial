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

	/**
	 * The Constructor!
	 *
	 * - Supplied `$arguments` override class property defaults.
	 * - If `$arguments['settings']` is not defined, uses the `$id` as the setting ID.
	 *
	 * @param \WP_Customize_Manager $manager
	 * @param string $id
	 * @param array $arguments
	 */
	public function __construct( object $manager, string $id, array $arguments = [] )
	{
		parent::__construct( $manager, $id, $arguments );
	}

	/**
	 * Retrieves the constant value for given module.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @param string $module
	 * @return mixed
	 */
	protected static function constant( string $key, mixed $default = FALSE, ?string $module = NULL ): mixed
	{
		return static::factory()->constant( $module ?? static::MODULE, $key, $default );
	}

	/**
	 * Calls the callbacks that have been added to given filter hook.
	 *
	 * @param string $hook
	 * @param mixed $arguments
	 * @return mixed
	 */
	protected static function filters( string $hook, ...$arguments ): mixed
	{
		return apply_filters( sprintf( '%s_%s_%s',
			static::BASE,
			static::CONTROL, // NOTE: usually the control name also contain the module name
			$hook
		), ...$arguments );
	}

	/**
	 * Calls the callbacks that have been added to given action hook.
	 *
	 * @param string $hook
	 * @param mixed $arguments
	 * @return true
	 */
	protected static function actions( string $hook, ...$arguments ): true
	{
		do_action( sprintf( '%s_%s_%s',
			static::BASE,
			static::CONTROL, // NOTE: usually the control name also contain the module name
			$hook
		), ...$arguments );

		return TRUE;
	}
}
