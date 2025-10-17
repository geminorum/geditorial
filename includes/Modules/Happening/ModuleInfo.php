<?php namespace geminorum\gEditorial\Modules\Happening;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;

class ModuleInfo extends gEditorial\Info
{

	const MODULE = 'happening';

	// @REF: https://support.cvent.com/s/communityarticle/What-does-my-Event-Status-mean
	public static function getStatuses( $context = NULL )
	{
		return [
			'processing' => [
				'slug' => 'processing',
				'name' => 'Processing',
				'meta' => [ 'tagline' => _x( 'The event is being created.', 'Info: Default Term for Status', 'geditorial-happening' ) ],
			],
			'upcoming' => [
				'slug' => 'upcoming',
				'name' => 'Upcoming',
				'meta' => [ 'tagline' => _x( 'The event start date and time is in the future.', 'Info: Default Term for Status', 'geditorial-happening' ) ],
			],
			'ongoing' => [
				'slug' => 'ongoing',
				'name' => 'Ongoing',
				'meta' => [ 'tagline' => _x( 'The event start date and time is in the past, and the event end date and time is in the future.', 'Info: Default Term for Status', 'geditorial-happening' ) ],
			],
			'completed' => [
				'slug' => 'completed',
				'name' => 'Completed',
				'meta' => [ 'tagline' => _x( 'The event\'s end date and time is in the past.', 'Info: Default Term for Status', 'geditorial-happening' ) ],
			],
			'archived' => [
				'slug' => 'archived',
				'name' => 'Archived',
				'meta' => [ 'tagline' => _x( 'The event\'s archive date is in the past.', 'Info: Default Term for Status', 'geditorial-happening' ) ],
			],
			'cancelled' => [
				'slug' => 'cancelled',
				'name' => 'Cancelled',
				'meta' => [ 'tagline' => _x( 'A planner has Canceled the event.', 'Info: Default Term for Status', 'geditorial-happening' ) ],
			],
		];
	}
}
