<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\User;

class Settings extends Core\Base
{

	const REPORTS  = 'geditorial-reports';
	const SETTINGS = 'geditorial-settings';
	const TOOLS    = 'geditorial-tools';

	// better to use `$this->get_module_url()`
	public static function subURL( $sub = FALSE, $context = 'reports', $extra = [] )
	{
		switch ( $context ) {
			case 'reports' : $url = self::reportsURL();  break;
			case 'settings': $url = self::settingsURL(); break;
			case 'tools'   : $url = self::toolsURL();    break;
			default        : $url = URL::current();
		}

		return add_query_arg( array_merge( [
			'sub' => $sub,
		], $extra ), $url );
	}

	// FIXME: MUST DEPRICATE
	public static function reportsURL( $full = TRUE, $dashboard = FALSE )
	{
		$relative = 'index.php?page='.self::REPORTS;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	// FIXME: MUST DEPRICATE
	public static function settingsURL( $full = TRUE )
	{
		$relative = 'admin.php?page='.self::SETTINGS;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	// FIXME: MUST DEPRICATE: problem with dashboard
	public static function toolsURL( $full = TRUE, $dashboard = FALSE )
	{
		$relative = $dashboard ? 'index.php?page='.self::TOOLS : 'admin.php?page='.self::TOOLS;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	public static function isReports( $screen = NULL )
	{
		if ( is_null( $screen ) )
			$screen = get_current_screen();

		if ( ! empty( $screen->base ) && Text::has( $screen->base, self::REPORTS ) )
			return TRUE;

		return FALSE;
	}

	public static function isSettings( $screen = NULL )
	{
		if ( is_null( $screen ) )
			$screen = get_current_screen();

		if ( ! empty( $screen->base ) && Text::has( $screen->base, self::SETTINGS ) )
			return TRUE;

		return FALSE;
	}

	public static function isTools( $screen = NULL )
	{
		if ( is_null( $screen ) )
			$screen = get_current_screen();

		if ( ! empty( $screen->base ) && Text::has( $screen->base, self::TOOLS ) )
			return TRUE;

		return FALSE;
	}

	public static function isDashboard( $screen = NULL )
	{
		if ( is_null( $screen ) )
			$screen = get_current_screen();

		if ( ! empty( $screen->base ) && Text::has( $screen->base, 'dashboard' ) )
			return TRUE;

		return FALSE;
	}

	public static function priorityOptions( $format = TRUE )
	{
		return
			array_reverse( Arraay::range( -100, -1000, 100, $format ), TRUE ) +
			array_reverse( Arraay::range( -10, -100, 10, $format ), TRUE ) +
			Arraay::range( 0, 100, 10, $format ) +
			Arraay::range( 100, 1000, 100, $format );
	}

	public static function minutesOptions()
	{
		return [
			'5'    => _x( '5 Minutes', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'10'   => _x( '10 Minutes', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'15'   => _x( '15 Minutes', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'30'   => _x( '30 Minutes', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'60'   => _x( '60 Minutes', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'120'  => _x( '2 Hours', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'180'  => _x( '3 Hours', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'240'  => _x( '4 Hours', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'480'  => _x( '8 Hours', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'1440' => _x( '24 Hours', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public static function supportsOptions()
	{
		return [
			'title'           => _x( 'Title', 'Settings: Option: PostType Support', GEDITORIAL_TEXTDOMAIN ),
			'editor'          => _x( 'Editor', 'Settings: Option: PostType Support', GEDITORIAL_TEXTDOMAIN ),
			'excerpt'         => _x( 'Excerpt', 'Settings: Option: PostType Support', GEDITORIAL_TEXTDOMAIN ),
			'author'          => _x( 'Author', 'Settings: Option: PostType Support', GEDITORIAL_TEXTDOMAIN ),
			'thumbnail'       => _x( 'Thumbnail', 'Settings: Option: PostType Support', GEDITORIAL_TEXTDOMAIN ),
			'comments'        => _x( 'Comments', 'Settings: Option: PostType Support', GEDITORIAL_TEXTDOMAIN ),
			'trackbacks'      => _x( 'Trackbacks', 'Settings: Option: PostType Support', GEDITORIAL_TEXTDOMAIN ),
			'custom-fields'   => _x( 'Custom Fields', 'Settings: Option: PostType Support', GEDITORIAL_TEXTDOMAIN ),
			'post-formats'    => _x( 'Post Formats', 'Settings: Option: PostType Support', GEDITORIAL_TEXTDOMAIN ),
			'revisions'       => _x( 'Revisions', 'Settings: Option: PostType Support', GEDITORIAL_TEXTDOMAIN ),
			'page-attributes' => _x( 'Post Attributes', 'Settings: Option: PostType Support', GEDITORIAL_TEXTDOMAIN ),
			'date-picker'     => _x( 'Persian Date: Date Picker', 'Settings: Option: PostType Support', GEDITORIAL_TEXTDOMAIN ),
			'editorial-meta'  => _x( 'Editorial: Meta Fields', 'Settings: Option: PostType Support', GEDITORIAL_TEXTDOMAIN ),
			'editorial-roles' => _x( 'Editorial: Custom Roles', 'Settings: Option: PostType Support', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public static function showOptionNone( $string = NULL )
	{
		if ( $string )
			return sprintf( _x( '&mdash; Select %s &mdash;', 'Settings: Dropdown Select Option None', GEDITORIAL_TEXTDOMAIN ), $string );

		return _x( '&mdash; Select &mdash;', 'Settings: Dropdown Select Option None', GEDITORIAL_TEXTDOMAIN );
	}

	public static function showOptionAll( $string = NULL )
	{
		if ( $string )
			return sprintf( _x( '&mdash; All %s &mdash;', 'Settings: Dropdown Select Option All', GEDITORIAL_TEXTDOMAIN ), $string );

		return _x( '&mdash; All &mdash;', 'Settings: Dropdown Select Option All', GEDITORIAL_TEXTDOMAIN );
	}

	public static function fieldSeparate( $string = 'from' )
	{
		switch ( $string ) {
			case 'from': $string = _x( 'from', 'Settings: Field Separate', GEDITORIAL_TEXTDOMAIN ); break;
			case 'into': $string = _x( 'into', 'Settings: Field Separate', GEDITORIAL_TEXTDOMAIN ); break;
			case 'in'  : $string = _x( 'in', 'Settings: Field Separate', GEDITORIAL_TEXTDOMAIN );   break;
			case 'to'  : $string = _x( 'to', 'Settings: Field Separate', GEDITORIAL_TEXTDOMAIN );   break;
			case 'as'  : $string = _x( 'as', 'Settings: Field Separate', GEDITORIAL_TEXTDOMAIN );   break;
			case 'or'  : $string = _x( 'or', 'Settings: Field Separate', GEDITORIAL_TEXTDOMAIN );   break;
		}

		printf( '<span class="-field-sep">&nbsp;&mdash; %s &mdash;&nbsp;</span>', $string );
	}

	public static function fieldSection( $title, $description = FALSE, $tag = 'h3' )
	{
		echo HTML::tag( $tag, $title );

		HTML::desc( $description );
	}

	public static function infoP2P()
	{
		return _x( 'Connected via <code>P2P</code>', 'Settings: Setting Info', GEDITORIAL_TEXTDOMAIN );
	}

	public static function getSetting_editor_button()
	{
		return [
			'field'   => 'editor_button',
			'title'   => _x( 'Editor Button', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => '1',
		];
	}

	public static function getSetting_shortcode_support()
	{
		return [
			'field'   => 'shortcode_support',
			'title'   => _x( 'Default Shortcodes', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => '0',
		];
	}

	public static function getSetting_thumbnail_support()
	{
		return [
			'field'   => 'thumbnail_support',
			'title'   => _x( 'Default Image Sizes', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => '0',
		];
	}

	public static function getSetting_markdown_support()
	{
		return [
			'field'   => 'markdown_support',
			'title'   => _x( 'Markdown Support', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => '0',
		];
	}

	public static function getSetting_multiple_instances()
	{
		return [
			'field'   => 'multiple_instances',
			'title'   => _x( 'Multiple Instances', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => '0',
		];
	}

	public static function getSetting_autolink_terms()
	{
		return [
			'field'       => 'autolink_terms',
			'title'       => _x( 'Autolink Terms', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Trying to link the terms titles in the content.', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => '0',
		];
	}

	public static function getSetting_rewrite_prefix()
	{
		return [
			'field'       => 'rewrite_prefix',
			'type'        => 'text',
			'title'       => _x( 'URL Base Prefix', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'String before the permalink structure', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => '',
			'dir'         => 'ltr',
			'placeholder' => 'wiki',
		];
	}

	public static function getSetting_redirect_archives()
	{
		return [
			'field'       => 'redirect_archives',
			'type'        => 'url',
			'title'       => _x( 'Redirect Archives', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Redirect Post Type Archives to a URL', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => 'http://example.com/archives/',
		];
	}

	public static function getSetting_comment_status()
	{
		return [
			'field'   => 'comment_status',
			'type'    => 'select',
			'title'   => _x( 'Comment Status', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => 'closed',
			'values'  => [
				'open'   => _x( 'Open', 'Settings: Setting Option', GEDITORIAL_TEXTDOMAIN ),
				'closed' => _x( 'Closed', 'Settings: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			],
		];
	}

	public static function getSetting_post_status()
	{
		return [
			'field'   => 'post_status',
			'type'    => 'select',
			'title'   => _x( 'Post Status', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => 'pending',
			'values'  => PostType::getStatuses(),
		];
	}

	public static function getSetting_post_type()
	{
		return [
			'field'   => 'post_type',
			'type'    => 'select',
			'title'   => _x( 'Post Type', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => 'post',
			'values'  => PostType::get( 2 ),
			'exclude' => [ 'attachment' ],
		];
	}

	public static function getSetting_insert_content()
	{
		return [
			'field'       => 'insert_content',
			'type'        => 'select',
			'title'       => _x( 'Insert in Content', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Puts automatically in the content', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => 'none',
			'values'      => [
				'none'   => _x( 'No', 'Settings: Setting Option', GEDITORIAL_TEXTDOMAIN ),
				'before' => _x( 'Before', 'Settings: Setting Option', GEDITORIAL_TEXTDOMAIN ),
				'after'  => _x( 'After', 'Settings: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			],
		];
	}

	public static function getSetting_insert_content_before()
	{
		$args = self::getSetting_insert_content();

		$args['field'] = 'insert_content_before';
		unset( $args['values'], $args['type'], $args['default'] );

		return $args;
	}

	public static function getSetting_insert_content_after()
	{
		$args = self::getSetting_insert_content();

		$args['field'] = 'insert_content_after';
		unset( $args['values'], $args['type'], $args['default'] );

		return $args;
	}

	public static function getSetting_insert_cover()
	{
		return [
			'field' => 'insert_cover',
			'title' => _x( 'Insert Cover', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	// FIXME: DEPRICATED: USE: `settings_insert_priority_option()`
	public static function getSetting_insert_priority()
	{
		return [
			'field'   => 'insert_priority',
			'type'    => 'priority',
			'title'   => _x( 'Insert Priority', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => '10',
		];
	}

	public static function getSetting_before_content()
	{
		return [
			'field'       => 'before_content',
			'type'        => 'textarea-quicktags',
			'title'       => _x( 'Before Content', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Adds <code>HTML</code> to the start of all the supported posttypes', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public static function getSetting_after_content()
	{
		return [
			'field'       => 'after_content',
			'type'        => 'textarea-quicktags',
			'title'       => _x( 'After Content', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Adds <code>HTML</code> to the end of all the supported posttypes', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public static function getSetting_admin_ordering()
	{
		return [
			'field'       => 'admin_ordering',
			'title'       => _x( 'Ordering', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Enhance item ordering on admin edit pages.', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => '1',
		];
	}

	public static function getSetting_admin_restrict()
	{
		return [
			'field'       => 'admin_restrict',
			'title'       => _x( 'List Restrictions', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Enhance restrictions on admin edit pages.', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public static function getSetting_admin_bulkactions()
	{
		return [
			'field'       => 'admin_bulkactions',
			'title'       => _x( 'Bulk Actions', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Enhance bulk actions on admin edit pages.', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public static function getSetting_adminbar_summary()
	{
		return [
			'field'       => 'adminbar_summary',
			'title'       => _x( 'Adminbar Summary', 'Setting: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Summary for the current item as a node in adminbar', 'Setting: Setting Description', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public static function getSetting_dashboard_widgets()
	{
		return [
			'field'       => 'dashboard_widgets',
			'title'       => _x( 'Dashboard Widgets', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Enhance admin dashboard with customized widgets', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public static function getSetting_dashboard_authors()
	{
		return [
			'field'       => 'dashboard_authors',
			'title'       => _x( 'Dashboard Authors', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Displays the author column on dashboard widget', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public static function getSetting_dashboard_count()
	{
		return [
			'field'       => 'dashboard_count',
			'type'        => 'number',
			'title'       => _x( 'Dashboard Count', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Total rows of items on dashboard widget', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => 10,
		];
	}

	public static function getSetting_summary_scope()
	{
		return [
			'field'       => 'summary_scope',
			'type'        => 'select',
			'title'       => _x( 'Summary Scope', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'User scope for the content summary', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => 'all',
			'values'      => [
				'all'     => _x( 'All Users', 'Settings: Setting Option', GEDITORIAL_TEXTDOMAIN ),
				'current' => _x( 'Current User', 'Settings: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			],
		];
	}

	public static function getSetting_count_not()
	{
		return [
			'field'       => 'count_not',
			'title'       => _x( 'Count Not', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Count not affacted items in content summary', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public static function getSetting_posttype_feeds()
	{
		return [
			'field'       => 'posttype_feeds',
			'title'       => _x( 'Feeds', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Supporting feeds on the posttype', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public static function getSetting_posttype_pages()
	{
		return [
			'field'       => 'posttype_pages',
			'title'       => _x( 'Pages', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Supporting pagination on the posttype', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public static function getSetting_display_searchform()
	{
		return [
			'field'       => 'display_searchform',
			'title'       => _x( 'Display Search Form', 'Setting: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Appends a search form to the content generated on front-end.', 'Setting: Setting Description', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public static function getSetting_calendar_type()
	{
		return [
			'field'   => 'calendar_type',
			'title'   => _x( 'Default Calendar', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'type'    => 'select',
			'default' => 'gregorian',
			'values'  => Helper::getDefualtCalendars( TRUE ),
			'section' => $section,
		];
	}

	public static function getSetting_calendar_list()
	{
		return [
			'field'   => 'calendar_list',
			'title'   => _x( 'Calendar List', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'type'    => 'checkbox',
			'default' => [ 'gregorian' ],
			'values'  => Helper::getDefualtCalendars( TRUE ),
			'section' => $section,
		];
	}

	public static function getSetting_supported_roles()
	{
		return [
			'field'   => 'supported_roles',
			'type'    => 'checkbox',
			'title'   => _x( 'Supported Roles', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => [],
			'exclude' => [ 'administrator' ],
			'values'  => User::getAllRoleList(),
			'section' => $section,
		];
	}

	public static function getSetting_excluded_roles()
	{
		return [
			'field'   => 'excluded_roles',
			'type'    => 'checkbox',
			'title'   => _x( 'Excluded Roles', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => [],
			'exclude' => [ 'administrator' ],
			'values'  => User::getAllRoleList(),
			'section' => $section,
		];
	}

	public static function getSetting_adminmenu_roles()
	{
		return [
			'field'   => 'adminmenu_roles',
			'type'    => 'checkbox',
			'title'   => _x( 'Admin Menu Roles', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => [],
			'exclude' => [ 'administrator', 'subscriber' ],
			'values'  => User::getAllRoleList(),
			'section' => $section,
		];
	}

	public static function getSetting_adminbar_roles()
	{
		return [
			'field'   => 'adminbar_roles',
			'type'    => 'checkbox',
			'title'   => _x( 'Adminbar Roles', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => [],
			'exclude' => [ 'administrator', 'subscriber' ],
			'values'  => User::getAllRoleList(),
			'section' => $section,
		];
	}

	public static function sub( $default = 'general' )
	{
		return trim( self::req( 'sub', $default ) );
	}

	public static function wrapOpen( $sub = 'general', $base = 'geditorial', $page = 'settings' )
	{
		echo '<div id="'.$base.'-'.$page.'" class="wrap '.$base.'-admin-wrap '.$base.'-'.$page.' '.$base.'-'.$page.'-'.$sub.' sub-'.$sub.'">';
	}

	public static function wrapClose()
	{
		echo '<div class="clear"></div></div>';
	}

	public static function wrapError( $message, $title = NULL )
	{
		self::wrapOpen( 'error' );
			self::headerTitle( $title );
			echo $message;
		self::wrapClose();
	}

	// @REF: `get_admin_page_title()`
	public static function headerTitle( $title = NULL, $back = NULL, $to = NULL, $icon = '', $count = FALSE, $search = FALSE )
	{
		$before = $class = '';

		if ( is_null( $title ) )
			$title = _x( 'Editorial', 'Settings', GEDITORIAL_TEXTDOMAIN );

		// FIXME: get cap from settings module
		if ( is_null( $back ) && current_user_can( 'manage_options' ) )
			$back = self::settingsURL();

		if ( is_null( $to ) )
			$to = _x( 'Back to Editorial', 'Settings', GEDITORIAL_TEXTDOMAIN );

		if ( is_array( $icon ) )
			$before = gEditorial()->icon( $icon[1], $icon[0] );

		else if ( $icon )
			$class = ' dashicons-before dashicons-'.$icon;

		$extra = '';

		if ( FALSE !== $count )
			$extra .= sprintf( ' <span class="title-count settings-title-count">%s</span>', Number::format( $count ) );

		printf( '<h1 class="wp-heading-inline settings-title'.$class.'">%s%s%s</h1>', $before, $title, $extra );

		// echo '<span class="subtitle">'.'</span>';

		$action = ' <a href="%s" class="page-title-action settings-title-action">%s</a>';

		if ( $back && is_array( $back ) )
			foreach ( $back as $back_link => $back_title )
				printf( $action, $back_link, $back_title );

		else if ( $back )
			printf( $action, $back, $to );

		if ( $search )
			echo HTML::tag( 'input', [
				'type'        => 'search',
				'class'       => [ 'settings-title-search', '-search', 'hide-if-no-js' ],
				'placeholder' => _x( 'Search …', 'Settings: Search Placeholder', GEDITORIAL_TEXTDOMAIN ),
				'autofocus'   => 'autofocus',
			] );

		echo '<hr class="wp-header-end">';
	}

	public static function message( $messages = NULL )
	{
		if ( is_null( $messages ) )
			$messages = self::messages();

		if ( isset( $_GET['message'] ) ) {

			if ( isset( $messages[$_GET['message']] ) )
				echo $messages[$_GET['message']];
			else
				echo HTML::warning( $_GET['message'] );

			$_SERVER['REQUEST_URI'] = remove_query_arg( [ 'message', 'count' ], $_SERVER['REQUEST_URI'] );
		}
	}

	public static function messages()
	{
		return [
			'resetting' => self::success( _x( 'Settings reset.', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'optimized' => self::success( _x( 'Tables optimized.', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'updated'   => self::success( _x( 'Settings updated.', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'purged'    => self::success( _x( 'Data purged.', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'maked'     => self::success( _x( 'File/Folder created.', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'error'     => self::error( _x( 'Error occurred!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'wrong'     => self::error( _x( 'Something\'s wrong!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'nochange'  => self::error( _x( 'No item changed!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'noadded'   => self::error( _x( 'No item added!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'noaccess'  => self::error( _x( 'You do not have the access!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'converted' => self::counted( _x( '%s items(s) converted!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'imported'  => self::counted( _x( '%s items(s) imported!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'created'   => self::counted( _x( '%s items(s) created!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'deleted'   => self::counted( _x( '%s items(s) deleted!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'cleaned'   => self::counted( _x( '%s items(s) cleaned!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'changed'   => self::counted( _x( '%s items(s) changed!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'emptied'   => self::counted( _x( '%s items(s) emptied!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'closed'    => self::counted( _x( '%s items(s) closed!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'ordered'   => self::counted( _x( '%s items(s) re-ordered!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'synced'    => self::counted( _x( '%s items(s) synced!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'huh'       => HTML::error( self::huh( self::req( 'huh', NULL ) ) ),
		];
	}

	public static function messageExtra()
	{
		$extra = [];

		if ( isset( $_REQUEST['count'] ) )
			$extra[] = sprintf( _x( '%s Counted!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ),
				Number::format( $_REQUEST['count'] ) );

		return count( $extra ) ? ' ('.implode( ', ', $extra ).')' : '';
	}

	public static function error( $message, $dismissible = TRUE )
	{
		return HTML::error( $message.self::messageExtra(), $dismissible );
	}

	public static function success( $message, $dismissible = TRUE )
	{
		return HTML::success( $message.self::messageExtra(), $dismissible );
	}

	public static function warning( $message, $dismissible = TRUE )
	{
		return HTML::warning( $message.self::messageExtra(), $dismissible );
	}

	public static function info( $message, $dismissible = TRUE )
	{
		return HTML::info( $message.self::messageExtra(), $dismissible );
	}

	public static function getButtonConfirm( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Are you sure? This operation can not be undone.', 'Settings: Confirm', GEDITORIAL_TEXTDOMAIN );

		return [ 'onclick' => sprintf( 'return confirm(\'%s\')', esc_attr( $message ) ) ];
	}

	public static function submitButton( $name = 'submit', $text = NULL, $primary = FALSE, $atts = [] )
	{
		$link    = FALSE;
		$classes = [ '-button', 'button' ];

		if ( is_null( $text ) )
			$text = 'reset' == $name
				? _x( 'Reset Settings', 'Settings: Button', GEDITORIAL_TEXTDOMAIN )
				: _x( 'Save Changes', 'Settings: Button', GEDITORIAL_TEXTDOMAIN );

		if ( TRUE === $atts )
			$atts = self::getButtonConfirm();

		else if ( ! is_array( $atts ) )
			$atts = [];

		if ( 'primary' == $primary )
			$primary = TRUE;

		else if ( 'link' == $primary )
			$link = TRUE;

		if ( TRUE === $primary )
			$classes[] = 'button-primary';

		else if ( $primary && 'link' != $primary )
			$classes[] = 'button-'.$primary;

		if ( $link )
			echo HTML::tag( 'a', array_merge( $atts, [
				'href'  => $name,
				'class' => $classes,
			] ), $text );

		else
			echo HTML::tag( 'input', array_merge( $atts, [
				'type'    => 'submit',
				'name'    => $name,
				'id'      => $name,
				'value'   => $text,
				'class'   => $classes,
				'default' => TRUE === $primary,
			] ) );

		echo '&nbsp;&nbsp;';
	}

	public static function counted( $message = NULL, $count = NULL, $class = 'notice-success' )
	{
		if ( is_null( $message ) )
			$message = _x( '%s Counted!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN );

		if ( is_null( $count ) )
			$count = self::req( 'count', 0 );

		return HTML::notice( sprintf( $message, Number::format( $count ) ), $class.' fade' );
	}

	public static function cheatin( $message = NULL )
	{
		echo HTML::error( is_null( $message ) ? _x( 'Cheatin&#8217; uh?', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) : $message );
	}

	public static function huh( $message = NULL )
	{
		if ( $message )
			return sprintf( _x( 'huh? %s', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ), $message );

		return _x( 'huh?', 'Settings: Message', GEDITORIAL_TEXTDOMAIN );
	}

	public static function headerNav( $uri = '', $active = '', $subs = [], $prefix = 'nav-tab-', $tag = 'h3' )
	{
		HTML::headerNav( $uri, $active, $subs, $prefix, $tag );
	}

	// @SOURCE: `add_settings_section()`
	public static function addModuleSection( $page, $atts = [] )
	{
		global $wp_settings_sections;

		$args = self::atts( [
			'id'            => FALSE,
			'title'         => FALSE,
			'callback'      => '__return_false',
			'section_class' => '',
		], $atts );

		if ( ! $args['id'] )
			return FALSE;

		return $wp_settings_sections[$page][$args['id']] = $args;
	}

	// @SOURCE: `do_settings_sections()`
	public static function moduleSections( $page )
	{
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! isset( $wp_settings_sections[$page] ) )
			return;

		foreach ( (array) $wp_settings_sections[$page] as $section ) {

			echo '<div class="-section-wrap '.$section['section_class'].'">';

				if ( $section['title'] )
					HTML::h2( $section['title'], '-section-title' );

				if ( $section['callback'] )
					call_user_func( $section['callback'], $section );

				if ( ! isset( $wp_settings_fields )
					|| ! isset( $wp_settings_fields[$page] )
					|| ! isset( $wp_settings_fields[$page][$section['id']] ) ) {

					echo '</div>';
					continue;
				}

				echo '<table class="form-table -section-table"><tbody class="-section-body -list">';
					// do_settings_fields( $page, $section['id'] );
					self::moduleSectionFields( $page, $section['id'] );
				echo '</tbody></table>';

			echo '</div>';
		}
	}

	// @SOURCE: `do_settings_fields()`
	public static function moduleSectionFields( $page, $section )
	{
		global $wp_settings_fields;

		if ( ! isset( $wp_settings_fields[$page][$section] ) )
			return;

		foreach ( (array) $wp_settings_fields[$page][$section] as $field ) {
			$class = '-field';

			if ( ! empty( $field['args']['class'] ) )
				$class .= ' '.esc_attr( $field['args']['class'] );

			echo '<tr class="'.$class.'">';

			if ( ! empty( $field['args']['label_for'] ) )
				echo '<th class="-th" scope="row"><label for="'
					.esc_attr( $field['args']['label_for'] ).'">'.$field['title'].'</label></th>';

			else
				echo '<th class="-th" scope="row">'.$field['title'].'</th>';

			echo '<td class="-td">';
				call_user_func( $field['callback'], $field['args'] );
			echo '</td></tr>';
		}
	}

	public static function moduleSectionEmpty( $description )
	{
		HTML::desc( $description, TRUE, '-section-description -section-empty' );
	}

	public static function moduleButtons( $module, $enabled = FALSE )
	{
		echo HTML::tag( 'input', [
			'type'  => 'submit',
			'value' => _x( 'Enable', 'Settings: Button', GEDITORIAL_TEXTDOMAIN ),
			'style' => $enabled ? 'display:none' : FALSE,
			'class' => [ 'hide-if-no-js', 'button-primary', 'button', 'button-small', '-button' ],
			'data'  => [
				'module' => $module->name,
				'do'     => 'enable',
			],
		] );

		echo HTML::tag( 'input', [
			'type'  => 'submit',
			'value' => _x( 'Disable', 'Settings: Button', GEDITORIAL_TEXTDOMAIN ),
			'style' => $enabled ? FALSE : 'display:none',
			'class' => [ 'hide-if-no-js', 'button-secondary', 'button', 'button-small', '-button', '-button-danger' ],
			'data'  => [
				'module' => $module->name,
				'do'     => 'disable',
			],
		] );

		echo HTML::tag( 'span', [
			'class' => [ 'button', 'hide-if-js' ],
		], _x( 'You have to enable Javascript!', 'Settings: Notice', GEDITORIAL_TEXTDOMAIN ) );
	}

	public static function moduleConfigure( $module, $enabled = FALSE )
	{
		if ( $module->configure )
			echo HTML::tag( 'a', [
				'href'  => add_query_arg( [ 'page' => static::SETTINGS, 'module' => $module->name ], get_admin_url( NULL, 'admin.php' ) ),
				'style' => $enabled ? FALSE : 'display:none',
				'class' => [ 'button-primary', 'button', 'button-small', '-button' ],
				'data'  => [
					'module' => $module->name,
					'do'     => 'configure',
				],
			], _x( 'Configure', 'Settings: Button', GEDITORIAL_TEXTDOMAIN ) );
	}

	public static function moduleInfo( $module, $tag = 'h3' )
	{
		HTML::h3( HTML::tag( 'a', [
			'href'   => self::getModuleDocsURL( $module ),
			'title'  => sprintf( _x( '%s Documentation', 'Settings', GEDITORIAL_TEXTDOMAIN ), $module->title ),
			'target' => '_blank',
		], $module->title ), '-title' );

		if ( isset( $module->desc ) )
			HTML::desc( $module->desc );

		// key for search filter
		echo '<span class="-module-key" style="display:none;">'.$module->name.'</span>';
	}

	public static function getModuleDocsURL( $module = FALSE )
	{
		return FALSE === $module || 'config' == $module->name
			? 'https://github.com/geminorum/geditorial/wiki'
			: 'https://github.com/geminorum/geditorial/wiki/Modules-'.Helper::moduleSlug( $module->name );
	}

	public static function settingsCredits()
	{
		echo '<div class="credits">';

		echo '<p>';
			echo 'This is a fork in structure of <a href="http://editflow.org/">EditFlow</a><br />';
			echo '<a href="https://github.com/geminorum/geditorial/issues" target="_blank">Feedback, Ideas and Bug Reports</a> are welcomed.<br />';
			echo 'You\'re using gEditorial <a href="https://github.com/geminorum/geditorial/releases/latest" target="_blank" title="Check for the latest version">v'.GEDITORIAL_VERSION.'</a>';
		echo '</p>';

		echo '<a href="http://geminorum.ir" title="it\'s a geminorum project"><img src="'
			.GEDITORIAL_URL.'assets/images/itsageminorumproject-lightgrey.svg" alt="" /></a>';

		echo '</div>';
	}

	public static function settingsSignature()
	{
		echo '<div class="signature"><p>';
			printf( _x( '<a href="%1$s">gEditorial</a> is a <a href="%2$s">geminorum</a> project.', 'Settings: Signature', GEDITORIAL_TEXTDOMAIN ),
				'http://github.com/geminorum/geditorial',
				'http://geminorum.ir/' );
		echo '</p></div>';
	}

	public static function settingsHelpContent( $module = FALSE )
	{
		if ( ! function_exists( 'gnetwork_github' ) )
			return [];

		$wikihome = [
			'id'       => 'geditorial-wikihome',
			'title'    => _x( 'Editorial Wiki', 'Settings: Help Content Title', GEDITORIAL_TEXTDOMAIN ),
			'callback' => [ __CLASS__, 'add_help_tab_home_callback' ],
			'module'   => $module,
		];

		if ( FALSE === $module || 'config' == $module->name )
			return [ $wikihome ];

		$wikimodule = [
			'id'       => 'geditorial-'.$module->name.'-wikihome',
			'title'    => sprintf( _x( '%s Wiki', 'Settings: Help Content Title', GEDITORIAL_TEXTDOMAIN ), $module->title ),
			'callback' => [ __CLASS__, 'add_help_tab_module_callback' ],
			'module'   => $module,
		];

		return [ $wikimodule, $wikihome ];
	}

	public static function add_help_tab_home_callback( $screen, $tab )
	{
		$tab['module'] = FALSE;
		self::add_help_tab_module_callback( $screen, $tab );
	}

	public static function add_help_tab_module_callback( $screen, $tab )
	{
		if ( ! function_exists( 'gnetwork_github' ) )
			return;

		$module = empty( $tab['module'] ) ? FALSE : $tab['module'];

		$page = FALSE === $module || 'config' == $module->name
			? 'Home'
			: 'Modules-'.Helper::moduleSlug( $module->name );

		echo gnetwork_github( [
			'repo'    => 'geminorum/geditorial',
			'type'    => 'wiki',
			'page'    => $page,
			'context' => 'help_tab',
		] );
	}

	public static function fieldType( $atts = [], &$scripts )
	{
		$args = self::atts( [
			'title'        => '&nbsp;',
			'label_for'    => '',
			'type'         => 'enabled',
			'field'        => FALSE,
			'values'       => [],
			'exclude'      => '',
			'none_title'   => NULL, // select option none title
			'none_value'   => NULL, // select option none value
			'filter'       => FALSE, // will use via sanitize
			'callback'     => FALSE, // callable for `callback` type
			'dir'          => FALSE,
			'disabled'     => FALSE,
			'readonly'     => FALSE,
			'default'      => '',
			'defaults'     => [], // default value to ignore && override the saved
			'description'  => isset( $atts['desc'] ) ? $atts['desc'] : '',
			'before'       => '', // html to print before field
			'after'        => '', // html to print after field
			'field_class'  => '', // formally just class!
			'class'        => '', // now used on wrapper
			'option_group' => 'settings',
			'option_base'  => 'geditorial',
			'options'      => [], // saved options
			'id_name_cb'   => FALSE, // id/name generator callback
			'id_attr'      => FALSE, // override
			'name_attr'    => FALSE, // override
			'step_attr'    => '1', // for number type
			'min_attr'     => '0', // for number type
			'rows_attr'    => '5', // for textarea type
			'cols_attr'    => '45', // for textarea type
			'placeholder'  => FALSE,
			'constant'     => FALSE, // override value if constant defined & disabling
			'data'         => [], // data attr
			'extra'        => [], // extra args to pass to deeper generator
			'wrap'         => FALSE,
			'cap'          => NULL,

			'string_disabled' => _x( 'Disabled', 'Settings', GEDITORIAL_TEXTDOMAIN ),
			'string_enabled'  => _x( 'Enabled', 'Settings', GEDITORIAL_TEXTDOMAIN ),
			'string_select'   => self::showOptionNone(),
			'string_noaccess' => _x( 'You do not have access to change this option.', 'Settings', GEDITORIAL_TEXTDOMAIN ),
		], $atts );

		if ( $args['wrap'] ) {
			if ( ! empty( $args['label_for'] ) )
				echo '<tr class="'.$args['class'].'"><th scope="row"><label for="'.esc_attr( $args['label_for'] ).'">'.$args['title'].'</label></th><td>';
			else
				echo '<tr class="'.$args['class'].'"><th scope="row">'.$args['title'].'</th><td>';
		}

		if ( ! $args['field'] )
			return;

		$html  = '';
		$value = $args['default'];

		if ( is_array( $args['exclude'] ) )
			$exclude = array_filter( $args['exclude'] );
		else if ( $args['exclude'] )
			$exclude = array_filter( explode( ',', $args['exclude'] ) );
		else
			$exclude = [];

		if ( $args['id_name_cb'] ) {
			list( $id, $name ) = call_user_func( $args['id_name_cb'], $args );
		} else {
			$id   = $args['id_attr'] ? $args['id_attr'] : ( $args['option_base'] ? $args['option_base'].'-' : '' ).$args['option_group'].'-'.esc_attr( $args['field'] );
			$name = $args['name_attr'] ? $args['name_attr'] : ( $args['option_base'] ? $args['option_base'].'_' : '' ).$args['option_group'].'['.esc_attr( $args['field'] ).']';
		}

		if ( isset( $args['options'][$args['field']] ) ) {
			$value = $args['options'][$args['field']];

			// override: using settings default instead of module's option
			if ( isset( $args['defaults'][$args['field']] )
				&& $value === $args['defaults'][$args['field']] )
					$value = $args['default'];
		}

		if ( $args['constant'] && defined( $args['constant'] ) ) {
			$value = constant( $args['constant'] );

			$args['disabled'] = TRUE;
			$args['after'] = '<code>'.$args['constant'].'</code>';
		}

		if ( is_null( $args['cap'] ) ) {

			if ( in_array( $args['type'], [ 'role', 'cap', 'user' ] ) )
				$args['cap'] = 'promote_users';
			else
				$args['cap'] = 'manage_options';
		}

		if ( ! current_user_can( $args['cap'] ) )
			$args['type'] = 'noaccess';

		if ( $args['before'] )
			echo $args['before'].'&nbsp;';

		switch ( $args['type'] ) {

			case 'hidden':

				echo HTML::tag( 'input', [
					'type'  => 'hidden',
					'id'    => $id,
					'name'  => $name,
					'value' => $value,
					'data'  => $args['data'],
				] );

				$args['description'] = FALSE;

			break;
			case 'enabled':

				$html = HTML::tag( 'option', [
					'value'    => '0',
					'selected' => '0' == $value,
				], esc_html( empty( $args['values'][0] ) ? $args['string_disabled'] : $args['values'][0] ) );

				$html .= HTML::tag( 'option', [
					'value'    => '1',
					'selected' => '1' == $value,
				], esc_html( empty( $args['values'][1] ) ? $args['string_enabled'] : $args['values'][1] ) );

				echo HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-enabled' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

			break;
			case 'text':

				if ( ! $args['field_class'] )
					$args['field_class'] = 'regular-text';

				echo HTML::tag( 'input', [
					'type'        => 'text',
					'id'          => $id,
					'name'        => $name,
					'value'       => $value,
					'class'       => $args['field_class'],
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				] );

			break;
			case 'number':

				if ( ! $args['field_class'] )
					$args['field_class'] = 'small-text';

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				echo HTML::tag( 'input', [
					'type'        => 'number',
					'id'          => $id,
					'name'        => $name,
					'value'       => $value,
					'step'        => $args['step_attr'],
					'min'         => $args['min_attr'],
					'class'       => HTML::attrClass( $args['field_class'], '-type-number' ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				] );

			break;
			case 'url':

				if ( ! $args['field_class'] )
					$args['field_class'] = [ 'regular-text', 'url-text' ];

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				echo HTML::tag( 'input', [
					'type'        => 'url',
					'id'          => $id,
					'name'        => $name,
					'value'       => $value,
					'class'       => $args['field_class'],
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				] );

			break;
			case 'email':

				if ( ! $args['field_class'] )
					$args['field_class'] = [ 'regular-text', 'email-text' ];

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				echo HTML::tag( 'input', [
					'type'        => 'email',
					'id'          => $id,
					'name'        => $name,
					'value'       => $value,
					'class'       => $args['field_class'],
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				] );

			break;
			case 'checkbox':

				if ( count( $args['values'] ) ) {

					if ( ! is_null( $args['none_title'] ) ) {

						$html = HTML::tag( 'input', [
							'type'     => 'checkbox',
							'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'name'     => $name.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'value'    => is_null( $args['none_value'] ) ? '1' : $args['none_value'],
							'checked'  => in_array( $args['none_value'], (array) $value ),
							'class'    => HTML::attrClass( $args['field_class'], '-type-checkbox', '-option-none' ),
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						] );

						echo '<p>'.HTML::tag( 'label', [
							'for' => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
						], $html.'&nbsp;'.esc_html( $args['none_title'] ) ).'</p>';
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = HTML::tag( 'input', [
							'type'     => 'checkbox',
							'id'       => $id.'-'.$value_name,
							'name'     => $name.'['.$value_name.']',
							'value'    => '1',
							'checked'  => in_array( $value_name, (array) $value ),
							'class'    => $args['field_class'],
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						] );

						echo '<p>'.HTML::tag( 'label', [
							'for' => $id.'-'.$value_name,
						], $html.'&nbsp;'.$value_title ).'</p>';
					}

				} else {

					$html = HTML::tag( 'input', [
						'type'     => 'checkbox',
						'id'       => $id,
						'name'     => $name,
						'value'    => '1',
						'checked'  => $value,
						'class'    => $args['field_class'],
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					] );

					echo '<p>'.HTML::tag( 'label', [
						'for' => $id,
					], $html.'&nbsp;'.$args['description'] ).'</p>';

					$args['description'] = FALSE;
				}

			break;
			case 'radio':

				if ( count( $args['values'] ) ) {

					if ( ! is_null( $args['none_title'] ) ) {

						$html = HTML::tag( 'input', [
							'type'     => 'radio',
							'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'name'     => $name,
							'value'    => is_null( $args['none_value'] ) ? FALSE : $args['none_value'],
							'checked'  => in_array( $args['none_value'], (array) $value ),
							'class'    => HTML::attrClass( $args['field_class'], '-type-radio', '-option-none' ),
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						] );

						echo '<p>'.HTML::tag( 'label', [
							'for' => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
						], $html.'&nbsp;'.esc_html( $args['none_title'] ) ).'</p>';
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = HTML::tag( 'input', [
							'type'     => 'radio',
							'id'       => $id.'-'.$value_name,
							'name'     => $name,
							'value'    => $value_name,
							'checked'  => in_array( $value_name, (array) $value ),
							'class'    => HTML::attrClass( $args['field_class'], '-type-radio' ),
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						] );

						echo '<p>'.HTML::tag( 'label', [
							'for' => $id.'-'.$value_name,
						], $html.'&nbsp;'.$value_title ).'</p>';
					}
				}

			break;
			case 'select':

				if ( FALSE !== $args['values'] ) {

					if ( ! is_null( $args['none_title'] ) ) {

						if ( is_null( $args['none_value'] ) )
							$args['none_value'] = '0';

						$html .= HTML::tag( 'option', [
							'value'    => $args['none_value'],
							'selected' => $value == $args['none_value'],
						], esc_html( $args['none_title'] ) );
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html .= HTML::tag( 'option', [
							'value'    => $value_name,
							'selected' => $value == $value_name,
						], esc_html( $value_title ) );
					}

					echo HTML::tag( 'select', [
						'id'       => $id,
						'name'     => $name,
						'class'    => HTML::attrClass( $args['field_class'], '-type-select' ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					], $html );
				}

			break;
			case 'textarea':
			case 'textarea-quicktags':

				if ( ! $args['field_class'] )
					$args['field_class'] = 'regular-text';

				if ( 'textarea-quicktags' == $args['type'] ) {

					$args['field_class'] = HTML::attrClass( $args['field_class'], 'textarea-quicktags', 'code' );

					if ( ! $args['values'] )
						$args['values'] = [
							'link',
							'em',
							'strong',
						];

					$scripts[] = 'quicktags({id:"'.$id.'",buttons:"'.implode( ',', $args['values'] ).'"});';

					wp_enqueue_script( 'quicktags' );
				}

				echo HTML::tag( 'textarea', [
					'id'          => $id,
					'name'        => $name,
					'rows'        => $args['rows_attr'],
					'cols'        => $args['cols_attr'],
					'class'       => HTML::attrClass( $args['field_class'], '-type'.$args['type'] ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				], $value );

			break;
			case 'page':

				if ( ! $args['values'] )
					$args['values'] = 'page';

				if ( is_null( $args['none_title'] ) )
					$args['none_title'] = $args['string_select'];

				if ( is_null( $args['none_value'] ) )
					$args['none_value'] = '0';

				$query = array_merge( [
					'post_type'   => $args['values'],
					'selected'    => $value,
					'exclude'     => implode( ',', $exclude ),
					'sort_column' => 'menu_order',
					'sort_order'  => 'asc',
					'post_status' => [ 'publish', 'future', 'draft' ],
				], $args['extra'] );

				$pages = get_pages( $query );

				if ( ! empty( $pages ) ) {

					$html .= HTML::tag( 'option', [
						'value' => $args['none_value'],
					], esc_html( $args['none_title'] ) );

					$html .= walk_page_dropdown_tree( $pages, ( isset( $query['depth'] ) ? $query['depth'] : 0 ), $query );

					echo HTML::tag( 'select', [
						'id'       => $id,
						'name'     => $name,
						'class'    => HTML::attrClass( $args['field_class'], '-type-page', '-posttype-'.$args['values'] ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					], $html );

				} else {
					$args['description'] = FALSE;
				}

			break;
			case 'role':

				if ( ! $args['values'] )
					$args['values'] = array_reverse( get_editable_roles() );

				if ( is_null( $args['none_title'] ) )
					$args['none_title'] = $args['string_select'];

				if ( is_null( $args['none_value'] ) )
					$args['none_value'] = '0';

				$html .= HTML::tag( 'option', [
					'value' => $args['none_value'],
				], esc_html( $args['none_title'] ) );

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html .= HTML::tag( 'option', [
						'value'    => $value_name,
						'selected' => $value == $value_name,
					], esc_html( translate_user_role( $value_title['name'] ) ) );
				}

				echo HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-role' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

			break;
			case 'user':

				if ( ! $args['values'] )
					$args['values'] = User::get( FALSE, FALSE, $args['extra'] );

				if ( ! is_null( $args['none_title'] ) ) {

					$html .= HTML::tag( 'option', [
						'value'    => is_null( $args['none_value'] ) ? FALSE : $args['none_value'],
						'selected' => $value == $args['none_value'],
					], esc_html( $args['none_title'] ) );
				}

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html .= HTML::tag( 'option', [
						'value'    => $value_name,
						'selected' => $value == $value_name,
					], esc_html( sprintf( '%1$s (%2$s)', $value_title->display_name, $value_title->user_login ) ) );
				}

				echo HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-user' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

			break;
			case 'priority':

				if ( ! $args['values'] )
					$args['values'] = self::priorityOptions( FALSE );

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html .= HTML::tag( 'option', [
						'value'    => $value_name,
						'selected' => $value == $value_name,
					], esc_html( $value_title ) );
				}

				echo HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-priority' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

			break;
			case 'button':

				self::submitButton(
					$args['field'],
					$value,
					( empty( $args['field_class'] ) ? 'secondary' : $args['field_class'] ),
					$args['values']
				);

			break;
			case 'file':

				echo HTML::tag( 'input', [
					'type'     => 'file',
					'id'       => $id,
					'name'     => $id,
					'class'    => $args['field_class'],
					'disabled' => $args['disabled'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				] );

			break;
			case 'posttypes':

				// FIXME: false to disable
				if ( ! $args['values'] )
					$args['values'] = PostType::get( 0,
						array_merge( [ 'public' => TRUE ], $args['extra'] ) );

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html = HTML::tag( 'input', [
						'type'     => 'checkbox',
						'id'       => $id.'-'.$value_name,
						'name'     => $name.'['.$value_name.']',
						'value'    => '1',
						'checked'  => in_array( $value_name, (array) $value ),
						'class'    => HTML::attrClass( $args['field_class'], '-type-posttypes' ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
					] );

					echo '<p>'.HTML::tag( 'label', [
						'for' => $id.'-'.$value_name,
					], $html.'&nbsp;'.esc_html( $value_title ) ).' &mdash; <code>'.$value_name.'</code>'.'</p>';
				}

			break;
			case 'taxonomies':

				if ( ! $args['values'] )
					$args['values'] = Taxonomy::get( 0, $args['extra'] );

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html = HTML::tag( 'input', [
						'type'     => 'checkbox',
						'id'       => $id.'-'.$value_name,
						'name'     => $name.'['.$value_name.']',
						'value'    => '1',
						'checked'  => in_array( $value_name, (array) $value ),
						'class'    => HTML::attrClass( $args['field_class'], '-type-taxonomies' ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
					] );

					echo '<p>'.HTML::tag( 'label', [
						'for' => $id.'-'.$value_name,
					], $html.'&nbsp;'.esc_html( $value_title ) ).' &mdash; <code>'.$value_name.'</code>'.'</p>';
				}

			break;
			case 'callback':

				if ( is_callable( $args['callback'] ) ) {

					call_user_func_array( $args['callback'], [ &$args,
						compact( 'html', 'value', 'name', 'id', 'exclude' ) ] );

				} else if ( WordPress::isDev() ) {

					echo 'Error: Setting Is Not Callable!';
				}

			break;
			case 'noaccess':

				echo HTML::tag( 'span', [
					'class' => '-type-noaccess',
				], $args['string_noaccess'] );

			break;
			case 'custom':

				if ( ! is_array( $args['values'] ) )
					echo $args['values'];
				else
					echo $value;

			break;
			case 'debug':

				self::dump( $args['options'] );

			break;
			default:

				echo 'Error: setting type not defind!';
		}

		if ( $args['after'] )
			echo '&nbsp;'.$args['after'];

		if ( FALSE !== $args['values'] )
			HTML::desc( $args['description'] );

		if ( $args['wrap'] )
			echo '</td></tr>';
	}
}
