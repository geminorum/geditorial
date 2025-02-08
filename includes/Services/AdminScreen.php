<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class AdminScreen extends WordPress\Main
{

	const BASE = 'geditorial';

	public static function setup()
	{
		if ( ! is_admin() )
			return;

		add_action( 'current_screen', [ __CLASS__, 'current_screen' ], 1, 1 );
	}

	public static function current_screen( $screen )
	{
		if ( empty( $screen->post_type ) )
			return;

		if ( ! $posttype = WordPress\PostType::object( $screen->post_type ) )
			return;

		$extra = [];

		if ( ! empty( $posttype->tinymce_disabled ) )
			$extra[] = 'disable-posttype-tinymce';

		if ( ! empty( $posttype->slug_disabled ) ) {
			remove_meta_box( 'slugdiv', $screen, 'normal' );
			$extra[] = 'disable-posttype-slug';
		}

		if ( ! empty( $posttype->date_disabled ) )
			$extra[] = 'disable-posttype-date';

		if ( ! empty( $posttype->author_disabled ) ) {
			remove_meta_box( 'authordiv', $screen, 'normal' );
			$extra[] = 'disable-posttype-author';
		}

		if ( ! empty( $posttype->password_disabled ) )
			$extra[] = 'disable-posttype-password';

		if ( empty( $extra ) )
			return;

		add_filter( 'admin_body_class',
			static function ( $classes ) use ( $extra ) {
				return trim( $classes ).' '.Core\HTML::prepClass( $extra );
			} );
	}
}
