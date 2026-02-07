<?php namespace geminorum\gEditorial\Modules\Today;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Today extends gEditorial\Module
{
	use Internals\Calendars;
	use Internals\CoreMenuPage;
	use Internals\MetaBoxCustom;
	use Internals\MetaBoxMain;
	use Internals\MetaBoxSupported;

	protected $__today = [];
	protected $__posts = [];
	protected $__home  = FALSE;

	public static function module()
	{
		return [
			'name'     => 'today',
			'title'    => _x( 'Today', 'Modules: Today', 'geditorial-admin' ),
			'desc'     => _x( 'The day in History', 'Modules: Today', 'geditorial-admin' ),
			'icon'     => 'calendar-alt',
			'access'   => 'beta',
			'keywords' => [
				'day',
				'calendar',
				'history',
				'cptmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_defaults'        => [
				'calendar_type',
				'calendar_list',
				[
					'field'       => 'today_in_draft',
					'title'       => _x( 'Fill The Day', 'Setting Title', 'geditorial-today' ),
					'description' => _x( 'Fills the current day info on newly drafted supported posttypes.', 'Setting Description', 'geditorial-today' ),
				],
			],
			'_dashboard' => [
				'adminmenu_roles' => [ NULL, $this->get_settings_default_roles() ],
				'admin_rowactions',
			],
			'_editlist' => [
				'admin_columns' => _x( 'Displays today column on edit list for supported posttypes.', 'Settings', 'geditorial-today' ),
			],
			'_frontend' => [
				[
					'field'       => 'insert_theday',
					'title'       => _x( 'Insert The Day', 'Setting Title', 'geditorial-today' ),
					'description' => _x( 'Displays the day info for supported posttypes.', 'Setting Description', 'geditorial-today' ),
				],
				$this->settings_insert_priority_option( -20, 'theday' ),
			],
			'_content' => [
				[
					'field'       => 'override_frontpage',
					'title'       => _x( 'Override Front-Page', 'Setting Title', 'geditorial-today' ),
					'description' => _x( 'Displays today list of connected posts on front-page.', 'Setting Description', 'geditorial-today' ),
				],
			],
			'_supports' => [
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'main_posttype', [
					'title',
					'excerpt',
					'editorial-roles'
				] ),
			],
			'_constants' => [
				'main_posttype_constant'  => [ NULL, 'day' ],
				'main_shortcode_constant' => [ NULL, 'the-day' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_posttype'     => 'day',
			'main_shortcode'    => 'the-day',
			'title_shortcode'   => 'the-day-title',
			'buttons_shortcode' => 'the-day-buttons',

			'metakey_cal'   => '_theday_cal',
			'metakey_day'   => '_theday_day',
			'metakey_month' => '_theday_month',
			'metakey_year'  => '_theday_year',

			'term_empty_the_day' => 'the-day-empty',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_posttype' => _n_noop( 'Day', 'Days', 'geditorial-today' ),
			],
			'labels' => [
				'main_posttype' => [
					'featured_image' => _x( 'Cover Image', 'Label: Featured Image', 'geditorial-today' ),
					'metabox_title'  => _x( 'The Day', 'Label: MetaBox Label', 'geditorial-today' ),
					'excerpt_label'  => _x( 'Summary', 'MetaBox Title', 'geditorial-today' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'mainbox_title'      => _x( 'The Day', 'MetaBox: `mainbox_title`', 'geditorial-today' ),
			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'quickedit_title'    => _x( 'The Day', 'MetaBox: `quickedit_title`', 'geditorial-today' ),
			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'supportedbox_title' => _x( 'The Day', 'MetaBox: `supportedbox_title`', 'geditorial-today' ),
		];

		$strings['misc'] = [
			'theday_column_title' => _x( 'Day', 'Column Title', 'geditorial-today' ),
		];

		return $strings;
	}

	public function get_global_fields()
	{
		$posttype = $this->constant( 'main_posttype' );

		return [
			'meta' => [
				$posttype => [
					'website_url' => [ 'type' => 'link' ],
					'wiki_url'    => [ 'type' => 'link' ],
				],
			],
		];
	}

	protected function get_module_templates()
	{
		return [
			'page_posttype' => [
				'frontpage' => _x( 'Editorial: Today: Front-page', 'Template Title', 'geditorial-today' ),
			],
		];
	}

	protected function posttypes_excluded( $extra = [] )
	{
		return $this->filters( 'posttypes_excluded',
			gEditorial\Settings::posttypesExcluded( $extra + [
				$this->constant( 'main_posttype' ),
			], $this->keep_posttypes )
		);
	}

	public function setup( $args = [] )
	{
		parent::setup();

		$this->filter( 'rewrite_rules_array', 1, 20 );
		$this->filter( 'post_type_link', 4 );

		if ( is_admin() )
			return;

		$this->filter_append( 'query_vars', $this->_get_query_vars() );
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'main_posttype' );
		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function meta_init()
	{
		$this->add_posttype_fields_for( 'meta', 'main_posttype' );
	}

	public function importer_init()
	{
		$this->filter_module( 'importer', 'fields', 2 );
		$this->filter_module( 'importer', 'prepare', 7 );
		$this->action_module( 'importer', 'saved', 2 );
	}

	public function init()
	{
		parent::init();

		$this->register_posttype( 'main_posttype', [], [
			'slug_disabled' => TRUE,
			'date_disabled' => TRUE,
		] );

		$this->register_shortcode( 'main_shortcode' );
		$this->register_shortcode( 'title_shortcode' );
		$this->register_shortcode( 'buttons_shortcode' );

		$this->filter( 'calendars_term_url', 4, 12, FALSE, $this->base );
		$this->filter( 'calendars_post_events', 3, 8, FALSE, $this->base );
		$this->filter( 'calendars_posttype_events', 3, 8, FALSE, $this->base );
		$this->filter_module( 'audit', 'auto_audit_save_post', 5 );

		if ( ! is_admin() )
			return;

		$this->filter( 'the_title', 2, 8 );
	}

	public function template_redirect()
	{
		if ( is_robots() || is_favicon() || is_feed() )
			return;

		if ( ! is_singular( $this->posttypes() ) )
			return;

		if ( $this->get_setting( 'insert_theday' ) ) {
			add_action( $this->hook_base( 'content', 'before' ),
				[ $this, 'insert_theday' ],
				$this->get_setting( 'insert_priority_theday', -20 )
			);

			$this->enqueue_styles(); // WTF: since no short-code available yet!
		}
	}

	public function setup_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'main_posttype' ) ) {

			$this->_edit_screen_supported( $posttype );
			$this->_save_meta_supported( $posttype );

		} else if ( $posttype = $this->is_inline_save_posttype( $this->posttypes() ) ) {

			if ( ! $this->get_setting( 'admin_columns' ) )
				return;

			$this->_edit_screen_supported( $posttype );
			$this->_save_meta_supported( $posttype );
		}
	}

	public function admin_menu()
	{
		$this->_hook_wp_submenu_page( 'adminmenu',
			'index.php',
			_x( 'Editorial Today', 'Page Title', 'geditorial-today' ),
			_x( 'My Today', 'Menu Title', 'geditorial-today' ),
			$this->role_can( 'adminmenu' ) ? 'exist' : 'do_not_allow',
			$this->get_adminpage_url( FALSE ),
		);
	}

	public function admin_adminmenu_load()
	{
		$this->register_help_tabs();
		$this->actions( 'load', self::req( 'page', NULL ) );

		// $this->enqueue_asset_js();
	}

	public function admin_adminmenu_page()
	{
		gEditorial\Settings::wrapOpen( $this->key, 'listtable' );

			gEditorial\Settings::headerTitle( 'listtable', _x( 'Editorial Today', 'Page Title', 'geditorial-today' ), FALSE );

			echo '<div id="poststuff"><div id="post-body" class="metabox-holder columns-2">';
				echo '<div id="postbox-container-2" class="postbox-container">';

					Core\HTML::h3( $this->the_day_title_callback() );

					foreach ( $this->__posts as $post )
						Core\HTML::h4( WordPress\Post::title( $post ) );

					echo $this->the_day_content_callback();

				echo '</div>';
				echo '<div id="postbox-container-1" class="postbox-container">';

					if ( WordPress\IsIt::dev() ) {
						self::dump( $this->__today );
						self::dump( $this->__posts );
					}

				echo '</div>';
			echo '</div></div>';


			$this->settings_signature( 'listtable' );
		gEditorial\Settings::wrapClose();
	}

	public function current_screen( $screen )
	{
		if ( 'post' == $screen->base ) {

			if ( $screen->post_type == $this->constant( 'main_posttype' ) ) {

				$this->_hook_general_mainbox( $screen );
				$this->_save_meta_supported( $screen->post_type );

				$this->action( 'edit_form_after_editor' );

				if ( post_type_supports( $screen->post_type, 'excerpt' ) )
					$this->metaboxcustom_add_metabox_excerpt( 'main_posttype', 'after_title' );

				$this->posttypes__media_register_headerbutton( 'main_posttype' );
				$this->_hook_post_updated_messages( 'main_posttype' );

			} else if ( $this->posttype_supported( $screen->post_type ) ) {

				$this->_hook_general_supportedbox( $screen );
				$this->_save_meta_supported( $screen->post_type );
			}

		} else if ( 'edit' == $screen->base ) {

			if ( $screen->post_type == $this->constant( 'main_posttype' ) ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				if ( $this->get_setting( 'admin_rowactions' ) )
					$this->filter( 'post_row_actions', 2 );

				$this->_save_meta_supported( $screen->post_type );
				$this->_edit_screen_supported( $screen->post_type );
				$this->_hook_bulk_post_updated_messages( 'main_posttype' );
				$this->_admin_enabled();

				$this->enqueue_asset_js( [], $screen );

			} else if ( $this->posttype_supported( $screen->post_type ) ) {

				if ( $this->get_setting( 'admin_rowactions' ) ) {

					$this->filter( 'page_row_actions', 2 );
					$this->filter( 'post_row_actions', 2 );
				}

				if ( $this->get_setting( 'admin_columns' ) ) {

					$this->_save_meta_supported( $screen->post_type );
					$this->_edit_screen_supported( $screen->post_type );
					$this->_admin_enabled();

					$this->enqueue_asset_js( [], $screen );
				}
			}
		}
	}

	// for main & supported
	private function _edit_screen_supported( $posttype )
	{
		add_filter( 'manage_'.$posttype.'_posts_columns', [ $this, 'manage_posts_columns' ], 12 );
		add_filter( 'manage_'.$posttype.'_posts_custom_column', [ $this, 'posts_custom_column' ], 10, 2 );
		add_filter( 'manage_edit-'.$posttype.'_sortable_columns', [ $this, 'sortable_columns' ] );

		$this->action( 'quick_edit_custom_box', 2 );
	}

	private function _save_meta_supported( $posttype )
	{
		$this->_hook_store_metabox( $posttype );
	}

	public function page_row_actions( $actions, $post )
	{
		return $this->post_row_actions( $actions, $post );
	}

	public function post_row_actions( $actions, $post )
	{
		if ( in_array( $post->post_status, [ 'trash', 'private', 'auto-draft' ] ) )
			return $actions;

		if ( ! $this->posttype_supported( $post->post_type )
			&& $post->post_type != $this->constant( 'main_posttype' ) )
				return $actions;

		if ( $link = $this->_get_today_admin_link( $post ) )
			return Core\Arraay::insert( $actions, [
				$this->classs() => Core\HTML::tag( 'a', [
					'href'   => $link,
					'title'  => _x( 'View on Today', 'Title Attr', 'geditorial-today' ),
					'class'  => '-today',
					'target' => '_blank',
				], _x( 'Today', 'Action', 'geditorial-today' ) ),
			], 'view', 'before' );

		return $actions;
	}

	private function _get_today_admin_link( $post )
	{
		if ( ! $this->role_can( 'adminmenu' ) )
			return FALSE;

		$display_year = $post->post_type != $this->constant( 'main_posttype' );
		$default_type = $this->default_calendar();

		$the_day = ModuleHelper::getTheDayFromPost( $post, $default_type, $this->get_the_day_constants( $display_year ) );

		return $this->get_the_day_admin_link( $the_day );
	}

	public function get_the_day_admin_link( $the_day )
	{
		return $this->get_adminpage_url( TRUE, $the_day, 'adminmenu' );
	}

	protected function _render_mainbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'mainbox';

		$this->_render_day_input( $object, $context );
	}

	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'supportedbox';

		$this->_render_day_input( $object, $context );
	}

	private function _render_day_input( $post, $context = NULL )
	{
		$calendars    = $this->list_calendars();
		$default_type = $this->default_calendar();
		$display_year = $post->post_type != $this->constant( 'main_posttype' );

		if ( 'auto-draft' == $post->post_status && $this->get_setting( 'today_in_draft' ) )
			$the_day = gEditorial\Datetime::getTheDay( NULL, $default_type );

		else if ( self::req( 'post' ) )
			$the_day = ModuleHelper::getTheDayFromPost( $post, $default_type, $this->get_the_day_constants( $display_year ) );

		else
			$the_day = ModuleHelper::getTheDayFromQuery( TRUE, $default_type, $this->get_the_day_constants( $display_year ) );

		ModuleHelper::theDaySelect( $the_day, $display_year, $default_type, $calendars );

		$the_date = ModuleHelper::getTheDayDateMySQL( $the_day, $default_type );
		$format   = gEditorial\Datetime::dateFormats( 'default' );

		// TODO: display in `table`
		// TODO: conversion buttons.
		// TODO: validation colors.
		// TODO: visible calendar type.
		// TODO: actions: button to founded `day` post-type on other calendars.
		// TODO: actions: button to add `day` post-type on other calendars.
		// TODO: actions: button to download ical.
		// TODO: must check for duplicate day and gave a green light via js.

		foreach ( $calendars as $calendar => $title ) {

			$other_day = array_merge( $the_day, [ 'cal' => $calendar ] );

			echo Core\HTML::wrap(
				Core\HTML::tag( 'a', [
					'href'   => $this->get_the_day_admin_link( $other_day ),
					'title'  => $title,
					'data'   => $other_day,
					'target' => '_blank',
				], gEditorial\Datetime::formatByCalendar( $format, $the_date, $calendar ) ),
				'field-wrap -theday-bycalendar'
			);
		}
	}

	public function manage_posts_columns( $columns )
	{
		return Core\Arraay::insert( $columns, [
			'theday' => $this->get_column_title( 'theday', 'main_posttype' ),
		], 'title', 'before' );
	}

	public function posts_custom_column( $column, $post_id )
	{
		if ( 'theday' == $column ) {

			if ( $this->check_hidden_column( $column ) )
				return;

			$the_day = ModuleHelper::getTheDayFromPost(
				WordPress\Post::get( $post_id ),
				$this->default_calendar(),
				$this->get_the_day_constants()
			);

			ModuleHelper::displayTheDay( $the_day );
		}
	}

	public function quick_edit_custom_box( $column, $posttype )
	{
		if ( 'theday' != $column )
			return FALSE;

		echo '<div class="inline-edit-col geditorial-admin-wrap-quickedit -today">';

			echo '<span class="title inline-edit-categories-label">';
				echo $this->strings_metabox_title_via_posttype( $posttype, 'quickedit' );
			echo '</span>';

			ModuleHelper::theDaySelect( [], TRUE, '', $this->list_calendars() );

		echo '</div>';

		$this->nonce_field( 'nobox' );
	}

	public function sortable_columns( $columns )
	{
		return array_merge( $columns, [ 'theday' => 'theday' ] ); // FIXME: add var query
	}

	// CAUTION: the ordering is crucial
	protected function get_the_day_constants( $year = TRUE )
	{
		$list = [
			'cal'   => $this->constant( 'metakey_cal' ),
			'month' => $this->constant( 'metakey_month' ),
			'day'   => $this->constant( 'metakey_day' ),
		];

		if ( $year )
			$list['year'] = $this->constant( 'metakey_year' );

		return $list;
	}

	// NOT USED
	protected function check_the_main_posttype( $the_day = [] )
	{
		return ModuleHelper::getPostsConnected( [
			'type'    => $this->constant( 'main_posttype' ),
			'the_day' => $the_day,
			'all'     => TRUE,
			'count'   => TRUE,
		], $this->get_the_day_constants() );
	}

	public function edit_form_after_editor( $post )
	{
		if ( gEditorial\MetaBox::checkDraftMetaBox( NULL, $post ) )
			return;

		echo $this->wrap_open( '-admin-nobox' );

			$this->actions( 'no_box', $post );

			$posttypes = $this->posttypes();
			$constants = $this->get_the_day_constants();

			$the_day = ModuleHelper::getTheDayFromPost( $post, $this->default_calendar(), $constants );

			list( $posts, $pagination ) = ModuleHelper::getPostsConnected( [
				'type'    => $posttypes,
				'the_day' => $the_day,
				'all'     => TRUE,
			], $constants );

			Core\HTML::tableList( [
				'_cb'   => 'ID',
				'title' => gEditorial\Tablelist::columnPostTitle(),
				'terms' => gEditorial\Tablelist::columnPostTerms(),
				'type'  => gEditorial\Tablelist::columnPostType(),
			], $posts, [
				'empty'  => _x( 'No posts with day information found.', 'Message', 'geditorial-today' ),
				'before' => static function ( $columns, $data, $args ) use ( $posttypes, $the_day ) {
					if ( $buttons = ModuleHelper::theDayNewConnected( $posttypes, $the_day ) )
						echo Core\HTML::wrap( implode( '&nbsp;&nbsp;', $buttons ), '-wrap-buttons' );
				},
			] );

		echo '</div>';
	}

	public function set_today_meta( $post_id, $postmeta, $constants )
	{
		foreach ( $constants as $field => $constant ) {

			// if year with no cal
			if ( 'year' == $field && ! array_key_exists( 'cal', $postmeta ) )
				unset( $postmeta['year'] );

			// if month with no cal
			if ( 'month' == $field && ! array_key_exists( 'cal', $postmeta ) )
				unset( $postmeta['month'] );

			// if only day with no month
			if ( 'day' == $field && ! array_key_exists( 'month', $postmeta ) )
				unset( $postmeta['day'] );

			if ( array_key_exists( $field, $postmeta ) ) {

				// if only cal meta, delete all
				if ( 'cal' == $field && 1 === count( $postmeta ) )
					delete_post_meta( $post_id, $constant );
				else
					update_post_meta( $post_id, $constant, $postmeta[$field] );

			} else {
				delete_post_meta( $post_id, $constant );
			}
		}
	}

	public function store_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post ) )
			return;

		// probably no input!
		if ( ! array_key_exists( 'geditorial-today-date-cal', $_POST ) )
			return;

		if ( ! $this->posttype_supported( $post->post_type )
			&& ! $this->is_posttype( 'main_posttype', $post ) )
				return;

		if ( ! $this->nonce_verify( 'supportedbox' )
			&& ! $this->nonce_verify( 'mainbox' )
			&& ! $this->nonce_verify( 'nobox' ) )
				return;

		$postmeta  = [];
		$constants = $this->get_the_day_constants( $post->post_type != $this->constant( 'main_posttype' ) );

		foreach ( $constants as $field => $constant ) {

			$key = 'geditorial-today-date-'.$field;

			if ( ! array_key_exists( $key, $_POST ) )
				continue;

			if ( ! $value = trim( $_POST[$key] ) )
				continue;

			if ( 'cal' == $field )
				$postmeta[$field] = Core\Date::sanitizeCalendar( $value, $this->default_calendar() );
			else
				$postmeta[$field] = Core\Number::translate( $value );
		}

		$this->set_today_meta( $post->ID, $postmeta, $constants );
	}

	public function the_title( $title, $post_id = NULL )
	{
		if ( $title )
			return $title;

		if ( ! $post = WordPress\Post::get( $post_id ) )
			return $title;

		if ( $this->is_posttype( 'main_posttype', $post ) ) {

			$the_day = ModuleHelper::getTheDayFromPost(
				$post,
				$this->default_calendar(),
				$this->get_the_day_constants()
			);

			return ModuleHelper::titleTheDay( $the_day );
		}

		return $title;
	}

	public function insert_theday( $content )
	{
		if ( ! $this->is_content_insert( FALSE ) )
			return;

		$the_day = ModuleHelper::getTheDayFromPost(
			WordPress\Post::get(),
			$this->default_calendar(),
			$this->get_the_day_constants()
		);

		ModuleHelper::displayTheDay( $the_day, FALSE );
	}

	public function template_include( $template )
	{
		if ( is_embed() || is_search() )
			return $template;

		if ( get_query_var( 'day_cal', FALSE )
			|| ( $this->get_setting( 'override_frontpage' ) && is_front_page() )
			|| is_post_type_archive( $this->constant( 'main_posttype' ) )
			|| is_page_template( 'today-frontpage.php' ) ) {

		} else {

			return $template;
		}

		WordPress\Theme::resetQuery( [
			'ID'          => 0, // -9999, // WTF: must be `0` to avoid notices
			'post_title'  => '&nbsp;', // avoid considering the `.-empty-title`
			'post_author' => 0,
			'post_type'   => $this->constant( 'main_posttype' ),
			'is_single'   => TRUE,
		], [], [ $this, 'the_day_content_callback' ],
			[ $this, 'the_day_title_callback' ] );

		$this->enqueue_styles();
		$this->filter( 'get_the_date', 3 );
		$this->filter_append( 'post_class', [
			'the-day',
		] );

		// return get_singular_template();
		return get_single_template();
	}

	public function prime_today( $the_day = NULL )
	{
		if ( ! empty( $this->__today ) && ! is_null( $the_day ) )
			return TRUE;

		$type      = $this->default_calendar();
		$constants = $this->get_the_day_constants();
		$the_day   = $the_day ?? ModuleHelper::getTheDayFromQuery( NULL, FALSE, $constants );
		$count     = count( array_filter( (array) $the_day ) );

		if ( ! $count ) {

			// `/{base}`
			// front-page no calendar without the year
			// display today in all calendars

			$this->__today = ModuleHelper::getTheDayAllCalendars( $this->get_calendars(), FALSE );
			$this->__posts = ModuleHelper::getDayPost( $this->__today[$type], $constants );
			$this->__home  = TRUE;

		} else if ( 1 === $count && ! empty( $the_day['cal'] ) ) {

			// `/{base}/{calendar}`
			// calendar only page without the year
			// display today in this calendar

			$_the_day = gEditorial\Datetime::getTheDay( NULL, $the_day['cal'] );

			unset( $_the_day['year'] );

			$this->__today = [ $the_day['cal'] => $_the_day ];
			$this->__home  = TRUE;

		} else if ( 3 === $count && empty( $the_day['year'] ) ) {

			// `/{base}/{calendar}/{month}/{day}`
			// day in calendar without the year
			// display the day on all calendars

			// NOTE: creates the day in this year, in lunar calendars (e.g. `islamic`) may shift a day or two!
			$datetime = ModuleHelper::getTheDayDateMySQL( $the_day, $type );

			$this->__today = ModuleHelper::getTheDayAllCalendars( $this->get_calendars(), FALSE, $datetime );
			$this->__posts = ModuleHelper::getDayPost( $this->__today[$type], $constants );

		} else if ( 2 === $count && ! empty( $the_day['month'] ) ) {

			// `/{base}/{calendar}/{month}`
			// month in calendar without the year
			// display the month on this calendar

			$this->__today = [ $the_day['cal'] => $the_day ];

		} else if ( 2 === $count && ! empty( $the_day['year'] ) ) {

			// `/{base}/{calendar}/year/{year}`
			// year in calendar without the month
			// display the year on this calendar

			$this->__today = [ $the_day['cal'] => $the_day ];

		} else if ( 3 === $count && ! empty( $the_day['year'] ) ) {

			// `/{base}/{calendar}/year/{year}/{month}`
			// month in calendar with the year
			// display the month on this calendar

			$this->__today = [ $the_day['cal'] => $the_day ];

		} else {

			// UNKNOWN

			$_type = ! empty( $the_day['cal'] ) ? $the_day['cal'] : $type;

			$this->__today = [ $_type => $the_day ];
			$this->__posts = ModuleHelper::getDayPost( $this->__today[$_type], $constants );
		}

		return TRUE;
	}

	// TODO: make clickable!!
	public function the_day_title_callback( $title = '', $post_id = 0, $the_day = NULL )
	{
		// theme-compt ID is `0`
		if ( $post_id )
			return $title;

		if ( ! $this->prime_today( $the_day ) )
			return $title;

		$titles    = [];
		$separator = ' &ndash; ';

		foreach ( $this->__today as $calendar => $_the_day )
			$titles[] = trim( ModuleHelper::titleTheDay( $_the_day, '[]', NULL ), '[]' );

		return implode( $separator, $titles );
	}

	public function the_day_content_callback( $content = '', $the_day = NULL, $posttypes = NULL )
	{
		if ( ! $this->prime_today( $the_day ) )
			return $content;

		$posttypes = $posttypes ?? get_query_var( 'day_posttype', $this->posttypes() );
		$type      = $this->default_calendar();
		$admin     = is_admin();
		$blocks    = [];

		foreach ( $this->__today as $calendar => $_the_day ) {

			// NOTE: we can query multiple days at once bu the DB takes forever to respond!

			list( $posts, $pagination ) = ModuleHelper::getPostsConnected( [
				'type'    => $posttypes,
				'the_day' => $_the_day,
				'all'     => TRUE,
			], $this->get_the_day_constants() );

			if ( ! empty( $posts ) )
				$blocks[$calendar] = $posts;
		}

		ob_start();

		foreach ( $this->__posts as $post ) {

			// has excerpt
			if ( $post->post_excerpt ) {
				$html = wpautop( WordPress\Strings::prepDescription( $post->post_excerpt, TRUE, FALSE ), FALSE );
				echo Core\HTML::wrap( $html, $this->classs( 'theday-excerpt' ) );
			}
		}

		if ( count( $blocks ) ) {

			echo '<div class="row">';

			foreach ( $blocks as $calendar => $posts ) {

				echo '<div class="col" data-calendar="'.$calendar.'">';
				echo '<ul class="-items">';

				foreach ( $posts as $post )
					echo gEditorial\ShortCode::postTitle( $post, [
						'title_tag' => 'li',
						'title_cb'  => [ $this, 'shortcode_posttitle_callback' ],
					] );

				echo '</ul>';
				echo '</div>';
			}

			echo '</div>';

		} else {

			Core\HTML::desc( _x( 'Nothing happened!', 'Message', 'geditorial-today' ) );
		}

		$_the_day   = Core\Arraay::getByKeyOrFirst( $this->__today, $type );
		$navigation = ModuleHelper::theDayNavigation( $_the_day, $type, ! $this->__home );
		$buttons    = ModuleHelper::theDayNewConnected( $posttypes, $_the_day, $this->__posts );

		if ( $navigation || $buttons ) {

			echo $admin
				? $this->wrap_open_buttons()
				: $this->wrap_open( [ '-buttons', '-navigation' ] );

				if ( $navigation )
					echo implode( '&nbsp;&nbsp;', $navigation );

				if ( $navigation && $buttons )
					echo '&nbsp;&nbsp;';

				if ( $buttons )
					echo implode( '&nbsp;&nbsp;', $buttons );

			echo $admin ? '</p>' : '</div>';
		}

		return Core\HTML::wrap( ob_get_clean(), $this->classs( 'theday-content' ) );
	}

	public function shortcode_posttitle_callback( $post, $atts, $text )
	{
		if ( ! $the_date = ModuleHelper::getTheDayFromPost( $post, $this->default_calendar() ) )
			return $text;

		if ( ! $prefix = trim( ModuleHelper::titleTheDay( $the_date, '[]', FALSE ), '[]' ) )
			return $text;

		return sprintf( '[%s]: %s', $prefix, $text );
	}

	public function get_the_date( $the_date, $d, $post, $the_day = NULL )
	{
		// theme-compt ID is `0`
		if ( $post->ID )
			return $the_date;

		if ( ! $this->prime_today( $the_day ) )
			return $the_date;

		$type = $this->default_calendar();

		// the day in default calendar
		if ( ! empty( $this->__today[$type] ) )
			return ModuleHelper::titleTheDay( $this->__today[$type], $the_date, NULL );

		// the day in first calendar
		foreach ( $this->__today as $calendar => $_the_day )
			return ModuleHelper::titleTheDay( $_the_day, $the_date, NULL );

		return $the_date;
	}

	public function post_type_link( $post_link, $post, $leavename, $sample )
	{
		if ( ! $this->is_posttype( 'main_posttype', $post ) )
			return $post_link;

		$the_day = ModuleHelper::getTheDayFromPost(
			$post,
			$this->default_calendar(),
			$this->get_the_day_constants()
		);

		return ModuleHelper::getTheDayLink( $the_day, NULL, FALSE );
	}

	// NOTE: same as `rest_base` on the main post-type
	public function get_link_base()
	{
		static $base;

		if ( empty( $base ) ) {
			$posttype = $this->constant( 'main_posttype' );
			$plural   = str_replace( '_', '-', Core\L10n::pluralize( $posttype ) );
			$base     = $this->constant( 'main_posttype'.'_rest', $this->constant( 'main_posttype'.'_archive', $plural ) );
			$base     = $this->filters( 'get_link_base', $base, $posttype );
		}

		return $base;
	}

	public function rewrite_rules_array( $rules )
	{
		$list     = [];
		$base     = $this->get_link_base();
		$posttype = $this->constant( 'main_posttype' );

		foreach ( $this->get_calendars() as $calendar ) {

			// `/{rest_base}/{cal}/{month}/{day}/{year}/{posttype}`
			// `/{rest_base}/{cal}/year/{year}/{month}/{posttype}`
			// TODO: support for week: `/{rest_base}/{cal}/week/{year}/{month}/{posttype}`

			/**
			 * - Better to always prefix with calendar.
			 * - To keep the link history intact and SEO happy.
			 * - The user may change his mind about multiple calendars.
			 */
			$prefix = '^'.$base.'/'.$calendar;

			$list[$prefix.'/([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})/(.+)/?$'] = 'index.php?post_type='.$posttype
				.'&day_cal='.$calendar
				.'&day_month=$matches[1]'
				.'&day_day=$matches[2]'
				.'&day_year=$matches[3]'
				.'&day_posttype=$matches[4]';

			$list[$prefix.'/([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})/?$'] = 'index.php?post_type='.$posttype
				.'&day_cal='.$calendar
				.'&day_month=$matches[1]'
				.'&day_day=$matches[2]'
				.'&day_year=$matches[3]';

			$list[$prefix.'/([0-9]{1,2})/([0-9]{1,2})/?$'] = 'index.php?post_type='.$posttype
				.'&day_cal='.$calendar
				.'&day_month=$matches[1]'
				.'&day_day=$matches[2]';

			$list[$prefix.'/([0-9]{1,2})/?$'] = 'index.php?post_type='.$posttype
				.'&day_cal='.$calendar
				.'&day_month=$matches[1]';

			$list[$prefix.'/year/([0-9]{4})/([0-9]{1,2})/(.+)/?$'] = 'index.php?post_type='.$posttype
				.'&day_cal='.$calendar
				.'&day_year=$matches[1]'
				.'&day_month=$matches[2]'
				.'&day_posttype=$matches[3]';

			$list[$prefix.'/year/([0-9]{4})/([0-9]{1,2})/?$'] = 'index.php?post_type='.$posttype
				.'&day_cal='.$calendar
				.'&day_year=$matches[1]'
				.'&day_month=$matches[2]';

			$list[$prefix.'/year/([0-9]{4})/?$'] = 'index.php?post_type='.$posttype
				.'&day_cal='.$calendar
				.'&day_year=$matches[1]';

			$list[$prefix.'/?$'] = 'index.php?post_type='.$posttype
				.'&day_cal='.$calendar;
		}

		return array_merge( $list, $rules );
	}

	private function _get_query_vars()
	{
		return [
			'cal'   => 'day_cal',
			'month' => 'day_month',
			'day'   => 'day_day',
			'year'  => 'day_year',
			'week'  => 'day_week',
			'type'  => 'day_posttype',
		];
	}

	private function _build_meta_query( $constants, $relation = 'OR' )
	{
		$query = [ 'meta_query' => [ 'relation' => $relation ] ];

		foreach ( $constants as $field => $constant ) {
			$query['meta_query'][$field.'_clause'] = [ 'key' => $constant, 'compare' => 'EXISTS' ];
			$query['meta_query']['orderby'][$field.'_clause'] = 'ASC';
		}

		return $query;
	}

	// MAYBE: filter by taxonomy
	public function calendars_term_url( $link, $term, $context, $datetime )
	{
		if ( ! in_array( $context, [ Services\Calendars::ICAL_TIMESPAN_CONTEXT ], TRUE ) )
			return $link;

		$the_day = gEditorial\Datetime::getTheDay( $datetime, $this->default_calendar() );

		if ( 1 === count( $the_day ) )
			return $link;

		return ModuleHelper::getTheDayLink( $the_day, NULL, FALSE );
	}

	public function calendars_post_events( $null, $post, $context )
	{
		if ( $this->is_posttype( 'main_posttype', $post ) ) {

			// The day without the year

			$events    = [];
			$default   = $this->default_calendar();
			$constants = $this->get_the_day_constants();
			$callback  = [ $this, 'calendars_get_theday_date_callback' ];

			if ( FALSE === ( $the_day = ModuleHelper::getTheDayFromPost( $post, $default, $constants ) ) )
				return $null;

			list( $items, $pagination ) = ModuleHelper::getPostsConnected( [
				'type'    => get_query_var( 'day_posttype', $this->posttypes() ),
				'the_day' => $the_day,
			], $constants );

			foreach ( $items as $item )
				$events[] = Services\Calendars::getPostEvent( $item, $context, $callback );

			return $events;

		} else if ( $this->posttype_supported( $post->post_type ) ) {

			$default   = $this->default_calendar();
			$constants = $this->get_the_day_constants();

			if ( FALSE === ( $the_day = ModuleHelper::getTheDayFromPost( $post, $default, $constants ) ) )
				return $null;

			if ( ! $the_date = ModuleHelper::getTheDayDateMySQL( $the_day, $default, FALSE ) )
				return $null;

			return Services\Calendars::getPostEvent( $post, $context, $the_date );
		}

		return $null;
	}

	public function calendars_get_theday_date_callback( $post, $context = NULL )
	{
		$default   = $this->default_calendar();
		$constants = $this->get_the_day_constants();

		if ( ! $the_day = ModuleHelper::getTheDayFromPost( $post, $default, $constants ) )
			return FALSE;

		if ( ! $the_date = ModuleHelper::getTheDayDateMySQL( $the_day, $default ) )
			return FALSE;

		return Core\Date::getObject( $the_date );
	}

	public function calendars_posttype_events( $null, $posttype, $context )
	{
		if ( $posttype !== $this->constant( 'main_posttype' ) )
			return $null;

		$events    = [];
		$default   = $this->default_calendar();
		$constants = $this->get_the_day_constants();
		$callback  = [ $this, 'calendars_get_theday_date_callback' ];

		if ( FALSE === ( $the_day = ModuleHelper::getTheDayFromQuery( FALSE, $default, $constants ) ) )
			return $null;

		list( $items, $pagination ) = ModuleHelper::getPostsConnected( [
			'type'    => get_query_var( 'day_posttype', $this->posttypes() ),
			'the_day' => $the_day,
			'all'     => TRUE,
		], $constants );

		foreach ( $items as $item )
			$events[] = Services\Calendars::getPostEvent( $item, $context, $callback );

		return $events;
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'cal'       => $this->default_calendar(),
			'day'       => '',
			'month'     => '',
			'year'      => '',
			'the_day'   => NULL,
			'posttypes' => $this->posttypes(),
			'context'   => NULL,
			'wrap'      => TRUE,
			'before'    => '',
			'after'     => '',
		], $atts, $tag ?: $this->constant( 'main_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		$the_day = $args['the_day'] ?? array_filter( Core\Arraay::keepByKeys( $args, [
			'cal'     => '',
			'day'     => '',
			'month'   => '',
			'year'    => '',
		] ) );

		if ( ! $html = $this->the_day_content_callback( '', $the_day, $args['posttypes'] ) )
			return $content;

		return gEditorial\ShortCode::wrap(
			$html,
			$this->constant( 'main_shortcode' ),
			$args
		);
	}

	public function title_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'cal'     => $this->default_calendar(),
			'day'     => '',
			'month'   => '',
			'year'    => '',
			'the_day' => NULL,
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
		], $atts, $tag ?: $this->constant( 'title_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		$the_day = $args['the_day'] ?? array_filter( Core\Arraay::keepByKeys( $args, [
			'cal'     => '',
			'day'     => '',
			'month'   => '',
			'year'    => '',
		] ) );

		if ( ! $html = trim( ModuleHelper::titleTheDay( $the_day, '[]', FALSE ), '[]' ) )
			return $content;

		return gEditorial\ShortCode::wrap(
			$html,
			$this->constant( 'title_shortcode' ),
			$args
		);
	}

	public function buttons_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'cal'        => $this->default_calendar(),
			'day'        => '',
			'month'      => '',
			'year'       => '',
			'the_day'    => NULL,
			'today'      => ! $this->__home,
			'navigation' => TRUE,
			'newposts'   => FALSE,
			'posttypes'  => $this->posttypes(),
			'context'    => NULL,
			'wrap'       => TRUE,
			'before'     => '',
			'after'      => '',
		], $atts, $tag ?: $this->constant( 'buttons_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		$the_day = $args['the_day'] ?? array_filter( Core\Arraay::keepByKeys( $args, [
			'cal'     => '',
			'day'     => '',
			'month'   => '',
			'year'    => '',
		] ) );

		$html = '';

		$navigation = $args['navigation'] ? ModuleHelper::theDayNavigation( $the_day, $this->default_calendar(), $args['today'], [] ) : [];
		$buttons    = $args['newposts'] ? ModuleHelper::theDayNewConnected( $args['posttypes'], $the_day ) : [];

		if ( $navigation || $buttons ) {

			$html.= $this->wrap_open( [ '-buttons', '-navigation' ] );

				if ( $navigation )
					$html.= implode( '&nbsp;&nbsp;', $navigation );

				if ( $navigation && $buttons )
					$html.= '&nbsp;&nbsp;';

				if ( $buttons )
					$html.= implode( '&nbsp;&nbsp;', $buttons );

			$html.= '</div>';
		}

		if ( ! $html )
			return $content;

		return gEditorial\ShortCode::wrap(
			$html,
			$this->constant( 'buttons_shortcode' ),
			$args
		);
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		$constants = $this->get_the_day_constants();
		$list      = $this->list_posttypes();
		$query     = $this->_build_meta_query( $constants );

		list( $posts, $pagination ) = gEditorial\Tablelist::getPosts(
			$query,
			[],
			array_keys( $list ),
			$this->get_sub_limit_option( $sub, 'reports' )
		);

		$pagination['before'][] = gEditorial\Tablelist::filterPostTypes( $list );
		$pagination['before'][] = gEditorial\Tablelist::filterAuthors( $list );
		$pagination['before'][] = gEditorial\Tablelist::filterSearch( $list );

		return Core\HTML::tableList( [
			'_cb'    => 'ID',
			'date'   => gEditorial\Tablelist::columnPostDate(),
			'type'   => gEditorial\Tablelist::columnPostType(),
			'title'  => gEditorial\Tablelist::columnPostTitle(),
			'theday' => [
				'title' => _x( 'The Day', 'Table Column', 'geditorial-today' ),
				'args'  => [
					'constants'    => $constants,
					'default_type' => $this->default_calendar(),
				],
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
					$the_day = ModuleHelper::getTheDayFromPost( $row,
						$column['args']['default_type'],
						$column['args']['constants']
					);

					return ModuleHelper::titleTheDay( $the_day );
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => Core\HTML::tag( 'h3', _x( 'Overview of Post with Day Information', 'Header', 'geditorial-today' ) ),
			'empty'      => _x( 'No posts with day information found.', 'Message', 'geditorial-today' ),
			'pagination' => $pagination,
		] );
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {
			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( gEditorial\Tablelist::isAction( 'reschedule_by_day' ) ) {

					$default   = $this->default_calendar();
					$constants = $this->get_the_day_constants();
					$args      = $this->_build_meta_query( $constants );

					$args['posts_per_page']   = -1;
					$args['post_type']        = self::req( 'posttype', $this->posttypes() );
					$args['suppress_filters'] = TRUE;

					$count = 0;
					$query = new \WP_Query();

					foreach ( $query->query( $args ) as $post ) {

						$the_day = ModuleHelper::getTheDayFromPost( $post, $default, $constants );
						$result  = gEditorial\Datetime::reSchedulePost( $post, $the_day, $default );

						if ( TRUE === $result )
							++$count;
					}

					WordPress\Redirect::doReferer( [
						'message' => 'scheduled',
						'count'   => $count,
					] );
				}
			}
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		Core\HTML::h3( _x( 'Today Tools', 'Header', 'geditorial-today' ) );
		echo '<table class="form-table">';

		echo '<tr><th scope="row">'._x( 'Re-schedule by Day', 'Header', 'geditorial-today' ).'</th><td>';

		echo Core\HTML::dropdown( $this->list_posttypes(), [ 'name' => 'posttype' ] );

		echo '&nbsp;&nbsp;';

		gEditorial\Settings::submitButton( 'reschedule_by_day', _x( 'Schedule', 'Setting', 'geditorial-today' ) );

		Core\HTML::desc( _x( 'Tries to re-set the date of posts based on it\'s day data.', 'Message', 'geditorial-today' ) );

		echo '</td></tr>';
		echo '</table>';
	}

	public function audit_auto_audit_save_post( $terms, $post, $taxonomy, $currents, $update )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $terms;

		if ( $exists = term_exists( $this->constant( 'term_empty_the_day' ), $taxonomy ) ) {

			$the_day = ModuleHelper::getTheDayFromPost(
				WordPress\Post::get( $post ),
				$this->default_calendar(),
				$this->get_the_day_constants()
			);

			if ( ! $the_day['day'] && ! $the_day['month'] && ! $the_day['year'] )
				$terms[] = $exists['term_id'];

			else
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );
		}

		return $terms;
	}

	public function audit_get_default_terms( $terms, $taxonomy )
	{
		return Services\Modulation::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_empty_the_day' ) => _x( 'No day', 'Default Term: Audit', 'geditorial-today' ),
		] ) : $terms;
	}

	private function _get_importer_fields( $posttype = NULL )
	{
		return [
			'today__cal'   => _x( 'Today: Calendar', 'Import Field', 'geditorial-today' ),
			'today__year'  => _x( 'Today: Year', 'Import Field', 'geditorial-today' ),
			'today__month' => _x( 'Today: Month', 'Import Field', 'geditorial-today' ),
			'today__day'   => _x( 'Today: Day', 'Import Field', 'geditorial-today' ),
			'today__full'  => _x( 'Today: Full', 'Import Field', 'geditorial-today' ),
		];
	}

	public function importer_fields( $fields, $posttype )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return $fields;

		return array_merge( $fields, $this->_get_importer_fields( $posttype ) );
	}

	public function importer_prepare( $value, $posttype, $field, $header, $raw, $source_id, $all_taxonomies )
	{
		if ( ! $this->posttype_supported( $posttype ) || empty( $value ) )
			return $value;

		if ( ! in_array( $field, array_keys( $this->_get_importer_fields( $posttype ) ) ) )
			return $value;

		switch ( $field ) {

			case 'today__cal'  : return Core\Date::sanitizeCalendar( Core\Text::trim( $value ), $this->default_calendar(), $this->list_calendars() );
			case 'today__year' :
			case 'today__month':
			case 'today__day'  : return Core\Number::translate( Core\Text::trim( $value ) );
			case 'today__full' : return ModuleHelper::parseTheFullDay( Core\Text::trim( $value ), $this->default_calendar() );
		}

		return $value;
	}

	// FIXME: use `$atts['prepared'][$field]`
	public function importer_saved( $post, $atts = [] )
	{
		if ( ! $post || ! $this->posttype_supported( $post->post_type ) )
			return;

		$default   = $this->default_calendar();
		$calendars = $this->list_calendars();
		$fields    = array_keys( $this->_get_importer_fields( $post->post_type ) );
		$postmeta  = [ 'cal' => $default ]; // `set_today_meta()` needs cal

		foreach ( $atts['map'] as $offset => $field ) {

			if ( ! in_array( $field, $fields ) )
				continue;

			if ( ! $value = trim( $atts['raw'][$offset] ) )
				continue;

			$key = str_ireplace( 'today__', '', $field );

			// will override all data!
			if ( 'full' == $key )
				$postmeta = ModuleHelper::parseTheFullDay( $value, array_key_exists( 'cal', $postmeta ) ? $postmeta['cal'] : $default );

			else if ( 'cal' == $key )
				$postmeta[$key] = Core\Date::sanitizeCalendar( $value, $default, $calendars );

			else
				$postmeta[$key] = Core\Number::translate( $value );
		}

		if ( count( $postmeta ) )
			$this->set_today_meta( $post->ID, $postmeta, $this->get_the_day_constants() );
	}
}
