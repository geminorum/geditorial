<?php namespace geminorum\gEditorial\Modules\Tabloid;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'tabloid';

	public static function stripByProp( $data, $key, $list, $subkey = 'name' )
	{
		if ( ! empty( $data[$key] ) && ! empty( $list ) ) {

			foreach ( $data[$key] as $offset => $meta )
				if ( in_array( $meta[$subkey], $list, TRUE ) )
					unset( $data[$key][$offset] );

			// NOTE: js mustache needs not object but array!
			if ( ! empty( $data[$key] ) )
				$data[$key] = array_values( $data[$key] );
		}

		return $data;
	}

	public static function stripEmptyValues( $data, $key, $subkey = 'rendered' )
	{
		if ( ! empty( $data[$key] ) ) {

			foreach ( $data[$key] as $offset => $meta )
				if ( empty( $meta[$subkey] ) )
					unset( $data[$key][$offset] );

			// NOTE: js mustache needs not object but array!
			if ( ! empty( $data[$key] ) )
				$data[$key] = array_values( $data[$key] );
		}

		return $data;
	}

	public static function prepCommentsforPost( $comments, $calendar = NULL, $fallback = [] )
	{
		if ( ! $comments )
			return $fallback;

		foreach ( $comments as &$comment ) {

			$avatar = empty( $comment['author_avatar_urls']['24'] ) ? '' : Core\HTML::img( $comment['author_avatar_urls']['24'] );
			$author = WordPress\User::getTitleRow( $comment['author'], $comment['author_name'], '<span title="%2$s">%1$s</span>' );

			$comment['content_rendered'] = $comment['content']['rendered'];
			$comment['author_rendered']  = Core\Text::trim( $avatar.' '.$author );
			$comment['date_rendered']    = gEditorial\Datetime::prepForDisplay(
				$comment['date'],
				gEditorial\Datetime::dateFormats( 'printdate' ),
				$calendar
			);

			unset( $comment['author_avatar_urls'] );
			unset( $comment['date_gmt'] );
			unset( $comment['content'] );
			unset( $comment['_links'] );
		}

		return $comments;
	}
}
