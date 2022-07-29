<?php namespace geminorum\gEditorial\Modules\Widgets\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\HTTP;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\WordPress\Theme;

class WPRestPosts extends gEditorial\Widget
{

	const MODULE = 'widgets';
	const WIDGET = 'wprest_posts';

	public static function setup()
	{
		return [
			'title' => _x( 'Editorial: WP-REST Posts', 'Widget Title', 'geditorial-widgets' ),
			'desc'  => _x( 'Displays list of posts from a public WordPress site.', 'Widget Description', 'geditorial-widgets' ),
		];
	}

	public function widget_html( $args, $instance )
	{
		if ( empty( $instance['resource'] ) )
			return FALSE;

		$context    = isset( $instance['context'] ) ? $instance['context'] : '';
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

			echo '<div class="-list-wrap wprest-posts"><ul class="-items">';

			Theme::restLoopBefore();

			foreach ( $data as $item ) {

				$post = Theme::restPost( $item, TRUE );

				if ( $template ) {

					echo '<li>';
						load_template( $template, FALSE, [ 'widget_instance' => $instance ] );
					echo '</li>';

				} else {

					echo ShortCode::postItem( $post, [
						'item_anchor' => '',
						'trim_chars'  => empty( $instance['trim_chars'] ) ? FALSE : $instance['trim_chars'],
					] );
				}
			}

			Theme::restLoopAfter();

			wp_reset_postdata();

			echo '</ul></div>';
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
		$this->form_trim_chars( $instance );

		$this->form_custom_empty( $instance, _x( 'No posts!', 'Widget: WP-REST Posts', 'geditorial-widgets' ) );
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
			'resource'   => 'url',
			'endpoint'   => 'key',
			'tags'       => 'key',
			'categories' => 'key',
			'extra'      => 'key',
		] );
	}
}
