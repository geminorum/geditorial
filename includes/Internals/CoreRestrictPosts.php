<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
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

		$this->filter_append( $this->hook_base( 'screen_restrict_taxonomies' ), $taxonomies, $priority );

		add_action( 'restrict_manage_posts',
			function ( $posttype, $which ) use ( $taxonomies ) {

				$option = get_user_option( $this->hook_base( 'restrict', $posttype ) );

				foreach ( $taxonomies as $taxonomy )
					if ( FALSE === $option || in_array( $taxonomy, (array) $option, TRUE ) )
						Listtable::restrictByTaxonomy( $taxonomy );

			}, $priority, 2 );

		add_action( 'parse_query',
			static function ( &$query ) use ( $taxonomies ) {

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
			static function ( $posttype, $which ) {

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
			static function ( $columns ) use ( $taxonomies ) {
				return array_merge( $columns,
					Core\Arraay::sameKey(
						Core\Arraay::prefixValues( $taxonomies, 'taxonomy-' ) ) );

			}, $priority, 1 );

		add_filter( 'posts_clauses',
			static function ( $pieces, $wp_query ) use ( $taxonomies, $posttype ) {

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

	/**
	 * Hooks post parent into core query.
	 *
	 * @param  string      $constant
	 * @param  null|string $query_var
	 * @return bool        $hooked
	 */
	protected function corerestrictposts__hook_parsequery_for_post_parent( $constant, $query_var = NULL )
	{
		$posttype  = $this->constant( $constant, $constant );
		$query_var = $query_var ?? sprintf( '%s_parent', $posttype );

		$this->filter_append( 'query_vars', $query_var );

		add_action( 'parse_query',
			static function ( &$query ) use ( $query_var ) {

				if ( ! isset( $query->query_vars[$query_var] ) )
					return;

				// overrides!
				$query->query_vars['post_parent'] = $query->query_vars[$query_var];

			}, 12, 1 );

		return TRUE;
	}

	/**
	 * Hooks column row for post children information.
	 * NOTE: the post children are from a dffrent posttype
	 *
	 * @param  string            $constant
	 * @param  null|string       $icon
	 * @param  null|string       $module
	 * @param  null|false|string $empty
	 * @param  int               $priority
	 * @return bool              $hooked
	 */
	protected function corerestrictposts__hook_columnrow_for_post_children( $parent_type, $constant, $icon = NULL, $module = NULL, $empty = NULL, $priority = 10 )
	{
		$posttype = $this->constant( $constant, $constant );
		$can      = WordPress\PostType::can( $posttype, 'edit_posts' );
		$link     = $can ? WordPress\PostType::edit( $posttype ) : FALSE;
		$edit     = $this->get_column_icon( $link, $icon, NULL, $posttype );
		$notice   = $empty ?? $this->get_string( 'post_children_empty', $constant, 'misc', gEditorial()->na() );
		$status   = WordPress\Status::available( $posttype );

		add_action( $this->hook_base( $module ?? 'tweaks', 'column_row', $parent_type ),
			function ( $post, $before, $after, $module ) use ( $constant, $posttype, $notice, $can, $edit, $status ) {

				$children = get_children( [
					'post_parent' => $post->ID,
					'post_type'   => $posttype,
					'post_status' => $status,
					'fields'      => 'ids',
				] );

				if ( ( ! $count = count( $children ) ) && ! $notice )
					return;

				printf( $before, '-post-children -type-'.$posttype.( $count ? ' -has-children' : ' -has-not-children' ) );

				if ( $count = count( $children ) )
					echo $edit.Core\HTML::tag( $can ? 'a' : 'span', [
						'href'  => $can ? WordPress\PostType::edit( $posttype, [
							sprintf( '%s_parent', $posttype ) => $post->ID,
						] ) : FALSE,
						'class' => '-counted',
					], $this->nooped_count( sprintf( '%s_count', $constant ), $count ) );

				else
					echo $edit.Core\HTML::tag( 'span', [ 'class' => '-na -empty-parent-post' ], $notice );

				echo $after;

			}, $priority, 4 );

		return TRUE;
	}

	/**
	 * Hooks column row for parent post information.
	 * NOTE: the parent post is from a dffrent posttype
	 *
	 * @param  string            $posttype
	 * @param  null|string       $icon
	 * @param  null|string       $module
	 * @param  null|false|string $empty
	 * @param  int               $priority
	 * @return bool              $hooked
	 */
	protected function corerestrictposts__hook_columnrow_for_parent_post( $posttype, $icon = NULL, $module = NULL, $empty = NULL, $priority = 10 )
	{
		$can    = WordPress\PostType::can( $posttype, 'edit_posts' );
		$link   = $can ? WordPress\PostType::edit( $posttype ) : FALSE;
		$edit   = $this->get_column_icon( $link, $icon, NULL, $posttype );
		$notice = $empty ?? $this->get_string( 'parent_post_empty', $posttype, 'misc', gEditorial()->na() );

		add_action( $this->hook_base( $module ?? 'tweaks', 'column_row', $posttype ),
			static function ( $post, $before, $after, $module ) use ( $posttype, $notice, $can, $edit ) {

				if ( ! $post->post_parent && ! $notice )
					return;

				printf( $before, '-parent-post -type-'.$posttype.( $post->post_parent ? ' -has-parent-post' : ' -has-not-parent-post' ) );

					if ( $post->post_parent )
						echo $edit.Helper::getPostTitleRow( $post->post_parent, $can ? 'edit' : FALSE, FALSE, 'posttype' );

					else
						echo $edit.Core\HTML::tag( 'span', [ 'class' => '-na -empty-parent-post' ], $notice );

				echo $after;

			}, $priority, 4 );

		return TRUE;
	}
}
