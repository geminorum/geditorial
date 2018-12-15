<?php namespace geminorum\gEditorial\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class DropdownPost extends Dropdown
{

	public function __construct( $directed, $title )
	{
		parent::__construct( $directed, $title );

		add_filter( 'request', [ __CLASS__, 'massage_query' ] );
		add_action( 'restrict_manage_posts', [ $this, 'show_dropdown' ] );
	}

	static function massage_query( $request )
	{
		return array_merge( $request, self::get_qv() );
	}
}
