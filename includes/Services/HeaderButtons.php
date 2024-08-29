<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\WordPress;

class HeaderButtons extends WordPress\Main
{

	const BASE = 'geditorial';

	public static function setup()
	{
		if ( ! is_admin() )
			return;

		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_enqueue_scripts' ], 9, 1 );
	}

	public static function register( $name, $atts = [] )
	{
		global $gEditorialHeaderButtons;

		if ( empty( $gEditorialHeaderButtons ) )
			$gEditorialHeaderButtons = [];

		if ( empty( $name ) )
			return FALSE;

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

		foreach ( $gEditorialHeaderButtons as $name => $button ) {

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
						'page-title-action',
						'-button',
						'-header-button',
						empty( $button['icon'] ) ? '' : '-button-icon',
						$button['class']
					),
				], $button['text'] );
		}

		Scripts::enqueue( 'admin.headerbuttons.all' );
		gEditorial()->enqueue_asset_config( $args, 'headerbuttons' );
	}
}
