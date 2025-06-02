<?php namespace geminorum\gEditorial\Modules\Widgets\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class WPRestSingle extends gEditorial\Widget
{

	const MODULE = 'widgets';
	const WIDGET = 'wprest_single';

	public static function setup()
	{
		return [
			'title' => _x( 'Editorial: WP-REST Single', 'Widget Title', 'geditorial-widgets' ),
			'desc'  => _x( 'Displays single post from a public WordPress site.', 'Widget Description', 'geditorial-widgets' ),
		];
	}

	public function widget_html( $args, $instance )
	{
		if ( empty( $instance['resource'] ) || empty( $instance['post_id'] ) )
			return FALSE;

		$context  = isset( $instance['context'] ) ? $instance['context'] : '';
		$endpoint = empty( $instance['endpoint'] ) ? 'posts' : $instance['endpoint'];
		$extra    = empty( $instance['extra'] ) ? FALSE : $instance['extra'];
		$empty    = empty( $instance['empty'] ) ? FALSE : $instance['empty'];

		// @REF: https://developer.wordpress.org/rest-api/reference/posts/
		$resource = Core\URL::untrail( $instance['resource'] ).'/wp-json/wp/v2/'.$endpoint.'/'.$instance['post_id'];

		if ( $extra )
			$resource.= '?'.$extra;

		$data = Core\HTTP::getJSON( $resource, [], FALSE );

		if ( empty( $data ) && ! $empty )
			return TRUE;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );

		if ( empty( $data ) ) {

			Core\HTML::desc( $empty, TRUE, '-empty' );

		} else {

			echo '<div class="-post-wrap wprest-single">';
			WordPress\Theme::restLoopBefore();

			$template = locate_template( WordPress\Theme::getPart( 'row', $context, FALSE ), FALSE );
			$post     = WordPress\Theme::restPost( $data, TRUE );

			if ( $template )
				load_template( $template, FALSE, [ 'widget_instance' => $instance ] );

			else
				echo gEditorial\ShortCode::postItem( $post, [
					'item_tag'    => '',
					'item_anchor' => '',
				] );

			WordPress\Theme::restLoopAfter();
			wp_reset_postdata();
			echo '</div>';
		}

		$this->after_widget( $args, $instance );

		return TRUE;
	}

	public function form( $instance )
	{
		$this->before_form( $instance );

		$this->form_title( $instance );
		$this->form_title_link( $instance );
		$this->form_title_image( $instance );

		$this->form_custom_link( $instance, '', 'resource', _x( 'Resource URL:', 'Widget: WP-REST Single', 'geditorial-widgets' ) );
		$this->form_custom_code( $instance, 'posts', 'endpoint', _x( 'Endpoint:', 'Widget: WP-REST Single', 'geditorial-widgets' ) );
		$this->form_custom_code( $instance, '', 'extra', _x( 'Extra Args:', 'Widget: WP-REST Single', 'geditorial-widgets' ) );
		$this->form_number( $instance, '', 'post_id', _x( 'Post ID:', 'Widget: WP-REST Single', 'geditorial-widgets' ) );

		$this->form_custom_empty( $instance, _x( 'No post!', 'Widget: WP-REST Single', 'geditorial-widgets' ) );
		$this->form_context( $instance );
		$this->form_class( $instance );

		echo '<div class="-group">';
		$this->form_open_widget( $instance );
		$this->form_after_title( $instance );
		$this->form_close_widget( $instance );
		echo '</div>';

		$this->after_form( $instance );
	}

	public function update( $new, $old )
	{
		$this->flush_widget_cache();

		return $this->handle_update( $new, $old, [], [
			'resource' => 'url',
			'endpoint' => 'key',
			'extra'    => 'key',
		] );
	}
}
