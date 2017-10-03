<?php namespace geminorum\gEditorial\Widgets\Widgets;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
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
			'title'  => _x( 'Editorial: WP-REST Posts', 'Modules: Widgets: Widget Title', GEDITORIAL_TEXTDOMAIN ),
			'desc'   => _x( 'Displays list of posts from a public WordPress site.', 'Modules: Widgets: Widget Description', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public function widget_html( $args, $instance )
	{
		if ( empty( $instance['resource'] ) )
			return FALSE;

		$context    = isset( $instance['context'] ) ? $instance['context'] : 'wprest-posts';
		$number     = empty( $instance['number'] ) ? 10 : absint( $instance['number'] );
		$tags       = empty( $instance['tags'] ) ? FALSE : $instance['tags'];
		$categories = empty( $instance['categories'] ) ? FALSE : $instance['categories'];
		$extra      = empty( $instance['extra'] ) ? FALSE : $instance['extra'];

		// @REF: https://developer.wordpress.org/rest-api/reference/posts/
		$resource = URL::untrail( $instance['resource'] ).'/wp-json/wp/v2/posts/?per_page='.$number;

		if ( $tags )
			$resource.= '&tags='.$tags;

		if ( $categories )
			$resource.= '&categories='.$categories;

		if ( $extra )
			$resource.= '&'.$extra;

		$data = HTTP::getJSON( $resource );

		if ( empty( $data ) )
			return FALSE;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );

		echo '<ul>';

		add_filter( 'the_permalink', [ '\geminorum\\gEditorial\\WordPress\\Theme', 'restPost_the_permalink' ], 1, 2 );

		foreach ( $data as $item ) {

			Theme::restPost( $item, TRUE );

			echo '<li>';
				get_template_part( 'row', $context );
			echo '</li>';
		}

		remove_filter( 'the_permalink', [ '\geminorum\\gEditorial\\WordPress\\Theme', 'restPost_the_permalink' ], 1, 2 );

		echo '</ul>';

		$this->after_widget( $args, $instance );

		return TRUE;
	}

	public function form( $instance )
	{
		$this->before_form( $instance );

		$this->form_title( $instance );
		$this->form_title_link( $instance );

		$this->form_custom_link( $instance, '', 'resource', _x( 'Resource URL:', 'Modules: Widgets: Widget: WP-REST Posts', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_custom_code( $instance, '', 'tags', _x( 'Tag IDs:', 'Modules: Widgets: Widget: WP-REST Posts', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_custom_code( $instance, '', 'categories', _x( 'Category IDs:', 'Modules: Widgets: Widget: WP-REST Posts', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_custom_code( $instance, '', 'extra', _x( 'Extra Args:', 'Modules: Widgets: Widget: WP-REST Posts', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_number( $instance );

		$this->form_context( $instance );
		$this->form_class( $instance );

		$this->after_form( $instance );
	}

	public function update( $new, $old )
	{
		$instance = $old;

		$instance['title']      = strip_tags( $new['title'] );
		$instance['title_link'] = strip_tags( $new['title_link'] );
		$instance['context']    = strip_tags( $new['context'] );
		$instance['class']      = strip_tags( $new['class'] );

		$instance['resource']   = esc_url( $new['resource'] );
		$instance['tags']       = strip_tags( $new['tags'] );
		$instance['categories'] = strip_tags( $new['categories'] );
		$instance['extra']      = strip_tags( $new['extra'] );
		$instance['number']     = intval( $new['number'] );

		$this->flush_widget_cache();

		return $instance;
	}
}
