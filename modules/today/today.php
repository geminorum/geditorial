<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Helpers\Today as ModuleHelper;

class Today extends gEditorial\Module
{

	protected $partials = [ 'helper' ];

	public static function module()
	{
		return [
			'name'  => 'today',
			'title' => _x( 'Today', 'Modules: Today', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'The day in History', 'Modules: Today', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'calendar-alt',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'multiple_instances',
				'calendar_type',
			],
			'posttypes_option' => 'posttypes_option',
		];
	}

	protected function get_global_constants()
	{
		return [
			'day_cpt'           => 'day',
			'day_cpt_archive'   => 'days',
			'day_cpt_permalink' => '/%postname%',

			'meta_cal'   => '_theday_cal',
			'meta_day'   => '_theday_day',
			'meta_month' => '_theday_month',
			'meta_year'  => '_theday_year',
		];
	}

	protected function get_global_strings()
	{
		return [
			'misc' => [
				'featured'              => _x( 'Cover Image', 'Modules: Today: Day CPT: Featured', GEDITORIAL_TEXTDOMAIN ),
				'meta_box_title'        => _x( 'The Day', 'Modules: Today: Meta Box Title', GEDITORIAL_TEXTDOMAIN ),
				'theday_column_title'   => _x( 'Day', 'Modules: Today: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'cover_column_title'    => _x( 'Cover', 'Modules: Today: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'children_column_title' => _x( 'Posts', 'Modules: Today: Column Title', GEDITORIAL_TEXTDOMAIN ),
			],
			'noops' => [
				'day_cpt' => _nx_noop( 'Day', 'Days', 'Modules: Today: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
		];
	}

	protected function get_global_supports()
	{
		return [
			'day_cpt' => [
				'title',
				'excerpt',
			],
		];
	}

	public function setup( $args = [] )
	{
		parent::setup();

		if ( ! is_admin() ) {

			// FIXME: add setting to disable this
			$this->filter( 'query_vars' );
			$this->filter( 'template_include' );
			$this->action( 'pre_get_posts' );
		}

		$this->filter( 'rewrite_rules_array' );
		$this->filter( 'post_type_link', 4 );
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'day_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->post_types_excluded = [ 'attachment', $this->constant( 'day_cpt' ) ];

		$this->register_post_type( 'day_cpt' );
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, 'day_cpt' ) ) {

			$this->_edit_screen_supported( $_REQUEST['post_type'] );

			$this->_save_meta_supported( $_REQUEST['post_type'] );

		} else if ( $this->is_inline_save( $_REQUEST, $this->post_types() ) ) {

			$this->_edit_screen_supported( $_REQUEST['post_type'] );

			$this->_save_meta_supported( $_REQUEST['post_type'] );
		}
	}

	public function current_screen( $screen )
	{
		if ( 'post' == $screen->base ) {

			if ( $screen->post_type == $this->constant( 'day_cpt' ) ) {

				// SEE: http://make.wordpress.org/core/2012/12/01/more-hooks-on-the-edit-screen/

				$this->_save_meta_supported( $screen->post_type );

				$this->filter( 'post_updated_messages' );
				$this->action( 'edit_form_advanced' );

				add_meta_box( $this->classs( 'main' ),
					$this->get_meta_box_title( 'day_cpt' ),
					[ $this, 'do_meta_boxes' ],
					$screen,
					'side',
					'high'
				);

			} else if ( in_array( $screen->post_type, $this->post_types() ) ) {

				$this->_save_meta_supported( $screen->post_type );

				add_meta_box( $this->classs( 'supported' ),
					$this->get_meta_box_title(),
					[ $this, 'do_meta_boxes' ],
					$screen,
					'side',
					'high'
				);
			}

		} else if ( 'edit' == $screen->base ) {

			if ( $screen->post_type == $this->constant( 'day_cpt' ) ) {

				$this->filter( 'bulk_post_updated_messages', 2 );

				$this->_save_meta_supported( $screen->post_type );
				$this->_admin_enabled();

				add_filter( 'disable_months_dropdown', '__return_true', 12 );

				$this->_edit_screen_supported( $screen->post_type );


				$this->enqueue_asset_js( $screen->base );

			} else if ( in_array( $screen->post_type, $this->post_types() ) ) {

				$this->_save_meta_supported( $screen->post_type );
				$this->_admin_enabled();

				$this->_edit_screen_supported( $screen->post_type );

				$this->enqueue_asset_js( $screen->base );
			}
		}
	}

	// for main & supported
	private function _edit_screen_supported( $post_type )
	{
		add_filter( 'manage_'.$post_type.'_posts_columns', [ $this, 'manage_posts_columns' ], 12 );
		add_filter( 'manage_'.$post_type.'_posts_custom_column', [ $this, 'posts_custom_column' ], 10, 2 );
		add_filter( 'manage_edit-'.$post_type.'_sortable_columns', [ $this, 'sortable_columns' ] );

		add_action( 'quick_edit_custom_box', [ $this, 'quick_edit_custom_box' ], 10, 2 );
	}

	private function _save_meta_supported( $post_type )
	{
		add_action( 'save_post', [ $this, 'save_post_supported' ], 20, 3 );
	}

	public function do_meta_boxes( $post, $box )
	{
		echo '<div class="geditorial-admin-wrap-metabox">';

			do_action( 'geditorial_today_meta_box', $post, $box );

			$default_type = $this->get_setting( 'calendar_type', 'gregorian' );

			if ( 'auto-draft' == $post->post_status
				&& $this->get_setting( 'today_in_draft', FALSE ) ) // FIXME: add setting
				$the_day = ModuleHelper::getTheDayFromToday( NULL, $default_type );

			else if ( self::req( 'post' ) )
				$the_day = ModuleHelper::getTheDayFromPost( $post, $default_type, $this->get_the_day_constants() );

			else
				$the_day = ModuleHelper::getTheDayFromQuery( TRUE, $default_type, $this->get_the_day_constants() );

			ModuleHelper::theDaySelect( $the_day, ( $post->post_type != $this->constant( 'day_cpt' ) ), $default_type );

		echo '</div>';

		wp_nonce_field( 'geditorial_today_post_main', '_geditorial_today_post_main' );
	}

	public function gpeople_support( $post_types )
	{
		return array_merge( $post_types, [ $this->constant( 'day_cpt' ) ] );
	}

	public function manage_posts_columns( $columns )
	{
		return Arraay::insert( $columns, [
			'theday' => $this->get_column_title( 'theday', 'day_cpt' ),
		], 'title', 'before' );
	}

	public function posts_custom_column( $column_name, $post_id )
	{
		if ( 'theday' == $column_name )
			ModuleHelper::displayTheDayFromPost( get_post( $post_id ),
				$this->get_setting( 'calendar_type', 'gregorian' ),
				$this->get_the_day_constants() );
	}

	public function quick_edit_custom_box( $column_name, $post_type )
	{
		if ( 'theday' != $column_name )
			return FALSE;

		echo '<div class="inline-edit-col geditorial-admin-wrap-quickedit -today">';
			echo '<span class="title inline-edit-categories-label">';
				echo $this->get_string( 'meta_box_title', $post_type, 'misc' );
			echo '</span>';
			ModuleHelper::theDaySelect( [], TRUE, '' );
		echo '</div>';

		wp_nonce_field( 'geditorial_today_post_raw', '_geditorial_today_post_raw' );
	}

	public function sortable_columns( $columns )
	{
		return array_merge( $columns, [ 'theday' => 'theday' ] ); // FIXME: add var query
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'day_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'day_cpt', $counts ) );
	}

	// CAUTION: the ordering is crucial
	protected function get_the_day_constants()
	{
		return [
			'cal'   => $this->constant( 'meta_cal' ),
			'month' => $this->constant( 'meta_month' ),
			'day'   => $this->constant( 'meta_day' ),
			'year'  => $this->constant( 'meta_year' ),
		];
	}

	protected function check_the_day_posttype( $the_day = [] )
	{
		return ModuleHelper::getPostsConnected( [
			'type'    => $this->constant( 'day_cpt' ),
			'the_day' => $the_day,
			'all'     => TRUE,
			'count'   => TRUE,
		], $this->get_the_day_constants() );
	}

	public function edit_form_advanced( $post )
	{
		if ( ! self::req( 'post' ) )
			return; // notice: save the post first

		echo '<div class="geditorial-admin-wrap-nobox">';

			do_action( 'geditorial_today_no_box', $post );

			$default_type = $this->get_setting( 'calendar_type', 'gregorian' );
			$constants    = $this->get_the_day_constants();
			$posttypes    = $this->post_types();

			// $the_day = ModuleHelper::getTheDayByPost( $post, $default_type, $constants );
			// $the_day = ModuleHelper::getTheDayFromQuery( TRUE, $default_type, $constants );
			$the_day = ModuleHelper::getTheDayFromPost( $post, $default_type, $constants );

			list( $posts, $pagination ) = ModuleHelper::getPostsConnected( [
				'type'    => $posttypes,
				'the_day' => $the_day,
				'all'     => TRUE,
			], $constants );

			ModuleHelper::theDayNewConnected( $posttypes, $the_day,
				( $this->check_the_day_posttype( $the_day ) ? FALSE : $this->constant( 'day_cpt' ) ) );

			HTML::tableList( [
				'type'  => Helper::tableColumnPostType(),
				'title' => Helper::tableColumnPostTitle(),
				'terms' => Helper::tableColumnPostTerms(),
			], $posts, [
				'empty' => Helper::tableArgEmptyPosts(),
			] );

		echo '</div>';
	}

	public function set_meta( $post_id, $postmeta, $key_suffix = '' )
	{
		if ( $postmeta )
			update_post_meta( $post_id, $key_suffix, $postmeta );
		else
			delete_post_meta( $post_id, $key_suffix );
	}

	public function save_post_supported( $post_id, $post, $update )
	{
		if ( $this->is_save_post( $post )
			|| $this->is_save_post( $post, $this->post_types() ) ) {

			if ( wp_verify_nonce( @$_REQUEST['_geditorial_today_post_main'], 'geditorial_today_post_main' )
				|| wp_verify_nonce( @$_REQUEST['_geditorial_today_post_raw'], 'geditorial_today_post_raw' ) ) {

				$default_type = $this->get_setting( 'calendar_type', 'gregorian' );

				foreach ( $this->get_the_day_constants() as $field => $constant ) {
					if ( isset( $_POST['geditorial-today-date-'.$field] ) ) {

						$value = trim( $_POST['geditorial-today-date-'.$field] );

						if ( 'cal' == $field )
							$value = Helper::sanitizeCalendar( trim( $value ), $default_type );
						else
							$value = Number::intval( trim( $value ), FALSE );

						$this->set_meta( $post_id, $value, $constant );
					}
				}
			}
		}

		return $post_id;
	}

	// @SEE: `bp_theme_compat_reset_post()`
	// https://wphierarchy.com/
	public function template_include( $template )
	{
		// self::kill( get_query_template( 'singular' ) );
		// if ( is_singular( $this->constant( 'day_cpt' ) ) ) {
		// 	add_filter( 'the_title', [ $this, 'the_title' ) );
		// 	add_filter( 'the_content', [ $this, 'the_content' ) );
		//
		// 	return $template;
		// 	return get_single_template();
		// }

		// if ( is_front_page() ) {
		if ( is_home() ) {

			// FIXME: add setting for this

			$this->the_day = ModuleHelper::getTheDayFromToday( NULL,
				$this->get_setting( 'calendar_type', 'gregorian' ) );

			$this->filter( 'the_title' );
			$this->filter( 'the_content' );
			$this->filter( 'get_the_date', 3 );

		} else if ( is_post_type_archive( $this->constant( 'day_cpt' ) ) ) {

			$this->the_day = ModuleHelper::getTheDayFromQuery( FALSE,
				$this->get_setting( 'calendar_type', 'gregorian' ),
				$this->get_the_day_constants() );

			// no day, just cal
			if ( 1 === count( $this->the_day ) )
				$this->the_day = ModuleHelper::getTheDayFromToday( NULL, $this->the_day['cal'] );

			$this->filter( 'the_title' );
			$this->filter( 'the_content' );
			$this->filter( 'get_the_date', 3 );

			return get_single_template();
		}

		// TODO: add frontpage based on current date

		return $template;
	}

	protected $the_day = [];
	protected $in_the_loop = FALSE;

	public function the_title( $title )
	{
		if ( $this->in_the_loop )
			return $title;

		if ( in_the_loop() )
			return 'DAY: '.implode( ', ', $this->the_day );

		return $title;
	}

	public function get_the_date( $the_date, $d, $post )
	{
		return 'DATE: '.implode( ', ', $this->the_day );
	}

	public function the_content( $content )
	{
		global $post;

		$costants = $this->get_the_day_constants();

		list( $posts, $pagination ) = ModuleHelper::getPostsConnected( [
			'type'    => get_query_var( 'day_posttype', 'any' ),
			'the_day' => $this->the_day,
		], $costants );

		ob_start();

		echo '<div class="geditorial-front-wrap-nobox">';

		ModuleHelper::theDayNewConnected( $this->post_types(), $this->the_day,
			( $this->check_the_day_posttype( $this->the_day ) ? FALSE : $this->constant( 'day_cpt' ) ) );

		if ( count( $posts ) ) {

			$this->in_the_loop = TRUE;
			echo '<ul>';

			foreach ( $posts as $post ) {

				setup_postdata( $post );
				echo '<li>';
				get_template_part( 'row', $this->constant( 'day_cpt' ) );
				echo '</li>';
			}

			wp_reset_postdata();

			echo '</ul>';
			$this->in_the_loop = FALSE;

		} else {
			_ex( 'Nothing happened!', 'Modules: Today', GEDITORIAL_TEXTDOMAIN );
		}

		echo '</div>';

		return ob_get_clean();
	}

	protected function get_the_day_query_vars()
	{
		return [
			'cal'   => 'day_cal',
			'month' => 'day_month',
			'day'   => 'day_day',
			'year'  => 'day_year',
			'type'  => 'day_posttype',
		];
	}

	public function query_vars( $public_query_vars )
	{
		return array_merge( $this->get_the_day_query_vars(), $public_query_vars );
	}

	// NO NEED if we override the whole archive page
	public function pre_get_posts( $wp_query )
	{
		return;

		if ( $wp_query->is_admin
			|| ! $wp_query->is_main_query() )
				return;

		$meta_query = [];

		foreach ( $this->get_the_day_constants() as $field => $constant )
			if ( $var = $wp_query->get( 'day_'.$field ) )
				$meta_query[] = [
					'key'     => $constant,
					'value'   => $var,
					'compare' => '=',
				];

		if ( count( $meta_query ) )
			$wp_query->set( 'meta_query', $meta_query );
	}

	public function pre_get_posts_temp( &$query )
	{
		// We want to act only on frontend and only main query
		if ( is_admin() || ! $query->is_main_query() )
			return;

		// A map from the timespan string to actual hours array
		$hours = [
			'morning'   => range(6, 11),
			'afternoon' => range(12, 17),
			'evening'   => range(18, 23),
			'night'     => range(0, 5)
		];

		// Get the custom vars, if available
		$customdate = $query->get('customdate');
		$timespan = $query->get('timespan');

		// If the vars are not set, this is not a query we're interested in
		if (!$customdate || !$timespan) {
			return;
		}

		// Get UNIX timestamp from the query var
		$timestamp = strtotime($customdate);

		// Do nothing if have the wrong values
		if (!$timestamp || !isset($hours[$timespan] ) ) {
			return;
		}

		// Reset query variables, because `WP_Query` does nothing with
		// 'customdate' or 'timespan', so it's better remove them
		$query->init();

		// Set date query based on custom vars
		$query->set('date_query', [
			[
				'year'  => date('Y', $timestamp),
				'month' => date('m', $timestamp),
				'day'   => date('d', $timestamp)
			],
			[
				'hour'    => $hours[$timespan],
				'compare' => 'IN'
			],
				'relation' => 'AND'
			]
		);
	}

	public function post_type_link( $post_link, $post, $leavename, $sample )
	{
		if ( $post->post_type == $this->constant( 'day_cpt' ) ) {

			$the_day = ModuleHelper::getTheDayFromPost( $post,
				$this->get_setting( 'calendar_type', 'gregorian' ),
				$this->get_the_day_constants() );

			return home_url( implode( '/', $the_day ).'/' );
		}

		return $post_link;
	}

	public function rewrite_rules_array( $rules )
	{
		$new_rules = [];
		$day_cpt   = $this->constant( 'day_cpt' );
		$pattern = '([^/]+)';

		foreach ( Helper::getDefualtCalendars( TRUE ) as $cal => $title ) {

			// /cal/month/day/year/posttype

			$new_rules[$cal.'/([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})/(.+)/?$'] = 'index.php?post_type='.$day_cpt
				.'&day_cal='.$cal
				.'&day_month=$matches[1]'
				.'&day_day=$matches[2]'
				.'&day_year=$matches[3]'
				.'&day_posttype=$matches[4]';

			$new_rules[$cal.'/([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})/?$'] = 'index.php?post_type='.$day_cpt
				.'&day_cal='.$cal
				.'&day_month=$matches[1]'
				.'&day_day=$matches[2]'
				.'&day_year=$matches[3]';

			$new_rules[$cal.'/([0-9]{1,2})/([0-9]{1,2})/?$'] = 'index.php?post_type='.$day_cpt
				.'&day_cal='.$cal
				.'&day_month=$matches[1]'
				.'&day_day=$matches[2]';

			$new_rules[$cal.'/([0-9]{1,2})/?$'] = 'index.php?post_type='.$day_cpt
				.'&day_cal='.$cal
				.'&day_month=$matches[1]';

			$new_rules[$cal.'/?$'] = 'index.php?post_type='.$day_cpt
				.'&day_cal='.$cal;
		}

		return array_merge( $new_rules, $rules );
	}
}
