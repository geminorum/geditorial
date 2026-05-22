<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class SystemBranding extends gEditorial\Service
{
	public static function credits()
	{
		if ( GEDITORIAL_DISABLE_CREDITS )
			return;

		echo '<div class="credits">';

		echo '<p>';
			echo 'This is a fork in structure of <a href="http://editflow.org/">EditFlow</a><br />';
			echo '<a href="https://github.com/geminorum/geditorial/issues" target="_blank">Feedback, Ideas and Bug Reports</a> are welcomed.<br />';
			echo 'You\'re using gEditorial <a href="https://github.com/geminorum/geditorial/releases/latest" target="_blank" title="Check for the latest version">v'.GEDITORIAL_VERSION.'</a>';
		echo '</p>';

		echo '<a href="https://geminorum.ir" title="it\'s a geminorum project"><img src="'
			.GEDITORIAL_URL.'assets/images/itsageminorumproject-lightgrey.min.svg" alt="" /></a>';

		echo '</div>';
	}

	public static function signature( $context = NULL )
	{
		if ( GEDITORIAL_DISABLE_CREDITS )
			return;

		echo '<div class="signature clear"><p>';
			printf(
				/* translators: `%1$s`: the plugin URL, `%2$s`: author URL */
				_x( '<a href="%1$s">gEditorial</a> is a <a href="%2$s">geminorum</a> project.', 'Service: System Branding: Signature', 'geditorial-admin' ),
				'https://github.com/geminorum/geditorial',
				'https://geminorum.ir/'
			);
		echo '</p></div>';
	}
}
