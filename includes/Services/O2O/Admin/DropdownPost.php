<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

class DropdownPost extends Dropdown
{

	public function __construct( $directed, $title )
	{
		parent::__construct( $directed, $title );

		add_filter( 'request', [ __CLASS__, 'massage_query' ] );
		add_action( 'restrict_manage_posts', [ $this, 'show_dropdown' ] );
	}

	public static function massage_query( $request )
	{
		return array_merge( $request, self::get_qv() );
	}
}

