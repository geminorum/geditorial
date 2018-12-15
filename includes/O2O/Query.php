<?php namespace geminorum\gEditorial\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class Query extends Core\Base
{

	protected $ctypes, $items, $query, $meta;
	protected $orderby, $order, $order_num;

	private static function expand_shortcuts( $q )
	{
		$shortcuts = [
			'connected'      => 'any',
			'connected_to'   => 'to',
			'connected_from' => 'from',
		];

		foreach ( $shortcuts as $key => $direction ) {
			if ( ! empty( $q[$key] ) ) {
				$q['connected_items']     = Utils::pluck( $q, $key );
				$q['connected_direction'] = $direction;
			}
		}

		return $q;
	}

	private static function expand_ctypes( $item, $directions, $object_type, $ctypes )
	{
		$o2o_types = [];

		foreach ( $ctypes as $i => $o2o_type ) {

			if ( ! $ctype = API::type( $o2o_type ) )
				continue;

			if ( isset( $directions[$i] ) )
				$directed = $ctype->set_direction( $directions[$i] );

			else
				$directed = $ctype->find_direction( $item, TRUE, $object_type );

			if ( ! $directed )
				continue;

			$o2o_types[] = $directed;
		}

		return $o2o_types;
	}

	private static function finalize_query_vars( $q, $directed, $item )
	{
		if ( $orderby_key = $directed->get_orderby_key() )
			$q = self::args( $q, [
				'connected_orderby'   => $orderby_key,
				'connected_order'     => 'ASC',
				'connected_order_num' => TRUE,
			] );

		$q = array_merge_recursive( $q, [ 'connected_meta' => $directed->data ] );
		$q = $directed->get_final_qv( $q, 'opposite' );

		return apply_filters( 'o2o_connected_args', $q, $directed, $item );
	}

	// create instance from mixed query vars
	// also returns the modified query vars
	public static function create_from_qv( $q, $object_type )
	{
		$q = self::expand_shortcuts( $q );

		if ( ! isset( $q['connected_type'] ) ) {

			if ( isset( $q['connected_items'] ) )
				return new \WP_Error( 'no_connection_type', "Queries without 'connected_type' are no longer supported." );

			return;
		}

		if ( isset( $q['connected_direction'] ) )
			$directions = (array) $q['connected_direction'];
		else
			$directions = [];

		$item = isset( $q['connected_items'] ) ? $q['connected_items'] : 'any';

		$ctypes = (array) $q['connected_type'];

		$o2o_types = self::expand_ctypes( $item, $directions, $object_type, $ctypes );

		if ( empty( $o2o_types ) )
			return new \WP_Error( 'no_direction', "Could not find direction(s)." );

		if ( 1 == count( $o2o_types ) )
			$q = self::finalize_query_vars( $q, $o2o_types[0], $item );

		$o2o_q = new Query;

		$o2o_q->ctypes = $o2o_types;
		$o2o_q->items  = $item;

		foreach ( [ 'meta', 'orderby', 'order_num', 'order' ] as $key )
			$o2o_q->$key = isset( $q["connected_$key"] ) ? $q["connected_$key"] : FALSE;

		$o2o_q->query = isset( $q['connected_query'] ) ? $q['connected_query'] : [];

		return [ $o2o_q, $q ];
	}

	protected function __construct() {}

	public function __get( $key )
	{
		return $this->$key;
	}

	private function do_other_query( $directed, $which )
	{
		$side = $directed->get( $which, 'side' );

		$qv = array_merge( $this->query, [
			'fields'       => 'ids',
			'o2o:per_page' => -1
		] );

		if ( 'any' != $this->items )
			$qv['o2o:include'] = Utils::normalize( $this->items );

		$qv = $directed->get_final_qv( $qv, $which );

		return $side->capture_query( $qv );
	}

	// for low-level query modifications
	public function alter_clauses( &$clauses, $main_id_column )
	{
		global $wpdb;

		$clauses['fields'].= ", {$wpdb->o2o}.*";

		$clauses['join'].= " INNER JOIN {$wpdb->o2o}";

		$where_parts = [];

		foreach ( $this->ctypes as $directed ) {

			if ( NULL === $directed ) // used by migration script
				continue;

			$part = $wpdb->prepare( "{$wpdb->o2o}.o2o_type = %s", $directed->name );

			$fields = [ 'o2o_from', 'o2o_to' ];

			switch ( $directed->get_direction() ) {

				case 'from':

					$fields = array_reverse( $fields );
					// fallthrough

				case 'to':

					list( $from, $to ) = $fields;

					$search = $this->do_other_query( $directed, 'current' );

					$part.= " AND $main_id_column = {$wpdb->o2o}.$from";
					$part.= " AND {$wpdb->o2o}.$to IN ($search)";

				break;
				default:

					$part.= sprintf ( " AND (
						($main_id_column = {$wpdb->o2o}.o2o_to AND {$wpdb->o2o}.o2o_from IN (%s)) OR
						($main_id_column = {$wpdb->o2o}.o2o_from AND {$wpdb->o2o}.o2o_to IN (%s))
					)",
						$this->do_other_query( $directed, 'current' ),
						$this->do_other_query( $directed, 'opposite' )
					);
			}

			$where_parts[] = '('.$part.')';
		}

		if ( 1 == count( $where_parts ) )
			$clauses['where'].= " AND ".$where_parts[0];

		else if ( ! empty( $where_parts ) )
			$clauses['where'].= " AND (".implode( ' OR ', $where_parts ).")";

		// handle custom fields
		if ( ! empty( $this->meta ) ) {

			$meta_clauses = self::metaSQLHelper( $this->meta );

			foreach ( $meta_clauses as $key => $value )
				$clauses[$key].= $value;
		}

		// handle ordering
		if ( $this->orderby ) {

			$clauses['join'].= $wpdb->prepare( "
				LEFT JOIN {$wpdb->o2ometa} AS o2om_order ON (
					{$wpdb->o2o}.o2o_id = o2om_order.o2o_id AND o2om_order.meta_key = %s
				)
			", $this->orderby );

			$order = ( 'DESC' == strtoupper( $this->order ) ) ? 'DESC' : 'ASC';

			$field = 'meta_value';

			if ( $this->order_num )
				$field.= '+0';

			$clauses['orderby'] = "o2om_order.$field $order, ".str_replace( 'ORDER BY ', '', $clauses['orderby'] );
		}

		return $clauses;
	}

	// @SOURE: `_p2p_meta_sql_helper()`
	public static function metaSQLHelper( $query )
	{
		global $wpdb;

		if ( isset( $query[0] ) ) {

			$meta_query = $query;

		} else {

			$meta_query = [];

			foreach ( $query as $key => $value )
				$meta_query[] = compact( 'key', 'value' );
		}

		return get_meta_sql( $meta_query, 'o2o', $wpdb->o2o, 'o2o_id' );
	}
}
