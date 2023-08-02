<?php namespace geminorum\gEditorial\Modules\Today;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'today';

	// TODO: check min/max for day/month
	// TODO: 4 digit year based on `$type`
	public static function parseTheFullDay( $text, $type = 'gregorian' )
	{
		if ( WordPress\Strings::isEmpty( $text ) )
			return [];

		$text = Core\Number::intval( trim( $text ), FALSE );
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

	// returns array of post date in given cal
	public static function getTheDayFromToday( $today = NULL, $type = 'gregorian' )
	{
		$the_day = [ 'cal' => 'gregorian' ];

		if ( is_null( $today ) )
			$today = current_time( 'timestamp' );

		if ( in_array( $type, [ 'hijri', 'islamic' ] ) ) {

			$convertor = [ 'gPersianDateDateTime', 'toHijri' ];
			$the_day['cal'] = 'islamic';

		} else if ( in_array( $type, [ 'jalali', 'persian' ] ) ) {

			$convertor = [ 'gPersianDateDateTime', 'toJalali' ];
			$the_day['cal'] = 'persian';
		}

		if ( class_exists( 'gPersianDateDateTime' )
			&& 'gregorian' != $the_day['cal'] ) {

			list(
				$the_day['year'],
				$the_day['month'],
				$the_day['day']
			) = call_user_func_array( $convertor,
				explode( '-', date( 'Y-n-j', $today ) ) );

		} else {

			$the_day['cal'] = 'gregorian';
			$the_day['day']   = date( 'j', $today );
			$the_day['month'] = date( 'n', $today );
			$the_day['year']  = date( 'Y', $today );
		}

		return $the_day;
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
			$gEditorialTodayMonths[$the_day['cal']] = Datetime::getMonths( $the_day['cal'] );

		$parts = [];

		if ( $the_day['day'] )
			$parts['day'] = Core\Number::localize( $the_day['day'] );

		if ( $the_day['month'] ) {

			$month = Core\Number::intval( $the_day['month'], FALSE );
			$key   = Core\Number::zeroise( $month, 2 );

			if ( isset( $gEditorialTodayMonths[$the_day['cal']][$key] ) )
				$the_day['month'] = $gEditorialTodayMonths[$the_day['cal']][$key];

			$parts['month'] = $the_day['month'];
		}

		if ( $the_day['year'] )
			$parts['year'] = Core\Number::localize( $the_day['year'] );

		if ( empty( $parts ) )
			return $empty;

		if ( $the_day['cal'] && $display_cal )
			$parts['cal'] = empty( $gEditorialTodayCalendars[$the_day['cal']] )
				? $the_day['cal']
				: $gEditorialTodayCalendars[$the_day['cal']];

		return WordPress\Strings::getJoined( $parts, '[', ']', '', Datetime::dateSeparator() );
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
				$gEditorialTodayMonths[$the_day['cal']] = Datetime::getMonths( $the_day['cal'] );

			echo '<div class="-today -date-badge">';

				if ( $the_day['day'] )
					echo '<span class="-day" data-day="'.Core\HTML::escape( $the_day['day'] )
						.'"><a target="_blank" href="'.self::getTheDayLink( $stored, 'day' )
						.'">'.Core\Number::localize( $the_day['day'] ).'</a></span>';

				if ( $the_day['month'] ) {

					$month = Core\Number::intval( $the_day['month'], FALSE );
					$key   = Core\Number::zeroise( $month, 2 );

					if ( isset( $gEditorialTodayMonths[$the_day['cal']][$key] ) )
						$the_day['month'] = $gEditorialTodayMonths[$the_day['cal']][$key];

					echo '<span class="-month" data-month="'.Core\HTML::escape( $month )
						.'"><a target="_blank" href="'.self::getTheDayLink( $stored, 'month' )
						.'">'.$the_day['month'].'</a></span>';
				}

				if ( $the_day['year'] )
					echo '<span class="-year" data-year="'.Core\HTML::escape( $the_day['year'] )
						.'"><a target="_blank" href="'.self::getTheDayLink( $stored, 'year' )
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

	public static function getTheDayLink( $the_day, $context = 'full' )
	{
		switch ( $context ) {
			case 'cal': unset( $the_day['year'], $the_day['month'], $the_day['day'] ); break;
			case 'year': unset( $the_day['month'], $the_day['day'] ); break;
			case 'month': unset( $the_day['day'] ); break;
		}

		return home_url( implode( '/', $the_day ) );
	}

	public static function getTheDayFromPost( $post, $default_type = 'gregorian', $constants = NULL )
	{
		$the_day = [];

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

	public static function getTheDayFromQuery( $admin = NULL, $default_type = 'gregorian', $constants = NULL )
	{
		$the_day = [];

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

		if ( empty( $the_day['cal'] ) )
			return array_merge( [ 'cal' => $default_type ], $the_day );

		return $the_day;
	}

	// NOT USED
	// FIXME: DROP THIS
	public static function getTheDayByPost( $post, $default_type = 'gregorian', $constants = NULL )
	{
		$the_day = [];

		if ( is_null( $constants ) )
			$constants = self::getTheDayConstants();

		$post_meta = get_post_meta( $post->ID );

		$the_day['cal'] = empty( $post_meta[$constants['cal']][0] ) ? self::req( 'cal', $default_type ) : $post_meta[$constants['cal']][0];

		$post_date = Datetime::getTheDayByPost( $post, $the_day['cal'] );

		$the_day['day']   = empty( $post_meta[$constants['day']][0] ) ? self::req( 'day', $post_date['day'] ) : $post_meta[$constants['day']][0];
		$the_day['month'] = empty( $post_meta[$constants['month']][0] ) ? self::req( 'month', $post_date['month'] ) : $post_meta[$constants['month']][0];
		$the_day['year']  = empty( $post_meta[$constants['year']][0] ) ? self::req( 'year', $post_date['year'] ) : $post_meta[$constants['year']][0];

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
		$the_day = self::atts( [
			'cal'   => '',
			'day'   => '',
			'month' => '',
			// 'year'  => '', // there is no year in day cpt
		], $stored );

		if ( is_null( $constants ) )
			$constants = self::getTheDayConstants();

		$args = [
			'post_type'        => self::constant( 'day_cpt', 'day' ),
			'post_status'      => 'any',
			'suppress_filters' => TRUE,
			'no_found_rows'    => TRUE,
			'meta_query'       => [],
		];

		foreach ( $constants as $field => $constant ) {
			if ( ! empty( $the_day[$field] ) ) {
				$args['orderby'][$field.'_clause'] = 'ASC'; // https://make.wordpress.org/core/?p=12639
				$args['meta_query'][$field.'_clause'] = [
					'key'     => $constant,
					'value'   => $the_day[$field],
					'compare' => '=',
				];
			}
		}

		$query = new \WP_Query();
		return $query->query( $args );
	}

	public static function getPostsConnected( $atts = [], $constants = NULL )
	{
		$args = self::atts( [
			'the_day' => [],
			'type'    => 'any',
			'all'     => FALSE,
			'count'   => FALSE,
			'limit'   => self::limit(),
			'paged'   => self::paged(),
			'orderby' => self::orderby( 'ID' ),
			'order'   => self::order( 'asc' ),
			'status'  => [ 'publish', 'future', 'draft', 'pending' ],
		], $atts );

		if ( is_null( $constants ) )
			$constants = self::getTheDayConstants();

		$query_args = [
			// 'orderby'          => $args['orderby'],
			// 'order'            => $args['order'],
			'post_type'        => $args['type'],
			'post_status'      => $args['status'],
			'suppress_filters' => TRUE,
			// 'no_found_rows'    => TRUE,
		];

		if ( ! $args['count'] && ! $args['all'] ) {
			$query_args['posts_per_page'] = $args['limit'];
			$query_args['offset'] = ( $args['paged'] - 1 ) * $args['limit'];
		}

		if ( $args['count'] )
			$query_args['fields'] = 'ids';

		$query_args['meta_query'] = [];

		foreach ( $constants as $field => $constant ) {
			if ( ! empty( $args['the_day'][$field] ) ) {
				$query_args['orderby'][$field.'_clause'] = 'ASC'; // https://make.wordpress.org/core/?p=12639
				$query_args['meta_query'][$field.'_clause'] = [
					'key'     => $constant,
					'value'   => $args['the_day'][$field],
					'compare' => '=',
				];
			}
		}

		$query = new \WP_Query();
		$posts = $query->query( $query_args );

		if ( $args['count'] )
			return count( $posts );

		$pagination = Core\HTML::tablePagination( $query->found_posts, $query->max_num_pages, $args['limit'], $args['paged'], [], $args['all'] );

		return [ $posts, $pagination ];
	}

	public static function theDaySelect( $atts = [], $year = TRUE, $default_type = 'gregorian', $calendars = NULL )
	{
		$args = self::atts( [
			'cal'   => $default_type,
			'day'   => '',
			'month' => '',
			'year'  => '',
		], $atts );

		if ( is_null( $calendars ) )
			$calendars = Services\Calendars::getDefualts( TRUE );

		$html = '';

		$html.= Core\HTML::tag( 'input', [
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

	public static function theDayNewConnected( $posttypes, $the_day = [], $the_post = FALSE )
	{
		if ( ! is_user_logged_in() )
			return;

		$html = '';

		unset( $the_day['year'] );

		foreach ( $posttypes as $posttype ) {

			$object = WordPress\PostType::object( $posttype );

			if ( ! current_user_can( $object->cap->create_posts ) )
				continue;

			$title = $object->labels->add_new_item;

			if ( is_admin() )
				$title = Helper::getPostTypeIcon( $object ).' '.$title;

			$html.= Core\HTML::button( $title,
				Core\WordPress::getPostNewLink( $object->name, $the_day ),
				/* translators: %s: singular name */
				sprintf( _x( 'New %s connected to this day', 'Title Attr', 'geditorial-today' ), $object->labels->singular_name ),
				is_admin()
			).' ';
		}

		if ( FALSE !== $the_post ) {

			$object = WordPress\PostType::object( self::constant( 'day_cpt', 'day' ) );

			if ( TRUE === $the_post ) {

				if ( current_user_can( $object->cap->create_posts ) ) {

					$title = $object->labels->add_new_item;

					if ( is_admin() )
						$title = Helper::getPostTypeIcon( $object ).' '.$title;

					$html.= Core\HTML::button( $title,
						Core\WordPress::getPostNewLink( $object->name, $the_day ),
						_x( 'New Day!', 'Title Attr', 'geditorial-today' ),
						is_admin()
					);
				}

			} else if ( $the_post ) {

				if ( current_user_can( 'edit_post', (int) $the_post ) ) {

					$title = $object->labels->edit_item;

					if ( is_admin() )
						$title = Helper::getPostTypeIcon( $object ).' '.$title;

					$html.= Core\HTML::button( $title,
						Core\WordPress::getPostEditLink( $the_post ),
						_x( 'Edit Day!', 'Title Attr', 'geditorial-today' ),
						is_admin()
					);
				}
			}
		}

		return Core\HTML::wrap( $html, 'field-wrap -buttons' );
	}
}
