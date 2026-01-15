<?php namespace geminorum\gEditorial\Modules\Schedule;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Schedule extends gEditorial\Module
{
	use Internals\Calendars;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'schedule',
			'title'    => _x( 'Schedule', 'Modules: Schedule', 'geditorial-admin' ),
			'desc'     => _x( 'Viewing and Schedule Content in a Customizable Calendar', 'Modules: Schedule', 'geditorial-admin' ),
			'icon'     => 'calendar-alt',
			'access'   => 'beta',
			'frontend' => FALSE,
			'disabled' => Services\Modulation::moduleCheckPersianDate(),
			'keywords' => [
				'calendar',
				'needs-persian-date',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_dashboard' => [
				// 'calendar_type',
				// 'calendar_list',
				'admin_rowactions',
				'adminmenu_roles' => [ NULL, $this->get_settings_default_roles() ],
				[
					'field'       => 'noschedule_statuses',
					'type'        => 'checkboxes',
					'title'       => _x( 'Non-Reschedulable Statuses', 'Setting Title', 'geditorial-schedule' ),
					'description' => _x( 'Posts in these statuses can <b>not</b> be rescheduled.', 'Setting Description', 'geditorial-schedule' ),
					'default'     => [ 'publish', 'future', 'private' ],
					'exclude'     => [ 'trash', 'inherit', 'auto-draft' ],
					'values'      => WordPress\Status::get(),
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

	public function do_ajax()
	{
		$post = self::unslash( $_POST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		switch ( $what ) {

			case 'reschedule':

				if ( empty( $post['post_id'] ) )
					gEditorial\Ajax::errorMessage();

				if ( ! current_user_can( 'edit_post', $post['post_id'] ) )
					gEditorial\Ajax::errorUserCant();

				gEditorial\Ajax::checkReferer( $this->hook( $post['post_id'] ) );

				if ( ! $target = WordPress\Post::get( $post['post_id'] ) )
					gEditorial\Ajax::errorMessage( _x( 'Post not found.', 'Message', 'geditorial-schedule' ) );

				if ( ! $this->can_reschedule( $target ) )
					gEditorial\Ajax::errorMessage( _x( 'Updating the post date dynamically doesn\'t work for published content.', 'Message', 'geditorial-schedule' ) );

				$result = gEditorial\Datetime::reSchedulePost( $target, $post );

				if ( TRUE === $result )
					gEditorial\Ajax::successMessage();

				if ( $result )
					gEditorial\Ajax::errorMessage( $result ?: NULL );

				gEditorial\Ajax::errorMessage();

			break;
			case 'addnew':

				gEditorial\Ajax::checkReferer( $this->hook( 'add-new' ) );

				parse_str( $post['data'], $data );

				if ( ! WordPress\PostType::can( $data['post_type'], 'create_posts' ) )
					gEditorial\Ajax::errorUserCant();

				// gEditorial\Ajax::success( '<li>'.$data['post_title'].'</li>' ); // FOR TEST!

				if ( ! $new = $this->add_new_post( $data['post_type'], $data['date_cal'], $data['date_year'], $data['date_month'], $data['date_day'], $data['post_title'] ) )
					gEditorial\Ajax::errorMessage();

				gEditorial\Ajax::success( $this->get_post_row( $data['date_day'], $new ) );
		}

		gEditorial\Ajax::errorWhat();
	}

	// TODO: use `$this->_hook_wp_submenu_page()`
	public function admin_menu()
	{
		$hook = add_submenu_page(
			'index.php',
			_x( 'Editorial Calendar', 'Page Title', 'geditorial-schedule' ),
			_x( 'My Calendar', 'Menu Title', 'geditorial-schedule' ),
			$this->role_can( 'adminmenu' ) ? 'exist' : 'do_not_allow',
			$this->get_adminpage_url( FALSE ),
			[ $this, 'admin_calendar_page' ]
		);

		add_action( 'load-'.$hook, [ $this, 'admin_calendar_load' ] );
		$this->screens['adminmenu'] = $hook;
	}

	public function admin_calendar_load()
	{
		$this->register_help_tabs();
		$this->actions( 'load', self::req( 'page', NULL ) );
		$this->enqueue_asset_js( 'calendar', NULL, [ 'jquery', gEditorial\Scripts::pkgSortable() ] );
	}

	public function page_row_actions( $actions, $post )
	{
		return $this->post_row_actions( $actions, $post );
	}

	public function post_row_actions( $actions, $post )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $actions;

		if ( ! $this->is_post_viewable( $post ) )
			return $actions;

		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return $actions;

		if ( $link = $this->get_calendar_link( $post ) )
			return Core\Arraay::insert( $actions, [
				$this->classs() => Core\HTML::tag( 'a', [
					'href'   => $link,
					'title'  => _x( 'View on Calendar', 'Title Attr', 'geditorial-schedule' ),
					'class'  => '-calendar',
					'target' => '_blank',
				], _x( 'Calendar', 'Action', 'geditorial-schedule' ) ),
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

		$calendars = $this->list_calendars();

		if ( count( $calendars ) > 1 ) {

			$links = [];

			foreach ( $calendars as $calendar => $calendar_title )
				$links[add_query_arg( [ 'cal' => $calendar ], $args['caption_link'] )] = Core\HTML::escape( $calendar_title );

		} else {

			$links = FALSE;
		}

		gEditorial\Settings::wrapOpen( $this->key, 'listtable' );

			gEditorial\Settings::headerTitle( 'listtable', _x( 'Editorial Calendar', 'Page Title', 'geditorial-schedule' ), $links );

			$html = Core\HTML::wrap( '', '-messages' );
			$html.= gEditorial\Datetime::getCalendar( $cal, $args );
			$html.= $this->add_new_box( $cal );

			echo '<div class="'.Core\HTML::prepClass( $this->classs( 'calendar' ) ).'" data-cal="'.$cal.'">'.$html.'</div>';

			$this->settings_signature( 'listtable' );
		gEditorial\Settings::wrapClose();
	}

	private function add_new_box( $calendar )
	{
		$html = Core\HTML::tag( 'input', [ 'type' => 'text', 'name' => 'post_title', 'data-field' => 'title', 'class' => 'regular-text' ] );

		$html.= Core\HTML::tag( 'input', [ 'type' => 'hidden', 'name' => 'post_type',  'data-field' => 'type'  ] );
		$html.= Core\HTML::tag( 'input', [ 'type' => 'hidden', 'name' => 'date_day',   'data-field' => 'day'   ] );
		$html.= Core\HTML::tag( 'input', [ 'type' => 'hidden', 'name' => 'date_month', 'data-field' => 'month' ] );
		$html.= Core\HTML::tag( 'input', [ 'type' => 'hidden', 'name' => 'date_year',  'data-field' => 'year'  ] );
		$html.= Core\HTML::tag( 'input', [ 'type' => 'hidden', 'name' => 'date_cal',   'data-field' => 'cal',   'value' => $calendar ] );
		$html.= Core\HTML::tag( 'input', [ 'type' => 'hidden', 'name' => 'nonce',      'data-field' => 'nonce', 'value' => wp_create_nonce( $this->hook( 'add-new' ) ) ] );

		$actions = Core\HTML::button( Core\HTML::getDashicon( 'yes' ),    '#', _x( 'Save', 'Title Attr', 'geditorial-schedule' ),   TRUE, [ 'action' => 'save'  ] );
		$actions.= Core\HTML::button( Core\HTML::getDashicon( 'no-alt' ), '#', _x( 'Cancel', 'Title Attr', 'geditorial-schedule' ), TRUE, [ 'action' => 'close' ] );

		$html.= Core\HTML::wrap( $actions, '-actions' );

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

		$html = gEditorial\Ajax::spinner();
		$html.= '<span class="-the-day-number">'.Core\Number::localize( $the_day ).'</span>';

		if ( $today )
			$html.= '<span class="-the-day-today">'._x( 'Today', 'Indicator', 'geditorial-schedule' ).'</span>';

		// must have sortable container
		$html.= '<ol class="-sortable" data-day="'.$the_day.'">';

		foreach ( $data as $post )
			$html.= $this->get_post_row( $the_day, $post['ID'], $args );

		$html.= '</ol>';

		if ( ! isset( $this->cache['posttype_addnew'] ) )
			$this->cache['posttype_addnew'] = $this->get_addnew_links( $args['post_type'] );

		if ( ! empty( $this->cache['posttype_addnew'] ) )
			$html.= Core\HTML::wrap( $this->cache['posttype_addnew'], '-buttons' );

		return $html;
	}

	private function get_post_row( $the_day, $post, $calendar_args = [] )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return '';

		$html = '<li data-day="'.$the_day.'" data-status="'.$post->post_status.'"';

		if ( $this->can_reschedule( $post ) && current_user_can( 'edit_post', $post->ID ) ) {

			$html.= ' data-post="'.$post->ID.'" data-nonce="'.wp_create_nonce( $this->hook( $post->ID ) ).'">';
			$html.= '<span class="-handle" title="'._x( 'Drag me!', 'Sortable', 'geditorial-schedule' ).'">';

		} else {

			$html.= '><span>';
		}

		if ( ! isset( $this->cache['posttype_statuses'] ) )
			$this->cache['posttype_statuses'] = WordPress\Status::get();

		if ( ! isset( $this->cache['posttype_icons'][$post->post_type] ) )
			$this->cache['posttype_icons'][$post->post_type] = Services\Icons::posttypeMarkup( $post->post_type );

		$title = Core\Number::localize( date( 'H:i', strtotime( $post->post_date ) ) );

		if ( $author = get_user_by( 'id', $post->post_author ) )
			$title = $author->display_name.' – '.$title;

		$title = $this->filters( 'post_row_title', $title, $post, $the_day, $calendar_args );

		$html.= $this->cache['posttype_icons'][$post->post_type].'</span> ';
		$html.= gEditorial\Helper::getPostTitleRow( $post, 'edit', $this->cache['posttype_statuses'], $title );

		return $html.'</li>';
	}

	private function get_addnew_links( $posttypes )
	{
		$buttons = '';

		foreach ( $posttypes as $posttype ) {

			$object = WordPress\PostType::object( $posttype );

			if ( current_user_can( $object->cap->create_posts ) ) {

				$buttons.= '<a href="'.WordPress\PostType::newLink( $object->name )
					.'" title="'.Core\HTML::escape( $object->labels->add_new_item )
					.'" data-type="'.$object->name.'" data-title="'.$object->labels->new_item
					.'" class="-the-day-newpost" target="_blank">'
					.Services\Icons::posttypeMarkup( $object ).'</a>';
			}
		}

		return $buttons;
	}

	private function add_new_post( $posttype, $cal, $year, $month, $day, $title )
	{
		if ( ! is_callable( 'gPersianDateDate', 'makeMySQL' ) )
			return FALSE;

		$data = [
			'post_title'  => self::unslash( $title ),
			'post_type'   => $posttype,
			'post_status' => 'draft',
			'post_date'   => \gPersianDateDate::makeMySQL( 0, 0, 0, $month, $day, $year, $cal ),
		];

		return wp_insert_post( $data );
	}

	private function get_calendar_link( $post )
	{
		if ( ! is_callable( 'gPersianDateDate', 'getByPost' ) )
			return FALSE;

		if ( ! $this->role_can( 'adminmenu' ) )
			return FALSE;

		$cal  = $this->default_calendar();
		$date = \gPersianDateDate::getByPost( $post, $cal );

		return $this->get_adminpage_url( TRUE, [
			'cal'   => $cal,
			'year'  => $date['year'],
			'month' => $date['mon'],
		], 'adminmenu' );
	}

	private function can_reschedule( $post )
	{
		return ! $this->in_setting( $post->post_status, 'noschedule_statuses', [ 'publish', 'future', 'private' ] );
	}
}
