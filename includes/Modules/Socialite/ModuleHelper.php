<?php namespace geminorum\gEditorial\Modules\Socialite;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Misc;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{
	const MODULE = 'socialite';

	// better to define here!
	public static function getIcon( string $field, ?string $context = NULL ): mixed
	{
		$default = [ 'gridicons', 'share' ];

		switch ( $field ) {
			case 'twitter'  : return [ 'social-logos', 'x' ];
			case 'tiktok'   : return [ 'social-logos', 'tiktok' ];
			case 'instagram': return [ 'social-logos', 'instagram' ];
			case 'telegram' : return [ 'social-logos', 'telegram' ];
			case 'facebook' : return [ 'social-logos', 'facebook' ];
			case 'youtube'  : return [ 'social-logos', 'youtube' ];
			case 'aparat'   : return [ 'misc-24', 'aparat' ];
			case 'behkhaan' : return [ 'misc-32', 'behkhaan' ];
			case 'fidibo'   : return [ 'misc-16', 'fidibo' ];
			case 'goodreads': return [ 'misc-24', 'goodreads' ];
			case 'eitaa'    : return [ 'misc-48', 'eitaa' ];
			case 'wikipedia': return [ 'misc-16', 'wikipedia' ];
			case 'neshan'   : return [ 'misc-16', 'octicons-location' ];
			case 'balad'    : return [ 'misc-16', 'octicons-location' ];
			case '_ical'    : return [ 'misc-16', 'calendar-plus-fill' ];
		}

		return Core\Icon::guess( $field, $default );
	}

	public static function getURLforTerm( string $field, object $term, ?string $context = NULL ): false|string
	{
		if ( '_ical' === $field )
			return Services\Calendars::linkTermCalendar( $term, $context );

		if ( in_array( $field, [ '_contact', '_email', '_url' ], TRUE ) )
			$field = Core\Text::stripPrefix( $field, '_' );

		if ( ! $metakey = Services\TaxonomyFields::getTermMetaKey( $field, $term->taxonomy ) )
			return FALSE;

		if ( ! $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
			return FALSE;

		switch ( $field ) {
			case 'twitter'  :
			case 'tiktok'   :
			case 'facebook' :
			case 'instagram':
			case 'telegram' :
			case 'youtube'  :
			case 'aparat'   :
			case 'behkhaan' :
			case 'fidibo'   :
			case 'goodreads':
			case 'eitaa'    :
			case 'wikipedia':
			case 'neshan':
			case 'balad':

				return Core\Socials::getHandleURL( $meta, $field );

			// Extra support for front-end only.
			case 'contact':
			case 'email':
			case 'url':
				return Core\Text::trim( $meta );
		}

		return Core\HTML::escapeURL( $meta );
	}

	public static function getURLforPost( string $field, object $post, ?string $context = NULL ): false|string
	{
		if ( '_ical' === $field )
			return Services\Calendars::linkPostCalendar( $post, $context );

		if ( in_array( $field, [ '_contact', '_email', '_url' ], TRUE ) )
			$field = Core\Text::stripPrefix( $field, '_' );

		if ( ! $meta = Services\PostTypeFields::getFieldRaw( $field, $post->ID ) )
			return FALSE;

		switch ( $field ) {
			case 'twitter'  :
			case 'tiktok'   :
			case 'facebook' :
			case 'instagram':
			case 'telegram' :
			case 'youtube'  :
			case 'aparat'   :
			case 'behkhaan' :
			case 'fidibo'   :
			case 'goodreads':
			case 'eitaa'    :
			case 'wikipedia':
			case 'neshan':
			case 'balad':

				return Core\Socials::getHandleURL( $meta, $field );

			// Extra support for front-end only.
			case 'contact':
			case 'email':
			case 'url':
				return Core\Text::trim( $meta );
		}

		return Core\HTML::escapeURL( $meta );
	}
}
