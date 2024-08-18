<?php namespace geminorum\gEditorial\Modules\Event;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Event extends gEditorial\Module
{
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreRestrictPosts;
	use Internals\Deprecated;
	use Internals\PostTypeFields;

	public static function module()
	{
		return [
			'name'   => 'event',
			'title'  => _x( 'Event', 'Modules: Event', 'geditorial-admin' ),
			'desc'   => _x( 'Integrated Events', 'Modules: Event', 'geditorial-admin' ),
			'icon'   => 'calendar-alt',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_fields' => [
				'extra_metadata' => _x( 'Specifies events based on the actual date and time.', 'Setting Description', 'geditorial-event' ),
			],
			'fields_option' => _x( 'Metadata Fields', 'Settings', 'geditorial-event' ),
			'_general' => [
				[
					'field'       => 'display_type',
					'title'       => _x( 'Display Calendar Type', 'Setting Title', 'geditorial-event' ),
					'description' => _x( 'For each event you can select the calendar type. Or else select default below.', 'Setting Description', 'geditorial-event' ),
					'default'     => '1',
				],
				'calendar_type',
				'calendar_list',
			],
			'_editlist' => [
				'admin_ordering',
			],
			'_supports' => [
				'assign_default_term',
				'comment_status',
				'widget_support',
				'thumbnail_support',
				$this->settings_supports_option( 'primary_posttype', TRUE ),
			],
		];
	}

	public function get_global_fields()
	{
		return [ 'meta' => [
			$this->constant( 'primary_posttype' ) => [
				'event_start' => [
					'title'       => _x( 'Event Start', 'Fields', 'geditorial-event' ),
					'description' => _x( 'Event Start', 'Fields', 'geditorial-event' ),
					'icon'        => 'calendar',
					'type'        => 'datetime',
				],
				'event_end' => [
					'title'       => _x( 'Event End', 'Fields', 'geditorial-event' ),
					'description' => _x( 'Event End', 'Fields', 'geditorial-event' ),
					'icon'        => 'calendar',
					'type'        => 'datetime',
				],
				'event_allday' => [
					'title'       => _x( 'Event All-Day', 'Fields', 'geditorial-event' ),
					'description' => _x( 'All-day event', 'Fields', 'geditorial-event' ),
					'icon'        => 'calendar-alt',
					'type'        => 'checkbox',
				],
				'event_repeat' => [
					'title'       => _x( 'Event Repeat', 'Fields', 'geditorial-event' ),
					'description' => _x( 'Event Repeat', 'Fields', 'geditorial-event' ),
					'icon'        => 'update',
					'type'        => 'select',
					'values'      => [
						'0'    => _x( 'Never', 'Fields', 'geditorial-event' ),
						'10'   => _x( 'Weekly', 'Fields', 'geditorial-event' ),
						'100'  => _x( 'Monthly', 'Fields', 'geditorial-event' ),
						'1000' => _x( 'Yearly', 'Fields', 'geditorial-event' ),
					],
				],
				'event_expire' => [
					'title'       => _x( 'Event Expire', 'Fields', 'geditorial-event' ),
					'description' => _x( 'Event Expire', 'Fields', 'geditorial-event' ),
					'icon'        => 'thumbs-down',
					'type'        => 'datetime',
				],
			],
		] ];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype'       => 'event',
			'primary_taxonomy'       => 'event_category',
			'type_taxonomy'          => 'event_type',
			'calendar_taxonomy'      => 'event_calendar',
			'calendar_type_taxonomy' => 'event_calendar_type', // FIXME: use `Almanac` Module

			'metakey_event_start'  => '_event_datetime_start',
			'metakey_event_end'    => '_event_datetime_end',
			'metakey_event_allday' => '_event_allday',
			'metakey_event_repeat' => '_event_repeat',
			'metakey_event_expire' => '_event_expire',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'primary_taxonomy'  => 'category',
				'type_taxonomy'     => 'tag',
				'calendar_taxonomy' => 'calendar',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'primary_posttype'  => _n_noop( 'Event', 'Events', 'geditorial-event' ),
				'primary_taxonomy'  => _n_noop( 'Event Category', 'Event Categories', 'geditorial-event' ),
				'type_taxonomy'     => _n_noop( 'Event Type', 'Event Types', 'geditorial-event' ),
				'calendar_taxonomy' => _n_noop( 'Event Calendar', 'Event Calendars', 'geditorial-event' ),
			],
			'labels' => [
				'primary_taxonomy' => [
					'menu_name'      => _x( 'Categories', 'Menu Title', 'geditorial-event' ),
					'featured_image' => _x( 'Poster Image', 'Label: Featured Image', 'geditorial-event' ),
				],
				'type_taxonomy' => [
					'menu_name' => _x( 'Types', 'Menu Title', 'geditorial-event' ),
				],
				'calendar_taxonomy' => [
					'menu_name' => _x( 'Calendars', 'Menu Title', 'geditorial-event' ),
				],
				'calendar_type_taxonomy' => [
					'name' => _x( 'Calendar Types', 'Taxonomy Label', 'geditorial-event' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'primary_posttype' => [
				'metabox_title' => _x( 'Date & Times', 'MetaBox Title', 'geditorial-event' ),
			],
		];

		$strings['misc'] = [
			'primary_posttype' => [
				'event_starts_column_title' => _x( 'Starts', 'Column Title', 'geditorial-event' ),
				'event_ends_column_title'   => _x( 'Ends', 'Column Title', 'geditorial-event' ),
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'type_taxonomy' => [
				'holiday' => _x( 'Holiday', 'Default Term', 'geditorial-event' ),
				'birth'   => _x( 'Birth', 'Default Term', 'geditorial-event' ),
				'death'   => _x( 'Death', 'Default Term', 'geditorial-event' ),
				'start'   => _x( 'Start', 'Default Term', 'geditorial-event' ),
				'end'     => _x( 'End', 'Default Term', 'geditorial-event' ),
			],
		];
	}

	// needed for fields options
	public function posttypes( $posttypes = NULL )
	{
		return [ $this->constant( 'primary_posttype' ) ];
	}

	// FIXME: WTF: `show_ui` is false so no taxonomy tabs support!
	public function before_settings( $module = FALSE )
	{
		if ( isset( $_POST['install_def_calendar_type_taxonomy'] ) )
			$this->insert_default_terms( 'calendar_type_taxonomy', array_intersect_key(
				Services\Calendars::getDefualts( TRUE ),
				array_flip( $this->get_setting( 'calendar_list', [] ) )
			) );
	}

	public function default_buttons( $module = FALSE )
	{
		parent::default_buttons( $module );

		if ( $this->get_setting( 'extra_metadata' ) )
			$this->register_button( 'install_def_calendar_type_taxonomy', _x( 'Install Default Calendar Types', 'Button', 'geditorial-event' ) );
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'primary_posttype' );
	}

	public function widgets_init()
	{
		return; // FIXME

		// register_widget( __NAMESPACE__.'\\Widgets\\Poster' ); // FIXME: drop this
		register_widget( __NAMESPACE__.'\\Widgets\\Upcoming' );
	}

	public function init()
	{
		parent::init();

		$metadata = $this->get_setting( 'extra_metadata' );

		$this->register_taxonomy( 'primary_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
		], 'primary_posttype' );

		$this->register_taxonomy( 'type_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'primary_posttype' );

		$this->register_taxonomy( 'calendar_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => '__checklist_terms_callback',
		], 'primary_posttype' );

		if ( $metadata )
			$this->register_taxonomy( 'calendar_type_taxonomy', [
				'show_ui' => FALSE,
			], 'primary_posttype' );

		$this->register_posttype( 'primary_posttype', [
			'hierarchical'     => TRUE,
			WordPress\PostType::PRIMARY_TAXONOMY_PROP => $this->constant( 'primary_taxonomy' ),
		] );

		if ( $metadata )
			$this->add_posttype_fields( $this->constant( 'primary_posttype' ), NULL, TRUE, $this->module->name );

		if ( is_admin() )
			return;

		$this->action( 'pre_get_posts', 1, 20, 'front' );
	}

	public function setup_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'primary_posttype' ) )
			$this->_edit_screen( $posttype );
	}

	public function current_screen( $screen )
	{
		$metadata = $this->get_setting( 'extra_metadata' );

		if ( $screen->post_type == $this->constant( 'primary_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'get_default_comment_status', 3 );

				if ( $metadata ) {

					$this->class_metabox( $screen, 'mainbox' );
					add_meta_box( $this->classs( 'mainbox' ),
						$this->get_meta_box_title( 'primary_posttype' ),
						[ $this, 'render_mainbox_metabox' ],
						$screen,
						'side',
						'high'
					);

					// $this->_hook_store_metabox( $screen->post_type ); // FIXME: not implemented yet!
					add_action( $this->hook( 'render_metabox' ), [ $this, 'render_metabox' ], 10, 4 );
				}

				$this->_hook_post_updated_messages( 'primary_posttype' );

			} else if ( 'edit' == $screen->base ) {

				if ( $metadata ) {

					$this->corerestrictposts__hook_screen_taxonomies( 'primary_taxonomy' );

					$this->filter( 'request' );

				} else {

					$this->coreadmin__hook_admin_ordering( $screen->post_type, 'date' );
				}

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->_edit_screen( $screen->post_type );
				$this->_hook_bulk_post_updated_messages( 'primary_posttype' );
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
		if ( $glance = $this->dashboard_glance_post( 'primary_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	public function manage_posts_columns( $columns )
	{
		return Core\Arraay::insert( $columns, [
			'event_starts' => $this->get_column_title( 'event_starts', 'primary_posttype' ),
			'event_ends'   => $this->get_column_title( 'event_ends', 'primary_posttype' ),
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

			// echo date_i18n( _x( 'F j, Y', 'Date Format', 'geditorial-event' ), strtotime( $event_meta[$this->constant( 'metakey_startdate' )][0] ) )
			// 	.'<br /><em>'.date_i18n( _x( 'F j, Y', 'Date Format', 'geditorial-event' ), strtotime( $event_meta[$this->constant( 'metakey_enddate' )][0] ) );

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

	// TODO: migrate to meta: `datestart`/`dateend`
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

	public function render_mainbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$fields = $this->get_posttype_fields( $post->post_type );

		echo $this->wrap_open( '-admin-metabox' );
			$this->actions( 'render_metabox', $post, $box, $fields, 'mainbox' );

			// old way metas
			// $this->render_box( $post ); // FIXME: add to module actions

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

		$html = Core\HTML::tag( 'input', [
			'type'        => 'text',
			'dir'         => 'ltr',
			'name'        => 'geditorial-event-date-start',
			'id'          => 'geditorial-event-date-start',
			'value'       => $args['date-start'],
			'title'       => _x( 'Date Start', 'Meta Box Input', 'geditorial-event' ),
			'placeholder' => _x( 'Date Start', 'Meta Box Input Placeholder', 'geditorial-event' ),
		] );

		$html.= Core\HTML::tag( 'input', [
			'type'        => 'text',
			'dir'         => 'ltr',
			'name'        => 'geditorial-event-time-start',
			'id'          => 'geditorial-event-time-start',
			'value'       => $args['time-start'],
			'title'       => _x( 'Time Start', 'Meta Box Input', 'geditorial-event' ),
			'placeholder' => _x( 'Time Start', 'Meta Box Input Placeholder', 'geditorial-event' ),
		] );

		echo Core\HTML::wrap( $html, 'field-wrap -inputtext-half ltr' );

		$html = Core\HTML::tag( 'input', [
			'type'        => 'text',
			'dir'         => 'ltr',
			'name'        => 'geditorial-event-date-end',
			'id'          => 'geditorial-event-date-end',
			'value'       => $args['date-end'],
			'title'       => _x( 'Date End', 'Meta Box Input', 'geditorial-event' ),
			'placeholder' => _x( 'Date End', 'Meta Box Input Placeholder', 'geditorial-event' ),
		] );

		$html.= Core\HTML::tag( 'input', [
			'type'        => 'text',
			'dir'         => 'ltr',
			'name'        => 'geditorial-event-time-end',
			'id'          => 'geditorial-event-time-end',
			'value'       => $args['time-end'],
			'title'       => _x( 'Time End', 'Meta Box Input', 'geditorial-event' ),
			'placeholder' => _x( 'Time End', 'Meta Box Input Placeholder', 'geditorial-event' ),
		] );

		echo Core\HTML::wrap( $html, 'field-wrap -inputtext-half ltr' );

		if ( $this->get_setting( 'display_type', TRUE ) )
			MetaBox::dropdownPostTaxonomy( $this->constant( 'calendar_type_taxonomy' ), $post, FALSE, FALSE, '', $args['cal-type'] );
	}

	// https://github.com/devinsays/event-posts/blob/master/event-posts.php
	// to page back into event on the event archives
	// http://www.billerickson.net/customize-the-wordpress-query/
	// https://gist.github.com/1238281
	// http://www.billerickson.net/code/event-query/
	public function pre_get_posts_front( &$wp_query )
	{
		// http://codex.wordpress.org/Function_Reference/current_time
		// $current_time = current_time('mysql');
		// list( $today_year, $today_month, $today_day, $hour, $minute, $second ) = split( '([^0-9])', $current_time );
		// $current_timestamp = $today_year . $today_month . $today_day . $hour . $minute;

		// $current_time = current_time( 'timestamp' );
		// $current_time = current_time( 'mysql' );

		if ( $wp_query->is_main_query()
			&& ! is_admin()
			&& is_post_type_archive( $this->constant( 'type_taxonomy' ) ) ) {

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
		if ( is_tax( $this->constant( 'primary_taxonomy' ) )
			|| is_tax( $this->constant( 'calendar_taxonomy' ) ) )
				$template = get_query_template( 'archive-'.$this->constant( 'primary_posttype' ) );

		return $template;
	}
}
