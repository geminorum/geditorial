<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class SystemHeartbeat extends gEditorial\Service
{
	const HEARTBEAT_KEY   = 'systemheartbeat';  // same as `mainkey` on the script
	const HEARTBEAT_VALUE = 'alive';

	public static function setup()
	{
		add_filter( 'heartbeat_received', [ __CLASS__, 'heartbeat_received' ], 10, 2 );

		add_action( 'admin_bar_menu', [ __CLASS__, 'admin_bar_menu' ], 99, 1 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ], -99 );
		add_action( 'wp_enqueue_scripts',    [ __CLASS__, 'enqueue_scripts' ], -99 );
	}

	public static function enqueue_scripts()
	{
		if ( ! is_admin_bar_showing() )
			return;

		// NOTE: since we need `gEditorial` object on this script!
		gEditorial()->enqueue_asset_config();

		gEditorial\Scripts::enqueue(
			'all.systemheartbeat',
			[
				'jquery',
				'heartbeat',
			]
		);
	}

	public static function admin_bar_menu( $wp_admin_bar )
	{
		if ( ! is_user_logged_in() )
			return;

		gEditorial()->enqueue_adminbar( TRUE );

		// Add the top-level Notifications button.
		$GLOBALS['wp_admin_bar']->add_menu( [
			'parent' => 'top-secondary',
			'id'     => self::classs( static::HEARTBEAT_KEY ),
			'title'  => Icons::adminBarMarkup( 'heart' ),
			'href'   => '#',
			'meta' => [
				// NOTE: heartbeat intervals by default will be determined on the client side.
				'title' => _x( 'Current System Heartbeat ❤', 'Service: System Heartbeat', 'geditorial' ),
				'class' => self::classs( 'adminbar', 'node', 'icononly' ),
			],
		] );
	}

	/**
	 * Adds the system heartbeat to the response.
	 *
	 * @param array $response
	 * @param array $data
	 * @return array
	 */
	public static function heartbeat_received( $response, $data )
	{
		if ( ( $data[static::HEARTBEAT_KEY] ?? '' ) === static::HEARTBEAT_VALUE )
			$response[static::HEARTBEAT_KEY] = $data[static::HEARTBEAT_KEY];

		return $response;
	}
}
