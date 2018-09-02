<?php namespace geminorum\gEditorial\O2O;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

// a dropdown above a list table in wp-admin
abstract class Dropdown
{

	protected $ctype;
	protected $title;

	public function __construct( $directed, $title )
	{
		$this->ctype = $directed;
		$this->title = $title;
	}

	public function show_dropdown()
	{
		echo $this->render_dropdown();
	}

	protected function render_dropdown()
	{
		$direction = $this->ctype->flip_direction()->get_direction();

		$labels = $this->ctype->get( 'current', 'labels' );

		if ( isset( $labels->dropdown_title ) )
			$title = $labels->dropdown_title;

		else if ( isset( $labels->column_title ) )
			$title = $labels->column_title;

		else
			$title = $this->title;

		return scbForms::input( [
			'type'    => 'select',
			'name'    => [ 'o2o', $this->ctype->name, $direction ],
			'choices' => self::get_choices( $this->ctype ),
			'text'    => $title,
		], $_GET );
	}

	protected static function get_qv()
	{
		if ( !isset( $_GET['o2o'] ) )
			return array();

		$args = array();

		$tmp = reset( $_GET['o2o'] );

		$args['connected_type'] = key( $_GET['o2o'] );

		list( $args['connected_direction'], $args['connected_items'] ) = each( $tmp );

		if ( !$args['connected_items'] )
			return array();

		return $args;
	}

	protected static function get_choices( $directed )
	{
		$extra_qv = array(
			'o2o:per_page' => -1,
			'o2o:context'  => 'admin_dropdown'
		);

		$connected = $directed->get_connected( 'any', $extra_qv, 'abstract' );

		$options = array();

		foreach ( $connected->items as $item )
			$options[ $item->get_id() ] = $item->get_title();

		return $options;
	}
}
