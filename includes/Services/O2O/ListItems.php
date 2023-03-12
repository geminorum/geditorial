<?php namespace geminorum\gEditorial\Services\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

// `P2P_List`
class ListItems extends Core\Base
{

	public $items;

	public $current_page = 1;
	public $total_pages  = 0;

	public function __construct( $items, $item_type )
	{
		if ( is_numeric( reset( $items ) ) ) {

			// Don't wrap when we just have a list of ids
			$this->items = $items;

		} else {

			$this->items = Utils::wrap( $items, __NAMESPACE__.'\\'.$item_type );
		}
	}
}
