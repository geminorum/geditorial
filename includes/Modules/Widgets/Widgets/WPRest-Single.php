<?php namespace geminorum\gEditorial\Widgets\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\HTTP;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\WordPress\Theme;

class WPRestSingle extends gEditorial\Widget
{

	const MODULE = 'widgets';

	public static function setup()
	{
		return [
			'module' => 'widgets',
			'name'   => 'wprest_single',
			'class'  => 'wprest-single',
			'title'  => _x( 'Editorial: WP-REST Single', 'Widget Title', 'geditorial-widgets' ),
			'desc'   => _x( 'Displays single post from a public WordPress site.', 'Widget Description', 'geditorial-widgets' ),
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
		$resource = URL::untrail( $instance['resource'] ).'/wp-json/wp/v2/'.$endpoint.'/'.$instance['post_id'];

		if ( $extra )
			$resource.= '?'.$extra;

		$data = HTTP::getJSON( $resource, [], FALSE );

		if ( empty( $data ) && ! $empty )
			return TRUE;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );

		if ( empty( $data ) ) {

			HTML::desc( $empty, TRUE, '-empty' );

		} else {

			echo '<div class="-post-wrap wprest-single">';
			Theme::restLoopBefore();

			$template = locate_template( Theme::getPart( 'row', $context, FALSE ), FALSE );
			$post     = Theme::restPost( $data, TRUE );

			if ( $template )
				load_template( $template, FALSE, [ 'widget_instance' => $instance ] );

			else
				echo ShortCode::postItem( $post, [
					'item_tag'    => '',
					'item_anchor' => '',
				] );

			Theme::restLoopAfter();
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

		$this->after_form( $instance );
	}

	public function update( $new, $old )
	{
		$instance = $old;

		$instance['title']       = sanitize_text_field( $new['title'] );
		$instance['title_link']  = strip_tags( $new['title_link'] );
		$instance['title_image'] = strip_tags( $new['title_image'] );
		$instance['context']     = strip_tags( $new['context'] );
		$instance['class']       = strip_tags( $new['class'] );
		$instance['empty']       = Helper::kses( $new['empty'], 'text' );

		$instance['resource'] = esc_url( $new['resource'] );
		$instance['endpoint'] = strip_tags( $new['endpoint'] );
		$instance['extra']    = strip_tags( $new['extra'] );
		$instance['post_id']  = (int) $new['post_id'];

		$this->flush_widget_cache();

		return $instance;
	}
}
