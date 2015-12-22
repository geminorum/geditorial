<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialEvent extends gEditorialModuleCore
{

	public static function module()
	{
		// return array(); // FIXME

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
					'field'       => 'display_type',
					'title'       => _x( 'Display Calendar Type', 'Event Module', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'For each event you can select the calendar type. Or else select default below.', 'Event Module', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '1',
				),
				array(
					'field'       => 'default_type',
					'title'       => _x( 'Default Calendar Type', 'Event Module', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'For All events the default calendar type. User can change it, if displayed.', 'Event Module', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'select',
					'default'     => 'gregorian',
					'values'      => array(
						'gregorian' => _x( 'Gregorian', 'Event Module: Calendar Type Select Option', GEDITORIAL_TEXTDOMAIN ),
						'persian'   => _x( 'Persian', 'Event Module: Calendar Type Select Option', GEDITORIAL_TEXTDOMAIN ),
						'islamic'   => _x( 'Islamic', 'Event Module: Calendar Type Select Option', GEDITORIAL_TEXTDOMAIN ),
					),
				),
			),
		);
	}

	protected function get_global_constants()
	{
		return array(
			'event_cpt'         => 'event',
			'event_cpt_archive' => 'events',
			'event_cat'         => 'event_cat',
			'type_tax'          => 'event_type',
			'cal_tax'           => 'event_calendar',
			'venue_tax'         => 'event_venue',

			'ical_endpoint' => 'ics',

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
					'meta_box_title'           => _x( 'Date & Times', 'Event Module: Meta Box Title', GEDITORIAL_TEXTDOMAIN ),
					'event_dates_column_title' => _x( 'Dates', 'Event Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'event_times_column_title' => _x( 'Times', 'Event Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'cal_tax' => array(
					'meta_box_title' => _x( 'Calendars', 'Event Module: Meta Box Title', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'labels' => array(
				'event_cpt' => array(
					'name'                  => _x( 'Events', 'Event Module: Event CPT Labels: Name', GEDITORIAL_TEXTDOMAIN ),
					'menu_name'             => _x( 'Events', 'Event Module: Event CPT Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
					'singular_name'         => _x( 'Event', 'Event Module: Event CPT Labels: Singular Name', GEDITORIAL_TEXTDOMAIN ),
					'description'           => _x( 'Event Post Type', 'Event Module: Event CPT Labels: Description', GEDITORIAL_TEXTDOMAIN ),
					'add_new'               => _x( 'Add New', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'add_new_item'          => _x( 'Add New Event', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'edit_item'             => _x( 'Edit Event', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'new_item'              => _x( 'New Event', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'view_item'             => _x( 'View Event', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'search_items'          => _x( 'Search Events', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'not_found'             => _x( 'No events found.', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'not_found_in_trash'    => _x( 'No events found in Trash.', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'parent_item_colon'     => _x( 'Parent Event:', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'all_items'             => _x( 'All Events', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'archives'              => _x( 'Event Archives', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'insert_into_item'      => _x( 'Insert into event', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'uploaded_to_this_item' => _x( 'Uploaded to this event', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'featured_image'        => _x( 'Poster Image', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'set_featured_image'    => _x( 'Set poster image', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'remove_featured_image' => _x( 'Remove poster image', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'use_featured_image'    => _x( 'Use as poster image', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'filter_items_list'     => _x( 'Filter events list', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'items_list_navigation' => _x( 'Events list navigation', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'items_list'            => _x( 'Events list', 'Event Module: Event CPT Labels', GEDITORIAL_TEXTDOMAIN ),
				),
				'event_cat' => array(
                    'name'                  => _x( 'Event Categories', 'Event Module: Event Category Tax Labels: Name', GEDITORIAL_TEXTDOMAIN ),
                    'menu_name'             => _x( 'Event Categories', 'Event Module: Event Category Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
                    'singular_name'         => _x( 'Event Category', 'Event Module: Event Category Tax Labels: Singular Name', GEDITORIAL_TEXTDOMAIN ),
                    'search_items'          => _x( 'Search Event Categories', 'Event Module: Event Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'all_items'             => _x( 'All Event Categories', 'Event Module: Event Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item'           => _x( 'Parent Event Category', 'Event Module: Event Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item_colon'     => _x( 'Parent Event Category:', 'Event Module: Event Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'edit_item'             => _x( 'Edit Event Category', 'Event Module: Event Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'view_item'             => _x( 'View Event Category', 'Event Module: Event Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'update_item'           => _x( 'Update Event Category', 'Event Module: Event Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'add_new_item'          => _x( 'Add New Event Category', 'Event Module: Event Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'new_item_name'         => _x( 'New Event Category Name', 'Event Module: Event Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'not_found'             => _x( 'No event categories found.', 'Event Module: Event Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'no_terms'              => _x( 'No event categories', 'Event Module: Event Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list_navigation' => _x( 'Event Categories list navigation', 'Event Module: Event Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list'            => _x( 'Event Categories list', 'Event Module: Event Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
				),
				'type_tax' => array(
					'name'      => _x( 'Calendar Types', 'Event Module: Calendar Type Tax Labels: Name', GEDITORIAL_TEXTDOMAIN ),
					'menu_name' => _x( 'Calendar Types', 'Event Module: Calendar Type Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
				),
				'cal_tax' => array(
                    'name'                  => _x( 'Calendars', 'Event Module: Calendar Tax Labels: Name', GEDITORIAL_TEXTDOMAIN ),
                    'menu_name'             => _x( 'Calendars', 'Event Module: Calendar Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
                    'singular_name'         => _x( 'Calendar', 'Event Module: Calendar Tax Labels: Singular Name', GEDITORIAL_TEXTDOMAIN ),
                    'search_items'          => _x( 'Search Calendars', 'Event Module: Calendar Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'all_items'             => _x( 'All Calendars', 'Event Module: Calendar Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item'           => _x( 'Parent Calendar', 'Event Module: Calendar Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item_colon'     => _x( 'Parent Calendar:', 'Event Module: Calendar Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'edit_item'             => _x( 'Edit Calendar', 'Event Module: Calendar Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'view_item'             => _x( 'View Calendar', 'Event Module: Calendar Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'update_item'           => _x( 'Update Calendar', 'Event Module: Calendar Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'add_new_item'          => _x( 'Add New Calendar', 'Event Module: Calendar Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'new_item_name'         => _x( 'New Calendar Name', 'Event Module: Calendar Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'not_found'             => _x( 'No calendars found.', 'Event Module: Calendar Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'no_terms'              => _x( 'No calendars', 'Event Module: Calendar Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list_navigation' => _x( 'Calendars list navigation', 'Event Module: Calendar Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list'            => _x( 'Calendars list', 'Event Module: Calendar Tax Labels', GEDITORIAL_TEXTDOMAIN ),
				),
				'venue_tax' => array(
                    'name'                  => _x( 'Venues', 'Event Module: Venue Tax Labels: Name', GEDITORIAL_TEXTDOMAIN ),
                    'menu_name'             => _x( 'Venues', 'Event Module: Venue Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
                    'singular_name'         => _x( 'Venue', 'Event Module: Venue Tax Labels: Singular Name', GEDITORIAL_TEXTDOMAIN ),
                    'search_items'          => _x( 'Search Venues', 'Event Module: Venue Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'all_items'             => _x( 'All Venues', 'Event Module: Venue Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item'           => _x( 'Parent Venue', 'Event Module: Venue Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item_colon'     => _x( 'Parent Venue:', 'Event Module: Venue Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'edit_item'             => _x( 'Edit Venue', 'Event Module: Venue Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'view_item'             => _x( 'View Venue', 'Event Module: Venue Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'update_item'           => _x( 'Update Venue', 'Event Module: Venue Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'add_new_item'          => _x( 'Add New Venue', 'Event Module: Venue Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'new_item_name'         => _x( 'New Venue Name', 'Event Module: Venue Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'not_found'             => _x( 'No venues found.', 'Event Module: Venue Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'no_terms'              => _x( 'No venues', 'Event Module: Venue Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list_navigation' => _x( 'Venues list navigation', 'Event Module: Venue Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list'            => _x( 'Venues list', 'Event Module: Venue Tax Labels', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'terms' => array(
				'type_tax' => array(
					'gregorian' => _x( 'Gregorian Calendar', 'Calendar Type Term', GEDITORIAL_TEXTDOMAIN ),
					'persian'   => _x( 'Persian (Jalali) Calendar', 'Calendar Type Term', GEDITORIAL_TEXTDOMAIN ),
					'islamic'   => _x( 'Islamic (Hijri, Arabic) calendar', 'Calendar Type Term', GEDITORIAL_TEXTDOMAIN ),
				),
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
				'trackbacks',
				'custom-fields',
				'comments',
				'revisions',
				'page-attributes',
			),
		);
	}

	public function setup( $partials = array() )
	{
		parent::setup( array(
			'templates',
			'helper',
		) );
	}

	public function init()
	{
		do_action( 'geditorial_event_init', $this->module );

		$this->do_globals();

		$this->register_post_type( 'event_cpt', array( 'hierarchical' => TRUE, ), array( 'post_tag' ) );
		$this->register_taxonomy( 'event_cat', array( 'hierarchical' => TRUE, ), 'event_cpt' );
		$this->register_taxonomy( 'cal_tax', array( 'hierarchical' => TRUE, ), 'event_cpt' );
		$this->register_taxonomy( 'venue_tax', array( 'hierarchical' => TRUE, ), 'event_cpt' );
		$this->register_taxonomy( 'type_tax', array( 'show_ui' => FALSE, ), 'event_cpt' );

		if ( is_admin() ) {

		} else {
			add_rewrite_endpoint( $this->constant( 'ical_endpoint' ), EP_PAGES, 'ical' );
			add_action( 'template_redirect', array( $this, 'template_redirect' ) );

			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
			add_filter( 'template_include', array( $this, 'template_include' ) );
		}
	}

	public function admin_init()
	{
		add_action( 'save_post_'.$this->constant( 'event_cpt' ), array( $this, 'save_post_main_cpt' ), 20, 3 );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 20, 2 );
		add_filter( 'manage_'.$this->constant( 'event_cpt' ).'_posts_columns', array( $this, 'manage_posts_columns' ) );
		add_action( 'manage_'.$this->constant( 'event_cpt' ).'_posts_custom_column', array( $this, 'posts_custom_column' ), 10, 2 );
		add_filter( 'manage_edit-'.$this->constant( 'event_cpt' ).'_sortable_columns', array( $this, 'sortable_columns' ) );
		add_filter( 'disable_months_dropdown', array( $this, 'disable_months_dropdown' ), 8, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
		add_action( 'parse_query', array( $this, 'parse_query' ) );
		add_action( 'load-edit.php', array( $this, 'load_edit_php' ) );
	}

	public function register_settings( $page = NULL )
	{
		if ( ! $this->is_register_settings( $page ) )
			return;

		if ( isset( $_POST['install_def_type_tax'] ) )
			$this->insert_default_terms( 'type_tax' );

		parent::register_settings( $page );
		$this->register_settings_button( 'install_def_type_tax', _x( 'Install Default Calendar Types', 'Event Module', GEDITORIAL_TEXTDOMAIN ) );
	}

	public function tweaks_strings( $strings )
	{
		$new = array(
			'taxonomies' => array(
				$this->constant( 'event_cat' ) => array(
					'column'     => 'taxonomy-'.$this->constant( 'event_cat' ),
					'dashicon'   => 'category',
					'title_attr' => $this->get_string( 'name', 'event_cat', 'labels' ),
				),
				$this->constant( 'cal_tax' ) => array(
					'column'     => 'taxonomy-'.$this->constant( 'cal_tax' ),
					'dashicon'   => 'calendar',
					'title_attr' => $this->get_string( 'name', 'cal_tax', 'labels' ),
				),
				$this->constant( 'venue_tax' ) => array(
					'column'     => 'taxonomy-'.$this->constant( 'venue_tax' ),
					'dashicon'   => 'location',
					'title_attr' => $this->get_string( 'name', 'venue_tax', 'labels' ),
				),
			),
		);

		return self::parse_args_r( $new, $strings );
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( $post_type == $this->constant( 'event_cpt' ) ) {

			$this->remove_meta_box( 'event_cpt', $post_type, 'parent' );
			add_meta_box( 'geditorial-event-dates',
				$this->get_meta_box_title( 'event_cpt' ),
				array( $this, 'do_meta_box_event' ),
				$post_type,
				'side',
				'high'
			);

			$this->add_meta_box_choose_tax( 'cal_tax', $post_type );
		}
	}

	public function do_meta_box_event( $post )
	{
		echo '<div class="geditorial-admin-wrap-metabox">';

		do_action( 'geditorial_event_meta_box', $post );

		$html = self::html( 'input', array(
			'type'        => 'text',
			'dir'         => 'ltr',
			'name'        => 'geditorial-event-date-start',
			'id'          => 'geditorial-event-date-start',
			'value'       => '',
			'title'       => _x( 'Date Start', 'Event Module: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Date Start', 'Event Module: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
		) );

		$html .= self::html( 'input', array(
			'type'        => 'text',
			'dir'         => 'ltr',
			'name'        => 'geditorial-event-time-start',
			'id'          => 'geditorial-event-time-start',
			'value'       => '',
			'title'       => _x( 'Time Start', 'Event Module: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Time Start', 'Event Module: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
		) );

		echo self::html( 'div', array(
			'class' => 'field-wrap field-wrap-inputtext-half ltr',
		), $html );

		$html = self::html( 'input', array(
			'type'        => 'text',
			'dir'         => 'ltr',
			'name'        => 'geditorial-event-date-end',
			'id'          => 'geditorial-event-date-end',
			'value'       => '',
			'title'       => _x( 'Date End', 'Event Module: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Date End', 'Event Module: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
		) );

		$html .= self::html( 'input', array(
			'type'        => 'text',
			'dir'         => 'ltr',
			'name'        => 'geditorial-event-time-end',
			'id'          => 'geditorial-event-time-end',
			'value'       => '',
			'title'       => _x( 'Time End', 'Event Module: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Time End', 'Event Module: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
		) );

		echo self::html( 'div', array(
			'class' => 'field-wrap field-wrap-inputtext-half ltr',
		), $html );

		if ( get_post_type_object( $this->constant( 'event_cpt' ) )->hierarchical )
			$this->field_post_parent( 'event_cpt', $post );

		if ( $this->get_setting( 'display_type', TRUE ) )
			$this->field_post_tax( 'type_tax', $post, FALSE, FALSE, '', $this->get_setting( 'default_type', 'gregorian' ) );

		echo '</div>';
	}

	public function save_post_main_cpt( $post_ID, $post, $update )
	{
		if ( ! $this->is_save_post( $post, 'event_cpt' ) )
			return $post_ID;

		// FIXME: save the data!

		return $post_ID;
	}

	public function disable_months_dropdown( $false, $post_type )
	{
		if ( $this->constant( 'event_cpt' ) == $post_type )
			return TRUE;

		return $false;
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
				$new_columns['event_dates'] = $this->get_column_title( 'event_dates', 'event_cpt' );
				$new_columns['event_times'] = $this->get_column_title( 'event_times', 'event_cpt' );
				$new_columns[$key]    = $value;

			} else if ( in_array( $key, array( 'author', 'date', 'comments' ) ) ) {
				continue; // he he!

			} else {
				$new_columns[$key] = $value;
			}
		}

		return $new_columns;
	}

	// FIXME: adjust!
	public function posts_custom_column( $column, $post_id )
	{
		$event_meta = get_post_custom( $post_id );

		if ( 'event_dates' == $column ) {
			// TODO: Localize
			//@$startdate = date( "F j, Y", $event_meta[$this->constant( 'event_startdate' )][0] );
			//@$enddate = date( "F j, Y", $event_meta[$this->constant( 'event_enddate' )][0] );
			//echo $startdate . '<br /><em>' . $enddate . '</em>';
			echo date_i18n( _x( 'F j, Y', 'Event Module', GEDITORIAL_TEXTDOMAIN ), strtotime( $event_meta[$this->constant( 'event_startdate' )][0] ) )
				.'<br /><em>'.date_i18n( _x( 'F j, Y', 'Event Module', GEDITORIAL_TEXTDOMAIN ), strtotime( $event_meta[$this->constant( 'event_enddate' )][0] ) );

		} else if ( 'event_times' == $column ) {
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

	public function load_edit_php()
	{
		add_filter( 'request', array( $this, 'load_edit_php_request' ) );
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
		if ( $this->is_current_posttype( 'event_cpt' ) )
			$messages[$this->constant( 'event_cpt' )] = $this->get_post_updated_messages( 'event_cpt' );

		return $messages;
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
		makeplugins_endpoints_do_json();
		exit;
	}
}
