<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

trait PostsToPosts
{

	public function settings_section_p2p()
	{
		Settings::fieldSection(
			_x( 'Posts-to-Posts', 'Internal: PostsToPosts: Setting Section Title', 'geditorial' )
		);
	}

	// @REF: https://github.com/scribu/wp-posts-to-posts/wiki
	public function p2p_register( $constant, $posttypes = NULL )
	{
		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		if ( empty( $posttypes ) )
			return FALSE;

		$to  = $this->constant( $constant );
		$p2p = $this->constant( $constant.'_p2p' );
		$pre = empty( $this->strings['p2p'][$constant] ) ? [] : $this->strings['p2p'][$constant];

		$args = array_merge( [
			'name'            => $p2p,
			'from'            => $posttypes,
			'to'              => $to,
			'can_create_post' => FALSE,
			'admin_column'    => 'from', // 'any', 'from', 'to', FALSE
			'admin_box'       => [
				'show'    => 'from',
				'context' => 'advanced',
			],
		], $pre );

		$hook = 'geditorial_'.$this->module->name.'_'.$to.'_p2p_args';

		if ( $args = apply_filters( $hook, $args, $posttypes ) )
			if ( p2p_register_connection_type( $args ) )
				$this->_p2p = $p2p;
	}

	public function p2p_get_meta( $p2p_id, $meta_key, $before = '', $after = '', $args = [] )
	{
		if ( ! $this->_p2p )
			return '';

		if ( ! $meta = p2p_get_meta( $p2p_id, $meta_key, TRUE ) )
			return '';

		if ( ! empty( $args['type'] ) && 'text' == $args['type'] )
			$meta = apply_filters( 'string_format_i18n', $meta );

		if ( ! empty( $args['template'] ) )
			$meta = sprintf( $args['template'], $meta );

		if ( ! empty( $args['title'] ) )
			$meta = '<span title="'.Core\HTML::escape( $args['title'] ).'">'.$meta.'</span>';

		return $before.$meta.$after;
	}

	public function p2p_get_meta_row( $constant, $p2p_id, $before = '', $after = '' )
	{
		if ( ! $this->_p2p )
			return '';

		$row = '';

		if ( ! empty( $this->strings['p2p'][$constant]['fields'] ) )
			foreach ( $this->strings['p2p'][$constant]['fields'] as $field => $args )
				$row.= $this->p2p_get_meta( $p2p_id, $field, $before, $after, $args );

		return $row;
	}

	// @REF: https://github.com/scribu/wp-posts-to-posts/wiki/Creating-connections-programmatically
	public function p2p_connect( $constant, $from, $to, $meta = [] )
	{
		if ( ! $this->_p2p )
			return FALSE;

		$type = p2p_type( $this->constant( $constant.'_p2p' ) );
		// $id   = $type->connect( $from, $to, [ 'date' => current_time( 'mysql' ) ] );
		$id   = $type->connect( $from, $to, $meta );

		if ( is_wp_error( $id ) )
			return FALSE;

		// foreach ( $meta as $key => $value )
		// 	p2p_add_meta( $id, $key, $value );

		return TRUE;
	}

	protected function column_row_p2p_to_posttype( $constant, $post, $before, $after )
	{
		static $icons = [];

		if ( ! $this->_p2p )
			return;

		$extra = [ 'p2p:per_page' => -1, 'p2p:context' => 'admin_column' ];
		$type  = $this->constant( $constant.'_p2p' );

		if ( ! $p2p_type = p2p_type( $type ) )
			return;

		$p2p   = $p2p_type->get_connected( $post, $extra, 'abstract' );
		$count = count( $p2p->items );

		if ( ! $count )
			return;

		if ( empty( $icons[$constant] ) )
			$icons[$constant] = $this->get_column_icon( FALSE, NULL, $this->strings['p2p'][$constant]['title']['to'] );

		if ( empty( $this->cache['posttypes'] ) )
			$this->cache['posttypes'] = WordPress\PostType::get( 2, [ 'show_ui' => TRUE ] );

		$args = [
			'connected_direction' => 'to',
			'connected_type'      => $type,
			'connected_items'     => $post->ID,
		];

		printf( $before, '-p2p -connected' );

			echo $icons[$constant];

			echo '<span class="-counted">'.$this->nooped_count( 'connected', $count ).'</span>';

			$list = [];

			foreach ( array_unique( Core\Arraay::pluck( $p2p->items, 'post_type' ) ) as $posttype )
				$list[] = Core\HTML::tag( 'a', [
					'href'   => WordPress\PostType::edit( $posttype, $args ),
					'title'  => _x( 'View the connected list', 'Module: P2P', 'geditorial' ),
					'target' => '_blank',
				], $this->cache['posttypes'][$posttype] );

			echo WordPress\Strings::getJoined( $list, ' <span class="-posttypes">(', ')</span>' );

		echo $after;
	}

	protected function column_row_p2p_from_posttype( $constant, $post, $before, $after )
	{
		static $icons = [];

		if ( ! $this->_p2p )
			return;

		if ( empty( $icons[$constant] ) )
			$icons[$constant] = $this->get_column_icon( FALSE, NULL, $this->strings['p2p'][$constant]['title']['from'] );

		$extra = [ 'p2p:per_page' => -1, 'p2p:context' => 'admin_column' ];
		$type  = $this->constant( $constant.'_p2p' );

		if ( ! $p2p_type = p2p_type( $type ) )
			return;

		$p2p = $p2p_type->get_connected( $post, $extra, 'abstract' );

		foreach ( $p2p->items as $item ) {

			printf( $before, '-p2p -connected' );

				if ( current_user_can( 'edit_post', $item->get_id() ) )
					echo $this->get_column_icon( get_edit_post_link( $item->get_id() ),
						NULL, $this->strings['p2p'][$constant]['title']['from'] );
				else
					echo $icons[$constant];

				$args = [
					'connected_direction' => 'to',
					'connected_type'      => $type,
					'connected_items'     => $item->get_id(),
				];

				echo Core\HTML::tag( 'a', [
					'href'   => WordPress\PostType::edit( $post->post_type, $args ),
					'title'  => _x( 'View all connected', 'Module: P2P', 'geditorial' ),
					'target' => '_blank',
				], WordPress\Strings::trimChars( $item->get_title(), 85 ) );

				echo $this->p2p_get_meta_row( $constant, $item->p2p_id, ' &ndash; ', '' );

			echo $after;
		}
	}
}
