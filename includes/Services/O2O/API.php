<?php namespace geminorum\gEditorial\Services\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress\Database;

class API extends Core\Base
{

	// @REF: https://github.com/scribu/wp-posts-to-posts/wiki/p2p_register_connection_type
	// @SOURCE: `p2p_register_connection_type()`
	public static function registerConnectionType( $args )
	{
		if ( ! did_action( 'init' ) )
			trigger_error( "Connection types should not be registered before the 'init' hook." );

		$argv = func_get_args();

		if ( count( $argv ) > 1 ) {

			$args = [];

			foreach ( [ 'from', 'to', 'reciprocal' ] as $i => $key )
				if ( isset( $argv[$i] ) )
					$args[$key] = $argv[$i];

		} else {

			$args = $argv[0];
		}

		if ( isset( $args['id'] ) )
			$args['name'] = Utils::pluck( $args, 'id' );

		if ( isset( $args['prevent_duplicates'] ) )
			$args['duplicate_connections'] = ! $args['prevent_duplicates'];

		if ( isset( $args['show_ui'] ) ) {

			$args['admin_box'] = [ 'show' => Utils::pluck( $args, 'show_ui' ) ];

			if ( isset( $args['context'] ) )
				$args['admin_box']['context'] = Utils::pluck( $args, 'context' );
		}

		if ( ! isset( $args['admin_box'] ) )
			$args['admin_box'] = 'any';

		$ctype = ConnectionTypeFactory::register( $args );

		do_action( 'o2o_registered_connection_type', $ctype, $args );

		return $ctype;
	}

	// @SOURCE: `p2p_type()`
	public static function type( $type )
	{
		return ConnectionTypeFactory::get_instance( $type );
	}

	// @SOURCE: `p2p_connection_exists()`
	public static function connectionExists( $o2o_type, $args = [] )
	{
		$args['fields'] = 'count';

		return (bool) self::getConnections( $o2o_type, $args );
	}

	// Retrieve connections
	// - 'direction': can be 'from', 'to' or 'any'
	// - 'from': object id. The first end of the connection. (optional)
	// - 'to': object id. The second end of the connection. (optional)
	// - 'fields': which field of the connection to return. Can be:
	// 'all', 'object_id', 'o2o_from', 'o2o_to', 'o2o_id' or 'count'
	// @SOURCE: `p2p_get_connections()`
	public static function getConnections( $o2o_type, $args = [] )
	{
		$args = self::args( $args, [
			'direction' => 'from',
			'from'      => 'any',
			'to'        => 'any',
			'fields'    => 'all',
		] );

		$result = [];

		foreach ( Utils::expandDirection( $args['direction'] ) as $direction ) {

			$dirs = [ $args['from'], $args['to'] ];

			if ( 'to' == $direction )
				$dirs = array_reverse( $dirs );

			if ( 'object_id' == $args['fields'] )
				$fields = ( 'to' == $direction ) ? 'o2o_from' : 'o2o_to';

			else
				$fields = $args['fields'];

			$result = array_merge( $result, self::_getConnections( $o2o_type, [
				'from'   => $dirs[0],
				'to'     => $dirs[1],
				'fields' => $fields
			] ) );
		}

		if ( 'count' == $args['fields'] )
			return array_sum( $result );

		return $result;
	}

	// @internal
	// `_p2p_get_connections()`
	private static function _getConnections( $o2o_type, $args = [] )
	{
		global $wpdb;

		$where = $wpdb->prepare( 'WHERE o2o_type = %s', $o2o_type );

		foreach ( [ 'from', 'to' ] as $key ) {

			if ( 'any' == $args[$key] )
				continue;

			if ( empty( $args[$key] ) )
				return [];

			$value = Database::array2SQL( Utils::normalize( $args[ $key ] ) );

			$where.= " AND o2o_$key IN ($value)";
		}

		switch ( $args['fields'] ) {

			case 'o2o_id':
			case 'o2o_from':
			case 'o2o_to':

				$sql_field = $args['fields'];

			break;
			case 'count':

				$sql_field = 'COUNT(*)';

			break;
			default:

				$sql_field = '*';
		}

		$query = "SELECT $sql_field FROM {$wpdb->o2o} $where";

		if ( '*' == $sql_field )
			return $wpdb->get_results( $query );

		else
			return $wpdb->get_col( $query );
	}

