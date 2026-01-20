<?php namespace geminorum\gEditorial\Modules\Papered;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'papered';

	public static function getPostProps( $post, $fallback = [] )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return $fallback;

		return Core\Arraay::stripByKeys( get_object_vars( $post ), [
			'post_content_filtered',
			'to_ping',
			'to_ping',
			'pinged',
			'guid',
			'filter',
			'comment_status',
			'ping_status',
			'post_password',
		] );
	}

	public static function getPostMetas( $post, $fallback = [] )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return $fallback;

		return Core\Arraay::stripByKeys( WordPress\Post::getMeta( $post ), [
			'_edit_lock',
		] );
	}

	public static function getGeneralTokens( $post, $fallback = [] )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return $fallback;

		return [
			'today' => gEditorial\Datetime::dateFormat( 'now', 'print' ),
		];
	}

	public static function getPosttypeFieldsData( $post, $module = 'meta', $fallback = [] )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return $fallback;

		return Core\Arraay::pluck(
			gEditorial()->module( $module )->get_posttype_fields_data( $post, FALSE, 'print' ),
			'rendered',
			'name'
		);
	}
}
