<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

class FieldTitleAttachment extends FieldTitle
{
	public function get_data( $item )
	{
		return [ 'title-attr' => $item->get_object()->post_title ];
	}
}
