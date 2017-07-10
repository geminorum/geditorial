<?php namespace geminorum\gEditorial\Helpers;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;

class Today extends gEditorial\Helper
{

	const MODULE = 'today';

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

	public static function titleTheDayFromPost( $post, $default_type = 'gregorian', $constants = NULL, $empty = '&mdash;' )
	{
		global $gEditorialTodayCalendars, $gEditorialTodayMonths;

		$the_day = self::atts( [
			'cal'   => $default_type,
			'day'   => '',
			'month' => '',
			'year'  => '',
		], self::getTheDayFromPost( $post, $default_type, $constants ) );

		if ( ! $the_day['day'] && ! $the_day['month'] && ! $the_day['year'] )
			return $empty;

		if ( empty( $gEditorialTodayCalendars ) )
			$gEditorialTodayCalendars = Helper::getDefualtCalendars();

		if ( ! isset( $gEditorialTodayMonths[$the_day['cal']] ) )
			$gEditorialTodayMonths[$the_day['cal']] = Helper::getMonths( $the_day['cal'] );

		$parts = [];

		if ( $the_day['day'] )
			$parts['day'] = Number::format( $the_day['day'] );

		if ( $the_day['month'] ) {

			$month = Number::intval( $the_day['month'], FALSE );
			$key   = Number::zeroise( $month, 2 );

			if ( isset( $gEditorialTodayMonths[$the_day['cal']][$key] ) )
				$the_day['month'] = $gEditorialTodayMonths[$the_day['cal']][$key];

			$parts['month'] = $the_day['month'];
		}

		if ( $the_day['year'] )
			$parts['year'] = Number::format( $the_day['year'] );

		if ( ! count( $parts ) )
			return $empty;

		if ( $the_day['cal'] )
			$parts['cal'] = empty( $gEditorialTodayCalendars[$the_day['cal']] ) ? $the_day['cal'] : $gEditorialTodayCalendars[$the_day['cal']];

		return Helper::getJoined( $parts, '[', ']' );
	}

	// TODO: link each part to front summary page
	public static function displayTheDayFromPost( $post, $default_type = 'gregorian', $constants = NULL, $empty = '&mdash;' )
	{
		global $gEditorialTodayCalendars, $gEditorialTodayMonths;

		$the_day = self::atts( [
			'cal'   => $default_type,
			'day'   => '',
			'month' => '',
			'year'  => '',
		], self::getTheDayFromPost( $post, $default_type, $constants ) );

		if ( ! $the_day['day'] && ! $the_day['month'] && ! $the_day['year'] ) {

			if ( $empty )
				echo '<div class="-today -date-icon-empty">'.$empty.'</div>';

		} else {

			if ( empty( $gEditorialTodayCalendars ) )
				$gEditorialTodayCalendars = Helper::getDefualtCalendars();

			if ( ! isset( $gEditorialTodayMonths[$the_day['cal']] ) )
				$gEditorialTodayMonths[$the_day['cal']] = Helper::getMonths( $the_day['cal'] );

			echo '<div class="-today -date-icon">';

				if ( $the_day['day'] )
					echo '<span class="-day" data-day="'.esc_attr( $the_day['day'] )
						.'">'.Number::format( $the_day['day'] ).'</span>';

				if ( $the_day['month'] ) {

					$month = Number::intval( $the_day['month'], FALSE );
					$key   = Number::zeroise( $month, 2 );

					if ( isset( $gEditorialTodayMonths[$the_day['cal']][$key] ) )
						$the_day['month'] = $gEditorialTodayMonths[$the_day['cal']][$key];

					echo '<span class="-month" data-month="'.esc_attr( $month ).'">'.$the_day['month'].'</span>';
				}

				if ( $the_day['year'] )
					echo '<span class="-year" data-year="'.esc_attr( $the_day['year'] ).'">'.Number::format( $the_day['year'] ).'</span>';

				if ( $the_day['cal'] )
					echo '<span class="-cal" data-cal="'.esc_attr( $the_day['cal'] ).'">'.
						( empty( $gEditorialTodayCalendars[$the_day['cal']] ) ? $the_day['cal'] : $gEditorialTodayCalendars[$the_day['cal']] )
					.'</span>';

			echo '</div>';
		}
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
	public static function getTheDayByPost( $post, $default_type = 'gregorian', $constants = NULL )
	{
		$the_day = [];

		if ( is_null( $constants ) )
			$constants = self::getTheDayConstants();

		$post_meta = get_post_meta( $post->ID );

		$the_day['cal'] = empty( $post_meta[$constants['cal']][0] ) ? self::req( 'cal', $default_type ) : $post_meta[$constants['cal']][0];

		$post_date = parent::getTheDayByPost( $post, $the_day['cal'] );

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
			'no_found_rows'    => TRUE,
		];

		if ( ! $args['count'] && ! $args['all'] ) {
			$query_args['posts_per_page'] = $args['limit'];
			$query_args['offset'] = ( $args['paged'] - 1 ) * $args['limit'];
		}

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

		$query = new \WP_Query;
		$posts = $query->query( $query_args );

		if ( $args['count'] )
			return count( $posts );

		$pagination = HTML::tablePagination( $query->found_posts, $query->max_num_pages, $args['limit'], $args['paged'], [], $args['all'] );

		return [ $posts, $pagination ];
	}

