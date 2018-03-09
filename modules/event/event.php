<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;

class Event extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'  => 'event',
			'title' => _x( 'Event', 'Modules: Event', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Integrated Events', 'Modules: Event', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'calendar-alt',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'extra_metadata',
					'title'       => _x( 'Start ~ End Support', 'Modules: Event: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Specifies events based on the actual date and time.', 'Modules: Event: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'display_type',
					'title'       => _x( 'Display Calendar Type', 'Modules: Event: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'For each event you can select the calendar type. Or else select default below.', 'Modules: Event: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '1',
				],
				'calendar_type',
				'calendar_list',
			],
			'_editlist' => [
				'admin_ordering',
			],
			'_supports' => [
				'comment_status',
				'thumbnail_support',
				$this->settings_supports_option( 'event_cpt', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'event_cpt'         => 'event',
			'event_cpt_archive' => 'events',
			'event_tag'         => 'event_tag',
			'event_cat'         => 'event_cat',
			'type_tax'          => 'event_type',
			'cal_tax'           => 'event_calendar',

			'endpoint_ical'     => 'ics',
			'metakey_startdate' => '_event_startdate',
			'metakey_enddate'   => '_event_enddate',
			'metakey_timezone'  => '_event_timezone',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'event_cat' => 'category',
				'event_tag' => 'tag',
				'cal_tax'   => 'calendar',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'event_cpt' => _nx_noop( 'Event', 'Events', 'Modules: Event: Noop', GEDITORIAL_TEXTDOMAIN ),
				'event_tag' => _nx_noop( 'Event Type', 'Event Types', 'Modules: Event: Noop', GEDITORIAL_TEXTDOMAIN ),
				'event_cat' => _nx_noop( 'Event Category', 'Event Categories', 'Modules: Event: Noop', GEDITORIAL_TEXTDOMAIN ),
				'cal_tax'   => _nx_noop( 'Event Calendar', 'Event Calendars', 'Modules: Event: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
			'labels' => [
				'type_tax' => [
					'name' => _x( 'Calendar Types', 'Modules: Event: Calendar Type Tax Labels: Name', GEDITORIAL_TEXTDOMAIN ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'event_cpt' => [
				'featured'                  => _x( 'Poster Image', 'Modules: Event: Event CPT: Featured', GEDITORIAL_TEXTDOMAIN ),
				'meta_box_title'            => _x( 'Date & Times', 'Modules: Event: Event CPT: Meta Box Title', GEDITORIAL_TEXTDOMAIN ),
				'event_starts_column_title' => _x( 'Starts', 'Modules: Event: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'event_ends_column_title'   => _x( 'Ends', 'Modules: Event: Column Title', GEDITORIAL_TEXTDOMAIN ),
			],
			'event_tag' => [
				'menu_name'           => _x( 'Types', 'Modules: Event: Event Types Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
				'meta_box_title'      => _x( 'Event Types', 'Modules: Event: Meta Box Title', GEDITORIAL_TEXTDOMAIN ),
				'tweaks_column_title' => _x( 'Event Types', 'Modules: Event: Column Title', GEDITORIAL_TEXTDOMAIN ),
			],
			'cal_tax' => [
				'menu_name'           => _x( 'Calendars', 'Modules: Event: Event Calendars Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
				'meta_box_title'      => _x( 'Event Calendars', 'Modules: Event: Meta Box Title', GEDITORIAL_TEXTDOMAIN ),
				'tweaks_column_title' => _x( 'Event Calendars', 'Modules: Event: Column Title', GEDITORIAL_TEXTDOMAIN ),
			],
		];

		$strings['terms'] = [
			'event_tag' => [
				'holiday' => _x( 'Holiday', 'Modules: Event: Event Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				'birth'   => _x( 'Birth', 'Modules: Event: Event Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				'death'   => _x( 'Death', 'Modules: Event: Event Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				'start'   => _x( 'Start', 'Modules: Event: Event Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				'end'     => _x( 'End', 'Modules: Event: Event Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
			],
		];

		return $strings;
	}

	public function before_settings( $module = FALSE )
	{
		if ( isset( $_POST['install_def_event_tag'] ) )
			$this->insert_default_terms( 'event_tag' );

		else if ( isset( $_POST['install_def_type_tax'] ) )
			$this->insert_default_terms( 'type_tax', array_intersect_key(
				Helper::getDefualtCalendars( TRUE ),
				array_flip( $this->get_setting( 'calendar_list', [] ) )
			) );
	}

	public function default_buttons( $module = FALSE )
	{
		parent::default_buttons( $module );

		$this->register_button( 'install_def_event_tag', _x( 'Install Default Event Types', 'Modules: Event: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

		if ( $this->get_setting( 'extra_metadata' ) )
			$this->register_button( 'install_def_type_tax', _x( 'Install Default Calendar Types', 'Modules: Event: Setting Button', GEDITORIAL_TEXTDOMAIN ) );
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'event_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'event_cat', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'event_cpt' );

		$this->register_taxonomy( 'event_tag', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'event_cpt' );

		$this->register_taxonomy( 'cal_tax', [
			'hierarchical' => TRUE,
		], 'event_cpt' );

		if ( $this->get_setting( 'extra_metadata' ) )
			$this->register_taxonomy( 'type_tax', [
				'show_ui' => FALSE,
			], 'event_cpt' );

		$this->register_posttype( 'event_cpt', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL, // default meta box
		] );

		add_rewrite_endpoint( $this->constant( 'endpoint_ical' ), EP_PAGES, 'ical' );

		if ( is_admin() )
			return;

		$this->action( 'pre_get_posts' );
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, 'event_cpt' ) )
			$this->_edit_screen( $_REQUEST['post_type'] );
	}

	public function current_screen( $screen )
	{
		$startend = $this->get_setting( 'extra_metadata' );

		if ( $screen->post_type == $this->constant( 'event_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'post_updated_messages' );
				$this->filter( 'get_default_comment_status', 3 );

				add_action( 'save_post_'.$screen->post_type, [ $this, 'save_post_main_cpt' ], 20, 3 );

				if ( $startend ) {

					$this->class_meta_box( $screen, 'main' );

					remove_meta_box( 'pageparentdiv', $screen, 'side' );
					add_meta_box( $this->classs( 'main' ),
						$this->get_meta_box_title( 'event_cpt' ),
						[ $this, 'do_meta_box_main' ],
						$screen,
						'side',
						'high'
					);
				}

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'bulk_post_updated_messages', 2 );

				if ( $startend ) {

					$this->action( 'restrict_manage_posts', 2, 12 );
					$this->action( 'parse_query' );
					$this->filter( 'request' );

				} else if ( $this->get_setting( 'admin_ordering', TRUE ) ) {

					add_action( 'pre_get_posts', [ $this, 'pre_get_posts_admin' ] );
				}

				$this->filter_true( 'disable_months_dropdown', 12 );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );

				$this->_edit_screen( $screen->post_type );
			}
		}
	}

	private function _edit_screen( $posttype )
	{
		if ( ! $this->get_setting( 'extra_metadata' ) )
			return;

		add_filter( 'manage_'.$posttype.'_posts_columns', [ $this, 'manage_posts_columns' ], 16 );
		add_action( 'manage_'.$posttype.'_posts_custom_column', [ $this, 'posts_custom_column' ], 10, 2 );
		add_filter( 'manage_edit-'.$posttype.'_sortable_columns', [ $this, 'sortable_columns' ] );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'event_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function meta_box_cb_event_tag( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $box['args'] );
		echo '</div>';
	}

	public function meta_box_cb_cal_tax( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $box['args'] );
		echo '</div>';
	}

	public function save_post_main_cpt( $post_ID, $post, $update )
	{
		if ( ! $this->is_save_post( $post, 'event_cpt' ) )
			return $post_ID;

		// FIXME: save the data!

		return $post_ID;
	}

	public function restrict_manage_posts( $posttype, $which )
	{
		$this->do_restrict_manage_posts_taxes( 'event_cat' );
	}

	// FIXME: merge filters
	public function pre_get_posts_admin( $wp_query )
	{
		if ( $this->constant( 'event_cpt' ) == $wp_query->get( 'post_type' ) ) {

			if ( $wp_query->is_admin ) {

				if ( ! isset( $_GET['orderby'] ) )
					$wp_query->set( 'orderby', 'date' );

				if ( ! isset( $_GET['order'] ) )
					$wp_query->set( 'order', 'DESC' );
			}
		}
	}

	public function parse_query( &$query )
	{
		$this->do_parse_query_taxes( $query, 'event_cat' );
	}

	public function manage_posts_columns( $columns )
	{
		return Arraay::insert( $columns, [
			'event_starts' => $this->get_column_title( 'event_starts', 'event_cpt' ),
			'event_ends'   => $this->get_column_title( 'event_ends', 'event_cpt' ),
		], 'title', 'before' );
	}

	public function posts_custom_column( $column, $post_id )
	{
		if ( 'event_starts' == $column ) {

			// $event_meta = get_post_custom( $post_id );
			// self::dump($event_meta);

			// TODO: Localize
			// @$startdate = date( "F j, Y", $event_meta[$this->constant( 'metakey_startdate' )][0] );
			// @$enddate = date( "F j, Y", $event_meta[$this->constant( 'metakey_enddate' )][0] );
			// echo $startdate . '<br /><em>' . $enddate . '</em>';

			// echo date_i18n( _x( 'F j, Y', 'Modules: Event', GEDITORIAL_TEXTDOMAIN ), strtotime( $event_meta[$this->constant( 'metakey_startdate' )][0] ) )
			// 	.'<br /><em>'.date_i18n( _x( 'F j, Y', 'Modules: Event', GEDITORIAL_TEXTDOMAIN ), strtotime( $event_meta[$this->constant( 'metakey_enddate' )][0] ) );

			echo '&mdash;';

		} else if ( 'event_ends' == $column ) {

			echo '&mdash;';

			// $event_meta = get_post_custom( $post_id );

			// TODO: Localize
			// $time_format = get_option( 'time_format', 'g:i a' );
			// @$starttime = date( $time_format, strtotime( $event_meta[$this->constant( 'metakey_startdate' )][0] ) );
			// @$endtime = date( $time_format, strtotime( $event_meta[$this->constant( 'metakey_enddate' )][0] ) );
			// echo $starttime . '<br />' .$endtime;
		}
	}

	public function sortable_columns( $columns )
	{
		return array_merge( $columns, [
			'event_starts' => [ 'event_starts', TRUE ],
			'event_ends'   => [ 'event_ends', TRUE ],
		] );
	}

	public function request( $query_vars )
	{
		if ( isset( $query_vars['orderby'] ) ) {

			if ( 'event_starts' == $query_vars['orderby'] )
				$query_vars = array_merge( $query_vars, [
					'meta_key' => $this->constant( 'metakey_startdate' ),
					'orderby'  => 'meta_value_num'
				] );

			else if ( 'event_ends' == $query_vars['orderby'] )
				$query_vars = array_merge( $query_vars, [
					'meta_key' => $this->constant( 'metakey_enddate' ),
					'orderby'  => 'meta_value_num'
				] );
		}

		return $query_vars;
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'event_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'event_cpt', $counts ) );
	}

	public function do_meta_box_main( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );

			$this->actions( 'meta_box', $post, $box );

			$this->render_box( $post );

		echo '</div>';
	}

	public function render_box( $post, $atts = [] )
	{
		$args = self::atts( [
			'cal-type'   => self::req( 'cal-type', $this->default_calendar() ),
			// 'parent-id'  => self::req( 'parent-id', FALSE ),
			'date-start' => self::req( 'date-start' ),
			'date-end'   => self::req( 'date-end' ),
			'time-start' => self::req( 'time-start' ),
			'time-end'   => self::req( 'time-end' ),
		], $atts );

		$html = HTML::tag( 'input', [
			'type'        => 'text',
			'dir'         => 'ltr',
			'name'        => 'geditorial-event-date-start',
			'id'          => 'geditorial-event-date-start',
			'value'       => $args['date-start'],
			'title'       => _x( 'Date Start', 'Modules: Event: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Date Start', 'Modules: Event: Meta Box Input Placeholder', GEDITORIAL_TEXTDOMAIN ),
		] );

		$html.= HTML::tag( 'input', [
			'type'        => 'text',
			'dir'         => 'ltr',
			'name'        => 'geditorial-event-time-start',
			'id'          => 'geditorial-event-time-start',
			'value'       => $args['time-start'],
			'title'       => _x( 'Time Start', 'Modules: Event: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Time Start', 'Modules: Event: Meta Box Input Placeholder', GEDITORIAL_TEXTDOMAIN ),
		] );

		echo HTML::wrap( $html, 'field-wrap field-wrap-inputtext-half ltr' );

		$html = HTML::tag( 'input', [
			'type'        => 'text',
			'dir'         => 'ltr',
			'name'        => 'geditorial-event-date-end',
			'id'          => 'geditorial-event-date-end',
			'value'       => $args['date-end'],
			'title'       => _x( 'Date End', 'Modules: Event: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Date End', 'Modules: Event: Meta Box Input Placeholder', GEDITORIAL_TEXTDOMAIN ),
		] );

		$html.= HTML::tag( 'input', [
			'type'        => 'text',
			'dir'         => 'ltr',
			'name'        => 'geditorial-event-time-end',
			'id'          => 'geditorial-event-time-end',
			'value'       => $args['time-end'],
			'title'       => _x( 'Time End', 'Modules: Event: Meta Box Input', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Time End', 'Modules: Event: Meta Box Input Placeholder', GEDITORIAL_TEXTDOMAIN ),
		] );

		echo HTML::wrap( $html, 'field-wrap field-wrap-inputtext-half ltr' );

		if ( $this->get_setting( 'display_type', TRUE ) )
			MetaBox::dropdownPostTaxonomy( $this->constant( 'type_tax' ), $post, FALSE, FALSE, '', $args['cal-type'] );

		MetaBox::fieldPostParent( $post );
	}

	// https://github.com/devinsays/event-posts/blob/master/event-posts.php
	// to page back into event on the event archives
	// http://www.billerickson.net/customize-the-wordpress-query/
	// https://gist.github.com/1238281
	// http://www.billerickson.net/code/event-query/
	public function pre_get_posts( &$wp_query )
	{
		// http://codex.wordpress.org/Function_Reference/current_time
		// $current_time = current_time('mysql');
		// list( $today_year, $today_month, $today_day, $hour, $minute, $second ) = split( '([^0-9])', $current_time );
		// $current_timestamp = $today_year . $today_month . $today_day . $hour . $minute;

		// $current_time = current_time( 'timestamp' );
		// $current_time = current_time( 'mysql' );

		if ( $wp_query->is_main_query()
			&& ! is_admin()
			&& is_post_type_archive( $this->constant( 'event_type' ) ) ) {

			$meta_query = [ [
				'key'     => $this->constant( 'metakey_startdate' ),
				'value'   => current_time( 'mysql' ),
				'compare' => '>'
			] ];

			$wp_query->set( 'meta_query', $meta_query );
			$wp_query->set( 'orderby', 'meta_value_num' );
			$wp_query->set( 'meta_key', $this->constant( 'metakey_startdate' ) );
			$wp_query->set( 'order', 'ASC' );
			// $wp_query->set( 'posts_per_page', '2' );
		}
	}

	// https://gist.github.com/1289603
	// Use archive-event.php for all events and 'event-category' taxonomy archives.
	public function template_include( $template )
	{
		if ( is_tax( $this->constant( 'event_cat' ) )
			|| is_tax( $this->constant( 'cal_tax' ) ) )
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
