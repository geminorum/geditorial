<?php namespace geminorum\gEditorial\Services\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class ItemTerm extends Item
{

	public function get_title()
	{
		return sanitize_term_field( 'name', $this->item->name, $this->item->term_id, $this->item->taxonomy, 'display' );
	}

	public function get_permalink()
	{
		return get_term_link( $this->item, $this->item->taxonomy );
	}

	public function get_editlink()
	{
		return get_edit_term_link( $this->item->term_id, $this->item->taxonomy );
	}

	function get_id()
	{
		return $this->item->term_id;
	}
}
