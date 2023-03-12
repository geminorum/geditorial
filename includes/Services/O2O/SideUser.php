<?php namespace geminorum\gEditorial\Services\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

// `P2P_Side_User`
class SideUser extends Side
{

	protected $item_type = 'ItemUser';

	public function __construct( $query_vars )
	{
		$this->query_vars = $query_vars;
	}

	public function get_object_type()
	{
		return 'user';
	}

	public function get_desc()
	{
		return _x( 'Users', 'O2O', 'geditorial' );
	}

	public function get_title()
	{
		return $this->get_desc();
	}

	public function get_labels()
	{
		return (object) [
			'singular_name' => _x( 'User', 'O2O', 'geditorial' ),
			'search_items'  => _x( 'Search Users', 'O2O', 'geditorial' ),
			'not_found'     => _x( 'No users found.', 'O2O', 'geditorial' ),
		];
	}

	public function can_edit_connections()
	{
		return current_user_can( 'list_users' );
	}

	public function can_create_item()
	{
		return FALSE;
	}

	public function translate_qv( $qv )
	{
		if ( isset( $qv['o2o:include'] ) )
			$qv['include'] = Utils::pluck( $qv, 'o2o:include' );

		if ( isset( $qv['o2o:exclude'] ) )
			$qv['exclude'] = Utils::pluck( $qv, 'o2o:exclude' );

		if ( isset( $qv['o2o:search'] ) && $qv['o2o:search'] )
			$qv['search'] = '*'.Utils::pluck( $qv, 'o2o:search' ).'*';

		if ( isset( $qv['o2o:page'] ) && $qv['o2o:page'] > 0 ) {
			if ( isset( $qv['o2o:per_page'] ) && $qv['o2o:per_page'] > 0 ) {
				$qv['number'] = $qv['o2o:per_page'];
				$qv['offset'] = $qv['o2o:per_page'] * ( $qv['o2o:page'] - 1 );
			}
		}

		return $qv;
	}

	public function do_query( $args )
	{
		return new \WP_User_Query( $args );
	}

	public function capture_query( $args )
	{
		$args['count_total'] = FALSE;

		$uq = new \WP_User_Query;
		$uq->_o2o_capture = TRUE; // needed by URLQuery

		$uq->prepare_query( $args );

		return "SELECT {$uq->query_fields}
			{$uq->query_from}
			{$uq->query_where}
			{$uq->query_orderby}
			{$uq->query_limit}";
	}

	public function get_list( $query )
	{
		$list = new ListItems( $query->get_results(), $this->item_type );

		$qv = $query->query_vars;

		if ( isset( $qv['o2o:page'] ) ) {
			$list->current_page = $qv['o2o:page'];
			$list->total_pages  = ceil( $query->get_total() / $qv['o2o:per_page'] );
		}

		return $list;
	}

	public function is_indeterminate( $side )
	{
		return TRUE;
	}

	public function get_base_qv( $q )
	{
		return array_merge( $this->query_vars, $q );
	}

	protected function recognize( $arg )
	{
		if ( is_a( $arg, 'WP_User' ) )
			return $arg;

		return get_user_by( 'id', $arg );
	}
}
