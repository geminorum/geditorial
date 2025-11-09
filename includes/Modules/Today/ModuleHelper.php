<?php namespace geminorum\gEditorial\Modules\Today;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'today';

	public static function getTheDayAllCalendars( $calendars, $year = FALSE, $datetime_string = NULL )
	{
		$list = [];

		foreach ( $calendars as $calendar ) {

			$the_day = gEditorial\Datetime::getTheDay( $datetime_string, $calendar );

			if ( ! $year )
				unset( $the_day['year'] );

			$list[$calendar] = $the_day;
		}

		return $list;
	}

	public static function getTheDayDateMySQL( $the_day, $type = NULL )
	{
		$cal   = empty( $the_day['cal'] ) ? $type : $the_day['cal'];
		$today = gEditorial\Datetime::getTheDay( NULL, $cal );

		$array = self::atts( [
			'year'     => $today['year'],
			'month'    => $today['month'],
			'day'      => $today['day'],
			'hour'     => 23,
			'minute'   => 59,
			'second'   => 59,
			'calendar' => $cal,
			'timezone' => NULL,
		], $the_day );

		return gEditorial\Datetime::makeMySQLFromArray( $array, NULL, FALSE );
	}

	// TODO: check minimum/max for day/month
	// TODO: 4 digit year based on `$type`
	public static function parseTheFullDay( $text, $type = NULL )
	{
		if ( WordPress\Strings::isEmpty( $text ) )
			return [];

		$text = Core\Number::translate( trim( $text ) );
		$text = trim( str_ireplace( [
			' ',
			'.',
			'-',
			'\\',
		], '/', $text ) );

		// NOTE: we can not determine 2 digits!
		if ( 4 === strlen( $text ) )
			return [
				'cal'   => $type,
				'year'  => $text,
				'month' => '',
				'day'   => '',
			];

		$parts = explode( '/', $text );

		if ( 4 === strlen( $parts[2] ) )
			$parts = array_reverse( $parts );

		$temp = [
			'cal'   => $type,
			'year'  => $parts[0],
			'month' => Core\Number::zeroise( $parts[1], 2 ),
			'day'   => Core\Number::zeroise( $parts[2], 2 ),
		];

		return $temp;
	}

	// NOTE: DEPRECATED
	public static function getTheDayFromToday( $today = NULL, $type = NULL )
	{
		self::_dev_dep( 'Datetime::getTheDay()' );
		return gEditorial\Datetime::getTheDay( $today, $type );
	}

	// NOT USED
	public static function titleToday( $calendars, $separator = ' &ndash; ', $year = FALSE )
	{
		$titles = [];
		$today  = self::getTheDayAllCalendars( $calendars, $year );

		foreach ( $today as $the_day )
			$titles[] = trim( ModuleHelper::titleTheDay( $the_day, '[]', FALSE ), '[]' );

		return implode( $separator, $titles );
	}

	public static function titleTheDay( $stored, $empty = '&mdash;', $display_cal = TRUE )
	{
		global $gEditorialTodayCalendars, $gEditorialTodayMonths;

		$the_day = array_merge( [
			'cal'   => '',
			'day'   => '',
			'month' => '',
			'year'  => '',
		], $stored );

		if ( ! $the_day['day'] && ! $the_day['month'] && ! $the_day['year'] )
			return $empty;

		if ( empty( $gEditorialTodayCalendars ) )
			$gEditorialTodayCalendars = Services\Calendars::getDefualts();

		if ( ! isset( $gEditorialTodayMonths[$the_day['cal']] ) )
			$gEditorialTodayMonths[$the_day['cal']] = gEditorial\Datetime::getMonths( $the_day['cal'] );

		$parts = [];

		if ( $the_day['day'] )
			$parts['day'] = Core\Number::localize( $the_day['day'] );

		if ( $the_day['month'] ) {

			$month = Core\Number::translate( $the_day['month'] );
			$key   = Core\Number::zeroise( $month, 2 );

			if ( isset( $gEditorialTodayMonths[$the_day['cal']][$key] ) )
				$the_day['month'] = $gEditorialTodayMonths[$the_day['cal']][$key];

			$parts['month'] = $the_day['month'];
		}

		if ( $the_day['year'] )
			$parts['year'] = Core\Number::localize( $the_day['year'] );

		if ( empty( $parts ) )
			return $empty;

		// maybe should display calendar!
		if ( is_null( $display_cal ) )
			$display_cal = 2 === count( array_filter( $the_day ) ) && ! empty( $the_day['year'] );

		if ( $the_day['cal'] && $display_cal )
			$parts['cal'] = empty( $gEditorialTodayCalendars[$the_day['cal']] )
				? $the_day['cal']
				: $gEditorialTodayCalendars[$the_day['cal']];

		return WordPress\Strings::getJoined( $parts, '[', ']', '', gEditorial\Datetime::dateSeparator() );
	}

	public static function displayTheDay( $stored, $empty = '&mdash;' )
	{
		global $gEditorialTodayCalendars, $gEditorialTodayMonths;

		$the_day = array_merge( [
			'cal'   => '',
			'day'   => '',
			'month' => '',
			'year'  => '',
		], $stored );

		if ( ! $the_day['day'] && ! $the_day['month'] && ! $the_day['year'] ) {

			if ( $empty )
				echo '<div class="-today -date-badge-empty">'.$empty.'</div>';

		} else {

			if ( empty( $gEditorialTodayCalendars ) )
				$gEditorialTodayCalendars = Services\Calendars::getDefualts();

			if ( ! isset( $gEditorialTodayMonths[$the_day['cal']] ) )
				$gEditorialTodayMonths[$the_day['cal']] = gEditorial\Datetime::getMonths( $the_day['cal'] );

			echo '<div class="-today -date-badge">';

				if ( $the_day['day'] )
					echo '<span class="-day" data-day="'.Core\HTML::escape( $the_day['day'] )
						.'"><a target="_blank" href="'.self::getTheDayLink( $stored, 'day' )
						.'">'.Core\Number::localize( $the_day['day'] ).'</a></span>';

				if ( $the_day['month'] ) {

					$month = Core\Number::translate( $the_day['month'] );
					$key   = Core\Number::zeroise( $month, 2 );

					if ( isset( $gEditorialTodayMonths[$the_day['cal']][$key] ) )
						$the_day['month'] = $gEditorialTodayMonths[$the_day['cal']][$key];

					echo '<span class="-month" data-month="'.Core\HTML::escape( $month )
						.'"><a target="_blank" href="'.self::getTheDayLink( $stored, 'monthly' )
						.'">'.$the_day['month'].'</a></span>';
				}

				if ( $the_day['year'] )
					echo '<span class="-year" data-year="'.Core\HTML::escape( $the_day['year'] )
						.'"><a target="_blank" href="'.self::getTheDayLink( $stored, 'yearly' )
						.'">'.Core\Number::localize( $the_day['year'] ).'</a></span>';

				if ( $the_day['cal'] )
					echo '<span class="-cal" data-cal="'.Core\HTML::escape( $the_day['cal'] )
						.'"><a target="_blank" href="'.self::getTheDayLink( $stored, 'cal' )
						.'">'.( empty( $gEditorialTodayCalendars[$the_day['cal']] )
							? $the_day['cal']
							: $gEditorialTodayCalendars[$the_day['cal']] ).'</a></span>';

			echo '</div>';
		}
	}

	public static function getTheDayLink( $data, $target = NULL, $admin = NULL )
	{
		$admin = $admin ?? is_admin();
		$path  = '';

		// sorting the variables!
		$the_day = self::atts( [
			'base'  => $admin ? '' : gEditorial()->module( static::MODULE )->get_link_base(),
			'cal'   => '',
			'month' => '',
			'day'   => '',
			'year'  => '',
		], $data );

		switch ( $target ?? 'full' ) {

			case 'cal'  : unset( $the_day['year'], $the_day['month'], $the_day['day'] ); break;
			case 'year' : unset( $the_day['month'], $the_day['day'] ); break;
			case 'month': unset( $the_day['day'] ); break;

			case 'annual'  : unset( $the_day['year'] ); break;
			case 'themonth': unset( $the_day['year'], $the_day['day'] ); break;

			case 'yearly':

				if ( $admin )
					unset( $the_day['month'], $the_day['day'] );

				else
					$path = sprintf( '%s/%s/year/%s', $the_day['base'], $the_day['cal'], $the_day['year'] ); break;

			case 'monthly':

				if ( $admin || empty( $the_day['year'] ) )
					unset( $the_day['day'] );
				else
					$path = sprintf( '%s/%s/year/%s/%s', $the_day['base'], $the_day['cal'], $the_day['year'], $the_day['month'] ); break;
		}

		if ( $admin )
			return gEditorial()->module( static::MODULE )->get_the_day_admin_link( $the_day );

		return home_url( $path ?: implode( '/', $the_day ) );
	}

	public static function getTheDayFromPost( $post, $default_type = NULL, $constants = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$the_day      = [];
		$default_type = $default_type ?? Core\L10n::calendar();

		if ( is_null( $constants ) )
			$constants = self::getTheDayConstants();

		$post_meta = get_post_meta( $post->ID );

		foreach ( $constants as $field => $constant )
			if ( ! empty( $post_meta[$constant][0] ) )
				$the_day[$field] = $post_meta[$constant][0];

		if ( empty( $the_day['cal'] ) )
			return array_merge( [ 'cal' => $default_type ], $the_day );

		return $the_day;
	}

	public static function getTheDayFromQuery( $admin = NULL, $default_type = NULL, $constants = NULL )
	{
		$the_day      = [];
		$default_type = $default_type ?? Core\L10n::calendar();

		if ( is_null( $admin ) )
			$admin = is_admin();

		if ( is_null( $constants ) )
			$constants = self::getTheDayConstants();

		foreach ( $constants as $field => $constant ) {

			if ( ! $admin && ( $var = get_query_var( 'day_'.$field, FALSE ) ) )
				$the_day[$field] = $var;

			if ( $admin && ( $var = self::req( $field, FALSE ) ) )
				$the_day[$field] = $var;
		}

		if ( $default_type && empty( $the_day['cal'] ) )
			return array_merge( [ 'cal' => $default_type ], $the_day );

		return $the_day;
	}

	public static function getTheDayConstants( $year = TRUE )
	{
		$list = [
			'cal'   => self::constant( 'metakey_cal', '_theday_cal' ),
			'day'   => self::constant( 'metakey_day', '_theday_day' ),
			'month' => self::constant( 'metakey_month', '_theday_month' ),
		];

		if ( $year )
			$list['year'] = self::constant( 'metakey_year', '_theday_year' );

		return $list;
	}

	public static function getDayPost( $stored, $constants = NULL )
	{
		$posttype = self::constant( 'main_posttype', 'day' );
		$the_day  = self::atts( [
			'cal'   => '',
			'day'   => '',
			'month' => '',
			// 'year'  => '', // there is no year in `day` post-type
		], $stored );

		if ( is_null( $constants ) )
			$constants = self::getTheDayConstants();

		$args = [
			'post_type'        => $posttype,
			'post_status'      => is_admin() ? WordPress\Status::acceptable( $posttype ) : 'publish',
			'suppress_filters' => TRUE,
			'no_found_rows'    => TRUE,
			'meta_query'       => [ 'relation' => 'AND' ],
		];

		foreach ( $constants as $field => $constant ) {
			if ( ! empty( $the_day[$field] ) ) {
				$args['orderby'][$field.'_clause'] = 'ASC'; // https://make.wordpress.org/core/?p=12639
				$args['meta_query'][$field.'_clause'] = [
					'key'     => $constant,
					'value'   => $the_day[$field],
					'compare' => '=',
					'type'    => 'cal' === $field ? 'CHAR' : 'NUMERIC',
				];
			}
		}

		$query = new \WP_Query();
		return $query->query( $args );
	}

	// NOTE: we can query multiple days at once bu the DB takes forever to respond!
	public static function getPostsConnected( $atts = [], $constants = NULL )
	{
		$args = self::atts( [
			'the_day' => [],
			'today'   => [],
			'type'    => 'any',
			'all'     => FALSE,
			'count'   => FALSE,
			'limit'   => self::limit(),
			'paged'   => self::paged(),
			'orderby' => self::orderby( 'ID' ),
			'order'   => self::order( 'desc' ),
			'status'  => is_admin() ? WordPress\Status::acceptable( isset( $atts['type'] ) ? $atts['type'] : 'any' ) : 'publish',
		], $atts );

		if ( empty( $args['today'] ) )
			$args['today'] = [ $args['the_day'] ];

		// if ( is_null( $constants ) )
		// 	$constants = self::getTheDayConstants();

		$query_args = [
			'orderby'             => $args['orderby'],
			'order'               => $args['order'],
			'post_type'           => $args['type'],
			'post_status'         => $args['status'],
			'posts_per_page'      => -1,
			'suppress_filters'    => TRUE,
			'no_found_rows'       => TRUE,
			'ignore_sticky_posts' => TRUE,
		];

		// if ( 'meta_value_num' === $query_args['orderby'] )
		// 	// $query_args['meta_key'] = $constants['year'];
		// 	$query_args['meta_key'] = array_values( $constants );

		if ( ! $args['count'] && ! $args['all'] ) {
			$query_args['posts_per_page'] = $args['limit'];
			$query_args['offset'] = ( $args['paged'] - 1 ) * $args['limit'];
		}

		if ( $args['count'] )
			$query_args['fields'] = 'ids';

		list( $query_args['meta_query'], $query_args['orderby'] ) = self::theDayMetaQuery( $args['today'], $constants );

		if ( 'date' === $query_args['orderby'] )
			$query_args['order'] = 'ASC';

		$query = new \WP_Query();
		$posts = $query->query( $query_args );

		if ( $args['count'] )
			return count( $posts );

		$pagination = Core\HTML::tablePagination(
			$query->found_posts,
			$query->max_num_pages,
			$args['limit'],
			$args['paged'],
			[],
			$args['all']
		);

		return [ $posts, $pagination ];
	}

	public static function theDayMetaQuery( $today, $constants = NULL )
	{
		$metaquery = [ 'relation' => 'OR' ];
		$orderby   = [];

		if ( is_null( $constants ) )
			$constants = self::getTheDayConstants();

		foreach ( $today as $offset => $the_day ) {

			if ( empty( $the_day ) )
				continue;

			$block = [];

			foreach ( $constants as $field => $constant ) {

				if ( empty( $the_day[$field] ) )
					continue;

				$clause = sprintf( 'the_day_%s_clause_%s', $field, $offset );

				if ( ! empty( $the_day[$field] ) )
					$block[$clause] = [
						'key'     => $constant,
						'value'   => $the_day[$field],
						'compare' => '=',
						'type'    => 'cal' === $field ? 'CHAR' : 'NUMERIC',
					];
			}

			if ( ! empty( $block ) ) {
				$clause = sprintf( 'the_day_%s_clause', $offset );
				$block['relation'] = 'AND';
				$metaquery[$clause] = $block;
			}
		}

		return [ $metaquery, $orderby ?: 'date' ];
	}

	public static function theDaySelect( $atts = [], $year = TRUE, $default_type = NULL, $calendars = NULL )
	{
		$args = self::atts( [
			'cal'   => $default_type ?? Core\L10n::calendar(),
			'day'   => '',
			'month' => '',
			'year'  => '',
		], $atts );

		if ( is_null( $calendars ) )
			$calendars = Services\Calendars::getDefualts( TRUE );

		$html = Core\HTML::tag( 'input', [
			'type'         => 'text',
			'autocomplete' => 'off',
			'min'          => '1',
			'max'          => '31',
			'class'        => '-day',
			'name'         => 'geditorial-today-date-day',
			'id'           => 'geditorial-today-date-day',
			'value'        => $args['day'],
			'title'        => _x( 'Day', 'Meta Box Input', 'geditorial-today' ),
			'placeholder'  => _x( 'Day', 'Meta Box Input Placeholder', 'geditorial-today' ),
			'data'         => [ 'ortho' => 'number' ],
			'onclick'      => 'this.focus();this.select()',
		] );

		$html.= Core\HTML::tag( 'input', [
			'type'         => 'text',
			'autocomplete' => 'off',
			'min'          => '1',
			'max'          => '12',
			'class'        => '-month',
			'name'         => 'geditorial-today-date-month',
			'id'           => 'geditorial-today-date-month',
			'value'        => $args['month'],
			'title'        => _x( 'Month', 'Meta Box Input', 'geditorial-today' ),
			'placeholder'  => _x( 'Month', 'Meta Box Input Placeholder', 'geditorial-today' ),
			'data'         => [ 'ortho' => 'number' ],
			'onclick'      => 'this.focus();this.select()',
		] );

		if ( $year )
			$html.= Core\HTML::tag( 'input', [
				'type'         => 'text',
				'autocomplete' => 'off',
				'class'        => '-year',
				'name'         => 'geditorial-today-date-year',
				'id'           => 'geditorial-today-date-year',
				'value'        => $year ? $args['year'] : '',
				'title'        => _x( 'Year', 'Meta Box Input', 'geditorial-today' ),
				'placeholder'  => _x( 'Year', 'Meta Box Input Placeholder', 'geditorial-today' ),
				'disabled'     => ! $year,
				'data'         => [ 'ortho' => 'number' ],
				'onclick'      => 'this.focus();this.select()',
			] );

		echo Core\HTML::wrap( $html, 'field-wrap '.( $year ? '-inputtext-date' : '-inputtext-half' ) );

		$html = Core\HTML::tag( 'option', [
			'value' => '',
		], _x( '&ndash; Select Calendar &ndash;', 'Meta Box Input Option None', 'geditorial-today' ) );

		foreach ( $calendars as $name => $title )
			$html.= Core\HTML::tag( 'option', [
				'value'    => $name,
				'selected' => $args['cal'] == $name,
			], $title );

		$html = Core\HTML::tag( 'select', [
			'class' => '-cal',
			'name'  => 'geditorial-today-date-cal',
			'id'    => 'geditorial-today-date-cal',
			'title' => _x( 'Calendar', 'Meta Box Input', 'geditorial-today' ),
		], $html );

		echo Core\HTML::wrap( $html, 'field-wrap -select' );
	}

	// TODO: add today button
	public static function theDayNavigation( $the_day, $type = NULL, $fallback = FALSE )
	{
		if ( empty( $the_day ) )
			return $fallback;

		if ( ! $datetime = Core\Date::getObject( self::getTheDayDateMySQL( $the_day, $type ) ) )
			return $fallback;

		if ( empty( $the_day['cal'] ) )
			$the_day['cal'] = $type;

		$buttons = [];
		$count   = count( $the_day );

		if ( 2 === $count && ! empty( $the_day['year'] ) ) {

			// yearly: `/{cal}/year/{year}`

			$buttons['next'] = Core\HTML::button(
				_x( 'Next Year', 'Button', 'geditorial-today' ),
				self::getTheDayLink( gEditorial\Datetime::getTheDay( $datetime->modify( '+1 year' ), $the_day['cal'] ), 'yearly' ),
				_x( 'The Next Year in this Calendar', 'Title Attr', 'geditorial-today' )
			);

			$buttons['previous'] = Core\HTML::button(
				_x( 'Previous Year', 'Button', 'geditorial-today' ),
				self::getTheDayLink( gEditorial\Datetime::getTheDay( $datetime->modify( '-1 year' ), $the_day['cal'] ), 'yearly' ),
				_x( 'The Previous Year in this Calendar', 'Title Attr', 'geditorial-today' )
			);

		} else if ( 2 === $count ) {

			// the-month: `/{cal}/{month}`

			$buttons['next'] = Core\HTML::button(
				_x( 'Next Month', 'Button', 'geditorial-today' ),
				self::getTheDayLink( gEditorial\Datetime::getTheDay( $datetime->modify( '+1 month' ), $the_day['cal'] ), 'themonth' ),
				_x( 'The Next Month in this Calendar', 'Title Attr', 'geditorial-today' )
			);

			$buttons['previous'] = Core\HTML::button(
				_x( 'Previous Month', 'Button', 'geditorial-today' ),
				self::getTheDayLink( gEditorial\Datetime::getTheDay( $datetime->modify( '-1 month' ), $the_day['cal'] ), 'themonth' ),
				_x( 'The Previous Month in this Calendar', 'Title Attr', 'geditorial-today' )
			);

		} else if ( 3 === $count && empty( $the_day['day'] ) ) {

			// monthly: `/{cal}/year/{year}/{month}`

			$buttons['next'] = Core\HTML::button(
				_x( 'Next Month', 'Button', 'geditorial-today' ),
				self::getTheDayLink( gEditorial\Datetime::getTheDay( $datetime->modify( '+1 month' ), $the_day['cal'] ), 'monthly' ),
				_x( 'The Next Month in this Year', 'Title Attr', 'geditorial-today' )
			);

			$buttons['previous'] = Core\HTML::button(
				_x( 'Previous Month', 'Button', 'geditorial-today' ),
				self::getTheDayLink( gEditorial\Datetime::getTheDay( $datetime->modify( '-1 month' ), $the_day['cal'] ), 'monthly' ),
				_x( 'The Previous Month in this Year', 'Title Attr', 'geditorial-today' )
			);

			$current = gEditorial\Datetime::getTheDay( $datetime, $the_day['cal'] );

			$buttons['month'] = Core\HTML::button(
				_x( 'This Month', 'Button', 'geditorial-today' ),
				self::getTheDayLink( $current, 'themonth' ),
				_x( 'This Month in the Calendar', 'Title Attr', 'geditorial-today' )
			);

			$buttons['year'] = Core\HTML::button(
				_x( 'This Year', 'Button', 'geditorial-today' ),
				self::getTheDayLink( $current, 'yearly' ),
				_x( 'This Year in the Calendar', 'Title Attr', 'geditorial-today' )
			);

		} else if ( 3 === $count ) {

			// annual: `/{cal}/{month}/{day}`

			$buttons['next'] = Core\HTML::button(
				_x( 'Next Day', 'Button', 'geditorial-today' ),
				self::getTheDayLink( gEditorial\Datetime::getTheDay( $datetime->modify( '+1 day' ), $the_day['cal'] ), 'annual' ),
				_x( 'The Next Day in this Calendar', 'Title Attr', 'geditorial-today' )
			);

			$buttons['previous'] = Core\HTML::button(
				_x( 'Previous Day', 'Button', 'geditorial-today' ),
				self::getTheDayLink( gEditorial\Datetime::getTheDay( $datetime->modify( '-1 day' ), $the_day['cal'] ), 'annual' ),
				_x( 'The Previous Day in this Calendar', 'Title Attr', 'geditorial-today' )
			);

			$current = gEditorial\Datetime::getTheDay( $datetime, $the_day['cal'] );

			$buttons['month'] = Core\HTML::button(
				_x( 'This Month', 'Button', 'geditorial-today' ),
				self::getTheDayLink( $current, 'themonth' ),
				_x( 'This Month in the Calendar', 'Title Attr', 'geditorial-today' )
			);

			$buttons['year'] = Core\HTML::button(
				_x( 'This Year', 'Button', 'geditorial-today' ),
				self::getTheDayLink( $current, 'yearly' ),
				_x( 'This Year in the Calendar', 'Title Attr', 'geditorial-today' )
			);

		} else {

			// full-date: `/{cal}/{month}/{day}/{year}`

			$buttons['next'] = Core\HTML::button(
				_x( 'Next Day', 'Button', 'geditorial-today' ),
				self::getTheDayLink( gEditorial\Datetime::getTheDay( $datetime->modify( '+1 day' ), $the_day['cal'] ), 'full' ),
				_x( 'The Next Day', 'Title Attr', 'geditorial-today' )
			);

			$buttons['previous'] = Core\HTML::button(
				_x( 'Previous Day', 'Button', 'geditorial-today' ),
				self::getTheDayLink( gEditorial\Datetime::getTheDay( $datetime->modify( '-1 day' ), $the_day['cal'] ), 'full' ),
				_x( 'The Previous Day', 'Title Attr', 'geditorial-today' )
			);

			$current = gEditorial\Datetime::getTheDay( $datetime, $the_day['cal'] );

			$buttons['month'] = Core\HTML::button(
				_x( 'This Month', 'Button', 'geditorial-today' ),
				self::getTheDayLink( $current, 'monthly' ),
				_x( 'This Month in the Calendar', 'Title Attr', 'geditorial-today' )
			);

			$buttons['year'] = Core\HTML::button(
				_x( 'This Year', 'Button', 'geditorial-today' ),
				self::getTheDayLink( $current, 'yearly' ),
				_x( 'This Year in the Calendar', 'Title Attr', 'geditorial-today' )
			);
		}

		return $buttons;
	}

	public static function theDayNewConnected( $posttypes, $the_day = [], $posts = [] )
	{
		if ( ! is_user_logged_in() )
			return [];

		$buttons = [];
		$admin   = is_admin();

		unset( $the_day['year'] );

		foreach ( (array) $posttypes as $posttype ) {

			$object = WordPress\PostType::object( $posttype );

			if ( ! current_user_can( $object->cap->create_posts ) )
				continue;

			$title = $object->labels->add_new_item;

			if ( $admin )
				$title = Services\Icons::posttypeMarkup( $object ).' '.$title;

			$buttons[] = Core\HTML::button( $title,
				WordPress\PostType::newLink( $object->name, $the_day ),
				sprintf(
					/* translators: `%s`: singular name */
					_x( 'New %s connected to this day', 'Title Attr', 'geditorial-today' ),
					$object->labels->singular_name
				),
				$admin
			);
		}

		if ( FALSE === $posts )
			return $buttons;

		$object = WordPress\PostType::object( self::constant( 'main_posttype', 'day' ) );

		if ( WordPress\PostType::can( $object, 'create_posts' ) ) {

			$title = $object->labels->add_new_item;

			if ( $admin )
				$title = Services\Icons::posttypeMarkup( $object ).' '.$title;

			$buttons[] = Core\HTML::button( $title,
				WordPress\PostType::newLink( $object->name, $the_day ),
				_x( 'New Day!', 'Title Attr', 'geditorial-today' ),
				$admin
			);
		}

		foreach ( $posts as $post ) {

			if ( current_user_can( 'edit_post', $post->ID ) ) {

				$title = $object->labels->edit_item;

				if ( $admin )
					$title = Services\Icons::posttypeMarkup( $object ).' '.$title;

				$buttons[] = Core\HTML::button( $title,
					WordPress\Post::edit( $post ),
					_x( 'Edit Day!', 'Title Attr', 'geditorial-today' ),
					$admin
				);
			}
		}

		return $buttons;
	}
}
