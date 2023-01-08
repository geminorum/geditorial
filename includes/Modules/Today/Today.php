<?php namespace geminorum\gEditorial\Modules\Today;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Theme;
use geminorum\gEditorial\WordPress\PostType;

class Today extends gEditorial\Module
{

	protected $the_day  = [];
	protected $the_post = [];

	public static function module()
	{
		return [
			'name'  => 'today',
			'title' => _x( 'Today', 'Modules: Today', 'geditorial' ),
			'desc'  => _x( 'The day in History', 'Modules: Today', 'geditorial' ),
			'icon'  => 'calendar-alt',
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_defaults' => [
				'calendar_type',
				'calendar_list',
				[
					'field'       => 'today_in_draft',
					'title'       => _x( 'Fill The Day', 'Setting Title', 'geditorial-today' ),
					'description' => _x( 'Fills the current day info on newly drafted supported posttypes.', 'Setting Description', 'geditorial-today' ),
				],
			],
			'_dashboard' => [
				'adminmenu_roles',
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
				'display_searchform',
			],
			'_supports' => [
				'thumbnail_support',
				$this->settings_supports_option( 'day_cpt', [
					'title',
					'excerpt',
					'editorial-roles'
				] ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'day_cpt'         => 'day',
			'day_cpt_archive' => 'days',

			'metakey_cal'   => '_theday_cal',
			'metakey_day'   => '_theday_day',
			'metakey_month' => '_theday_month',
			'metakey_year'  => '_theday_year',

			'term_empty_the_day' => 'the-day-empty',
		];
	}

	protected function get_global_strings()
	{
		return [
			'misc' => [
				'featured'            => _x( 'Cover Image', 'Posttype Featured', 'geditorial-today' ),
				'excerpt_metabox'     => _x( 'Summary', 'MetaBox Title', 'geditorial-today' ),
				'meta_box_title'      => _x( 'The Day', 'MetaBox Title', 'geditorial-today' ),
				'theday_column_title' => _x( 'Day', 'Column Title', 'geditorial-today' ),
			],
			'noops' => [
				'day_cpt' => _nx_noop( 'Day', 'Days', 'Noop', 'geditorial-today' ),
			],
		];
	}

	protected function get_module_templates()
	{
		return [
			'page_cpt' => [
				'frontpage' => _x( 'Editorial: Today: Front-page', 'Template Title', 'geditorial-today' ),
			],
		];
	}

	protected function posttypes_excluded()
	{
		return Settings::posttypesExcluded( $this->constant( 'day_cpt' ) );
	}

	public function setup( $args = [] )
	{
		parent::setup();

		$this->filter( 'rewrite_rules_array' );
		$this->filter( 'post_type_link', 4 );

		if ( is_admin() )
			return;

		$this->filter( 'query_vars' );
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'day_cpt' );
		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		// TODO: main posttype must be optional
		$this->register_posttype( 'day_cpt' );

		$this->filter_module( 'audit', 'auto_audit_save_post', 5 );

		if ( ! is_admin() )
			return;

		$this->filter( 'the_title', 2, 8 );

		$this->filter_module( 'importer', 'fields', 2 );
		$this->filter_module( 'importer', 'prepare', 7 );
		$this->action_module( 'importer', 'saved', 8 );
	}

	public function template_redirect()
	{
		if ( ! is_singular( $this->posttypes() ) )
			return;

		if ( $this->get_setting( 'insert_theday' ) ) {
			add_action( $this->base.'_content_before',
				[ $this, 'insert_theday' ],
				$this->get_setting( 'insert_priority_theday', -20 )
			);

			$this->enqueue_styles(); // since no shortcode available yet!
		}
	}

