<?php namespace geminorum\gEditorial\Services\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

// `P2P_Connection_Type_Factory`
class ConnectionTypeFactory extends Core\Base
{

	private static $instances = [];

	public static function register( $atts )
	{
		$args = self::atts( [
			'name'                  => FALSE,
			'from'                  => 'post',
			'to'                    => 'post',
			'from_query_vars'       => [],
			'to_query_vars'         => [],
			'fields'                => [],
			'data'                  => [],
			'cardinality'           => 'many-to-many',
			'duplicate_connections' => FALSE,
			'self_connections'      => FALSE,
			'sortable'              => FALSE,
			'title'                 => [],
			'from_labels'           => '',
			'to_labels'             => '',
			'reciprocal'            => FALSE,
		], $atts );

		if ( strlen( $args['name'] ) > 44 ) {
			trigger_error( sprintf( "Connection name '%s' is longer than 44 characters.", $args['name'] ), E_USER_WARNING );
			return FALSE;
		}

		$sides = [];

		foreach ( [ 'from', 'to' ] as $direction )
			$sides[$direction] = self::create_side( $args, $direction );

		if ( ! $args['name'] ) {
			trigger_error( "Connection types without a 'name' parameter are deprecated.", E_USER_WARNING );
			$args['name'] = self::generate_name( $sides, $args );
		}

		$args = apply_filters( 'o2o_connection_type_args', $args, $sides );

		$ctype = new ConnectionType( $args, $sides );
		$ctype->strategy = self::get_direction_strategy( $sides, Utils::pluck( $args, 'reciprocal' ) );

		self::$instances[$ctype->name] = $ctype;

		return $ctype;
	}

	private static function create_side( &$args, $direction )
	{
		$object = Utils::pluck( $args, $direction );

		if ( in_array( $object, [
			'user',
			'attachment',
			// 'term', // FIXME: NOT SUPPORTED YET!
			// 'comment', // FIXME: NOT SUPPORTED YET!
			] ) )
			$object_type = $object;

		else
			$object_type = 'post';

		$query_vars = Utils::pluck( $args, $direction.'_query_vars' );

		if ( 'post' == $object_type )
			$query_vars['post_type'] = (array) $object;

		$class = __NAMESPACE__.'\\Side'.ucfirst( $object_type );

		return new $class( $query_vars );
	}

	private static function generate_name( $sides, $args )
	{
		$vals = [
			$sides['from']->get_object_type(),
			$sides['to']->get_object_type(),
			$sides['from']->query_vars,
			$sides['to']->query_vars,
			$args['data']
		];

		return md5( serialize( $vals ) );
	}

	private static function get_direction_strategy( $sides, $reciprocal )
	{
		if ( $sides['from']->is_same_type( $sides['to'] ) &&
			$sides['from']->is_indeterminate( $sides['to'] ) ) {

			if ( $reciprocal )
				$class = __NAMESPACE__.'\\ReciprocalConnectionType';

			else
				$class = __NAMESPACE__.'\\IndeterminateConnectionType';

		} else {

			$class = __NAMESPACE__.'\\DeterminateConnectionType';
		}

		return new $class;
	}

	public static function get_all_instances()
	{
		return self::$instances;
	}

	public static function get_instance( $hash )
	{
		if ( isset( self::$instances[$hash] ) )
			return self::$instances[$hash];

		return FALSE;
	}
}
