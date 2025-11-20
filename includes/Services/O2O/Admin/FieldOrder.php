<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

class FieldOrder implements Field
{
	protected $sort_key;

	public function __construct( $sort_key )
	{
		$this->sort_key = $sort_key;
	}

	public function get_title()
	{
		return '';
	}

	public function render( $o2o_id, $_ )
	{
		return Core\HTML::tag( 'input', [
			'type'  => 'hidden',
			'name'  => "o2o_order[$this->sort_key][]",
			'value' => $o2o_id,
		] );
	}
}
