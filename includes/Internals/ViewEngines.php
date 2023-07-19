<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

trait ViewEngines
{

	protected $view_engines = [];

	protected function render_view_string( $template, $data = [], $verbose = TRUE )
	{
		if ( empty( $this->view_engines[0] ) )
			$this->view_engines[0] = $this->get_view_engine( 0 );

		$html    = $this->view_engines[0]->render( $template, $data );
		$filtred = $this->filters( 'render_view_string', $html, $template, $data );

		if ( ! $verbose )
			return $filtred;

		echo $filtred;
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki/Mustache-Tags
	protected function render_view( $part, $data = [], $path = NULL, $verbose = TRUE )
	{
		if ( is_null( $path ) )
			$path = $this->get_view_path();

		if ( empty( $this->view_engines[$path] ) )
			$this->view_engines[$path] = $this->get_view_engine( $path );

		$html    = $this->view_engines[$path]->loadTemplate( $part )->render( $data );
		$filtred = $this->filters( 'render_view', $html, $part, $data );

		if ( ! $verbose )
			return $filtred;

		echo $filtred;
	}

	// NOTE: always gets a new instance
	protected function get_view_engine( $path = NULL )
	{
		if ( is_null( $path ) )
			$path = $this->get_view_path();

		$args = [
			'cache_file_mode' => FS_CHMOD_FILE,
			// 'cache'           => $this->path.'views/cache',
			'cache'           => get_temp_dir(),

			'template_class_prefix' => sprintf( '__%s_%s_', $this->base, $this->key ),

			'escape' => static function( $value ) {
				return htmlspecialchars( $value, ENT_COMPAT, 'UTF-8' );
			},
		];

		if ( $path ) {
			$args['loader']          = new \Mustache_Loader_FilesystemLoader( $path );
			$args['partials_loader'] = new \Mustache_Loader_FilesystemLoader( $path.'/partials' );
		}

		return new \Mustache_Engine( $args );
	}

	protected function get_view_path( $part = FALSE, $path = NULL )
	{
		if ( is_null( $path ) )
			$path = $this->path.'views';

		return $part ? sprintf( '%s/%s.mustache', $path, $part ) : $path;
	}
}
