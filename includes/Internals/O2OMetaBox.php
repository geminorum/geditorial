<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

// TODO: `_hook_o2o_listbox()` @SEE: `_hook_children_listbox()`

trait O2OMetaBox
{
	protected function o2o_register_metabox_from( $connection_constant, $posttypes = NULL, $screen = NULL )
	{
		if ( ! $this->_o2o )
			return FALSE;

		$connection = $this->constant( $connection_constant );

		if ( ! $type = Services\O2O\API::type( $connection ) )
			return FALSE;

		$this->o2o_hook_store_metabox( $screen->post_type, $type );

		$title = Services\O2O\API::metaboxTitle( $type, 'from' );
		$info  = WordPress\MetaBox::markupTitleHelp( $type->get_desc() );

		add_meta_box( $this->classs( $type->name ),
			$this->get_string( 'metabox_title', $connection_constant, 'metabox', $title ).$info,
			[ $this, 'o2o_render_from_connectedbox' ],
			$screen,
			'side',
			'low',
			[
				'o2o'        => $type,
				'title'      => $title,
				'connection' => $connection,
				'posttypes'  => $posttypes ?? $this->posttypes(), // TODO: must get from connection-type itself
				'multiple'   => $type->cardinality['from'] === 'many',
			]
		);

		$this->class_metabox( $screen, 'connectedbox' );
	}

	public function o2o_render_from_connectedbox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );

			$this->o2o_render_type_posts( $post, $box['args']['o2o'], [
				'title'    => $box['args']['title'],
				'posttype' => $box['args']['posttypes'],
				'multiple' => $box['args']['multiple'],
			] );

			do_action(
				$this->hook_base( 'o2o', 'render_from_metabox' ),
				$post,
				$box,
				$box['args']['o2o'],
				$box['args']['posttypes']
			);

		echo '</div>';

		$this->nonce_field( 'o2obox', $box['args']['o2o']->name );
	}

	protected function o2o_register_metabox_to( $connection_constant, $posttypes = NULL, $screen = NULL )
	{
		if ( ! $this->_o2o )
			return FALSE;

		$connection = $this->constant( $connection_constant );

		if ( ! $type = Services\O2O\API::type( $connection ) )
			return FALSE;

		$this->o2o_hook_store_metabox( $screen->post_type, $type );

		$title = Services\O2O\API::metaboxTitle( $type, 'to' );
		$info  = WordPress\MetaBox::markupTitleHelp( $type->get_desc() );

		add_meta_box( $this->classs( $type->name ),
			$this->get_string( 'metabox_title', $connection_constant, 'metabox', $title ).$info,
			[ $this, 'o2o_render_to_connectedbox' ],
			$screen,
			'side',
			'low',
			[
				'o2o'        => $type,
				'title'      => $title,
				'connection' => $connection,
				'posttypes'  => $posttypes ?? $this->posttypes(), // TODO: must get from connection-type itself
				'multiple'   => $type->cardinality['to'] === 'many',
			]
		);

		$this->class_metabox( $screen, 'connectedbox' );
	}

	public function o2o_render_to_connectedbox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );

			$this->o2o_render_type_posts( $post, $box['args']['o2o'], [
				'title'    => $box['args']['title'],
				'posttype' => $box['args']['posttypes'],
				'multiple' => $box['args']['multiple'],
			] );

			do_action(
				$this->hook_base( 'o2o', 'render_to_metabox' ),
				$post,
				$box,
				$box['args']['o2o'],
				$box['args']['posttypes']
			);

		echo '</div>';

		$this->nonce_field( 'o2obox', $box['args']['o2o']->name );
	}

	protected function o2o_hook_store_metabox( $posttype, $type )
	{
		add_action( sprintf( 'save_post_%s', $posttype ),
			function ( $post_id, $post, $update ) use ( $type ) {

				if ( ! $this->is_save_post( $post ) )
					return;

				if ( ! $this->nonce_verify( 'o2obox', NULL, $type->name ) )
					return;

				if ( ! current_user_can( 'edit_post', $post->ID ) )
					return;

				$request = $this->classs( $type->name );

				if ( FALSE === ( $data = self::req( $request, FALSE ) ) )
					return;

				$connected = Core\Arraay::pluck( $type->get_connected( $post, [], 'abstract' )->items, 'ID' );
				$sanitized = Core\Arraay::prepNumeral( $data );

				// NOTE: loop through saved items for disconnecting
				foreach ( $connected as $already ) {

					if ( in_array( $already, $sanitized, TRUE ) )
						continue;

					$o2o = $type->disconnect( $post_id, $already );

					if ( is_wp_error( $o2o ) )
						self::_log( $o2o );
				}

				// NOTE: loop through new items for connecting
				foreach ( $sanitized as $to ) {

					if ( in_array( $to, $connected, TRUE ) )
						continue;

					$o2o = $type->connect( $post_id, $to );

					if ( is_wp_error( $o2o ) )
						self::_log( $o2o );
				}

			}, 20, 3 );
	}

	protected function o2o_render_type_posts( $post, $type, $atts = [] )
	{
		$module  = $this->key;
		$options = [];

		$args = self::atts( [
			'name'        => $type->name,
			'title'       => NULL,
			'description' => $type->get_desc(),
			'placeholder' => NULL,
			'exclude'     => NULL,
			'posttype'    => NULL,
			'taxonomy'    => NULL,
			'multiple'    => NULL,
			'role'        => NULL,
		], $atts );

		$connected = $type->get_connected( $post, [], 'abstract' );

		foreach ( $connected->items as $item )
			$options[] = Core\HTML::tag( 'option', [
				'selected' => TRUE,
				'value'    => $item->get_id(),
			], $item->get_title() );

		$atts = [
			'name'  => sprintf( '%s[]', $this->classs_base( $module, $args['name'] ) ),
			'title' => sprintf( '%s :: %s', $args['title'], $args['description'] ),
			'class' => [
				Core\Text::dashed( $this->base, 'searchselect', 'select2' ),
				Core\Text::dashed( $this->base, $module, 'o2o', $args['name'] ),
			],
			'data' => [
				'o2o-type'  => $type->name,
				'o2o-title' => $args['title'],
				'o2o-desc'  => $args['description'],

				'query-target'   => 'post',
				'query-exclude'  => is_null( $args['exclude'] ) ? $post->ID : ( $args['exclude'] ? implode( ',', (array) $args['exclude'] ) : FALSE ),
				'query-posttype' => $args['posttype'] ? implode( ',', (array) $args['posttype'] ) : FALSE,
				'query-taxonomy' => $args['taxonomy'] ? implode( ',', (array) $args['taxonomy'] ) : FALSE,
				'query-role'     => $args['role']     ? implode( ',', (array) $args['role'] )     : FALSE,
				'query-minimum'  => 3,

				'searchselect-placeholder' => $args['placeholder'] ?? FALSE,
			],

			'multiple' => $args['multiple'] ?? FALSE,
		];

		echo Core\HTML::wrap(
			Core\HTML::tag( 'select', $atts, implode( "\n", $options ) ),
			'field-wrap -select-multiple hide-if-no-js'
		);

		Core\HTML::inputHidden( $atts['name'], '0' );

		return Services\SearchSelect::enqueueSelect2();
	}
}
