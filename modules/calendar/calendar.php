<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\WordPress;

class Calendar extends gEditorial\Module
{

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
			'_general' => [
				'calendar_type',
				'calendar_list',
			],
			'posttypes_option' => 'posttypes_option',
		];
	}

	public function admin_menu()
	{
		$hook = add_submenu_page(
			'index.php',
			_x( 'Editorial Calendar', 'Modules: Calendar: Page Title', GEDITORIAL_TEXTDOMAIN ),
			_x( 'My Calendar', 'Modules: Calendar: Menu Title', GEDITORIAL_TEXTDOMAIN ),
			$this->caps['reports'],
			$this->classs(),
			[ $this, 'admin_calendar_page' ]
		);

		add_action( 'load-'.$hook, [ $this, 'admin_calendar_load' ] );
	}

	public function admin_calendar_load()
	{
		$page = isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : NULL;

		$screen = get_current_screen();

		foreach ( Settings::settingsHelpContent() as $tab )
			$screen->add_help_tab( $tab );

		$this->actions( 'load', $page );

		// $this->enqueue_asset_js();
	}

	public function admin_calendar_page()
	{
		$args = [
			'initial'          => FALSE,
			'caption_link'     => remove_query_arg( [ 'year', 'month' ] ),
			'post_type'        => $this->post_types(),
			'exclude_statuses' => [ 'private', 'trash', 'auto-draft', 'inherit' ],

			'link_build_callback' => [ $this, 'calendar_link_build' ],
			'the_day_callback'    => [ $this, 'calendar_the_day' ],
		];

		if ( $year = self::req( 'year', FALSE ) )
			$args['this_year'] = $year;

		if ( $month = self::req( 'month', FALSE ) )
			$args['this_month'] = $month;

		Settings::wrapOpen( $this->key, $this->base, 'listtable' );

			Settings::headerTitle( _x( 'Editorial Calendar', 'Modules: Calendar: Page Title', GEDITORIAL_TEXTDOMAIN ), FALSE );

			$html = Helper::getCalendar( self::req( 'cal', $this->default_calendar() ), $args );
			echo HTML::wrap( $html, $this->classs( 'calendar' ) );

			$this->settings_signature( $this->module, 'listtable' );
		Settings::wrapClose();
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

		$html = '<span class="-the-day-number">'.Number::format( $the_day ).'</span>';

		if ( $today )
			$html.= '<span class="-the-day-today">'._x( 'Today', 'Modules: Calendar', GEDITORIAL_TEXTDOMAIN ).'</span>';

		if ( $data ) {
			$html.= '<ul>';
			foreach ( $data as $post )
				$html.= '<li>'.Helper::getPostTypeIcon( $post['type'] )
					.' '.Helper::getPostTitleRow( $post['ID'] ).'</li>'; // FIXME: add status/author/front/preview
			$html.= '</ul>';
		}

		// $html.= self::dump( $data, TRUE, FALSE );

		$buttons = '';

		foreach ( $args['post_type'] as $posttype ) {

			$object = get_post_type_object( $posttype );

			if ( current_user_can( $object->cap->create_posts ) ) {

				// FIXME: much better to create via ajax and control the post date

				$buttons.= '<a href="'.$this->new_post_link( $object->name, $cal, $args['this_year'], $args['this_month'], $the_day )
					.'" title="'.esc_attr( $object->labels->add_new_item ).'" class="-the-day-newpost" target="_blank">'
					.Helper::getPostTypeIcon( $object )
					.'</a> ';
			}
		}

		if ( $buttons )
			$html.= HTML::wrap( $buttons, '-buttons' );

		return $html;
	}

	// FIXME: not good!
	public function new_post_link( $post_type, $cal, $year, $month, $day )
	{
		$args = [];

		if ( is_callable( 'gPersianDateDate', 'makeMySQL' ) )
			$args['post_date'] = \gPersianDateDate::makeMySQL( 0, 0, 0, $month, $day, $year, $cal );

		return WordPress::getPostNewLink( $post_type, $args );
	}
}
