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

		add_action( 'init', [ __CLASS__, 'init_late_admin' ], 999 );
		add_action( 'current_screen', [ __CLASS__, 'current_screen' ], 1, 1 );
	}

	public static function init_late_admin()
	{
		add_filter( 'screen_settings', [ __CLASS__, 'screen_settings' ], 12, 2 );
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen_option' ], 12, 3 );

		if ( $posttype = self::req( 'post_type', 'post' ) )
			self::_handle_set_screen_options( $posttype );
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

	// NOTE: see `corerestrictposts__hook_screen_taxonomies()`
	public static function screen_settings( $settings, $screen )
	{
		$taxonomies = apply_filters( static::BASE.'_screen_restrict_taxonomies', [], $screen );

		if ( empty( $taxonomies ) )
			return $settings;

		$selected = get_user_option( sprintf( '%s_restrict_%s', static::BASE, $screen->post_type ) );
		$name     = sprintf( '%s-restrict-%s', static::BASE, $screen->post_type );

		$html = '<fieldset><legend>'._x( 'Restrictions', 'Service: AdminScreen: Screen Settings Title', 'geditorial-admin' ).'</legend>';

		$html.= Core\HTML::multiSelect( array_map( 'get_taxonomy', $taxonomies ), [
			'item_tag' => FALSE, // 'span',
			'prop'     => 'label',
			'value'    => 'name',
			'id'       => static::BASE.'-tax-restrictions',
			'name'     => $name,
			'selected' => FALSE === $selected ? $taxonomies : $selected,
		] );

		// hidden to clear the settings
		$html.= '<input type="hidden" name="'.$name.'[0]" value="1" /></fieldset>';

		return $settings.$html;
	}

	// Lets our screen options passing through
	// @since WP 5.4.2 Only applied to options ending with '_page',
	// or the 'layout_columns' option
	// @REF: https://core.trac.wordpress.org/changeset/47951
	public static function set_screen_option( $false, $option, $value )
	{
		return Core\Text::starts( $option, static::BASE ) ? $value : $false;
	}

	private static function _handle_set_screen_options( $posttype )
	{
		$name = sprintf( '%s-restrict-%s', static::BASE, $posttype );

		if ( ! isset( $_POST[$name] ) )
			return FALSE;

		check_admin_referer( 'screen-options-nonce', 'screenoptionnonce' );

		return update_user_option(
			get_current_user_id(),
			sprintf( '%s_restrict_%s', static::BASE, $posttype ),
			Core\Arraay::prepString( array_keys( $_POST[$name] ) )
		);
	}
}
