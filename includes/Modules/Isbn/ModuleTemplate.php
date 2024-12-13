<?php namespace geminorum\gEditorial\Modules\Isbn;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleTemplate extends gEditorial\Template
{

	const MODULE = 'isbn';

	public static function barcode( $atts = [] )
	{
		$args = self::atts( [
			'id'       => isset( $atts['post'] ) ? $atts['post'] : NULL,
			'filter'   => FALSE,
			'default'  => FALSE,
			'validate' => TRUE,
		], $atts );

		if ( ! $isbn = Services\PostTypeFields::getFieldRaw( 'isbn', $args['id'], 'meta', TRUE ) )
			return $args['default'];

		$isbn = Core\ISBN::sanitize( $isbn, TRUE );

		if ( $args['validate'] && ! Core\ISBN::validate( $isbn ) )
			return $args['default'];

		$args = self::atts( [
			'link'   => NULL,
			'before' => '',
			'after'  => '',
			'echo'   => TRUE,
		], $atts );

		$html = Core\HTML::img( ModuleHelper::barcode( $isbn ), '-barcode-isbn', $isbn );

		if ( is_null( $args['link'] ) )
			$html = Core\HTML::link( $html, Info::lookupURLforISBN( $isbn ) );

		else if ( $args['link'] )
			$html = Core\HTML::link( $html, $args['link'] );

		$html = $args['before'].$html.$args['after'];

		if ( ! $args['before'] )
			echo $html;

		echo $html;
		return TRUE;
	}
}
