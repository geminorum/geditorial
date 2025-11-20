<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

abstract class FieldTitle implements Field
{
	protected $title;

	public function __construct( $title = '' )
	{
		$this->title = $title;
	}

	public function get_title()
	{
		return $this->title;
	}

	public function render( $o2o_id, $item )
	{
		$data = array_merge( $this->get_data( $item ), [
			'title' => $item->title,
			'url'   => $item->get_editlink(),
		] );

		return Mustache::render( 'column-title', $data );
	}

	abstract function get_data( $item );
}
