<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\WordPress;

class Paired extends WordPress\Main
{

	const BASE = 'geditorial';

	const PAIRED_TAXONOMY_PROP  = 'paired_taxonomy';
	const PAIRED_POSTTYPE_PROP  = 'paired_posttype';
	const PAIRED_SUPPORTED_PROP = 'paired_supported';
	const PAIRED_REST_FROM      = 'paired-from-items';
	const PAIRED_REST_TO        = 'paired-to-items';

	public static function setup()
	{
		add_filter( static::BASE.'_tabloid_post_summaries',
			[ __CLASS__, 'tabloid_post_summaries_multipaired' ], 45, 4 );

		add_filter( static::BASE.'_papered_view_data_for_post',
			[ __CLASS__, 'papered_view_data_for_post' ], 99, 4 );
	}

	// returns the paired taxonomy, otherwise `FALSE`
	public static function isPostType( $posttype )
	{
		if ( ! $posttype = WordPress\PostType::object( $posttype ) )
			return FALSE;

		return empty( $posttype->{self::PAIRED_TAXONOMY_PROP} ) ? FALSE : $posttype->{self::PAIRED_TAXONOMY_PROP};
	}

	// returns the paired posttype, otherwise `FALSE`
	public static function isTaxonomy( $taxonomy )
	{
		if ( ! $taxonomy = WordPress\Taxonomy::object( $taxonomy ) )
			return FALSE;

		return empty( $taxonomy->{self::PAIRED_POSTTYPE_PROP} ) ? FALSE : $taxonomy->{self::PAIRED_POSTTYPE_PROP};
	}

	public static function getPostTypes()
	{
		$list = [];

		foreach ( get_post_types( [ '_builtin' => FALSE ] ) as $posttype )
			if ( $paired = self::isPostType( $posttype ) )
				$list[$posttype] = $paired;

		return apply_filters( sprintf( '%s_paired_posttypes', static::BASE ), $list );
	}

	// OLD: `paired_get_to_term_direct()`
	public static function getToTerm( $post, $posttype, $taxonomy )
	{
		if ( empty( $post ) || ( ! $post = WordPress\Post::get( $post ) ) )
			return FALSE;

		if ( ! $term_id = get_post_meta( $post->ID, sprintf( '_%s_term_id', $posttype ), TRUE ) )
			return FALSE;

		return get_term_by( 'id', (int) $term_id, $taxonomy );
	}

	public static function getGlobalSummaryForPost( $post, $context = NULL, $fields = NULL )
	{
		// NOTE: must be $list['posttype'] = [ $items ];
		$list = apply_filters( static::BASE.'_paired_globalsummary_for_post', [], $post, $context );

		if ( empty( $list ) )
			return [];

		$args = [
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'post_type'      => array_keys( $list ), // NOTE: cannot use `any` because then checks for `viewable`
			'post_status'    => WordPress\Status::acceptable( array_keys( $list ), 'query', is_admin() ? [ 'pending', 'draft' ] : [] ),
			'post__in'       => Core\Arraay::prepNumeral( ...array_values( $list ) ),
			'fields'         => $fields ?? 'all', // or `ids`

			'ignore_sticky_posts'    => TRUE,
			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		];

		$posts = get_posts( apply_filters( static::BASE.'_paired_globalsummary_for_post_args', $args, $post, $list, $context ) );

		return empty( $posts ) ? [] : $posts;
	}

	public static function getGlobalSummaryForPostMarkup( $post, $context, $table_class = '' )
	{
		$items = self::getGlobalSummaryForPost( $post, $context );

		if ( empty( $items ) )
			return FALSE;

		/* translators: %s: item count */
		$default  = _x( 'Connected (%s)', 'Service: Paired: Global Summary Title', 'geditorial-admin' );
		$template = apply_filters( static::BASE.'_paired_globalsummary_for_post_title', $default, $post );
		$columns  = apply_filters( static::BASE.'_paired_globalsummary_for_post_columns', [
			'index' => _x( '#', 'Service: Paired: Global Summary Title Column', 'geditorial-admin' ),
			'date'  => _x( 'Date', 'Service: Paired: Global Summary Title Column', 'geditorial-admin' ),
			'type'  => _x( 'Type', 'Service: Paired: Global Summary Title Column', 'geditorial-admin' ),
			'title' => _x( 'Title', 'Service: Paired: Global Summary Title Column', 'geditorial-admin' ),
		], $post, $context );

		$posts = $types = [];

		foreach ( $items as $offset => $item ) {

			if ( empty( $types[$item->post_type] ) )
				$types[$item->post_type] = Helper::getPostTypeLabel( $item->post_type, 'singular_name' );

			$posts[] = apply_filters( static::BASE.'_paired_globalsummary_for_post_data', [
				'title' => self::_get_item_title( $item, $context ),
				'type'  => $types[$item->post_type],
				'date'  => self::_get_item_date( $item, $context ),
				'index' => Core\Number::localize( $offset + 1 ),
			], $item, $context );
		}

		return [
			'context' => $context,
			'count'   => count( $items ),
			'title'   => sprintf( $template, WordPress\Strings::getCounted( count( $items ) ) ),
			'html'    => Core\HTML::tableSimple( $posts, $columns, FALSE, $table_class ),
		];
	}

	private static function _get_item_title( $post, $context = NULL )
	{
		if ( ! in_array( $context, [ 'print', 'printpage', 'editpost' ], TRUE ) )
			return WordPress\Post::fullTitle( $post, 'overview' ) ?: '';

		if ( $custom = PostTypeFields::getFieldRaw( 'print_title', $post->ID ) )
			return $custom;

		return WordPress\Post::title( $post, FALSE );
	}

	private static function _get_item_date( $post, $context = NULL )
	{
		if ( $custom = PostTypeFields::getFieldRaw( 'print_date', $post->ID ) )
			return Datetime::prepForDisplay( $custom );

		return Datetime::prepForDisplay( $post->post_date );
	}

	public static function tabloid_post_summaries_multipaired( $list, $data, $post, $context )
	{
		if ( ! $markup = self::getGlobalSummaryForPostMarkup( $post, 'multipaired', 'field-wrap -table' ) )
			return $list;

		$list[] = [
			'key'     => 'multipaired',
			'class'   => '-paired-summary',
			'title'   => $markup['title'],
			'content' => Core\HTML::wrap( $markup['html'] ),
		];

		return $list;
	}

	public static function papered_view_data_for_post( $data, $profile, $source, $context )
	{
		if ( empty( $data['profile']['flags'] ) )
			return $data;

		if ( ! in_array( 'needs-globalsummary', (array) $data['profile']['flags'], TRUE ) )
			return $data;

		if ( ! $post = WordPress\Post::get( $source ) )
			return $data;

		if ( $markup = self::getGlobalSummaryForPostMarkup( $post, $context, 'table table-striped table-bordered' ) )
			$data['source']['tokens']['globalsummary'] = Core\HTML::wrap( $markup['html'] );

		return $data;
	}
}
