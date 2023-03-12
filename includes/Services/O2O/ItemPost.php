<?php namespace geminorum\gEditorial\Services\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

// `P2P_Item_Post`
class ItemPost extends Item
{

	public function get_title()
	{
		return get_the_title( $this->item );
	}

	public function get_permalink()
	{
		return get_permalink( $this->item );
	}

	public function get_editlink()
	{
		return get_edit_post_link( $this->item );
	}
}
