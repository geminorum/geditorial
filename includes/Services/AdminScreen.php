<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class AdminScreen extends gEditorial\Service
{
	public static function setup()
	{
		if ( ! is_admin() )
			return;

		add_action( 'init', [ __CLASS__, 'init_late_admin' ], 999 );
		add_action( 'current_screen', [ __CLASS__, 'current_screen' ], 1, 1 );
		add_action( 'admin_print_styles', [ __CLASS__, 'admin_print_styles' ], 1, 0 );
	}

	public static function init_late_admin()
	{
		add_filter( 'screen_settings', [ __CLASS__, 'screen_settings' ], 12, 2 );
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen_option' ], 12, 3 );

		if ( $posttype = self::req( 'post_type', 'post' ) )
			self::_handle_set_screen_options( $posttype );

		if ( ! WordPress\Screen::mustRegisterUI( FALSE ) )
			return;

		add_filter( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ], 9, 2 );
	}

	// @REF: https://wpartisan.me/?p=434
	// @REF: https://core.trac.wordpress.org/ticket/45283
	// @SEE: https://make.wordpress.org/core/2012/12/01/more-hooks-on-the-edit-screen/
	public static function add_meta_boxes( $posttype, $post )
	{
		if ( WordPress\Post::supportBlocks( $post ) )
			return;

		add_action( 'edit_form_after_title', [ __CLASS__, 'edit_form_after_title' ] );
	}

	public static function current_screen( $screen )
	{
		if ( $screen->taxonomy ) {

			if ( 'term' === $screen->base ) {

				add_action( "{$screen->taxonomy}_term_edit_form_top", [ __CLASS__, 'term_edit_form_open' ], -9999999, 2 );
				add_action( "{$screen->taxonomy}_edit_form", [ __CLASS__, 'term_edit_form_close' ], 9999999, 2 );
			}

			self::_enqueue_screen_script( $screen );

		} else if ( $screen->post_type ) {

			self::_handle_posttype_body_class( $screen );
		}
	}

	public static function admin_print_styles()
	{
		self::_print_user_colors();
	}

	// @REF: https://wordpress.stackexchange.com/a/369713
	// @SEE: https://github.com/WordPress/gutenberg/blob/trunk/packages/components/src/utils/theme-variables.scss
	// @SEE: https://make.wordpress.org/core/2021/01/29/introducing-css-custom-properties/
	private static function _print_user_colors()
	{
		global $_wp_admin_css_colors;

		$colors = [];
		$scheme = WordPress\User::colorScheme();

		if ( ! array_key_exists( $scheme, $_wp_admin_css_colors ) )
			return FALSE;

		foreach ( $_wp_admin_css_colors[$scheme]->colors as $key => $data )
			if ( $color = sanitize_hex_color( $data ) )
				$colors[] = "\t".sprintf( '--%s-admin-color-%s: %s;', static::BASE, $key, $color );

		foreach ( $_wp_admin_css_colors[$scheme]->icon_colors as $key => $data )
			if ( $color = sanitize_hex_color( $data ) )
				$colors[] = "\t".sprintf( '--%s-admin-color-icon-%s: %s;', static::BASE, $key, $color );

		// @hook `geditorial_adminscreen_colors`
		if ( ! $colors = apply_filters( implode( '_', [ static::BASE, 'adminscreen', 'colors' ] ), $colors, $scheme, static::BASE ) )
			return FALSE;

        echo '<style id="'.static::BASE.'-admin-colors" data-scheme="'.$scheme.'">'."\n";
			echo ':root {'."\n".implode( "\n", $colors )."\n".'}'."\n";
		echo '</style>'."\n";

		return $scheme;
	}

	public static function edit_form_after_title( $post )
	{
		echo '<div id="postbox-container-after-title" class="postbox-container">';
			do_meta_boxes( get_current_screen(), 'after_title', $post );
		echo '</div>';
	}

	public static function term_edit_form_open( $term, $taxonomy )
	{
		echo '<div id="poststuff">';
		echo '<div id="post-body" class="metabox-holder columns-2">';
		echo '<div id="post-body-content">';
	}

	public static function term_edit_form_close( $term, $taxonomy )
	{
		echo '</div><div id="postbox-container-1" class="postbox-container">';
			// do_accordion_sections( get_current_screen(), 'side', $term );
			do_meta_boxes( get_current_screen(), 'side', $term );
		echo '</div><br class="clear" /></div></div>';
	}

	private static function _enqueue_screen_script( $screen, $taxonomy = NULL, $mainkey = NULL )
	{
		$taxonomy = $taxonomy ?? $screen->taxonomy;
		$mainkey  = $mainkey  ?? 'adminscreen';

		if ( ! apply_filters( static::BASE.'_'.$mainkey.'_enhancements', TRUE, $taxonomy, $mainkey, $screen ) )
			return FALSE;

		if ( 'edit-tags' === $screen->base ) {

			$asset = [
				// '_nonce'   => wp_create_nonce( $mainkey ),
				// 'strings'  => [],
				'settings' => [
					'inputs'  => apply_filters( implode( '_', [ static::BASE, $mainkey, 'fillbyquery', 'inputs' ] ), [], $taxonomy, $mainkey ),
					'selects' => apply_filters( implode( '_', [ static::BASE, $mainkey, 'fillbyquery', 'selects' ] ), [], $taxonomy, $mainkey ),
				],
			];

			gEditorial()->enqueue_asset_config( $asset, $mainkey );
			gEditorial\Scripts::enqueue( sprintf( '%s.%s', $screen->base, $mainkey ) );
		}

		return TRUE;
	}

	public static function _handle_posttype_body_class( $screen )
	{
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
		add_action( 'edit_form_after_title',
			static function ( $post ) {

				$title = WordPress\Post::title( $post );
				$after = gEditorial\Settings::fieldAfterIcon( '#', _x( 'This Title is Auto-Generated.', 'Service: AdminScreen: ReadOnly Title Info', 'geditorial-admin' ) );

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
	 * @param object $screen
	 * @return void
	 */
	public static function disableQuickEdit( $screen = NULL )
	{
		if ( is_null( $screen ) )
			$screen = get_current_screen();

		add_filter( 'page_row_actions',
			static function ( $actions, $post) use ( $screen ) {
				if ( $post->post_type === $screen->post_type )
					unset( $actions['inline hide-if-no-js'] );
				return $actions;
			}, 12, 2 );

		add_filter( 'post_row_actions',
			static function ( $actions, $post ) use ( $screen ) {
				if ( $post->post_type === $screen->post_type )
					unset( $actions['inline hide-if-no-js'] );
				return $actions;
			}, 12, 2 );

		add_filter( 'bulk_actions-'.$screen->id,
			static function ( $actions ) {
				unset( $actions['edit'] );
				return $actions;
			} );
	}

	// NOTE: see `corerestrictposts__hook_screen_taxonomies()`
	public static function screen_settings( $settings, $screen )
	{
		$taxonomies = apply_filters(
			static::BASE.'_screen_restrict_taxonomies',
			[],
			$screen
		);

		if ( empty( $taxonomies ) )
			return $settings;

		$name  = sprintf( '%s-restrict-%s', static::BASE, $screen->post_type );
		$value = get_user_option( sprintf( '%s_restrict_%s', static::BASE, $screen->post_type ) );

		$html = '<fieldset>';
		$html.= Core\HTML::tag( 'legend', _x( 'Restrictions', 'Service: AdminScreen: Screen Settings Title', 'geditorial-admin' ) );

		$html.= Core\HTML::multiSelect( array_map( 'get_taxonomy', $taxonomies ), [
			'item_tag' => FALSE, // 'span',
			'prop'     => 'label',
			'value'    => 'name',
			'id'       => static::BASE.'-tax-restrictions',
			'name'     => $name,
			'selected' => FALSE === $value ? $taxonomies : $value,
		] );

		// hidden to clear the settings
		$html.= '<input type="hidden" name="'.$name.'[0]" value="1" />';
		$html.= '</fieldset>';

		return $settings.$html;
	}

	/**
	 * Lets the plugin screen options passing through.
	 *
	 * Only applied to options ending with `_page`,
	 * or the `layout_columns` option @since WP 5.4.2
	 * @REF: https://core.trac.wordpress.org/changeset/47951
	 *
	 * @param mixed $false
	 * @param string $option
	 * @param mixed $value
	 * @return mixed
	 */
	public static function set_screen_option( $false, $option, $value )
	{
		return Core\Text::starts( $option, static::BASE ) ? $value : $false;
	}

	// @SEE: https://www.joedolson.com/2013/01/custom-wordpress-screen-options/
	// @SEE: https://webkul.com/blog/how-to-add-custom-screen-option-in-woocommerce/
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

	// @source https://code.tutsplus.com/integrating-with-wordpress-ui-meta-boxes-on-custom-pages--wp-26843a
	// @ref https://gist.github.com/stephenh1988/3676396
	public static function loadLayout( $layout_context, $context = NULL, $object = NULL )
	{
		// Trigger the add_meta_boxes hooks to allow meta boxes to be added.
		do_action( sprintf( 'add_meta_boxes_%s', $layout_context ), $object );
		do_action( 'add_meta_boxes', $layout_context, $object );

		// Enqueue WordPress script for handling the meta boxes.
		wp_enqueue_script( 'postbox' );

		add_screen_option( 'layout_columns', [
			'max'     => 2,
			'default' => 2,
		] );

		add_action( 'admin_print_footer_scripts',
			static function () {
				Core\HTML::wrapjQueryReady( 'postboxes.add_postbox_toggles(pagenow);' );
			} );
	}

	// @see `wp_dashboard()`
	public static function renderLayout( $context, $main_callback = NULL, $title_callback = NULL, $object = NULL )
	{
		if ( ! $screen = get_current_screen() )
			return FALSE;

		echo '<div id="poststuff">';
		echo '<div id="post-body" class="metabox-holder columns-'.( 1 == $screen->get_columns() ? '1' : '2' ).'">';
			echo '<div id="post-body-content">';

				if ( $title_callback && is_callable( $title_callback ) ) {
					echo '<div id="titlediv">';
						call_user_func_array( $title_callback, [ $context, $screen, $object ] );
					echo '</div>';
				}

				if ( $main_callback && is_callable( $main_callback ) ) {
					echo '<div id="postdivrich" class="postarea wp-editor-expand">';
						call_user_func_array( $main_callback, [ $context, $screen, $object ] );
					echo '</div>';
				}

			echo '</div>';
			echo '<div id="postbox-container-1" class="postbox-container">';

				do_meta_boxes( $screen, 'side', $object );

			echo '</div>';
			echo '<div id="postbox-container-2" class="postbox-container">';

				do_meta_boxes( $screen, 'normal', $object );
				do_meta_boxes( $screen, 'advanced', $object );

			echo '</div>';

		echo '</div>';
		echo '</div>';

		// Used to save closed meta-boxes and their order.
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', FALSE );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', FALSE );
	}
}
