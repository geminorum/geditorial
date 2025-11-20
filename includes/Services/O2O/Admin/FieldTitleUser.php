<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

class FieldTitleUser extends FieldTitle
{
	public function get_data( $user )
	{
		return [ 'title-attr' => ''];
	}
}