	// `p2p_get_connection()`
	public static function getConnection( $o2o_id )
	{
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->o2o} WHERE o2o_id = %d", $o2o_id ) );
	}

	// `p2p_create_connection()`
	public static function createConnection( $o2o_type, $args )
	{
		global $wpdb;

		$args = self::args( $args, [
			'direction' => 'from',
			'from'      => FALSE,
			'to'        => FALSE,
			'meta'      => [],
		] );

		list( $from ) = Utils::normalize( $args['from'] );
		list( $to )   = Utils::normalize( $args['to'] );

		if ( ! $from || ! $to )
			return FALSE;

		$dirs = [ $from, $to ];

		if ( 'to' == $args['direction'] )
			$dirs = array_reverse( $dirs );

		$wpdb->insert( $wpdb->o2o, [
			'o2o_type' => $o2o_type,
			'o2o_from' => $dirs[0],
			'o2o_to'   => $dirs[1]
		] );

		$o2o_id = $wpdb->insert_id;

		foreach ( $args['meta'] as $key => $value )
			self::addMeta( $o2o_id, $key, $value );

		do_action( 'o2o_created_connection', $o2o_id );

		return $o2o_id;
	}

	// `p2p_delete_connections()`
	public static function deleteConnections( $o2o_type, $args = [] )
	{
		$args['fields'] = 'o2o_id';

		return self::deleteConnection( self::getConnections( $o2o_type, $args ) );
	}

	// `p2p_delete_connection()`
	public static function deleteConnection( $o2o_id )
	{
		global $wpdb;

		if ( empty( $o2o_id ) )
			return 0;

		$o2o_ids = array_map( 'absint', (array) $o2o_id );

		do_action( 'o2o_delete_connections', $o2o_ids );

		$where = "WHERE o2o_id IN (".implode( ',', $o2o_ids ).")";

		$count = $wpdb->query( "DELETE FROM {$wpdb->o2o} $where" );
		$wpdb->query( "DELETE FROM {$wpdb->o2ometa} $where" );

		return $count;
	}

	public static function convertConnection( $o2o_old_type, $o2o_new_type )
	{
		global $wpdb;

		if ( ! self::type( $o2o_new_type ) )
			return FALSE;

		return $wpdb->update( $wpdb->o2o,
			[ 'o2o_type' => $o2o_new_type ],
			[ 'o2o_type' => $o2o_old_type ]
		);
	}

	public static function getConnectionCounts()
	{
		global $wpdb;

		$counts = $wpdb->get_results( "
			SELECT o2o_type, COUNT(*) as count
			FROM {$wpdb->o2o}
			GROUP BY o2o_type
		" );

		$counts = Core\Arraay::listFold( $counts, 'o2o_type', 'count' );

		foreach ( ConnectionTypeFactory::get_all_instances() as $o2o_type => $ctype )
			if ( ! isset( $counts[$o2o_type] ) )
				$counts[$o2o_type] = 0;

		ksort( $counts );

		return $counts;
	}

	// `p2p_get_meta`
	public static function getMeta( $o2o_id, $key = '', $single = FALSE )
	{
		return get_metadata( 'o2o', $o2o_id, $key, $single );
	}

	// `p2p_update_meta`
	public static function updateMeta( $o2o_id, $key, $value, $prev_value = '' )
	{
		return update_metadata( 'o2o', $o2o_id, $key, $value, $prev_value );
	}

	// `p2p_add_meta`
	public static function addMeta( $o2o_id, $key, $value, $unique = FALSE )
	{
		return add_metadata( 'o2o', $o2o_id, $key, $value, $unique );
	}

	// `p2p_delete_meta`
	public static function deleteMeta( $o2o_id, $key, $value = '' )
	{
		return delete_metadata( 'o2o', $o2o_id, $key, $value );
	}

	// `p2p_list_posts()`
	public static function listPosts( $posts, $args = [] )
	{
		if ( is_a( $posts, __NAMESPACE__.'\\ListItems' ) ) {

			$list = $posts;

		} else {

			if ( is_a( $posts, 'WP_Query' ) )
				$posts = $posts->posts;

			$list = new ListItems( $posts, __NAMESPACE__.'\\ItemPost' );
		}

		return ListRenderer::render( $list, $args );
	}

	// given a list of objects and another list of connected items,
	// distribute each connected item to it's respective counterpart
	// `p2p_distribute_connected()`
	public static function distributeConnected( $items, $connected, $prop_name )
	{
		$indexed_list = [];

		foreach ( $items as $item ) {
			$item->{$prop_name} = [];
			$indexed_list[$item->ID] = $item;
		}

		$groups = Core\Arraay::groupBy( $connected, [ __NAMESPACE__.'\\Utils', 'getOtherID' ] );

		foreach ( $groups as $outer_item_id => $connected_items )
			$indexed_list[$outer_item_id]->{$prop_name} = $connected_items;
	}

	public static function metaboxTitle( $type, $direction )
	{
		$labels = $type->get_field( 'labels', $direction );

		/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
		$template = _x( 'Connected %1$s', 'O2O: MetaBox Title', 'geditorial' );

		return vsprintf( $template, [
			$labels->name,
			$labels->singular_name,
			Core\Text::strToLower( $labels->name ),
			Core\Text::strToLower( $labels->singular_name ),
			'%s',
		] );
	}
}