	public static function theDaySelect( $atts = [], $year = TRUE, $default_type = 'gregorian' )
	{
		$args = self::atts( [
			'cal'   => $default_type,
			'day'   => '',
			'month' => '',
			'year'  => '',
		], $atts );

		$html = '';

		$html .= HTML::tag( 'input', [
			'type'         => 'text',
			'autocomplete' => 'off',
			'min'          => '1',
			'max'          => '31',
			'class'        => '-day',
			'name'         => 'geditorial-today-date-day',
			'id'           => 'geditorial-today-date-day',
			'value'        => $args['day'],
			'title'        => _x( 'Day', 'Modules: Today: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder'  => _x( 'Day', 'Modules: Today: Meta Box Input Placeholder', GEDITORIAL_TEXTDOMAIN ),
			'data'         => [ 'ortho' => 'number' ],
		] );

		$html .= HTML::tag( 'input', [
			'type'         => 'text',
			'autocomplete' => 'off',
			'min'          => '1',
			'max'          => '12',
			'class'        => '-month',
			'name'         => 'geditorial-today-date-month',
			'id'           => 'geditorial-today-date-month',
			'value'        => $args['month'],
			'title'        => _x( 'Month', 'Modules: Today: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder'  => _x( 'Month', 'Modules: Today: Meta Box Input Placeholder', GEDITORIAL_TEXTDOMAIN ),
			'data'         => [ 'ortho' => 'number' ],
		] );

		if ( $year )
			$html .= HTML::tag( 'input', [
				'type'         => 'text',
				'autocomplete' => 'off',
				'class'        => '-year',
				'name'         => 'geditorial-today-date-year',
				'id'           => 'geditorial-today-date-year',
				'value'        => $year ? $args['year'] : '',
				'title'        => _x( 'Year', 'Modules: Today: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
				'placeholder'  => _x( 'Year', 'Modules: Today: Meta Box Input Placeholder', GEDITORIAL_TEXTDOMAIN ),
				'disabled'     => ! $year,
				'data'         => [ 'ortho' => 'number' ],
			] );

		echo HTML::tag( 'div', [
			'class' => 'field-wrap '.( $year ? 'field-wrap-inputtext-date' : 'field-wrap-inputtext-half' ),
		], $html );

		$html = HTML::tag( 'option', [
			'value' => '',
		], _x( '&mdash; Select Calendar &mdash;', 'Modules: Today: Meta Box Input Option None', GEDITORIAL_TEXTDOMAIN ) );

		foreach ( self::getDefualtCalendars( TRUE ) as $name => $title )
			$html .= HTML::tag( 'option', [
				'value'    => $name,
				'selected' => $args['cal'] == $name,
			], $title );

		$html = HTML::tag( 'select', [
			'class' => '-cal',
			'name'  => 'geditorial-today-date-cal',
			'id'    => 'geditorial-today-date-cal',
			'title' => _x( 'Calendar', 'Modules: Today: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
		], $html );

		echo HTML::tag( 'div', [
			'class' => 'field-wrap field-wrap-select',
		], $html );

		// TODO: insert conversion buttons
	}

	public static function theDayNewConnected( $posttypes, $the_day = [], $posttype = FALSE )
	{
		if ( ! is_user_logged_in() )
			return;

		$html = '';
		$new  = admin_url( 'post-new.php' );

		if ( $posttype ) {

			$posttype_object = get_post_type_object( $posttype );

			if ( current_user_can( $posttype_object->cap->create_posts ) ) {

				// FIXME: get edit item url

				$noyear = $the_day;

				unset( $noyear['year'] );

				$html .= HTML::tag( 'a', [
					'href'          => add_query_arg( array_merge( $noyear, [ 'post_type' => $posttype ] ), $new ),
					'class'         => 'button -add-posttype -add-posttype-'.$posttype,
					'target'        => '_blank',
					'data-posttype' => $posttype,
					'title'         => _x( 'New Day!', 'Modules: Today', GEDITORIAL_TEXTDOMAIN ),
				], Helper::getPostTypeIcon( $posttype_object ).' '.$posttype_object->labels->edit_item );
			}

			unset( $posttype_object, $posttype, $noyear );
		}

		foreach ( $posttypes as $posttype ) {

			$posttype_object = get_post_type_object( $posttype );

			if ( ! current_user_can( $posttype_object->cap->create_posts ) )
				continue;

			$html .= HTML::tag( 'a', [
				'href'          => add_query_arg( array_merge( $the_day, [ 'post_type' => $posttype ] ), $new ),
				'class'         => 'button -add-posttype -add-posttype-'.$posttype,
				'target'        => '_blank',
				'data-posttype' => $posttype,
				'title'         => sprintf( _x( 'New %s connected to this day', 'Modules: Today', GEDITORIAL_TEXTDOMAIN ), $posttype_object->labels->singular_name ),
			], Helper::getPostTypeIcon( $posttype_object ).' '.$posttype_object->labels->add_new_item );
		}

		echo HTML::tag( 'div', [
			'class' => 'field-wrap field-wrap-buttons',
		], $html );
	}
}
