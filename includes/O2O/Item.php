<?php namespace geminorum\gEditorial\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

abstract class Item
{
	// A uniform wrapper for various types of WP objects, i.e. posts or users.

	protected $item;

	function __construct( $item )
	{
		$this->item = $item;
	}

	function __isset( $key )
	{
		return isset( $this->item->$key );
	}

	function __get( $key )
	{
		return $this->item->$key;
	}

	function __set( $key, $value )
	{
		$this->item->$key = $value;
	}

	function get_object()
	{
		return $this->item;
	}

	function get_id()
	{
		return $this->item->ID;
	}

	abstract function get_permalink();
	abstract function get_title();
}
