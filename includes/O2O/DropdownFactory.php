<?php namespace geminorum\gEditorial\O2O;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class DropdownFactory extends Factory
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
		$class = __NAMESPACE__.'Dropdown_'.ucfirst( $object_type );
		$item  = new $class( $directed, $title );
	}
}
