<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait ObjectsToObjects
{

	protected function o2o_register( $constant, $context = NULL, $posttypes = NULL, $extra = NULL )
	{
		if ( ! $from = $this->constant( $constant ) )
			return FALSE;

		$context   = $context   ?? 'o2o';
		$posttypes = $posttypes ?? $this->get_setting_posttypes( $context );

		if ( ! $posttypes )
			return FALSE;

		$name = $this->constant( self::und( $constant, $context ), self::und( $from, $context ) );
		$pre  = $extra ?? ( $this->strings[$context][$constant] ?? [] );

		$args = apply_filters( $this->hook( $from, $context, 'args' ), array_merge( [
			'title' => _x( 'Connected Posts', 'O2O: MetaBox Title', 'geditorial-admin' ),

			'fields'          => [],
			'reciprocal'      => TRUE,
			'can_create_post' => FALSE,
			'admin_column'    => FALSE,   // 'from',
			'admin_box'       => [
				// 'show'    => 'from',
				'context' => $this->get_setting( self::und( $context, 'adminbox_context' ), FALSE ) ? 'advanced' : 'normal',
			],

		], $pre, [
			'name' => $name,
			'from' => $from,
			'to'   => $posttypes,
		] ), $posttypes, $name );

		if ( $this->get_setting( self::und( $context, 'field_desc' ), FALSE ) )
			$args['fields']['desc'] = [
				'title' => $this->get_setting( self::und( $context, 'string_desc' ), _x( 'Description', 'O2O: Field Title', 'geditorial-admin' ) ),
				'type'  => 'text',
			];

		if ( ! $args )
			return FALSE; // $this->log( 'NOTICE', 'O2O REGISTRATION SKIPPED BY FILTER ON: '.$o2o );

		if ( ! Services\O2O\API::registerConnectionType( $args ) )
			return $this->log( 'NOTICE', 'O2O REGISTRATION ERROR ON: '.$name );

		return $this->_o2o = $name;
	}

	// @REF: https://github.com/scribu/wp-posts-to-posts/wiki
	protected function o2o_register_Legacy( $constant, $posttypes = NULL )
	{
		if ( ! $posttypes = $posttypes ?? $this->posttypes() )
			return FALSE;

		$to  = $this->constant( $constant );
		$o2o = $this->constant( self::und( $constant, 'o2o' ) );
		$pre = empty( $this->strings['o2o'][$constant] ) ? [] : $this->strings['o2o'][$constant];

		$args = apply_filters( $this->hook( $to, 'o2o', 'args' ), array_merge( [
			'name'            => $o2o,
			'from'            => $posttypes,
			'to'              => $to,
			'can_create_post' => FALSE,
			'admin_column'    => FALSE, // 'any', 'from', 'to', FALSE
			'admin_box'       => [
				'show'    => 'from',
				'context' => 'advanced',
			],
		], $pre ), $posttypes, $o2o );

		if ( ! $args )
			return FALSE; // $this->log( 'NOTICE', 'O2O REGISTRATION SKIPPED BY FILTER ON: '.$o2o );

		if ( ! Services\O2O\API::registerConnectionType( $args ) )
			return $this->log( 'NOTICE', 'O2O REGISTRATION ERROR ON: '.$o2o );

		return $this->_o2o = $o2o;
	}

	protected function o2o__hook_insert_content( $o2o, $constant, $context = NULL )
	{
		if ( ! $o2o || is_admin() )
			return FALSE;

		$context = $context ?? 'o2o';

		if ( $this->get_setting( self::und( $context, 'insert_content' ) ) )
			add_action( $this->hook_base( 'content', 'after' ),
				function () use ( $o2o, $constant, $context ) {

					if ( ! $this->is_content_insert( $this->get_setting_posttypes( $context ) ) )
						return;

					$this->o2o_list_connected( $o2o, $constant, $context, NULL, '-after' );

				}, $this->get_setting( self::und( $context, 'insert_priority' ), 100 ) );

		return $o2o;
	}

	/**
	 * Retrieves settings field for the `O2O` description field.
	 *
	 * @param string $context
	 * @param string $title
	 * @param string $description
	 * @return array
	 */
	protected function settings_o2o_field_desc( $context = NULL, $title = NULL, $description = NULL )
	{
		return [
			'field'       => self::und( $context ?? 'o2o', 'field_desc' ),
			'title'       => $title ?? _x( 'Description Field', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Displays description field on connected metabox.', 'Setting Description', 'geditorial-admin' ),
		];
	}

	public function o2o_get_meta( $o2o_id, $meta_key, $before = '', $after = '', $args = [] )
	{
		if ( ! $this->_o2o )
			return '';

		if ( ! $meta = Services\O2O\API::getMeta( $o2o_id, $meta_key, TRUE ) )
			return '';

		if ( ! empty( $args['type'] ) && 'text' == $args['type'] )
			$meta = apply_filters( 'string_format_i18n', $meta );

		if ( ! empty( $args['template'] ) )
			$meta = sprintf( $args['template'], $meta );

		if ( ! empty( $args['title'] ) )
			$meta = '<span title="'.Core\HTML::escape( $args['title'] ).'">'.$meta.'</span>';

		return $before.$meta.$after;
	}

	public function o2o_get_meta_row( $constant, $o2o_id, $before = '', $after = '' )
	{
		if ( ! $this->_o2o )
			return '';

		$row = '';

		if ( ! empty( $this->strings['o2o'][$constant]['fields'] ) )
			foreach ( $this->strings['o2o'][$constant]['fields'] as $field => $args )
				$row.= $this->o2o_get_meta( $o2o_id, $field, $before, $after, $args );

		return $row;
	}

	// @REF: https://github.com/scribu/wp-posts-to-posts/wiki/Creating-connections-programmatically
	public function o2o_connect( $constant, $from, $to, $meta = [] )
	{
		if ( ! $this->_o2o )
			return FALSE;

		$type = Services\O2O\API::type( $this->constant( self::und( $constant, 'o2o' ) ) );
		// $id   = $type->connect( $from, $to, [ 'date' => current_time( 'mysql' ) ] );
		$id   = $type->connect( $from, $to, $meta );

		if ( is_wp_error( $id ) )
			return FALSE;

		// foreach ( $meta as $key => $value )
		// 	Services\O2O\API::addMeta( $id, $key, $value );

		return TRUE;
	}

	protected function column_row_o2o_to_posttype( $constant, $post, $before, $after )
	{
		static $icons = [];

		if ( ! $this->_o2o )
			return;

		$type  = $this->constant( self::und( $constant, 'o2o' ) );
		$extra = [
			'o2o:per_page' => -1,
			'o2o:context'  => 'admin_column',
		];

		if ( ! $o2o_type = Services\O2O\API::type( $type ) )
			return;

		$o2o   = $o2o_type->get_connected( $post, $extra, 'abstract' );
		$count = count( $o2o->items );

		if ( ! $count )
			return;

		if ( empty( $icons[$constant] ) )
			$icons[$constant] = $this->get_column_icon(
				FALSE,
				NULL,
				$this->strings['o2o'][$constant]['title']['to'] ?? NULL
			);

		if ( empty( $this->cache['posttypes'] ) )
			$this->cache['posttypes'] = WordPress\PostType::get( 2, [ 'show_ui' => TRUE ] );

		$args = [
			'connected_direction' => 'to',
			'connected_type'      => $type,
			'connected_items'     => $post->ID,
		];

		printf( $before, '-o2o -connected' );

			echo $icons[$constant];

			echo '<span class="-counted">'.$this->nooped_count( 'connected', $count ).'</span>';

			$list = [];

			foreach ( array_unique( Core\Arraay::pluck( $o2o->items, 'post_type' ) ) as $posttype )
				$list[] = Core\HTML::tag( 'a', [
					'href'   => WordPress\PostType::edit( $posttype, $args ),
					'title'  => _x( 'View the connected list', 'Internal: ObjectsToObjects', 'geditorial' ),
					'target' => '_blank',
				], $this->cache['posttypes'][$posttype] );

			echo WordPress\Strings::getJoined( $list, ' <span class="-posttypes">(', ')</span>' );

		echo $after;
	}

	protected function column_row_o2o_from_posttype( $constant, $post, $before, $after )
	{
		static $icons = [];

		if ( ! $this->_o2o )
			return;

		if ( empty( $icons[$constant] ) )
			$icons[$constant] = $this->get_column_icon(
				FALSE,
				NULL,
				$this->strings['o2o'][$constant]['title']['from'] ?? NULL
			);

		$type  = $this->constant( $constant.'_o2o' );
		$extra = [
			'o2o:per_page' => -1,
			'o2o:context'  => 'admin_column',
		];

		if ( ! $o2o_type = Services\O2O\API::type( $type ) )
			return;

		$o2o = $o2o_type->get_connected( $post, $extra, 'abstract' );

		foreach ( $o2o->items as $item ) {

			printf( $before, '-o2o -connected' );

				if ( current_user_can( 'edit_post', $item->get_id() ) )
					echo $this->get_column_icon( get_edit_post_link( $item->get_id() ),
						NULL, $this->strings['o2o'][$constant]['title']['from'] ?? NULL );
				else
					echo $icons[$constant];

				$args = [
					'connected_direction' => 'to',
					'connected_type'      => $type,
					'connected_items'     => $item->get_id(),
				];

				echo Core\HTML::tag( 'a', [
					'href'   => WordPress\PostType::edit( $post->post_type, $args ),
					'title'  => _x( 'View all connected', 'Internal: ObjectsToObjects', 'geditorial' ),
					'target' => '_blank',
				], WordPress\Strings::trimChars( $item->get_title(), 85 ) );

				echo $this->o2o_get_meta_row( $constant, $item->o2o_id, ' &ndash; ', '' );

			echo $after;
		}
	}

	// TODO: https://github.com/scribu/wp-posts-to-posts/wiki/Related-posts
	// NOTE: DEPRECATED: use `main_shortcode()`
	// OLD: `list_p2p()`
	public function o2o_list_connected( $o2o, $constant, $context = NULL, $post = NULL, $class = '' )
	{
		if ( ! $o2o )
			return;

		if ( ! $post = WordPress\Post::get( $post ) )
			return;

		$context   = $context ?? 'o2o';
		$connected = new \WP_Query( [
			'connected_type'  => $o2o,
			'connected_items' => $post,
			'posts_per_page'  => -1,
		] );

		if ( $connected->have_posts() ) {

			echo $this->wrap_open( [ '-'.$context, $class ] );

			if ( $this->is_posttype( $constant, $post ) )
				Core\HTML::h3( $this->get_setting( self::und( $context, 'title_from' ) ), '-title -'.$context.'-from' );

			else
				Core\HTML::h3( $this->get_setting( self::und( $context, 'title_to' ) ), '-title -'.$context.'-to' );

			echo '<ul>';

			while ( $connected->have_posts() ) {
				$connected->the_post();

				echo gEditorial\ShortCode::postItem( $GLOBALS['post'], [
					'item_link'  => WordPress\Post::link( NULL, FALSE ),
					'item_after' => $this->o2o_get_meta_row( $constant, $GLOBALS['post']->o2o_id, ' &ndash; ', '' ),
				] );
			}

			echo '</ul></div>';
			wp_reset_postdata();
		}
	}
}
