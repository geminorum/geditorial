<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Plugin;
use geminorum\gEditorial\Helper;

trait RawImports
{

	// protected $imports_datafile = ''; // NOTE: MUST SET ON MODULE

	protected function get_imports_datafile()
	{
		return empty( $this->imports_datafile ) ? FALSE : sprintf( '%sdata/%s', $this->path, $this->imports_datafile );
	}

	// DEFAULT METHOD
	protected function get_imports_raw_data( $type = NULL )
	{
		if ( empty( $this->imports_datafile ) )
			return FALSE;

		$data  = NULL;
		$group = $this->hook_base( 'rawimports_data' );

		if ( FALSE !== ( $cache = wp_cache_get( $this->key, $group ) ) )
			return $cache;

		if ( is_null( $type ) ) {

			$filetype = wp_check_filetype( $this->imports_datafile, [
				'csv'  => 'text/csv',
				'json' => 'application/json',
				'xml'  => 'application/xml',
				'php'  => 'application/x-httpd-php',
			] );

			$type = $filetype['ext'];
		}

		switch ( $type ) {
			case 'csv' : $data = Helper::parseCSV( $this->get_imports_datafile() ); break;
			case 'json': $data = Helper::parseJSON( $this->get_imports_datafile() ); break;
			case 'xml' : $data = Helper::parseXML( $this->get_imports_datafile() ); break;
			case 'php' : $data = Core\File::requireData( $this->get_imports_datafile(), [] ); break;
		}

		if ( empty( $data ) )
			wp_cache_set( $this->key, NULL, $group ); // to avoid repeats

		else
			wp_cache_set( $this->key, $data, $group );

		return empty( $data ) ? NULL : $data;
	}

	protected function get_imports_page_url( $sub = NULL, $extra = [] )
	{
		return $this->get_module_url( 'imports', is_null( $sub ) ? $this->key : $sub, $extra );
	}

	protected function render_imports_toolbox_card( $imports_url = NULL )
	{
		if ( is_null( $imports_url ) )
			$imports_url = $this->get_imports_page_url();

		echo $this->wrap_open( 'card -toolbox-card' );

			Core\HTML::h4( $this->get_string( 'title', 'wp_importer', 'misc', $this->module->title ), 'title' );
			Core\HTML::desc( $this->get_string( 'description', 'wp_importer', 'misc', '' ) );

			$link = Core\HTML::tag( 'a' , [
				'href'  => $imports_url,
				'class' => [ 'button', '-button' ],
			], $this->get_string( 'button', 'wp_importer', 'misc',
				_x( 'Go to Imports', 'Module: Importer Button', 'geditorial' ) ) );

			echo Core\HTML::wrap( Core\HTML::renderList( (array) $link ), '-toolbox-links' );

		echo '</div>';
	}

	protected function _hook_wp_register_importer()
	{
		if ( ! function_exists( 'register_importer' ) )
			return FALSE;

		return register_importer(
			$this->classs(),
			$this->get_string( 'title', 'wp_importer', 'misc', $this->module->title ),
			$this->get_string( 'description', 'wp_importer', 'misc', '' ),
			[ $this, '_callback_wp_register_importer' ]
		);
	}

	public function _callback_wp_register_importer()
	{
		$url = $this->get_imports_page_url();

		echo $this->wrap_open( 'wrap' ); // NOTE: needs `wrap` class for admin styles

			Core\HTML::h1( $this->get_string( 'title', 'wp_importer', 'misc', $this->module->title ) );
			Core\HTML::desc( sprintf( $this->get_string( 'redirect', 'wp_importer', 'misc', Plugin::moment( FALSE ) ), $url ) );

			Core\WordPress::redirectJS( $url, 1000 );
		echo '</div>';
	}
}
