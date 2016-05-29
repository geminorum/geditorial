<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialEvent extends gEditorialModuleCore
{

	public static function module()
	{
		return array(
			'name'     => 'event',
			'title'    => _x( 'Event', 'Event Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Events Integrated', 'Event Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon' => 'calendar-alt',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'startend_support',
					'title'       => _x( 'Start ~ End Support', 'Event Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Specify events based on the actual date & time', 'Event Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '1',
				),
				array(
					'field'       => 'display_type',
					'title'       => _x( 'Display Calendar Type', 'Event Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'For each event you can select the calendar type. Or else select default below.', 'Event Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '1',
				),
				'calendar_type',
				'comment_status',
			),
		);
	}

	protected function get_global_constants()
	{
		return array(
			'event_cpt'         => 'event',
			'event_cpt_archive' => 'events',
			'event_tag'         => 'event_tag',
			'event_cat'         => 'event_cat',
			'type_tax'          => 'event_type',
			'cal_tax'           => 'event_calendar',

			'ical_endpoint'   => 'ics',
			'event_startdate' => 'event_startdate',
			'event_enddate'   => 'event_enddate',
			'event_timezone'  => 'event_timezone',
			'mysql_format'    => 'Y-m-d H:i:s',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
				'event_cpt' => array(
					'featured'       => _x( 'Poster Image', 'Event Module: Event CPT: Featured', GEDITORIAL_TEXTDOMAIN ),
					'meta_box_title' => _x( 'Date & Times', 'Event Module: Event CPT: Meta Box Title', GEDITORIAL_TEXTDOMAIN ),

					'event_dates_column_title' => _x( 'Dates', 'Event Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'event_times_column_title' => _x( 'Times', 'Event Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'event_tag' => array(
					'meta_box_title'      => _x( 'Event Types', 'Event Module: Meta Box Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Event Types', 'Event Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'cal_tax' => array(
					'meta_box_title'      => _x( 'Event Calendars', 'Event Module: Meta Box Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Event Calendars', 'Event Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'noops' => array(
				'event_cpt' => _nx_noop( 'Event',          'Events',           'Event Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'event_tag' => _nx_noop( 'Event Type',     'Event Types',      'Event Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'event_cat' => _nx_noop( 'Event Category', 'Event Categories', 'Event Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'cal_tax'   => _nx_noop( 'Event Calendar', 'Event Calendars',  'Event Module: Noop', GEDITORIAL_TEXTDOMAIN ),
			),
			'labels' => array(
				'type_tax' => array(
					'name' => _x( 'Calendar Types', 'Event Module: Calendar Type Tax Labels: Name', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'terms' => array(
				'event_tag' => array(
					'holiday' => _x( 'Holiday', 'Event Module: Event Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'birth'   => _x( 'Birth', 'Event Module: Event Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'death'   => _x( 'Death', 'Event Module: Event Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'start'   => _x( 'Start', 'Event Module: Event Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'end'     => _x( 'End', 'Event Module: Event Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				),
				'type_tax' => gEditorialHelper::getDefualtCalendars( TRUE ),
			),
		);
	}

	protected function get_global_supports()
	{
		return array(
			'event_cpt' => array(
				'title',
				'editor',
				'excerpt',
				'author',
				'thumbnail',
				// 'trackbacks',
				// 'custom-fields',
				'comments',
				'revisions',
				// 'page-attributes',
			),
		);
	}

	public function init()
	{
		do_action( 'geditorial_event_init', $this->module );

		$this->do_globals();

		$this->register_post_type( 'event_cpt', array( 'hierarchical' => TRUE, ), array( 'post_tag' ) );
		$this->register_taxonomy( 'event_cat', array( 'hierarchical' => TRUE, ), 'event_cpt' );
		$this->register_taxonomy( 'event_tag', array( 'hierarchical' => TRUE, ), 'event_cpt' );
		$this->register_taxonomy( 'cal_tax', array( 'hierarchical' => TRUE, ), 'event_cpt' );

		if ( $this->get_setting( 'startend_support', TRUE ) )
			$this->register_taxonomy( 'type_tax', array( 'show_ui' => FALSE, ), 'event_cpt' );

		add_rewrite_endpoint( $this->constant( 'ical_endpoint' ), EP_PAGES, 'ical' );

		if ( ! is_admin() ) {

			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
			add_action( 'template_redirect', array( $this, 'template_redirect' ) );
			add_filter( 'template_include', array( $this, 'template_include' ) );
		}
	}

	public function current_screen( $screen )
	{
		$startend = $this->get_setting( 'startend_support', TRUE );

		if ( $screen->post_type == $this->constant( 'event_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
				add_filter( 'get_default_comment_status', array( $this, 'get_default_comment_status' ), 10, 3 );

				add_action( 'save_post_'.$screen->post_type, array( $this, 'save_post_main_cpt' ), 20, 3 );

				if ( $startend ) {

					add_meta_box( 'geditorial-event',
						$this->get_meta_box_title( 'event_cpt' ),
						array( $this, 'do_meta_boxes' ),
						$screen,
						'side',
						'high'
					);
				}

				$this->remove_meta_box( $screen->post_type, $screen->post_type, 'parent' );
				$this->add_meta_box_choose_tax( 'event_tag', $screen->post_type );
				$this->add_meta_box_choose_tax( 'cal_tax', $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				add_filter( 'disable_months_dropdown', '__return_true', 12 );
				add_filter( 'manage_'.$screen->post_type.'_posts_columns', array( $this, 'manage_posts_columns' ) );
				add_filter( 'manage_edit-'.$screen->post_type.'_sortable_columns', array( $this, 'sortable_columns' ) );
				add_action( 'manage_'.$screen->post_type.'_posts_custom_column', array( $this, 'posts_custom_column' ), 10, 2 );

				if ( $startend ) {

					add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
					add_action( 'parse_query', array( $this, 'parse_query' ) );

					// add_action( 'load-edit.php', array( $this, 'load_edit_php' ) );
					add_filter( 'request', array( $this, 'load_edit_php_request' ) );
				}
			}
		}
	}

	public function register_settings( $page = NULL )
	{
		if ( ! $this->is_register_settings( $page ) )
			return;

		if ( isset( $_POST['install_def_event_tag'] ) )
			$this->insert_default_terms( 'event_tag' );

		if ( isset( $_POST['install_def_type_tax'] ) )
			$this->insert_default_terms( 'type_tax' );

		parent::register_settings( $page );

		$this->register_settings_button( 'install_def_event_tag', _x( 'Install Default Event Types', 'Event Module', GEDITORIAL_TEXTDOMAIN ) );

		if ( $this->get_setting( 'startend_support', TRUE ) )
			$this->register_settings_button( 'install_def_type_tax', _x( 'Install Default Calendar Types', 'Event Module', GEDITORIAL_TEXTDOMAIN ) );
	}

	public function gpeople_support( $post_types )
	{
		return array_merge( $post_types, array( $this->constant( 'event_cpt' ) ) );
	}

	public function tweaks_strings( $strings )
	{
		$new = array(
			'taxonomies' => array(
				$this->constant( 'event_tag' ) => array(
					'column'     => 'taxonomy-'.$this->constant( 'event_tag' ),
					'dashicon'   => 'tag',
					'title_attr' => $this->get_column_title( 'tweaks', 'event_tag' ),
				),
				$this->constant( 'event_cat' ) => array(
					'column'     => 'taxonomy-'.$this->constant( 'event_cat' ),
					'dashicon'   => 'category',
					'title_attr' => $this->get_column_title( 'tweaks', 'event_cat' ),
				),
				$this->constant( 'cal_tax' ) => array(
					'column'     => 'taxonomy-'.$this->constant( 'cal_tax' ),
					'dashicon'   => 'calendar',
					'title_attr' => $this->get_column_title( 'tweaks', 'cal_tax' ),
				),
				$this->constant( 'venue_tax' ) => array(
					'column'     => 'taxonomy-'.$this->constant( 'venue_tax' ),
					'dashicon'   => 'location',
					'title_attr' => $this->get_column_title( 'tweaks', 'venue_tax' ),
				),
			),
		);

		return self::recursiveParseArgs( $new, $strings );
	}

	public function dashboard_glance_items( $items )
	{
		$items[] = $this->dashboard_glance_post( 'event_cpt' );
		return $items;
	}

	public function save_post_main_cpt( $post_ID, $post, $update )
	{
		if ( ! $this->is_save_post( $post, 'event_cpt' ) )
			return $post_ID;

		// FIXME: save the data!

		return $post_ID;
	}

	public function restrict_manage_posts()
	{
		$this->do_restrict_manage_posts_taxes( array(
			'event_cat',
		), 'event_cpt' );
	}

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query->query_vars, array(
			'event_cat',
		), 'event_cpt' );
	}

	public function manage_posts_columns( $posts_columns )
	{
		$new_columns = array();

		foreach ( $posts_columns as $key => $value ) {

			if ( 'title' == $key ) {

				if ( $this->get_setting( 'startend_support', TRUE ) ) {
					$new_columns['event_dates'] = $this->get_column_title( 'event_dates', 'event_cpt' );
					$new_columns['event_times'] = $this->get_column_title( 'event_times', 'event_cpt' );
				}

				$new_columns[$key] = $value;

			} else if ( in_array( $key, array( 'author', 'date', 'comments' ) ) ) {
				continue; // he he!

			} else {
				$new_columns[$key] = $value;
			}
		}

		return $new_columns;
	}

	public function posts_custom_column( $column, $post_id )
	{
		// FIXME: adjust!
		if ( 'event_dates' == $column ) {

			$event_meta = get_post_custom( $post_id );

			// TODO: Localize
			// @$startdate = date( "F j, Y", $event_meta[$this->constant( 'event_startdate' )][0] );
			// @$enddate = date( "F j, Y", $event_meta[$this->constant( 'event_enddate' )][0] );
			// echo $startdate . '<br /><em>' . $enddate . '</em>';

			echo date_i18n( _x( 'F j, Y', 'Event Module', GEDITORIAL_TEXTDOMAIN ), strtotime( $event_meta[$this->constant( 'event_startdate' )][0] ) )
				.'<br /><em>'.date_i18n( _x( 'F j, Y', 'Event Module', GEDITORIAL_TEXTDOMAIN ), strtotime( $event_meta[$this->constant( 'event_enddate' )][0] ) );

		// FIXME: adjust!
		} else if ( 'event_times' == $column ) {

			$event_meta = get_post_custom( $post_id );

			// TODO: Localize
			$time_format = get_option( 'time_format', 'g:i a' );
			@$starttime = date( $time_format, strtotime( $event_meta[$this->constant( 'event_startdate' )][0] ) );
			@$endtime = date( $time_format,  strtotime( $event_meta[$this->constant( 'event_enddate' )][0] ) );
			echo $starttime . '<br />' .$endtime;
		}
	}

	public function sortable_columns( $columns )
	{
		$columns['event_dates'] = 'event_dates';
		return $columns;
	}

	// http://justintadlock.com/archives/2011/06/27/custom-columns-for-custom-post-types
	public function load_edit_php_request( $vars )
	{
		if ( isset( $vars['post_type'] ) && $this->constant( 'event_cpt' ) == $vars['post_type'] ) {
			if ( isset( $vars['orderby'] ) && 'event_dates' == $vars['orderby'] ) {
				$vars = array_merge(
					$vars,
					array(
						'meta_key' => $this->constant( 'event_startdate' ),
						'orderby' => 'meta_value_num'
					)
				);
			}
		}

		return $vars;
	}

	public function post_updated_messages( $messages )
	{
		$messages[$this->constant( 'event_cpt' )] = $this->get_post_updated_messages( 'event_cpt' );
		return $messages;
	}

	public function do_meta_boxes( $post, $box )
	{
		echo '<div class="geditorial-admin-wrap-metabox">';

			do_action( 'geditorial_event_meta_box', $post, $box );

			$this->render_box( $post );

		echo '</div>';
	}

	public function render_box( $post, $atts = array() )
	{
		$args = self::atts( array(
			'cal-type'   => self::req( 'cal-type', $this->get_setting( 'calendar_type', 'gregorian' ) ),
			// 'parent-id'  => self::req( 'parent-id', FALSE ),
			'date-start' => self::req( 'date-start' ),
			'date-end'   => self::req( 'date-end' ),
			'time-start' => self::req( 'time-start' ),
			'time-end'   => self::req( 'time-end' ),
		), $atts );

		$html = self::html( 'input', array(
			'type'        => 'text',
			'dir'         => 'ltr',
			'name'        => 'geditorial-event-date-start',
			'id'          => 'geditorial-event-date-start',
			'value'       => $args['date-start'],
			'title'       => _x( 'Date Start', 'Event Module: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Date Start', 'Event Module: Meta Box Input Placeholder', GEDITORIAL_TEXTDOMAIN ),
		) );

		$html .= self::html( 'input', array(
			'type'        => 'text',
			'dir'         => 'ltr',
			'name'        => 'geditorial-event-time-start',
			'id'          => 'geditorial-event-time-start',
			'value'       => $args['time-start'],
			'title'       => _x( 'Time Start', 'Event Module: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Time Start', 'Event Module: Meta Box Input Placeholder', GEDITORIAL_TEXTDOMAIN ),
		) );

		echo self::html( 'div', array(
			'class' => 'field-wrap field-wrap-inputtext-half ltr',
		), $html );

		$html = self::html( 'input', array(
			'type'        => 'text',
			'dir'         => 'ltr',
			'name'        => 'geditorial-event-date-end',
			'id'          => 'geditorial-event-date-end',
			'value'       => $args['date-end'],
			'title'       => _x( 'Date End', 'Event Module: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Date End', 'Event Module: Meta Box Input Placeholder', GEDITORIAL_TEXTDOMAIN ),
		) );

		$html .= self::html( 'input', array(
			'type'        => 'text',
			'dir'         => 'ltr',
			'name'        => 'geditorial-event-time-end',
			'id'          => 'geditorial-event-time-end',
			'value'       => $args['time-end'],
			'title'       => _x( 'Time End', 'Event Module: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Time End', 'Event Module: Meta Box Input Placeholder', GEDITORIAL_TEXTDOMAIN ),
		) );

		echo self::html( 'div', array(
			'class' => 'field-wrap field-wrap-inputtext-half ltr',
		), $html );

		if ( get_post_type_object( $this->constant( 'event_cpt' ) )->hierarchical )
			$this->field_post_parent( 'event_cpt', $post );

		if ( $this->get_setting( 'display_type', TRUE ) )
			$this->field_post_tax( 'type_tax', $post, FALSE, FALSE, '', $args['cal-type'] );
	}

	// https://github.com/devinsays/event-posts/blob/master/event-posts.php
	// to page back into event on the event archives
	// http://www.billerickson.net/customize-the-wordpress-query/
	// https://gist.github.com/1238281
	// http://www.billerickson.net/code/event-query/
	public function pre_get_posts( $query )
	{
		// http://codex.wordpress.org/Function_Reference/current_time
		// $current_time = current_time('mysql');
		// list( $today_year, $today_month, $today_day, $hour, $minute, $second ) = split( '([^0-9])', $current_time );
		// $current_timestamp = $today_year . $today_month . $today_day . $hour . $minute;

		// $current_time = current_time( 'timestamp' );
		// $current_time = current_time( 'mysql' );

		if ( $query->is_main_query()
			&& ! is_admin()
			&& is_post_type_archive( $this->constant( 'event_type' ) ) ) {

			$meta_query = array(
				array(
					'key'     => $this->constant( 'event_startdate' ),
					'value'   => current_time( 'mysql' ),
					'compare' => '>'
				)
			);

			$query->set( 'meta_query', $meta_query );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'meta_key', $this->constant( 'event_startdate' ) );
			$query->set( 'order', 'ASC' );
			// $query->set( 'posts_per_page', '2' );
		}
	}

	// https://gist.github.com/1289603
	// Use archive-event.php for all events and 'event-category' taxonomy archives.
	public function template_include( $template )
	{
		if ( is_tax( $this->constant( 'event_cat' ) )
			|| is_tax( $this->constant( 'cal_tax' ) )
			|| is_tax( $this->constant( 'venue_tax' ) ) )
				$template = get_query_template( 'archive-'.$this->constant( 'event_cpt' ) );

		return $template;
	}

	// https://make.wordpress.org/plugins/2012/06/07/rewrite-endpoints-api/
	// https://gist.github.com/joncave/2891111
	public function template_redirect()
	{
		global $wp_query;

		if ( ! isset( $wp_query->query_vars['ical'] ) || ! is_singular() )
			return;

		// output some JSON (normally you might include a template file here)
		// makeplugins_endpoints_do_json(); // FIXME
		exit;
	}
}
