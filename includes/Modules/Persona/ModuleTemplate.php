<?php namespace geminorum\gEditorial\Modules\Persona;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleTemplate extends gEditorial\Template
{

	const MODULE = 'persona';

	public static function vcard( $atts = [], $check = TRUE )
	{
		$args = self::atts( [
			'id'      => NULL,
			'default' => FALSE,
			'before'  => '',
			'after'   => '',
			'before'  => '',
			'after'   => '',
			'echo'    => TRUE,
		], $atts );

		if ( $check && ! gEditorial()->enabled( 'meta' ) )
			return $args['default'];

		if ( ! $post = WordPress\Post::get( $args['id'] ) )
			return $args['default'];

		$vcard = new \JeroenDesloovere\VCard\VCard();
		$field = [
			'id'      => $post,
			'context' => 'export',
			'default' => '',
		];

		// gEditorial()->module( 'persona' )->make_human_title( $post, 'export' )
		$vcard->addName(
			self::getMetaField( 'last_name', $field, FALSE ),
			self::getMetaField( 'first_name', $field, FALSE ),
		);

		if ( $mobile = self::getMetaField( 'mobile_number', $field, FALSE ) )
			$vcard->addPhoneNumber( $mobile, 'PREF;MOBILE' );

		if ( $phone = self::getMetaField( 'phone_number', $field, FALSE ) )
			$vcard->addPhoneNumber( $phone, 'HOME' );

		if ( $home = self::getMetaField( 'home_address', $field, FALSE ) )
			$vcard->addAddress( $home, NULL, NULL, NULL, NULL, NULL, 'Iran', 'HOME' );

		// $vcard->addCompany( '' );
		// $vcard->addEmail( '' );
		// $vcard->addURL( '', 'PREF' );

		$html = $vcard->getOutput();

		if ( ! $args['echo'] )
			return $args['before'].$html.$args['after'];

		echo $args['before'].$html.$args['after'];

		return TRUE;
	}

	public static function summary( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = NULL;

		if ( ! array_key_exists( 'type', $atts ) )
			$atts['type'] = self::constant( 'human_posttype', 'human' );

		return self::metaSummary( $atts );
	}

	public static function theCover( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = NULL;

		return self::cover( $atts );
	}

	public static function cover( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = 'paired';

		if ( ! array_key_exists( 'type', $atts ) )
			$atts['type'] = self::constant( 'human_posttype', 'human' );

		return parent::postImage( $atts, static::MODULE );
	}
}
