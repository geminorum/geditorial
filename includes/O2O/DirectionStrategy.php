<?php namespace geminorum\gEditorial\O2O;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

// `P2P_Direction_Strategy`
interface DirectionStrategy
{

	function get_arrow();

	function choose_direction( $direction );

	function directions_for_admin( $direction, $show_ui );

	function get_directed_class();
}
