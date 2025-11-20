<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

class Box extends Core\Base
{
	private $ctype;
	private $args;
	private $columns;
	private $labels;
	private $connected_items;

	private static $enqueued_scripts = FALSE;
	private static $admin_box_qv = [
		'update_post_term_cache' => FALSE,
		'update_post_meta_cache' => FALSE,
		'post_status'            => 'any',
	];

	public function __construct( $args, $columns, $ctype )
	{
		$this->args    = $args;
		$this->columns = $columns;
		$this->ctype   = $ctype;
		$this->labels  = $this->ctype->get( 'opposite', 'labels' );
	}

	public static function add_templates()
	{
		self::add_template( 'tab-list' );
		self::add_template( 'table-row' );
	}

	private static function add_template( $slug )
	{
		echo Core\HTML::tag( 'script', [
			'type' => 'text/html',
			'id'   => "o2o-template-$slug",
		], file_get_contents( dirname( __FILE__ )."/templates/$slug.html" ) );
	}

	public function render( $item )
	{
		$extra_qv = array_merge( self::$admin_box_qv, [
			'o2o:context'  => 'admin_box',
			'o2o:per_page' => -1,
		] );

		$this->connected_items = $this->ctype->get_connected( $item, $extra_qv, 'abstract' )->items;

		$data = [
			'attributes'         => $this->render_data_attributes(),
			'connections'        => $this->render_connections_table( $item ),
			'create-connections' => $this->render_create_connections( $item ),
			'help'               => isset( $this->labels->help ) ? $this->labels->help : '',
		];

		echo Mustache::render( 'box', $data );
	}

	protected function render_data_attributes()
	{
		$data_attr = [
			'o2o_type'              => $this->ctype->name,
			'duplicate_connections' => $this->ctype->duplicate_connections,
			'cardinality'           => $this->ctype->get( 'opposite', 'cardinality' ),
			'direction'             => $this->ctype->get_direction(),
		];

		$data_attr_str = [];

		foreach ( $data_attr as $key => $value )
			$data_attr_str[] = "data-$key='".$value."'";

		return implode( ' ', $data_attr_str );
	}

	protected function render_connections_table( $item )
	{
		$data = [];

		if ( empty( $this->connected_items ) )
			$data['hide'] = 'style="display:none"';

		$tbody = [];

		foreach ( $this->connected_items as $item )
			$tbody[] = $this->connection_row( $item->o2o_id, $item );

		$data['tbody'] = $tbody;

		foreach ( $this->columns as $key => $field )
			$data['thead'][] = [
				'column' => $key,
				'title'  => $field->get_title(),
			];

		return $data;
	}

	protected function render_create_connections( $item )
	{
		$data = [ 'label' => $this->labels->create ];

		if ( 'one' == $this->ctype->get( 'opposite', 'cardinality' ) ) {

			if ( ! empty( $this->connected_items ) )
				$data['hide'] = 'style="display:none"';
		}

		// Search tab
		$tab_content = Mustache::render( 'tab-search', [
			'placeholder' => $this->labels->search_items,
		] );

		$data['tabs'][] = [
			'tab-id'      => 'search',
			'tab-title'   => _x( 'Search', 'O2O', 'geditorial-admin' ),
			'is-active'   => [ TRUE ],
			'tab-content' => $tab_content
		];

		// `Create post` tab
		if ( $this->can_create_post() ) {

			$tab_content = Mustache::render( 'tab-create-post', [
				'title' => $this->labels->add_new_item,
			] );

			$data['tabs'][] = [
				'tab-id'      => 'create-post',
				'tab-title'   => $this->labels->new_item,
				'tab-content' => $tab_content,
			];
		}

		$data['show-tab-headers'] = count( $data['tabs'] ) > 1 ? [ TRUE ] : FALSE;

		return $data;
	}

	protected function connection_row( $o2o_id, $item, $render = FALSE )
	{
		$item->title = apply_filters( 'o2o_connected_title',
			$item->get_title(),
			$item->get_object(),
			$this->ctype
		);

		$data = [];

		foreach ( $this->columns as $key => $field )
			$data['columns'][] = [
				'column'  => $key,
				'content' => $field->render( $o2o_id, $item ),
			];

		return $render
			? Mustache::render( 'table-row', $data )
			: $data;
	}

