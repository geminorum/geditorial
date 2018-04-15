<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;

class Calendar extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;

	private $posttype_icons    = [];
	private $posttype_addnew   = NULL;
	private $posttype_statuses = NULL;

	public static function module()
	{
		return [
			'name'     => 'calendar',
			'title'    => _x( 'Calendar', 'Modules: Calendar', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Viewing Upcoming Content in a Customizable Calendar', 'Modules: Calendar', GEDITORIAL_TEXTDOMAIN ),
			'icon'     => 'calendar-alt',
			'frontend' => FALSE,
			'disabled' => defined( 'GPERSIANDATE_VERSION' ) ? FALSE : _x( 'Needs gPersianDate', 'Modules: Calendar', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	protected function get_global_settings()
	{
		return [
			'_dashboard' => [
				'calendar_type',
				'calendar_list',
				'admin_rowactions',
				'adminmenu_roles',
				[
					'field'       => 'noschedule_statuses',
					'type'        => 'checkboxes',
					'title'       => _x( 'Non-Reschedulable Statuses', 'Modules: Calendar: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Posts in these statuses can <b>not</b> be rescheduled.', 'Modules: Calendar: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => [ 'publish', 'future', 'private' ],
					'exclude'     => [ 'trash', 'inherit', 'auto-draft' ],
					'values'      => PostType::getStatuses(),
				],
			],
			'posttypes_option' => 'posttypes_option',
		];
	}

	public function init()
	{
		parent::init();

		// has no frontend

		if ( $this->get_setting( 'admin_rowactions' ) ) {
			$this->filter( 'page_row_actions', 2 );
			$this->filter( 'post_row_actions', 2 );
		}
	}

	public function init_ajax()
	{
		$this->_hook_ajax();
	}

	public function ajax()
	{
		$post = self::unslash( $_POST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		switch ( $what ) {

			case 'reorder':

				if ( empty( $post['post_id'] ) )
					Ajax::errorMessage();

				if ( ! current_user_can( 'edit_post', $post['post_id'] ) )
					Ajax::errorUserCant();

				Ajax::checkReferer( $this->hook( $post['post_id'] ) );

				$result = $this->reschedule_post( $post['post_id'], $post['cal'], $post['year'], $post['month'], $post['day'] );

				if ( TRUE === $result )
					Ajax::successMessage();

				if ( $result )
					Ajax::errorMessage( $result );

				Ajax::errorMessage();

			break;
			case 'addnew':

				Ajax::checkReferer( $this->hook( 'add-new' ) );

				parse_str( $post['data'], $data );

				$object = PostType::object( $data['post_type'] );

				if ( ! current_user_can( $object->cap->create_posts ) )
					Ajax::errorUserCant();

				// Ajax::success( '<li>'.$data['post_title'].'</li>' ); // FOR TEST!

				if ( ! $new = $this->add_new_post( $data['post_type'], $data['date_cal'], $data['date_year'], $data['date_month'], $data['date_day'], $data['post_title'] ) )
					Ajax::errorMessage();

				Ajax::success( $this->get_post_row( $data['date_day'], $new ) );
		}

		Ajax::errorWhat();
	}

	public function admin_menu()
	{
		$hook = add_submenu_page(
			'index.php',
			_x( 'Editorial Calendar', 'Modules: Calendar: Page Title', GEDITORIAL_TEXTDOMAIN ),
			_x( 'My Calendar', 'Modules: Calendar: Menu Title', GEDITORIAL_TEXTDOMAIN ),
			$this->role_can( 'adminmenu' ) ? 'read' : 'do_not_allow',
			$this->get_adminmenu(),
			[ $this, 'admin_calendar_page' ]
		);

		add_action( 'load-'.$hook, [ $this, 'admin_calendar_load' ] );
	}

	public function admin_calendar_load()
	{
		$this->register_help_tabs();
		$this->actions( 'load', self::req( 'page', NULL ) );
		$this->enqueue_asset_js( 'calendar', NULL, [ 'jquery', Scripts::pkgSortable() ] );
	}

	public function page_row_actions( $actions, $post )
	{
		return $this->post_row_actions( $actions, $post );
	}

	public function post_row_actions( $actions, $post )
	{
		if ( in_array( $post->post_status, [ 'trash', 'private', 'auto-draft' ] ) )
			return $actions;

		if ( ! in_array( $post->post_type, $this->posttypes() ) )
			return $actions;

		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return $actions;

		if ( $link = $this->get_calendar_link( $post ) )
			return Arraay::insert( $actions, [
				$this->classs() => HTML::tag( 'a', [
					'href'   => $link,
					'title'  => _x( 'View on Calendar', 'Modules: Calendar', GEDITORIAL_TEXTDOMAIN ),
					'class'  => '-calendar',
					'target' => '_blank',
				], _x( 'Calendar', 'Modules: Calendar', GEDITORIAL_TEXTDOMAIN ) ),
			], 'view', 'before' );

		return $actions;
	}

	public function admin_calendar_page()
	{
		$cal = self::req( 'cal', $this->default_calendar() );

		$args = [
			'id'                  => $this->classs( 'calendar' ),
			'initial'             => FALSE,
			'caption_link'        => remove_query_arg( [ 'year', 'month' ] ),
			'post_type'           => $this->posttypes(),
			'exclude_statuses'    => [ 'private', 'trash', 'auto-draft', 'inherit' ],
			'link_build_callback' => [ $this, 'calendar_link_build' ],
			'the_day_callback'    => [ $this, 'calendar_the_day' ],
		];

		if ( $year = self::req( 'year', FALSE ) )
			$args['this_year'] = $year;

		if ( $month = self::req( 'month', FALSE ) )
			$args['this_month'] = $month;

		$calendars = [];

		foreach ( $this->get_calendars() as $calendar => $calendar_title )
			$calendars[add_query_arg( [ 'cal' => $calendar ], $args['caption_link'] )] = HTML::escape( $calendar_title );

		Settings::wrapOpen( $this->key, 'listtable' );

			Settings::headerTitle( _x( 'Editorial Calendar', 'Modules: Calendar: Page Title', GEDITORIAL_TEXTDOMAIN ), $calendars );

			$html = HTML::wrap( '', '-messages' );
			$html.= Helper::getCalendar( $cal, $args );
			$html.= $this->add_new_box( $cal );

			echo '<div class="'.$this->classs( 'calendar' ).'" data-cal="'.$cal.'">'.$html.'</div>';

			$this->settings_signature( 'listtable' );
		Settings::wrapClose();
	}

	private function add_new_box( $calendar )
	{
		$html = HTML::tag( 'input', [ 'type' => 'text', 'name' => 'post_title', 'data-field' => 'title', 'class' => 'regular-text' ] );

		$html.= HTML::tag( 'input', [ 'type' => 'hidden', 'name' => 'post_type', 'data-field' => 'type' ] );
		$html.= HTML::tag( 'input', [ 'type' => 'hidden', 'name' => 'date_day', 'data-field' => 'day' ] );
		$html.= HTML::tag( 'input', [ 'type' => 'hidden', 'name' => 'date_month', 'data-field' => 'month' ] );
		$html.= HTML::tag( 'input', [ 'type' => 'hidden', 'name' => 'date_year', 'data-field' => 'year' ] );
		$html.= HTML::tag( 'input', [ 'type' => 'hidden', 'name' => 'date_cal', 'data-field' => 'cal', 'value' => $calendar ] );
		$html.= HTML::tag( 'input', [ 'type' => 'hidden', 'name' => 'nonce', 'data-field' => 'nonce', 'value' => wp_create_nonce( $this->hook( 'add-new' ) ) ] );

		$actions = HTML::button( HTML::getDashicon( 'yes '), '#', _x( 'Save', 'Modules: Calendar', GEDITORIAL_TEXTDOMAIN ), TRUE, [ 'action' => 'save' ] );
		$actions.= HTML::button( HTML::getDashicon( 'no-alt '), '#', _x( 'Cancel', 'Modules: Calendar', GEDITORIAL_TEXTDOMAIN ), TRUE, [ 'action' => 'close' ] );

		$html.= HTML::wrap( $actions, '-actions' );

		return '<div class="hidden" id="'.$this->classs( 'add-new' ).'">'.$html.'</div>';
	}

	public function calendar_link_build( $for, $year = NULL, $month = NULL, $day = NULL, $args = [] )
	{
		return add_query_arg( [
			'year'  => $year,
			'month' => $month,
			'cal'   => self::req( 'cal', $this->default_calendar() ),
		] );
	}

	public function calendar_the_day( $the_day, $data = [], $args = [], $today = FALSE )
	{
		$cal = self::req( 'cal', $this->default_calendar() );

		$html = Ajax::spinner();
		$html.= '<span class="-the-day-number">'.Number::format( $the_day ).'</span>';

		if ( $today )
			$html.= '<span class="-the-day-today">'._x( 'Today', 'Modules: Calendar', GEDITORIAL_TEXTDOMAIN ).'</span>';

		// must have sortable container
		$html.= '<ol class="-sortable" data-day="'.$the_day.'">';

		foreach ( $data as $post )
			$html.= $this->get_post_row( $the_day, $post['ID'], $args );

		$html.= '</ol>';

		if ( is_null( $this->posttype_addnew ) )
			$this->posttype_addnew = $this->get_addnew_links( $args['post_type'] );

		if ( $this->posttype_addnew )
			$html.= HTML::wrap( $this->posttype_addnew, '-buttons' );

		return $html;
	}

	private function get_post_row( $the_day, $post, $calendar_args = [] )
	{
		if ( ! $post = get_post( $post ) )
			return '';

		$html = '<li data-day="'.$the_day.'"';

		if ( $this->can_reschedule( $post ) && current_user_can( 'edit_post', $post->ID ) ) {

			$html.= ' data-post="'.$post->ID.'" data-nonce="'.wp_create_nonce( $this->hook( $post->ID ) ).'">';
			$html.= '<span class="-handle" title="'._x( 'Drag me!', 'Modules: Calendar: Sortable', GEDITORIAL_TEXTDOMAIN ).'">';

		} else {

			$html.= '><span>';
		}

		if ( is_null( $this->posttype_statuses ) )
			$this->posttype_statuses = PostType::getStatuses();

		if ( ! isset( $this->posttype_icons[$post->post_type] ) )
			$this->posttype_icons[$post->post_type] = Helper::getPostTypeIcon( $post->post_type );

		$title = Number::format( date( 'H:i', strtotime( $post->post_date ) ) );

		if ( $author = get_user_by( 'id', $post->post_author ) )
			$title = $author->display_name.' – '.$title;

		$title = $this->filters( 'post_row_title', $title, $post, $the_day, $calendar_args );

		$html.= $this->posttype_icons[$post->post_type].'</span> ';
		$html.= Helper::getPostTitleRow( $post, 'edit', $this->posttype_statuses, $title );

		return $html.'</li>';
	}

	private function get_addnew_links( $posttypes )
	{
		$buttons = '';

		foreach ( $posttypes as $posttype ) {

			$object = PostType::object( $posttype );

			if ( current_user_can( $object->cap->create_posts ) ) {

				$buttons.= '<a href="'.WordPress::getPostNewLink( $object->name )
					.'" title="'.HTML::escape( $object->labels->add_new_item )
					.'" data-type="'.$object->name.'" data-title="'.$object->labels->new_item
					.'" class="-the-day-newpost" target="_blank">'
					.Helper::getPostTypeIcon( $object ).'</a>';
			}
		}

		return $buttons;
	}

	private function add_new_post( $posttype, $cal, $year, $month, $day, $title )
	{
		if ( ! is_callable( 'gPersianDateDate', 'makeMySQL' ) )
			return FALSE;

		$data = [
			'post_title'  => wp_unslash( $title ),
			'post_type'   => $posttype,
			'post_status' => 'draft',
			'post_date'   => \gPersianDateDate::makeMySQL( 0, 0, 0, $month, $day, $year, $cal ),
		];

		return wp_insert_post( $data );
	}

	private function get_calendar_link( $post )
	{
		if ( ! is_callable( 'gPersianDateDate', 'getByCal' ) )
			return FALSE;

		if ( ! $this->role_can( 'adminmenu' ) )
			return FALSE;

		$cal  = $this->default_calendar();
		$date = \gPersianDateDate::getByCal( strtotime( $post->post_date_gmt ), $cal );

		return $this->get_adminmenu( FALSE, [
			'cal'   => $cal,
			'year'  => $date['year'],
			'month' => $date['mon'],
		] );
	}

	private function can_reschedule( $post )
	{
		return ! in_array( $post->post_status, $this->get_setting( 'noschedule_statuses', [ 'publish', 'future', 'private' ] ) );
	}

	// - for post time, if the post is unpublished, the change sets the
	// publication timestamp
	// - if the post was published or scheduled for the future, the change will
	// change the timestamp. 'publish' posts will become scheduled if moved past
	// today and 'future' posts will be published if moved before today
	// @REF: `handle_ajax_drag_and_drop()`
	private function reschedule_post( $post, $cal, $year, $month, $day )
	{
		global $wpdb;

		if ( ! is_callable( 'gPersianDateDate', 'make' ) )
			return FALSE;

		if ( ! $post = get_post( $post ) )
			return FALSE;

		if ( ! $this->can_reschedule( $post ) )
			return _x( 'Updating the post date dynamically doesn\'t work for published content.', 'Modules: Calendar', GEDITORIAL_TEXTDOMAIN );

		// persist the old hourstamp because we can't manipulate the exact time
		// on the calendar bump the last modified timestamps too
		$old_time  = date( 'H:i:s', strtotime( $post->post_date ) );
		$post_time = explode( ':', $old_time );

		if ( ! $timestamp = \gPersianDateDate::make( $post_time[0], $post_time[1], $post_time[2], $month, $day, $year, $cal ) )
			return _x( 'Something is wrong with the new date.', 'Modules: Calendar', GEDITORIAL_TEXTDOMAIN );

		$data = [
			'post_date'         => date( 'Y-m-d', $timestamp ).' '.$old_time,
			'post_modified'     => current_time( 'mysql' ),
			'post_modified_gmt' => current_time( 'mysql', 1 ),
		];

		// by default, changing a post on the calendar won't set the timestamp.
		// if the user desires that to be the behaviour, they can set the result
		// of this filter to 'true' with how WordPress works internally,
		// setting 'post_date_gmt' will set the timestamp
		if ( $this->filters( 'set_timestamp', FALSE ) )
			$data['post_date_gmt'] = date( 'Y-m-d', $timestamp )
				.' '.date( 'H:i:s', strtotime( $post->post_date_gmt ) );

		// self::__log( $post->post_date );
		// self::__log( $data['post_date'] );
		// self::__log( date( 'Y-m-d H:i:s', $timestamp ) );
		// return FALSE;

		// @SEE http://core.trac.wordpress.org/ticket/18362
		if ( ! $update = $wpdb->update( $wpdb->posts, $data, [ 'ID' => $post->ID ] ) )
			return FALSE;

		clean_post_cache( $post->ID );

		return TRUE;
	}
}
