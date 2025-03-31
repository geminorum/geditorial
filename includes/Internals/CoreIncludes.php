<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

trait CoreIncludes
{

	public function get_module_path( $context = NULL )
	{
		return $this->filters( 'path', $this->path, $context );
	}

	/**
	 * Requires a relative file.
	 *
	 * @param string|array $filenames
	 * @param bool $once
	 * @return void
	 */
	protected function require_code( $filenames, $once = TRUE )
	{
		foreach ( (array) $filenames as $filename )
			if ( $once )
				require_once $this->path.$filename.'.php';
			else
				require $this->path.$filename.'.php';
	}

	/**
	 * Determines and loads a template part.
	 *
	 * `{$theme_path}/editorial/templates/{$module_name}-{$slug}-{name}.php`
	 * `{$theme_path}/editorial/templates/{$module_name}-{$slug}.php`
	 *
	 * @source `locate_template()`
	 * @source `get_template_part()`
	 *
	 * @param  string      $slug
	 * @param  null|string $name
	 * @param  bool        $load
	 * @param  bool        $once
	 * @param  array       $args
	 * @return string      $located
	 */
	protected function locate_template_part( $slug, $name = NULL, $load = FALSE, $once = TRUE, $args = [] )
	{
		$located   = '';
		$templates = WordPress\Theme::getPart( $slug, $name, FALSE, $args );

		$child  = sprintf( '%s/editorial/templates/%s-', STYLESHEETPATH, $this->key );
		$theme  = sprintf( '%s/editorial/templates/%s-', TEMPLATEPATH, $this->key );
		$module = sprintf( '%sTemplates/', $this->path );

		foreach ( (array) $templates as $template ) {

			if ( ! $template )
				continue;

			if ( file_exists( $child.$template ) ) {

				$located = $child.$template;
				break;

			} else if ( file_exists( $theme.$template ) ) {

				$located = $theme.$template;
				break;

			} else if ( file_exists( $module.$template ) ) {

				$located = $module.$template;
				break;
			}
		}

		if ( $load && '' !== $located )
			load_template( $located, $once, $args );

		return $located;
	}
}
