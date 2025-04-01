<?php namespace geminorum\gEditorial\Modules\Home;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;

class ModuleInfo extends gEditorial\Info
{
	const MODULE = 'home';

	public static function getHelpTabs( $context = NULL )
	{
		return [
			[
				'title'   => _x( 'Featured Content', 'Help Tab Title', 'geditorial-home' ),
				'id'      => static::classs( 'featured-content' ),
				'content' => self::buffer( [ __CLASS__, 'renderrenderHelpTab_featured_content' ] ),
			],
		];
	}

	public static function renderrenderHelpTab_featured_content()
	{
		echo '<div class="-info"><p>Featured Content allows users to spotlight their posts and have them uniquely displayed by a theme. The content is intended to be displayed on a blog’s front page; by using the module consistently in this manner, users are given a reliable Featured Content experience on which they can rely even when switching themes.</p>';
		echo '<code><pre>
add_theme_support( \'featured-content\', [
	\'filter\'     => \'mytheme_get_featured_posts\',
	\'max_posts\'  => 20,
	\'post_types\' => [ \'post\', \'page\' ),
) );
</pre></code>';
		echo '<p class="-from">Adopted from: <a href="https://jetpack.com/support/featured-content/" target="_blank">Jetpack Featured Content</a> by <a href="https://automattic.com/" target="_blank">Automattic</a></p></div>';

	}
}
