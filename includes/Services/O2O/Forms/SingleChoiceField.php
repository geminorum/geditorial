<?php namespace geminorum\gEditorial\Services\O2O\Forms;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

// Base class for form fields with single choice.
// OLD: `scbSingleChoiceField`
abstract class SingleChoiceField extends FormField
{
	/**
	 * Validates a value against a field.
	 *
	 * @param mixed $value
	 * @return mixed|null
	 */
	public function validate( $value )
	{
		if ( isset( $this->choices[ $value ] ) )
			return $value;

		return NULL;
	}

	/**
	 * Generate the corresponding HTML for a field.
	 *
	 * @param array $args
	 * @return string
	 */
	protected function _render( $args )
	{
		$args = wp_parse_args( $args, [
			'numeric'  => FALSE, // Use numeric array instead of associative
		] );

		if ( isset( $args['selected'] ) )
			$args['selected'] = (string) $args['selected'];

		else
			$args['selected'] = [ 'foo' ];  // hack to make default blank

		return $this->_render_specific( $args );
	}

	/**
	 * Sets value using a reference.
	 *
	 * @param array $args
	 * @param string $value
	 * @return void
	 */
	protected function _set_value( &$args, $value )
	{
		$args['selected'] = $value;
	}

	/**
	 * Generate the corresponding HTML for a field.
	 *
	 * @param array $args
	 * @return string
	 */
	abstract protected function _render_specific( $args );
}
