<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait ContentReplace
{

	// NOTE: better to hook in `template_redirect` after `is_singular()` check
	protected function contentreplace__autolink_terms( $constant, $setting = NULL, $only_parents = TRUE )
	{
		if ( ! $this->get_setting( $setting ?? 'autolink_terms' ) )
			return FALSE;

		if ( ! $taxonomy = $this->constant( $constant, $constant ) )
			return FALSE;

		if ( empty( $this->cache['contentreplace'][$taxonomy] ) ) {

			$terms = get_terms( [
				'taxonomy'   => $taxonomy,
				'hide_empty' => FALSE,
				'parent'     => $only_parents ? 0 : '',
				'orderby'    => 'none',

				'suppress_filter'        => TRUE,
				// 'update_term_meta_cache' => FALSE,
			] );

			if ( ! $terms || is_wp_error( $terms ) )
				return FALSE;

			$this->cache['contentreplace'][$taxonomy] = $terms;

		} else {

			$terms = $this->cache['contentreplace'][$taxonomy];
		}

		add_filter( 'the_content',
			function ( $content ) use ( $terms ) {

				if ( WordPress\Strings::isEmpty( $content ) )
					return $content;

				foreach ( $terms as $term ) {

					if ( ! $pattern = $this->contentreplace__get_pattern_by_term( $term ) )
						continue;

					$content = preg_replace_callback(
						'/'.$pattern.'/miu',
						function ( $matched ) use ( $term ) {
							return $matched[1].
								$this->contentreplace__callback_for_term( $matched[2], $term )
								.$matched[3];
						},
						$content
					);
				}

				return $content;

			}, 9, 1 );

		return count( $terms );
	}

	protected function contentreplace__callback_for_term( $matched, $term )
	{
		return Core\HTML::tag( 'a', [
			'href'           => WordPress\Term::link( $term ),
			'title'          => Core\Text::stripTags( $term->description ) ?: FALSE,
			'class'          => sprintf( '-%s-term', $term->taxonomy ),
			'data-term'      => WordPress\Term::title( $term ),
			'data-termid'    => $term->term_id,
			'data-bs-toggle' => 'tooltip',
		], $matched );
	}

	protected function contentreplace__get_pattern_by_term( $term, $skip_links = TRUE )
	{
		if ( empty( $term->name ) )
			return FALSE;

		$words = apply_filters(
			$this->hook_base( 'contentreplace', 'term', 'words' ),
			[
				Core\Text::trimQuotes( $term->name ),
			],
			$term,
		);

		if ( empty( $words ) )
			return FALSE;

		$pattern = '(^|[^\\w\\-])('.implode( '|', array_map( 'preg_quote', $words ) ).')($|[^\\w\\-])';

		if ( $skip_links )
			$pattern = '<a[^>]*>.*?<\/a\s*>(*SKIP)(*FAIL)|'.$pattern;

		return $pattern;
	}
}
