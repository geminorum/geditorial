<?php namespace geminorum\gEditorial\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

// `P2P_Determinate_Connection_Type`
class DeterminateConnectionType implements DirectionStrategy
{

	public function get_arrow()
	{
		return '&rarr;';
	}

	public function choose_direction( $direction )
	{
		return $direction;
	}

	public function directions_for_admin( $direction, $show_ui )
	{
		return array_intersect(
			Utils::expandDirection( $show_ui ),
			Utils::expandDirection( $direction )
		);
	}

	public function get_directed_class()
	{
		return __NAMESPACE__.'\\DirectedConnectionType';
	}
}
