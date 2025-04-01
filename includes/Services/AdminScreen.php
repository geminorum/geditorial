<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

class AdminScreen extends gEditorial\Service
{
	public static function setup()
	{
		if ( ! is_admin() )
			return;

		add_action( 'current_screen', [ __CLASS__, 'current_screen' ], 1, 1 );
	}

	public static function current_screen( $screen )
	{
		if ( empty( $screen->post_type ) )
			return;

		if ( ! $posttype = WordPress\PostType::object( $screen->post_type ) )
			return;

		if ( WordPress\PostType::supportBlocks( $posttype ) )
			return;

		$extra = [];

		if ( ! empty( $posttype->readonly_title ) ) {
			$extra[] = 'readonly-posttype-title';
			self::_hook_editform_readonly_title( $screen );
		}

		if ( ! empty( $posttype->tinymce_disabled ) )
			$extra[] = 'disable-posttype-tinymce';

		if ( ! empty( $posttype->slug_disabled ) ) {
			remove_meta_box( 'slugdiv', $screen, 'normal' );
			$extra[] = 'disable-posttype-slug';
		}

		if ( ! empty( $posttype->date_disabled ) ) {
			add_filter( 'disable_months_dropdown', '__return_true' );
			$extra[] = 'disable-posttype-date';
		}

		if ( ! empty( $posttype->author_disabled ) ) {
			remove_meta_box( 'authordiv', $screen, 'normal' );
			$extra[] = 'disable-posttype-author';
		}

		if ( ! empty( $posttype->password_disabled ) )
			$extra[] = 'disable-posttype-password';

		if ( empty( $extra ) )
			return;

		add_filter( 'admin_body_class',
			static function ( $classes ) use ( $extra ) {
				return trim( $classes ).' '.Core\HTML::prepClass( $extra );
			} );
	}

	// @REF: https://make.wordpress.org/core/2012/12/01/more-hooks-on-the-edit-screen/
	private static function _hook_editform_readonly_title( $screen = NULL )
	{
		add_action( 'edit_form_after_title', static function ( $post ) {

			$title = WordPress\Post::title( $post );
			$after = Settings::fieldAfterIcon( '#', _x( 'This Title is Auto-Generated.', 'Service: AdminScreen: ReadOnly Title Info', 'geditorial-admin' ) );

			echo Core\HTML::wrap(
				$title.' '.$after,
				'-readonly-title',
				TRUE,
				[],
				sprintf( '%s-readonlytitle', static::BASE )
			);
		}, 1, 1 );
	}

	/**
	 * Hides inline/bulk edit row action.
	 * @source https://core.trac.wordpress.org/ticket/19343
	 * @see: `quick_edit_enabled_for_post_type`
	 *
	 * @param  null|object $screen
	 * @return void
	 */
	public static function disableQuickEdit( $screen = NULL )
	{
		if ( is_null( $screen ) )
			$screen = get_current_screen();

		add_filter( 'page_row_actions', static function ( $actions, $post) use ( $screen ) {
			if ( $post->post_type === $screen->post_type )
				unset( $actions['inline hide-if-no-js'] );
			return $actions;
		}, 12, 2 );

		add_filter( 'post_row_actions', static function ( $actions, $post ) use ( $screen ) {
			if ( $post->post_type === $screen->post_type )
				unset( $actions['inline hide-if-no-js'] );
			return $actions;
		}, 12, 2 );

		add_filter( 'bulk_actions-'.$screen->id, static function ( $actions ) {
			unset( $actions['edit'] );
			return $actions;
		} );
	}
}
