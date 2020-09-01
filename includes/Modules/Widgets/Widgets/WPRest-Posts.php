<?php namespace geminorum\gEditorial\Widgets\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\HTTP;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\WordPress\Theme;

class WPRestPosts extends gEditorial\Widget
{

	const MODULE = 'widgets';

	public static function setup()
	{
		return [
			'module' => 'widgets',
			'name'   => 'wprest_posts',
			'class'  => 'wprest-posts',
			'title'  => _x( 'Editorial: WP-REST Posts', 'Widget Title', 'geditorial-widgets' ),
			'desc'   => _x( 'Displays list of posts from a public WordPress site.', 'Widget Description', 'geditorial-widgets' ),
		];
	}

	public function widget_html( $args, $instance )
	{
		if ( empty( $instance['resource'] ) )
			return FALSE;

		$context    = isset( $instance['context'] ) ? $instance['context'] : 'wprest-posts';
		$number     = empty( $instance['number'] ) ? 10 : absint( $instance['number'] );
		$endpoint   = empty( $instance['endpoint'] ) ? 'posts' : $instance['endpoint'];
		$tags       = empty( $instance['tags'] ) ? FALSE : $instance['tags'];
		$categories = empty( $instance['categories'] ) ? FALSE : $instance['categories'];
		$extra      = empty( $instance['extra'] ) ? FALSE : $instance['extra'];
		$empty      = empty( $instance['empty'] ) ? FALSE : $instance['empty'];

		// @REF: https://developer.wordpress.org/rest-api/reference/posts/
		$resource = URL::untrail( $instance['resource'] ).'/wp-json/wp/v2/'.$endpoint.'/?per_page='.$number;

		if ( $tags )
			$resource.= '&tags='.$tags;

		if ( $categories )
			$resource.= '&categories='.$categories;

		if ( $extra )
			$resource.= '&'.$extra;

		$data = HTTP::getJSON( $resource, [], FALSE );

		if ( empty( $data ) && ! $empty )
			return TRUE;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );

		if ( empty( $data ) ) {

			HTML::desc( $empty, TRUE, '-empty' );

		} else {

			$template = locate_template( Theme::getPart( 'row', $context, FALSE ) );

			echo '<ul>';

			Theme::restLoopBefore();

			foreach ( $data as $item ) {

				$post = Theme::restPost( $item, TRUE );

				if ( $template ) {

					echo '<li>';
						load_template( $template, FALSE );
					echo '</li>';

				} else {

					echo ShortCode::postItem( $post, [
						'item_anchor' => '',
					] );
				}
			}

			Theme::restLoopAfter();

			wp_reset_postdata();

			echo '</ul>';
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

		$this->form_custom_link( $instance, '', 'resource', _x( 'Resource URL:', 'Widget: WP-REST Posts', 'geditorial-widgets' ) );
		$this->form_custom_code( $instance, 'posts', 'endpoint', _x( 'Endpoint:', 'Widget: WP-REST Posts', 'geditorial-widgets' ) );
		$this->form_custom_code( $instance, '', 'tags', _x( 'Tag IDs:', 'Widget: WP-REST Posts', 'geditorial-widgets' ) );
		$this->form_custom_code( $instance, '', 'categories', _x( 'Category IDs:', 'Widget: WP-REST Posts', 'geditorial-widgets' ) );
		$this->form_custom_code( $instance, '', 'extra', _x( 'Extra Args:', 'Widget: WP-REST Posts', 'geditorial-widgets' ) );
		$this->form_number( $instance );

		$this->form_custom_empty( $instance, _x( 'No posts!', 'Widget: WP-REST Posts', 'geditorial-widgets' ) );
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
		$instance['empty']       = wp_kses_post( $new['empty'] ); // FIXME: use `Helper::kses()`

		$instance['resource']   = esc_url( $new['resource'] );
		$instance['endpoint']   = strip_tags( $new['endpoint'] );
		$instance['tags']       = strip_tags( $new['tags'] );
		$instance['categories'] = strip_tags( $new['categories'] );
		$instance['extra']      = strip_tags( $new['extra'] );
		$instance['number']     = intval( $new['number'] );

		$this->flush_widget_cache();

		return $instance;
	}
}
