<?php namespace geminorum\gEditorial\Internals;

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

trait ViewEngines
{

	protected $base = NULL;
	protected $key  = NULL;
	protected $path = NULL;

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
	protected function render_view( string $part, array $data = [], ?string $path = NULL, bool $verbose = TRUE ): true|string
	{
		self::_dep( '$this->viewengine__render()' );

		$path = $path ?? $this->get_view_path();

		if ( empty( $this->view_engines[$path] ) )
			$this->view_engines[$path] = $this->viewengine__get( $path );

		$html     = $this->view_engines[$path]->loadTemplate( $part )->render( $data );
		$filtered = $this->filters( 'render_view', $html, $part, $data );

		if ( ! $verbose )
			return $filtered;

		echo $filtered;
		return TRUE;
	}

	// NOTE: always gets a new instance
	protected function viewengine__get( string $path = '' ): object
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

			$partials = sprintf( '%spartials', Core\File::trail( $path ) );

			if ( is_dir( $partials ) )
				$args['partials_loader'] = new \Mustache\Loader\FilesystemLoader( $partials );
		}

		return new \Mustache\Engine( $args );
	}

	public function viewengine__view_by_template( string $template, string $context, string $default = 'default', ?string $path = NULL ): false|array
	{
		$view     = FALSE;
		$target   = self::dsh( $context, $template );
		$fallback = self::dsh( $default, $template );
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

	public function viewengine__view_by_post( object $post, string $context, string $default = 'default', ?string $path = NULL ): false|array
	{
		$target   = $view = FALSE;
		$fallback = self::dsh( $context, 'type', $default );
		$roots    = $this->viewengine__roots( $path );

		if ( $post = WordPress\Post::get( $post ) )
			$target = self::dsh( $context, 'type', $post->post_type );

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

	public function viewengine__view_by_term( object $term, string $context, string $default = 'default', ?string $path = NULL ): false|array
	{
		$target   = $view = FALSE;
		$fallback = self::dsh( $context, 'tax', $default );
		$roots    = $this->viewengine__roots( $path );

		if ( $term = WordPress\Term::get( $term ) )
			$target = self::dsh( $context, 'tax', $term->taxonomy );

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

	#[\Deprecated()]
	protected function get_view_part_by_post( object $post, string $context, string $default = 'default' ): string
	{
		$part = $fallback = self::dsh( $context, 'type', $default );

		if ( $post = WordPress\Post::get( $post ) )
			$part = self::dsh( $context, 'type', $post->post_type );

		if ( ! Core\File::readable( $this->get_view_path( $part ) ) )
			$part = $fallback;

		return $this->filters( 'view_part_by_post', $part, $post, $context, $fallback );
	}

	#[\Deprecated()]
	protected function get_view_part_by_term( object $term, string $context, string $default = 'default' ): string
	{
		$part = $fallback = self::dsh( $context, 'tax', $default );

		if ( $term = WordPress\Term::get( $term ) )
			$part = self::dsh( $context, 'tax', $term->taxonomy );

		if ( ! Core\File::readable( $this->get_view_path( $part ) ) )
			$part = $fallback;

		return $this->filters( 'view_part_by_term', $part, $term, $context, $fallback );
	}

	#[\Deprecated()]
	protected function get_view_path( false|string $part = FALSE, ?string $path = NULL ): string
	{
		$path = $path ?? ( $this->path.'views' );

		return $part ? sprintf( '%s/%s.mustache', $path, $part ) : $path;
	}
}
