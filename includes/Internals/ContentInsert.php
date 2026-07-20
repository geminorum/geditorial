<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait ContentInsert
{
	// Should we insert content?
	public function is_content_insert( mixed $posttypes = '', bool $first_page = TRUE, bool $embed = FALSE ): bool
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

	protected function hook_content_insert( ?int $default_priority = NULL, ?string $setting_default = NULL ): bool
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
	/***```
	public function insert_content( string $content ): void
	{
		if ( ! $this->is_content_insert( FALSE ) )
			return;

		if ( ! $post = WordPress\Post::get() )
			return;

		$html = '';

		$this->wrap_content_insert( $html, 'before' );
	}
	```***/

	protected function wrap_content_insert( mixed $html, array|string $extra = [], string|false|null $insert = NULL ): void
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

	protected function is_page_content_insert( string|false|null $insert = NULL ): bool
	{
		$insert = $insert ?? $this->get_setting( 'insert_content', 'none' );

		switch ( $insert ) {
			case 'before': return WordPress\Isit::contentFirstPage();
			case 'after' : return WordPress\Isit::contentLastPage();
		}

		return FALSE;
	}

	protected function contentinsert__control_term_field_after( ?string $setting_key = NULL ): string
	{
		// NOTE: control term is not set!
		if ( ! $setting = $this->get_setting( $setting_key ?? 'control_termid', 0 ) )
			return '';

		return gEditorial\Settings::fieldAfterText(
			WordPress\Term::title( absint( $setting ) ),
			'code'
		);
	}

	protected function contentinsert__control_term_check( mixed $post = NULL, ?string $setting_key = NULL ): bool
	{
		// NOTE: control term is not set!
		if ( ! $setting = $this->get_setting( $setting_key ?? 'control_termid', 0 ) )
			return TRUE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		// NOTE: control term is somehow deleted or missing!
		if ( ! $term = WordPress\Term::get( absint( $setting ) ) )
			return FALSE;

		if ( ! has_term( $term, $term->taxonomy, $post ) )
			return FALSE;

		return TRUE;
	}
}
