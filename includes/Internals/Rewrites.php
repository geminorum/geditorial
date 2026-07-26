<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait Rewrites
{
	protected function rewrites__get_queryvar( ?string $constant_prefix = 'main' ): string
	{
		return $this->constant( self::und( $constant_prefix, 'queryvar' ) );
	}

	protected function rewrites__get_endpoint( ?string $constant_prefix = 'main', ?string $query = NULL ): string
	{
		return $this->constant( self::und( $constant_prefix, 'endpoint' ),
			$query ?? $this->rewrites__get_queryvar( $constant_prefix )
		);
	}

	// `$constant_prefix.'_endpoint' => 'endpoint',`
	// `$constant_prefix.'_queryvar' => 'endpoint',`
	// @SEE: on taxonomies: https://core.trac.wordpress.org/ticket/33728
	protected function rewrites__add_endpoint( ?string $constant_prefix = 'main' ): bool
	{
		if ( ! $query = $this->rewrites__get_queryvar( $constant_prefix ) )
			return FALSE;

		$endpoint = $this->rewrites__get_endpoint( $constant_prefix, $query );

		add_rewrite_endpoint(
			$endpoint,
			EP_PERMALINK | EP_PAGES,
			$query
		);

		$this->rewrites__endpoint_verbose_page( $constant_prefix, $query, $endpoint );

		$this->filter_append( 'query_vars', $query );

		return TRUE;
	}

	// @source `View All Posts Pages` 0.9.4 by `Erick Hitter`
	// @link https://wordpress.org/plugins/view-all-posts-pages/
	// NOTE: Extra rules needed if verbose page rules are requested.
	protected function rewrites__endpoint_verbose_page( ?string $constant_prefix = 'main', ?string $query = NULL, ?string $endpoint = NULL ): void
	{
		global $wp_rewrite;

		if ( ! $wp_rewrite->use_verbose_page_rules )
			return;

		$query    = $query    ?? $this->rewrites__get_queryvar( $constant_prefix );
		$endpoint = $endpoint ?? $this->rewrites__get_endpoint( $constant_prefix, $query );

		// Build regex.
		$regex  = substr( str_replace( $wp_rewrite->rewritecode, $wp_rewrite->rewritereplace, $wp_rewrite->permalink_structure ), 1 );
		$regex  = trailingslashit( $regex );
		$regex .= $endpoint.'/?$';

		// Build corresponding query string.
		$string = substr( str_replace( $wp_rewrite->rewritecode, $wp_rewrite->queryreplace, $wp_rewrite->permalink_structure ), 1 );
		$string = explode( '/', $string );
		$string = array_filter( $string );

		$i = 1;

		foreach ( $string as $key => $qv ) {
			$string[$key].= '$matches['.$i.']';
			$i++;
		}

		$string[] = $query.'=1';

		$string = implode( '&', $string );

		add_rewrite_rule(
			$regex,
			$wp_rewrite->index.'?'.$string,
			'top'
		);
	}

	protected function rewrites__add_tag( ?string $constant_prefix = 'main', mixed $posttypes = NULL ): string
	{
		if ( ! $query = $this->rewrites__get_queryvar( $constant_prefix ) )
			return FALSE;

		$this->filter_append( 'query_vars', $query );

		add_rewrite_tag( '%'.$query.'%', '([^&]+)' );
		add_rewrite_rule( '^'.$query.'/([^/]*)/?', 'index.php?'.$query.'=$matches[1]', 'top' );

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		else if ( \is_string( $posttypes ) )
			$posttypes = $this->constant( $posttypes, [] );

		else if ( ! $posttypes )
			return $query;

		foreach ( (array) $posttypes as $posttype )
			add_rewrite_rule(
				'^'.$posttype.'/'.$query.'/([^/]*)/?',
				'index.php?post_type='.$posttype.'&'.$query.'=$matches[1]',
				'top'
			);

		return $query;
	}

	// @REF: https://core.trac.wordpress.org/ticket/33728
	protected function rewrites__add_taxonomy_endpoint( string $taxonomy_constant, ?string $constant_prefix = 'main' ): bool
	{
		if ( ! $query = $this->rewrites__get_queryvar( $constant_prefix ) )
			return FALSE;

		if ( ! $object = WordPress\Taxonomy::object( $taxonomy_constant ) )
			return FALSE;

		$endpoint = $this->rewrites__get_endpoint( $constant_prefix, $query );

		add_rewrite_rule(
			sprintf( '%s/(.+?)/%s(/(.*))?/?$', $object->rewrite['slug'] ?? $object->name, $endpoint ),
			sprintf( 'index.php?%s=$matches[1]&%s=$matches[3]', $object->query_var ?? $object->name, $query ),
			'top',
		);

		return TRUE;
	}

	// @source https://alex.blog/2011/10/07/code-snippet-helper-class-to-add-custom-taxonomy-to-post-permalinks/
	protected function rewrites__add_taxonomy_tag( string $taxonomy_constant ): bool
	{
		if ( ! $object = WordPress\Taxonomy::object( $this->constant( $taxonomy_constant ) ) )
			return FALSE;

		$rewrite_tag = '%'.$this->constant(
			self::und( $taxonomy_constant, 'rewritetag' ),
			$object->rewrite['slug'] ?? $object->name,
		).'%';

		add_rewrite_tag(
			$rewrite_tag,  // The rewrite tag to use. Defaults to the taxonomy slug.
			'([^/]+)'   ,  // What regex to use to validate the value of the tag. Defaults to anything but a forward slash.
		);

		$callback = function ( $permalink, $post )
			use ( $rewrite_tag, $object ) {

			if ( ! Core\Text::has( $permalink, $rewrite_tag ) )
				return $permalink;

			// Get the custom taxonomy terms in use by this post
			$terms = WordPress\Taxonomy::getPostTerms( $object->name, $post );

			if ( empty( $terms ) ) {

				// If no terms are assigned to this post, use the taxonomy slug instead (can't leave the placeholder there)
				$permalink = str_replace( $rewrite_tag, $object->name, $permalink );

			} else {

				// Replace the placeholder rewrite tag with the first term's slug
				$first_term = array_shift( $terms );
				$permalink  = str_replace( $rewrite_tag, $first_term->slug, $permalink );
			}

			return $permalink;
		};

		add_filter( 'post_link',      $callback, 10, 2 );  // normal posts
		add_filter( 'post_type_link', $callback, 10, 2 );  // custom post types

		return TRUE;
	}
}
