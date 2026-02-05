<?php namespace geminorum\gEditorial\Internals;

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

trait ViewEngines
{

	protected $view_engines = [];

	// @SEE: https://github.com/GaryJones/Gamajo-Template-Loader
	protected function viewengine__roots( $path = NULL )
	{
		return [
			sprintf( '%s/editorial/views/%s/', STYLESHEETPATH, $this->key ),
			sprintf( '%s/editorial/views/%s/', TEMPLATEPATH, $this->key ),
			$path ?? sprintf( '%sviews/', $this->path ),
			sprintf( '%sassets/views/', GEDITORIAL_DIR ),
		];
	}

	public function viewengine__render_string( $template, $data = [], $verbose = TRUE )
	{
		if ( empty( $template ) )
			return $verbose ? FALSE : '';

		// with no `Mustache_Loader_FilesystemLoader`
		if ( empty( $this->view_engines['__string__'] ) )
			$this->view_engines['__string__'] = $this->viewengine__get();

		$html     = $this->view_engines[0]->render( $template, $data );
		$filtered = $this->filters( 'render_view_string', $html, $template, $data );

		if ( ! $verbose )
			return $filtered;

		echo $filtered;
	}

	public function viewengine__render( $view, $data = [], $verbose = TRUE )
	{
		$key = $this->hash( $view );
		list( $part, $root ) = $view;

		if ( empty( $this->view_engines[$key] ) )
			$this->view_engines[$key] = $this->viewengine__get( $root );

		$html     = $this->view_engines[$key]->loadTemplate( $part )->render( $data );
		$filtered = $this->filters( 'render_view', $html, $part, $data );

		if ( ! $verbose )
			return $filtered;

		echo $filtered;
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki/Mustache-Tags
	protected function render_view( $part, $data = [], $path = NULL, $verbose = TRUE )
	{
		self::_dep( '$this->viewengine__render()' );

		if ( is_null( $path ) )
			$path = $this->get_view_path();

		if ( empty( $this->view_engines[$path] ) )
			$this->view_engines[$path] = $this->viewengine__get( $path );

		$html     = $this->view_engines[$path]->loadTemplate( $part )->render( $data );
		$filtered = $this->filters( 'render_view', $html, $part, $data );

		if ( ! $verbose )
			return $filtered;

		echo $filtered;
	}

	// NOTE: always gets a new instance
	protected function viewengine__get( $path = FALSE )
	{
		$args = [
			'cache_file_mode' => FS_CHMOD_FILE,
			'cache'           => get_temp_dir(),

			'template_class_prefix' => sprintf( '__%s_%s_', $this->base, $this->key ),

			'escape' => static function ( $value ) {
				return htmlspecialchars( $value, ENT_COMPAT, 'UTF-8' );
			},
		];

		if ( $path ) {

			$args['loader'] = new \Mustache\Loader\FilesystemLoader( $path );

			if ( is_dir( $path.'partials' ) )
				$args['partials_loader'] = new \Mustache\Loader\FilesystemLoader( $path.'partials' );
		}

		return new \Mustache\Engine( $args );
	}

	public function viewengine__view_by_template( $template, $context, $default = 'default', $path = NULL )
	{
		$view     = FALSE;
		$target   = Core\Text::dashed( $context, $template );
		$fallback = Core\Text::dashed( $default, $template );
		$roots    = $this->viewengine__roots( $path );

		foreach ( $roots as $root ) {

			if ( Core\File::readable( sprintf( '%s/%s.mustache', $root, $target ) ) ) {
				$view = [ $target, $root ];
				break;
			}

			if ( Core\File::readable( sprintf( '%s/%s.mustache', $root, $fallback ) ) ) {
				$view = [ $fallback, $root ];
				break;
			}
		}

		return $this->filters( 'view_by_template', $view, $template, $context, $fallback, $roots, $target );
	}

	public function viewengine__view_by_post( $post, $context, $default = 'default', $path = NULL )
	{
		$target   = $view = FALSE;
		$fallback = Core\Text::dashed( $context, 'type', $default );
		$roots    = $this->viewengine__roots( $path );

		if ( $post = WordPress\Post::get( $post ) )
			$target = Core\Text::dashed( $context, 'type', $post->post_type );

		foreach ( $roots as $root ) {

			if ( $target && Core\File::readable( sprintf( '%s/%s.mustache', $root, $target ) ) ) {
				$view = [ $target, $root ];
				break;
			}

			if ( Core\File::readable( sprintf( '%s/%s.mustache', $root, $fallback ) ) ) {
				$view = [ $fallback, $root ];
				break;
			}
		}

		return $this->filters( 'view_by_post', $view, $post, $context, $fallback, $roots, $target );
	}

	public function viewengine__view_by_term( $term, $context, $default = 'default', $path = NULL )
	{
		$target   = $view = FALSE;
		$fallback = Core\Text::dashed( $context, 'tax', $default );
		$roots    = $this->viewengine__roots( $path );

		if ( $term = WordPress\Term::get( $term ) )
			$target = Core\Text::dashed( $context, 'tax', $term->taxonomy );

		foreach ( $roots as $root ) {

			if ( $target && Core\File::readable( sprintf( '%s/%s.mustache', $root, $target ) ) ) {
				$view = [ $target, $root ];
				break;
			}

			if ( Core\File::readable( sprintf( '%s/%s.mustache', $root, $fallback ) ) ) {
				$view = [ $fallback, $root ];
				break;
			}
		}

		return $this->filters( 'view_by_term', $view, $term, $context, $fallback, $roots, $target );
	}

	// NOTE: DEPRECATED
	protected function get_view_part_by_post( $post, $context, $default = 'default' )
	{
		self::_dep();

		$part = $fallback = sprintf( '%s-type-%s', $context, $default );

		if ( $post = WordPress\Post::get( $post ) )
			$part = sprintf( '%s-type-%s', $context, $post->post_type );

		if ( ! Core\File::readable( $this->get_view_path( $part ) ) )
			$part = $fallback;

		return $this->filters( 'view_part_by_post', $part, $post, $context, $fallback );
	}

	// NOTE: DEPRECATED
	protected function get_view_part_by_term( $term, $context, $default = 'default' )
	{
		self::_dep();

		$part = $fallback = sprintf( '%s-tax-%s', $context, $default );

		if ( $term = WordPress\Term::get( $term ) )
			$part = sprintf( '%s-tax-%s', $context, $term->taxonomy );

		if ( ! Core\File::readable( $this->get_view_path( $part ) ) )
			$part = $fallback;

		return $this->filters( 'view_part_by_term', $part, $term, $context, $fallback );
	}

	protected function get_view_path( $part = FALSE, $path = NULL )
	{
		self::_dep();

		if ( is_null( $path ) )
			$path = $this->path.'views';

		return $part ? sprintf( '%s/%s.mustache', $path, $part ) : $path;
	}
}
