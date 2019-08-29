<?php namespace geminorum\gEditorial\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

abstract class Factory
{
	protected $key;

	protected $queue = [];

	public function __construct()
	{
		add_action( 'o2o_registered_connection_type', [ $this, 'check_ctype' ], 10, 2 );
	}

	// check if a newly registered connection type needs an item to be produced
	public function check_ctype( $ctype, $args )
	{
		$sub_args = $this->expand_arg( $args );

		if ( ! $sub_args['show'] )
			return FALSE;

		$this->queue[$ctype->name] = (object) $sub_args;
	}

	// collect sub-args from main connection type args and set defaults
	protected function expand_arg( $args )
	{
		if ( isset( $args[$this->key] ) ) {

			$sub_args = $args[$this->key];

			if ( ! is_array( $sub_args ) )
				$sub_args = [ 'show' => $sub_args ];

		} else {

			$sub_args = [ 'show' => FALSE ];
		}

		return Core\Base::args( $sub_args, [ 'show' => 'any' ] );
	}

	// begin processing item queue for a particular screen
	public function add_items()
	{
		$screen = get_current_screen();

		$screen_map = [
			'edit'  => 'post',
			'users' => 'user',
		];

		if ( ! isset( $screen_map[$screen->base] ) )
			return;

		$this->filter( $screen_map[$screen->base], $screen->post_type );
	}

	// filter item queue based on object type
	public function filter( $object_type, $post_type )
	{
		foreach ( $this->queue as $o2o_type => $args ) {

			$ctype = API::type( $o2o_type );

			$directions = self::determine_directions( $ctype, $object_type, $post_type, $args->show );

			$title = self::get_title( $directions, $ctype );

			foreach ( $directions as $direction ) {
				$key = 'to' == $direction ? 'to' : 'from';

				$directed = $ctype->set_direction( $direction );

				$this->add_item( $directed, $object_type, $post_type, $title[$key] );
			}
		}
	}

	// Produce an item and add it to the screen.
	abstract function add_item( $directed, $object_type, $post_type, $title );

	protected static function get_title( $directions, $ctype )
	{
		$title = [
			'from' => $ctype->get_field( 'title', 'from' ),
			'to'   => $ctype->get_field( 'title', 'to' ),
		];

		if ( count( $directions ) > 1 && $title['from'] == $title['to'] ) {
			$title['from'] .= _x( ' (from)', 'O2O', 'geditorial' );
			$title['to']   .= _x( ' (to)', 'O2O', 'geditorial' );
		}

		return $title;
	}

	protected static function determine_directions( $ctype, $object_type, $post_type, $show_ui )
	{
		if ( ! $direction = $ctype->direction_from_types( $object_type, $post_type ) )
			return [];

		return $ctype->strategy->directions_for_admin( $direction, $show_ui );
	}
}
