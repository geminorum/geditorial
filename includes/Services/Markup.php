<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Markup extends gEditorial\Service
{
	public static function setup(): void
	{
		add_filter( self::und( static::BASE, 'markdown_to_html' ), [ __CLASS__, 'markdownToHTML' ],   10, 3 );
		add_filter( self::und( static::BASE, 'html_to_markdown' ), [ __CLASS__, 'markdownFromHTML' ], 10, 2 );
		add_filter( 'kses_allowed_protocols', [ __CLASS__, 'kses_allowed_protocols' ], 20, 1 );
	}

	// @hook `geditorial_markdown_to_html`
	public static function markdownToHTML( mixed $raw, bool $autop = TRUE, bool $strip_frontmatter = TRUE ): string
	{
		return self::mdExtra( Core\Text::trim( $raw ), $autop, $strip_frontmatter );
	}

	// @hook `geditorial_html_to_markdown`
	public static function markdownFromHTML( mixed $raw, bool $autop = TRUE ): string
	{
		static $instance;

		if ( ! is_string( $raw ) || self::empty( $raw ) )
			return '';

		if ( empty( $instance ) )
			/**
			 * @package `league/html-to-markdown`
			 * @source https://github.com/thephpleague/html-to-markdown
			 */
			$instance = new \League\HTMLToMarkdown\HtmlConverter();

		if ( $autop && is_string( $raw ) )
			// NOTE: usually needed for WordPress contents
			$raw = wpautop( $raw );

		return $instance->convert( $raw );
	}

	/**
	 * Filters the list of protocols allowed in HTML attributes.
	 *
	 * @param array $protocols
	 * @return array
	 */
	public static function kses_allowed_protocols( array $protocols ): array
	{
		return array_merge( $protocols, [
			'tel',  // to be safe
			'sms',  // to be safe
			'geo',
			'binaryeye', // https://github.com/markusfisch/BinaryEye
		] );
	}

	/**
	 * Transforms given Markdown content into HTML.
	 *
	 * @param mixed $markdown
	 * @param bool $autop
	 * @param bool $strip_frontmatter
	 * @return string
	 */
	public static function mdExtra( mixed $markdown, bool $autop = TRUE, bool $strip_frontmatter = TRUE ): string
	{
		static $instance;

		if ( ! is_string( $markdown ) || self::empty( $markdown ) )
			return '';

		if ( ! class_exists( '\Michelf\MarkdownExtra' ) )
			return $strip_frontmatter
				? self::stripFrontMatter( $markdown )
				: $markdown;

		if ( empty( $instance ) )
			/**
			 * @package `michelf/php-markdown`
			 * @source https://github.com/michelf/php-markdown
			 * @docs https://michelf.ca/projects/php-markdown/reference/
			 */
			$instance = new \Michelf\MarkdownExtra();

		if ( $strip_frontmatter )
			$markdown = self::stripFrontMatter( $markdown );

		$markdown = $instance->defaultTransform( $markdown );

		return $autop
			? $markdown // NOTE: the default is wrapped with paragraphs.
			: Core\Text::removeP( $markdown );
	}

	// @source https://github.com/ergebnis/front-matter/blob/main/src/YamlParser.php
	private const FRONTMATTER_PATTERN = "{^(?P<frontMatterWithDelimiters>(?:---)[\r\n|\n]*(?P<frontMatterWithoutDelimiters>.*?)[\r\n|\n]+(?:---)[\r\n|\n]{0,1})(?P<bodyMatter>.*)$}s";

	public static function stripFrontMatter( mixed $text ): string
	{
		if ( ! $text )
			return '';

		$text = (string) $text;

		if ( ! preg_match( static::FRONTMATTER_PATTERN, $text, $matches ) )
			return $text;

		return str_replace( $matches['frontMatterWithDelimiters'], '', $text );
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki
	public static function getMustache( ?string $base = NULL ): object
	{
		global $gEditorialMustache;

		if ( ! empty( $gEditorialMustache ) )
			return $gEditorialMustache;

		$base = $base ?? static::factory()->get_dir(); // `GEDITORIAL_DIR`

		$gEditorialMustache = new \Mustache\Engine( [
			'template_class_prefix' => sprintf( '__%s_', static::BASE ),
			'cache_file_mode'       => FS_CHMOD_FILE,
			'cache'                 => get_temp_dir(), // `$base.'assets/views/cache',`

			'loader'          => new \Mustache\Loader\FilesystemLoader( $base.'assets/views' ),
			'partials_loader' => new \Mustache\Loader\FilesystemLoader( $base.'assets/views/partials' ),
			'escape'          => static function ( $value ) {
				return htmlspecialchars( $value, ENT_COMPAT, 'UTF-8' );
			},
		] );

		return $gEditorialMustache;
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki/Mustache-Tags
	public static function renderMustache( string $part, array $data = [], bool $verbose = TRUE ): true|string
	{
		$engine = self::getMustache();
		$html   = $engine->loadTemplate( $part )->render( $data );

		if ( ! $verbose )
			return $html;

		echo $html;
		return TRUE;
	}

	/**
	 * Separates given string by set of delimiters into an array.
	 * NOTE: applies the plugin filter on default delimiters
	 *
	 * @param mixed $string
	 * @param string|array $delimiters
	 * @param int $limit
	 * @param string $delimiter
	 * @return array
	 */
	public static function getSeparated( mixed $string, string|array|null $delimiters = NULL, ?int $limit = NULL, ?string $delimiter = NULL ): array
	{
		return WordPress\Strings::getSeparated(
			$string,
			$delimiters ?? self::getDelimiters( $delimiter ?? '|' ),
			$limit,
			$delimiter ?? '|',
		);
	}

	/**
	 * Retrieves the list of string delimiters.
	 *
	 * @param string $default
	 * @return null|array
	 */
	public static function getDelimiters( ?string $default = NULL ): null|array
	{
		return apply_filters( self::und( static::BASE, 'string_delimiters' ),
			Core\Arraay::prepSplitters( GEDITORIAL_STRING_DELIMITERS, $default ?? '|' ),
			$default ?? '|',
		);
	}

	/**
	 * Renders a circle progress markup.
	 * NOTE: `$completed` starts at zero.
	 *
	 * @param int $completed
	 * @param int $total
	 * @param false|string $hint
	 * @param string $template
	 * @return void
	 */
	public static function renderCircleProgress( int $completed, int $total, false|string $hint = FALSE, ?string $template = NULL ): void
	{
		$step = $completed + 1;

		// Given `r` (circle element's `r` attribute), `dashoffset = ((100-$desired_percentage)/100) * PI * (r*2)`.
		$circle_r   = 6.5;
		$percentage = ( $completed / $total ) * 100;
		$dashoffset = ( ( 100 - $percentage ) / 100 ) * ( pi() * ( $circle_r * 2 ) );

		$caption = sprintf(
			/* translators: `%1$s`: step number, `%2$s`: total tasks */
			_x( 'Step %1$s of %2$s', 'Service: Markup', 'geditorial' ),
			Core\Number::localize( $step ),
			Core\Number::localize( $total ),
		);

		if ( $hint )
			$caption = sprintf( $template ?? '%s: %s', $caption, $hint );

		$markup = <<<MARKUP
<span class='progress-wrapper'>
	<svg class="circle-progress" width="17" height="17" version="1.1" xmlns="http://www.w3.org/2000/svg">
		<circle r="6.5" cx="10" cy="10" fill="transparent" stroke-dasharray="40.859" stroke-dashoffset="0"></circle>
		<circle class="bar" r="6.5" cx="190" cy="10" fill="transparent" stroke-dasharray="40.859" stroke-dashoffset="{$dashoffset}" transform='rotate(-90 100 100)'></circle>
	</svg>
	<span>{$caption}</span>
</span>
MARKUP;

		echo Core\HTML::wrap(
			$markup,
			'markup-circle-progress',
			FALSE,
			[
				'percentage'   => $percentage,
				'total-steps'  => $total,
				'current-step' => $step - 1,
			]
		);
	}

	public static function getImgCursorHover(): string
	{
		return ' onmouseover="(function(e){e.style.cursor=\'url(\'+e.src+\'),auto\';}(this))" onmouseleave="this.style.cursor=\'auto\'"';
	}
}
