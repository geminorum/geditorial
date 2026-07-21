<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait CoreHooks
{

	/**
	 * Filters a module hook if second parameter is equal to value of given constant.
	 *
	 * @param string|null $module
	 * @param string $hook
	 * @param mixed $override
	 * @param string $target
	 * @param int|null $priority
	 * @return true
	 */
	protected function filter_module_i2c(
		?string $module,
		string $hook,
		mixed $override,
		string $target,
		?int $priority = NULL,
	): true {

		return $this->filter_if_2_set_1(
			$this->hook_base( $module ?? $this->key, $hook ),
			$override,
			$this->constant( $target, $target ),
			$priority ?? 10,
		);
	}
}
