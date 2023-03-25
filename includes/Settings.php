<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Strings;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\User;

class Settings extends Core\Base
{

	const BASE     = 'geditorial';
	const REPORTS  = 'geditorial-reports';
	const SETTINGS = 'geditorial-settings';
	const TOOLS    = 'geditorial-tools';
	const IMPORTS  = 'geditorial-imports';

	// better to use `$this->get_module_url()`
	public static function subURL( $sub = FALSE, $context = 'reports', $extra = [] )
	{
		switch ( $context ) {
			case 'reports' : $url = self::reportsURL();  break;
			case 'settings': $url = self::settingsURL(); break;
			case 'tools'   : $url = self::toolsURL();    break;
			case 'imports' : $url = self::importsURL();  break;
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
	public static function toolsURL( $full = TRUE, $tools_menu = FALSE )
	{
		$relative = $tools_menu ? 'tools.php?page='.self::TOOLS : 'admin.php?page='.self::TOOLS;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	// FIXME: MUST DEPRICATE
	public static function importsURL( $full = TRUE )
	{
		$relative = 'tools.php?page='.self::IMPORTS;

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

	public static function isImports( $screen = NULL )
	{
		if ( is_null( $screen ) )
			$screen = get_current_screen();

		if ( ! empty( $screen->base ) && Text::has( $screen->base, self::IMPORTS ) )
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

	public static function getPageExcludes( $include = [], $context = 'settings' )
	{
		$pages = [];

		if ( ! in_array( 'front', $include, TRUE ) )
			$pages[] = get_option( 'page_on_front' );

		if ( ! in_array( 'posts', $include, TRUE ) )
			$pages[] = get_option( 'page_for_posts' );

		if ( ! in_array( 'privacy', $include, TRUE ) )
			$pages[] = get_option( 'wp_page_for_privacy_policy' );

		return array_filter( apply_filters( static::BASE.'_page_excludes', $pages, $context ) );
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
			'5'    => _x( '5 Minutes', 'Settings: Option: Time in Minutes', 'geditorial' ),
			'10'   => _x( '10 Minutes', 'Settings: Option: Time in Minutes', 'geditorial' ),
			'15'   => _x( '15 Minutes', 'Settings: Option: Time in Minutes', 'geditorial' ),
			'30'   => _x( '30 Minutes', 'Settings: Option: Time in Minutes', 'geditorial' ),
			'60'   => _x( '60 Minutes', 'Settings: Option: Time in Minutes', 'geditorial' ),
			'120'  => _x( '2 Hours', 'Settings: Option: Time in Minutes', 'geditorial' ),
			'180'  => _x( '3 Hours', 'Settings: Option: Time in Minutes', 'geditorial' ),
			'240'  => _x( '4 Hours', 'Settings: Option: Time in Minutes', 'geditorial' ),
			'480'  => _x( '8 Hours', 'Settings: Option: Time in Minutes', 'geditorial' ),
			'1440' => _x( '24 Hours', 'Settings: Option: Time in Minutes', 'geditorial' ),
		];
	}

	public static function supportsOptions()
	{
		return [
			'title'           => _x( 'Title', 'Settings: Option: PostType Support', 'geditorial' ),
			'editor'          => _x( 'Editor', 'Settings: Option: PostType Support', 'geditorial' ),
			'excerpt'         => _x( 'Excerpt', 'Settings: Option: PostType Support', 'geditorial' ),
			'author'          => _x( 'Author', 'Settings: Option: PostType Support', 'geditorial' ),
			'thumbnail'       => _x( 'Thumbnail', 'Settings: Option: PostType Support', 'geditorial' ),
			'comments'        => _x( 'Comments', 'Settings: Option: PostType Support', 'geditorial' ),
			'trackbacks'      => _x( 'Trackbacks', 'Settings: Option: PostType Support', 'geditorial' ),
			'custom-fields'   => _x( 'Custom Fields', 'Settings: Option: PostType Support', 'geditorial' ),
			'post-formats'    => _x( 'Post Formats', 'Settings: Option: PostType Support', 'geditorial' ),
			'revisions'       => _x( 'Revisions', 'Settings: Option: PostType Support', 'geditorial' ),
			'page-attributes' => _x( 'Post Attributes', 'Settings: Option: PostType Support', 'geditorial' ),
			'amp'             => _x( 'Accelerated Mobile Pages', 'Settings: Option: PostType Support', 'geditorial' ),
			'date-picker'     => _x( 'Persian Date: Date Picker', 'Settings: Option: PostType Support', 'geditorial' ),
			'editorial-meta'  => _x( 'Editorial: Meta Fields', 'Settings: Option: PostType Support', 'geditorial' ),
			'editorial-roles' => _x( 'Editorial: Custom Roles', 'Settings: Option: PostType Support', 'geditorial' ),
		];
	}

	public static function posttypesExcluded( $extra = [] )
	{
		$list = [
			'attachment',        // WP Core
			'wp_theme',          // WP Core
			'wp_block',          // WP Core
			'wp_navigation',     // WP Core
			'wp_global_styles',  // WP Core
			'wp_template_part',  // WP Core
			'wp_template',       // WP Core
			'user_request',      // WP Core
			'oembed_cache',      // WP Core
			'bp-email',          // BuddyPress
			'shop_order',        // WooCommerce
			'shop_coupon',       // WooCommerce
			'guest-author',      // Co-Authors Plus
			'amp_validated_url', // AMP
			'inbound_message',
		];

		if ( class_exists( 'bbPress' ) )
			$list = array_merge( $list, [
				'forum',
				'topic',
				'reply',
			] );

		return array_merge( $list, (array) $extra );
	}

	public static function taxonomiesExcluded( $extra = [] )
	{
		$list = [
			'nav_menu',               // WP Core
			'wp_theme',               // WP Core
			'link_category',          // WP Core
			'post_format',            // WP Core
			'wp_template_part_area',  // WP Core
			'amp_validation_error',   // AMP
			'product_type',           // WooCommerce
			'product_visibility',     // WooCommerce
			'product_shipping_class', // WooCommerce
			'bp-email-type',          // BuddyPress
			'bp_member_type',         // BuddyPress
			'bp_group_type',          // BuddyPress
		];

		if ( class_exists( 'bbPress' ) )
			$list = array_merge( $list, [
				'topic-tag',
			] );

		return array_merge( $list, (array) $extra );
	}

	public static function rolesExcluded( $extra = [] )
	{
		return array_merge( [
			'backwpup_admin',
			'backwpup_check',
			'backwpup_helper',
		], (array) $extra );
	}

	public static function showOptionNone( $string = NULL )
	{
		if ( $string )
			/* translators: %s: options */
			return sprintf( _x( '&ndash; Select %s &ndash;', 'Settings: Dropdown Select Option None', 'geditorial' ), $string );

		return _x( '&ndash; Select &ndash;', 'Settings: Dropdown Select Option None', 'geditorial' );
	}

	public static function showRadioNone( $string = NULL )
	{
		if ( $string )
			/* translators: %s: options */
			return sprintf( _x( 'None %s', 'Settings: Radio Select Option None', 'geditorial' ), $string );

		return _x( 'None', 'Settings: Radio Select Option None', 'geditorial' );
	}

	public static function showOptionAll( $string = NULL )
	{
		if ( $string )
			/* translators: %s: options */
			return sprintf( _x( '&ndash; All %s &ndash;', 'Settings: Dropdown Select Option All', 'geditorial' ), $string );

		return _x( '&ndash; All &ndash;', 'Settings: Dropdown Select Option All', 'geditorial' );
	}

	public static function fieldSeparate( $string = 'from' )
	{
		switch ( $string ) {
			case 'count': $string = _x( 'count', 'Settings: Field Separate', 'geditorial' ); break;
			case 'from' : $string = _x( 'from', 'Settings: Field Separate', 'geditorial' );  break;
			case 'into' : $string = _x( 'into', 'Settings: Field Separate', 'geditorial' );  break;
			case 'like' : $string = _x( 'like', 'Settings: Field Separate', 'geditorial' );  break;
			case 'ex'   : $string = _x( 'ex', 'Settings: Field Separate', 'geditorial' );    break;
			case 'in'   : $string = _x( 'in', 'Settings: Field Separate', 'geditorial' );    break;
			case 'to'   : $string = _x( 'to', 'Settings: Field Separate', 'geditorial' );    break;
			case 'as'   : $string = _x( 'as', 'Settings: Field Separate', 'geditorial' );    break;
			case 'or'   : $string = _x( 'or', 'Settings: Field Separate', 'geditorial' );    break;
			case 'on'   : $string = _x( 'on', 'Settings: Field Separate', 'geditorial' );    break;
		}

		printf( '<span class="-field-sep">&nbsp;&mdash; %s &mdash;&nbsp;</span>', $string );
	}

	public static function fieldSection( $title, $description = FALSE, $tag = 'h2' )
	{
		echo HTML::tag( $tag, $title );

		HTML::desc( $description );
	}

	public static function fieldAfterText( $text, $wrap = 'span', $class = '-text-wrap' )
	{
		return $text ? HTML::tag( $wrap, [ 'class' => '-field-after '.$class ], $text ) : '';
	}

	public static function fieldAfterIcon( $url = '', $title = NULL, $icon = 'info' )
	{
		if ( ! $url )
			return '';

		if ( is_null( $title ) )
			$title = _x( 'See More Information', 'Settings', 'geditorial' );

		$html = HTML::tag( 'a', [
			'href'   => $url,
			'target' => '_blank',
			'rel'    => 'noreferrer',
			'data'   => [
				'tooltip'     => $title,
				'tooltip-pos' => HTML::rtl() ? 'left' : 'right',
			],
		], HTML::getDashicon( $icon ) );

		return '<span class="-field-after -icon-wrap">'.$html.'</span>';
	}

	public static function infoP2P()
	{
		/* translators: %s: code placeholder */
		return sprintf( _x( 'Connected via %s', 'Settings: Setting Info', 'geditorial' ), '<code>P2P</code>' );
	}

	public static function getSetting_editor_button( $description = NULL )
	{
		return [
			'field'       => 'editor_button',
			'title'       => _x( 'Editor Button', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
			'default'     => '1',
		];
	}

	public static function getSetting_quick_newpost( $description = NULL )
	{
		return [
			'field'       => 'quick_newpost',
			'title'       => _x( 'Quick New Post', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
			'default'     => '0',
		];
	}

	public static function getSetting_widget_support( $description = NULL )
	{
		return [
			'field'       => 'widget_support',
			'title'       => _x( 'Default Widgets', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
			'default'     => '0',
		];
	}

	public static function getSetting_shortcode_support( $description = NULL )
	{
		return [
			'field'       => 'shortcode_support',
			'title'       => _x( 'Default Shortcodes', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
			'default'     => '0',
		];
	}

	public static function getSetting_thumbnail_support( $description = NULL )
	{
		return [
			'field'       => 'thumbnail_support',
			'title'       => _x( 'Default Image Sizes', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
			'default'     => '0',
		];
	}

	public static function getSetting_thumbnail_fallback( $description = NULL )
	{
		return [
			'field'       => 'thumbnail_fallback',
			'title'       => _x( 'Thumbnail Fallback', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Sets the parent post thumbnail image as fallback for the child post.', 'Settings: Setting Description', 'geditorial' ),
			'default'     => '0',
		];
	}

	public static function getSetting_restapi_support( $description = NULL )
	{
		return [
			'field'       => 'restapi_support',
			'title'       => _x( 'REST API Support', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
			'default'     => '0',
		];
	}

	public static function getSetting_legacy_migration( $description = NULL )
	{
		return [
			'field'       => 'legacy_migration',
			'title'       => _x( 'Legacy Migration', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Imports metadata from legacy plugin system.', 'Settings: Setting Description', 'geditorial' ),
			'default'     => '0',
		];
	}

	public static function getSetting_assign_default_term( $description = NULL )
	{
		return [
			'field'       => 'assign_default_term',
			'title'       => _x( 'Assign Default Term', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Applies the fallback default term from primary taxonomy.', 'Settings: Setting Description', 'geditorial' ),
			'default'     => '0',
		];
	}

	public static function getSetting_multiple_instances( $description = NULL )
	{
		return [
			'field'       => 'multiple_instances',
			'title'       => _x( 'Multiple Instances', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
			'default'     => '0',
		];
	}

	public static function getSetting_comment_status( $description = NULL )
	{
		return [
			'field'       => 'comment_status',
			'type'        => 'select',
			'title'       => _x( 'Comment Status', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Determines the default status of the new post comments.', 'Settings: Setting Description', 'geditorial' ),
			'default'     => 'closed',
			'values'      => [
				'open'   => _x( 'Open', 'Settings: Setting Option', 'geditorial' ),
				'closed' => _x( 'Closed', 'Settings: Setting Option', 'geditorial' ),
			],
		];
	}

	public static function getSetting_post_status( $description = NULL )
	{
		return [
			'field'       => 'post_status',
			'type'        => 'select',
			'title'       => _x( 'Post Status', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
			'default'     => 'pending',
			'values'      => Arraay::stripByKeys( PostType::getStatuses(), [
				'future',
				'auto-draft',
				'inherit',
				'trash',
			] ),
		];
	}

	public static function getSetting_post_type( $description = NULL )
	{
		return [
			'field'       => 'post_type',
			'type'        => 'select',
			'title'       => _x( 'Post Type', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
			'default'     => 'post',
			'values'      => PostType::get( 2 ),
			'exclude'     => [ 'attachment', 'wp_theme' ],
		];
	}

	public static function getSetting_insert_content( $description = NULL )
	{
		return [
			'field'       => 'insert_content',
			'type'        => 'select',
			'title'       => _x( 'Insert in Content', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Outputs automatically in the content.', 'Settings: Setting Description', 'geditorial' ),
			'default'     => 'none',
			'values'      => [
				'none'   => _x( 'No', 'Settings: Setting Option', 'geditorial' ),
				'before' => _x( 'Before', 'Settings: Setting Option', 'geditorial' ),
				'after'  => _x( 'After', 'Settings: Setting Option', 'geditorial' ),
			],
		];
	}

	public static function getSetting_insert_content_enabled( $description = NULL )
	{
		return array_merge( self:: getSetting_insert_content( $description ), [
			'type'    => 'enabled',
			'values'  => [],
			'default' => '',
		] );
	}

	public static function getSetting_insert_cover( $description = NULL )
	{
		return [
			'field'       => 'insert_cover',
			'title'       => _x( 'Insert Cover', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
		];
	}

	// FIXME: DEPRECATED: USE: `settings_insert_priority_option()`
	public static function getSetting_insert_priority( $description = NULL )
	{
		return [
			'field'       => 'insert_priority',
			'type'        => 'priority',
			'title'       => _x( 'Insert Priority', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
			'default'     => '10',
		];
	}

	public static function getSetting_before_content( $description = NULL )
	{
		return [
			'field'       => 'before_content',
			'type'        => 'textarea-quicktags',
			'title'       => _x( 'Before Content', 'Settings: Setting Title', 'geditorial' ),
			/* translators: %s: code placeholder */
			'description' => $description ?: sprintf( _x( 'Adds %s before start of all the supported post-types.', 'Settings: Setting Description', 'geditorial' ), '<code>HTML</code>' ),
		];
	}

	public static function getSetting_after_content( $description = NULL )
	{
		return [
			'field'       => 'after_content',
			'type'        => 'textarea-quicktags',
			'title'       => _x( 'After Content', 'Settings: Setting Title', 'geditorial' ),
			/* translators: %s: code placeholder */
			'description' => $description ?: sprintf( _x( 'Adds %s after end of all the supported post-types.', 'Settings: Setting Description', 'geditorial' ), '<code>HTML</code>' ),
		];
	}

	public static function getSetting_admin_ordering( $description = NULL )
	{
		return [
			'field'       => 'admin_ordering',
			'title'       => _x( 'Ordering', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Enhances item ordering on admin edit pages.', 'Settings: Setting Description', 'geditorial' ),
			'default'     => '1',
		];
	}

	public static function getSetting_admin_restrict( $description = NULL )
	{
		return [
			'field'       => 'admin_restrict',
			'title'       => _x( 'List Restrictions', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Enhances restrictions on admin edit pages.', 'Settings: Setting Description', 'geditorial' ),
		];
	}

	public static function getSetting_admin_columns( $description = NULL )
	{
		return [
			'field'       => 'admin_columns',
			'title'       => _x( 'List Columns', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Enhances columns on admin edit pages.', 'Settings: Setting Description', 'geditorial' ),
		];
	}

	public static function getSetting_admin_bulkactions( $description = NULL )
	{
		return [
			'field'       => 'admin_bulkactions',
			'title'       => _x( 'Bulk Actions', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Enhances bulk actions on admin edit pages.', 'Settings: Setting Description', 'geditorial' ),
		];
	}

	public static function getSetting_admin_rowactions( $description = NULL )
	{
		return [
			'field'       => 'admin_rowactions',
			'title'       => _x( 'Row Actions', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Enhances row actions on admin edit pages.', 'Settings: Setting Description', 'geditorial' ),
		];
	}

	public static function getSetting_adminbar_summary( $description = NULL )
	{
		return [
			'field'       => 'adminbar_summary',
			'title'       => _x( 'Adminbar Summary', 'Setting: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Summary for the current item as a node in admin-bar.', 'Setting: Setting Description', 'geditorial' ),
		];
	}

	public static function getSetting_dashboard_widgets( $description = NULL )
	{
		return [
			'field'       => 'dashboard_widgets',
			'title'       => _x( 'Dashboard Widgets', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Enhances admin dashboard with customized widgets.', 'Settings: Setting Description', 'geditorial' ),
		];
	}

	public static function getSetting_dashboard_authors( $description = NULL )
	{
		return [
			'field'       => 'dashboard_authors',
			'title'       => _x( 'Dashboard Authors', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Displays author column on the dashboard widget.', 'Settings: Setting Description', 'geditorial' ),
		];
	}

	public static function getSetting_dashboard_statuses( $description = NULL )
	{
		return [
			'field'       => 'dashboard_statuses',
			'title'       => _x( 'Dashboard Statuses', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Displays status column on the dashboard widget.', 'Settings: Setting Description', 'geditorial' ),
		];
	}

	public static function getSetting_dashboard_count( $description = NULL )
	{
		return [
			'field'       => 'dashboard_count',
			'type'        => 'number',
			'title'       => _x( 'Dashboard Count', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Limits displaying rows of items on the dashboard widget.', 'Settings: Setting Description', 'geditorial' ),
			'default'     => 10,
		];
	}

	public static function getSetting_summary_scope( $description = NULL )
	{
		return [
			'field'       => 'summary_scope',
			'type'        => 'select',
			'title'       => _x( 'Summary Scope', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'User scope for the content summary.', 'Settings: Setting Description', 'geditorial' ),
			'default'     => 'all',
			'values'      => [
				'all'     => _x( 'All Users', 'Settings: Setting Option', 'geditorial' ),
				'current' => _x( 'Current User', 'Settings: Setting Option', 'geditorial' ),
				'roles'   => _x( 'Within the Roles', 'Settings: Setting Option', 'geditorial' ),
			],
		];
	}

	public static function getSetting_summary_drafts( $description = NULL )
	{
		return [
			'field'       => 'summary_drafts',
			'title'       => _x( 'Include Drafts', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Include drafted items in the content summary.', 'Settings: Setting Description', 'geditorial' ),
		];
	}

	public static function getSetting_summary_excludes( $description = NULL, $values = [], $empty = NULL )
	{
		return [
			'field'        => 'summary_excludes',
			'type'         => 'checkboxes-values',
			'title'        => _x( 'Summary Excludes', 'Settings: Setting Title', 'geditorial' ),
			'description'  => $description ?: _x( 'Selected terms will be excluded on the content summary.', 'Settings: Setting Description', 'geditorial' ),
			'string_empty' => $empty ?: _x( 'There are no items available!', 'Settings: Setting Empty String', 'geditorial' ),
			'values'       => $values,
		];
	}

	public static function getSetting_paired_exclude_terms( $description = NULL, $taxonomy = 'post_tag', $empty = NULL )
	{
		return [
			'field'        => 'paired_exclude_terms',
			'type'         => 'checkbox-panel',
			'title'        => _x( 'Exclude Terms', 'Settings: Setting Title', 'geditorial' ),
			'description'  => $description ?: _x( 'Items with selected terms will be excluded form dropdown on supported post-types.', 'Settings: Setting Description', 'geditorial' ),
			'string_empty' => $empty ?: _x( 'There are no items available!', 'Settings: Setting Empty String', 'geditorial' ),
			'values'       => Taxonomy::listTerms( $taxonomy ),
		];
	}

	public static function getSetting_paired_force_parents( $description = NULL )
	{
		return [
			'field'        => 'paired_force_parents',
			'title'       => _x( 'Force Parents', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Includes parents on the supported post-types.', 'Settings: Setting Description', 'geditorial' ),
		];
	}

	public static function getSetting_count_not( $description = NULL )
	{
		return [
			'field'       => 'count_not',
			'title'       => _x( 'Count Not', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Counts not affected items in the content summary.', 'Settings: Setting Description', 'geditorial' ),
		];
	}

	public static function getSetting_posttype_feeds( $description = NULL )
	{
		return [
			'field'       => 'posttype_feeds',
			'title'       => _x( 'Feeds', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Supports feeds for the supported post-types.', 'Settings: Setting Description', 'geditorial' ),
		];
	}

	public static function getSetting_posttype_pages( $description = NULL )
	{
		return [
			'field'       => 'posttype_pages',
			'title'       => _x( 'Pages', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Supports pagination on the supported post-types.', 'Settings: Setting Description', 'geditorial' ),
		];
	}

	public static function getSetting_empty_content( $description = NULL )
	{
		return [
			'field'       => 'empty_content',
			'type'        => 'textarea-quicktags',
			'title'       => _x( 'Empty Content', 'Setting: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Displays as empty content placeholder.', 'Setting: Setting Description', 'geditorial' ),
			'default'     => _x( 'There are no content by this title. Search again or create one.', 'Setting: Setting Default', 'geditorial' ),
		];
	}

	public static function getSetting_archive_title( $description = NULL )
	{
		return [
			'field'       => 'archive_title',
			'type'        => 'text',
			'title'       => _x( 'Archive Title', 'Setting: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Displays as archive title. Leave blank for default.', 'Setting: Setting Description', 'geditorial' ),
		];
	}

	public static function getSetting_archive_template( $description = NULL )
	{
		return [
			'field'       => 'archive_template',
			'type'        => 'select',
			'title'       => _x( 'Archive Template', 'Setting: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Used as page template on the archive page.', 'Setting: Setting Description', 'geditorial' ),
			'none_title'  => self::showOptionNone(),
			'values'      => wp_get_theme()->get_page_templates(),
		];
	}

	public static function getSetting_display_searchform( $description = NULL )
	{
		return [
			'field'       => 'display_searchform',
			'title'       => _x( 'Display Search Form', 'Setting: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Appends a search form to the content generated on front-end.', 'Setting: Setting Description', 'geditorial' ),
		];
	}

	public static function getSetting_display_threshold( $description = NULL )
	{
		return [
			'field'       => 'display_threshold',
			'type'        => 'number',
			'title'       => _x( 'Display Threshold', 'Setting: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Maximum number of items to consider as a long list.', 'Setting: Setting Description', 'geditorial' ),
			'default'     => '5',
		];
	}

	public static function getSetting_display_perpage( $description = NULL )
	{
		return [
			'field'       => 'display_perpage',
			'type'        => 'number',
			'title'       => _x( 'Display Per-Page', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: _x( 'Total rows of items per each page of the list.', 'Settings: Setting Description', 'geditorial' ),
			'default'     => 15,
		];
	}

	public static function getSetting_calendar_type( $description = NULL )
	{
		return [
			'field'       => 'calendar_type',
			'title'       => _x( 'Default Calendar', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
			'type'        => 'select',
			'default'     => 'gregorian',
			'values'      => Datetime::getDefualtCalendars( TRUE ),
		];
	}

	public static function getSetting_calendar_list( $description = NULL )
	{
		return [
			'field'       => 'calendar_list',
			'title'       => _x( 'Calendar List', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
			'type'        => 'checkboxes',
			'default'     => [ 'gregorian' ],
			'values'      => Datetime::getDefualtCalendars( TRUE ),
		];
	}

	public static function getSetting_extra_metadata( $description = NULL )
	{
		return [
			'field'       => 'extra_metadata',
			'title'       => _x( 'Metadata Support', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
		];
	}

	public static function getSetting_supported_roles( $description = NULL )
	{
		return [
			'field'       => 'supported_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Supported Roles', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
			'default'     => [],
			'exclude'     => self::rolesExcluded( [ 'administrator' ] ),
			'values'      => User::getAllRoleList(),
		];
	}

	public static function getSetting_excluded_roles( $description = NULL )
	{
		return [
			'field'       => 'excluded_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Excluded Roles', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
			'default'     => [],
			'exclude'     => self::rolesExcluded( [ 'administrator' ] ),
			'values'      => User::getAllRoleList(),
		];
	}

	public static function getSetting_adminmenu_roles( $description = NULL )
	{
		return [
			'field'       => 'adminmenu_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Admin Menu Roles', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
			'default'     => [],
			'exclude'     => self::rolesExcluded( [ 'administrator', 'subscriber' ] ),
			'values'      => User::getAllRoleList(),
		];
	}

	public static function getSetting_metabox_roles( $description = NULL )
	{
		return [
			'field'       => 'metabox_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Meta Box Roles', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
			'default'     => [],
			'exclude'     => self::rolesExcluded( [ 'administrator', 'subscriber' ] ),
			'values'      => User::getAllRoleList(),
		];
	}

	public static function getSetting_adminbar_roles( $description = NULL )
	{
		return [
			'field'       => 'adminbar_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Adminbar Roles', 'Settings: Setting Title', 'geditorial' ),
			'description' => $description ?: '',
			'default'     => [],
			'exclude'     => self::rolesExcluded( [ 'administrator', 'subscriber' ] ),
			'values'      => User::getAllRoleList(),
		];
	}

	public static function sub( $default = 'general' )
	{
		return trim( self::req( 'sub', $default ) );
	}

	public static function wrapOpen( $sub, $context = 'settings' )
	{
		echo '<div id="'.static::BASE.'-'.$context.'" class="'.HTML::prepClass(
			'wrap',
			'-settings-wrap',
			static::BASE.'-admin-wrap',
			static::BASE.'-'.$context,
			static::BASE.'-'.$context.'-'.$sub,
			'sub-'.$sub
		).'">';
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
	public static function headerTitle( $title = NULL, $back = NULL, $to = NULL, $icon = '', $count = FALSE, $search = FALSE, $filters = FALSE )
	{
		$before = $class = '';

		if ( is_null( $title ) )
			$title = _x( 'Editorial', 'Settings', 'geditorial' );

		// FIXME: get cap from settings module
		if ( is_null( $back ) && current_user_can( 'manage_options' ) )
			$back = self::settingsURL();

		if ( is_null( $to ) )
			$to = _x( 'Back to Editorial', 'Settings', 'geditorial' );

		if ( is_array( $icon ) )
			$before = gEditorial()->icon( $icon[1], $icon[0] );

		else if ( $icon )
			$class = ' dashicons-before dashicons-'.$icon;

		$extra = '';

		if ( FALSE !== $count )
			$extra.= sprintf( ' <span class="title-count settings-title-count">%s</span>', Number::format( $count ) );

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
				'class'       => [ 'settings-title-search', '-search', 'hide-if-no-js' ], // 'fuzzy-search' // fuzzy wont work persian
				'placeholder' => _x( 'Search …', 'Settings: Search Placeholder', 'geditorial' ),
				'autofocus'   => 'autofocus',
			] );

		if ( $filters ) {
			echo '<div class="settings-title-filters">';
				echo '<label>'._x( 'All', 'Settings', 'geditorial' );
				echo ' <input type="radio" name="filter-status" data-filter="all" value="all" checked="checked" /></label> ';

				echo '<label>'._x( 'Enabled', 'Settings', 'geditorial' );
				echo ' <input type="radio" name="filter-status" data-filter="enabled" value="true" /></label> ';

				echo '<label>'._x( 'Disabled', 'Settings', 'geditorial' );
				echo ' <input type="radio" name="filter-status" data-filter="disabled" value="false" /></label>';
			echo '</div>';
		}

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
			'resetting' => self::success( _x( 'Settings reset.', 'Settings: Message', 'geditorial' ) ),
			'updated'   => self::success( _x( 'Settings updated.', 'Settings: Message', 'geditorial' ) ),
			'disabled'  => self::success( _x( 'Module disabled.', 'Settings: Message', 'geditorial' ) ),
			'optimized' => self::success( _x( 'Tables optimized.', 'Settings: Message', 'geditorial' ) ),
			'purged'    => self::success( _x( 'Data purged.', 'Settings: Message', 'geditorial' ) ),
			'maked'     => self::success( _x( 'File/Folder created.', 'Settings: Message', 'geditorial' ) ),
			'mailed'    => self::success( _x( 'Mail sent successfully.', 'Settings: Message', 'geditorial' ) ),
			'error'     => self::error( _x( 'Error occurred!', 'Settings: Message', 'geditorial' ) ),
			'wrong'     => self::error( _x( 'Something&#8217;s wrong!', 'Settings: Message', 'geditorial' ) ),
			'nochange'  => self::error( _x( 'No item changed!', 'Settings: Message', 'geditorial' ) ),
			'noadded'   => self::error( _x( 'No item added!', 'Settings: Message', 'geditorial' ) ),
			'noaccess'  => self::error( _x( 'You do not have the access!', 'Settings: Message', 'geditorial' ) ),
			/* translators: %s: count */
			'converted' => self::counted( _x( '%s items(s) converted!', 'Settings: Message', 'geditorial' ) ),
			/* translators: %s: count */
			'imported'  => self::counted( _x( '%s items(s) imported!', 'Settings: Message', 'geditorial' ) ),
			/* translators: %s: count */
			'created'   => self::counted( _x( '%s items(s) created!', 'Settings: Message', 'geditorial' ) ),
			/* translators: %s: count */
			'deleted'   => self::counted( _x( '%s items(s) deleted!', 'Settings: Message', 'geditorial' ) ),
			/* translators: %s: count */
			'cleaned'   => self::counted( _x( '%s items(s) cleaned!', 'Settings: Message', 'geditorial' ) ),
			/* translators: %s: count */
			'changed'   => self::counted( _x( '%s items(s) changed!', 'Settings: Message', 'geditorial' ) ),
			/* translators: %s: count */
			'emptied'   => self::counted( _x( '%s items(s) emptied!', 'Settings: Message', 'geditorial' ) ),
			/* translators: %s: count */
			'closed'    => self::counted( _x( '%s items(s) closed!', 'Settings: Message', 'geditorial' ) ),
			/* translators: %s: count */
			'ordered'   => self::counted( _x( '%s items(s) re-ordered!', 'Settings: Message', 'geditorial' ) ),
			/* translators: %s: count */
			'scheduled' => self::counted( _x( '%s items(s) re-scheduled!', 'Settings: Message', 'geditorial' ) ),
			/* translators: %s: count */
			'synced'    => self::counted( _x( '%s items(s) synced!', 'Settings: Message', 'geditorial' ) ),
			'huh'       => HTML::error( self::huh( self::req( 'huh', NULL ) ) ),
		];
	}

	public static function messageExtra()
	{
		$extra = [];

		if ( isset( $_REQUEST['count'] ) )
			/* translators: %s: count */
			$extra[] = sprintf( _x( '%s Counted!', 'Settings: Message', 'geditorial' ),
				Number::format( $_REQUEST['count'] ) );

		return count( $extra ) ? ' ('.implode( Strings::separator(), $extra ).')' : '';
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
			$message = _x( 'Are you sure? This operation can not be undone.', 'Settings: Confirm', 'geditorial' );

		return [ 'onclick' => sprintf( 'return confirm(\'%s\')', HTML::escape( $message ) ) ];
	}

	public static function submitButton( $name = 'submit', $text = NULL, $primary = FALSE, $atts = [], $after = '&nbsp;&nbsp;' )
	{
		$link    = FALSE;
		$classes = [ '-button', 'button' ];

		if ( is_null( $text ) )
			$text = 'reset' == $name
				? _x( 'Reset Settings', 'Settings: Button', 'geditorial' )
				: _x( 'Save Changes', 'Settings: Button', 'geditorial' );

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
				// 'id'      => $name, // FIXME: must sanitize
				'value'   => $text,
				'class'   => $classes,
				'default' => TRUE === $primary,
			] ) );

		echo $after;
	}

	public static function counted( $message = NULL, $count = NULL, $class = 'notice-success' )
	{
		if ( is_null( $message ) )
			/* translators: %s: count */
			$message = _x( '%s Counted!', 'Settings: Message', 'geditorial' );

		if ( is_null( $count ) )
			$count = self::req( 'count', 0 );

		return HTML::notice( sprintf( $message, Number::format( $count ) ), $class.' fade' );
	}

	public static function cheatin( $message = NULL )
	{
		echo HTML::error( is_null( $message ) ? _x( 'Cheatin&#8217; uh?', 'Settings: Message', 'geditorial' ) : $message );
	}

	public static function huh( $message = NULL )
	{
		if ( $message )
			/* translators: %s: message */
			return sprintf( _x( 'huh? %s', 'Settings: Message', 'geditorial' ), $message );

		return _x( 'huh?', 'Settings: Message', 'geditorial' );
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

			echo '<div class="'.HTML::prepClass( '-section-wrap', $section['section_class'] ).'">';

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
			$class = [ '-field' ];

			if ( ! empty( $field['args']['class'] ) )
				$class[] = $field['args']['class'];

			echo '<tr class="'.HTML::prepClass( $class ).'">';

			if ( ! empty( $field['args']['label_for'] ) )
				echo '<th class="-th" scope="row"><label for="'
					.HTML::escape( $field['args']['label_for'] )
					.'">'.$field['title'].'</label></th>';

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
			'value' => _x( 'Enable', 'Settings: Button', 'geditorial' ),
			'style' => $enabled ? 'display:none' : FALSE,
			'class' => [ 'hide-if-no-js', 'button-primary', 'button', 'button-small', '-button' ],
			'data'  => [
				'module' => $module->name,
				'do'     => 'enable',
			],
		] );

		echo HTML::tag( 'input', [
			'type'  => 'submit',
			'value' => _x( 'Disable', 'Settings: Button', 'geditorial' ),
			'style' => $enabled ? FALSE : 'display:none',
			'class' => [ 'hide-if-no-js', 'button-secondary', 'button', 'button-small', '-button', '-button-danger' ],
			'data'  => [
				'module' => $module->name,
				'do'     => 'disable',
			],
		] );

		echo HTML::tag( 'span', [
			'class' => [ 'button', 'hide-if-js' ],
		], _x( 'You have to enable Javascript!', 'Settings: Notice', 'geditorial' ) );
	}

	// FIXME: use `Settings::subURL()`
	public static function moduleConfigure( $module, $enabled = FALSE )
	{
		if ( ! $module->configure )
			return;

		if ( 'tools' === $module->configure )
			echo HTML::tag( 'a', [
				'href'  => add_query_arg( [ 'page' => static::TOOLS, 'sub' => $module->name ], get_admin_url( NULL, 'admin.php' ) ),
				'style' => $enabled ? FALSE : 'display:none',
				'class' => [ 'button-primary', 'button', 'button-small', '-button' ],
				'data'  => [
					'module' => $module->name,
					'do'     => 'configure',
				],
			], _x( 'Tools', 'Settings: Button', 'geditorial' ) );


		else if ( 'reports' === $module->configure )
			echo HTML::tag( 'a', [
				'href'  => add_query_arg( [ 'page' => static::REPORTS, 'sub' => $module->name ], get_admin_url( NULL, 'index.php' ) ),
				'style' => $enabled ? FALSE : 'display:none',
				'class' => [ 'button-primary', 'button', 'button-small', '-button' ],
				'data'  => [
					'module' => $module->name,
					'do'     => 'configure',
				],
			], _x( 'Reports', 'Settings: Button', 'geditorial' ) );

		else
			echo HTML::tag( 'a', [
				'href'  => add_query_arg( [ 'page' => static::SETTINGS, 'module' => $module->name ], get_admin_url( NULL, 'admin.php' ) ),
				'style' => $enabled ? FALSE : 'display:none',
				'class' => [ 'button-primary', 'button', 'button-small', '-button' ],
				'data'  => [
					'module' => $module->name,
					'do'     => 'configure',
				],
			], _x( 'Configure', 'Settings: Button', 'geditorial' ) );
	}

	public static function moduleInfo( $module, $enabled = FALSE, $tag = 'h3' )
	{
		HTML::h3( HTML::tag( 'a', [
			'href'   => self::getModuleDocsURL( $module ),
			/* translators: %s: module title */
			'title'  => sprintf( _x( '%s Documentation', 'Settings', 'geditorial' ), $module->title ),
			'target' => '_blank',
		], $module->title ), '-title' );

		if ( isset( $module->desc ) )
			HTML::desc( $module->desc );

		// list.js filters
		echo '<span class="-module-title" style="display:none;" aria-hidden="true">'.$module->title.'</span>';
		echo '<span class="-module-key" style="display:none;" aria-hidden="true">'.$module->name.'</span>';
		echo '<span class="status" data-do="enabled" style="display:none;" aria-hidden="true">'.( $enabled ? 'true' : 'false' ).'</span>';
	}

	/**
	 * Returns Documentation URL for the module.
	 *
	 * @param boolean|object $module
	 * @return string $url
	 */
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

		echo '<a href="https://geminorum.ir" title="it\'s a geminorum project"><img src="'
			.GEDITORIAL_URL.'assets/images/itsageminorumproject-lightgrey.svg" alt="" /></a>';

		echo '</div>';
	}

	public static function settingsSignature()
	{
		echo '<div class="signature clear"><p>';
			/* translators: %1$s: plugin url, %2$s: author url */
			printf( _x( '<a href="%1$s">gEditorial</a> is a <a href="%2$s">geminorum</a> project.', 'Settings: Signature', 'geditorial' ),
				'https://github.com/geminorum/geditorial',
				'https://geminorum.ir/' );
		echo '</p></div>';
	}

	public static function helpSidebar( $list )
	{
		if ( ! is_array( $list ) )
			return $list;

		$html = '';

		foreach ( $list as $link )
			$html.= '<li>'.HTML::link( $link['title'], $link['url'], TRUE ).'</li>';

		return $html ? HTML::wrap( '<ul>'.$html.'</ul>', '-help-sidebar' ) : FALSE;
	}

	/**
	 * Returns the help content for given module
	 *
	 * @param boolean|object $module
	 * @return array $wiki_info
	 */
	public static function helpContent( $module = FALSE )
	{
		if ( ! function_exists( 'gnetwork_github' ) )
			return [];

		$wikihome = [
			'id'       => 'geditorial-wikihome',
			'title'    => _x( 'Editorial Wiki', 'Settings: Help Content Title', 'geditorial' ),
			'callback' => [ __CLASS__, 'add_help_tab_home_callback' ],
			'module'   => $module,
		];

		if ( FALSE === $module || 'config' === $module->name )
			return [ $wikihome ];

		$wikimodule = [
			'id'       => 'geditorial-'.$module->name.'-wikihome',
			/* translators: %s: module title */
			'title'    => sprintf( _x( '%s Wiki', 'Settings: Help Content Title', 'geditorial' ), $module->title ),
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

		$page = FALSE === $module || 'config' === $module->name
			? 'Home'
			: 'Modules-'.Helper::moduleSlug( $module->name );

		echo gnetwork_github( [
			'repo'    => 'geminorum/geditorial',
			'type'    => 'wiki',
			'page'    => $page,
			'context' => 'help_tab',
		] );
	}

	public static function fieldType( $atts, &$scripts )
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

			'string_disabled' => _x( 'Disabled', 'Settings', 'geditorial' ),
			'string_enabled'  => _x( 'Enabled', 'Settings', 'geditorial' ),
			'string_select'   => self::showOptionNone(),
			'string_empty'    => _x( 'No options!', 'Settings', 'geditorial' ),
			'string_noaccess' => _x( 'You do not have access to change this option.', 'Settings', 'geditorial' ),

			'template_value' => '%s', // used on display value output
		], $atts );

		if ( TRUE === $args['wrap'] )
			$args['wrap'] = 'div';

		if ( 'tr' == $args['wrap'] ) {

			if ( ! empty( $args['label_for'] ) )
				echo '<tr class="'.HTML::prepClass( $args['class'] ).'"><th scope="row"><label for="'.HTML::escape( $args['label_for'] ).'">'.$args['title'].'</label></th><td>';

			else
				echo '<tr class="'.HTML::prepClass( $args['class'] ).'"><th scope="row">'.$args['title'].'</th><td>';

		} else if ( $args['wrap'] ) {

			echo '<'.$args['wrap'].' class="'.HTML::prepClass( '-wrap', '-settings-field', '-'.$args['type'] ).'">';
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
			$id   = $args['id_attr'] ? $args['id_attr'] : ( $args['option_base'] ? $args['option_base'].'-' : '' ).$args['option_group'].'-'.HTML::escape( $args['field'] );
			$name = $args['name_attr'] ? $args['name_attr'] : ( $args['option_base'] ? $args['option_base'].'_' : '' ).$args['option_group'].'['.HTML::escape( $args['field'] ).']';
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

		if ( TRUE === $args['cap'] ) {

			// do nothing!

		} else if ( empty( $args['cap'] ) ) {

			$args['type'] = 'noaccess';

		} else if ( ! current_user_can( $args['cap'] ) ) {

			$args['type'] = 'noaccess';
		}

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
				], HTML::escape( empty( $args['values'][0] ) ? $args['string_disabled'] : $args['values'][0] ) );

				$html.= HTML::tag( 'option', [
					'value'    => '1',
					'selected' => '1' == $value,
				], HTML::escape( empty( $args['values'][1] ) ? $args['string_enabled'] : $args['values'][1] ) );

				echo HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-enabled' ),
					// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
					// @REF: https://stackoverflow.com/a/368834
					'disabled' => $args['disabled'] || $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

				if ( $args['readonly'] )
					HTML::inputHidden( $name, $value );

			break;
			case 'disabled':

				$html = HTML::tag( 'option', [
					'value'    => '0',
					'selected' => '0' == $value,
				], empty( $args['values'][0] ) ? $args['string_enabled'] : $args['values'][0] );

				$html.= HTML::tag( 'option', [
					'value'    => '1',
					'selected' => '1' == $value,
				], empty( $args['values'][1] ) ? $args['string_disabled'] : $args['values'][1] );

				echo HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-disabled' ),
					// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
					// @REF: https://stackoverflow.com/a/368834
					'disabled' => $args['disabled'] || $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

				if ( $args['readonly'] )
					HTML::inputHidden( $name, $value );

			break;
			case 'text':

				if ( ! $args['field_class'] )
					$args['field_class'] = 'regular-text';

				if ( FALSE === $args['values'] ) {

					HTML::desc( $args['string_empty'], TRUE, '-empty' );

				} else if ( count( $args['values'] ) ) {

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = HTML::tag( 'input', [
							'type'        => 'text',
							'id'          => $id.'-'.$value_name,
							'name'        => $name.'['.$value_name.']',
							'value'       => isset( $value[$value_name] ) ? $value[$value_name] : '',
							'class'       => HTML::attrClass( $args['field_class'], '-type-text' ),
							'placeholder' => $args['placeholder'],
							'disabled'    => HTML::attrBoolean( $args['disabled'], $value_name ),
							'readonly'    => HTML::attrBoolean( $args['readonly'], $value_name ),
							'dir'         => $args['dir'],
							'data'        => $args['data'],
						] );

						$html.= '&nbsp;<span class="-field-after">'.$value_title.'</span>';

						HTML::label( $html, $id.'-'.$value_name );
					}

				} else {

					echo HTML::tag( 'input', [
						'type'        => 'text',
						'id'          => $id,
						'name'        => $name,
						'value'       => $value,
						'class'       => HTML::attrClass( $args['field_class'], '-type-text' ),
						'placeholder' => $args['placeholder'],
						'disabled'    => $args['disabled'],
						'readonly'    => $args['readonly'],
						'dir'         => $args['dir'],
						'data'        => $args['data'],
					] );
				}

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
					'value'       => (int) $value,
					'step'        => (int) $args['step_attr'],
					'min'         => (int) $args['min_attr'],
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
					'class'       => HTML::attrClass( $args['field_class'], '-type-url' ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				] );

			break;
			case 'color':

				if ( ! $args['field_class'] )
					$args['field_class'] = [ 'small-text', 'color-text' ];

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				echo HTML::tag( 'input', [
					'type'        => 'text', // it's better to be `text`
					'id'          => $id,
					'name'        => $name,
					'value'       => $value,
					'class'       => HTML::attrClass( $args['field_class'], '-type-color' ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				] );

				// CAUTION: module must enqueue `wp-color-picker` styles/scripts
				// @SEE: `Scripts::enqueueColorPicker()`
				$scripts[] = '$("#'.$id.'").wpColorPicker();';

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
					'class'       => HTML::attrClass( $args['field_class'], '-type-email' ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				] );

			break;
			case 'checkbox':

				$html = HTML::tag( 'input', [
					'type'     => 'checkbox',
					'id'       => $id,
					'name'     => $name,
					'value'    => '1',
					'checked'  => $value,
					'class'    => HTML::attrClass( $args['field_class'], '-type-checkbox' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				] );

				HTML::label( $html.'&nbsp;'.$args['description'], $id );

				$args['description'] = FALSE;

			break;
			case 'checkboxes':
			case 'checkboxes-values':

				if ( count( $args['values'] ) ) {

					if ( ! is_null( $args['none_title'] ) ) {

						$html = HTML::tag( 'input', [
							'type'     => 'checkbox',
							'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'name'     => $name.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'value'    => is_null( $args['none_value'] ) ? '1' : $args['none_value'],
							'checked'  => FALSE === $value || in_array( $args['none_value'], (array) $value ),
							'class'    => HTML::attrClass( $args['field_class'], '-type-checkbox', '-option-none' ),
							'disabled' => HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
							'readonly' => HTML::attrBoolean( $args['readonly'], $args['none_value'] ),
							'dir'      => $args['dir'],
						] );

						$for = $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] );

						HTML::label( $html.'&nbsp;'.$args['none_title'], $for );
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = HTML::tag( 'input', [
							'type'     => 'checkbox',
							'id'       => $id.'-'.$value_name,
							'name'     => $name.'['.$value_name.']',
							'value'    => '1',
							'checked'  => TRUE === $value || in_array( $value_name, (array) $value ),
							'class'    => HTML::attrClass( $args['field_class'], '-type-checkbox' ),
							'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
							'readonly' => HTML::attrBoolean( $args['readonly'], $value_name ),
							'dir'      => $args['dir'],
						] );

						$html.= '&nbsp;'.$value_title;

						if ( 'checkboxes-values' == $args['type'] )
							$html.= ' &mdash; <code>'.sprintf( $args['template_value'], $value_name ).'</code>';

						HTML::label( $html, $id.'-'.$value_name );
					}

				} else if ( is_array( $args['values'] ) ) {

					$args['description'] = FALSE;
					HTML::desc( $args['string_empty'], TRUE, '-empty' );
				}

			break;
			case 'checkbox-panel':

				if ( count( $args['values'] ) ) {

					echo '<div class="wp-tab-panel"><ul>';

					if ( ! is_null( $args['none_title'] ) ) {

						$html = HTML::tag( 'input', [
							'type'     => 'checkbox',
							'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'name'     => $name.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'value'    => is_null( $args['none_value'] ) ? '1' : $args['none_value'],
							'checked'  => FALSE === $value || in_array( $args['none_value'], (array) $value ),
							'class'    => HTML::attrClass( $args['field_class'], '-type-checkbox', '-option-none' ),
							'disabled' => HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
							'readonly' => HTML::attrBoolean( $args['readonly'], $args['none_value'] ),
							'dir'      => $args['dir'],
						] );

						$for = $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] );

						HTML::label( $html.'&nbsp;'.$args['none_title'], $for, 'li' );
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = HTML::tag( 'input', [
							'type'     => 'checkbox',
							'id'       => $id.'-'.$value_name,
							'name'     => $name.'['.$value_name.']',
							'value'    => '1',
							'checked'  => TRUE === $value || in_array( $value_name, (array) $value ),
							'class'    => HTML::attrClass( $args['field_class'], '-type-checkbox' ),
							'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
							'readonly' => HTML::attrBoolean( $args['readonly'], $value_name ),
							'dir'      => $args['dir'],
						] );

						HTML::label( $html.'&nbsp;'.$value_title, $id.'-'.$value_name, 'li' );
					}

					echo '</ul></div>';

				} else if ( is_array( $args['values'] ) ) {

					$args['description'] = FALSE;
					HTML::desc( $args['string_empty'], TRUE, '-empty' );
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
							'disabled' => HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
							'readonly' => HTML::attrBoolean( $args['readonly'], $args['none_value'] ),
							'dir'      => $args['dir'],
						] );

						$for = $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] );

						HTML::label( $html.'&nbsp;'.$args['none_title'], $for );
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
							'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
							'readonly' => HTML::attrBoolean( $args['readonly'], $value_name ),
							'dir'      => $args['dir'],
						] );

						HTML::label( $html.'&nbsp;'.$value_title, $id.'-'.$value_name );
					}
				}

			break;
			case 'select':

				if ( FALSE !== $args['values'] ) {

					if ( ! is_null( $args['none_title'] ) ) {

						if ( is_null( $args['none_value'] ) )
							$args['none_value'] = '0';

						$html.= HTML::tag( 'option', [
							'value'    => $args['none_value'],
							'selected' => $value == $args['none_value'],
							'disabled' => HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
						], $args['none_title'] );
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html.= HTML::tag( 'option', [
							'value'    => $value_name,
							'selected' => $value == $value_name,
							'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
						], $value_title );
					}

					echo HTML::tag( 'select', [
						'id'       => $id,
						'name'     => $name,
						'class'    => HTML::attrClass( $args['field_class'], '-type-select' ),
						// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
						// @REF: https://stackoverflow.com/a/368834
						// `disabled` previously applied to `option` elements
						'disabled' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					], $html );

					if ( $args['readonly'] )
						HTML::inputHidden( $name, $value );

				} else {

					HTML::desc( $args['string_empty'], TRUE, '-empty' );
				}

			break;
			case 'textarea':
			case 'textarea-quicktags':
			case 'textarea-quicktags-tokens':
			case 'textarea-code-editor':

				if ( ! $args['field_class'] )
					$args['field_class'] = [ 'regular-text', 'textarea-autosize' ];

				if ( 'textarea-quicktags' == $args['type'] ) {

					$args['field_class'] = HTML::attrClass( $args['field_class'], 'textarea-quicktags', 'code' );

					if ( ! $args['dir'] && HTML::rtl() )
						$args['field_class'][] = 'quicktags-rtl';

					if ( ! $args['values'] )
						$args['values'] = [
							'link',
							'em',
							'strong',
						];

					$scripts[] = 'quicktags({id:"'.$id.'",buttons:"'.implode( ',', $args['values'] ).'"});';

					wp_enqueue_script( 'quicktags' );

				} else if ( 'textarea-quicktags-tokens' == $args['type'] ) {

					$args['field_class'] = HTML::attrClass( $args['field_class'], 'textarea-quicktags', 'code' );

					if ( ! $args['dir'] && HTML::rtl() )
						$args['field_class'][] = 'quicktags-rtl';

					if ( ! $args['values'] )
						$args['values'] = [
							'subject',
							'content',
							'topic',
							'site',
							'domain',
							'url',
							'display_name',
							'email',
							'useragent',
						];

					$scripts[] = 'quicktags({id:"'.$id.'",buttons:"_none"});';

					foreach ( $args['values'] as $button )
						$scripts[] = 'QTags.addButton("token_'.$button.'","'.$button.'","{{'.$button.'}}","","","",0,"'.$id.'");';

					wp_enqueue_script( 'quicktags' );

				} else if ( 'textarea-code-editor' == $args['type'] ) {

					// @SEE: `wp_get_code_editor_settings()`
					if ( ! $args['values'] )
						$args['values'] = [
							'lineNumbers'  => TRUE,
							'lineWrapping' => TRUE,
							'mode'         => 'htmlmixed',
						];

					// CAUTION: module must enqueue `code-editor` styles/scripts
					// @SEE: `Scripts::enqueueCodeEditor()`
					$scripts[] = sprintf( 'wp.CodeMirror.fromTextArea(document.getElementById("%s"), %s);',
						$id, wp_json_encode( $args['values'] ) );
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
				], esc_textarea( $value ) );

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

					$html.= HTML::tag( 'option', [
						'value' => $args['none_value'],
					], $args['none_title'] );

					$html.= walk_page_dropdown_tree( $pages, ( isset( $query['depth'] ) ? $query['depth'] : 0 ), $query );

					echo HTML::tag( 'select', [
						'id'       => $id,
						'name'     => $name,
						'class'    => HTML::attrClass( $args['field_class'], '-type-page', '-posttype-'.$args['values'] ),
						// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
						// @REF: https://stackoverflow.com/a/368834
						'disabled' => $args['disabled'] || $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					], $html );

					if ( $args['readonly'] )
						HTML::inputHidden( $name, $value );

				} else {

					$args['description'] = FALSE;
				}

			break;

			case 'navmenu':

				if ( ! $args['values'] )
					$args['values'] = wp_list_pluck( wp_get_nav_menus(), 'name', 'term_id' );

				if ( ! empty( $args['values'] ) ) {

					if ( is_null( $args['none_title'] ) )
						$args['none_title'] = $args['string_select'];

					if ( is_null( $args['none_value'] ) )
						$args['none_value'] = '0';

					$html.= HTML::tag( 'option', [
						'value'    => $args['none_value'],
						'selected' => $value == $args['none_value'],
						'disabled' => HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
					], $args['none_title'] );

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html.= HTML::tag( 'option', [
							'value'    => $value_name,
							'selected' => $value == $value_name,
							'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
						], $value_title );
					}

					echo HTML::tag( 'select', [
						'id'       => $id,
						'name'     => $name,
						'class'    => HTML::attrClass( $args['field_class'], '-type-navmenu' ),
						// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
						// @REF: https://stackoverflow.com/a/368834
						// `disabled` previously applied to `option` elements
						'disabled' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					], $html );

					if ( $args['readonly'] )
						HTML::inputHidden( $name, $value );

				} else {

					HTML::desc( $args['string_empty'], TRUE, '-empty' );

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

				$html.= HTML::tag( 'option', [
					'value'    => $args['none_value'],
					'selected' => $value == $args['none_value'],
					'disabled' => HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
				], $args['none_title'] );

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html.= HTML::tag( 'option', [
						'value'    => $value_name,
						'selected' => $value == $value_name,
						'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
					], HTML::escape( translate_user_role( $value_title['name'] ) ) );
				}

				echo HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-role' ),
					// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
					// @REF: https://stackoverflow.com/a/368834
					'disabled' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

				if ( $args['readonly'] )
					HTML::inputHidden( $name, $value );

			break;
			case 'user':

				if ( ! $args['values'] )
					$args['values'] = User::get( FALSE, FALSE, $args['extra'] );

				if ( ! is_null( $args['none_title'] ) ) {

					if ( is_null( $args['none_value'] ) )
						$args['none_value'] = FALSE;

					$html.= HTML::tag( 'option', [
						'value'    => $args['none_value'],
						'selected' => $value == $args['none_value'],
						'disabled' => HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
					], $args['none_title'] );
				}

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html.= HTML::tag( 'option', [
						'value'    => $value_name,
						'selected' => $value == $value_name,
						'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
					], HTML::escape( sprintf( '%1$s (%2$s)', $value_title->display_name, $value_title->user_login ) ) );
				}

				echo HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-user' ),
					// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
					// @REF: https://stackoverflow.com/a/368834
					'disabled' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

				if ( $args['readonly'] )
					HTML::inputHidden( $name, $value );

			break;
			case 'priority':

				if ( ! $args['values'] )
					$args['values'] = self::priorityOptions( FALSE );

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html.= HTML::tag( 'option', [
						'value'    => $value_name,
						'selected' => $value == $value_name,
						'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
					], HTML::escape( $value_title ) );
				}

				echo HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-priority' ),
					// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
					// @REF: https://stackoverflow.com/a/368834
					'disabled' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

				if ( $args['readonly'] )
					HTML::inputHidden( $name, $value );

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
					'name'     => $name,
					'class'    => HTML::attrClass( $args['field_class'], '-type-file' ),
					'disabled' => $args['disabled'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
					'accept'   => empty( $args['values'] ) ? FALSE : implode( ',', $args['values'] ),
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
						'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
						'readonly' => HTML::attrBoolean( $args['readonly'], $value_name ),
						'dir'      => $args['dir'],
					] );

					$html.= '&nbsp;'.HTML::escape( $value_title );
					$html.= ' &mdash; <code>'.sprintf( $args['template_value'], $value_name ).'</code>';

					HTML::label( $html, $id.'-'.$value_name );
				}

			break;
			case 'taxonomies':

				if ( ! $args['values'] )
					$args['values'] = Taxonomy::get( 0, $args['extra'] );

				echo '<div class="wp-tab-panel"><ul>';

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
						'disabled' => HTML::attrBoolean( $args['disabled'], $value_name ),
						'readonly' => HTML::attrBoolean( $args['readonly'], $value_name ),
						'dir'      => $args['dir'],
					] );

					$html.= '&nbsp;'.HTML::escape( $value_title );
					$html.= ' &mdash; <code>'.sprintf( $args['template_value'], $value_name ).'</code>';

					HTML::label( $html, $id.'-'.$value_name, 'li' );
				}

				echo '</ul></div>';

			break;
			case 'object':

				if ( $args['values'] ) {

					if ( empty( $value ) )
						$value = [];

					echo '<div class="-wrap -type-object-wrap" data-setting="type-object" data-field="'.$args['field'].'">';

						echo self::fieldType_getObjectForm( $args, $args['values'], $name );
						echo '<div class="-body">';

						foreach ( $value as $value_value )
							echo self::fieldType_getObjectForm( $args, $args['values'], $name, $value_value );

						echo '</div><p data-setting="object-controls" class="submit geditorial-wrap -wrap-buttons">';

							echo HTML::tag( 'a', [
								'href'  => '#',
								'class' => '-icon-button',
								'data'  => [
									'setting' => 'object-addnew',
									'target'  => $args['field'],
								],
							], HTML::getDashicon( 'plus-alt' ) );

					echo '</p></div>';

					Scripts::enqueue( 'settings.typeobject' );

				} else {

					HTML::desc( $args['string_empty'], TRUE, '-empty' );
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

		if ( 'tr' == $args['wrap'] )
			echo '</td></tr>';

		else if ( $args['wrap'] )
			echo '</'.$args['wrap'].'>';
	}

	// FIXME: support more types!
	// WTF: not possible to pass fields with arrays (checknoxes/multiple select)
	private static function fieldType_getObjectForm( $args, $fields, $name_prefix = '', $options = [] )
	{
		$group = '';

		foreach ( $fields as $index => $field ) {

			$html = '';
			$name = $name_prefix.'['.$field['field'].'][]';

			$default     = array_key_exists( 'default', $field ) ? $field['default'] : '';
			$value       = array_key_exists( $field['field'], $options ) ? $options[$field['field']] : $default;
			$placeholder = array_key_exists( 'placeholder', $field ) ? $field['placeholder'] : FALSE;
			$description = array_key_exists( 'description', $field ) ? $field['description'] : FALSE;
			$values      = array_key_exists( 'values', $field ) ? $field['values'] : [];

			switch ( $field['type'] ) {

				case 'select':

					if ( FALSE !== $values ) {

						if ( empty( $field['field_class'] ) )
							$field['field_class'] = '';

						if ( empty( $field['dir'] ) )
							$field['dir'] = FALSE;

						foreach ( $values as $value_name => $value_title ) {

							$html.= HTML::tag( 'option', [
								'value'    => $value_name,
								'selected' => $value == $value_name,
							], $value_title );
						}

						$html = HTML::tag( 'select', [
							'name'  => $name,
							'class' => HTML::attrClass( $field['field_class'], '-type-select' ),
							'dir'   => $field['dir'],
						], $html );

						$html.= '&nbsp;<span class="-field-after">'.$field['title'].'</span>';
					}

					break;

				case 'number':

					if ( empty( $field['field_class'] ) )
						$field['field_class'] = 'small-text';

					if ( empty( $field['dir'] ) )
						$field['dir'] = 'ltr';

					$html = HTML::tag( 'input', [
						'type'        => 'text',
						'name'        => $name,
						'placeholder' => $placeholder,
						'title'       => $description,
						'value'       => $value,
						'class'       => HTML::attrClass( $field['field_class'], '-type-number' ),
						'dir'         => $field['dir'],
					] );

					$html.= '&nbsp;<span class="-field-after">'.$field['title'].'</span>';

					break;

				case 'text':
				default:

					if ( empty( $field['field_class'] ) )
						$field['field_class'] = 'regular-text';

					$html = HTML::tag( 'input', [
						'type'        => 'text',
						'name'        => $name,
						'placeholder' => $placeholder,
						'title'       => $description,
						'value'       => $value,
						'class'       => HTML::attrClass( $field['field_class'], '-type-text' ),
						'dir'         => empty( $field['dir'] ) ? FALSE : $field['dir'],
					] );

					$html.= '&nbsp;<span class="-field-after">'.$field['title'].'</span>';
			}

			$group.= HTML::tag( 'p', $html );
		}

		$group.= '<div data-setting="object-group-controls" class="-group-controls">';
			$group.= HTML::tag( 'a', [
				'href'  => '#',
				'class' => '-icon-button',
				'data'  => [
					'setting' => 'object-remove',
					'target'  => $args['field'],
				],
			], HTML::getDashicon( 'dismiss' ) );
		$group.= '</div>';

		return HTML::tag( 'div', [
			'class' => '-object-group',
			'style' => empty( $options ) ? 'display:none' : FALSE,
			'data'  => empty( $options ) ? [ 'setting' => 'object-empty' ] : FALSE,
		], $group );
	}

	public static function fieldType_switchOnOff( $atts = [] )
	{
		$args = self::atts( [
			'id'         => FALSE,
			'name'       => '',
			'class'      => FALSE,
			'checked'    => FALSE,
			'disabled'   => FALSE,
			'string_on'  => _x( 'On', 'Settings: Switch On-Off', 'geditorial' ),
			'string_off' => _x( 'Off', 'Settings: Switch On-Off', 'geditorial' ),
			'echo'       => TRUE,
		], $atts );

		$input = HTML::tag( 'input', [
			'type'     => 'checkbox',
			'value'    => '1',
			'id'       => $args['id'],
			'name'     => $args['name'],
			'checked'  => $args['checked'],
			'disabled' => $args['disabled'],
			'class'    => HTML::attrClass( $args['class'], '-type-switchonoff-input -checkbox' ), // `.checkbox`
		] );

		$html = '<span class="switch__circle"><span class="switch__circle-inner"></span></span>';
		$html.= '<span class="switch__left">'.$args['string_off'].'</span>';
		$html.= '<span class="switch__right">'.$args['string_on'].'</span>';

		$label = HTML::tag( 'label', [
			'for'   => $args['id'],
			'class' => '-type-switchonoff-label -switch', // `.switch`
		], $html );

		$html = HTML::wrap( $input.$label, '-type-switchonoff' );

		if ( ! $args['echo'] )
			return $html;

		echo $html;

		return TRUE;
	}
}