	public function init_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'day_cpt' ) ) {

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
		$hook = add_submenu_page(
			'index.php',
			_x( 'Editorial Today', 'Page Title', 'geditorial-today' ),
			_x( 'My Today', 'Menu Title', 'geditorial-today' ),
			$this->role_can( 'adminmenu' ) ? 'read' : 'do_not_allow',
			$this->get_adminmenu(),
			[ $this, 'admin_today_page' ]
		);

		add_action( 'load-'.$hook, [ $this, 'admin_today_load' ] );
	}

	public function admin_today_load()
	{
		$this->register_help_tabs();
		$this->actions( 'load', self::req( 'page', NULL ) );

		$constants = $this->get_the_day_constants();

		$this->the_day = ModuleHelper::getTheDayFromQuery( FALSE, $this->default_calendar(), $constants );

		if ( 1 === count( $this->the_day ) )
			$this->the_day = ModuleHelper::getTheDayFromToday( NULL, $this->the_day['cal'] );

		$this->the_post = ModuleHelper::getDayPost( $this->the_day, $constants );

		// $this->enqueue_asset_js();
	}

	public function admin_today_page()
	{
		Settings::wrapOpen( $this->key, 'listtable' );

			Settings::headerTitle( _x( 'Editorial Today', 'Page Title', 'geditorial-today' ), FALSE );

			echo '<div id="poststuff"><div id="post-body" class="metabox-holder columns-2">';
				echo '<div id="postbox-container-2" class="postbox-container">';

					$title = trim( ModuleHelper::titleTheDay( $this->the_day ), '[]' );

					if ( ! empty( $this->the_post[0] ) )
						$title = PostType::getPostTitle( $this->the_post[0] ).' ['.$title.']';

					HTML::h3( $title );

					$html = $this->the_day_content();
					echo HTML::wrap( $html, $this->classs( 'today' ) );


				echo '</div>';
				echo '<div id="postbox-container-1" class="postbox-container">';

					// self::dump( $this->the_day );
					// self::dump( $this->the_post );

				echo '</div>';
			echo '</div></div>';


			$this->settings_signature( 'listtable' );
		Settings::wrapClose();
	}

	public function current_screen( $screen )
	{
		if ( 'post' == $screen->base ) {

			if ( $screen->post_type == $this->constant( 'day_cpt' ) ) {

				// SEE: http://make.wordpress.org/core/2012/12/01/more-hooks-on-the-edit-screen/

				$this->_save_meta_supported( $screen->post_type );

				$this->filter( 'post_updated_messages' );
				$this->action( 'edit_form_after_editor' );

				add_meta_box( $this->classs( 'supportedbox' ),
					$this->get_meta_box_title( 'day_cpt' ),
					[ $this, 'render_supportedbox_metabox' ],
					$screen,
					'side',
					'high'
				);

				if ( post_type_supports( $screen->post_type, 'excerpt' ) ) {

					remove_meta_box( 'postexcerpt', $screen, 'normal' );
					MetaBox::classEditorBox( $screen, $this->classs( 'excerpt' ) );

					add_meta_box( $this->classs( 'excerpt' ),
						$this->get_string( 'excerpt_metabox', 'day_cpt', 'misc' ),
						[ $this, 'do_metabox_excerpt' ],
						$screen,
						'after_title'
					);
				}

			} else if ( $this->posttype_supported( $screen->post_type ) ) {

				$this->_save_meta_supported( $screen->post_type );

				add_meta_box( $this->classs( 'supportedbox' ),
					$this->get_meta_box_title(),
					[ $this, 'render_supportedbox_metabox' ],
					$screen,
					'side',
					'high'
				);
			}

		} else if ( 'edit' == $screen->base ) {

			if ( $screen->post_type == $this->constant( 'day_cpt' ) ) {

				$this->filter_true( 'disable_months_dropdown', 12 );
				$this->filter( 'bulk_post_updated_messages', 2 );

				if ( $this->get_setting( 'admin_rowactions' ) )
					$this->filter( 'post_row_actions', 2 );

				$this->_save_meta_supported( $screen->post_type );
				$this->_edit_screen_supported( $screen->post_type );
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
			&& $post->post_type != $this->constant( 'day_cpt' ) )
				return $actions;

		if ( $link = $this->get_today_admin_link( $post ) )
			return Arraay::insert( $actions, [
				$this->classs() => HTML::tag( 'a', [
					'href'   => $link,
					'title'  => _x( 'View on Today', 'Title Attr', 'geditorial-today' ),
					'class'  => '-today',
					'target' => '_blank',
				], _x( 'Today', 'Action', 'geditorial-today' ) ),
			], 'view', 'before' );

		return $actions;
	}

	private function get_today_admin_link( $post )
	{
		if ( ! $this->role_can( 'adminmenu' ) )
			return FALSE;

		$display_year = $post->post_type != $this->constant( 'day_cpt' );
		$default_type = $this->default_calendar();

		$the_day = ModuleHelper::getTheDayFromPost( $post, $default_type, $this->get_the_day_constants( $display_year ) );

		return $this->get_adminmenu( FALSE, $the_day );
	}

	public function render_supportedbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			$this->actions( 'render_metabox', $post, $box, NULL, 'supportedbox' );

			$display_year = $post->post_type != $this->constant( 'day_cpt' );
			$default_type = $this->default_calendar();

			// FIXME: must first check query

			if ( 'auto-draft' == $post->post_status && $this->get_setting( 'today_in_draft' ) )
				$the_day = ModuleHelper::getTheDayFromToday( NULL, $default_type );

			else if ( self::req( 'post' ) )
				$the_day = ModuleHelper::getTheDayFromPost( $post, $default_type, $this->get_the_day_constants( $display_year ) );

			else
				$the_day = ModuleHelper::getTheDayFromQuery( TRUE, $default_type, $this->get_the_day_constants( $display_year ) );

			ModuleHelper::theDaySelect( $the_day, $display_year, $default_type, $this->get_calendars() );

			// TODO: conversion buttons
			// FIXME: must check for duplicate day and gave a green light via js

		echo '</div>';

		$this->nonce_field( 'supportedbox' );
	}

	public function do_metabox_excerpt( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		MetaBox::fieldEditorBox(
			$post->post_excerpt,
			'excerpt',
			$this->get_string( 'excerpt_metabox', 'day_cpt', 'misc' )
		);
	}

	public function manage_posts_columns( $columns )
	{
		return Arraay::insert( $columns, [
			'theday' => $this->get_column_title( 'theday', 'day_cpt' ),
		], 'title', 'before' );
	}

	public function posts_custom_column( $column_name, $post_id )
	{
		if ( 'theday' == $column_name ) {

			$the_day = ModuleHelper::getTheDayFromPost(
				PostType::getPost( $post_id ),
				$this->default_calendar(),
				$this->get_the_day_constants()
			);

			ModuleHelper::displayTheDay( $the_day );
		}
	}

	public function quick_edit_custom_box( $column_name, $posttype )
	{
		if ( 'theday' != $column_name )
			return FALSE;

		echo '<div class="inline-edit-col geditorial-admin-wrap-quickedit -today">';

			echo '<span class="title inline-edit-categories-label">';
				echo $this->get_string( 'meta_box_title', $posttype, 'misc' );
			echo '</span>';

			ModuleHelper::theDaySelect( [], TRUE, '', $this->get_calendars() );

		echo '</div>';

		$this->nonce_field( 'nobox' );
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
	protected function check_the_day_posttype( $the_day = [] )
	{
		return ModuleHelper::getPostsConnected( [
			'type'    => $this->constant( 'day_cpt' ),
			'the_day' => $the_day,
			'all'     => TRUE,
			'count'   => TRUE,
		], $this->get_the_day_constants() );
	}

	public function edit_form_after_editor( $post )
	{
		// TODO: use `check_draft_metabox()`
		if ( ! self::req( 'post' ) )
			return HTML::desc( _x( 'You can connect posts to this day once you\'ve saved it for the first time.', 'Message', 'geditorial-today' ) );

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

			if ( $buttons = ModuleHelper::theDayNewConnected( $posttypes, $the_day ) )
				echo $buttons.'<br />';

			HTML::tableList( [
				'_cb'   => 'ID',
				'title' => Tablelist::columnPostTitle(),
				'terms' => Tablelist::columnPostTerms(),
				'type'  => Tablelist::columnPostType(),
			], $posts, [
				'empty' => _x( 'No posts with day information found.', 'Message', 'geditorial-today' ),
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
			&& $this->constant( 'day_cpt' ) != $post->post_type )
				return;

		if ( ! $this->nonce_verify( 'supportedbox' )
			&& ! $this->nonce_verify( 'nobox' ) )
				return;

		$postmeta  = [];
		$constants = $this->get_the_day_constants( $post->post_type != $this->constant( 'day_cpt' ) );

		foreach ( $constants as $field => $constant ) {

			$key = 'geditorial-today-date-'.$field;

			if ( ! array_key_exists( $key, $_POST ) )
				continue;

			if ( ! $value = trim( $_POST[$key] ) )
				continue;

			if ( 'cal' == $field )
				$postmeta[$field] = Datetime::sanitizeCalendar( $value, $this->default_calendar() );
			else
				$postmeta[$field] = Number::intval( $value, FALSE );
		}

		$this->set_today_meta( $post->ID, $postmeta, $constants );
	}

	public function the_title( $title, $post_id = NULL )
	{
		if ( $title )
			return $title;

		if ( ! $post = PostType::getPost( $post_id ) )
			return $title;

		if ( $this->constant( 'day_cpt' ) == $post->post_type ) {

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
			PostType::getPost(),
			$this->default_calendar(),
			$this->get_the_day_constants()
		);

		ModuleHelper::displayTheDay( $the_day, FALSE );
	}

	public function template_include( $template )
	{
		if ( is_embed() || is_search() )
			return $template;

		if ( ( $this->get_setting( 'override_frontpage' ) && is_front_page() )
			|| is_post_type_archive( $this->constant( 'day_cpt' ) )
			|| is_page_template( 'today-frontpage.php' ) ) {

			$constants = $this->get_the_day_constants();

			if ( is_front_page() || is_page_template() ) {

				$this->the_day = ModuleHelper::getTheDayFromToday( NULL, $this->default_calendar() );

			} else {

				$this->the_day = ModuleHelper::getTheDayFromQuery( FALSE, $this->default_calendar(), $constants );

				// no day, just cal
				if ( 1 === count( $this->the_day ) )
					$this->the_day = ModuleHelper::getTheDayFromToday( NULL, $this->the_day['cal'] );
			}

			$this->the_post = ModuleHelper::getDayPost( $this->the_day, $constants );

			$title = trim( ModuleHelper::titleTheDay( $this->the_day, '[]', FALSE ), '[]' );

			if ( ! empty( $this->the_post[0] ) )
				$title = PostType::getPostTitle( $this->the_post[0] ).' ['.$title.']';

			Theme::resetQuery( [
				'ID'          => 0, // -9999, // WTF: must be `0` to avoid notices
				'post_title'  => $title,
				'post_author' => 0,
				'post_type'   => $this->constant( 'day_cpt' ),
				'is_single'   => TRUE,
			], [ $this, 'the_day_content' ] );

			$this->enqueue_styles();
			$this->filter( 'get_the_date', 3 );

			defined( 'GNETWORK_DISABLE_CONTENT_ACTIONS' )
				or define( 'GNETWORK_DISABLE_CONTENT_ACTIONS', TRUE );

			defined( 'GEDITORIAL_DISABLE_CONTENT_ACTIONS' )
				or define( 'GEDITORIAL_DISABLE_CONTENT_ACTIONS', TRUE );

			// return get_singular_template();
			return get_single_template();
		}

		return $template;
	}

	public function the_day_content( $content = '' )
	{
		// global $post;

		$posttypes = $this->posttypes();

		list( $posts, $pagination ) = ModuleHelper::getPostsConnected( [
			'type'    => get_query_var( 'day_posttype', $posttypes ),
			'the_day' => $this->the_day,
		], $this->get_the_day_constants() );

		ob_start();

		if ( ! empty( $this->the_post[0] ) ) {

			// has excerpt
			if ( $this->the_post[0]->post_excerpt ) {
				$html = wpautop( Helper::prepDescription( $this->the_post[0]->post_excerpt, TRUE, FALSE ), FALSE );
				echo HTML::wrap( $html, $this->classs( 'theday-excerpt' ) );
			}
		}

		// TODO: next/prev day buttons
		// TODO: next/perv month button

		$buttons = ModuleHelper::theDayNewConnected( $posttypes, $this->the_day, ( empty( $this->the_post[0] ) ? TRUE : $this->the_post[0]->ID ) );

		if ( $buttons )
			echo $buttons.'<br />';

		if ( count( $posts ) ) {

			echo '<ul class="-items">';
			foreach ( $posts as $post )
				echo ShortCode::postTitle( $post, [ 'title_tag' => 'li' ] );
			echo '</ul>';

		} else {

			HTML::desc( _x( 'Nothing happened!', 'Message', 'geditorial-today' ) );
		}

		if ( ! is_admin() ) {

			foreach ( $posttypes as $posttype )
				$hiddens['post_type[]'] = $posttype;

			echo $this->get_search_form( $hiddens );
		}

		return HTML::wrap( ob_get_clean(), $this->classs( 'theday-content' ) );
	}

	public function get_the_date( $the_date, $d, $post )
	{
		return ModuleHelper::titleTheDay( $this->the_day );
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

	// FIXME: ADOPT THIS
	public function pre_get_posts( &$wp_query )
	{
		if ( is_admin() || ! $wp_query->is_main_query() )
			return;

		// a map from the timespan string to actual hours array
		$hours = [
			'morning'   => range( 6, 11 ),
			'afternoon' => range( 12, 17 ),
			'evening'   => range( 18, 23 ),
			'night'     => range( 0, 5 ),
		];

		$customdate = $wp_query->get( 'customdate' );
		$timespan   = $wp_query->get( 'timespan' );

		// if the vars are not set, this is not a query we're interested in
		if ( ! $customdate || ! $timespan )
			return;

		$timestamp = strtotime( $customdate );

		// do nothing if have the wrong values
		if ( ! $timestamp || ! isset( $hours[$timespan] ) )
			return;

		// reset query variables, because `WP_Query` does nothing with
		// 'customdate' or 'timespan', so it's better remove them
		$wp_query->init();

		// set date query based on custom vars
		$wp_query->set( 'date_query', [
			'relation' => 'AND',
			[
				'year'  => date( 'Y', $timestamp ),
				'month' => date( 'm', $timestamp ),
				'day'   => date( 'd', $timestamp ),
			],
			[
				'hour'    => $hours[$timespan],
				'compare' => 'IN'
			],
		] );
	}

	public function post_type_link( $post_link, $post, $leavename, $sample )
	{
		if ( $post->post_type == $this->constant( 'day_cpt' ) ) {

			$the_day = ModuleHelper::getTheDayFromPost(
				$post,
				$this->default_calendar(),
				$this->get_the_day_constants()
			);

			return ModuleHelper::getTheDayLink( $the_day );
		}

		return $post_link;
	}

	// `/cal/month/day/year/posttype`
	public function rewrite_rules_array( $rules )
	{
		$new_rules = [];
		$day_cpt   = $this->constant( 'day_cpt' );
		$pattern = '([^/]+)';

		foreach ( $this->get_setting( 'calendar_list', [] ) as $cal ) {

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

	private function build_meta_query( $constants, $relation = 'OR' )
	{
		$query = [ 'meta_query' => [ 'relation' => $relation ] ];

		foreach ( $constants as $field => $constant ) {
			$query['meta_query'][$field.'_clause'] = [ 'key' => $constant, 'compare' => 'EXISTS' ];
			$query['meta_query']['orderby'][$field.'_clause'] = 'ASC';
		}

		return $query;
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		$constants = $this->get_the_day_constants();
		$list      = $this->list_posttypes();
		$query     = $this->build_meta_query( $constants );

		list( $posts, $pagination ) = Tablelist::getPosts( $query, [], array_keys( $list ), $this->get_sub_limit_option( $sub ) );

		$pagination['before'][] = Tablelist::filterPostTypes( $list );
		$pagination['before'][] = Tablelist::filterAuthors( $list );
		$pagination['before'][] = Tablelist::filterSearch( $list );

		return HTML::tableList( [
			'_cb'   => 'ID',
			'ID'    => Tablelist::columnPostID(),
			'date'  => Tablelist::columnPostDate(),
			'type'  => Tablelist::columnPostType(),
			'title' => Tablelist::columnPostTitle(),
			'theday' => [
				'title'    => _x( 'The Day', 'Table Column', 'geditorial-today' ),
				'args'     => [
					'constants'    => $constants,
					'default_type' => $this->default_calendar(),
				],
				'callback' => static function( $value, $row, $column, $index, $key, $args ) {
					$the_day = ModuleHelper::getTheDayFromPost( $row,
						$column['args']['default_type'],
						$column['args']['constants'] );

					return ModuleHelper::titleTheDay( $the_day );
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of Post with Day Information', 'Header', 'geditorial-today' ) ),
			'empty'      => _x( 'No posts with day information found.', 'Message', 'geditorial-today' ),
			'pagination' => $pagination,
		] );
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {
			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( Tablelist::isAction( 'reschedule_by_day' ) ) {

					$default   = $this->default_calendar();
					$constants = $this->get_the_day_constants();
					$args      = $this->build_meta_query( $constants );

					$args['posts_per_page']   = -1;
					$args['post_type']        = self::req( 'posttype', $this->posttypes() );
					$args['suppress_filters'] = TRUE;

					$count = 0;
					$query = new \WP_Query();

					foreach ( $query->query( $args ) as $post ) {

						$the_day = ModuleHelper::getTheDayFromPost( $post, $default, $constants );
						$result  = Datetime::reSchedulePost( $post, $the_day );

						if ( TRUE === $result )
							$count++;
					}

					WordPress::redirectReferer( [
						'message' => 'scheduled',
						'count'   => $count,
					] );
				}
			}
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		HTML::h3( _x( 'Today Tools', 'Header', 'geditorial-today' ) );
		echo '<table class="form-table">';

		echo '<tr><th scope="row">'._x( 'Re-schedule by Day', 'Header', 'geditorial-today' ).'</th><td>';

		echo HTML::dropdown( $this->list_posttypes(), [ 'name' => 'posttype' ] );

		echo '&nbsp;&nbsp;';

		Settings::submitButton( 'reschedule_by_day', _x( 'Schedule', 'Setting', 'geditorial-today' ) );

		HTML::desc( _x( 'Tries to re-set the date of posts based on it\'s day data.', 'Message', 'geditorial-today' ) );

		echo '</td></tr>';
		echo '</table>';
	}

	public function audit_auto_audit_save_post( $terms, $post, $taxonomy, $currents, $update )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $terms;

		if ( $exists = term_exists( $this->constant( 'term_empty_the_day' ), $taxonomy ) ) {

			$the_day = ModuleHelper::getTheDayFromPost(
				PostType::getPost( $post ),
				$this->default_calendar(),
				$this->get_the_day_constants()
			);

			if ( ! $the_day['day'] && ! $the_day['month'] && ! $the_day['year'] )
				$terms[] = $exists['term_id'];

			else
				$terms = Arraay::stripByValue( $terms, $exists['term_id'] );
		}

		return $terms;
	}

	public function audit_get_default_terms( $terms, $taxonomy )
	{
		if ( $taxonomy === gEditorial()->constant( 'audit', 'audit_tax', 'audit_attribute' ) )
			$terms = array_merge( $terms, [
				$this->constant( 'term_empty_the_day' ) => _x( 'No day', 'Default Term: Audit', 'geditorial-today' ),
			] );

		return $terms;
	}

	private function get_importer_fields( $posttype = NULL )
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

		return array_merge( $fields, $this->get_importer_fields( $posttype ) );
	}

	public function importer_prepare( $value, $posttype, $field, $raw, $source_id, $taxonomies, $key )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return $value;

		if ( ! in_array( $field, array_keys( $this->get_importer_fields( $posttype ) ) ) )
			return $value;

		switch ( $field ) {

			case 'today__cal': return Datetime::sanitizeCalendar( trim( $value ), $this->default_calendar() );
			case 'today__year':
			case 'today__month':
			case 'today__day': return Number::intval( trim( $value ), FALSE );
			case 'today__full': return ModuleHelper::parseTheFullDay( trim( $value ), $this->default_calendar() );
		}

		return $value;
	}

	// FIXME: use `$prepared[$field]`
	public function importer_saved( $post, $data, $prepared, $field_map, $source_id, $attach_id, $terms_all, $raw )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return;

		$default  = $this->default_calendar();
		$fields   = array_keys( $this->get_importer_fields( $post->post_type ) );
		$postmeta = [ 'cal' => $default ]; // `set_today_meta()` needs cal

		foreach ( $field_map as $offset => $field ) {

			if ( ! in_array( $field, $fields ) )
				continue;

			if ( ! $value = trim( $raw[$offset] ) )
				continue;

			$key = str_ireplace( 'today__', '', $field );

			// will override all data!
			if ( 'full' == $key )
				$postmeta = ModuleHelper::parseTheFullDay( $value, array_key_exists( 'cal', $postmeta ) ? $postmeta['cal'] : $default );

			else if ( 'cal' == $key )
				$postmeta[$key] = Datetime::sanitizeCalendar( $value, $default );

			else
				$postmeta[$key] = Number::intval( $value, FALSE );
		}

		if ( count( $postmeta ) )
			$this->set_today_meta( $post->ID, $postmeta, $this->get_the_day_constants() );
	}
}
