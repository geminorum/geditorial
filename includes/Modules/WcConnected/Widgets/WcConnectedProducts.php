<?php namespace geminorum\gEditorial\Modules\WcConnected\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class WcConnectedProducts extends gEditorial\Widget
{
	const MODULE = 'wc_connected';
	const WIDGET = 'wc_connected_products';

	public static function setup()
	{
		return [
			'title' => _x( 'Editorial: WC Connected Products', 'Widget Title', 'geditorial-wc-connected' ),
			'desc'  => _x( 'Displays connected products for currently queried post.', 'Widget Description', 'geditorial-wc-connected' ),
		];
	}

	public function widget( $args, $instance )
	{
		if ( is_singular( self::posttypes() ) )
			$this->widget_cache(
				$args,
				$instance,
				sprintf( '_queried_%d', get_queried_object_id() )
			);
	}

	public function widget_html( $args, $instance )
	{
		if ( ! $post_id = get_queried_object_id() )
			return FALSE;

		$metakey   = self::constant( 'metakey_connected', '_connected_products' );
		$connected = get_metadata( 'post', (int) $post_id, $metakey, FALSE );

		if ( empty( $connected ) )
			return FALSE;

		$shortcode = new \WC_Shortcode_Products( array_merge( $args, [
			'ids'     => implode( ',', $connected ),
			'columns' => empty( $instance['columns'] ) ? '' : $instance['columns'],
			'limit'   => empty( $instance['limit'] ) ? '-1' : $instance['limit'],
		] ), 'products' );

		if ( ! $html = $shortcode->get_content() )
			return FALSE;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );
			echo $html;
		$this->after_widget( $args, $instance );

		return TRUE;
	}

	public function form( $instance )
	{
		$this->before_form( $instance );

		$this->form_open_group( 'config' );
			$this->form_number( $instance, '', 'limit', _x( 'Limit:', 'Widget: WC Connected Products', 'geditorial-wc-connected' ) );
			$this->form_number( $instance, '', 'columns', _x( 'Columns:', 'Widget: WC Connected Products', 'geditorial-wc-connected' ) );
		$this->form_close_group();

		$this->form_open_group( 'heading' );
			$this->form_title( $instance );
			$this->form_title_link( $instance );
			$this->form_title_image( $instance );
			$this->form_class( $instance );
			// $this->form_context( $instance );
		$this->form_close_group();

		$this->form_open_group( 'customs' );
			$this->form_open_widget( $instance );
			$this->form_after_title( $instance );
			$this->form_close_widget( $instance );
		$this->form_close_group();

		$this->after_form( $instance );
	}

	public function update( $new, $old )
	{
		$this->flush_widget_cache();

		return $this->handle_update( $new, $old, [], [
			'limit'   => 'digit',
			'columns' => 'digit',
		] );
	}
}
