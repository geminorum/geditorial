<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait CoreAdminBar
{
	/**
	 * Retrieves the default CSS class for module with extra additions.
	 * @OLD: `get_adminbar_node_class()`
	 * @OLD: `class_for_adminbar_node()`
	 *
	 * @param string|array $extra
	 * @param bool $icon_only
	 * @return string
	 */
	protected function adminbar__get_css_class( $extra = [], $icon_only = FALSE )
	{
		return Core\HTML::prepClass(
			$this->classs_base( 'adminbar', 'node', $icon_only ? 'icononly' : '' ),
			sprintf( '-%s', $this->key ),
			$extra
		);
	}

	/**
	 * Checks for required conditions for adding admin-bar nodes.
	 *
	 * @param false|string $context
	 * @param bool $check_for_mobile
	 * @return bool
	 */
	protected function adminbar__check_general( $context = NULL, $check_for_mobile = NULL )
	{
		if ( is_admin() )
			return FALSE;

		if ( ( $check_for_mobile ?? TRUE ) && WordPress\IsIt::mobile() )
			return FALSE;

		if ( FALSE === $context )
			return TRUE;

		if ( ! $this->role_can( $context ?? 'adminbar' ) )
			return FALSE;

		return TRUE;
	}

	/**
	 * Checks for required conditions for adding admin-bar nodes on singular posts.
	 *
	 * @param string|array $posttypes
	 * @param string $capability
	 * @param bool $check_for_mobile
	 * @return false|object
	 */
	protected function adminbar__check_singular_post( $posttypes = NULL, $capability = NULL, $check_for_mobile = NULL )
	{
		if ( is_admin() )
			return FALSE;

		if ( ( $check_for_mobile ?? TRUE ) && WordPress\IsIt::mobile() )
			return FALSE;

		if ( ! is_singular( $posttypes ?? $this->posttypes() ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( get_queried_object_id() ) )
			return FALSE;

		if ( ! WordPress\Post::can( $post, $capability ) )
			return FALSE;

		return $post;
	}

	/**
	 * Retrieves mark-up for given icon or module default.
	 * NOTE: used before menu texts with `.blavatar`
	 * NOTE: for icon only use `Services\Icons::adminBarMarkup()`
	 *
	 * @param false|string|array $icon
	 * @param string|array $extra
	 * @return string
	 */
	protected function adminbar__get_icon( $icon = NULL, $extra = [] )
	{
		if ( FALSE === $icon )
			return '';

		return Services\Icons::get(
			$icon ?? $this->module->icon,
			'admin-site',
			Core\HTML::attrClass( 'blavatar', $extra )
		);
	}

	/**
	 * Retrieves mark-up for given spinner or default.
	 *
	 * @param false|string $spinner
	 * @return string
	 */
	protected function adminbar__get_spinner( $spinner = NULL )
	{
		if ( FALSE === $spinner )
			return '';

		return gEditorial\Ajax::spinner( FALSE, [
			'spinner' => $spinner ?? 'fade-stagger-squares',
		] );
	}

	/**
	 * Registers admin-bar nodes for assigned terms on a post.
	 * NOTE: NOT USED YET!
	 *
	 * @param string|array $posttype
	 * @param string $taxonomy
	 * @param string $parent
	 * @param bool $link_to_edit
	 * @param false|string|array $icon
	 * @return array
	 */
	protected function adminbar__get_posttype_primary_taxonomy_nodes( $posttype, $taxonomy, $parent = NULL, $link_to_edit = FALSE, $icon = NULL )
	{
		$nodes    = [];
		$posttype = $this->constant( $posttype, $posttype );  // NOTE: can be array of post-types
		$taxonomy = $this->constant( $taxonomy, $taxonomy );  // NOTE: must be single taxonomy

		if ( ! $post = $this->adminbar__check_singular_post( NULL, 'read_post' ) )
			return $nodes;

		if ( ! $terms = WordPress\Taxonomy::getPostTerms( $taxonomy, $post ) )
			return $nodes;

		$node_id = $this->classs();
		$icon    = $this->adminbar__get_icon( $icon );
		$query   = FALSE;

		$nodes[] = [
			'parent' => $parent ?? $this->base,
			'id'     => $node_id,
			'title'  => $icon.Services\CustomTaxonomy::getLabel( $taxonomy, 'extended_label' ),
			'href'   => WordPress\Taxonomy::link( $taxonomy ),
			'meta'   => [
				'rel'   => $taxonomy,
				'class' => $this->adminbar__get_css_class(),
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
					'class' => $this->adminbar__get_css_class(),
				],
			];

		return $nodes;
	}
}
