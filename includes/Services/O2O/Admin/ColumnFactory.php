<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

class ColumnFactory extends O2O\Factory
{
	protected $key = 'admin_column';

	public function __construct()
	{
		parent::__construct();

		add_action( 'load-edit.php', [ $this, 'add_items' ] );
		add_action( 'load-users.php', [ $this, 'add_items'] );
	}

	public function add_item( $directed, $object_type, $post_type, $title )
	{
		$class = __NAMESPACE__.'\\Column'.ucfirst( $object_type );
		$column = new $class( $directed );

		$screen = get_current_screen();

		add_filter( "manage_{$screen->id}_columns", [ $column, 'add_column' ] );
		add_action( 'admin_print_styles', [ $column, 'styles' ] );
	}
}

