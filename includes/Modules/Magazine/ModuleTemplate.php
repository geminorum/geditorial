<?php namespace geminorum\gEditorial\Modules\Magazine;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleTemplate extends gEditorial\Template
{

	const MODULE = 'magazine';

	public static function getLatestIssueID(): int
	{
		return (int) WordPress\PostType::getLastMenuOrder( self::constant( 'primary_posttype', 'issue' ), '', 'ID', 'publish' );
	}

	public static function theIssue( array $atts = [] ): bool|string
	{
		if ( ! array_key_exists( 'item_title_cb', $atts ) )
			$atts['item_title_cb'] = [ __CLASS__, 'theIssueTitleCB' ];

		if ( ! array_key_exists( 'item_tag', $atts ) )
			$atts['item_tag'] = FALSE;

		return self::pairedLink( $atts, static::MODULE );
	}

	public static function theIssueTitleCB( object $post, array $args = [] ): string
	{
		return Core\Text::stripTags( self::getMetaField( 'number_line', [
			'id'      => $post->ID,
			'default' => $args['item_title'],
		] ) );
	}

	public static function theIssueMeta( string $field = 'number_line', array $atts = [] ): bool|string
	{
		if ( ! array_key_exists( 'echo', $atts ) )
			$atts['echo'] = TRUE;

		$meta = self::getMetaField( $field, $atts );

		if ( ! $atts['echo'] )
			return $meta;

		echo $meta;
		return TRUE;
	}

	public static function theCover( array $atts = [] ): bool|string
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = NULL;

		return self::cover( $atts );
	}

	public static function cover( array $atts = [] ): bool|string
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = 'paired';

		if ( ! array_key_exists( 'type', $atts ) )
			$atts['type'] = self::constant( 'primary_posttype', 'issue' );

		return parent::postImage( $atts, static::MODULE );
	}

	#[\Deprecated('USE `Template::theCover()`')]
	public static function theIssueCover( array $atts = [] ): bool|string
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = NULL;

		return self::cover( $atts );
	}

	#[\Deprecated('USE `Template::cover()`')]
	public static function issueCover( array $atts = [] ): bool|string
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = 'paired';

		if ( ! array_key_exists( 'type', $atts ) )
			$atts['type'] = self::constant( 'primary_posttype', 'issue' );

		return parent::postImage( $atts, static::MODULE );
	}

	#[\Deprecated()]
	public static function sanitize_field( array|string $field ): array
	{
		if ( is_array( $field ) )
			return $field;

		$fields = [
			'over-title' => [ 'ot', 'over-title' ],
			'sub-title'  => [ 'st', 'sub-title' ],
			'number'     => [ 'number_line', 'issue_number_line', 'number' ],
			'pages'      => [ 'issue_total_pages', 'pages' ],
			'start'      => [ 'in_issue_page_start', 'start' ],
			'order'      => [ 'in_issue_order', 'order' ],
		];

		if ( isset( $fields[$field] ) )
			return $fields[$field];

		return [ $field ];
	}

	public static function spanTiles( array $atts = [] ): bool|string
	{
		if ( ! array_key_exists( 'taxonomy', $atts ) )
			$atts['taxonomy'] = self::constant( 'span_taxonomy', 'issue_span' );

		if ( ! array_key_exists( 'posttype', $atts ) )
			$atts['posttype'] = self::constant( 'primary_posttype', 'issue' );

		return parent::getSpanTiles( $atts, static::MODULE );
	}
}
