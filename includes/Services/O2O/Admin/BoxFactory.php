<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Services\O2O;

class BoxFactory extends O2O\Factory
{
	protected $key = 'admin_box';

	public function __construct()
	{
		parent::__construct();

		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_post' ], 10, 2 );
		add_action( 'wp_ajax_o2o_box', [ $this, 'wp_ajax_o2o_box' ] );
	}

	public function expand_arg( $args )
	{
		$box_args = parent::expand_arg( $args );

		foreach ( [ 'can_create_post' ] as $key )
			if ( isset( $args[$key] ) )
				$box_args[$key] = O2O\Utils::pluck( $args, $key );

		return wp_parse_args( $box_args, [
			'context'         => 'side',
			'priority'        => 'default',
			'can_create_post' => TRUE
		] );
	}

	public function add_meta_boxes( $post_type )
	{
		$this->filter( 'post', $post_type );
	}

	public function add_item( $directed, $object_type, $post_type, $title )
	{
		if ( ! self::show_box( $directed, $GLOBALS['post'] ) )
			return;

		$box      = $this->create_box( $directed );
		$box_args = $this->queue[ $directed->name ];

		add_meta_box(
			sprintf( 'o2o-%s-%s', $directed->get_direction(), $directed->name ),
			$title,
			[ $box, 'render' ],
			$post_type,
			$box_args->context,
			$box_args->priority
		);

		Services\ObjectToObject::enqueueBox();
	}

	private static function show_box( $directed, $post )
	{
		$show = $directed->get( 'opposite', 'side' )->can_edit_connections();

		return apply_filters( 'o2o_admin_box_show', $show, $directed, $post );
	}

	private function create_box( $directed )
	{
		$box_args = $this->queue[$directed->name];

		$title_class = str_replace( '\\Side', '\\Admin\\FieldTitle',
			get_class( $directed->get( 'opposite', 'side' ) ) );

		$columns = [
			'delete' => new FieldDelete,
			'title'  => new $title_class( $directed->get( 'opposite', 'labels' )->singular_name ),
		];

		foreach ( $directed->fields as $key => $data )
			$columns['meta-'.$key] = new FieldGeneric( $key, $data );

		if ( $orderby_key = $directed->get_orderby_key() ) {
			$columns['order'] = new FieldOrder( $orderby_key );
		}

		return new Box( $box_args, $columns, $directed );
	}

	// Collect metadata from all boxes.
	public function save_post( $post_id, $post )
	{
		if ( 'revision' == $post->post_type || defined( 'DOING_AJAX' ) )
			return;

		if ( isset( $_POST['o2o_connections'] ) ) {

			// Loop through the hidden fields instead of through $_POST['o2o_meta'] because empty checkboxes send no data.
			foreach ( $_POST['o2o_connections'] as $o2o_id ) {

				$data = O2O\Forms\API::get_value( [ 'o2o_meta', $o2o_id ], $_POST, [] );
				// $data = Core\Base::req( 'o2o_meta', [], $o2o_id );

				$connection = O2O\API::getConnection( $o2o_id );

				if ( ! $connection )
					continue;

				$fields = O2O\API::type( $connection->o2o_type )->fields;

				foreach ( $fields as $key => &$field )
					$field['name'] = $key;

				$data = O2O\Forms\API::validate_post_data( $fields, $data );

				O2O\Forms\API::update_meta( $fields, $data, $o2o_id, 'o2o' );
			}
		}

		// Ordering
		if ( isset( $_POST['o2o_order'] ) )
			foreach ( $_POST['o2o_order'] as $key => $list )
				foreach ( $list as $i => $o2o_id )
					O2O\API::updateMeta( $o2o_id, $key, $i );
	}

	// Controller for all box Ajax requests.
	public function wp_ajax_o2o_box()
	{
		check_ajax_referer( GEDITORIAL_O2O_BOX_NONCE, 'nonce' );

		$ctype = O2O\API::type( $_REQUEST['o2o_type'] );

		if ( ! $ctype || ! isset( $this->queue[$ctype->name] ) )
			die ( 0 );

		$directed = $ctype->set_direction( $_REQUEST['direction'] );

		if ( ! $directed )
			die ( 0 );

		$post = get_post( $_REQUEST['from'] );

		if ( !$post )
			die ( 0 );

		if ( ! self::show_box( $directed, $post ) )
			die ( -1 );

		$box = $this->create_box( $directed );

		$method = 'ajax_'.esc_attr( $_REQUEST['subaction'] );

		$box->$method();
	}
}
