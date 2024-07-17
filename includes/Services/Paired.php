<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Datetime;
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

		add_filter( static::BASE.'_papered_view_data',
			[ __CLASS__, 'papered_view_data' ], 99, 4 );
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
			'post_type'      => array_keys( $list ), // NOTE: cannot use `any` because then checks for viewable
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

	public static function getGlobalSummaryForPostMarkup( $post, $context, $wrap_class = '' )
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
			'title' => _x( 'Title', 'Service: Paired: Global Summary Title Column', 'geditorial-admin' ),
		], $post, $context );

		$posts = [];

		foreach ( $items as $offset => $item ) {

			if ( in_array( $context, [ 'print', 'printpage' ], TRUE ) )
				$title = WordPress\Post::title( $item, FALSE );

			else
				$title = WordPress\Post::fullTitle( $item, 'overview' ) ?: '';

			$posts[] = apply_filters( static::BASE.'_paired_globalsummary_for_post_data', [
				'title' => $title,
				'date'  => Datetime::prepForDisplay( $item->post_date ),
				'index' => Core\Number::localize( $offset + 1 ),
			], $item, $context );
		}

		return [
			'context' => $context,
			'count'   => count( $items ),
			'title'   => sprintf( $template, WordPress\Strings::getCounted( count( $items ) ) ),
			'html'    => Core\HTML::tableSimple( $posts, $columns, FALSE, $wrap_class ),
		];
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

	public static function papered_view_data( $data, $profile, $source, $context )
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
