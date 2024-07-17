<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\WordPress;

trait FramePage
{

	protected function framepage_get_mainlink_for_post( $post, $atts = [] )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$args = self::atts( [
			'context'      => 'mainbutton',
			'link_context' => 'framepage',
			'refkey'       => 'linked',
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
			$args['refkey'] => $post->ID,
			'target'        => $args['target'] ?? 'none',
			'noheader'      => 1,
		], $args['link_context'] ?? 'framepage' );

		$name  = Helper::getPostTypeLabel( $args['posttype'], 'singular_name' );
		$title = $args['title'] ?? $this->get_string( $args['context'].'_title', $args['posttype'], 'metabox', NULL );
		$text  = $args['text'] ?? $this->get_string( $args['context'].'_text', $args['posttype'], 'metabox', $args['name'] ?? $name );
		$class = [ 'do-colorbox-iframe' ];

		if ( in_array( $args['context'], [ 'mainbutton' ], TRUE ) )
			$class = array_merge( $class, [
				'button',
				'-button',
				// '-button-full',
				'-button-icon',
				'-mainbutton',
			] );

		return Core\HTML::tag( 'a', [
			'href'   => $link,
			'title'  => $title ? sprintf( $title, WordPress\Post::title( $post, $name ), $name ) : FALSE,
			'class'  => array_merge( $class, (array) $args['extra'] ),
			'target' => '_blank',
			'data'   => array_merge( [
				'module'        => $this->key,
				$args['refkey'] => $post->ID,
				'target'        => $args['target'] ?? 'none',
				'max-width'     => $args['maxwidth'],
			], $args['data'] ),
		], sprintf( $text, Helper::getIcon( $args['icon'] ?? $this->module->icon ), $name ) );
	}

	protected function framepage_get_mainlink_for_term( $term, $atts = [] )
	{
		if ( ! $term = WordPress\Term::get( $term ) )
			return FALSE;

		$args = self::atts( [
			'context'      => 'mainbutton',
			'link_context' => 'framepage',
			'refkey'       => 'linked',
			'taxonomy'     => $term->taxonomy,
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
			$args['refkey'] => $term->term_id,
			'target'        => $args['target'] ?? 'none',
			'noheader'      => 1,
		], $args['link_context'] ?? 'framepage' );

		$name  = Helper::getTaxonomyLabel( $args['taxonomy'], 'singular_name' );
		$title = $args['title'] ?? $this->get_string( $args['context'].'_title', $args['taxonomy'], 'metabox', NULL );
		$text  = $args['text'] ?? $this->get_string( $args['context'].'_text', $args['taxonomy'], 'metabox', $args['name'] ?? $name );
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
			'title'  => $title ? sprintf( $title, WordPress\Term::title( $term, $name ), $name ) : FALSE,
			'class'  => array_merge( $class, (array) $args['extra'] ),
			'target' => '_blank',
			'data'   => array_merge( [
				'module'        => $this->key,
				$args['refkey'] => $term->term_id,
				'target'        => $args['target'] ?? 'none',
				'max-width'     => $args['maxwidth'],
			], $args['data'] ),
		], sprintf( $text, Helper::getIcon( $args['icon'] ?? $this->module->icon ), $name ) );
	}
}
