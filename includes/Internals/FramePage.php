<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\WordPress;

trait FramePage
{

	protected function framepage_get_mainlink( $post, $atts = [] )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$args = self::atts( [
			'context'      => 'mainbutton',
			'link_context' => 'framepage',
			'posttype'     => $post->post_type,
			'target'       => 'none',
			'maxwidth'     => '95%', // '1100px',
			'link'         => NULL,
			'name'         => NULL,
			'title'        => NULL,
			'text'         => NULL,
			'icon'         => NULL,
			'extra'        => [],
			'data'         => [],
		], $atts );

		$link = $args['link'] ?? $this->get_adminpage_url( TRUE, [
			'linked'   => $post->ID,
			'target'   => $args['target'] ?? 'none',
			'noheader' => 1,
		], $args['link_context'] ?? 'framepage' );

		$name  = Helper::getPostTypeLabel( $args['posttype'], 'singular_name' );
		$title = $args['title'] ?? $this->get_string( $args['context'].'_title', $args['posttype'], 'metabox', NULL );
		$text  = $args['text'] ?? $this->get_string( $args['context'].'_text', $args['posttype'], 'metabox', $args['name'] ?? $name );
		$class = [ 'do-colorbox-iframe' ];

		if ( in_array( $args['context'], [ 'mainbutton' ], TRUE ) )
			$class = array_merge( $class, [
				'button',
				'-button',
				'-button-full',
				'-button-icon',
				'-mainbutton',
			] );

		return Core\HTML::tag( 'a', [
			'href'   => $link,
			'title'  => $title ? sprintf( $title, WordPress\Post::title( $post, $name ), $name ) : FALSE,
			'class'  => array_merge( $class, $args['extra'] ),
			'target' => '_blank',
			'data'   => array_merge( [
				'module'    => $this->key,
				'linked'    => $post->ID,
				'target'    => $args['target'] ?? 'none',
				'max-width' => $args['maxwidth'],
			], $args['data'] ),
		], sprintf( $text, Helper::getIcon( $args['icon'] ?? $this->module->icon ), $name ) );
	}
}
