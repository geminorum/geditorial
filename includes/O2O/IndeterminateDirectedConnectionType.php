<?php namespace geminorum\gEditorial\O2O;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

// `P2P_Indeterminate_Directed_Connection_Type`
class IndeterminateDirectedConnectionType extends DirectedConnectionType
{

	protected function recognize( $arg, $unused = NULL )
	{
		foreach ( [ 'current', 'opposite' ] as $side ) {

			$item = $this->get( $side, 'side' )->item_recognize( $arg );

			if ( $item )
				return $item;
		}

		return FALSE;
	}

	public function get_final_qv( $q, $unused = NULL )
	{
		$side = $this->get( 'current', 'side' );

		// the sides are of the same type, so just use one for translating
		$q = $side->translate_qv( $q );

		$args = $side->get_base_qv( $q );

		$other_qv = $this->get( 'opposite', 'side' )->get_base_qv( $q );

		// need to be inclusive
		if ( isset( $other_qv['post_type'] ) ) {
			$args['post_type'] = array_unique( array_merge(
				(array) $args['post_type'],
				(array) $other_qv['post_type']
			) );
		}

		return $args;
	}

	protected function get_non_connectable( $item, $extra_qv )
	{
		$to_exclude = parent::get_non_connectable( $item, $extra_qv );

		if ( ! $this->self_connections )
			$to_exclude[] = $item->get_id();

		return $to_exclude;
	}
}
