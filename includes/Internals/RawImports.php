<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait RawImports
{

	// NOTE: MUST SET ON MODULE
	// protected $imports_datafiles = [
	// 	'default' => 'default.json',
	// ];

	protected function get_imports_datafile( $key = 'default' )
	{
		return empty( $this->imports_datafiles[$key] )
			? FALSE
			: sprintf( '%sdata/%s',
				$this->path,
				$this->imports_datafiles[$key]
			);
	}

	// DEFAULT METHOD
	protected function get_imports_raw_data( $key = 'default', $type = NULL )
	{
		if ( empty( $this->imports_datafiles[$key] ) )
			return FALSE;

		$data  = NULL;
		$id    = $this->hook_key( $key );
		$group = $this->hook_base( 'rawimports_data' );

		if ( FALSE !== ( $cache = wp_cache_get( $id, $group ) ) )
			return $cache;

		if ( is_null( $type ) ) {

			$filetype = Core\File::type( $this->imports_datafiles[$key], [
				'csv'  => 'text/csv',
				'json' => 'application/json',
				'xml'  => 'application/xml',
				'php'  => 'application/x-httpd-php',
				'txt'  => 'text/plain',
			] );

			$type = $filetype['ext'];
		}

		switch ( $type ) {
			case 'csv' : $data = gEditorial\Parser::fromCSV_Legacy( $this->get_imports_datafile( $key ) );  break;
			case 'json': $data = gEditorial\Parser::fromJSON_Legacy( $this->get_imports_datafile( $key ) ); break;
			case 'xml' : $data = gEditorial\Parser::fromXML_Legacy( $this->get_imports_datafile( $key ) );  break;
			case 'txt' : $data = gEditorial\Parser::fromTXT_Legacy( $this->get_imports_datafile( $key ) );  break;
			case 'php' : $data = Core\File::requireData( $this->get_imports_datafile( $key ), [] );         break;
		}

		wp_cache_set(
			$id,
			empty( $data ) ? NULL : $data, // to avoid repeats
			$group
		);

		return empty( $data ) ? NULL : $data;
	}

	protected function get_imports_page_url( $sub = NULL, $extra = [] )
	{
		return $this->get_module_url( 'imports', is_null( $sub ) ? $this->key : $sub, $extra );
	}

	protected function render_imports_toolbox_card( $imports_url = NULL )
	{
		$buttons = [];

		echo $this->wrap_open( 'card -toolbox-card' );

			Core\HTML::h4( $this->get_string( 'title', 'wp_importer', 'misc', $this->module->title ), 'title' );
			Core\HTML::desc( $this->get_string( 'description', 'wp_importer', 'misc', '' ) );

			$buttons[] = Core\HTML::button(
				$this->get_string( 'button', 'wp_importer', 'misc', _x( 'Go to Imports', 'Module: Importer Button', 'geditorial' ) ),
				$imports_url ?? $this->get_imports_page_url(),
			);

			echo Core\HTML::wrap( Core\HTML::rows( $buttons ), '-toolbox-links' );

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
			Core\HTML::desc( sprintf( $this->get_string( 'redirect', 'wp_importer', 'misc', gEditorial\Plugin::moment( FALSE ) ), $url ) );

			WordPress\Redirect::doJS( $url, 1000 );
		echo '</div>';
	}
}
