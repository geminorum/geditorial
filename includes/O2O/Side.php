<?php namespace geminorum\gEditorial\O2O;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

abstract class Side
{

	protected $item_type;

	abstract function get_object_type();

	abstract function get_title();
	abstract function get_desc();
	abstract function get_labels();

	abstract function can_edit_connections();
	abstract function can_create_item();

	abstract function get_base_qv( $q );
	abstract function translate_qv( $qv );
	abstract function do_query( $args );
	abstract function capture_query( $args );
	abstract function get_list( $query );

	abstract function is_indeterminate( $side );

	final function is_same_type( $side )
	{
		return $this->get_object_type() == $side->get_object_type();
	}

	function item_recognize( $arg )
	{
		$class = __NAMESPACE__.'\\'.$this->item_type;

		if ( is_a( $arg, __NAMESPACE__.'\\Item' ) ) {

			if ( ! is_a( $arg, $class ) )
				return FALSE;

			$arg = $arg->get_object();
		}

		if ( ! $raw_item = $this->recognize( $arg ) )
			return FALSE;

		return new $class( $raw_item );
	}

	abstract protected function recognize( $arg );
}
