<?php namespace geminorum\gEditorial\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

// `P2P_Indeterminate_Connection_Type`
class IndeterminateConnectionType implements DirectionStrategy
{

	public function get_arrow()
	{
		return '&harr;';
	}

	public function choose_direction( $direction )
	{
		return 'from';
	}

	public function directions_for_admin( $_, $show_ui )
	{
		return array_intersect(
			Utils::expandDirection( $show_ui ),
			Utils::expandDirection( 'any' )
		);
	}

	public function get_directed_class()
	{
		return __NAMESPACE__.'\\IndeterminateDirectedConnectionType';
	}
}
