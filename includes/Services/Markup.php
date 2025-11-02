<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Markup extends gEditorial\Service
{
	public static function setup()
	{
		add_filter( static::BASE.'_markdown_to_html', [ __CLASS__, 'markdown_to_html' ] );
		add_filter( 'kses_allowed_protocols', [ __CLASS__, 'kses_allowed_protocols' ], 20, 1 );
	}

	// @hook `geditorial_markdown_to_html`
	public static function markdown_to_html( $raw )
	{
		return self::mdExtra( $raw );
	}

	/**
	 * Filters the list of protocols allowed in HTML attributes.
	 *
	 * @param array $protocols
	 * @return array
	 */
	public static function kses_allowed_protocols( $protocols )
	{
		return array_merge( $protocols, [
			'tel', // to be safe
			'sms', // to be safe
			'geo',
			'binaryeye', // https://github.com/markusfisch/BinaryEye
		] );
	}

	public static function mdExtra( $markdown )
	{
		global $gEditorialMarkdownExtra;

		if ( empty( $markdown ) || ! class_exists( '\Michelf\MarkdownExtra' ) )
			return $markdown;

		if ( empty( $gEditorialMarkdownExtra ) )
			$gEditorialMarkdownExtra = new \Michelf\MarkdownExtra();

		return $gEditorialMarkdownExtra->defaultTransform( $markdown );
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki
	public static function getMustache( $base = GEDITORIAL_DIR )
	{
		global $gEditorialMustache;

		if ( ! empty( $gEditorialMustache ) )
			return $gEditorialMustache;

		$gEditorialMustache = new \Mustache\Engine( [
			'template_class_prefix' => '__'.static::BASE.'_',
			'cache_file_mode'       => FS_CHMOD_FILE,
			// 'cache'                 => $base.'assets/views/cache',
			'cache'                 => get_temp_dir(),

			'loader'          => new \Mustache\Loader\FilesystemLoader( $base.'assets/views' ),
			'partials_loader' => new \Mustache\Loader\FilesystemLoader( $base.'assets/views/partials' ),
			'escape'          => static function ( $value ) {
				return htmlspecialchars( $value, ENT_COMPAT, 'UTF-8' );
			},
		] );

		return $gEditorialMustache;
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki/Mustache-Tags
	public static function renderMustache( $part, $data = [], $verbose = TRUE )
	{
		$engine = self::getMustache();
		$html   = $engine->loadTemplate( $part )->render( $data );

		if ( ! $verbose )
			return $html;

		echo $html;
	}

	/**
	 * Separates given string by set of delimiters into an array.
	 * NOTE: applies the plugin filter on default delimiters
	 *
	 * @param string $string
	 * @param null|string|array $delimiters
	 * @param null|int $limit
	 * @param string $delimiter
	 * @return array
	 */
	public static function getSeparated( $string, $delimiters = NULL, $limit = NULL, $delimiter = '|' )
	{
		return WordPress\Strings::getSeparated(
			$string,
			$delimiters ?? self::getDelimiters( $delimiter ),
			$limit,
			$delimiter
		);
	}

	/**
	 * Retrieves the list of string delimiters.
	 *
	 * @param string $default
	 * @return null|array
	 */
	public static function getDelimiters( $default = '|' )
	{
		return apply_filters( static::BASE.'_string_delimiters',
			Core\Arraay::prepSplitters( GEDITORIAL_STRING_DELIMITERS, $default ) );
	}
}
