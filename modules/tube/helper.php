<?php namespace geminorum\gEditorial\Helpers;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;

class Tube extends gEditorial\Helper
{

	// @REF: https://gist.github.com/billerickson/82fb6f24599f95501d36b79a360ac8b1
	public static function thumbnailYoutube( $url )
	{
		if ( $id = preg_replace( '~(?:http|https|)(?::\/\/|)(?:www.|)(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/ytscreeningroom\?v=|\/feeds\/api\/videos\/|\/user\S*[^\w\-\s]|\S*[^\w\-\s]))([\w\-]{11})[a-z0-9;:@#?&%=+\/\$_.-]*~i', '$1', $url ) )
			return 'https://img.youtube.com/vi/'.$id.'/0.jpg';

		return FALSE;
	}
}
