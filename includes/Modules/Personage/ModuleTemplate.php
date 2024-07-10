<?php namespace geminorum\gEditorial\Modules\Personage;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleTemplate extends gEditorial\Template
{

	const MODULE = 'personage';

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

		// @package `jeroendesloovere/vcard`
		$vcard = new \JeroenDesloovere\VCard\VCard();
		$field = [
			'id'      => $post,
			'context' => 'export',
			'default' => '',
		];

		// gEditorial()->module( 'personage' )->make_human_title( $post, 'export' )
		$vcard->addName(
			self::getMetaField( 'last_name', $field, FALSE ),
			self::getMetaField( 'first_name', $field, FALSE ),
		);

		if ( $mobile = self::getMetaField( 'mobile_number', $field, FALSE ) )
			$vcard->addPhoneNumber( $mobile, 'PREF;MOBILE' );

		if ( $mobile2 = self::getMetaField( 'mobile_secondary', $field, FALSE ) )
			$vcard->addPhoneNumber( $mobile2, 'MOBILE' );

		if ( $phone = self::getMetaField( 'phone_number', $field, FALSE ) )
			$vcard->addPhoneNumber( $phone, 'HOME' );

		if ( $phone2 = self::getMetaField( 'phone_secondary', $field, FALSE ) )
			$vcard->addPhoneNumber( $phone2, 'WORK' );

		if ( $home = self::getMetaField( 'home_address', $field, FALSE ) )
			/**
			 * Add address
			 *
			 * @param  string [optional] $name
			 * @param  string [optional] $extended
			 * @param  string [optional] $street
			 * @param  string [optional] $city
			 * @param  string [optional] $region
			 * @param  string [optional] $zip
			 * @param  string [optional] $country
			 * @param  string [optional] $type
			 *                                     $type may be DOM | INTL | POSTAL | PARCEL | HOME | WORK
			 *                                     or any combination of these: e.g. "WORK;PARCEL;POSTAL"
			 * @return $this
			 */
			$vcard->addAddress(
				$home,
				NULL,
				NULL,
				NULL,
				self::constant( 'GCORE_DEFAULT_PROVINCE_CODE', 'Tehran' ),
				NULL,
				self::constat( 'GCORE_DEFAULT_COUNTRY_CODE', 'Iran' ),
				'HOME'
			);

		if ( $work = self::getMetaField( 'work_address', $field, FALSE ) )
			$vcard->addAddress(
				$work,
				NULL,
				NULL,
				NULL,
				self::constant( 'GCORE_DEFAULT_PROVINCE_CODE', 'Tehran' ),
				NULL,
				self::constat( 'GCORE_DEFAULT_COUNTRY_CODE', 'Iran' ),
				'WORK'
			);

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
