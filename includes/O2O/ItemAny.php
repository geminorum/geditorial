<?php namespace geminorum\gEditorial\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

// `P2P_Item_Any`
class ItemAny extends Item
{

	public function __construct() {}

	public function get_permalink() {}

	public function get_title() {}

	public function get_object()
	{
		return 'any';
	}

	public function get_id()
	{
		return FALSE;
	}
}
