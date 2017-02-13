<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialTodayHelper extends gEditorialHelper
{

	const MODULE = 'today';

	// returns array of post date in given cal
	public static function getTheDayFromToday( $today = NULL, $type = 'gregorian' )
	{
		$the_day = array( 'cal' => 'gregorian' );

		if ( is_null( $today ) )
			$today = current_time( 'timestamp' );

		if ( in_array( $type, array( 'hijri', 'islamic' ) ) ) {

			$convertor = array( 'gPersianDateDateTime', 'toHijri' );
			$the_day['cal'] = 'islamic';

		} else if ( in_array( $type, array( 'jalali', 'persian' ) ) ) {

			$convertor = array( 'gPersianDateDateTime', 'toJalali' );
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

	// TODO: link each part to front summary page
	public static function displayTheDayFromPost( $post, $default_type = 'gregorian', $constants = NULL )
	{
		static $calendars, $months;

		$the_day = self::atts( array(
			'cal'   => $default_type,
			'day'   => '',
			'month' => '',
			'year'  => '',
		), self::getTheDayFromPost( $post, $default_type, $constants ) );

		if ( ! $the_day['day'] && ! $the_day['month'] && ! $the_day['year'] ) {

			echo '<div class="-date-icon-empty">';
				echo '&mdash;';
			echo '</div>';

		} else {

			if ( empty( $calendars ) )
				$calendars = gEditorialHelper::getDefualtCalendars();

			if ( ! isset( $months[$the_day['cal']] ) )
				$months[$the_day['cal']] = gEditorialHelper::getMonths( $the_day['cal'] );

			echo '<div class="-date-icon">';

				if ( $the_day['day'] )
					echo '<span class="-day">'.gEditorialNumber::format( $the_day['day'] ).'</span>';

				if ( $the_day['month'] ) {

					$key = gEditorialNumber::intval( $key, FALSE );
					$key = gEditorialNumber::zeroise( $the_day['month'], 2 );

					if ( isset( $months[$the_day['cal']][$key] ) )
						$the_day['month'] = $months[$the_day['cal']][$key];

					echo '<span class="-month">'.$the_day['month'].'</span>';
				}

				if ( $the_day['year'] )
					echo '<span class="-year">'.gEditorialNumber::format( $the_day['year'] ).'</span>';

				if ( $the_day['cal'] )
					echo '<span class="-cal">'.
						( empty( $calendars[$the_day['cal']] ) ? $the_day['cal'] : $calendars[$the_day['cal']] )
					.'</span>';

			echo '</div>';
		}
	}

	public static function getTheDayFromPost( $post, $default_type = 'gregorian', $constants = NULL )
	{
		$the_day = array();

		if ( is_null( $constants ) )
			$constants = self::getTheDayConstants();

		$post_meta = get_post_meta( $post->ID );

		foreach ( $constants as $field => $constant )
			if ( ! empty( $post_meta[$constant][0] ) )
				$the_day[$field] = $post_meta[$constant][0];

		if ( empty ( $the_day['cal'] ) )
			return array_merge( array( 'cal' => $default_type ), $the_day );

		return $the_day;
	}

	public static function getTheDayFromQuery( $admin = NULL, $default_type = 'gregorian', $constants = NULL )
	{
		$the_day = array();

		if ( is_null( $admin ) )
			$admin = is_admin();

		if ( is_null( $constants ) )
			$constants = self::getTheDayConstants();

		foreach ( $constants as $field => $constant ) {

			if ( ! $admin && ( $var = get_query_var( 'day_'.$field, FALSE ) ) )
				$the_day[$field] = $var;

			if ( $admin && ( $var = self::req( $field, FALSE ) ))
				$the_day[$field] = $var;
		}

		if ( empty ( $the_day['cal'] ) )
			return array_merge( array( 'cal' => $default_type ), $the_day );

		return $the_day;
	}

	// NOT USED
	public static function getTheDayByPost( $post, $default_type = 'gregorian', $constants = NULL )
	{
		$the_day = array();

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

	public static function getTheDayConstants()
	{
		return array(
			'cal'   => self::constant( 'meta_cal', '_theday_cal' ),
			'day'   => self::constant( 'meta_day', '_theday_day' ),
			'month' => self::constant( 'meta_month', '_theday_month' ),
			'year'  => self::constant( 'meta_year', '_theday_year' ),
		);
	}

	public static function getPostsConnected( $atts = array(), $constants = NULL )
	{
		$args = self::atts( array(
			'the_day' => array(),
			'type'    => 'any',
			'all'     => FALSE,
			'count'   => FALSE,
			'limit'   => self::limit(),
			'paged'   => self::paged(),
			'orderby' => self::orderby( 'ID' ),
			'order'   => self::order( 'asc' ),
			'status'  => array( 'publish', 'future', 'draft', 'pending' ),
		), $atts );

		if ( is_null( $constants ) )
			$constants = self::getTheDayConstants();

		$query_args = array(
			// 'orderby'          => $args['orderby'],
			// 'order'            => $args['order'],
			'post_type'        => $args['type'],
			'post_status'      => $args['status'],
			'suppress_filters' => TRUE,
			'no_found_rows'    => TRUE,
		);

		if ( ! $args['count'] && ! $args['all'] ) {
			$query_args['posts_per_page'] = $args['limit'];
			$query_args['offset'] = ( $args['paged'] - 1 ) * $args['limit'];
		}

		$query_args['meta_query'] = array();

		foreach ( $constants as $field => $constant ) {
			if ( ! empty( $args['the_day'][$field] ) ) {
				$query_args['orderby'][$field.'_clause'] = 'ASC'; // https://make.wordpress.org/core/2015/03/30/query-improvements-in-wp-4-2-orderby-and-meta_query/
				$query_args['meta_query'][$field.'_clause'] = array(
					'key'     => $constant,
					'value'   => $args['the_day'][$field],
					'compare' => '=',
				);
			}
		}

		$query = new \WP_Query;
		$posts = $query->query( $query_args );

		if ( $args['count'] )
			return count( $posts );

		$pagination = gEditorialHTML::tablePagination( $query->found_posts, $query->max_num_pages, $args['limit'], $args['paged'], $args['all'] );

		return array( $posts, $pagination );
	}

	public static function theDaySelect( $atts = array(), $year = TRUE, $default_type = 'gregorian' )
	{
		$args = self::atts( array(
			'cal'   => $default_type,
			'day'   => '',
			'month' => '',
			'year'  => '',
		), $atts );

		$html = '';

		$html .= gEditorialHTML::tag( 'input', array(
			'type'        => 'text',
			'dir'         => 'ltr',
			'min'         => '1',
			'max'         => '31',
			'name'        => 'geditorial-today-date-day',
			'id'          => 'geditorial-today-date-day',
			'value'       => $args['day'],
			'title'       => _x( 'Day', 'Today Module: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Day', 'Today Module: Meta Box Input Placeholder', GEDITORIAL_TEXTDOMAIN ),
		) );

		$html .= gEditorialHTML::tag( 'input', array(
			'type'        => 'text',
			'dir'         => 'ltr',
			'min'         => '1',
			'max'         => '12',
			'name'        => 'geditorial-today-date-month',
			'id'          => 'geditorial-today-date-month',
			'value'       => $args['month'],
			'title'       => _x( 'Month', 'Today Module: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Month', 'Today Module: Meta Box Input Placeholder', GEDITORIAL_TEXTDOMAIN ),
		) );

		if ( $year )
		$html .= gEditorialHTML::tag( 'input', array(
			'type'        => 'text',
			'dir'         => 'ltr',
			'name'        => 'geditorial-today-date-year',
			'id'          => 'geditorial-today-date-year',
			'value'       => $year ? $args['year'] : '',
			'title'       => _x( 'Year', 'Today Module: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Year', 'Today Module: Meta Box Input Placeholder', GEDITORIAL_TEXTDOMAIN ),
			'disabled'    => ! $year,
		) );

		echo gEditorialHTML::tag( 'div', array(
			'class' => 'field-wrap '.( $year ? 'field-wrap-inputtext-date' : 'field-wrap-inputtext-half' ),
		), $html );

		$html = '';

		foreach ( self::getDefualtCalendars( TRUE ) as $name => $title )
			$html .= gEditorialHTML::tag( 'option', array(
				'value'    => $name,
				'selected' => $args['cal'] == $name,
			), $title );

		$html = gEditorialHTML::tag( 'select', array(
			'name'        => 'geditorial-today-date-cal',
			'id'          => 'geditorial-today-date-cal',
			'title'       => _x( 'Calendar', 'Today Module: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Calendar', 'Today Module: Meta Box Input Placeholder', GEDITORIAL_TEXTDOMAIN ),
		), $html );

		echo gEditorialHTML::tag( 'div', array(
			'class' => 'field-wrap field-wrap-select',
		), $html );

		// TODO: insert conversion buttons
	}

	public static function theDayNewConnected( $posttypes, $the_day = array(), $posttype = FALSE )
	{
		if ( ! is_user_logged_in() )
			return;

		$html = '';
		$new  = admin_url( 'post-new.php' );

		if ( $posttype ) {

			$posttype_object = get_post_type_object( $posttype );

			if ( current_user_can( $posttype_object->cap->create_posts ) ) {

				// FIXME: get edit item url

				$icon   = gEditorialHTML::getDashicon( $posttype_object->menu_icon ? $posttype_object->menu_icon : 'admin-post' );
				$noyear = $the_day;

				unset( $noyear['year'] );

				$html .= gEditorialHTML::tag( 'a', array(
					'href'          => add_query_arg( array_merge( $noyear, array( 'post_type' => $posttype ) ), $new ),
					'class'         => 'button -add-posttype -add-posttype-'.$posttype,
					'target'        => '_blank',
					'data-posttype' => $posttype,
					'title'         => _x( 'New Day!', 'Today Module', GEDITORIAL_TEXTDOMAIN ),
				), $icon.' '.$posttype_object->labels->edit_item );
			}

			unset( $posttype_object, $posttype, $noyear );
		}

		foreach ( $posttypes as $posttype ) {

			$posttype_object = get_post_type_object( $posttype );

			if ( ! current_user_can( $posttype_object->cap->create_posts ) )
				continue;

			$icon = gEditorialHTML::getDashicon( $posttype_object->menu_icon ? $posttype_object->menu_icon : 'admin-post' );

			$html .= gEditorialHTML::tag( 'a', array(
				'href'          => add_query_arg( array_merge( $the_day, array( 'post_type' => $posttype ) ), $new ),
				'class'         => 'button -add-posttype -add-posttype-'.$posttype,
				'target'        => '_blank',
				'data-posttype' => $posttype,
				'title'         => sprintf( _x( 'New %s connected to this day', 'Today Module', GEDITORIAL_TEXTDOMAIN ), $posttype_object->labels->singular_name ),
			), $icon.' '.$posttype_object->labels->add_new_item );
		}

		echo gEditorialHTML::tag( 'div', array(
			'class' => 'field-wrap field-wrap-buttons',
		), $html );
	}
}
