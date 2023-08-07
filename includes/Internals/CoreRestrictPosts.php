<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Listtable;
use geminorum\gEditorial\WordPress;

trait CoreRestrictPosts
{

	/**
	 * Hooks corresponding actions/filters for `restrict_manage_posts` of WordPress.
	 * NOTE: enabled by default, use `admin_restrict` setting for disable
	 * NOTE: uses screen settings added by the plugin
	 * OLD: `_hook_screen_restrict_taxonomies()`
	 *
	 * @param  string|array $constants
	 * @param  null|bool|string $check_role
	 * @param  int $priority
	 * @return bool $hooked
	 */
	protected function corerestrictposts__hook_screen_taxonomies( $constants, $check_role = FALSE, $priority = 10 )
	{
		if ( empty( $constants ) )
			return FALSE;

		if ( ! $this->get_setting( 'admin_restrict', TRUE ) )
			return FALSE;

		if ( FALSE !== $check_role && ! $this->role_can( $check_role ?? 'reports' ) )
			return FALSE;

		$taxonomies = array_filter( $this->constants( (array) $constants ), 'taxonomy_exists' );

		if ( empty( $taxonomies ) )
			return FALSE;

		add_filter( $this->base.'_screen_restrict_taxonomies',
			function ( $pre ) use ( $taxonomies ) {
				return array_merge( $pre, $taxonomies );
			}, $priority, 2 );

		add_action( 'restrict_manage_posts',
			function ( $posttype, $which ) use ( $taxonomies ) {

				$option = get_user_option( sprintf( '%s_restrict_%s', $this->base, $posttype ) );

				foreach ( $taxonomies as $taxonomy )
					if ( FALSE === $option || in_array( $taxonomy, (array) $option, TRUE ) )
						Listtable::restrictByTaxonomy( $taxonomy );

			}, 20, 2 );

		add_action( 'parse_query',
			function ( &$query ) use ( $taxonomies ) {

				foreach ( $taxonomies as $taxonomy )
					Listtable::parseQueryTaxonomy( $query, $taxonomy );

			}, 12, 1 );

		return TRUE;
	}

	/**
	 * Hooks corresponding action for `restrict_manage_posts` of WordPress.
	 * NOTE: disabled by default, use `admin_restrict` setting for enable
	 * OLD: `do_restrict_manage_posts_authors()`
	 *
	 * @param  null|bool|string $check_role
	 * @param  int  $priority
	 * @return bool $hooked
	 */
	protected function corerestrictposts__hook_screen_authors( $check_role = FALSE, $priority = 12 )
	{
		if ( ! $this->get_setting( 'admin_restrict', FALSE ) )
			return FALSE;

		if ( FALSE !== $check_role && ! $this->role_can( $check_role ?? 'reports' ) )
			return FALSE;

		// FIXME: WTF: check for `list_users` cap!

		add_action( 'restrict_manage_posts',
			function ( $posttype, $which ) {

				Listtable::restrictByAuthor( $GLOBALS['wp_query']->get( 'author' ) ?: 0 );

			}, $priority, 2 );

		return TRUE;
	}

	/**
	 * Hooks corresponding actions/filters for `sortable_columns` of WordPress.
	 * NOTE: must add `taxonomy-{$taxonomy}` column
	 * TODO: maybe setting the default order here!
	 *
	 * @param  string $posttype
	 * @param  string|array $constants
	 * @param  int $priority
	 * @return bool $hooked
	 */
	protected function corerestrictposts__hook_sortby_taxonomies( $posttype, $constants, $priority = 10 )
	{
		if ( empty( $posttype ) || empty( $constants ) )
			return FALSE;

		$taxonomies = array_filter( $this->constants( (array) $constants ), 'taxonomy_exists' );

		if ( empty( $taxonomies ) )
			return FALSE;

		add_filter( sprintf( 'manage_edit-%s_sortable_columns', $posttype ),
			function ( $columns ) use ( $taxonomies ) {
				return array_merge( $columns,
					Core\Arraay::sameKey(
						Core\Arraay::prefixValues( $taxonomies, 'taxonomy-' ) ) );

			}, $priority, 1 );

		add_filter( 'posts_clauses',
			function ( $pieces, $wp_query ) use ( $taxonomies, $posttype ) {

				if ( ! isset( $wp_query->query['orderby'] ) )
					return $pieces;

				if ( $posttype !== WordPress\PostType::current() )
					return $pieces;

				foreach ( $taxonomies as $taxonomy )
					if ( sprintf( 'taxonomy-%s', $taxonomy ) === $wp_query->query['orderby'] )
						return Listtable::orderClausesByTaxonomy( $pieces, $wp_query, $taxonomy );

				return $pieces;
			}, 10, 2 );

		return TRUE;
	}
}
