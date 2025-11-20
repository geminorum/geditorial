<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

class FieldDelete implements Field
{
	public function get_title()
	{
		$data = [
			'title' => _x( 'Delete all connections', 'O2O', 'geditorial-admin' ),
		];

		return Mustache::render( 'column-delete-all', $data );
	}

	public function render( $o2o_id, $_ )
	{
		$data = [
			'o2o_id' => $o2o_id,
			'title'  => _x( 'Delete connection', 'O2O', 'geditorial-admin' ),
		];

		return Mustache::render( 'column-delete', $data );
	}
}
