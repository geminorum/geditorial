<?php namespace geminorum\gEditorial\Services\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class ItemComment extends Item
{

	public function get_title()
	{
		return $this->item->comment_author;
	}

	public function get_permalink()
	{
		return FALSE; // TODO
	}

	public function get_editlink()
	{
		return FALSE; // TODO
	}

	function get_id()
	{
		return $this->item->comment_ID;
	}
}
