<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Services\O2O;

class ColumnUser extends Column
{
	public function __construct( $directed )
	{
		parent::__construct( $directed );

		add_action( 'pre_user_query', [ __CLASS__, 'user_query' ], 9 );
		add_filter( 'manage_users_custom_column', [ $this, 'display_column' ], 10, 3 );
	}

	protected function get_items()
	{
		global $wp_list_table;
		return $wp_list_table->items;
	}

	// Add the query vars to the global user query (on the user admin screen)
	public static function user_query( $query )
	{
		if ( isset( $query->_o2o_capture ) )
			return;

		// Don't overwrite existing O2O query
		if ( isset( $query->query_vars['connected_type'] ) )
			return;

		O2O\Utils::append(
			$query->query_vars,
			wp_array_slice_assoc(
				$_GET,
				Services\ObjectsToObjects::get_custom_query_vars()
			)
		);
	}

	public function get_admin_link( $item )
	{
		$args = [
			'connected_type'      => $this->ctype->name,
			'connected_direction' => $this->ctype->flip_direction()->get_direction(),
			'connected_items'     => $item->get_id(),
		];

		return apply_filters( 'o2o_user_admin_column_link', add_query_arg( $args, admin_url( 'users.php' ) ), $item );
	}

	public function display_column( $content, $column, $item_id )
	{
		return $content.parent::render_column( $column, $item_id );
	}
}

