<?php namespace geminorum\gEditorial\Services\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class DirectedConnectionType
{
	protected $ctype;
	protected $direction;

	protected $self_connections;
	protected $duplicate_connections;

	public function __construct( $ctype, $direction )
	{
		$this->ctype     = $ctype;
		$this->direction = $direction;
	}

	public function __get( $key )
	{
		return $this->ctype->{$key};
	}

	public function __isset( $key )
	{
		return isset( $this->ctype->{$key} );
	}

	public function get_direction()
	{
		return $this->direction;
	}

	public function set_direction( $direction )
	{
		return $this->ctype->set_direction( $direction );
	}

	public function lose_direction()
	{
		return $this->ctype;
	}

	public function flip_direction()
	{
		return $this->set_direction( Utils::flipDirection( $this->direction ) );
	}

	public function get( $side, $field )
	{
		static $map = [
			'current' => [
				'to'   => 'to',
				'from' => 'from',
				'any'  => 'from'
			],
			'opposite' => [
				'to'   => 'from',
				'from' => 'to',
				'any'  => 'to'
			]
		];

		return $this->ctype->get_field( $field, $map[$side][$this->direction] );
	}

	private function abstract_query( $qv, $which, $output = 'abstract' )
	{
		$side  = $this->get( $which, 'side' );
		$qv    = $this->get_final_qv( $qv, $which );
		$query = $side->do_query( $qv );

		if ( 'raw' == $output )
			return $query;

		return $side->get_list( $query );
	}

	protected function recognize_any( $item, $which = 'current' )
	{
		if ( 'any' == $item )
			return new ItemAny;

		if ( is_a( $item, __NAMESPACE__.'\\ItemAny' ) )
			return $item;

		return $this->recognize( $item, $which );
	}

	protected function recognize( $item, $which = 'current' )
	{
		return $this->get( $which, 'side' )->item_recognize( $item );
	}

	public function get_final_qv( $q, $which = 'current' )
	{
		$side = $this->get( $which, 'side' );

		return $side->get_base_qv( $side->translate_qv( $q ) );
	}

	// Get a list of posts connected to other posts connected to a post
	public function get_related( $item, $extra_qv = [], $output = 'raw' )
	{
		$extra_qv['fields']       = 'ids';
		$extra_qv['o2o:per_page'] = -1;

		$connected = $this->get_connected( $item, $extra_qv, 'abstract' );

		$additional_qv = [
			'o2o:exclude'  => Utils::normalize( $item ),
			'o2o:per_page' => -1
		];

		return $this->flip_direction()->get_connected( $connected->items, $additional_qv, $output );
	}

	// Get a list of items that are connected to a given item
	public function get_connected( $item, $extra_qv = [], $output = 'raw' )
	{
		$args = array_merge( $extra_qv, [
			'connected_type'      => $this->name,
			'connected_direction' => $this->direction,
			'connected_items'     => $item
		] );

		return $this->abstract_query( $args, 'opposite', $output );
	}

	public function get_orderby_key()
	{
		if ( ! $this->sortable || 'any' == $this->direction )
			return FALSE;

		if ( 'any' == $this->sortable || $this->direction == $this->sortable )
			return '_order_'.$this->direction;

		// back-compat
		if ( 'from' == $this->direction )
			return $this->sortable;

		return FALSE;
	}

	// Get a list of items that could be connected to a given item
	public function get_connectable( $arg, $extra_qv = [], $output = 'raw' )
	{
		$side = $this->get( 'opposite', 'side' );
		$item = $this->recognize_any( $arg );

		$extra_qv['o2o:exclude'] = $this->get_non_connectable( $item, $extra_qv );

		$qv = apply_filters( 'o2o_connectable_args', $extra_qv, $this, $item->get_object() );

		return $this->abstract_query( $qv, 'opposite', $output );
	}

	protected function get_non_connectable( $item, $extra_qv )
	{
		$to_exclude = [];

		if ( 'one' == $this->get( 'current', 'cardinality' ) )
			$to_check = 'any';

		else if ( ! $this->duplicate_connections )
			$to_check = $item;

		else
			return $to_exclude;

		$extra_qv['fields']       = 'ids';
		$extra_qv['o2o:per_page'] = -1;

		$already_connected = $this->get_connected( $to_check, $extra_qv, 'abstract' )->items;

		Utils::append( $to_exclude, $already_connected );

		return $to_exclude;
	}

	// connect two items
	public function connect( $from_arg, $to_arg, $meta = [] )
	{
		if ( ! $from = $this->recognize( $from_arg, 'current' ) )
			return new \WP_Error( 'first_parameter', 'Invalid first parameter.' );

		if ( ! $to = $this->recognize( $to_arg, 'opposite' ) )
			return new \WP_Error( 'second_parameter', 'Invalid second parameter.' );

		if ( ! $this->self_connections && $from->get_id() == $to->get_id() )
			return new \WP_Error( 'self_connection', 'Connection between an element and itself is not allowed.' );

		if ( ! $this->duplicate_connections && $this->get_o2o_id( $from, $to ) )
			return new \WP_Error( 'duplicate_connection', 'Duplicate connections are not allowed.' );

		if ( 'one' == $this->get( 'opposite', 'cardinality' ) ) {

			if ( $this->has_connections( $from ) )
				return new \WP_Error( 'cardinality_opposite', 'Cardinality problem (opposite).' );
		}

		if ( 'one' == $this->get( 'current', 'cardinality' ) ) {

			if ( $this->flip_direction()->has_connections( $to ) )
				return new \WP_Error( 'cardinality_current', 'Cardinality problem (current).' );
		}

		$o2o_id = $this->createConnection( [
			'from' => $from,
			'to'   => $to,
			'meta' => array_merge( $meta, $this->data )
		] );

		// store additional default values
		foreach ( $this->fields as $key => $args ) {
			// (array) null == []
			foreach ( (array) $this->get_default( $args, $o2o_id ) as $default_value )
				API::addMeta( $o2o_id, $key, $default_value );
		}

		return $o2o_id;
	}

	protected function has_connections( $item )
	{
		$extra_qv = [ 'o2o:per_page' => 1 ];

		$connections = $this->get_connected( $item, $extra_qv, 'abstract' );

		return ! empty( $connections->items );
	}

	protected function get_default( $args, $o2o_id )
	{
		if ( isset( $args['default_cb'] ) )
			return call_user_func( $args['default_cb'], API::getConnection( $o2o_id ), $this->direction );

		if ( ! isset( $args['default'] ) )
			return NULL;

		return $args['default'];
	}

	// disconnect two items
	public function disconnect( $from_arg, $to_arg )
	{
		if ( ! $from = $this->recognize( $from_arg, 'current' ) )
			return new \WP_Error( 'first_parameter', 'Invalid first parameter.' );

		if ( ! $to = $this->recognize_any( $to_arg, 'opposite' ) )
			return new \WP_Error( 'second_parameter', 'Invalid second parameter.' );

		return $this->deleteConnections( compact( 'from', 'to' ) );
	}

	public function get_o2o_id( $from, $to )
	{
		return Utils::first( $this->getConnections( [
			'from'   => $from,
			'to'     => $to,
			'fields' => 'o2o_id'
		] ) );
	}

	// transforms $this->getConnections( ... )
	// into API::getConnections( $this->name, ... ) etc.
	public function __call( $method, $argv )
	{
		list( $args ) = $argv;

		$args['direction'] = $this->direction;

		foreach ( [ 'from', 'to' ] as $key )
			if ( isset( $args[$key] ) && is_a( $args[$key], __NAMESPACE__.'\\ItemAny' ) )
				$args[$key] = 'any';

		return call_user_func( [ __NAMESPACE__.'\\API', $method ], $this->name, $args );
	}
}
