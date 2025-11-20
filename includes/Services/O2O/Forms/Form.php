<?php namespace geminorum\gEditorial\Services\O2O\Forms;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

// A wrapper for `scbForms`, containing the form-data.
// OLD: `scbForm`
class Form extends Core\Base
{
	protected $data   = [];
	protected $prefix = [];

	/**
	 * Constructor.
	 *
	 * @param array $data
	 * @param string|boolean $prefix (optional)
	 *
	 * @return void
	 */
	public function __construct( $data, $prefix = FALSE )
	{
		if ( is_array( $data ) )
			$this->data = $data;

		if ( $prefix )
			$this->prefix = (array) $prefix;
	}

	/**
	 * Traverses the form.
	 *
	 * @param string $path
	 *
	 * @return object A Forms\Form
	 */
	public function traverse_to( $path )
	{
		$data = API::get_value( $path, $this->data );

		$prefix = array_merge( $this->prefix, (array) $path );

		return new Form( $data, $prefix );
	}

	/**
	 * Generates form field.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function input( $args )
	{
		$value = API::get_value( $args['name'], $this->data );

		if ( ! empty( $this->prefix ) )
			$args['name'] = array_merge( $this->prefix, (array) $args['name'] );

		return API::input_with_value( $args, $value );
	}
}
