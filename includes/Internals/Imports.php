<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Plugin;
use geminorum\gEditorial\Helper;

trait Imports
{

	// protected $imports_datafile = ''; // NOTE: MUST SET ON MODULE

	protected function get_imports_datafile()
	{
		return empty( $this->imports_datafile ) ? FALSE : sprintf( '%sdata/%s', $this->path, $this->imports_datafile );
	}

	// DEFAULT METHOD
	protected function get_imports_raw_data()
	{
		if ( empty( $this->imports_datafile ) )
			return FALSE;

		$filetype = wp_check_filetype( $this->imports_datafile, [
			'csv'  => 'text/csv',
			'json' => 'application/json',
			'xml'  => 'application/xml',
		] );

		switch( $filetype['ext'] ) {
			case 'csv' : return Helper::parseCSV( $this->get_imports_datafile() );
			case 'json': return Helper::parseJSON( $this->get_imports_datafile() );
			case 'xml' : return Helper::parseXML( $this->get_imports_datafile() );
		}

		return FALSE;
	}

	protected function get_imports_page_url( $sub = NULL )
	{
		return $this->get_module_url( 'imports', is_null( $sub ) ? $this->key : $sub );
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
