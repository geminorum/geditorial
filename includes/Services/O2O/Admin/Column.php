<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

abstract class Column
{
	protected $ctype;
	protected $column_id;
	protected $connected = [];

	public function __construct( $directed )
	{
		$this->ctype = $directed;

		$this->column_id = sprintf( 'o2o-%s-%s',
			$this->ctype->get_direction(),
			$this->ctype->name
		);
	}

	public function add_column( $columns )
	{
		$this->prepare_items();

		$labels = $this->ctype->get( 'current', 'labels' );
		$title  = isset( $labels->column_title )
			? $labels->column_title
			: $this->ctype->get( 'current', 'title' );

		return array_splice( $columns, 0, -1 ) + [ $this->column_id => $title ] + $columns;
	}

	protected abstract function get_items();

	protected function prepare_items()
	{
		$items = $this->get_items();

		$extra_qv = [
			'o2o:per_page' => -1,
			'o2o:context'  => 'admin_column',
		];

		$connected = $this->ctype->get_connected( $items, $extra_qv, 'abstract' );

		$this->connected = Core\Arraay::groupBy( $connected->items, [ O2O\Utils, 'getOtherID' ] );
	}

	public function styles()
	{
?><style type="text/css">
.column-<?php echo $this->column_id; ?> ul {
	margin-top: 0;
	margin-bottom: 0;
}
</style><?php
	}

	abstract function get_admin_link( $item );

	protected function render_column( $column, $item_id )
	{
		if ( $this->column_id != $column )
			return '';

		if ( ! isset( $this->connected[$item_id] ) )
			return '';

		$links = [];

		foreach ( $this->connected[$item_id] as $item )
			$links[] = Core\HTML::link( $item->get_title(), $this->get_admin_link( $item ) );

		return Core\HTML::rows( $links );
	}
}
