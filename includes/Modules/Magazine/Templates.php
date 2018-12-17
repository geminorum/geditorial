<?php namespace geminorum\gEditorial\Templates;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\WordPress;

class Magazine extends gEditorial\Template
{

	const MODULE = 'magazine';

	public static function getLatestIssueID()
	{
		return WordPress::getLastPostOrder( self::constant( 'issue_cpt', 'issue' ), '', 'ID', 'publish' );
	}

	public static function theIssue( $atts = [] )
	{
		if ( ! array_key_exists( 'item_title_cb', $atts ) )
			$atts['item_title_cb'] = [ __CLASS__, 'theIssueTitleCB' ];

		if ( ! array_key_exists( 'item_tag', $atts ) )
			$atts['item_tag'] = FALSE;

		return self::assocLink( $atts, static::MODULE );
	}

	public static function theIssueTitleCB( $post, $args = [] )
	{
		return trim( strip_tags( self::getMetaField( 'number', [
			'id'      => $post->ID,
			'default' => $args['item_title'],
		] ) ) );
	}

	public static function theIssueMeta( $field = 'number', $atts = [] )
	{
		if ( ! array_key_exists( 'echo', $atts ) )
			$atts['echo'] = TRUE;

		$meta = self::getMetaField( $field, $atts );

		if ( ! $atts['echo'] )
			return $meta;

		echo $meta;
		return TRUE;
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
			$atts['id'] = 'assoc';

		if ( ! array_key_exists( 'type', $atts ) )
			$atts['type'] = self::constant( 'issue_cpt', 'issue' );

		return parent::postImage( $atts, static::MODULE );
	}

	// FIXME: DEPRECATED
	public static function theIssueCover( $atts = [] )
	{
		self::_dep( 'gEditorialMagazineTemplates::theCover()' );

		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = NULL;

		return self::cover( $atts );
	}

	// FIXME: DEPRECATED
	public static function issueCover( $atts = [] )
	{
		self::_dep( 'gEditorialMagazineTemplates::cover()' );

		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = 'assoc';

		if ( ! array_key_exists( 'type', $atts ) )
			$atts['type'] = self::constant( 'issue_cpt', 'issue' );

		return parent::postImage( $atts, static::MODULE );
	}

	// FIXME: DEPRECATED
	public static function sanitize_field( $field )
	{
		self::_dep( 'gEditorialMagazineTemplates::sanitizeField()' );

		if ( is_array( $field ) )
			return $field;

		$fields = [
			'over-title' => [ 'ot', 'over-title' ],
			'sub-title'  => [ 'st', 'sub-title' ],
			'number'     => [ 'issue_number_line', 'number' ],
			'pages'      => [ 'issue_total_pages', 'pages' ],
			'start'      => [ 'in_issue_page_start', 'start' ],
			'order'      => [ 'in_issue_order', 'order' ],
		];

		if ( isset( $fields[$field] ) )
			return $fields[$field];

		return [ $field ];
	}
}