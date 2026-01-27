<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait CoreAdminBar
{
	/**
	 * Registers admin-bar nodes for assigned terms on a post.
	 * NOTE: NOT USED YET!
	 *
	 * @param string|array $posttype
	 * @param string $taxonomy
	 * @param string $parent
	 * @param bool $link_to_edit
	 * @return array
	 */
	protected function adminbar__get_posttype_primary_taxonomy_nodes( $posttype, $taxonomy, $parent = NULL, $link_to_edit = FALSE )
	{
		$nodes    = [];
		$posttype = $this->constant( $posttype, $posttype );  // NOTE: can be array of post-types
		$taxonomy = $this->constant( $taxonomy, $taxonomy );  // NOTE: must be single taxonomy

		if ( is_admin() || ! is_singular( $posttype ) || WordPress\IsIt::mobile() )
			return $nodes;

		$post_id = get_queried_object_id();
		$node_id = $this->classs();
		$query   = FALSE;

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return $nodes;

		if ( ! $terms = WordPress\Taxonomy::getPostTerms( $taxonomy, $post_id ) )
			return $nodes;

		$nodes[] = [
			'parent' => $parent ?? $this->base,
			'id'     => $node_id,
			'title'  => Services\CustomTaxonomy::getLabel( $taxonomy, 'extended_label' ),
			'href'   => WordPress\Taxonomy::link( $taxonomy ),
			'meta'   => [
				'rel'   => $taxonomy,
				'class' => $this->class_for_adminbar_node(),
			],
		];

		if ( $link_to_edit && ! is_array( $posttype ) && WordPress\PostType::can( $posttype, 'edit_posts' ) )
			$query = WordPress\Taxonomy::queryVar( $taxonomy );

		foreach ( $terms as $term )
			$nodes[] = [
				'parent' => $node_id,
				'id'     => $this->classs( 'term', $term->term_id ),
				'title'  => WordPress\Term::title( $term ),
				'href'   => $query
					? WordPress\Term::link( $term )
					: WordPress\PostType::edit( $posttype, [ $query => rawurldecode( $term->slug ) ] ),
				'meta' => [
					'rel'   => $term->term_id,
					'class' => $this->class_for_adminbar_node(),
				],
			];

		return $nodes;
	}
}
