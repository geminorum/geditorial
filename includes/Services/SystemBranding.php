<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class SystemBranding extends gEditorial\Service
{
	const AUTHOR_URL   = 'https://geminorum.ir/';
	const HOMEPAGE_URL = 'https://geminorum.ir/wordpress/geditorial';
	const GITHUB_URL   = 'https://github.com/geminorum/geditorial';
	const LATEST_URL   = 'https://github.com/geminorum/geditorial/releases/latest';
	const ISSUES_URL   = 'https://github.com/geminorum/geditorial/issues';
	const WIKI_URL     = 'https://github.com/geminorum/geditorial/wiki';

	public static function credits( ?string $context = NULL ): void
	{
		if ( GEDITORIAL_DISABLE_CREDITS )
			return;

		echo '<div class="credits">';

		echo '<p>';
			echo 'This is a fork in structure of <a href="http://editflow.org/">EditFlow</a><br />';
			echo '<a href="'.static::ISSUES_URL.'" target="_blank">Feedback, Ideas and Bug Reports</a> are welcomed.<br />';
			echo 'You\'re using gEditorial <a href="'.static::LATEST_URL.'" target="_blank" title="Check for the latest version">v'.GEDITORIAL_VERSION.'</a>';
		echo '</p>';

		echo '<a href="'.static::AUTHOR_URL.'" title="it\'s a geminorum project"><img src="'
			.GEDITORIAL_URL.'assets/images/itsageminorumproject-lightgrey.min.svg" alt="" /></a>';

		echo '</div>';
	}

	public static function signature( ?string $context = NULL ): void
	{
		if ( GEDITORIAL_DISABLE_CREDITS )
			return;

		echo '<div class="signature clear"><p>';
			printf(
				/* translators: `%1$s`: the plugin URL, `%2$s`: author URL */
				_x( '<a href="%1$s">gEditorial</a> is a <a href="%2$s">geminorum</a> project.', 'Service: System Branding: Signature', 'geditorial-admin' ),
				static::GITHUB_URL,
				static::AUTHOR_URL,
			);
		echo '</p></div>';
	}
}