	protected function candidate_row( $item )
	{
		$title = apply_filters( 'o2o_candidate_title',
			$item->get_title(),
			$item->get_object(),
			$this->ctype
		);

		$title_data = array_merge( $this->columns['title']->get_data( $item ), [
			'title'   => $title,
			'item-id' => $item->get_id(),
		] );

		$data = [];

		$data['columns'][] = [
			'column' => 'create',
			'content' => Mustache::render( 'column-create', $title_data )
		];

		return $data;
	}

	protected function candidate_rows( $current_post_id, $page = 1, $search = '' )
	{
		$extra_qv = array_merge( self::$admin_box_qv, [
			'o2o:context'  => 'admin_box_candidates',
			'o2o:search'   => $search,
			'o2o:page'     => $page,
			'o2o:per_page' => 5,
		] );

		$candidate = $this->ctype->get_connectable( $current_post_id, $extra_qv, 'abstract' );

		if ( empty( $candidate->items ) )
			return Core\HTML::wrap( $this->labels->not_found, 'o2o-notice' );

		$data = [];

		foreach ( $candidate->items as $item )
			$data['rows'][] = $this->candidate_row( $item );


		if ( $candidate->total_pages > 1 )
			$data['navigation'] = [
				'current-page'    => number_format_i18n( $candidate->current_page ),
				'total-pages'     => number_format_i18n( $candidate->total_pages ),
				'total-pages-raw' => $candidate->total_pages,
				'prev-inactive'   => ( 1 == $candidate->current_page ) ? 'inactive' : '',
				'next-inactive'   => ( $candidate->total_pages == $candidate->current_page ) ? 'inactive' : '',
				'prev-label'      => _x( 'previous', 'O2O', 'geditorial-admin' ),
				'next-label'      => _x( 'next', 'O2O', 'geditorial-admin' ),
				'of-label'        => _x( 'of', 'O2O', 'geditorial-admin' ),
			];

		return $data;
	}

	public function ajax_create_post()
	{
		if ( ! $this->can_create_post() )
			die ( -1 );

		$args = [
			'post_title'  => $_POST['post_title'],
			'post_author' => get_current_user_id(),
			'post_type'   => $this->ctype->get( 'opposite', 'side' )->first_post_type(),
		];

		$from = absint( $_POST['from'] );
		$args = apply_filters( 'o2o_new_post_args', $args, $this->ctype, $from );

		$this->safe_connect( wp_insert_post( $args ) );
	}

	public function ajax_connect()
	{
		$this->safe_connect( $_POST['to'] );
	}

	private function safe_connect( $to )
	{
		$from = absint( $_POST['from'] );
		$to   = absint( $to );

		if ( ! $from || ! $to )
			die ( -1 );

		$o2o_id = $this->ctype->connect( $from, $to );

		self::maybe_send_error( $o2o_id );

		$item = $this->ctype->get( 'opposite','side')->item_recognize( $to );

		die ( json_encode( [
			'row' => $this->connection_row( $o2o_id, $item, TRUE ),
		] ) );
	}

	public function ajax_disconnect()
	{
		O2O\API::deleteConnection( $_POST['o2o_id'] );

		$this->refresh_candidates();
	}

	public function ajax_clear_connections()
	{
		$r = $this->ctype->disconnect( $_POST['from'], 'any' );

		self::maybe_send_error( $r );

		$this->refresh_candidates();
	}

	protected static function maybe_send_error( $r )
	{
		if ( ! is_wp_error( $r ) )
			return;

		die ( json_encode( [
			'error' => $r->get_error_message(),
		] ) );
	}

	public function ajax_search()
	{
		$this->refresh_candidates();
	}

	private function refresh_candidates()
	{
		die ( json_encode( $this->candidate_rows(
			$_REQUEST['from'],
			$_REQUEST['paged'],
			$_REQUEST['s']
		) ) );
	}

	protected function can_create_post()
	{
		if ( ! $this->args->can_create_post )
			return FALSE;

		$side = $this->ctype->get( 'opposite', 'side' );

		return $side->can_create_item();
	}
}
