<?php namespace geminorum\gEditorial\Modules\Banking;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'banking';

	// NOTE: like `WordPress\Post::summary()`
	public static function generateSummary( $info )
	{
		$summary     = [];
		$description = [];

		if ( ! empty( $info['banklogo'] ) )
			$summary['image'] = $info['banklogo'];

		if ( ! empty( $info['account'] ) )
			$summary['title'] = sprintf(
				/* translators: %s: account */
				_x( 'Number: %s', 'Helper', 'geditorial-banking' ),
				Core\Number::localize( $info['account'] )
			);

		if ( ! empty( $info['bankname'] ) )
			$description[] = sprintf(
				/* translators: %s: bank-name */
				_x( 'Account with %s', 'Helper', 'geditorial-banking' ),
				$info['bankname']
			);

		if ( ! empty( $info['country'] ) )
			$description[] = sprintf(
				/* translators: %s: country */
				_x( 'From %s', 'Helper', 'geditorial-banking' ),
				$info['country']
			);

		if ( ! empty( $description ) )
			$summary['description'] = WordPress\Strings::getJoined( $description, '<p>', '</p>', '', '</p><p>' );

		return $summary;
	}
}
