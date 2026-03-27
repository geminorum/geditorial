<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ClassicEditor extends gEditorial\Service
{
	const TINYMCE_FILTERS = [
		'teeny_mce_buttons',
		'mce_buttons',
		'mce_buttons_2',
		'mce_buttons_3',
		'mce_buttons_4',
	];

	public static $tinymce_buttons = [
		[], // 0: `teeny_mce_buttons`
		[], // 1: `mce_buttons`
		[], // 2: `mce_buttons_2`
		[], // 3: `mce_buttons_3`
		[], // 4: `mce_buttons_4`
	];

	public static function setup()
	{
		if ( is_admin() ) {

			add_action( 'current_screen', [ __CLASS__, 'hook_buttons' ], 999 );

		} else {

			add_action( 'init', [ __CLASS__, 'hook_buttons' ], 999 );
		}
	}

	public static function hook_buttons()
	{
		if ( 'true' != get_user_option( 'rich_editing' ) )
			return;

		if ( empty( array_filter( static::$tinymce_buttons ) ) )
			return;

		foreach ( static::TINYMCE_FILTERS as $level => $filter )
			add_filter( $filter, static function ( $buttons, $editor_id )
				use ( $level, $filter ) {

				// if ( WordPress\IsIt::blockEditor() )
				// 	return $buttons;

				if ( empty( static::$tinymce_buttons[$level] ) )
					return $buttons;

				foreach ( static::$tinymce_buttons[$level] as $plugin => $filepath )
					array_push( $buttons, $plugin );

				return $buttons;

			}, 12, 2 );

		add_filter( 'mce_external_plugins', [ __CLASS__, 'mce_external_plugins' ] );
		add_filter( 'mce_external_languages', [ __CLASS__, 'mce_external_languages' ] );
	}

	public static function registerButton( $button, $filepath, $level = NULL )
	{
		static::$tinymce_buttons[( $level ?? 1 )][$button] = sprintf( '%s%s', self::factory()->get_url(), $filepath );
	}

	public static function mce_external_plugins( $plugin_array )
	{
		$variant = self::const( 'SCRIPT_DEBUG' ) ? '' : '.min';

		foreach ( self::$tinymce_buttons as $row )
			foreach ( $row as $plugin => $filepath )
				if ( $filepath )
					$plugin_array[$plugin] = sprintf( '%s%s.js', $filepath, $variant );

		return $plugin_array;
	}

	public static function mce_external_languages( $languages )
	{
		return array_merge( $languages, [
			static::BASE => sprintf( '%sincludes/Misc/TinyMceStrings.php', self::factory()->get_dir() ),
		] );
	}

	public static function getTinyMceStrings( $locale )
	{
		$strings = apply_filters( self::und( static::BASE, 'tinymce_strings' ), [] );

		return count( $strings ) ? 'tinyMCE.addI18n("'.$locale.'.'.static::BASE.'", '.Core\HTML::encode( $strings ).');'."\n" : '';
	}

	// TODO: `line-count`
	public static function renderEditorStatusInfo( $target )
	{
		echo '<div class="-wrap -editor-status-info">';

			echo '<div data-target="'.$target.'" class="-status-count hide-if-no-js">';

				printf(
					/* translators: `%s`: words count */
					_x( 'Words: %s', 'Service: ClassicEditor: WordCount', 'geditorial' ),
					Core\HTML::span( Core\Number::format( '0' ), 'word-count' )
				);

				echo '&nbsp;|&nbsp;';

				printf(
					/* translators: `%s`: chars count */
					_x( 'Chars: %s', 'Service: ClassicEditor: WordCount', 'geditorial' ),
					Core\HTML::span( Core\Number::format( '0' ), 'char-count' )
				);

			echo '</div>';

			do_action( self::und( static::BASE, 'editor_status_info' ), $target );

		echo '</div>';
	}
}
