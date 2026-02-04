<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait CoreMenuPage
{
	/**
	 * Slugs for $parent_slug (first parameter)
	 *
	 * Dashboard: `index.php`
	 * Posts: `edit.php`
	 * Media: `upload.php`
	 * Pages: `edit.php?post_type=page`
	 * Comments: `edit-comments.php`
	 * Custom Post Types: `edit.php?post_type=your_post_type`
	 * Appearance: `themes.php`
	 * Plugins: `plugins.php`
	 * Users: `users.php`
	 * Tools: `tools.php`
	 * Settings: `options-general.php`
	 * Network Settings: `settings.php`
	 */

	protected function _hook_menu_posttype( $constant, $parent_slug = 'index.php', $context = 'adminpage' )
	{
		if ( ! $posttype = get_post_type_object( $this->constant( $constant ) ) )
			return FALSE;

		$this->screens[$constant] = add_submenu_page(
			$parent_slug,
			Core\HTML::escape( $this->get_string( 'page_title', $constant, $context, $posttype->labels->all_items ) ),
			Core\HTML::escape( $this->get_string( 'menu_title', $constant, $context, $posttype->labels->menu_name ) ),
			$posttype->cap->edit_posts,
			'edit.php?post_type='.$posttype->name
		);

		return $this->screens[$constant];
	}

	// $parent_slug options: `options-general.php`, `users.php`
	// also: `$this->_hook_parentfile_for_optionsgeneralphp();`
	// also: `$this->_hook_parentfile_for_usersphp();`
	protected function _hook_menu_taxonomy( $constant, $parent_slug = 'index.php', $context = 'submenu' )
	{
		if ( ! $taxonomy = get_taxonomy( $this->constant( $constant ) ) )
			return FALSE;

		$this->screens[$constant] = add_submenu_page(
			$parent_slug,
			Core\HTML::escape( $this->get_string( 'page_title', $constant, $context, $taxonomy->labels->name ) ),
			Core\HTML::escape( $this->get_string( 'menu_title', $constant, $context, $taxonomy->labels->menu_name ) ),
			$taxonomy->cap->manage_terms,
			'edit-tags.php?taxonomy='.$taxonomy->name
		);

		return $this->screens[$constant];
	}

	protected function _hook_wp_submenu_page( $context, $parent_slug, $page_title, $menu_title = NULL, $capability = NULL, $menu_slug = '', $callback = '', $position = NULL )
	{
		if ( ! $context )
			return FALSE;

		$default_callback = [ $this, sprintf( 'admin_%s_page', $context ) ];

		$this->screens[$context] = add_submenu_page(
			$parent_slug,
			$page_title,
			( $menu_title ?? $page_title ),
			( $capability ?? ( $this->caps[$context] ?? 'manage_options' ) ),
			( empty( $menu_slug ) ? $this->classs_base( $context ) : $menu_slug ),
			( empty( $callback ) ? ( is_callable( $default_callback ) ? $default_callback : '' ) : $callback ),
			( $position ?? ( $this->positions[$context] ?? NULL ) )
		);

		if ( $this->screens[$context] )
			add_action(
				sprintf( 'load-%s', $this->screens[$context] ),
				[ $this, sprintf( 'admin_%s_load', $context ) ],
				10,
				0
			);

		return $this->screens[$context];
	}

	// NOTE: hack to keep the sub-menu only on primary paired post-type
	// for hiding the menu just set `show_in_menu` to `FALSE` on taxonomy arguments
	protected function remove_taxonomy_submenu( $taxonomies, $posttypes = NULL )
	{
		if ( ! $taxonomies )
			return;

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		foreach ( (array) $taxonomies as $taxonomy )
			foreach ( $posttypes as $posttype )
				remove_submenu_page(
					'post' == $posttype ? 'edit.php' : 'edit.php?post_type='.$posttype,
					'post' == $posttype ? 'edit-tags.php?taxonomy='.$taxonomy : 'edit-tags.php?taxonomy='.$taxonomy.'&amp;post_type='.$posttype
				);
	}

	protected function _hook_parentfile_for_usersphp()
	{
		$this->filter_string( 'parent_file', current_user_can( 'list_users' ) ? 'users.php' : 'profile.php' );
	}

	protected function _hook_parentfile_for_optionsgeneralphp()
	{
		$this->filter_string( 'parent_file', current_user_can( 'manage_options' ) ? 'options-general.php' : 'tools.php' );
	}

	/**
	 * Hacks WordPress core for users with no `create_posts` cap
	 * @REF: https://core.trac.wordpress.org/ticket/22895
	 * @REF: https://core.trac.wordpress.org/ticket/29714
	 * @REF: https://core.trac.wordpress.org/ticket/56280
	 * @REF: https://wordpress.stackexchange.com/a/178059
	 * @REF: https://herbmiller.me/wordpress-capabilities-restrict-add-new-allowing-edit/
	 * @SEE: https://gist.github.com/luistar15/333a25888e8804fd17490815a74ecc21
	 * @SEE: https://github.com/WordPress/wordpress-develop/pull/3024
	 *
	 * @param string|object $posttype
	 * @return bool
	 */
	protected function _hack_adminmenu_no_create_posts( $posttype )
	{
		if ( ! $object = WordPress\PostType::object( $posttype ) )
			return FALSE;

		add_submenu_page(
			'edit.php?post_type='.$object->name,
			'',
			'',
			$object->cap->edit_posts,
			$this->classs( $object->name )
		);

		add_filter( 'add_menu_classes',
			function ( $menu ) use ( $object ) {
				remove_submenu_page( 'edit.php?post_type='.$object->name, $this->classs( $object->name ) );
				return $menu;
			} );
	}
}
