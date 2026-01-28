<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Info extends WordPress\Main
{
	const BASE = 'geditorial';

	public static function factory()
	{
		return gEditorial();
	}

	// `https://db-ip.com/xxx.xxx.xx.xxx`
	// TODO: customize for this plugin
	// MAYBE: `Services\InternetProtocol`
	public static function lookupIP( $ip )
	{
		if ( function_exists( 'gnetwork_ip_lookup' ) )
			return gnetwork_ip_lookup( $ip );

		return $ip;
	}

	// NOTE: must return HTML link tag
	public static function lookupLatLng( $latlng )
	{
		return Core\HTML::tag( 'a', [
			'href'   => self::lookupURLforLatLng( $latlng ),
			'class'  => '-latlng-lookup',
			'target' => '_blank',
			'rel'    => 'noreferrer',
		], Core\LatLng::prep( $latlng, TRUE ) );
	}

	// @SEE: https://www.latlong.net/countries.html
	// @REF: https://stackoverflow.com/a/52943975
	// `maps.google.com/?q=35.6928,50.82565`
	public static function lookupURLforLatLng( $latlng )
	{
		if ( ! $latlng )
			return '#';

		if ( ! is_array( $latlng ) )
			$latlng = Core\LatLng::extract( $latlng );

		// $url = add_query_arg( [
		// 	'api'   => '1',
		// 	'query' => sprintf( '%s,%s', $latlng[0], $latlng[1] ),
		// ], 'https://www.google.com/maps/search/' );

		$url = sprintf( 'geo:%s,%s', $latlng[0], $latlng[1] );

		return apply_filters( static::BASE.'_lookup_latlng', $url, $latlng );
	}

	public static function lookupCountry( $code )
	{
		if ( function_exists( 'gnetwork_country_lookup' ) )
			return gnetwork_country_lookup( $code );

		return $code;
	}

	// NOTE: must return HTML link tag
	public static function lookupISBN( $isbn )
	{
		return Core\HTML::tag( 'a', [
			'href'   => self::lookupURLforISBN( $isbn ),
			'class'  => '-isbn-lookup',
			'target' => '_blank',
			'rel'    => 'noreferrer',
		], Core\ISBN::prep( $isbn, TRUE ) );
	}

	// https://ketab.ir/search/9786005334548
	// https://openlibrary.org/search?isbn=9786227260267
	// https://books.google.com/books?vid=isbn9789646799950
	// https://www.google.com/search?tbm=bks&q=9786005334395
	// https://www.google.com/search?q=9786229627747
	// https://fa.wikipedia.org/wiki/%D9%88%DB%8C%DA%98%D9%87:%D9%85%D9%86%D8%A7%D8%A8%D8%B9_%DA%A9%D8%AA%D8%A7%D8%A8?isbn=0-13-981176-1
	// https://en.wikipedia.org/wiki/Special:BookSources?isbn=9786227260267
	// https://en.wikipedia.org/wiki/Special:BookSources/978-0-618-05676-7
	// https://en.wikipedia.org/wiki/Special:BookSources/978-1-55783-528-4
	// https://en.wikipedia.org/wiki/Special:BookSources?isbn=122334
	// https://www.goodreads.com/search?q=MAGICNUMBER
	// https://www.goodreads.com/search?utf8=%E2%9C%93&q=0-13-981176-1&search_type=books
	// NOTE: must return HTML link tag
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

	// NOTE: must return HTML link tag
	public static function lookupVIN( $vin )
	{
		return Core\HTML::tag( 'a', [
			'href'   => self::lookupURLforVIN( $vin ),
			'class'  => '-vin-lookup',
			'target' => '_blank',
			'rel'    => 'noreferrer',
		], Core\Validation::sanitizeVIN( $vin ) );
	}

	// `https://en.vindecoder.pl/en/decode/JH2RC3605MM101581`
	// @SEE: https://vpic.nhtsa.dot.gov/decoder/
	public static function lookupURLforVIN( $vin )
	{
		$url = sprintf(
			'https://en.vindecoder.pl/en/decode/%s',
			Core\Validation::sanitizeVIN( $vin )
		);

		return apply_filters( static::BASE.'_lookup_vin', $url, $vin );
	}

	public static function fromIBAN( $input, $pre = [] )
	{
		$info = $pre;
		$raw  = Core\Number::translate( $input );

		try {

			$iban = Misc\IBAN::createFromString( $raw );

			$info['raw']       = $iban->toFormattedString( '' );
			$info['country']   = $iban->getCountryCode();
			$info['formatted'] = $iban->toFormattedString();

		} catch ( \Exception $e ) {

			$info = FALSE;
		}

		return apply_filters( static::BASE.'_info_from_iban', $info, $raw, $input, $pre );
	}

	public static function fromPostCode( $input, $pre = [] )
	{
		$info = $pre;

		if ( $raw = Core\PostCode::sanitize( $input ) ) {

			/**
			 * @package `brick/postcode`
			 * @link https://github.com/brick/postcode
			 */
			$formatter = new \Brick\Postcode\PostcodeFormatter;
			$country   = self::const( 'GCORE_DEFAULT_COUNTRY_CODE', 'IR' );

			if ( ! empty( $info['country'] ) )
				$country = $info['country'];

			try {

				$info['formatted'] = $formatter->format( $country, $raw );
				$info['raw']       = $raw;
				$info['country']   = $country;

			} catch ( \Brick\Postcode\UnknownCountryException $e ) {

				// Exception thrown when an unknown country code is provided

				$info['raw']     = $raw;
				$info['country'] = $country;

			} catch ( \Brick\Postcode\InvalidPostcodeException $e ) {

				// Exception thrown when trying to format an invalid postcode

				$info = FALSE;
			}

		} else {

			$info = FALSE;
		}

		return apply_filters( static::BASE.'_info_from_postcode', $info, $raw, $input, $pre );
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

		Core\HTML::desc( sprintf(
			/* translators: `%1$s`: plugin URL, `%2$s`: plugin URL */
			_x( 'Please consider installing <a href="%1$s" target="_blank">Posts to Posts</a> or <a href="%2$s" target="_blank">Objects to Objects</a>.', 'Info: P2P', 'geditorial-admin' ),
			'https://github.com/scribu/wp-posts-to-posts/',
			'https://github.com/voceconnect/objects-to-objects' )
		);
	}

	// OLD: `infoP2P()`
	public static function renderConnectedP2P()
	{
		return sprintf(
			/* translators: `%s`: code placeholder */
			_x( 'Connected via %s', 'Info: P2P', 'geditorial-admin' ),
			Core\HTML::code( 'P2P' )
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
		Core\HTML::desc(
			$before._x( 'There are no reports available!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -no-reports'
		);

		return FALSE;
	}

	public static function renderNoImportsAvailable( $before = '', $after = '' )
	{
		Core\HTML::desc(
			$before._x( 'There are no imports available!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -no-imports'
		);

		return FALSE;
	}

	public static function renderNoExportsAvailable( $before = '', $after = '' )
	{
		Core\HTML::desc(
			$before._x( 'There are no exports available!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -no-exports'
		);

		return FALSE;
	}

	public static function renderNoCustomsAvailable( $before = '', $after = '' )
	{
		Core\HTML::desc(
			$before._x( 'There are no customs available!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -no-customs'
		);

		return FALSE;
	}

	public static function renderNoToolsAvailable( $before = '', $after = '' )
	{
		Core\HTML::desc(
			$before._x( 'There are no tools available!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -no-tools'
		);

		return FALSE;
	}

	public static function renderNoRolesAvailable( $before = '', $after = '' )
	{
		Core\HTML::desc(
			$before._x( 'There are no roles available!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -no-roles'
		);

		return FALSE;
	}

	public static function renderNoPostsAvailable( $before = '', $after = '' )
	{
		Core\HTML::desc(
			$before._x( 'There are no posts available!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -no-posts'
		);

		return FALSE;
	}

	public static function renderNoTermsAvailable( $before = '', $after = '' )
	{
		Core\HTML::desc(
			$before._x( 'There are no terms available!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -no-terms'
		);

		return FALSE;
	}

	public static function renderNoDataAvailable( $before = '', $after = '' )
	{
		Core\HTML::desc(
			$before._x( 'There are no data available!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -no-data'
		);

		return FALSE;
	}

	public static function renderEmptyMIMEtype( $before = '', $after = '' )
	{
		Core\HTML::desc(
			$before._x( 'The MIME-type is not provided!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -not-empty-mimetype'
		);

		return FALSE;
	}

	public static function renderEmptyPosttype( $before = '', $after = '' )
	{
		Core\HTML::desc(
			$before._x( 'The post-type is not provided!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -not-empty-posttype'
		);

		return FALSE;
	}

	public static function renderEmptyTaxonomy( $before = '', $after = '' )
	{
		Core\HTML::desc(
			$before._x( 'The taxonomy is not provided!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -not-empty-taxonomy'
		);

		return FALSE;
	}

	public static function renderNotSupportedPosttype( $before = '', $after = '' )
	{
		Core\HTML::desc(
			$before._x( 'The post-type is not supported!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -not-supported-posttype'
		);

		return FALSE;
	}

	public static function renderNotSupportedTaxonomy( $before = '', $after = '' )
	{
		Core\HTML::desc(
			$before._x( 'The taxonomy is not supported!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -not-supported-taxonomy'
		);

		return FALSE;
	}

	public static function renderNotSupportedField( $before = '', $after = '' )
	{
		Core\HTML::desc(
			$before._x( 'The field is not supported!', 'Info: Message', 'geditorial-admin' ).$after,
			FALSE,
			'-empty -not-supported-field'
		);

		return FALSE;
	}

	public static function renderRegistered( $datetime_string, $before = '', $after = '' )
	{
		echo $before.sprintf(
			/* translators: `%s`: date-time string */
			_x( 'Registered on %s', 'Info: Message', 'geditorial' ),
			Helper::getDateEditRow( $datetime_string, '-registered' )
		).$after;
	}

	// NOTE: front-end only. `$icon` must be an array
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

	/**
	 * Retrieves unit full/abbreviation titles given the identifier key.
	 *
	 * @param string $key
	 * @param string $fallback
	 * @return array $info
	 */
	public static function getUnit( $key, $fallback = NULL )
	{
		switch ( $key ) {

			case 'day'          : return [ _x( 'Days', 'Info: Unit', 'geditorial' ), _x( 'days', 'Info: Unit', 'geditorial' ) ];
			case 'hour'         : return [ _x( 'Hours', 'Info: Unit', 'geditorial' ), _x( 'hours', 'Info: Unit', 'geditorial' ) ];
			case 'week'         : return [ _x( 'Weeks', 'Info: Unit', 'geditorial' ), _x( 'weeks', 'Info: Unit', 'geditorial' ) ];
			case 'year'         : return [ _x( 'Years', 'Info: Unit', 'geditorial' ), _x( 'years', 'Info: Unit', 'geditorial' ) ];
			case 'gram'         : return [ _x( 'Grams', 'Info: Unit', 'geditorial' ), _x( 'g', 'Info: Unit', 'geditorial' ) ];
			case 'kilogram'     : return [ _x( 'Kilograms', 'Info: Unit', 'geditorial' ), _x( 'kg', 'Info: Unit', 'geditorial' ) ];
			case 'millimetre'   : return [ _x( 'Millimetres', 'Info: Unit', 'geditorial' ), _x( 'mm', 'Info: Unit', 'geditorial' ) ];
			case 'centimetre'   : return [ _x( 'Centimetres', 'Info: Unit', 'geditorial' ), _x( 'cm', 'Info: Unit', 'geditorial' ) ];
			case 'meter'        :
			case 'metre'        : return [ _x( 'Metres', 'Info: Unit', 'geditorial' ), _x( 'm', 'Info: Unit', 'geditorial' ) ];
			case 'kilometre'    : return [ _x( 'Kilometres', 'Info: Unit', 'geditorial' ), _x( 'km', 'Info: Unit', 'geditorial' ) ];
			case 'km_per_hour'  : return [ _x( 'Kilometres per Hour', 'Info: Unit', 'geditorial' ), _x( 'kmph', 'Info: Unit', 'geditorial' ) ];
			case 'european'     : return [ _x( 'European', 'Info: Unit', 'geditorial' ), _x( 'eu', 'Info: Unit', 'geditorial' ) ];
			case 'international': return [ _x( 'International', 'Info: Unit', 'geditorial' ), _x( 'int', 'Info: Unit', 'geditorial' ) ];
			case 'person'       : return [ _x( 'Persons', 'Info: Unit', 'geditorial' ), _x( 'persons', 'Info: Unit', 'geditorial' ) ];
			case 'card'         : return [ _x( 'Cards', 'Info: Unit', 'geditorial' ), _x( 'cards', 'Info: Unit', 'geditorial' ) ];
			case 'line'         : return [ _x( 'Lines', 'Info: Unit', 'geditorial' ), _x( 'lines', 'Info: Unit', 'geditorial' ) ];
			case 'shot'         : return [ _x( 'Shots', 'Info: Unit', 'geditorial' ), _x( 'shots', 'Info: Unit', 'geditorial' ) ];
		}

		return [ $fallback, $fallback, $fallback ];
	}

	public static function getNoop( $key, $fallback = NULL )
	{
		switch ( $key ) {

			case 'item':
			case 'items':
			case 'paired_item':
			case 'paired_items':
				/* translators: `%s`: items count */
				return _nx_noop( '%s Item', '%s Items', 'Info: Noop', 'geditorial' );

			case 'participant':
			case 'participants':
				/* translators: `%s`: participants count */
				return _nx_noop( '%s Participant', '%s Participants', 'Info: Noop', 'geditorial' );

			case 'record':
			case 'records':
				/* translators: `%s`: records count */
				return _nx_noop( '%s Record', '%s Records', 'Info: Noop', 'geditorial' );

			case 'row':
			case 'rows':
				/* translators: `%s`: rows count */
				return _nx_noop( '%s Row', '%s Rows', 'Info: Noop', 'geditorial' );

			case 'account':
			case 'accounts':
				/* translators: `%s`: accounts count */
				return _nx_noop( '%s Account', '%s Accounts', 'Info: Noop', 'geditorial' );

			case 'member':
			case 'members':
			case 'family_member':
			case 'family_members':
				/* translators: `%s`: items count */
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
				/* translators: `%s`: people count */
				return _nx_noop( '%s Person', '%s People', 'Info: Noop', 'geditorial' );

			case 'car':
			case 'cars':
				/* translators: `%s`: car count */
				return _nx_noop( '%s Car', '%s Cars', 'Info: Noop', 'geditorial' );

			case 'vehicle':
			case 'vehicles':
				/* translators: `%s`: vehicle count */
				return _nx_noop( '%s Vehicle', '%s Vehicles', 'Info: Noop', 'geditorial' );

			case 'position':
			case 'positions':
				/* translators: `%s`: position count */
				return _nx_noop( '%s Position', '%s Positions', 'Info: Noop', 'geditorial' );

			case 'entry':
			case 'entries':
				/* translators: `%s`: entry count */
				return _nx_noop( '%s Entry', '%s Entries', 'Info: Noop', 'geditorial' );

			case 'event':
			case 'events':
				/* translators: `%s`: event count */
				return _nx_noop( '%s Event', '%s Events', 'Info: Noop', 'geditorial' );

			case 'post':
			case 'posts':
				/* translators: `%s`: posts count */
				return _nx_noop( '%s Post', '%s Posts', 'Info: Noop', 'geditorial' );

			case 'term':
			case 'terms':
				/* translators: `%s`: terms count */
				return _nx_noop( '%s Term', '%s Terms', 'Info: Noop', 'geditorial' );

			case 'color':
			case 'colors':
				/* translators: `%s`: colors count */
				return _nx_noop( '%s Color', '%s Colors', 'Info: Noop', 'geditorial' );

			case 'size':
			case 'sizes':
				/* translators: `%s`: sizes count */
				return _nx_noop( '%s Size', '%s Sizes', 'Info: Noop', 'geditorial' );

			case 'connected':
				/* translators: `%s`: items count */
				return _nx_noop( '%s Item Connected', '%s Items Connected', 'Info: Noop', 'geditorial' );

			case 'word':
			case 'words':
				/* translators: `%s`: words count */
				return _nx_noop( '%s Word', '%s Words', 'Info: Noop', 'geditorial' );

			case 'second':
			case 'seconds':
				/* translators: `%s`: second count */
				return _nx_noop( '%s Second', '%s Seconds', 'Info: Noop', 'geditorial' );

			case 'hour':
			case 'hours':
				/* translators: `%s`: hour count */
				return _nx_noop( '%s Hour', '%s Hours', 'Info: Noop', 'geditorial' );

			case 'week':
			case 'weeks':
				/* translators: `%s`: week count */
				return _nx_noop( '%s Week', '%s Weeks', 'Info: Noop', 'geditorial' );

			case 'day':
			case 'days':
				/* translators: `%s`: day count */
				return _nx_noop( '%s Day', '%s Days', 'Info: Noop', 'geditorial' );

			case 'month':
			case 'months':
				/* translators: `%s`: month count */
				return _nx_noop( '%s Month', '%s Months', 'Info: Noop', 'geditorial' );

			case 'year':
			case 'years':
				/* translators: `%s`: year count */
				return _nx_noop( '%s Year', '%s Years', 'Info: Noop', 'geditorial' );

			case 'page':
			case 'pages':
				/* translators: `%s`: page count */
				return _nx_noop( '%s Page', '%s Pages', 'Info: Noop', 'geditorial' );

			case 'volume':
			case 'volumes':
				/* translators: `%s`: volume count */
				return _nx_noop( '%s Volume', '%s Volumes', 'Info: Noop', 'geditorial' );

			case 'disc':
			case 'discs':
				/* translators: `%s`: disc count */
				return _nx_noop( '%s Disc', '%s Discs', 'Info: Noop', 'geditorial' );

			case 'gram':
				/* translators: `%s`: unit amount */
				return _nx_noop( '%s Gram', '%s Grams', 'Info: Noop', 'geditorial' );

			case 'kg':
			case 'kilogram':
				/* translators: `%s`: unit amount */
				return _nx_noop( '%s Kilogram', '%s Kilograms', 'Info: Noop', 'geditorial' );

			case 'mm':
			case 'millimetre':
			case 'millimeter':
				/* translators: `%s`: unit amount */
				return _nx_noop( '%s Millimetre', '%s Millimetres', 'Info: Noop', 'geditorial' );

			case 'cm':
			case 'centimetre':
			case 'centimeter':
				/* translators: `%s`: unit amount */
				return _nx_noop( '%s Centimetre', '%s Centimetres', 'Info: Noop', 'geditorial' );

			case 'metre':
			case 'meter':
				/**
				 * `Metre` is the standard spelling of the metric unit for length
				 * in nearly all English-speaking nations, the exceptions being
				 * the United States and the Philippines which use `meter`.
				 * Measuring devices (such as ammeter, speedometer) are spelled
				 * "-meter" in all variants of English.
				 *
				 * @ref https://en.wikipedia.org/wiki/Metre
				 * @see https://www.grammarly.com/commonly-confused-words/meter-vs-metre
				 */
				/* translators: `%s`: unit amount */
				return _nx_noop( '%s Metre', '%s Metres', 'Info: Noop', 'geditorial' );

			case 'km':
			case 'kilometre':
			case 'kilometer':
				/* translators: `%s`: unit amount */
				return _nx_noop( '%s Kilometre', '%s Kilometres', 'Info: Noop', 'geditorial' );

			case 'kmh':
			case 'kph':
			case 'km_per_hour':
				/* translators: `%s`: unit amount */
				return _nx_noop( '%s Kilometer per Hour', '%s Kilometres per Hour', 'Info: Noop', 'geditorial' );

			case 'card':
			case 'cards':
				/* translators: `%s`: unit amount */
				return _nx_noop( '%s Card', '%s Cards', 'Info: Noop', 'geditorial' );

			case 'line':
			case 'lines':
				/* translators: `%s`: unit amount */
				return _nx_noop( '%s Line', '%s Lines', 'Info: Noop', 'geditorial' );

			case 'shot':
			case 'shots':
				/* translators: `%s`: unit amount */
				return _nx_noop( '%s Shot', '%s Shots', 'Info: Noop', 'geditorial' );
		}

		return $fallback;
	}

	public static function getHelpTabs( $context = NULL ) { return []; }

	// TODO: add click to select
	public static function renderHelpTabList( $list )
	{
		echo Core\HTML::wrap( Core\HTML::rows( $list ), [
			static::classs( 'help-tab-content' ),
			static::MODULE ? sprintf( '-%s', static::MODULE ) : '',
			'-help-tab-content',
			'-info',
		] );
	}

	public static function getPosttypePropTitle( $prop, $posttype = NULL, $context = NULL )
	{
		$title = '';

		switch ( $prop ) {
			case 'ID'                   : $title = _x( 'ID', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_author'          : $title = Services\CustomPostType::getLabel( $posttype, 'author_label', FALSE, _x( 'Author', 'Info: Posttype Prop Title', 'geditorial' ) ); break;
			case 'post_date'            : $title = _x( 'Date', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_date_gmt'        : $title = _x( 'Date (GMT)', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_content'         : $title = _x( 'Content', 'Info: Posttype Prop Title', 'geditorial' ); break;
			case 'post_title'           : $title = Services\CustomPostType::getLabel( $posttype, 'column_title', FALSE, _x( 'Title', 'Info: Posttype Prop Title', 'geditorial' ) ); break;
			case 'post_excerpt'         : $title = Services\CustomPostType::getLabel( $posttype, 'excerpt_label', FALSE, _x( 'Excerpt', 'Info: Posttype Prop Title', 'geditorial' ) ); break;
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

	public static function getDropzoneStrings()
	{
		return apply_filters( static::BASE.'_strings_dropzone', [
				/* translators: The text used before any files are dropped. */
				'dictDefaultMessage' => _x( 'Drop files here to upload', 'Info: Dropzone: `dictDefaultMessage`', 'geditorial' ),

				/* translators: The text that replaces the default message text it the browser is not supported. */
				'dictFallbackMessage' => _x( 'Your browser does not support drag\'n\'drop file uploads.', 'Info: Dropzone: `dictFallbackMessage`', 'geditorial' ),

				/* translators: The text that will be added before the fallback form. If you provide a  fallback element yourself, or if this option is `null` this will be ignored. */
				'dictFallbackText' => _x( 'Please use the fallback form below to upload your files like in the olden days.', 'Info: Dropzone: `dictFallbackText`', 'geditorial' ),

				/* translators: If the file-size is too big. `{{filesize}}` and `{{maxFilesize}}` will be replaced with the respective configuration values. */
				'dictFileTooBig' => _x( 'File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.', 'Info: Dropzone: `dictFileTooBig`', 'geditorial' ),

				/* translators: If the file doesn't match the file-type. */
				'dictInvalidFileType' => _x( 'You can\'t upload files of this type.', 'Info: Dropzone: `dictInvalidFileType`', 'geditorial' ),

				/* translators: If the server response was invalid. `{{statusCode}}` will be replaced with the servers status code. */
				'dictResponseError' => _x( 'Server responded with {{statusCode}} code.', 'Info: Dropzone: `dictResponseError`', 'geditorial' ),

				/* translators: If `addRemoveLinks` is true, the text to be used for the cancel upload link. */
				'dictCancelUpload' => _x( 'Cancel upload', 'Info: Dropzone: `dictCancelUpload`', 'geditorial' ),

				/* translators: The text that is displayed if an upload was manually canceled. */
				'dictUploadCanceled' => _x( 'Upload canceled.', 'Info: Dropzone: `dictUploadCanceled`', 'geditorial' ),

				/* translators: If `addRemoveLinks` is true, the text to be used for confirmation when cancelling upload. */
				'dictCancelUploadConfirmation' => _x( 'Are you sure you want to cancel this upload?', 'Info: Dropzone: `dictCancelUploadConfirmation`', 'geditorial' ),

				/* translators: If `addRemoveLinks` is true, the text to be used to remove a file. */
				'dictRemoveFile' => _x( 'Remove file', 'Info: Dropzone: `dictRemoveFile`', 'geditorial' ),

				/* translators: Displayed if `maxFiles` is st and exceeded. The string `{{maxFiles}}` will be replaced by the configuration value. */
				'dictMaxFilesExceeded' => _x( 'You can not upload any more files.', 'Info: Dropzone: `dictMaxFilesExceeded`', 'geditorial' ),

				/* translators: Allows you to translate the different units. Starting with `tb` for terabytes and going down to `b` for bytes. */
				// 'dictFileSizeUnits' => '', // { tb: "TB", gb: "GB", mb: "MB", kb: "KB", b: "b" }

				/* translators: If this is not null, then the user will be prompted before removing a file. */
				// 'dictRemoveFileConfirmation => '', // NULL
		] );
	}

	/**
	 * Provides the distribution of the population according to age.
	 * @source: https://en.wikipedia.org/wiki/Demographic_profile
	 *
	 * @param bool $extended
	 * @return array $data
	 */
	public static function getAgeStructure( $extended = FALSE )
	{
		$data = [
			'00to14' => [
				'slug' => '00to14',
				'name' => _x( '0–14 years', 'Datetime: Age Structure', 'geditorial' ),
				'meta' => [
					'max' => 14,
				],
			],
			'15to24' => [
				'slug' => '15to24',
				'name' => _x( '15–24 years', 'Datetime: Age Structure', 'geditorial' ),
				'meta' => [
					'min' => 15,
					'max' => 24,
				],
			],
			'25to54' => [
				'slug' => '25to54',
				'name' => _x( '25–54 years', 'Datetime: Age Structure', 'geditorial' ),
				'meta' => [
					'min' => 25,
					'max' => 54,
				],
			],
			'55to64' => [
				'slug' => '55to64',
				'name' => _x( '55–64 years', 'Datetime: Age Structure', 'geditorial' ),
				'meta' => [
					'min' => 55,
					'max' => 64,
				],
			],
			'65over' => [
				'slug' => '65over',
				'name' => _x( '65 years and over', 'Datetime: Age Structure', 'geditorial' ),
				'meta' => [
					'min' => 65,
				],
			],
		];

		return $extended ? $data : Core\Arraay::pluck( $data, 'name', 'slug' );
	}

	/**
	 * The American Medical Association's age designations.
	 * @source https://www.nih.gov/nih-style-guide/age
	 * NOTE: `min`/`max` meta values are based on months
	 *
	 * - Neonates or newborns (birth to 1 month)
	 * - Infants (1 month to 1 year)
	 * - Children (1 year through 12 years)
	 * - Adolescents (13 years through 17 years. They may also be referred to as teenagers depending on the context.)
	 * - Adults (18 years or older)
	 * - Older adults (65 and older)
	 *
	 * @param bool $extended
	 * @return array $data
	 */
	public static function getMedicalAge( $extended = FALSE )
	{
		$data = [
			'newborns'     => [ 'slug' => 'newborns',     'name' => _x( 'Newborns', 'Datetime: Medical Age', 'geditorial' ),     'meta' => [               'max' => 1   ] ],
			'infants'      => [ 'slug' => 'infants',      'name' => _x( 'Infants', 'Datetime: Medical Age', 'geditorial' ),      'meta' => [ 'min' => 1,   'max' => 12  ] ],
			'children'     => [ 'slug' => 'children',     'name' => _x( 'Children', 'Datetime: Medical Age', 'geditorial' ),     'meta' => [ 'min' => 13,  'max' => 144 ] ],
			'adolescents'  => [ 'slug' => 'adolescents',  'name' => _x( 'Adolescents', 'Datetime: Medical Age', 'geditorial' ),  'meta' => [ 'min' => 145, 'max' => 204 ] ],
			'adults'       => [ 'slug' => 'adults',       'name' => _x( 'Adults', 'Datetime: Medical Age', 'geditorial' ),       'meta' => [ 'min' => 205, 'max' => 781 ] ],
			'older-adults' => [ 'slug' => 'older-adults', 'name' => _x( 'Older Adults', 'Datetime: Medical Age', 'geditorial' ), 'meta' => [ 'min' => 781,              ] ],
		];

		return $extended ? $data : Core\Arraay::pluck( $data, 'name', 'slug' );
	}
}
