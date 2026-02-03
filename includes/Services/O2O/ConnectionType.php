<?php namespace geminorum\gEditorial\Services\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

// `P2P_Connection_Type`
class ConnectionType extends Core\Base
{
	public $name;
	public $side;
	public $cardinality;
	public $labels;
	public $fields;
	public $args;
	public $strategy;
	public $duplicate_connections;
	public $self_connections;
	public $reciprocal;

	public $sortable;
	public $data = [];

	protected $title;

	public function __construct( $args, $sides )
	{
		$this->name = $args['name'];
		$this->side = $sides;

		$this->set_self_connections( $args );
		$this->set_cardinality( Utils::pluck( $args, 'cardinality' ) );

		$labels = [];

		foreach ( [ 'from', 'to' ] as $key )
			$labels[$key] = (array) Utils::pluck( $args, $key.'_labels' );

		$this->labels = $labels;
		$this->fields = $this->expand_fields( Utils::pluck( $args, 'fields' ) );
		$this->args   = $args;
	}

	public function get_field( $field, $direction )
	{
		if ( isset( $this->{$field} ) )
			$value = $this->{$field};

		else
			$value = $this->args[$field];

		if ( 'title' == $field )
			return $this->expand_title( $value, $direction );

		if ( 'labels' == $field )
			return $this->expand_labels( $value, $direction );

		if ( FALSE === $direction )
			return $value;

		return $value[$direction];
	}

	private function set_self_connections( &$args )
	{
		$from_side = $this->side['from'];
		$to_side   = $this->side['to'];

		if ( ! $from_side->is_same_type( $to_side ) )
			$args['self_connections'] = TRUE;
	}

	private function expand_fields( $fields )
	{
		foreach ( $fields as &$field_args ) {

			if ( ! is_array( $field_args ) )
				$field_args = [ 'title' => $field_args ];

			if ( ! isset( $field_args['type'] ) )
				$field_args['type'] = isset( $field_args['values'] ) ? 'select' : 'text';

			else if ( 'checkbox' == $field_args['type'] && ! isset( $field_args['values'] ) )
				$field_args['values'] = [ TRUE => ' ' ];
		}

		return $fields;
	}

	private function set_cardinality( $cardinality )
	{
		$parts = explode( '-', $cardinality );

		$this->cardinality['from'] = $parts[0];
		$this->cardinality['to']   = $parts[2];

		foreach ( $this->cardinality as $key => &$value )
			if ( 'one' != $value )
				$value = 'many';
	}

	private function expand_labels( $additional_labels, $key )
	{
		$labels = clone $this->side[$key]->get_labels();
		$labels->create = _x( 'Create connections', 'O2O', 'geditorial' );

		foreach ( $additional_labels[$key] as $key => $var )
			$labels->$key = $var;

		return $labels;
	}

	private function expand_title( $title, $key )
	{
		if ( $title && ! is_array( $title ) )
			return $title;

		if ( isset( $title[$key] ) )
			return $title[$key];

		$other_key = 'from' == $key ? 'to' : 'from';

		return sprintf(
			/* translators: `%s`: title */
			_x( 'Connected %s', 'O2O', 'geditorial' ),
			$this->side[$other_key]->get_title()
		);
	}

	public function __call( $method, $args )
	{
		if ( ! method_exists( __NAMESPACE__.'\\DirectedConnectionType', $method ) ) {
			trigger_error( "Method '$method' does not exist.", E_USER_ERROR );
			return;
		}

		if ( ! $r = $this->direction_from_item( $args[0] ) ) {
			trigger_error( sprintf( "Can't determine direction for '%s' type.", $this->name ), E_USER_WARNING );
			return FALSE;
		}

		// Replace the first argument with the normalized one, to avoid having to do it again
		list( $direction, $args[0] ) = $r;

		return call_user_func_array( [ $this->set_direction( $direction ), $method ], $args );
	}

	public function set_direction( $direction, $instantiate = TRUE )
	{
		if ( ! in_array( $direction, [ 'from', 'to', 'any' ] ) )
			return FALSE;

		if ( $instantiate ) {
			$class = $this->strategy->get_directed_class();

			return new $class( $this, $direction );
		}

		return $direction;
	}

	// Attempt to guess direction based on a parameter
	public function find_direction( $arg, $instantiate = TRUE, $object_type = NULL )
	{
		if ( $object_type ) {

			if ( ! $direction = $this->direction_from_object_type( $object_type ) )
				return FALSE;

			if ( in_array( $direction, [ 'from', 'to' ] ) )
				return $this->set_direction( $direction, $instantiate );
		}

		if ( ! $r = $this->direction_from_item( $arg ) )
			return FALSE;

		list( $direction, $item ) = $r;

		return $this->set_direction( $direction, $instantiate );
	}

	protected function direction_from_item( $arg )
	{
		if ( is_array( $arg ) )
			$arg = reset( $arg );

		foreach ( [ 'from', 'to' ] as $direction ) {

			if ( ! $item = $this->side[$direction]->item_recognize( $arg ) )
				continue;

			return [ $this->strategy->choose_direction( $direction ), $item ];
		}

		return FALSE;
	}

