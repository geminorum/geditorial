<?php namespace geminorum\gEditorial\Services\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

// `P2P_Reciprocal_Connection_Type`
class ReciprocalConnectionType extends IndeterminateConnectionType
{

	public function choose_direction( $direction )
	{
		return 'any';
	}

	public function directions_for_admin( $direction, $show_ui )
	{
		if ( $show_ui )
			$directions = [ 'any' ];

		else
			$directions = [];

		return $directions;
	}
}
