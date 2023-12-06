<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

trait PostDate
{

	public function postdate__get_post_data_for_latechores( $post, $metakeys )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$date = FALSE;

		foreach ( (array) $metakeys as $metakey )
			if ( $metakey && ( $date = get_post_meta( $post->ID, $metakey, TRUE ) ) )
				break;

		if ( ! $date )
			return FALSE;

		if ( Core\Date::isInFormat( $date, 'Y-m-d' ) )
			$datetime = sprintf( '%s 23:59:59', $date );

		else if ( Core\Date::isInFormat( $date, Core\Date::MYSQL_FORMAT ) )
			$datetime = $date;

		else
			return $this->log( 'FAILED', sprintf( 'after-care process of #%s: date is not valid: %s', $post->ID, $date ), [ $post->ID, $date ] );

		return [
			'post_date'     => $datetime,
			'post_date_gmt' => get_gmt_from_date( $datetime ),
		];
	}
}
