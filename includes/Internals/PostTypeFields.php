<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

trait PostTypeFields
{

	// NOTE: `$data` maybe empty
	protected function posttypefields__do_action_import_data( $post, $data, $override = FALSE, $check_access = TRUE, $module = 'meta' )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		do_action( sprintf( '%s_posttypefields_import_raw_data', $this->base ), $post, $data, $override, $check_access, $module );
	}

	protected function posttypefields_get_post_by( $field_key, $value, $posttype_constant, $sanitize = FALSE, $module = 'meta' )
	{
		if ( ! $field_key || ! $value || ! $posttype_constant || ! gEditorial()->enabled( $module ) )
			return FALSE;

		$metakey  = gEditorial()->module( $module )->get_postmeta_key( $field_key );
		$posttype = $this->constant( $posttype_constant, $posttype_constant );

		if ( $sanitize ) {

			if ( ! $field = gEditorial()->module( $module )->get_posttype_field_args( $field_key, $posttype ) )
				$value = Core\Number::intval( trim( $value ), FALSE );

			else
				$value = gEditorial()->module( $module )->sanitize_posttype_field( $value, $field );

			if ( ! $value )
				return FALSE;
		}

		if ( $matches = WordPress\PostType::getIDbyMeta( $metakey, $value, FALSE ) )
			foreach ( $matches as $match )
				if ( $posttype === get_post_type( intval( $match ) ) )
					return intval( $match );

		return FALSE;
	}

	protected function posttypefields_connect_paired_by( $field_key, $data, $post )
	{
		if ( ! $this->_paired )
			return FALSE;

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		$values = WordPress\Strings::getSeparated( $data );
		$list   = [];

		foreach ( $values as $value )
			if ( $parent = $this->posttypefields_get_post_by( $field_key, $value, $constants[0], TRUE ) )
				$list[] = $parent;

		if ( count( $list ) )
			$this->paired_do_store_connection(
				$post,
				$list,
				$constants[0],
				$constants[1],
				$this->get_setting( 'multiple_instances' )
			);

		return $list;
	}
}