	protected function direction_from_object_type( $current )
	{
		$from = $this->side['from']->get_object_type();
		$to   = $this->side['to']->get_object_type();

		if ( $from == $to && $current == $from )
			return 'any';

		if ( $current == $from )
			return 'to';

		if ( $current == $to )
			return 'from';

		return FALSE;
	}

	public function direction_from_types( $object_type, $post_types = NULL )
	{
		foreach ( [ 'from', 'to' ] as $direction ) {
			if ( ! $this->_type_check( $direction, $object_type, $post_types ) )
				continue;

			return $this->strategy->choose_direction( $direction );
		}

		return FALSE;
	}

	private function _type_check( $direction, $object_type, $post_types )
	{
		if ( $object_type != $this->side[$direction]->get_object_type() )
			return FALSE;

		$side = $this->side[$direction];

		if ( ! method_exists( $side, 'recognize_post_type' ) )
			return TRUE;

		foreach ( (array) $post_types as $post_type )
			if ( $side->recognize_post_type( $post_type ) )
				return TRUE;

		return FALSE;
	}

	// alias for `get_prev()`
	public function get_previous( $from, $to )
	{
		return $this->get_prev( $from, $to );
	}

	// Get the previous post in an ordered connection
	public function get_prev( $from, $to )
	{
		return $this->get_adjacent( $from, $to, -1 );
	}

	// Get the next post in an ordered connection
	public function get_next( $from, $to )
	{
		return $this->get_adjacent( $from, $to, +1 );
	}

	// Get another post in an ordered connection
	public function get_adjacent( $from, $to, $which )
	{
		// The direction needs to be based on the second parameter,
		// so that it's consistent with $this->connect( $from, $to ) etc.
		if ( ! $r = $this->direction_from_item( $to ) )
			return FALSE;

		list( $direction, $to ) = $r;

		$directed = $this->set_direction( $direction );

		if ( ! $key = $directed->get_orderby_key() )
			return FALSE;

		if ( ! $o2o_id = $directed->get_o2o_id( $to, $from ) )
			return FALSE;

		$order = (int) API::getMeta( $o2o_id, $key, TRUE );

		$adjacent = $directed->get_connected( $to, [
			'connected_meta' => [ [
				'key'   => $key,
				'value' => $order + $which
			] ],
		], 'abstract' );

		if ( empty( $adjacent->items ) )
			return FALSE;

		$item = reset( $adjacent->items );

		return $item->get_object();
	}

	// Get the previous, next, and parent items, in an ordered connection type.
	public function get_adjacent_items( $item )
	{
		$result = [
			'parent'   => FALSE,
			'previous' => FALSE,
			'next'     => FALSE,
		];

		if ( ! $r = $this->direction_from_item( $item ) )
			return FALSE;

		list( $direction, $item ) = $r;

		$connected_series = $this->set_direction( $direction )->get_connected( $item, [], 'abstract' )->items;

		if ( empty( $connected_series ) )
			return $r;

		if ( count( $connected_series ) > 1 )
			trigger_error( 'More than one connected parents found.', E_USER_WARNING );

		$parent = $connected_series[0];

		$result['parent']   = $parent->get_object();
		$result['previous'] = $this->get_previous( $item->ID, $parent->ID );
		$result['next']     = $this->get_next( $item, $parent );

		return $result;
	}

	// Optimized inner query, after the outer query was executed
	// populates each of the outer query's $post objects with
	// a 'connected' property, containing a list of connected posts
	public function each_connected( $items, $extra_qv = [], $prop_name = 'connected' )
	{
		if ( is_a( $items, 'WP_Query' ) )
			$items =& $items->posts;

		if ( empty( $items ) || ! is_object( $items[0] ) )
			return;

		$post_types = array_unique( Core\Arraay::pluck( $items, 'post_type' ) );

		if ( count( $post_types ) > 1 )
			$extra_qv['post_type'] = 'any';

		$possible_directions = [];

		foreach ( [ 'from', 'to' ] as $direction ) {

			$side = $this->side[$direction];

			if ( 'post' == $side->get_object_type() )
				foreach ( $post_types as $post_type )
					if ( $side->recognize_post_type( $post_type ) )
						$possible_directions[] = $direction;
		}

		if ( ! $direction = Utils::compressDirection( $possible_directions ) )
			return FALSE;

		$directed = $this->set_direction( $direction );

		// ignore pagination
		foreach ( [ 'showposts', 'posts_per_page', 'posts_per_archive_page' ] as $disabled_qv )
			if ( isset( $extra_qv[$disabled_qv] ) )
				trigger_error( "Can't use '$disabled_qv' in an inner query", E_USER_WARNING );

		$extra_qv['nopaging'] = TRUE;

		$q = $directed->get_connected( $items, $extra_qv, 'abstract' );

		$raw_connected = [];

		foreach ( $q->items as $item )
			$raw_connected[] = $item->get_object();

		API::distributeConnected( $items, $raw_connected, $prop_name );
	}

	public function get_desc()
	{
		$desc = [];

		foreach ( [ 'from', 'to' ] as $key )
			$desc[$key] = $this->side[$key]->get_desc();

		$label = Core\Text::spaced( $desc['from'], $this->strategy->get_arrow(), $desc['to'] );
		$title = $this->get_field( 'title', 'from' );

		if ( $title )
			$label.= " ($title)";

		return $label;
	}
}
