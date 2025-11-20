<?php namespace geminorum\gEditorial\Services\O2O\Forms;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

// Interface for form fields.
// OLD: `scbFormField_I`
interface FormFieldInterface {

	/**
	 * Generate the corresponding HTML for a field.
	 *
	 * @param mixed $value (optional) The value to use.
	 *
	 * @return string
	 */
	function render( $value = NULL );

	/**
	 * Validates a value against a field.
	 *
	 * @param mixed $value The value to check.
	 *
	 * @return mixed null if the validation failed, sanitized value otherwise.
	 */
	function validate( $value );
}
