<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\WordPress;

class HeaderButtons extends gEditorial\Service
{
	public static function setup()
	{
		if ( ! is_admin() )
			return;

		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_enqueue_scripts' ], 9, 1 );

		// TODO: move to `Barcodes` Service
		add_filter( 'kses_allowed_protocols', function ( $protocols ) {
			return array_merge( $protocols, [ 'binaryeye' ] );
		} );
	}

	public static function register( $name, $atts = [], $override = FALSE )
	{
		global $gEditorialHeaderButtons;

		if ( empty( $gEditorialHeaderButtons ) )
			$gEditorialHeaderButtons = [];

		if ( empty( $name ) )
			return FALSE;

		if ( ! $override && array_key_exists( $name, $gEditorialHeaderButtons ) )
			return $name;

		$gEditorialHeaderButtons[$name] = self::atts( [
			'html'  => FALSE, // will override the whole link!
			'name'  => $name,
			'text'  => $name,
			'title' => NULL,
			'id'    => FALSE,
			'link'  => FALSE,
			'class' => FALSE,
			'icon'  => FALSE,
			'data'  => [],

			'priority'       => 10,
			'cap_check'      => FALSE,   // bool or cap
			'hide_in_search' => TRUE,
			'newtab'         => FALSE,
		], $atts );

		return $name;
	}

	public static function admin_enqueue_scripts( $hook_suffix )
	{
		global $gEditorialHeaderButtons;

		if ( empty( $gEditorialHeaderButtons ) )
			return;

		$buttons = $gEditorialHeaderButtons;
		$search  = self::req( 's' );
		$args    = [ 'buttons' => [] ];

		if ( count( $buttons ) > 1 )
			$buttons = Core\Arraay::sortByPriority( $buttons, 'priority' );

		foreach ( $buttons as $name => $button ) {

			if ( $search && ! empty( $button['hide_in_search'] ) )
				continue;

			if ( ! empty( $button['cap_check'] ) && ! WordPress\User::cuc( $button['cap_check'] ) )
				continue;

			if ( ! empty( $button['icon'] ) )
				$button['text'] = sprintf( '%s %s', Helper::getIcon( $button['icon'] ), $button['text'] );

			if ( ! empty( $button['html'] ) )
				$args['buttons'][] = $button['html'];

			else
				$args['buttons'][] = Core\HTML::tag( 'a', [
					'id'     => $button['id'],
					'data'   => $button['data'],
					'href'   => $button['link'] ?: '#',
					'title'  => $button['title'] ?: FALSE,
					'target' => $button['newtab'] ? '_blank': FALSE,
					'class'  => Core\HTML::attrClass(
						'page-title-action', // NOTE: should not use `.button`!
						empty( $button['icon'] ) ? '' : '-has-icon',
						$button['class']
					),
				], trim( $button['text'] ) );
		}

		Scripts::enqueue( 'admin.headerbuttons.all' );
		gEditorial()->enqueue_asset_config( $args, 'headerbuttons' );
	}

	// TODO: move to `Barcodes` Service
	public static function registerSearchWithBarcode()
	{
		/**
		 * You can invoke Binary Eye with a web URI intent from anything
		 * that can open URIs. There are two options:
		 *
		 * - `binaryeye://scan`
		 * - `http(s)://markusfisch.de/BinaryEye`
		 *
		 * If you want to get the scanned contents, you can add a `ret` query
		 * argument with a (URL encoded) URI template. For example:
		 *
		 * `http://markusfisch.de/BinaryEye?ret=http%3A%2F%2Fexample.com%2F%3Fresult%3D{RESULT}`
		 *
		 * Supported symbols are:
		 * `RESULT` - scanned content
		 * `RESULT_BYTES` - raw result as a hex string
		 * `FORMAT` - barcode format
		 *
		 * @source https://github.com/markusfisch/BinaryEye
		 */
		$url = add_query_arg( [
			's'      => '{RESULT}',
			// 'format' => '{FORMAT}',
		], Core\URL::current() );

		self::register( 'barcodescanner', [
			'icon'  => [ 'misc-512', 'openlibrary-barcodescanner' ],
			'text'  => '',
			'title' => _x( 'Scan to Search using BinaryEye', 'Service: HeaderButtons: Title Attr', 'geditorial-admin' ),
			'link'  => sprintf( 'binaryeye://scan?ret=%s', rawurlencode( $url ) ),
			'class' => [
				'-only-icon',
				'-mobile-only-inline-block',
			],
			'hide_in_search' => FALSE,
			'priority'       => 9999,
		] );
	}
}
