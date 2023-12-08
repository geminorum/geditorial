<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

trait PairedFront
{
	// excludes paired posttype from subterm archives
	protected function _hook_paired_exclude_from_subterm()
	{
		if ( ! $this->get_setting( 'subterms_support' ) )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		add_action( 'pre_get_posts', function ( &$wp_query ) use ( $constants ) {

			$subterms = $this->constant( $constants[2] );

			if ( ! $wp_query->is_main_query() || ! $wp_query->is_tax( $subterms ) )
				return;

			$primaries = WordPress\PostType::getIDs( $this->constant( $constants[0] ), [
				'tax_query' => [ [
					'taxonomy' => $subterms,
					'terms'    => [ get_queried_object_id() ],
				] ],
			] );

			if ( count( $primaries ) ) {

				if ( $not = $wp_query->get( 'post__not_in' ) )
					$primaries = Core\Arraay::prepNumeral( $not, $primaries );

				$wp_query->set( 'post__not_in', $primaries );
			}
		}, 8 );
	}

	protected function _hook_paired_override_term_link()
	{
		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		add_filter( 'term_link', function ( $link, $term, $taxonomy ) use ( $constants ) {

			if ( $taxonomy !== $this->constant( $constants[1] ) )
				return $link;

			if ( $post_id = $this->paired_get_to_post_id( $term, $constants[0], $constants[1] ) )
				return get_permalink( $post_id );

			return $link;

		}, 9, 3 );
	}
}
