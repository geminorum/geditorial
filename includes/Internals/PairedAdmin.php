<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Listtable;
use geminorum\gEditorial\WordPress;

trait PairedAdmin
{

	/**
	 * Hooks corresponding actions/filters for `restrict_manage_posts` of WordPress.
	 * NOTE: enabled by default, use `admin_restrict` setting for disable
	 * NOTE: uses screen settings added by the plugin
	 * OLD: `_hook_screen_restrict_paired()`
	 *
	 * @param  null|bool|string $check_role
	 * @param  int $priority
	 * @return bool $hooked
	 */
	protected function paired__hook_screen_restrictposts( $check_role = FALSE, $priority = 10 )
	{
		if ( ! $this->_paired )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		if ( ! $this->get_setting( 'admin_restrict', TRUE ) )
			return FALSE;

		if ( FALSE !== $check_role && ! $this->role_can( $check_role ?? 'reports' ) )
			return FALSE;

		else if ( ! WordPress\PostType::can( $this->constant( $constants[0] ), 'read' ) )
			return FALSE;

		$this->filter_append( $this->hook_base( 'screen_restrict_taxonomies' ), $this->constant( $constants[1] ), $priority );

		add_action( 'restrict_manage_posts',
			function ( $posttype, $which ) use ( $constants ) {

				$option   = get_user_option( sprintf( '%s_restrict_%s', $this->base, $posttype ) );
				$taxonomy = $this->constant( $constants[1] );

				if ( FALSE === $option || in_array( $taxonomy, (array) $option, TRUE ) )
					Listtable::restrictByTaxonomy( $taxonomy, $this->constant( $constants[0] ) );

			}, 20, 2 );

		add_action( 'parse_query',
			function ( &$query ) use ( $constants ) {

				Listtable::parseQueryTaxonomy( $query, $this->constant( $constants[1] ) );

			}, 12, 1 );

		return TRUE;
	}

	// TODO: check capability
	// OLD: `_hook_paired_tweaks_column_attr()`
	protected function pairedadmin__hook_tweaks_column_connected( $supported = NULL )
	{
		if ( ! $this->_paired )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		if ( is_null( $supported ) )
			$supported = $this->posttypes();

		if ( empty( $supported ) )
			return FALSE;

		add_action( $this->hook_base( 'tweaks', 'column_attr' ),
			function( $post ) use ( $constants, $supported ) {

				if ( count( $supported ) > 1 ) {

					// extensive query only for multiple supported

					$posts = $this->paired_get_from_posts( $post->ID, $constants[0], $constants[1] );

					if ( ! $count = count( $posts ) )
						return;

					$posttypes = array_unique( Core\Arraay::pluck( $posts, 'post_type' ) );

				} else {

					// simple count query for single supported

					$count = $this->paired_get_from_posts( $post->ID, $constants[0], $constants[1], TRUE );

					if ( ! $count )
						return;

					$posttypes = $supported;
				}

				$title = $this->get_posttype_label( $constants[0], 'column_title', $this->constant( $constants[0] ) );

				echo '<li class="-row -'.$this->key.' -connected">';

					echo $this->get_column_icon( FALSE, NULL, $title );

					$args = [ $this->constant( $constants[1] ) => $post->post_name ];

					if ( empty( $this->cache['posttypes'] ) )
						$this->cache['posttypes'] = WordPress\PostType::get( 2 );

					echo '<span class="-counted">'.$this->nooped_count( 'connected', $count ).'</span>';

					$list = [];

					foreach ( $posttypes as $posttype )
						$list[] = Core\HTML::tag( 'a', [
							'href'   => Core\WordPress::getPostTypeEditLink( $posttype, 0, $args ),
							'title'  => _x( 'View the connected list', 'Module: Paired: Title Attr', 'geditorial' ),
							'target' => '_blank',
						], $this->cache['posttypes'][$posttype] );

					echo WordPress\Strings::getJoined( $list, ' <span class="-posttypes">(', ')</span>' );

				echo '</li>';
			} );

		return TRUE;
	}

	// TODO: add an advance version with modal for paired summary in `Missioned`/`Trained`/`Programmed`/`Meeted`
	protected function paired__hook_tweaks_column( $posttype = NULL, $priority = 10 )
	{
		if ( ! $this->_paired )
			return FALSE;

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		add_action( $this->hook_base( 'tweaks', 'column_row' ),
			function( $post ) use ( $constants ) {

				if ( ! $items = $this->paired_do_get_to_posts( $constants[0], $constants[1], $post ) )
					return;

				$before = $this->wrap_open_row( $this->constant( $constants[1] ), '-paired-row' );
				$before.= $this->get_column_icon( FALSE, NULL, NULL, $constants[1] );
				$after  = '</li>';

				foreach ( $items as $post_id ) {

					if ( ! $post = WordPress\Post::get( $post_id ) )
						continue;

					echo $before.WordPress\Post::fullTitle( $post, TRUE ).$after;
				}

			}, $priority, 1 );
	}
}
