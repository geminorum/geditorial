<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

class DropdownFactory extends O2O\Factory
{
	protected $key = 'admin_dropdown';

	public function __construct()
	{
		parent::__construct();

		add_action( 'load-edit.php', [ $this, 'add_items' ] );
		add_action( 'load-users.php', [ $this, 'add_items' ] );
	}

	public function add_item( $directed, $object_type, $post_type, $title )
	{
		$class = __NAMESPACE__.'\\Dropdown'.ucfirst( $object_type );

		new $class( $directed, $title );
	}
}
