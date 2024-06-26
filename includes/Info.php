<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Misc;
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

	// NOTE: must return link html tag
	public static function lookupLatLng( $latlng )
	{
		return Core\HTML::tag( 'a', [
			'href'   => self::lookupURLforLatLng( $latlng ),
			'class'  => '-latlng-lookup',
			'target' => '_blank',
			'rel'    => 'noreferrer',
		], Core\Geography::prepLatLng( $latlng, TRUE ) );
	}

	// @SEE: https://www.latlong.net/countries.html
	// @REF: https://stackoverflow.com/a/52943975
	public static function lookupURLforLatLng( $latlng )
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

	public static function lookupCountry( $code )
	{
		if ( function_exists( 'gnetwork_country_lookup' ) )
			return gnetwork_country_lookup( $code );

		return $code;
	}

	// NOTE: must return link html tag
	public static function lookupISBN( $isbn )
	{
		return Core\HTML::tag( 'a', [
			'href'   => self::lookupURLforISBN( $isbn ),
			'class'  => '-isbn-lookup',
			'target' => '_blank',
			'rel'    => 'noreferrer',
		], Core\ISBN::prep( $isbn, TRUE ) );
	}

	// https://books.google.com/books?vid=isbn9789646799950
	// https://www.google.com/search?tbm=bks&q=9786005334395
	// https://www.google.com/search?q=9786229627747
	public static function lookupURLforISBN( $isbn )
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

	public static function fromIBAN( $input, $pre = [] )
	{
		$info = $pre;
		$raw  = Core\Number::translate( $input );

		try {

			$iban = Misc\IBAN::createFromString( $raw );

			$info['raw']     = $iban->toFormattedString( '' );
			$info['country'] = $iban->getCountryCode();

		} catch ( \Exception $e ) {

			$info = FALSE;
		}

		return apply_filters( static::BASE.'_info_from_iban', $info, $raw, $input, $pre );
	}

	public static function fromCardNumber( $input, $pre = [] )
	{
		$info = $pre;

		if ( $raw = Core\Validation::sanitizeCardNumber( $input ) )
			$info['raw'] = $raw;
		else
			$info = FALSE;

		return apply_filters( static::BASE.'_info_from_card_number', $info, $raw, $input, $pre );
	}

	public static function renderNoticeP2P()
	{
		if ( defined( 'P2P_PLUGIN_VERSION' ) )
			return;

		/* translators: %1$s: plugin url, %2$s: plugin url */
		Core\HTML::desc( sprintf( _x( 'Please consider installing <a href="%1$s" target="_blank">Posts to Posts</a> or <a href="%2$s" target="_blank">Objects to Objects</a>.', 'Info: P2P', 'geditorial-admin' ),
			'https://github.com/scribu/wp-posts-to-posts/', 'https://github.com/voceconnect/objects-to-objects' ) );
	}

	// OLD: `infoP2P()`
	public static function renderConnectedP2P()
	{
		return sprintf(
			/* translators: %s: code placeholder */
			_x( 'Connected via %s', 'Info: P2P', 'geditorial-admin' ),
			'<code>P2P</code>'
		);
	}

	public static function renderSomethingIsWrong( $before = '', $after = '' )
	{
		return Core\HTML::desc( $before.Plugin::wrong( FALSE ).$after, FALSE, '-empty -wrong' );
	}

	public static function renderWaitForAMoment( $before = '', $after = '' )
	{
		return Core\HTML::desc( $before.Plugin::moment( FALSE ).$after, FALSE, '-empty -moment' );
	}

	public static function renderNoReportsAvailable( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'There are no reports available!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -no-reports'
		);
	}

	public static function renderNoImportsAvailable( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'There are no imports available!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -no-imports'
		);
	}

	public static function renderNoToolsAvailable( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'There are no tools available!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -no-tools'
		);
	}

	public static function renderNoRolesAvailable( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'There are no roles available!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -no-roles'
		);
	}

	public static function renderNoPostsAvailable( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'There are no posts available!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -no-posts'
		);
	}

	public static function renderNoTermsAvailable( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'There are no terms available!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -no-terms'
		);
	}

	public static function renderEmptyMIMEtype( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'The MIME-type is not provided!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -not-empty-mimetype'
		);
	}

	public static function renderEmptyPosttype( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'The post-type is not provided!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -not-empty-posttype'
		);
	}

	public static function renderEmptyTaxonomy( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'The taxonomy is not provided!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -not-empty-taxonomy'
		);
	}

	public static function renderNotSupportedPosttype( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'The post-type is not supported!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -not-supported-posttype'
		);
	}

	public static function renderNotSupportedTaxonomy( $before = '', $after = '' )
	{
		return Core\HTML::desc(
			$before._x( 'The taxonomy is not supported!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -not-supported-taxonomy'
		);
	}

	public static function renderRegistered( $datetime_string, $before = '', $after = '' )
	{
		echo $before.sprintf(
			/* translators: %s: datetime string */
			_x( 'Registered on %s', 'Info: Message', 'geditorial-admin' ),
			Helper::getDateEditRow( $datetime_string, '-registered' )
		).$after;
	}

	// NOTE: for front-end only, `$icon` must be array
	public static function getIcon( $icon, $title = FALSE, $link = FALSE )
	{
		return Core\HTML::tag( ( $link ? 'a' : 'span' ), [
			'href'   => $link ?: FALSE,
			'title'  => $title ?: FALSE,
			'class'  => [ '-icon', ( $link ? '-link' : '-info' ) ],
			'target' => $link ? '_blank' : FALSE,
		], gEditorial()->icon( $icon[1], $icon[0] ) );
	}

	public static function renderIcon( $for, $text = NULL, $link = FALSE, $verbose = TRUE )
	{
		$html = '';

		switch ( $for ) {
			case 'url'       : $html = self::getIcon( [ 'octicons', 'link' ], $text ?? _x( 'URL', 'Info: Icon Title', 'geditorial' ), $link ); break;
			case 'email'     : $html = self::getIcon( [ 'octicons', 'mail' ], $text ?? _x( 'Email', 'Info: Icon Title', 'geditorial' ), $link ); break;
			case 'roles'     : $html = self::getIcon( [ 'gridicons', 'user-circle' ], $text ?? _x( 'Roles', 'Info: Icon Title', 'geditorial' ), $link ); break;
			case 'fullname'  : $html = self::getIcon( [ 'octicons', 'note' ], $text ?? _x( 'Name', 'Info: Icon Title', 'geditorial' ), $link ); break;
			case 'registered': $html = self::getIcon( [ 'octicons', 'calendar' ], $text ?? _x( 'Registered', 'Info: Icon Title', 'geditorial' ), $link ); break;
			default          : $html = self::getIcon( Core\Icon::guess( $for, [ 'social-logos', 'mail' ] ), $text ?? _x( 'Contact', 'Info: Icon Title', 'geditorial' ), $link ); break;
		}

		if ( ! $verbose )
			return $html;

		echo $html.' '; // NOTE: extra space only on verbose

		return $html ? TRUE : FALSE;
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

			case 'term':
			case 'terms':
				/* translators: %s: terms count */
				return _nx_noop( '%s Term', '%s Terms', 'Info: Noop', 'geditorial' );

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

			case 'page':
			case 'pages':
				/* translators: %s: page count */
				return _nx_noop( '%s Page', '%s Pages', 'Info: Noop', 'geditorial' );

			case 'volume':
			case 'volumes':
				/* translators: %s: volume count */
				return _nx_noop( '%s Volume', '%s Volumes', 'Info: Noop', 'geditorial' );

			case 'disc':
			case 'discs':
				/* translators: %s: disc count */
				return _nx_noop( '%s Disc', '%s Discs', 'Info: Noop', 'geditorial' );

			case 'gram':
				/* translators: %s: unit amount */
				return _nx_noop( '%s Gram', '%s Grams', 'Info: Noop', 'geditorial' );

			case 'kg':
			case 'kilogram':
				/* translators: %s: unit amount */
				return _nx_noop( '%s Kilogram', '%s Kilograms', 'Info: Noop', 'geditorial' );

			case 'mm':
			case 'milimeter':
				/* translators: %s: unit amount */
				return _nx_noop( '%s Millimetre', '%s Millimetres', 'Info: Noop', 'geditorial' );

			case 'cm':
			case 'centimeter':
				/* translators: %s: unit amount */
				return _nx_noop( '%s Centimeter', '%s Centimeters', 'Info: Noop', 'geditorial' );

			case 'meter':
				/* translators: %s: unit amount */
				return _nx_noop( '%s Meter', '%s Meters', 'Info: Noop', 'geditorial' );
		}

		return $fallback;
	}

	public static function getHelpTabs( $context = NULL ) { return []; }

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

	public static function getPosttypePropTitle( $prop, $context = NULL )
	{
		$title = '';

		switch ( $prop ) {
			case 'ID'                   : $title = _x( 'ID', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_author'          : $title = _x( 'Author', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_date'            : $title = _x( 'Date', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_date_gmt'        : $title = _x( 'Date (GMT)', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_content'         : $title = _x( 'Content', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_title'           : $title = _x( 'Title', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_excerpt'         : $title = _x( 'Excerpt', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_status'          : $title = _x( 'Status', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'comment_status'       : $title = _x( 'Comment Status', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'ping_status'          : $title = _x( 'Ping Status', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_password'        : $title = _x( 'Password', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_name'            : $title = _x( 'Slug', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'to_ping'              : $title = _x( 'Ping', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'pinged'               : $title = _x( 'Pinged', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_modified'        : $title = _x( 'Modified', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_modified_gmt'    : $title = _x( 'Modified (GMT)', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_content_filtered': $title = _x( 'Content Filtered', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_parent'          : $title = _x( 'Parent ID', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'guid'                 : $title = _x( 'GUID', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'menu_order'           : $title = _x( 'Order', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_type'            : $title = _x( 'Type', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_mime_type'       : $title = _x( 'Mime-Type', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'comment_count'        : $title = _x( 'Comment Count', 'Info: Posttype Prop Title', 'geditorial' ); break;

		}

		return apply_filters( static::BASE.'_posttype_prop_title', $title, $prop, $context );
	}
}
