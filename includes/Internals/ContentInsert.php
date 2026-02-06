<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait ContentInsert
{
	// Should we insert content?
	public function is_content_insert( $posttypes = '', $first_page = TRUE, $embed = FALSE )
	{
		if ( ! $embed && is_embed() )
			return FALSE;

		if ( ! is_main_query() )
			return FALSE;

		if ( ! in_the_loop() )
			return FALSE;

		if ( $first_page && 1 != $GLOBALS['page'] )
			return FALSE;

		if ( FALSE === $posttypes )
			return TRUE;

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		else if ( $posttypes && ! is_array( $posttypes ) )
			$posttypes = $this->constant( $posttypes, $posttypes );

		return is_singular( $posttypes );
	}

	protected function hook_insert_content( $default_priority = NULL, $setting_default = NULL )
	{
		if ( 'none' === ( $insert = $this->get_setting( 'insert_content', $setting_default ?? 'none' ) ) )
			return FALSE;

		return add_action(
			$this->hook_base( 'content', $insert ),
			[ $this, 'insert_content' ],
			$this->get_setting( 'insert_priority', $default_priority ?? 50 )
		);
	}

	// TODO: insert content settings for each post-type
	// NOTE: Example Usage
	/***
	public function insert_content( $content )
	{
		if ( ! $this->is_content_insert( FALSE ) )
			return;

		if ( ! $post = WordPress\Post::get() )
			return;

		$html = '';

		$this->wrap_content_insert( $html, 'before' );
	}
	***/

	protected function wrap_content_insert( $html, $extra = [], $insert = NULL )
	{
		if ( ! $html )
			return;

		$insert = $insert ?? $this->get_setting( 'insert_content', 'none' );
		$margin = 'none' !== $insert ? ( 'after' === $insert ? 'mt-3' : 'mb-3' ) : 'm-0'; // NOTE: BS compatible CSS classes

		echo $this->wrap( $html, Core\HTML::attrClass(
			sprintf( '-%s', $insert ),
			$margin,
			$extra
		) );
	}

	protected function is_page_content_insert( $insert = NULL )
	{
		$insert = $insert ?? $this->get_setting( 'insert_content', 'none' );

		switch ( $insert ) {
			case 'before': return WordPress\Isit::contentFirstPage();
			case 'after' : return WordPress\Isit::contentLastPage();
		}

		return FALSE;
	}
}
