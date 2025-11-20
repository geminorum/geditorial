<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

class FieldGeneric implements Field
{
	protected $key;
	protected $data;

	public function __construct( $key, $data )
	{
		$this->key  = $key;
		$this->data = $data;
	}

	public function get_title()
	{
		return $this->data['title'];
	}

	public function render( $o2o_id, $_ )
	{
		$args         = $this->data;
		$args['name'] = [ 'o2o_meta', $o2o_id, $this->key ];

		if ( 'select' == $args['type'] && ! isset( $args['text'] ) )
			$args['text'] = '';

		return O2O\Forms\API::input_from_meta( $args, $o2o_id, 'o2o' );
	}
}
