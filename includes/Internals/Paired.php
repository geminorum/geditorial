<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

trait Paired
{

	protected function paired__hook_tweaks_column( $posttype, $priority = 10 )
	{
		if ( ! $this->_paired )
			return FALSE;

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		add_action( $this->base.'_tweaks_column_row', function( $post ) use ( $constants, $posttype ) {

			if ( ! $items = $this->paired_do_get_to_posts( $constants[0], $constants[1], $post ) )
				return;

			$paired = $this->constant( $constants[1] );
			$before = $this->wrap_open_row( $paired, '-paired-row' );
			$before.= $this->get_column_icon( FALSE, NULL, NULL, $posttype );
			$after  = '</li>';

			foreach ( $items as $term_id => $post_id ) {

				if ( ! $post = WordPress\Post::get( $post_id ) )
					continue;

				$suffix = Core\HTML::link(
					WordPress\Post::title( $post ),
					WordPress\Post::link( $post )
				);

				echo $before.WordPress\PostType::getParentTitles( $post, $suffix, TRUE ).$after;
			}

		}, $priority, 1 );
	}
}
