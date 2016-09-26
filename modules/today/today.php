<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialToday extends gEditorialModuleCore
{

	public static function module()
	{
		return array(
			'name'  => 'today',
			'title' => _x( 'Today', 'Today Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'The day in History', 'Today Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'calendar-alt',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				'multiple_instances',
				'calendar_type',
			),
			'posttypes_option' => 'posttypes_option',
		);
	}

	protected function get_global_constants()
	{
		return array(
			'day_cpt'           => 'day',
			'day_cpt_archive'   => 'days',
			'day_cpt_permalink' => '/%postname%',
			'day_shortcode'     => 'day',
			'year_shortcode'    => 'year',

			'meta_cal'   => '_theday_cal',
			'meta_day'   => '_theday_day',
			'meta_month' => '_theday_month',
			'meta_year'  => '_theday_year',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
				'meta_box_title'      => _x( 'The Day', 'Today Module: Meta Box Title', GEDITORIAL_TEXTDOMAIN ),
				'theday_column_title' => _x( 'The Day', 'Today Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
			),
			'noops' => array(
				'day_cpt' => _nx_noop( 'Day', 'Days', 'Today Module: Today CPT Labels: Name', GEDITORIAL_TEXTDOMAIN ),
			),
		);
	}

	protected function get_global_supports()
	{
		return array(
			'day_cpt' => array(
				'title',
				'excerpt',
			),
		);
	}

	public function setup( $partials = array() )
	{
		parent::setup( array(
			'helper',
		) );

		if ( ! is_admin() ) {

			// FIXME: add setting to disable this
			add_filter( 'query_vars', array( $this, 'query_vars' ) );
			add_filter( 'template_include', array( $this, 'template_include' ) );
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		}

		add_filter( 'rewrite_rules_array', array( $this, 'rewrite_rules_array' ) );
		add_filter( 'post_type_link', array( $this, 'post_type_link' ), 10, 4 );
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'day_cpt' );
	}

	public function init()
	{
		do_action( 'geditorial_today_init', $this->module );

		$this->do_globals();

		$this->post_types_excluded = array( $this->constant( 'day_cpt' ) );
		$this->register_post_type( 'day_cpt', array(), array( 'post_tag' ) );

		$this->register_shortcode( 'day_shortcode', array( 'gEditorialTodayTemplates', 'day_shortcode' ) );
		$this->register_shortcode( 'year_shortcode', array( 'gEditorialTodayTemplates', 'year_shortcode' ) );
	}

	public function current_screen( $screen )
	{
		if ( 'post' == $screen->base ) {

			if ( $screen->post_type == $this->constant( 'day_cpt' ) ) {

				// SEE: http://make.wordpress.org/core/2012/12/01/more-hooks-on-the-edit-screen/
				// add_action( 'edit_form_after_title', function() {
				//     echo '<h2>This is edit_form_after_title!</h2>';
				// } );
				//
				// add_action( 'edit_form_after_editor', function() {
				//     echo '<h2>This is edit_form_after_editor!</h2>';
				// } );
				//
				// add_action( 'submitpost_box', function() {
				//     echo '<h2>This is submitpost_box!</h2>';
				// } );

				add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
				add_action( 'save_post', array( $this, 'save_post_supported' ), 20, 3 );
				add_action( 'edit_form_advanced', array( $this, 'edit_form_advanced' ), 10, 1 );

				add_meta_box( 'geditorial-today',
					$this->get_meta_box_title( 'day_cpt' ),
					array( $this, 'do_meta_boxes' ),
					$screen,
					'side',
					'high'
				);

			} else if ( in_array( $screen->post_type, $this->post_types() ) ) {

				add_action( 'save_post', array( $this, 'save_post_supported' ), 20, 3 );

				add_meta_box( 'geditorial-today-supported',
					$this->get_meta_box_title(),
					array( $this, 'do_meta_boxes' ),
					$screen,
					'side',
					'high'
				);
			}

		} else if ( 'edit' == $screen->base ) {

			if ( $screen->post_type == $this->constant( 'day_cpt' ) ) {

				add_filter( 'disable_months_dropdown', '__return_true', 12 );
				add_filter( 'manage_'.$screen->post_type.'_posts_columns', array( $this, 'manage_posts_columns' ) );
				add_filter( 'manage_'.$screen->post_type.'_posts_custom_column', array( $this, 'posts_custom_column'), 10, 2 );
				add_filter( 'manage_edit-'.$screen->post_type.'_sortable_columns', array( $this, 'sortable_columns' ) );

			} else if ( in_array( $screen->post_type, $this->post_types() ) ) {

				add_filter( 'manage_'.$screen->post_type.'_posts_columns', array( $this, 'manage_posts_columns_supported' ), 12 );
				add_filter( 'manage_'.$screen->post_type.'_posts_custom_column', array( $this, 'posts_custom_column'), 10, 2 );
				add_filter( 'manage_edit-'.$screen->post_type.'_sortable_columns', array( $this, 'sortable_columns' ) );
			}
		}
	}

	public function do_meta_boxes( $post, $box )
	{
		echo '<div class="geditorial-admin-wrap-metabox">';

			do_action( 'geditorial_today_meta_box', $post, $box );

			$default_type = $this->get_setting( 'calendar_type', 'gregorian' );

			if ( 'auto-draft' == $post->post_status
				&& $this->get_setting( 'today_in_draft', FALSE ) ) // FIXME: add setting
				$the_day = gEditorialTodayHelper::getTheDayFromToday( NULL, $default_type );

			else if ( self::req( 'post' ) )
				$the_day = gEditorialTodayHelper::getTheDayFromPost( $post, $default_type, $this->get_the_day_constants() );

			else
				$the_day = gEditorialTodayHelper::getTheDayFromQuery( TRUE, $default_type, $this->get_the_day_constants() );

			gEditorialTodayHelper::theDaySelect( $the_day, ( $post->post_type != $this->constant( 'day_cpt' ) ), $default_type );

		echo '</div>';
	}

	public function gpeople_support( $post_types )
	{
		return array_merge( $post_types, array( $this->constant( 'day_cpt' ) ) );
	}

	public function manage_posts_columns_supported( $posts_columns )
	{
		$new_columns = array();

		foreach ( $posts_columns as $key => $value ) {

			if ( $key == 'title' ) {
				$new_columns['theday'] = $this->get_column_title( 'theday', 'day_cpt' );
				$new_columns[$key] = $value;
			} else {
				$new_columns[$key] = $value;
			}
		}

		return $new_columns;
	}

	public function manage_posts_columns( $posts_columns )
	{
		$new_columns = array();
		foreach ( $posts_columns as $key => $value ) {

			if ( $key == 'title' ) {
				$new_columns['theday'] = $this->get_column_title( 'theday', 'day_cpt' );
				$new_columns['cover'] = $this->get_column_title( 'cover', 'day_cpt' );
				$new_columns[$key] = $value;

			} else if ( 'date' == $key ){
				$new_columns['children'] = $this->get_column_title( 'children', 'day_cpt' );

			} else if ( in_array( $key, array( 'author', 'comments' ) ) ) {
				continue; // he he!

			} else {
				$new_columns[$key] = $value;
			}
		}
		return $new_columns;
	}

	public function posts_custom_column( $column_name, $post_id )
	{
		if ( 'children' == $column_name )
			$this->column_count( $this->get_linked_posts( $post_id, 'day_cpt', 'day_tax', TRUE ) );

		else if ( 'theday' == $column_name )
			$this->column_theday( $post_id );

		else if ( 'cover' == $column_name )
			$this->column_thumb( $post_id, $this->get_image_size_key( 'day_cpt' ) );
	}

	public function sortable_columns( $columns )
	{
		$columns['theday'] = 'theday'; // FIXME: add var query
		return $columns;
	}

	public function column_theday( $post_id )
	{
		gEditorialTodayHelper::displayTheDayFromPost( get_post( $post_id ),
			$this->get_setting( 'calendar_type', 'gregorian' ),
			$this->get_the_day_constants() );
	}

	public function post_updated_messages( $messages )
	{
		$messages[$this->constant( 'day_cpt' )] = $this->get_post_updated_messages( 'day_cpt' );
		return $messages;
	}

	// CAUTION: the ordering is crucial
	protected function get_the_day_constants()
	{
		return array(
			'cal'   => $this->constant( 'meta_cal' ),
			'month' => $this->constant( 'meta_month' ),
			'day'   => $this->constant( 'meta_day' ),
			'year'  => $this->constant( 'meta_year' ),
		);
	}

	protected function check_the_day_posttype( $the_day = array() )
	{
		return gEditorialTodayHelper::getPostsConnected( array(
			'type'    => $this->constant( 'day_cpt' ),
			'the_day' => $the_day,
			'all'     => TRUE,
			'count'   => TRUE,
		), $this->get_the_day_constants() );
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

			// $the_day = gEditorialTodayHelper::getTheDayByPost( $post, $default_type, $constants );
			// $the_day = gEditorialTodayHelper::getTheDayFromQuery( TRUE, $default_type, $constants );
			$the_day = gEditorialTodayHelper::getTheDayFromPost( $post, $default_type, $constants );

			list( $posts, $pagination ) = gEditorialTodayHelper::getPostsConnected( array(
				'type'    => $posttypes,
				'the_day' => $the_day,
				'all'     => TRUE,
			), $constants );

			gEditorialTodayHelper::theDayNewConnected( $posttypes, $the_day,
				( $this->check_the_day_posttype( $the_day ) ? FALSE : $this->constant( 'day_cpt' ) ) );

			self::tableList( array(

				'type' => array(
					'title' => _x( 'Type', 'Today Module', GEDITORIAL_TEXTDOMAIN ),
					'args'  => array(
						'post_types' => self::getPostTypes( TRUE ),
					),
					'callback' => function( $value, $row, $column, $index ){
						return isset( $column['args']['post_types'][$row->post_type] ) ? $column['args']['post_types'][$row->post_type] : $row->post_type;
					},
				),

				'post' => array(
					'title' => _x( 'Post', 'Today Module', GEDITORIAL_TEXTDOMAIN ),
					'args'  => array(
						'url'   => get_bloginfo( 'url' ),
						'admin' => admin_url( 'post.php' ),
					),
					'callback' => function( $value, $row, $column, $index ){

						$edit = add_query_arg( array(
							'action' => 'edit',
							'post'   => $row->ID,
						), $column['args']['admin'] );

						$view = add_query_arg( array(
							'p' => $row->ID,
						), $column['args']['url'] );

						$terms = get_the_term_list( $row->ID, 'post_tag', '<br />', ', ', '' );
						return $row->post_title.' <small>( <a href="'.$edit.'" target="_blank">Edit</a> | <a href="'.$view.'" target="_blank">View</a> )</small><br /><small>'.$terms.'</small>';
					},
				),

			), $posts, array(
				'empty' => gEditorialHTML::warning( _x( 'No Posts!', 'Today Module: Table Notice', GEDITORIAL_TEXTDOMAIN ) ),
			) );

		echo '</div>';
	}

	public function set_meta( $post_id, $postmeta, $key_suffix = '' )
	{
		if ( $postmeta )
			update_post_meta( $post_id, $key_suffix, $postmeta );
		else
			delete_post_meta( $post_id, $key_suffix );
	}

	public function save_post_supported( $post_ID, $post, $update )
	{
		if ( $this->is_save_post( $post )
			|| $this->is_save_post( $post, $this->post_types() ) ) {

			foreach ( $this->get_the_day_constants() as $field => $constant )
				if ( isset( $_POST['geditorial-today-date-'.$field] ) )
					$this->set_meta( $post_ID, trim( $_POST['geditorial-today-date-'.$field] ), $constant );
		}

		return $post_ID;
	}

	// https://wphierarchy.com/
	public function template_include( $template )
	{
		// self::kill(get_query_template( 'singular' ));
		// if ( is_singular( $this->constant( 'day_cpt' ) ) ) {
		// 	add_filter( 'the_title', array( $this, 'the_title' ) );
		// 	add_filter( 'the_content', array( $this, 'the_content' ) );
		//
		// 	return get_single_template();

		// if ( is_front_page() ) {
		if ( is_home() ) {

			// FIXME: add setting for this

			$this->the_day = gEditorialTodayHelper::getTheDayFromToday( NULL,
				$this->get_setting( 'calendar_type', 'gregorian' ) );

			add_filter( 'the_title', array( $this, 'the_title' ) );
			add_filter( 'the_content', array( $this, 'the_content' ) );
			add_filter( 'get_the_date', array( $this, 'get_the_date' ), 10, 3 );

		} else if ( is_post_type_archive( $this->constant( 'day_cpt' ) ) ) {

			$this->the_day = gEditorialTodayHelper::getTheDayFromQuery( FALSE,
				$this->get_setting( 'calendar_type', 'gregorian' ),
				$this->get_the_day_constants() );

			// no day, just cal
			if ( 1 === count( $this->the_day ) )
				$this->the_day = gEditorialTodayHelper::getTheDayFromToday( NULL, $this->the_day['cal'] );

			add_filter( 'the_title', array( $this, 'the_title' ) );
			add_filter( 'the_content', array( $this, 'the_content' ) );
			add_filter( 'get_the_date', array( $this, 'get_the_date' ), 10, 3 );

			return get_single_template();
		}

		// TODO: add frontpage based on current date

		return $template;
	}

	protected $the_day = array();
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
		$costants = $this->get_the_day_constants();

		list( $posts, $pagination ) = gEditorialTodayHelper::getPostsConnected( array(
			'type'    => get_query_var( 'day_posttype', 'any' ),
			'the_day' => $this->the_day,
		), $costants );

		ob_start();

		echo '<div class="geditorial-front-wrap-nobox">';

		gEditorialTodayHelper::theDayNewConnected( $this->post_types(), $this->the_day,
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
			_ex( 'Nothing happened!', 'Today Module', GEDITORIAL_TEXTDOMAIN );
		}

		echo '</div>';

		return ob_get_clean();
	}

	protected function get_the_day_query_vars()
	{
		return array(
			'cal'   => 'day_cal',
			'month' => 'day_month',
			'day'   => 'day_day',
			'year'  => 'day_year',
			'type'  => 'day_posttype',
		);
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

		$meta_query = array();

		foreach ( $this->get_the_day_constants() as $field => $constant )
			if ( $var = $wp_query->get( 'day_'.$field ) )
				$meta_query[] = array(
					'key'     => $constant,
					'value'   => $var,
					'compare' => '=',
				);

		if ( count( $meta_query ) )
			$wp_query->set( 'meta_query', $meta_query );
	}

	public function pre_get_posts_temp( &$query )
	{
		// We want to act only on frontend and only main query
		if ( is_admin() || ! $query->is_main_query() )
			return;

		// A map from the timespan string to actual hours array
		$hours = array(
			'morning'   => range(6, 11),
			'afternoon' => range(12, 17),
			'evening'   => range(18, 23),
			'night'     => range(0, 5)
		);

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
		if (!$timestamp || !isset($hours[$timespan])) {
			return;
		}

		// Reset query variables, because `WP_Query` does nothing with
		// 'customdate' or 'timespan', so it's better remove them
		$query->init();

		// Set date query based on custom vars
		$query->set('date_query', array(
			array(
				'year'  => date('Y', $timestamp),
				'month' => date('m', $timestamp),
				'day'   => date('d', $timestamp)
			),
			array(
				'hour'    => $hours[$timespan],
				'compare' => 'IN'
			),
				'relation' => 'AND'
			) );
	}

	public function post_type_link( $post_link, $post, $leavename, $sample )
	{
		if ( $post->post_type == $this->constant( 'day_cpt' ) ) {

			$the_day = gEditorialTodayHelper::getTheDayFromPost( $post,
				$this->get_setting( 'calendar_type', 'gregorian' ),
				$this->get_the_day_constants() );

			return home_url( implode( '/', $the_day ).'/' );
		}

		return $post_link;
	}

	public function rewrite_rules_array( $rules )
	{
		$new_rules = array();
		$day_cpt   = $this->constant( 'day_cpt' );
		$pattern = '([^/]+)';

		foreach ( gEditorialHelper::getDefualtCalendars( TRUE ) as $cal => $title ) {

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
