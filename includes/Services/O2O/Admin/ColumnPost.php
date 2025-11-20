<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

class ColumnPost extends Column
{
	public function __construct( $directed )
	{
		parent::__construct( $directed );

		$screen = get_current_screen();

		add_action( "manage_{$screen->post_type}_posts_custom_column", [ $this, 'display_column' ], 10, 2 );
	}

	protected function get_items()
	{
		global $wp_query;

		return $wp_query->posts;
	}

	public function get_admin_link( $item )
	{
		$args = [
			'connected_type'      => $this->ctype->name,
			'connected_direction' => $this->ctype->flip_direction()->get_direction(),
			'connected_items'     => $item->get_id(),
			'post_type'           => get_current_screen()->post_type
		];

		return apply_filters( 'o2o_post_admin_column_link', add_query_arg( $args, admin_url( 'edit.php' ) ), $item );
	}

	function display_column( $column, $item_id )
	{
		echo parent::render_column( $column, $item_id );
	}
}

