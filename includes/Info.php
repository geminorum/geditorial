<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Info extends WordPress\Main
{

	const BASE = 'geditorial';

	public static function lookupIP( $ip )
	{
		if ( function_exists( 'gnetwork_ip_lookup' ) )
			return gnetwork_ip_lookup( $ip );

		return $ip;
	}

	// @SEE: https://www.latlong.net/countries.html
	// @REF: https://stackoverflow.com/a/52943975
	public static function lookupLatLng( $latlng )
	{
		if ( ! $latlng )
			return '#';

		if ( ! is_array( $latlng ) )
			$latlng = Core\Geography::extractLatLng( $latlng );

		$url = add_query_arg( [
			'api'   => '1',
			'query' => sprintf( '%s,%s', $latlng[0], $latlng[1] ),
		], 'https://www.google.com/maps/search/' );

		return apply_filters( static::BASE.'_lookup_latlng', $url, $latlng );
	}

	// https://books.google.com/books?vid=isbn9789646799950
	// https://www.google.com/search?tbm=bks&q=9786005334395
	public static function lookupISBN( $isbn )
	{
		// $url = add_query_arg( [
		// 	// 'q' => 'ISBN:'.urlencode( ISBN::sanitize( $isbn ) ),
		// 	'q' => urlencode( ISBN::sanitize( $isbn ) ),
		// ], 'https://www.google.com/search' );

		$url = add_query_arg( [
			'vid' => urlencode( 'isbn'.Core\ISBN::sanitize( $isbn ) ),
		], 'https://books.google.com/books' );

		return apply_filters( static::BASE.'_lookup_isbn', $url, $isbn );
	}

	public static function renderNoticeP2P()
	{
		if ( defined( 'P2P_PLUGIN_VERSION' ) )
			return;

		/* translators: %1$s: plugin url, %2$s: plugin url */
		Core\HTML::desc( sprintf( _x( 'Please consider installing <a href="%1$s" target="_blank">Posts to Posts</a> or <a href="%2$s" target="_blank">Objects to Objects</a>.', 'Info: P2P', 'geditorial' ),
			'https://github.com/scribu/wp-posts-to-posts/', 'https://github.com/voceconnect/objects-to-objects' ) );
	}

	// OLD: `infoP2P()`
	public static function renderConnectedP2P()
	{
		return sprintf(
			/* translators: %s: code placeholder */
			_x( 'Connected via %s', 'Info: P2P', 'geditorial' ),
			'<code>P2P</code>'
		);
	}

	public static function renderSomethingIsWrong( $before = '', $after = '' )
	{
		return Core\HTML::desc( $before.Plugin::wrong( FALSE ).$after, FALSE, '-empty -wrong' );
	}

	public static function renderNoReportsAvailable( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'There are no reports available!', 'Info: Message', 'geditorial' ).$after,
			FALSE,
			'-empty -no-reports'
		);
	}

	public static function renderNoImportsAvailable( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'There are no imports available!', 'Info: Message', 'geditorial' ).$after,
			FALSE,
			'-empty -no-imports'
		);
	}

	public static function renderNoPostsAvailable( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'There are no posts available!', 'Info: Message', 'geditorial' ).$after,
			FALSE,
			'-empty -no-posts'
		);
	}

	public static function renderEmptyPosttype( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'The post-type is not provided!', 'Info: Message', 'geditorial' ).$after,
			FALSE,
			'-empty -not-empty-posttype'
		);
	}

	public static function renderEmptyTaxonomy( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'The taxonomy is not provided!', 'Info: Message', 'geditorial' ).$after,
			FALSE,
			'-empty -not-empty-taxonomy'
		);
	}

	public static function renderNotSupportedPosttype( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'The post-type is not supported!', 'Info: Message', 'geditorial' ).$after,
			FALSE,
			'-empty -not-supported-posttype'
		);
	}

	public static function renderNotSupportedTaxonomy( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'The taxonomy is not supported!', 'Info: Message', 'geditorial' ).$after,
			FALSE,
			'-empty -not-supported-taxonomy'
		);
	}

	public static function getNoop( $key, $fallback = NULL )
	{
		switch ( $key ) {

			case 'item':
			case 'items':
			case 'paired_item':
			case 'paired_items':
				/* translators: %s: items count */
				return _nx_noop( '%s Item', '%s Items', 'Info: Noop', 'geditorial' );

			case 'member':
			case 'members':
			case 'family_member':
			case 'family_members':
				/* translators: %s: items count */
				return _nx_noop( '%s Member', '%s Members', 'Info: Noop', 'geditorial' );


			case 'people':
			case 'person':
				/**
				 * Persons vs. People vs. Peoples
				 * Most of the time, `people` is the correct word to choose as a plural
				 * for `person`. `Persons` is archaic, and it is safe to avoid using it,
				 * except in legal writing, which has its own traditional language.
				 * `Peoples` is only necessary when you refer to distinct ethnic groups.
				 * @source https://www.grammarly.com/blog/persons-people-peoples/
				 */
				/* translators: %s: people count */
				return _nx_noop( '%s Person', '%s People', 'Info: Noop', 'geditorial' );

			case 'post':
			case 'posts':
				/* translators: %s: posts count */
				return _nx_noop( '%s Post', '%s Posts', 'Info: Noop', 'geditorial' );

			case 'connected':
				/* translators: %s: items count */
				return _nx_noop( '%s Item Connected', '%s Items Connected', 'Info: Noop', 'geditorial' );

			case 'word':
			case 'words':
				/* translators: %s: words count */
				return _nx_noop( '%s Word', '%s Words', 'Info: Noop', 'geditorial' );

			case 'second':
			case 'seconds':
				/* translators: %s: second count */
				return _nx_noop( '%s Second', '%s Seconds', 'Info: Noop', 'geditorial' );

			case 'hour':
			case 'hours':
				/* translators: %s: hour count */
				return _nx_noop( '%s Hour', '%s Hours', 'Info: Noop', 'geditorial' );

			case 'week':
			case 'weeks':
				/* translators: %s: week count */
				return _nx_noop( '%s Week', '%s Weeks', 'Info: Noop', 'geditorial' );

			case 'day':
			case 'days':
				/* translators: %s: day count */
				return _nx_noop( '%s Day', '%s Days', 'Info: Noop', 'geditorial' );

			case 'month':
			case 'months':
				/* translators: %s: month count */
				return _nx_noop( '%s Month', '%s Months', 'Info: Noop', 'geditorial' );

			case 'year':
			case 'years':
				/* translators: %s: year count */
				return _nx_noop( '%s Year', '%s Years', 'Info: Noop', 'geditorial' );
		}

		return $fallback;
	}

	public static function getHelpTabs( $context = NULL ) {}

	// TODO: add click to select
	public static function renderHelpTabList( $list )
	{
		if ( ! $list )
			return;

		echo Core\HTML::wrap( Core\HTML::renderList( $list ), [
			// sprintf( '%s-help-tab-content', static::BASE ),
			self::classs( 'help-tab-content' ),
			static::MODULE ? sprintf( '-%s', static::MODULE ) : '',
			'-help-tab-content',
			'-info',
		] );
	}
}
