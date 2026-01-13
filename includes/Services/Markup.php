<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Markup extends gEditorial\Service
{
	public static function setup()
	{
		add_filter( static::BASE.'_markdown_to_html', [ __CLASS__, 'markdown_to_html' ], 10, 3 );
		add_filter( 'kses_allowed_protocols', [ __CLASS__, 'kses_allowed_protocols' ], 20, 1 );
	}

	// @hook `geditorial_markdown_to_html`
	public static function markdown_to_html( $raw, $autop = TRUE, $strip_frontmatter = TRUE )
	{
		return self::mdExtra( Core\Text::trim( $raw ), $autop, $strip_frontmatter );
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

	public static function mdExtra( $markdown, $autop = TRUE, $strip_frontmatter = TRUE )
	{
		global $gEditorialMarkdownExtra;

		if ( empty( $markdown ) || ! class_exists( '\Michelf\MarkdownExtra' ) )
			return $strip_frontmatter ? self::stripFrontMatter( $markdown ) : $markdown;

		if ( empty( $gEditorialMarkdownExtra ) )
			/**
			 * @package `michelf/php-markdown`
			 * @source https://github.com/michelf/php-markdown
			 * @docs https://michelf.ca/projects/php-markdown/reference/
			 */
			$gEditorialMarkdownExtra = new \Michelf\MarkdownExtra();

		if ( $strip_frontmatter )
			$markdown = self::stripFrontMatter( $markdown );

		$markdown = $gEditorialMarkdownExtra->defaultTransform( $markdown );

		return $autop ? $markdown : Core\Text::removeP( $markdown );
	}

	// @source https://github.com/ergebnis/front-matter/blob/main/src/YamlParser.php
	private const FRONTMATTER_PATTERN = "{^(?P<frontMatterWithDelimiters>(?:---)[\r\n|\n]*(?P<frontMatterWithoutDelimiters>.*?)[\r\n|\n]+(?:---)[\r\n|\n]{0,1})(?P<bodyMatter>.*)$}s";

	public static function stripFrontMatter( $text )
	{
		if ( empty( $text ) )
			return $text;

		if ( ! preg_match( static::FRONTMATTER_PATTERN, (string) $text, $matches ) )
			return $text;

		return str_replace( $matches['frontMatterWithDelimiters'], '', $text );
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

	/**
	 * Renders a circle progress markup.
	 * NOTE: `$completed` starts at zero.
	 *
	 * @param int $completed
	 * @param int $total
	 * @return void
	 */
	public static function renderCircleProgress( $completed, $total, $hint = FALSE, $template = NULL )
	{
		$step_number = $completed + 1;

		// Given 'r' (circle element's r attr), `dashoffset` = ((100-$desired_percentage)/100) * PI * (r*2).
		$percentage = ( $completed / $total ) * 100;
		$circle_r   = 6.5;
		$dashoffset = ( ( 100 - $percentage ) / 100 ) * ( pi() * ( $circle_r * 2 ) );

		$text = sprintf(
			/* translators: `%1$s`: step number, `%2$s`: total tasks */
			_x( 'Step %1$s of %2$s', 'Service: Markup', 'geditorial' ),
			Core\Number::localize( $step_number ),
			Core\Number::localize( $total ),
		);

		if ( $hint )
			$text = sprintf( $template ?? '%s: %s', $text, $hint );

		$data = [
			'percentage'   => $percentage,
			'total-steps'  => $total,
			'current-step' => $step_number - 1,
		];

		$markup = <<<MARKUP
<span class='progress-wrapper'>
	<svg class="circle-progress" width="17" height="17" version="1.1" xmlns="http://www.w3.org/2000/svg">
		<circle r="6.5" cx="10" cy="10" fill="transparent" stroke-dasharray="40.859" stroke-dashoffset="0"></circle>
		<circle class="bar" r="6.5" cx="190" cy="10" fill="transparent" stroke-dasharray="40.859" stroke-dashoffset="{$dashoffset}" transform='rotate(-90 100 100)'></circle>
	</svg>
	<span>{$text}</span>
</span>
MARKUP;

		echo Core\HTML::wrap( $markup, 'markup-circle-progress', FALSE, $data );
	}
}
