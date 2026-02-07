<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Settings extends WordPress\Main
{

	const BASE     = 'geditorial';
	const REPORTS  = 'geditorial-reports';   // NOTE: DEPRECATED
	const SETTINGS = 'geditorial-settings';  // NOTE: DEPRECATED
	const TOOLS    = 'geditorial-tools';     // NOTE: DEPRECATED
	const ROLES    = 'geditorial-roles';     // NOTE: DEPRECATED
	const IMPORTS  = 'geditorial-imports';   // NOTE: DEPRECATED
	const CUSTOMS  = 'geditorial-customs';   // NOTE: DEPRECATED

	public static function factory()
	{
		return gEditorial();
	}

	public static function getURLbyContext( $context, $full = TRUE, $extra = [] )
	{
		$url = sprintf( 'admin.php?page=%s', self::classs( $context ) );

		switch ( $context ) {

			case 'roles':

				if ( current_user_can( 'list_users' ) )
					$url = sprintf( 'users.php?page=%s', self::classs( $context ) );

				break;

			case 'tools':
			case 'imports':

				if ( current_user_can( 'edit_posts' ) )
					$url = sprintf( 'tools.php?page=%s', self::classs( $context ) );

				break;

			case 'customs':

				if ( current_user_can( 'edit_theme_options' ) )
					$url = sprintf( 'themes.php?page=%s', self::classs( $context ) );

				break;

			case 'settings':
				break;

			default:
			case 'dashboard':
			case 'reports':

				$url = sprintf( 'index.php?page=%s', self::classs( $context ) );
		}

		if ( $full )
			$url = get_admin_url( NULL, $url );

		return $extra ? add_query_arg( $extra, $url ) : $url;
	}

	public static function isScreenContext( $context, $screen = NULL )
	{
		$screen = $screen ?? get_current_screen();

		if ( ! empty( $screen->base ) && Core\Text::has( $screen->base, self::classs( $context ) ) )
			return TRUE;

		return FALSE;
	}

	// NOTE: DEPRECATED
	// NOTE: better to use `$this->get_module_url()`
	public static function subURL( $sub = FALSE, $context = 'reports', $extra = [] )
	{
		self::_dep( 'Settings::getURLbyContext()' );

		switch ( $context ) {
			case 'reports' : $url = self::reportsURL();  break;
			case 'settings': $url = self::settingsURL(); break;
			case 'tools'   : $url = self::toolsURL();    break;
			case 'roles'   : $url = self::rolesURL();    break;
			case 'imports' : $url = self::importsURL();  break;
			case 'customs' : $url = self::customsURL();  break;
			default        : $url = Core\URL::current();
		}

		return add_query_arg( array_merge( [
			'sub' => $sub,
		], $extra ), $url );
	}

	// NOTE: DEPRECATED
	public static function reportsURL( $full = TRUE, $dashboard = FALSE )
	{
		self::_dep( 'Settings::getURLbyContext()' );

		$relative = 'index.php?page='.self::REPORTS;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	// NOTE: DEPRECATED
	public static function settingsURL( $full = TRUE )
	{
		self::_dep( 'Settings::getURLbyContext()' );

		$relative = 'admin.php?page='.self::SETTINGS;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	// NOTE: DEPRECATED: problem with dashboard
	public static function toolsURL( $full = TRUE, $tools_menu = FALSE )
	{
		self::_dep( 'Settings::getURLbyContext()' );

		$relative = $tools_menu ? 'tools.php?page='.self::TOOLS : 'admin.php?page='.self::TOOLS;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	// NOTE: DEPRECATED: problem with dashboard
	public static function rolesURL( $full = TRUE, $users_menu = FALSE )
	{
		self::_dep( 'Settings::getURLbyContext()' );

		$relative = $users_menu ? 'users.php?page='.self::ROLES : 'admin.php?page='.self::ROLES;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	// NOTE: DEPRECATED
	public static function importsURL( $full = TRUE )
	{
		self::_dep( 'Settings::getURLbyContext()' );

		// NOTE: `tools.php` hard-coded for users with `edit_posts` cap!
		if ( current_user_can( 'edit_posts' ) )
			$relative = 'tools.php?page='.self::IMPORTS;

		else
			$relative = 'import.php?page='.self::IMPORTS;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	// NOTE: DEPRECATED
	public static function customsURL( $full = TRUE )
	{
		self::_dep( 'Settings::getURLbyContext()' );

		$relative = 'themes.php?page='.self::CUSTOMS;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	// NOTE: DEPRECATED
	public static function isReports( $screen = NULL )
	{
		self::_dep( 'Settings::isScreenContext()' );

		$screen = $screen ?? get_current_screen();

		if ( ! empty( $screen->base ) && Core\Text::has( $screen->base, self::REPORTS ) )
			return TRUE;

		return FALSE;
	}

	// NOTE: DEPRECATED
	public static function isSettings( $screen = NULL )
	{
		self::_dep( 'Settings::isScreenContext()' );

		$screen = $screen ?? get_current_screen();

		if ( ! empty( $screen->base ) && Core\Text::has( $screen->base, self::SETTINGS ) )
			return TRUE;

		return FALSE;
	}

	// NOTE: DEPRECATED
	public static function isTools( $screen = NULL )
	{
		self::_dep( 'Settings::isScreenContext()' );

		$screen = $screen ?? get_current_screen();

		if ( ! empty( $screen->base ) && Core\Text::has( $screen->base, self::TOOLS ) )
			return TRUE;

		return FALSE;
	}

	// NOTE: DEPRECATED
	public static function isRoles( $screen = NULL )
	{
		self::_dep( 'Settings::isScreenContext()' );

		$screen = $screen ?? get_current_screen();

		if ( ! empty( $screen->base ) && Core\Text::has( $screen->base, self::ROLES ) )
			return TRUE;

		return FALSE;
	}

	// NOTE: DEPRECATED
	public static function isImports( $screen = NULL )
	{
		self::_dep( 'Settings::isScreenContext()' );

		$screen = $screen ?? get_current_screen();

		if ( ! empty( $screen->base ) && Core\Text::has( $screen->base, self::IMPORTS ) )
			return TRUE;

		return FALSE;
	}

	// NOTE: DEPRECATED
	public static function isCustoms( $screen = NULL )
	{
		self::_dep( 'Settings::isScreenContext()' );

		$screen = $screen ?? get_current_screen();

		if ( ! empty( $screen->base ) && Core\Text::has( $screen->base, self::CUSTOMS ) )
			return TRUE;

		return FALSE;
	}

	// NOTE: DEPRECATED
	public static function isDashboard( $screen = NULL )
	{
		self::_dep( 'Settings::isScreenContext()' );

		$screen = $screen ?? get_current_screen();

		if ( ! empty( $screen->base ) && Core\Text::has( $screen->base, 'dashboard' ) )
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

		return Core\Arraay::prepNumeral( apply_filters( static::BASE.'_page_excludes', $pages, $context ) );
	}

	public static function priorityOptions( $format = TRUE )
	{
		return
			array_reverse( Core\Arraay::range( -100, -1000, 100, $format ), TRUE ) +
			array_reverse( Core\Arraay::range( -10, -100, 10, $format ), TRUE ) +
			Core\Arraay::range( 0, 100, 10, $format ) +
			Core\Arraay::range( 100, 1000, 100, $format );
	}

	public static function minutesOptions()
	{
		return [
			'5'    => _x( '5 Minutes', 'Settings: Option: Time in Minutes', 'geditorial-admin' ),
			'10'   => _x( '10 Minutes', 'Settings: Option: Time in Minutes', 'geditorial-admin' ),
			'15'   => _x( '15 Minutes', 'Settings: Option: Time in Minutes', 'geditorial-admin' ),
			'30'   => _x( '30 Minutes', 'Settings: Option: Time in Minutes', 'geditorial-admin' ),
			'60'   => _x( '60 Minutes', 'Settings: Option: Time in Minutes', 'geditorial-admin' ),
			'120'  => _x( '2 Hours', 'Settings: Option: Time in Minutes', 'geditorial-admin' ),
			'180'  => _x( '3 Hours', 'Settings: Option: Time in Minutes', 'geditorial-admin' ),
			'240'  => _x( '4 Hours', 'Settings: Option: Time in Minutes', 'geditorial-admin' ),
			'480'  => _x( '8 Hours', 'Settings: Option: Time in Minutes', 'geditorial-admin' ),
			'1440' => _x( '24 Hours', 'Settings: Option: Time in Minutes', 'geditorial-admin' ),
		];
	}

	public static function supportsOptions()
	{
		return [
			'title'           => _x( 'Title', 'Settings: Option: PostType Support', 'geditorial-admin' ),
			'editor'          => _x( 'Editor', 'Settings: Option: PostType Support', 'geditorial-admin' ), // NOTE: `'editor' => [ 'notes' => TRUE ],` // @SEE: https://make.wordpress.org/core/2025/11/15/notes-feature-in-wordpress-6-9/
			'excerpt'         => _x( 'Excerpt', 'Settings: Option: PostType Support', 'geditorial-admin' ),
			'author'          => _x( 'Author', 'Settings: Option: PostType Support', 'geditorial-admin' ),
			'autosave'        => _x( 'Auto-Save', 'Settings: Option: PostType Support', 'geditorial-admin' ), // NOTE: For backward compatibility reasons, adding `editor` support implies `autosave` support, so one would need to explicitly use `remove_post_type_support()` to remove it. @REF https://core.trac.wordpress.org/changeset/58201
			'thumbnail'       => _x( 'Thumbnail', 'Settings: Option: PostType Support', 'geditorial-admin' ),
			'comments'        => _x( 'Comments', 'Settings: Option: PostType Support', 'geditorial-admin' ),
			'trackbacks'      => _x( 'Trackbacks', 'Settings: Option: PostType Support', 'geditorial-admin' ),
			'custom-fields'   => _x( 'Custom Fields', 'Settings: Option: PostType Support', 'geditorial-admin' ),
			'post-formats'    => _x( 'Post Formats', 'Settings: Option: PostType Support', 'geditorial-admin' ),
			'revisions'       => _x( 'Revisions', 'Settings: Option: PostType Support', 'geditorial-admin' ),
			'page-attributes' => _x( 'Post Attributes', 'Settings: Option: PostType Support', 'geditorial-admin' ),
			'amp'             => _x( 'Accelerated Mobile Pages', 'Settings: Option: PostType Support', 'geditorial-admin' ),
			'date-picker'     => _x( 'Persian Date: Date Picker', 'Settings: Option: PostType Support', 'geditorial-admin' ),
			'editorial-meta'  => _x( 'Editorial: Meta Fields', 'Settings: Option: PostType Support', 'geditorial-admin' ),
			'editorial-seo'   => _x( 'Editorial: SEO', 'Settings: Option: PostType Support', 'geditorial-admin' ),
			'editorial-geo'   => _x( 'Editorial: Geo Data', 'Settings: Option: PostType Support', 'geditorial-admin' ),
			'editorial-units' => _x( 'Editorial: Measurement Units', 'Settings: Option: PostType Support', 'geditorial-admin' ),
			'editorial-roles' => _x( 'Editorial: Custom Roles', 'Settings: Option: PostType Support', 'geditorial-admin' ),
		];
	}

	public static function posttypesParents( $extra = [], $context = 'settings' )
	{
		$list = [
			'human',
			'team_member',
			'department',
			'publication',
			'entry',
		];

		return Core\Arraay::prepString( apply_filters( static::BASE.'_posttypes_parents', array_merge( $list, (array) $extra ), $context ) );
	}

	/**
	 * Returns the filtered list of general excluded post-types.
	 *
	 * @param array $extra
	 * @param string $context
	 * @return array $excluded
	 */
	public static function posttypesExcluded( $extra = [], $keeps = [], $context = 'settings' )
	{
		$list = [
			'attachment',          // WP Core
			'revision',            // WP Core
			'nav_menu_item',       // WP Core
			'custom_css',          // WP Core
			'customize_changeset', // WP Core
			'wp_block',            // WP Core
			'wp_global_styles',    // WP Core
			'wp_navigation',       // WP Core
			'wp_template_part',    // WP Core
			'wp_template',         // WP Core
			'user_request',        // WP Core
			'oembed_cache',        // WP Core

			'bp-email'          ,   // BuddyPress
			// 'product'           ,   // WooCommerce
			'shop_order'        ,   // WooCommerce
			'shop_coupon'       ,   // WooCommerce
			'guest-author'      ,   // Co-Authors Plus
			'amp_validated_url' ,   // AMP
			'inbound_message'   ,   // Flamingo
			'table'             ,   // TablePress
			'wafs'              ,   // Advanced Free Shipping for WooCommerce
			'csp-report'        ,   // HTTPS Mixed Content Detector
		];

		if ( class_exists( 'bbPress' ) )
			$list = array_merge( $list, [
				'forum',
				'topic',
				'reply',
			] );

		// @hook: `geditorial_posttypes_excluded`
		return apply_filters(
			implode( '_', [ static::BASE, 'posttypes', 'excluded' ] ),
			array_diff( array_merge( $list, (array) $extra ), (array) $keeps ),
			$context,
			(array) $extra,
			(array) $keeps
		);
	}

	public static function taxonomiesExcluded( $extra = [], $keeps = [], $context = 'settings' )
	{
		$list = [
			'nav_menu',               // WP Core
			'wp_theme',               // WP Core
			'link_category',          // WP Core
			'post_format',            // WP Core
			'wp_template_part_area',  // WP Core
			'wp_pattern_category',    // WP Core
			'amp_validation_error',   // AMP
			'product_type',           // WooCommerce
			'product_visibility',     // WooCommerce
			'product_shipping_class', // WooCommerce
			'bp-email-type',          // BuddyPress
			'bp_member_type',         // BuddyPress
			'bp_group_type',          // BuddyPress

			'ef_editorial_meta',  // EditFlow
			'ef_usergroup'     ,  // EditFlow
			'following_users'  ,  // EditFlow ?!

			'table_category', // TablePress

			'flamingo_contact_tag'    ,  // Flamingo
			'flamingo_inbound_channel',  // Flamingo

			'audit_attribute' ,   // Editorial: Audit
			'affiliation'     ,   // Editorial: People
			// 'relation'        ,   // Editorial: Byline
			'user_group'      ,   // Editorial: Users
			'user_type'       ,   // Editorial: Users
			// 'event_calendar'  ,   // Editorial: Happening
			// 'event_type'      ,   // Editorial: Happening
			'redundant', // Editorial: Redundancy

			'cartable_user' ,  // Editorial
			'cartable_group',  // Editorial
			'follow_users'  ,  // Editorial
			'follow_groups' ,  // Editorial
		];

		if ( class_exists( 'bbPress' ) )
			$list = array_merge( $list, [
				'topic-tag',
			] );

		// @hook: `geditorial_taxonomies_excluded`
		return apply_filters(
			implode( '_', [ static::BASE, 'taxonomies', 'excluded' ] ),
			array_diff( array_merge( $list, (array) $extra ), (array) $keeps ),
			$context,
			(array) $extra,
			(array) $keeps
		);
	}

	public static function rolesExcluded( $extra = [], $keeps = [], $context = 'settings' )
	{
		$list = [
			'administrator',  // WP Core
			'subscriber',     // WP Core

			'backwpup_admin',
			'backwpup_check',
			'backwpup_helper',
		];

		// @hook: `geditorial_roles_excluded`
		return apply_filters(
			implode( '_', [ static::BASE, 'roles', 'excluded' ] ),
			array_diff( array_merge( $list, (array) $extra ), (array) $keeps ),
			$context,
			(array) $extra,
			(array) $keeps
		);
	}

	public static function showOptionNone( $string = NULL )
	{
		if ( $string )
			return sprintf(
				/* translators: `%s`: options */
				_x( '&ndash; Select %s &ndash;', 'Settings: Dropdown Select Option None', 'geditorial-admin' ),
				$string
			);

		return _x( '&ndash; Select &ndash;', 'Settings: Dropdown Select Option None', 'geditorial-admin' );
	}

	public static function showRadioNone( $string = NULL )
	{
		if ( $string )
			return sprintf(
				/* translators: `%s`: options */
				_x( 'None %s', 'Settings: Radio Select Option None', 'geditorial-admin' ),
				$string
			);

		return _x( 'None', 'Settings: Radio Select Option None', 'geditorial-admin' );
	}

	public static function showOptionAll( $string = NULL )
	{
		if ( $string )
			return sprintf(
				/* translators: `%s`: options */
				_x( '&ndash; All %s &ndash;', 'Settings: Dropdown Select Option All', 'geditorial-admin' ),
				$string
			);

		return _x( '&ndash; All &ndash;', 'Settings: Dropdown Select Option All', 'geditorial-admin' );
	}

	public static function fieldSeparate( $string = 'from', $before = '', $after = '' )
	{
		switch ( $string ) {
			case 'count': $string = _x( 'count', 'Settings: Field Separate', 'geditorial-admin' ); break;
			case 'from' : $string = _x( 'from', 'Settings: Field Separate', 'geditorial-admin' );  break;
			case 'into' : $string = _x( 'into', 'Settings: Field Separate', 'geditorial-admin' );  break;
			case 'like' : $string = _x( 'like', 'Settings: Field Separate', 'geditorial-admin' );  break;
			case 'via' : $string  = _x( 'via', 'Settings: Field Separate', 'geditorial-admin' );  break;
			case 'ex'   : $string = _x( 'ex', 'Settings: Field Separate', 'geditorial-admin' );    break;
			case 'in'   : $string = _x( 'in', 'Settings: Field Separate', 'geditorial-admin' );    break;
			case 'to'   : $string = _x( 'to', 'Settings: Field Separate', 'geditorial-admin' );    break;
			case 'as'   : $string = _x( 'as', 'Settings: Field Separate', 'geditorial-admin' );    break;
			case 'or'   : $string = _x( 'or', 'Settings: Field Separate', 'geditorial-admin' );    break;
			case 'on'   : $string = _x( 'on', 'Settings: Field Separate', 'geditorial-admin' );    break;
		}

		printf( '%s<span class="-field-sep">&nbsp;&mdash; %s &mdash;&nbsp;</span>%s', $before, $string, $after );
	}

	public static function fieldSection( $section, $tag = 'h2' )
	{
		echo Core\HTML::tag( $tag, ( $section['title'] ?? '' ).self::fieldAfterIcon( $section['link'] ?? '' ) );

		Core\HTML::desc( ( $section['description'] ?? '' ) );
	}

	public static function fieldSectionAdopted( $source, $author = NULL, $link = NULL )
	{
		$template = $author
			/* translators: `%1$s`: source name, `%2$s`: author name */
			? _x( 'Adopted from %1$s by %2$s.', 'Settings: Section Description', 'geditorial-admin' )
			/* translators: `%1$s`: source name */
			: _x( 'Adopted from %1$s.', 'Settings: Section Description', 'geditorial-admin' );

		$html = sprintf( $template,
			Core\HTML::code( $source ),
			Core\HTML::code( $author ?? '' )
		);

		if ( empty( $link ) )
			return $html;

		return Core\Text::spaced(
			$html,
			sprintf(
				/* translators: `%1$s`: link start, `%2$s`: link end */
				_x( 'Visit %1$shere%2$s for more information.', 'Settings: Section Description', 'geditorial-admin' ),
				sprintf( '<a href="%s" target="_blank">', Core\HTML::escapeURL( $link ) ),
				'</a>'
			)
		);
	}

	public static function fieldAfterText( $text, $wrap = 'span', $class = '-text-wrap' )
	{
		return $text ? Core\HTML::tag( $wrap, [ 'class' => '-field-after '.$class ], $text ) : '';
	}

	public static function fieldAfterIcon( $url = '', $title = NULL, $icon = 'info' )
	{
		if ( ! $url )
			return '';

		$html = Core\HTML::tag( 'a', [
			'href'   => $url,
			'target' => '_blank',
			'rel'    => 'noreferrer',
			'data'   => [
				'tooltip'     => $title ?? _x( 'See More Information', 'Settings', 'geditorial-admin' ),
				'tooltip-pos' => Core\L10n::rtl() ? 'left' : 'right',
			],
		], Core\HTML::getDashicon( $icon ) );

		return '<span class="-field-after -icon-wrap">'.$html.'</span>';
	}

	// NOTE: @see `WordPress\PostType::NAME_INPUT_PATTERN`
	public static function fieldAfterPostTypeConstant()
	{
		return self::fieldAfterIcon( '#',
			_x( 'Must not exceed 20 characters and may only contain lowercase alphanumeric characters, dashes, and underscores.', 'Setting: Setting Info', 'geditorial-admin' ) );
	}

	// NOTE: @see `WordPress\Taxonomy::NAME_INPUT_PATTERN`
	public static function fieldAfterTaxonomyConstant()
	{
		return self::fieldAfterIcon( '#',
			_x( 'Must not exceed 32 characters and may only contain lowercase alphanumeric characters, dashes, and underscores.', 'Setting: Setting Info', 'geditorial-admin' ) );
	}

	// NOTE: @see `WordPress\ShortCode::NAME_INPUT_PATTERN`
	public static function fieldAfterShortCodeConstant()
	{
		return self::fieldAfterIcon( '#',
			_x( 'Do not use spaces or reserved characters.', 'Setting: Setting Info', 'geditorial-admin' ) );
	}

	public static function getSetting_thrift_mode( $description = NULL )
	{
		return [
			'field'       => 'thrift_mode',
			'type'        => 'disabled',
			'title'       => _x( 'Thrift Mode', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Tries to be more careful with system resources!', 'Settings: Setting Description', 'geditorial-admin' ),
		];
	}

	public static function getSetting_debug_mode( $description = NULL )
	{
		return [
			'field'       => 'debug_mode',
			'type'        => 'disabled',
			'title'       => _x( 'Debug Mode', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Tries to figure out what happens behind the curtains!', 'Settings: Setting Description', 'geditorial-admin' ),
		];
	}

	public static function getSetting_editor_button( $description = NULL )
	{
		return [
			'field'       => 'editor_button',
			'title'       => _x( 'Editor Button', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => '1',
		];
	}

	public static function getSetting_quick_newpost( $description = NULL )
	{
		return [
			'field'       => 'quick_newpost',
			'title'       => _x( 'Quick New Post', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => '0',
		];
	}

	public static function getSetting_admin_displaystates( $description = NULL, $default = 0 )
	{
		return [
			'field'       => 'admin_displaystates',
			'title'       => _x( 'Display States', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Appends assigned data as post state on post edit screen.', 'Setting: Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}

	public static function getSetting_widget_support( $description = NULL )
	{
		return [
			'field'       => 'widget_support',
			'title'       => _x( 'Default Widgets', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => '0',
		];
	}

	public static function getSetting_shortcode_support( $description = NULL )
	{
		return [
			'field'       => 'shortcode_support',
			'title'       => _x( 'Default Shortcodes', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => '0',
		];
	}

	public static function getSetting_tabs_support( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'tabs_support',
			'title'       => _x( 'Tabs Support', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => $default ?? '1',
		];
	}

	public static function getSetting_tab_title( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'tab_title',
			'type'        => 'text',
			'title'       => _x( 'Tab Title', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Template for the custom tab title. Leave empty to use defaults.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?: '',
			'placeholder' => $default ?: '',
		];
	}

	public static function getSetting_tab_priority( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'tab_priority',
			'type'        => 'priority',
			'title'       => _x( 'Tab Priority', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? sprintf(
				/* translators: `%s`: zero placeholder */
				_x( 'Priority of the custom tab. Leave at %s to use defaults.', 'Setting Description', 'geditorial-admin' ),
				Core\HTML::code( '0' )
			),
			'default' => $default ?: 0,
		];
	}

	public static function getSetting_woocommerce_support( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'woocommerce_support',
			'title'       => _x( 'WooCommerce Support', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? '',
			'default'     => $default ?? '0',
		];
	}

	public static function getSetting_buddybress_support( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'buddybress_support',
			'title'       => _x( 'BuddyPress Support', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? '',
			'default'     => $default ?? '0',
		];
	}

	public static function getSetting_avatar_support( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'avatar_support',
			'title'       => _x( 'Avatar Support', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? '',
			'default'     => $default ?? '0',
		];
	}

	public static function getSetting_thumbnail_support( $description = NULL )
	{
		return [
			'field'       => 'thumbnail_support',
			'title'       => _x( 'Default Image Sizes', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => '0',
		];
	}

	public static function getSetting_thumbnail_fallback( $description = NULL )
	{
		return [
			'field'       => 'thumbnail_fallback',
			'title'       => _x( 'Thumbnail Fallback', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Sets the parent post thumbnail image as fallback for the child post.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => '0',
		];
	}

	public static function getSetting_legacy_migration( $description = NULL )
	{
		return [
			'field'       => 'legacy_migration',
			'title'       => _x( 'Legacy Migration', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Imports metadata from legacy plugin system.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => '0',
		];
	}

	public static function getSetting_assignment_dock( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'assignment_dock',
			'title'       => _x( 'Assignment Dock', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Select to use advanced assignment UI on edit post screen.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function getSetting_metabox_advanced( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'metabox_advanced',
			'title'       => _x( 'Advanced Meta-Box', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Select to use advanced meta-box UI on edit post screen.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function getSetting_show_in_quickedit( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'show_in_quickedit',
			'title'       => _x( 'Show in Quick-Edit', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Whether to show the taxonomy in the quick/bulk edit panel.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function getSetting_show_in_navmenus( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'show_in_navmenus',
			'title'       => _x( 'Show in Navigation', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Makes available for selection in navigation menus.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function getSetting_autolink_terms( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'autolink_terms',
			'title'       => _x( 'Auto-link Terms', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Tries to linkify the terms in the content.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function getSetting_selectmultiple_term( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'selectmultiple_term',
			'title'       => _x( 'Multiple Terms', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Whether to assign multiple terms in edit panel.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function getSetting_assign_default_term( $description = NULL )
	{
		return [
			'field'       => 'assign_default_term',
			'title'       => _x( 'Assign Default Term', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Applies the fallback default term from primary taxonomy.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => '0',
		];
	}

	public static function getSetting_multiple_instances( $description = NULL )
	{
		return [
			'field'       => 'multiple_instances',
			'title'       => _x( 'Multiple Instances', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => '0',
		];
	}

	public static function getSetting_comment_status( $description = NULL )
	{
		return [
			'field'       => 'comment_status',
			'type'        => 'select',
			'title'       => _x( 'Comment Status', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Determines the default status of the new post comments.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => 'closed',
			'values'      => [
				'open'   => _x( 'Open', 'Settings: Setting Option', 'geditorial-admin' ),
				'closed' => _x( 'Closed', 'Settings: Setting Option', 'geditorial-admin' ),
			],
		];
	}

	public static function getSetting_post_status( $description = NULL )
	{
		return [
			'field'       => 'post_status',
			'type'        => 'select',
			'title'       => _x( 'Post Status', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => 'pending',
			'values'      => Core\Arraay::stripByKeys( WordPress\Status::get(), [
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
			'title'       => _x( 'Post Type', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => 'post',
			'values'      => WordPress\PostType::get( 2, [ 'show_ui' => TRUE ] ),
			'exclude'     => [ 'attachment', 'wp_theme' ],
		];
	}

	public static function getSetting_insert_content( $description = NULL )
	{
		return [
			'field'       => 'insert_content',
			'type'        => 'select',
			'title'       => _x( 'Insert in Content', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Outputs automatically in the content.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => 'none',
			'values'      => [
				'none'   => _x( 'No', 'Settings: Setting Option', 'geditorial-admin' ),
				'before' => _x( 'Before', 'Settings: Setting Option', 'geditorial-admin' ),
				'after'  => _x( 'After', 'Settings: Setting Option', 'geditorial-admin' ),
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
			'title'       => _x( 'Insert Cover', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
		];
	}

	// FIXME: DEPRECATED: USE: `settings_insert_priority_option()`
	public static function getSetting_insert_priority( $description = NULL )
	{
		return [
			'field'       => 'insert_priority',
			'type'        => 'priority',
			'title'       => _x( 'Insert Priority', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => '10',
		];
	}

	public static function getSetting_before_content( $description = NULL )
	{
		return [
			'field'       => 'before_content',
			'type'        => 'textarea-quicktags',
			'title'       => _x( 'Before Content', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? sprintf(
				/* translators: `%s`: code placeholder */
				_x( 'Adds %s before start of all the supported post-types.', 'Settings: Setting Description', 'geditorial-admin' ),
				Core\HTML::code( 'HTML' )
			),
		];
	}

	public static function getSetting_after_content( $description = NULL )
	{
		return [
			'field'       => 'after_content',
			'type'        => 'textarea-quicktags',
			'title'       => _x( 'After Content', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? sprintf(
				/* translators: `%s`: code placeholder */
				_x( 'Adds %s after end of all the supported post-types.', 'Settings: Setting Description', 'geditorial-admin' ),
				Core\HTML::code( 'HTML' )
			),
		];
	}

	public static function getSetting_admin_ordering( $description = NULL )
	{
		return [
			'field'       => 'admin_ordering',
			'title'       => _x( 'Ordering', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Enhances item ordering on admin edit pages.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => '1',
		];
	}

	public static function getSetting_admin_restrict( $description = NULL )
	{
		return [
			'field'       => 'admin_restrict',
			'title'       => _x( 'List Restrictions', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Enhances restrictions on admin edit pages.', 'Settings: Setting Description', 'geditorial-admin' ),
		];
	}

	public static function getSetting_admin_columns( $description = NULL )
	{
		return [
			'field'       => 'admin_columns',
			'title'       => _x( 'List Columns', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Enhances columns on admin edit pages.', 'Settings: Setting Description', 'geditorial-admin' ),
		];
	}

	public static function getSetting_admin_bulkactions( $description = NULL )
	{
		return [
			'field'       => 'admin_bulkactions',
			'title'       => _x( 'Bulk Actions', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Enhances bulk actions on admin edit pages.', 'Settings: Setting Description', 'geditorial-admin' ),
		];
	}

	public static function getSetting_admin_rowactions( $description = NULL )
	{
		return [
			'field'       => 'admin_rowactions',
			'title'       => _x( 'Row Actions', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Enhances row actions on admin edit pages.', 'Settings: Setting Description', 'geditorial-admin' ),
		];
	}

	public static function getSetting_adminbar_summary( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'adminbar_summary',
			'title'       => _x( 'Adminbar Summary', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Summary for the current item as a node in admin-bar.', 'Setting: Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '1',
		];
	}

	public static function getSetting_adminbar_tools( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'adminbar_tools',
			'title'       => _x( 'Adminbar Tools', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Enabeles enhancement tools on the admin-bar.', 'Setting: Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function getSetting_dashboard_widgets( $description = NULL )
	{
		return [
			'field'       => 'dashboard_widgets',
			'title'       => _x( 'Dashboard Widgets', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Enhances admin dashboard with customized widgets.', 'Settings: Setting Description', 'geditorial-admin' ),
		];
	}

	public static function getSetting_dashboard_authors( $description = NULL )
	{
		return [
			'field'       => 'dashboard_authors',
			'title'       => _x( 'Dashboard Authors', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Displays author column on the dashboard widget.', 'Settings: Setting Description', 'geditorial-admin' ),
		];
	}

	public static function getSetting_dashboard_statuses( $description = NULL )
	{
		return [
			'field'       => 'dashboard_statuses',
			'title'       => _x( 'Dashboard Statuses', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Displays status column on the dashboard widget.', 'Settings: Setting Description', 'geditorial-admin' ),
		];
	}

	public static function getSetting_dashboard_count( $description = NULL )
	{
		return [
			'field'       => 'dashboard_count',
			'type'        => 'number',
			'title'       => _x( 'Dashboard Count', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Limits displaying rows of items on the dashboard widget.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => 10,
		];
	}

	public static function getSetting_summary_scope( $description = NULL )
	{
		return [
			'field'       => 'summary_scope',
			'type'        => 'select',
			'title'       => _x( 'Summary Scope', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'User scope for the content summary.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => 'all',
			'values'      => [
				'all'     => _x( 'All Users', 'Settings: Setting Option', 'geditorial-admin' ),
				'current' => _x( 'Current User', 'Settings: Setting Option', 'geditorial-admin' ),
				'roles'   => _x( 'Within the Roles', 'Settings: Setting Option', 'geditorial-admin' ),
			],
		];
	}

	public static function getSetting_summary_drafts( $description = NULL )
	{
		return [
			'field'       => 'summary_drafts',
			'title'       => _x( 'Include Drafts', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Include drafted items in the content summary.', 'Settings: Setting Description', 'geditorial-admin' ),
		];
	}

	public static function getSetting_summary_excludes( $description = NULL, $values = [], $empty = NULL )
	{
		return [
			'field'        => 'summary_excludes',
			'type'         => 'checkboxes-values',
			'title'        => _x( 'Summary Excludes', 'Settings: Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Selected terms will be excluded on the content summary.', 'Settings: Setting Description', 'geditorial-admin' ),
			'string_empty' => $empty ?: _x( 'There are no items available!', 'Settings: Setting Empty String', 'geditorial-admin' ),
			'values'       => $values,
		];
	}

	public static function getSetting_summary_parents( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'summary_parents',
			'title'       => _x( 'Summary Parents', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Displays only parent terms on the content summary.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '1',
		];
	}

	public static function getSetting_public_statuses( $description = NULL, $values = [], $empty = NULL )
	{
		return [
			'field'        => 'public_statuses',
			'type'         => 'checkboxes-values',
			'title'        => _x( 'Public Statuses', 'Settings: Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Selected terms will be acceptable on the public content queries.', 'Settings: Setting Description', 'geditorial-admin' ),
			'string_empty' => $empty ?: _x( 'There are no items available!', 'Settings: Setting Empty String', 'geditorial-admin' ),
			'values'       => $values,
		];
	}

	public static function getSetting_paired_roles( $description = NULL, $roles = NULL, $excludes = NULL )
	{
		return [
			'field'       => 'paired_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Paired Roles', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can assign paired defenitions.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? self::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function getSetting_paired_exclude_terms( $description = NULL, $taxonomy = 'post_tag', $empty = NULL )
	{
		return [
			'field'        => 'paired_exclude_terms',
			'type'         => 'checkbox-panel',
			'title'        => _x( 'Exclude Terms', 'Settings: Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Items with selected terms will be excluded form dropdown on supported post-types.', 'Settings: Setting Description', 'geditorial-admin' ),
			'string_empty' => $empty ?: _x( 'There are no items available!', 'Settings: Setting Empty String', 'geditorial-admin' ),
			'values'       => WordPress\Taxonomy::listTerms( $taxonomy ),
		];
	}

	public static function getSetting_paired_force_parents( $description = NULL )
	{
		return [
			'field'       => 'paired_force_parents',
			'title'       => _x( 'Force Parents', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Includes parents on the supported post-types.', 'Settings: Setting Description', 'geditorial-admin' ),
		];
	}

	public static function getSetting_paired_globalsummary( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'paired_globalsummary',
			'title'       => _x( 'Global Summary', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Includes connected main posts on the global summary for each supported item.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function getSetting_display_globalsummary( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'display_globalsummary',
			'title'       => _x( 'Global Summary', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Displays connected posts on the global summary on the main post edit screen.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function getSetting_paired_manage_restricted( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'paired_manage_restricted',
			'title'       => _x( 'Management Restricted', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Limits creation and deletion of the main posts to administrators.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function getSetting_parents_as_views( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'parents_as_views',
			'title'       => _x( 'Parents as Views', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Prepends the parent terms to views on supported post-types.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function getSetting_force_parents( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'force_parents',
			'title'       => _x( 'Force Parents', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Includes parents when selecting the main contents.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function getSetting_count_not( $description = NULL )
	{
		return [
			'field'       => 'count_not',
			'title'       => _x( 'Count Not', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Counts not affected items in the content summary.', 'Settings: Setting Description', 'geditorial-admin' ),
		];
	}

	public static function getSetting_posttype_feeds( $description = NULL )
	{
		return [
			'field'       => 'posttype_feeds',
			'title'       => _x( 'Feeds', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Supports feeds for the supported post-types.', 'Settings: Setting Description', 'geditorial-admin' ),
		];
	}

	public static function getSetting_posttype_pages( $description = NULL )
	{
		return [
			'field'       => 'posttype_pages',
			'title'       => _x( 'Pages', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Supports pagination on the supported post-types.', 'Settings: Setting Description', 'geditorial-admin' ),
		];
	}

	public static function getSetting_units_posttypes( $description = NULL, $values = NULL, $empty = NULL )
	{
		return [
			'field'        => 'units_posttypes',
			'type'         => 'checkboxes-values',
			'title'        => _x( 'Units Post-types', 'Settings: Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Unit Fields will be available for selected post-type.', 'Settings: Setting Description', 'geditorial-admin' ),
			'string_empty' => $empty ?: _x( 'There are no unit post-types available!', 'Settings: Setting Empty String', 'geditorial-admin' ),
			'values'       => $values ?? WordPress\PostType::get( 0, [ 'show_ui' => TRUE ] ),
		];
	}

	public static function getSetting_main_posttype_constant( $description = NULL, $default = '' )
	{
		return [
			'field'       => 'main_posttype_constant',
			'type'        => 'text',
			'title'       => _x( 'Post-Type Key', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the main post-type key. Leave blank for default.', 'Setting: Setting Description', 'geditorial-admin' ),
			'after'       => self::fieldAfterPostTypeConstant(),
			'pattern'     => WordPress\PostType::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function getSetting_main_taxonomy_constant( $description = NULL, $default = '' )
	{
		return [
			'field'       => 'main_taxonomy_constant',
			'type'        => 'text',
			'title'       => _x( 'Taxonomy Key', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the main taxonomy key. Leave blank for default.', 'Setting: Setting Description', 'geditorial-admin' ),
			'after'       => self::fieldAfterTaxonomyConstant(),
			'pattern'     => WordPress\Taxonomy::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function getSetting_category_taxonomy_constant( $description = NULL, $default = '' )
	{
		return [
			'field'       => 'category_taxonomy_constant',
			'type'        => 'text',
			'title'       => _x( 'Taxonomy Key', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the main taxonomy key. Leave blank for default.', 'Setting: Setting Description', 'geditorial-admin' ),
			'after'       => self::fieldAfterTaxonomyConstant(),
			'pattern'     => WordPress\Taxonomy::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function getSetting_main_shortcode_constant( $description = NULL, $default = '' )
	{
		return [
			'field'       => 'main_shortcode_constant',
			'type'        => 'text',
			'title'       => _x( 'Shortcode Tag', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the main short-code tag. Leave blank for default.', 'Setting: Setting Description', 'geditorial-admin' ),
			'after'       => self::fieldAfterShortCodeConstant(),
			'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function getSetting_searchform_shortcode_constant( $description = NULL, $default = '' )
	{
		return [
			'field'       => 'searchform_shortcode_constant',
			'type'        => 'text',
			'title'       => _x( 'Search Form Shortcode Tag', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the search form short-code tag. Leave blank for default.', 'Setting: Setting Description', 'geditorial-admin' ),
			'after'       => self::fieldAfterShortCodeConstant(),
			'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function getSetting_span_shortcode_constant( $description = NULL, $default = '' )
	{
		return [
			'field'       => 'span_shortcode_constant',
			'type'        => 'text',
			'title'       => _x( 'Span Shortcode Tag', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the span short-code tag. Leave blank for default.', 'Setting: Setting Description', 'geditorial-admin' ),
			'after'       => self::fieldAfterShortCodeConstant(),
			'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function getSetting_cover_shortcode_constant( $description = NULL, $default = '' )
	{
		return [
			'field'       => 'cover_shortcode_constant',
			'type'        => 'text',
			'title'       => _x( 'Cover Shortcode Tag', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the cover short-code tag. Leave blank for default.', 'Setting: Setting Description', 'geditorial-admin' ),
			'after'       => self::fieldAfterShortCodeConstant(),
			'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function getSetting_subterm_shortcode_constant( $description = NULL, $default = '' )
	{
		return [
			'field'       => 'subterm_shortcode_constant',
			'type'        => 'text',
			'title'       => _x( 'Sub-Term Shortcode Tag', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the sub-term short-code tag. Leave blank for default.', 'Setting: Setting Description', 'geditorial-admin' ),
			'after'       => self::fieldAfterShortCodeConstant(),
			'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function getSetting_connected_shortcode_constant( $description = NULL, $default = '' )
	{
		return [
			'field'       => 'connected_shortcode_constant',
			'type'        => 'text',
			'title'       => _x( 'Connected Shortcode Tag', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the connected short-code tag. Leave blank for default.', 'Setting: Setting Description', 'geditorial-admin' ),
			'after'       => self::fieldAfterShortCodeConstant(),
			'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function getSetting_children_shortcode_constant( $description = NULL, $default = '' )
	{
		return [
			'field'       => 'children_shortcode_constant',
			'type'        => 'text',
			'title'       => _x( 'Children Shortcode Tag', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the children short-code tag. Leave blank for default.', 'Setting: Setting Description', 'geditorial-admin' ),
			'after'       => self::fieldAfterShortCodeConstant(),
			'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function getSetting_primary_posttype_constant( $description = NULL, $default = '' )
	{
		return [
			'field'       => 'primary_posttype_constant',
			'type'        => 'text',
			'title'       => _x( 'Primary Post-Type Key', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the primary post-type key. Leave blank for default.', 'Setting: Setting Description', 'geditorial-admin' ),
			'after'       => self::fieldAfterPostTypeConstant(),
			'pattern'     => WordPress\PostType::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function getSetting_primary_taxonomy_constant( $description = NULL, $default = '' )
	{
		return [
			'field'       => 'primary_taxonomy_constant',
			'type'        => 'text',
			'title'       => _x( 'Primary Taxonomy Key', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the primary taxonomy key. Leave blank for default.', 'Setting: Setting Description', 'geditorial-admin' ),
			'after'       => self::fieldAfterTaxonomyConstant(),
			'pattern'     => WordPress\Taxonomy::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function getSetting_secondary_posttype_constant( $description = NULL, $default = '' )
	{
		return [
			'field'       => 'secondary_posttype_constant',
			'type'        => 'text',
			'title'       => _x( 'Secondary Post-Type Key', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the secondary post-type key. Leave blank for default.', 'Setting: Setting Description', 'geditorial-admin' ),
			'after'       => self::fieldAfterPostTypeConstant(),
			'pattern'     => WordPress\PostType::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function getSetting_secondary_taxonomy_constant( $description = NULL, $default = '' )
	{
		return [
			'field'       => 'secondary_taxonomy_constant',
			'type'        => 'text',
			'title'       => _x( 'Secondary Taxonomy Key', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the secondary taxonomy key. Leave blank for default.', 'Setting: Setting Description', 'geditorial-admin' ),
			'after'       => self::fieldAfterTaxonomyConstant(),
			'pattern'     => WordPress\Taxonomy::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function getSetting_subcontent_posttypes( $description = NULL, $values = NULL, $empty = NULL )
	{
		return [
			'field'        => 'subcontent_posttypes',
			'type'         => 'checkboxes-values',
			'title'        => _x( 'Supported Post-types', 'Settings: Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Will be available for selected post-type.', 'Settings: Setting Description', 'geditorial-admin' ),
			'string_empty' => $empty ?: _x( 'There are no supported post-types available!', 'Settings: Setting Empty String', 'geditorial-admin' ),
			'values'       => $values ?? WordPress\PostType::get( 0, [ 'show_ui' => TRUE ] ),
		];
	}

	public static function getSetting_subcontent_fields( $description = NULL, $values = [], $empty = NULL, $default = TRUE )
	{
		return [
			'field'        => 'subcontent_fields',
			'type'         => 'checkboxes-values',
			'title'        => _x( 'Supported Fields', 'Settings: Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Determines the optional fields for each supported post-type.', 'Settings: Setting Description', 'geditorial-admin' ),
			'string_empty' => $empty ?: _x( 'There are no supported fields available!', 'Settings: Setting Empty String', 'geditorial-admin' ),
			'values'       => $values,
			'default'      => $default,
		];
	}

	public static function getSetting_subcontent_types( $description = NULL, $values = [], $empty = NULL, $default = TRUE )
	{
		return [
			'field'        => 'subcontent_types',
			'type'         => 'checkboxes-values',
			'title'        => _x( 'Supported Types', 'Settings: Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Determines the optional types for each supported post-type.', 'Settings: Setting Description', 'geditorial-admin' ),
			'string_empty' => $empty ?: _x( 'There are no supported types available!', 'Settings: Setting Empty String', 'geditorial-admin' ),
			'values'       => $values,
			'default'      => $default,
		];
	}

	public static function getSetting_parent_posttypes( $description = NULL, $values = NULL, $empty = NULL )
	{
		return [
			'field'        => 'parent_posttypes',
			'type'         => 'checkboxes-values',
			'title'        => _x( 'Parent Post-types', 'Settings: Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Selected parents will be used on the selection box.', 'Settings: Setting Description', 'geditorial-admin' ),
			'string_empty' => $empty ?: _x( 'There are no parents available!', 'Settings: Setting Empty String', 'geditorial-admin' ),
			'values'       => $values ?? WordPress\PostType::get( 0, [ 'show_ui' => TRUE ] ),
		];
	}

	public static function getSetting_custom_archives( $description = NULL, $default = '' )
	{
		return [
			'field'       => 'custom_archives',
			'type'        => 'text',
			'title'       => _x( 'Custom Archives', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the main archives page for the content.', 'Setting: Setting Description', 'geditorial-admin' ),
			'field_class' => [ 'regular-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function getSetting_empty_content( $description = NULL )
	{
		return [
			'field'       => 'empty_content',
			'type'        => 'textarea-quicktags',
			'title'       => _x( 'Empty Content', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Displays as empty content placeholder.', 'Setting: Setting Description', 'geditorial-admin' ),
			'default'     => _x( 'There are no content by this title. Search again or create one.', 'Setting: Setting Default', 'geditorial-admin' ),
		];
	}

	public static function getSetting_archive_empty_items( $description = NULL )
	{
		return [
			'field'       => 'archive_empty_items',
			'type'        => 'textarea-quicktags',
			'title'       => _x( 'Empty Items', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Displays as empty items placeholder.', 'Setting: Setting Description', 'geditorial-admin' ),
			'default'     => _x( 'There are no contents available.', 'Setting: Setting Default', 'geditorial-admin' ),
		];
	}

	public static function getSetting_archive_override( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'archive_override',
			'title'       => _x( 'Archive Override', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Overrides default template hierarchy for archive.', 'Setting: Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '1',
		];
	}

	public static function getSetting_archive_title( $description = NULL, $placeholder = FALSE )
	{
		return [
			'field'       => 'archive_title',
			'type'        => 'text',
			'title'       => _x( 'Archive Title', 'Setting: Setting Title', 'geditorial-admin' ),
			'placeholder' => $placeholder,
			'description' => $description ?? sprintf(
				/* translators: `%s`: zero placeholder */
				_x( 'Displays as archive title. Leave blank for default or %s to disable.', 'Setting: Setting Description', 'geditorial-admin' ),
				Core\HTML::code( '0' )
			),
		];
	}

	public static function getSetting_newpost_title( $description = NULL, $placeholder = FALSE )
	{
		return [
			'field'       => 'newpost_title',
			'type'        => 'text',
			'title'       => _x( 'New-post Title', 'Setting: Setting Title', 'geditorial-admin' ),
			'placeholder' => $placeholder,
			'description' => $description ?? sprintf(
				/* translators: `%s`: zero placeholder */
				_x( 'Displays as new-post title. Leave blank for default or %s to disable.', 'Setting: Setting Description', 'geditorial-admin' ),
				Core\HTML::code( '0' )
			),
		];
	}

	public static function getSetting_archive_content( $description = NULL )
	{
		return [
			'field'       => 'archive_content',
			'type'        => 'textarea-quicktags',
			'title'       => _x( 'Archive Content', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? sprintf(
				/* translators: `%s`: zero placeholder */
				_x( 'Displays as archive content. Leave blank for default or %s to disable.', 'Setting: Setting Description', 'geditorial-admin' ),
				Core\HTML::code( '0' )
			),
		];
	}

	public static function getSetting_archive_template( $description = NULL )
	{
		return [
			'field'       => 'archive_template',
			'type'        => 'select',
			'title'       => _x( 'Archive Template', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Used as page template on the archive page.', 'Setting: Setting Description', 'geditorial-admin' ),
			'none_title'  => self::showOptionNone(),
			'values'      => wp_get_theme()->get_page_templates(),
		];
	}

	public static function getSetting_newpost_template( $description = NULL )
	{
		return [
			'field'       => 'newpost_template',
			'type'        => 'select',
			'title'       => _x( 'New-post Template', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Used as page template on the new-post page.', 'Setting: Setting Description', 'geditorial-admin' ),
			'none_title'  => self::showOptionNone(),
			'values'      => wp_get_theme()->get_page_templates(),
		];
	}

	public static function getSetting_display_searchform( $description = NULL )
	{
		return [
			'field'       => 'display_searchform',
			'title'       => _x( 'Display Search Form', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Appends a search form to the content generated on front-end.', 'Setting: Setting Description', 'geditorial-admin' ),
		];
	}

	public static function getSetting_display_threshold( $description = NULL )
	{
		return [
			'field'       => 'display_threshold',
			'type'        => 'number',
			'title'       => _x( 'Display Threshold', 'Setting: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Maximum number of items to consider as a long list.', 'Setting: Setting Description', 'geditorial-admin' ),
			'default'     => '5',
		];
	}

	public static function getSetting_display_perpage( $description = NULL )
	{
		return [
			'field'       => 'display_perpage',
			'type'        => 'number',
			'title'       => _x( 'Display Per-Page', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Total rows of items per each page of the list.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => 15,
		];
	}

	public static function getSetting_frontend_search( $description = NULL, $default = 0 )
	{
		return [
			'field'       => 'frontend_search',
			'title'       => _x( 'Front-end Search', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Adds results by the information on front-end search.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}

	// NOTE: DEPRECATED
	public static function getSetting_posttype_viewable( $description = NULL, $default = 1 )
	{
		return [
			'field'       => 'posttype_viewable',
			'title'       => _x( 'Viewable Post-Type', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Determines the visibility of the main post-type.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}

	public static function getSetting_contents_viewable( $description = NULL, $default = 1 )
	{
		return [
			'field'       => 'contents_viewable',
			'title'       => _x( 'Viewable Contents', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Determines whether the contents are publicly viewable.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}

	public static function getSetting_custom_captype( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'custom_captype',
			'title'       => _x( 'Custom Capabilities', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Registers custom capability-type for the contents.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function getSetting_auto_term_parents( $description = NULL, $default = NULL )
	{
		return [
			'field'       => 'auto_term_parents',
			'title'       => _x( 'Auto Term Parents', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Auto-assigns parent terms on supported posts.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '1',
		];
	}

	public static function getSetting_override_dates( $description = NULL, $default = 1 )
	{
		return [
			'field'        => 'override_dates',
			'title'        => _x( 'Override Dates', 'Settings: Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Tries to override post-date with provided date data on supported post-types.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}

	public static function getSetting_calendar_type( $description = NULL )
	{
		return [
			'field'       => 'calendar_type',
			'title'       => _x( 'Default Calendar', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'type'        => 'select',
			'default'     => Core\L10n::calendar(),
			'values'      => Services\Calendars::getDefualts( TRUE ),
		];
	}

	public static function getSetting_calendar_list( $description = NULL )
	{
		return [
			'field'       => 'calendar_list',
			'title'       => _x( 'Calendar List', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'type'        => 'checkboxes',
			'default'     => [ Core\L10n::calendar() ],
			'values'      => Services\Calendars::getDefualts( TRUE ),
		];
	}

	public static function getSetting_add_audit_attribute( $description = NULL, $module = 'audit' )
	{
		return [
			'field'       => 'add_audit_attribute',
			'title'       => _x( 'Add Audit Attribute', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Appends an audit attribute to each item.', 'Setting Description', 'geditorial-admin' ),
			'disabled'    => ! gEditorial()->enabled( $module ),
		];
	}

	public static function getSetting_supported_roles( $description = NULL, $roles = NULL, $excludes = NULL )
	{
		return [
			'field'       => 'supported_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Supported Roles', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? self::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function getSetting_excluded_roles( $description = NULL, $roles = NULL, $excludes = NULL )
	{
		return [
			'field'       => 'excluded_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Excluded Roles', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? self::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function getSetting_adminmenu_roles( $description = NULL, $roles = NULL, $excludes = NULL )
	{
		return [
			'field'       => 'adminmenu_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Admin Menu Roles', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? self::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function getSetting_metabox_roles( $description = NULL, $roles = NULL, $excludes = NULL )
	{
		return [
			'field'       => 'metabox_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Meta Box Roles', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? self::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function getSetting_adminbar_roles( $description = NULL, $roles = NULL, $excludes = NULL )
	{
		return [
			'field'       => 'adminbar_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Adminbar Roles', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? self::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function getSetting_reports_roles( $description = NULL, $roles = NULL, $excludes = NULL )
	{
		return [
			'field'       => 'reports_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Reports Roles', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can access data reports.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? self::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function getSetting_tools_roles( $description = NULL, $roles = NULL, $excludes = NULL )
	{
		return [
			'field'       => 'tools_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Tools Roles', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can access data tools.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? self::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function getSetting_imports_roles( $description = NULL, $roles = NULL, $excludes = NULL )
	{
		return [
			'field'       => 'imports_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Imports Roles', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can access data imports.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? self::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	// NOTE: DEPRECATED
	public static function getSetting_exports_roles( $description = NULL, $roles = NULL, $excludes = NULL )
	{
		return [
			'field'       => 'exports_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Exports Roles', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can export data entries.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? self::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function getSetting_prints_roles( $description = NULL, $roles = NULL, $excludes = NULL )
	{
		return [
			'field'       => 'prints_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Prints Roles', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can print data entries.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? self::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function getSetting_uploads_roles( $description = NULL, $roles = NULL, $excludes = NULL )
	{
		return [
			'field'       => 'uploads_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Uploads Roles', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can upload data into the site.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? self::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function getSetting_public_roles( $description = NULL, $roles = NULL, $excludes = NULL )
	{
		return [
			'field'       => 'public_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Public Roles', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can access the links to public endpoints in the site.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? self::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function getSetting_overview_roles( $description = NULL, $roles = NULL, $excludes = NULL )
	{
		return [
			'field'       => 'overview_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Overview Roles', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can view data overviews.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? self::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function getSetting_manage_roles( $description = NULL, $roles = NULL, $excludes = NULL )
	{
		return [
			'field'       => 'manage_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Manage Roles', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can manage, edit and delete entry defenitions.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? self::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function getSetting_assign_roles( $description = NULL, $roles = NULL, $excludes = NULL )
	{
		return [
			'field'       => 'assign_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Assign Roles', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can assign entry defenitions.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? self::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function getSetting_reports_post_edit( $description = NULL, $default = 1 )
	{
		return [
			'field'       => 'reports_post_edit',
			'title'       => _x( 'Edit Post Reports', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Also checks for <strong>edit-post</strong> capability for <em>reports</em> roles.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}

	public static function getSetting_assign_post_edit( $description = NULL, $default = 1 )
	{
		return [
			'field'       => 'assign_post_edit',
			'title'       => _x( 'Edit Post Assign', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Also checks for <strong>edit-post</strong> capability for <em>assign</em> roles.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}

	public static function getSetting_overview_fields( $description = NULL, $fields = NULL, $empty = NULL )
	{
		return [
			'field'        => 'overview_fields',
			'type'         => 'checkbox-panel',
			'title'        => _x( 'Overview Fields', 'Settings: Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Whether to appear as columns on the overview.', 'Setting Description', 'geditorial-admin' ),
			'default'      => [],
			'values'       => $fields ?? [],
			'string_empty' => $empty ?? _x( 'There are no fields available!', 'Settings: Setting Empty String', 'geditorial-admin' ),
		];
	}

	public static function getSetting_overview_units( $description = NULL, $fields = NULL, $empty = NULL )
	{
		return [
			'field'        => 'overview_units',
			'type'         => 'checkbox-panel',
			'title'        => _x( 'Overview Units', 'Settings: Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Whether to appear as columns on the overview.', 'Setting Description', 'geditorial-admin' ),
			'default'      => [],
			'values'       => $fields ?? [],
			'string_empty' => $empty ?? _x( 'There are no units available!', 'Settings: Setting Empty String', 'geditorial-admin' ),
		];
	}

	public static function getSetting_overview_taxonomies( $description = NULL, $taxes = NULL, $empty = NULL )
	{
		return [
			'field'        => 'overview_taxonomies',
			'type'         => 'checkbox-panel',
			'title'        => _x( 'Overview Taxonomies', 'Settings: Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Whether to appear as columns on the overview.', 'Setting Description', 'geditorial-admin' ),
			'default'      => [],
			'values'       => $taxes ?? [],
			'string_empty' => $empty ?? _x( 'There are no taxonomies available!', 'Settings: Setting Empty String', 'geditorial-admin' ),
		];
	}

	public static function getSetting_append_identifier_code( $description = NULL )
	{
		return [
			'field'       => 'append_identifier_code',
			'title'       => _x( 'Append Identifier', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Appends the identifier code field data to each item supported title.', 'Settings: Setting Description', 'geditorial-admin' ),
		];
	}

	public static function getSetting_printpage_enqueue_librefonts( $description = NULL )
	{
		return [
			'field'       => 'printpage_enqueue_librefonts',
			'title'       => _x( 'Enqueue Libre Fonts', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Loads Libre Barcode fonts on print page html head.', 'Settings: Setting Description', 'geditorial-admin' ),
			'after'       => self::fieldAfterIcon( 'https://graphicore.github.io/librebarcode/' ),
		];
	}

	public static function getSetting_force_sanitize( $description = NULL, $default = 0 )
	{
		return [
			'field'       => 'force_sanitize',
			'title'       => _x( 'Force Sanitize', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Tries to force the sanitization upon storing data.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}

	public static function getSetting_restapi_restricted( $description = NULL, $default = 1 )
	{
		return [
			'field'       => 'restapi_restricted',
			'title'       => _x( 'Restricted API', 'Settings: Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Access Rest-API requires logged-in users.', 'Settings: Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}

	public static function sub( $default = 'general' )
	{
		return trim( self::req( 'sub', $default ) );
	}

	public static function wrapOpen( $sub, $context = 'settings', $iframe_title = '' )
	{
		if ( self::req( 'noheader' ) ) {

			add_filter( 'show_admin_bar', '__return_false', 9999 );
			wp_dequeue_script( 'admin-bar' );
			wp_dequeue_style( 'admin-bar' );

			self::define( 'IFRAME_REQUEST', TRUE );
			iframe_header( $iframe_title );
		}

		echo '<div id="'.static::BASE.'-'.$context.'" class="'.Core\HTML::prepClass(
			'wrap',
			'-settings-wrap',
			self::req( 'noheader' ) ? '-iframe-wrap' : '',
			static::BASE.'-admin-wrap',
			static::BASE.'-'.$context,
			static::BASE.'-'.$context.'-'.$sub,
			'sub-'.$sub
		).'">';
	}

	public static function wrapClose( $iframe_exit = TRUE )
	{
		echo '<div class="clear"></div></div>';

		if ( self::req( 'noheader' ) ) {

			iframe_footer();

			if ( $iframe_exit )
				exit;
		}
	}

	public static function wrapError( $message, $title = NULL )
	{
		self::wrapOpen( 'error' );
			self::headerTitle( 'error', $title );
			echo $message;
		self::wrapClose();
	}

	// @REF: `get_admin_page_title()`
	public static function headerTitle( $context = NULL, $title = NULL, $back = NULL, $to = NULL, $icon = '', $count = FALSE, $search = FALSE, $filters = FALSE )
	{
		$system = Plugin::system();
		$before = $class = '';

		if ( is_null( $title ) )
			$title = $system ?: _x( 'Editorial', 'Settings', 'geditorial-admin' );

		// FIXME: get cap from settings module
		if ( is_null( $back ) && current_user_can( 'manage_options' ) )
			$back = self::getURLbyContext( $context ?? 'settings' );

		if ( is_null( $to ) )
			$to = $system ? sprintf(
				/* translators: `%s`: system string */
				_x( 'Back to %s', 'Settings', 'geditorial-admin' ),
				$system
			) : _x( 'Back to Editorial', 'Settings', 'geditorial-admin' );

		if ( is_array( $icon ) )
			$before = gEditorial()->icon( $icon[1], $icon[0] );

		else if ( $icon )
			$class = ' dashicons-before dashicons-'.$icon;

		$extra = '';

		if ( FALSE !== $count )
			$extra.= sprintf( ' <span class="title-count settings-title-count">%s</span>', Core\Number::format( $count ) );

		printf( '<h1 class="wp-heading-inline settings-title'.$class.'">%s%s%s</h1>', $before, $title, $extra );

		// echo '<span class="subtitle">'.'</span>';

		$action = ' <a href="%s" class="page-title-action settings-title-action">%s</a>';

		if ( $back && is_array( $back ) )
			foreach ( $back as $back_link => $back_title )
				printf( $action, $back_link, $back_title );

		else if ( $back )
			printf( $action, $back, $to );

		if ( $search )
			echo Core\HTML::tag( 'input', [
				'type'        => 'search',
				'class'       => [ 'settings-title-search', '-search', 'hide-if-no-js' ], // 'fuzzy-search' // fuzzy wont work persian
				'placeholder' => _x( 'Search …', 'Settings: Search Placeholder', 'geditorial-admin' ),
				'autofocus'   => 'autofocus',
			] );

		if ( $filters ) {
			echo '<div class="settings-title-filters">';
				echo '<label>'._x( 'All', 'Settings', 'geditorial-admin' );
				echo ' <input type="radio" name="filter-status" data-filter="all" value="all" checked="checked" /></label> ';

				echo '<label>'._x( 'Enabled', 'Settings', 'geditorial-admin' );
				echo ' <input type="radio" name="filter-status" data-filter="enabled" value="true" /></label> ';

				echo '<label>'._x( 'Disabled', 'Settings', 'geditorial-admin' );
				echo ' <input type="radio" name="filter-status" data-filter="disabled" value="false" /></label>';

				// TODO: add more filter for `access`:
				// `unknown` or `private`, `stable`, `beta`, `alpha`, `beta`, `deprecated`, `planned`

			echo '</div>';
		}

		echo '<hr class="wp-header-end">';
	}

	public static function sideOpen( $title = NULL, $uri = '', $active = '', $subs = [], $heading = NULL )
	{
		echo '<div class="side-nav-wrap">';

		Core\HTML::h2( $title ?? ( Plugin::system() ?: _x( 'Editorial', 'Settings: Header Title', 'geditorial-admin' ) ), '-title' );
		Core\HTML::headerNav( $uri, $active, $subs, 'side-nav', 'ul', 'li' );

		echo '<div class="side-nav-content">';

		if ( 'overview' == $active )
			return;

		if ( FALSE === $heading )
			return;

		if ( $heading )
			$subtitle = $heading;

		else if ( ! empty( $subs[$active]['title'] ) )
			$subtitle = $subs[$active]['title'];

		else if ( ! empty( $subs[$active] ) )
			$subtitle = $subs[$active];

		else
			return;

		Core\HTML::h2( $subtitle, 'wp-heading-inline settings-title' );
		echo '<hr class="wp-header-end">';
	}

	public static function sideClose()
	{
		// echo '</div><div class="clear"></div></div>';
		echo '</div></div>';
	}

	// @SEE: `wp_admin_notice()` @since WP 6.4.0
	// @SEE: https://core.trac.wordpress.org/ticket/57791
	public static function message( $messages = NULL )
	{
		$messages = $messages ?? self::messages();

		if ( isset( $_GET['message'] ) ) {

			if ( isset( $messages[$_GET['message']] ) )
				echo $messages[$_GET['message']];
			else
				echo Core\HTML::warning( $_GET['message'] );

			$_SERVER['REQUEST_URI'] = remove_query_arg( [ 'message', 'count' ], $_SERVER['REQUEST_URI'] );
		}
	}

	// `processed`
	// `cleared`
	public static function messages()
	{
		return [
			'resetting' => self::success( _x( 'Settings reset.', 'Settings: Message', 'geditorial-admin' ) ),
			'updated'   => self::success( _x( 'Settings updated.', 'Settings: Message', 'geditorial-admin' ) ),
			'disabled'  => self::success( _x( 'Module disabled.', 'Settings: Message', 'geditorial-admin' ) ),
			'optimized' => self::success( _x( 'Tables optimized.', 'Settings: Message', 'geditorial-admin' ) ),
			'purged'    => self::success( _x( 'Data purged.', 'Settings: Message', 'geditorial-admin' ) ),
			'maked'     => self::success( _x( 'File/Folder created.', 'Settings: Message', 'geditorial-admin' ) ),
			'mailed'    => self::success( _x( 'Mail sent successfully.', 'Settings: Message', 'geditorial-admin' ) ),
			'added'     => self::success( _x( 'Item added successfully.', 'Settings: Message', 'geditorial-admin' ) ),
			'removed'   => self::success( _x( 'Item removed successfully.', 'Settings: Message', 'geditorial-admin' ) ),
			'error'     => self::error( _x( 'Error occurred!', 'Settings: Message', 'geditorial-admin' ) ),
			'wrong'     => self::error( _x( 'Something&#8217;s wrong!', 'Settings: Message', 'geditorial-admin' ) ),
			'nochange'  => self::error( _x( 'No item changed!', 'Settings: Message', 'geditorial-admin' ) ),
			'noadded'   => self::error( _x( 'No item added!', 'Settings: Message', 'geditorial-admin' ) ),
			'noaccess'  => self::error( _x( 'You do not have the access!', 'Settings: Message', 'geditorial-admin' ) ),
			/* translators: `%s`: count */
			'converted' => self::counted( _x( '%s items(s) converted!', 'Settings: Message', 'geditorial-admin' ) ),
			/* translators: `%s`: count */
			'imported'  => self::counted( _x( '%s items(s) imported!', 'Settings: Message', 'geditorial-admin' ) ),
			/* translators: `%s`: count */
			'created'   => self::counted( _x( '%s items(s) created!', 'Settings: Message', 'geditorial-admin' ) ),
			/* translators: `%s`: count */
			'deleted'   => self::counted( _x( '%s items(s) deleted!', 'Settings: Message', 'geditorial-admin' ) ),
			/* translators: `%s`: count */
			'cleaned'   => self::counted( _x( '%s items(s) cleaned!', 'Settings: Message', 'geditorial-admin' ) ),
			/* translators: `%s`: count */
			'changed'   => self::counted( _x( '%s items(s) changed!', 'Settings: Message', 'geditorial-admin' ) ),
			/* translators: `%s`: count */
			'emptied'   => self::counted( _x( '%s items(s) emptied!', 'Settings: Message', 'geditorial-admin' ) ),
			/* translators: `%s`: count */
			'closed'    => self::counted( _x( '%s items(s) closed!', 'Settings: Message', 'geditorial-admin' ) ),
			/* translators: `%s`: count */
			'ordered'   => self::counted( _x( '%s items(s) re-ordered!', 'Settings: Message', 'geditorial-admin' ) ),
			/* translators: `%s`: count */
			'scheduled' => self::counted( _x( '%s items(s) re-scheduled!', 'Settings: Message', 'geditorial-admin' ) ),
			/* translators: `%s`: count */
			'synced'    => self::counted( _x( '%s items(s) synced!', 'Settings: Message', 'geditorial-admin' ) ),
			'huh'       => Core\HTML::error( self::huh( self::req( 'huh', NULL ) ) ),
		];
	}

	public static function messageExtra()
	{
		$extra = [];

		if ( isset( $_REQUEST['count'] ) )
			$extra[] = sprintf(
				/* translators: `%s`: count */
				_x( '%s item(s) counted!', 'Settings: Message', 'geditorial-admin' ),
				Core\Number::format( $_REQUEST['count'] )
			);

		return count( $extra ) ? ' ('.implode( WordPress\Strings::separator(), $extra ).')' : '';
	}

	public static function error( $message, $dismissible = TRUE )
	{
		return Core\HTML::error( $message.self::messageExtra(), $dismissible );
	}

	public static function success( $message, $dismissible = TRUE )
	{
		return Core\HTML::success( $message.self::messageExtra(), $dismissible );
	}

	public static function warning( $message, $dismissible = TRUE )
	{
		return Core\HTML::warning( $message.self::messageExtra(), $dismissible );
	}

	public static function info( $message, $dismissible = TRUE )
	{
		return Core\HTML::info( $message.self::messageExtra(), $dismissible );
	}

	public static function getButtonConfirm( $message = NULL )
	{
		return [ 'onclick' => sprintf(
			'return confirm(\'%s\')',
			Core\HTML::escape( $message ?? _x( 'Are you sure? This operation can not be undone.', 'Settings: Confirm', 'geditorial-admin' ) )
		) ];
	}

	public static function submitCheckBox( $name = 'submit', $text = '', $atts = [], $after = '' )
	{
		$id = Core\Text::sanitizeBase( $name );

		$input = Core\HTML::tag( 'input', array_merge( [
			'type'  => 'checkbox',
			'value' => '1',
			'name'  => $name,
			'id'    => $id,
		], $atts ) );

		Core\HTML::label( $input.$text, $id, 'span' );

		echo $after;
	}

	// same as `submitButton()` but wraps in action array
	public static function actionButton( $name = 'submit', $text = NULL, $primary = FALSE, $atts = [], $after = '' )
	{
		return self::submitButton( sprintf( '%s[%s]', 'action', $name ), $text, $primary, $atts, $after );
	}

	public static function submitButton( $name = 'submit', $text = NULL, $primary = FALSE, $atts = [], $after = '' )
	{
		$link    = FALSE;
		$classes = [ '-button', 'button' ];

		if ( is_null( $text ) )
			$text = 'reset' == $name
				? _x( 'Reset Settings', 'Settings: Button', 'geditorial-admin' )
				: _x( 'Save Changes', 'Settings: Button', 'geditorial-admin' );

		if ( TRUE === $atts )
			$atts = self::getButtonConfirm();

		else if ( ! is_array( $atts ) )
			$atts = [];

		if ( 'primary' == $primary )
			$primary = TRUE;

		else if ( 'link' === $primary || 'link-small' === $primary )
			$link = TRUE;

		if ( TRUE === $primary )
			$classes[] = 'button-primary';

		else if ( $primary && 'link' !== $primary && 'link-small' !== $primary )
			$classes[] = 'button-'.$primary;

		else if ( 'link-small' === $primary )
			$classes[] = 'button-small';

		if ( $link )
			echo Core\HTML::tag( 'a', array_merge( $atts, [
				'href'  => $name,
				'class' => $classes,
			] ), $text );

		else
			echo Core\HTML::tag( 'input', array_merge( $atts, [
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
			/* translators: `%s`: count */
			$message = _x( '%s Counted!', 'Settings: Message', 'geditorial-admin' );

		return Core\HTML::notice( sprintf( $message, Core\Number::format( $count ?? self::req( 'count', 0 ) ) ), $class.' fade' );
	}

	public static function cheatin( $message = NULL )
	{
		echo Core\HTML::error( $message ?? _x( 'Cheatin&#8217; uh?', 'Settings: Message', 'geditorial-admin' ) );
	}

	public static function huh( $message = NULL )
	{
		if ( $message )
			return sprintf(
				/* translators: `%s`: message */
				_x( 'huh? %s', 'Settings: Message', 'geditorial-admin' ),
				$message
			);

		return _x( 'huh?', 'Settings: Message', 'geditorial-admin' );
	}

	public static function getModuleSectionTitle( $suffix )
	{
		switch ( $suffix ) {
			case '_general'    : return [ _x( 'General', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_setup'      : return [ _x( 'Setup', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_config'     : return [ _x( 'Configuration', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_defaults'   : return [ _x( 'Defaults', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_tweaks'     : return [ _x( 'Tweaks', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_misc'       : return [ _x( 'Miscellaneous', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_archives'   : return [ _x( 'Archives', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_frontend'   : return [ _x( 'Front-end', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_backend'    : return [ _x( 'Back-end', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_content'    : return [ _x( 'Generated Contents', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_featured'   : return [ _x( 'Featured Contents', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_connected'  : return [ _x( 'Connected Contents', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_supports'   : return [ _x( 'Feature Support', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_dashboard'  : return [ _x( 'Admin Dashboard', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_editlist'   : return [ _x( 'Admin Edit List', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_comments'   : return [ _x( 'Admin Comment List', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_editpost'   : return [ _x( 'Admin Edit Post', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_edittags'   : return [ _x( 'Admin Edit Terms', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_columns'    : return [ _x( 'Admin List Columns', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_import'     : return [ _x( 'Import Preferences', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_reports'    : return [ _x( 'Report Preferences', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_printpage'  : return [ _x( 'Print Preferences', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_shortcode'  : return [ _x( 'ShortCode Preferences', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_strings'    : return [ _x( 'Custom Strings', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_avatars'    : return [ _x( 'User Avatars', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_tabs'       : return [ _x( 'Content Tabs', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_fields'     : return [ _x( 'Data Fields', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_constants'  : return [ _x( 'Custom Constants', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_posttypes'  : return [ _x( 'Post-Types', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_taxonomies' : return [ _x( 'Taxonomies', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_subcontent' : return [ _x( 'Sub-Contents', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_bulkactions': return [ _x( 'Bulk Actions', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_customtabs' : return [ _x( 'Custom Tabs', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_roles'      : return [ _x( 'Availability', 'Settings: Section Title', 'geditorial-admin' ), _x( 'Though Administrators have it all!', 'Settings: Section Description', 'geditorial-admin' ) ];
			case '_units'      : return [ _x( 'Units Fields', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_geo'        : return [ _x( 'Geo Fields', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_woocommerce': return [ _x( 'WooCommerce', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_p2p'        : return [ _x( 'Posts-to-Posts', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
			case '_o2o'        : return [ _x( 'Objects-to-Objects', 'Settings: Section Title', 'geditorial-admin' ), NULL ];
		}

		return FALSE;
	}

	public static function makeModuleSectionTitle( $suffix )
	{
		$title = '';

		foreach ( explode( '_', str_replace( ' ', '_', $suffix ) ) as $word )
			$title.= ucfirst( $word ).' ';

		return $title;
	}

	// @SOURCE: `add_settings_section()`
	public static function addModuleSection( $page, $atts = [] )
	{
		global $wp_settings_sections;

		$args = self::atts( [
			'id'            => FALSE,
			'title'         => FALSE,
			'description'   => FALSE,
			'link'          => FALSE,
			'callback'      => '__return_false',
			'section_class' => '',
		], $atts );

		if ( ! $args['id'] )
			return FALSE;

		if ( ! $args['title'] )
			$args['title'] = self::makeModuleSectionTitle( $args['id'] );

		return $wp_settings_sections[$page][$args['id']] = $args;
	}

	// @SOURCE: `do_settings_sections()`
	public static function moduleSections( $page )
	{
		global $wp_settings_sections, $wp_settings_fields;

		if ( empty( $wp_settings_sections[$page] ) )
			return;

		$tabs   = Core\Arraay::pluck( $wp_settings_sections[$page], 'title', 'id' );
		$active = Core\Arraay::keyFirst( $tabs );

		echo '<div class="base-tabs-list -base nav-tab-base">';

			Core\HTML::tabNav( $active, $tabs );

			foreach ( (array) $wp_settings_sections[$page] as $section ) {

				echo '<div class="nav-tab-content -content'.( $active === $section['id'] ? ' nav-tab-active -active' : '' ).'" data-tab="'.$section['id'].'">';

				if ( $section['callback'] && '__return_false' !== $section['callback'] )
					call_user_func( $section['callback'], $section );

				else
					self::fieldSection( $section );

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

		echo '</div>';
	}

	// NOTE: THE OLD CLASSIC WAY!
	public static function moduleSections_OLD( $page )
	{
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! isset( $wp_settings_sections[$page] ) )
			return;

		foreach ( (array) $wp_settings_sections[$page] as $section ) {

			echo '<div class="'.Core\HTML::prepClass( '-section-wrap', $section['section_class'] ).'">';

				Core\HTML::h2( $section['title'], '-section-title' );

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

			echo '<tr class="'.Core\HTML::prepClass( $class ).'">';

			if ( ! empty( $field['args']['label_for'] ) )
				echo '<th class="-th" scope="row"><label for="'
					.Core\HTML::escape( $field['args']['label_for'] )
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
		Core\HTML::desc( $description, TRUE, '-section-description -section-empty' );
	}

	public static function moduleButtons( $module, $enabled = FALSE )
	{
		if ( $module->autoload ) {
			echo Core\HTML::wrap( _x( 'Auto-loaded!', 'Settings: Notice', 'geditorial-admin' ), '-autoloaded -warning', FALSE );
			return;
		}

		echo Core\HTML::tag( 'input', [
			'type'  => 'submit',
			'value' => _x( 'Enable', 'Settings: Button', 'geditorial-admin' ),
			'style' => $enabled ? 'display:none' : FALSE,
			'class' => Core\HTML::buttonClass( TRUE, [ 'button-primary', 'hide-if-no-js' ] ),
			'data'  => [
				'module' => $module->name,
				'do'     => 'enable',
			],
		] );

		echo Core\HTML::tag( 'input', [
			'type'  => 'submit',
			'value' => _x( 'Disable', 'Settings: Button', 'geditorial-admin' ),
			'style' => $enabled ? FALSE : 'display:none',
			'class' => Core\HTML::buttonClass( TRUE, [ 'button-secondary', 'hide-if-no-js', '-button-danger' ] ),
			'data'  => [
				'module' => $module->name,
				'do'     => 'disable',
			],
		] );

		echo Core\HTML::tag( 'span', [
			'class' => Core\HTML::buttonClass( TRUE, [ '-danger', 'hide-if-js' ] ),
		], _x( 'You have to enable Javascript!', 'Settings: Notice', 'geditorial-admin' ) );
	}

	public static function moduleConfigure( $module, $enabled = FALSE )
	{
		if ( ! $module->configure )
			return;

		if ( 'tools' === $module->configure )
			echo Core\HTML::tag( 'a', [
				'href'  => Settings::getURLbyContext( 'tools', TRUE, [ 'sub' => $module->name ] ),
				'style' => $enabled ? FALSE : 'display:none',
				'class' => Core\HTML::buttonClass( TRUE, 'button-primary' ),
				'data'  => [
					'module' => $module->name,
					'do'     => 'configure',
				],
			], _x( 'Tools', 'Settings: Button', 'geditorial-admin' ) );


		else if ( 'reports' === $module->configure )
			echo Core\HTML::tag( 'a', [
				'href'  => Settings::getURLbyContext( 'reports', TRUE, [ 'sub' => $module->name ] ),
				'style' => $enabled ? FALSE : 'display:none',
				'class' => Core\HTML::buttonClass( TRUE, 'button-primary' ),
				'data'  => [
					'module' => $module->name,
					'do'     => 'configure',
				],
			], _x( 'Reports', 'Settings: Button', 'geditorial-admin' ) );

		else
			echo Core\HTML::tag( 'a', [
				'href'  => Settings::getURLbyContext( 'settings', TRUE, [ 'module' => $module->name ] ),
				'style' => $enabled ? FALSE : 'display:none',
				'class' => Core\HTML::buttonClass( TRUE, 'button-primary' ),
				'data'  => [
					'module' => $module->name,
					'do'     => 'configure',
				],
			], _x( 'Configure', 'Settings: Button', 'geditorial-admin' ) );
	}

	public static function moduleInfo( $module, $enabled = FALSE, $tag = 'h3' )
	{
		$access = ( ! empty( $module->access ) && 'stable' !== $module->access )
			? sprintf( ' <code title="%3$s" class="-acccess -access-%1$s">%2$s</code>',
				$module->access,
				strtoupper( $module->access ),
				_x( 'Access Code', 'Settings: Title Attr', 'geditorial-admin' )
			) : '';

		Core\HTML::h3( Core\HTML::tag( 'a', [
			'href'   => self::getModuleDocsURL( $module ),
			'target' => '_blank',
			'title'  => sprintf(
				/* translators: `%s`: module title */
				_x( '%s Documentation', 'Settings', 'geditorial-admin' ),
				$module->title
			),
		], $module->title ).$access, '-title' );

		Core\HTML::desc( Core\Text::wordWrap( $module->desc ?? '' ) );

		// list.js filters
		echo '<span class="-module-title" style="display:none;" aria-hidden="true">'.$module->title.'</span>';
		echo '<span class="-module-key" style="display:none;" aria-hidden="true">'.$module->name.'</span>';
		echo '<span class="-module-access" style="display:none;" aria-hidden="true">'.$module->access.'</span>';
		echo '<span class="-module-keywords" style="display:none;" aria-hidden="true">'.implode( ' ', Core\Arraay::prepString( $module->keywords ) ).'</span>';
		echo '<span class="status" data-do="enabled" style="display:none;" aria-hidden="true">'.( $enabled ? 'true' : 'false' ).'</span>';
	}

	/**
	 * Returns Documentation URL for the module.
	 *
	 * @param boolean|object $module
	 * @return string
	 */
	public static function getModuleDocsURL( $module = FALSE )
	{
		return FALSE === $module || 'config' == $module->name
			? 'https://github.com/geminorum/geditorial/wiki'
			: 'https://github.com/geminorum/geditorial/wiki/Modules-'.Services\Modulation::moduleSlug( $module->name );
	}

	public static function settingsCredits()
	{
		if ( GEDITORIAL_DISABLE_CREDITS )
			return;

		echo '<div class="credits">';

		echo '<p>';
			echo 'This is a fork in structure of <a href="http://editflow.org/">EditFlow</a><br />';
			echo '<a href="https://github.com/geminorum/geditorial/issues" target="_blank">Feedback, Ideas and Bug Reports</a> are welcomed.<br />';
			echo 'You\'re using gEditorial <a href="https://github.com/geminorum/geditorial/releases/latest" target="_blank" title="Check for the latest version">v'.GEDITORIAL_VERSION.'</a>';
		echo '</p>';

		echo '<a href="https://geminorum.ir" title="it\'s a geminorum project"><img src="'
			.GEDITORIAL_URL.'assets/images/itsageminorumproject-lightgrey.min.svg" alt="" /></a>';

		echo '</div>';
	}

	public static function settingsSignature( $context = NULL )
	{
		if ( GEDITORIAL_DISABLE_CREDITS )
			return;

		echo '<div class="signature clear"><p>';
			printf(
				/* translators: `%1$s`: plugin url, `%2$s`: author url */
				_x( '<a href="%1$s">gEditorial</a> is a <a href="%2$s">geminorum</a> project.', 'Settings: Signature', 'geditorial-admin' ),
				'https://github.com/geminorum/geditorial',
				'https://geminorum.ir/'
			);
		echo '</p></div>';
	}

	public static function helpSidebar( $list )
	{
		if ( ! is_array( $list ) )
			return $list;

		$html = '';

		foreach ( $list as $link )
			$html.= '<li>'.Core\HTML::link( $link['title'], $link['url'], TRUE ).'</li>';

		return $html ? Core\HTML::wrap( '<ul>'.$html.'</ul>', '-help-sidebar' ) : FALSE;
	}

	/**
	 * Returns the help content for given module
	 *
	 * @param boolean|object $module
	 * @return array
	 */
	public static function helpContent( $module = FALSE )
	{
		if ( ! function_exists( 'gnetwork_github' ) )
			return [];

		$wikihome = [
			'module'   => $module,
			'id'       => 'geditorial-wikihome',
			'callback' => [ __CLASS__, 'add_help_tab_home_callback' ],
			'title'    => _x( 'Editorial Wiki', 'Settings: Help Content Title', 'geditorial-admin' ),
		];

		if ( FALSE === $module || 'config' === $module->name )
			return [ $wikihome ];

		$wikimodule = [
			'module'   => $module,
			'id'       => 'geditorial-'.$module->name.'-wikihome',
			'callback' => [ __CLASS__, 'add_help_tab_module_callback' ],
			'title'    => sprintf(
				/* translators: `%s`: module title */
				_x( '%s Wiki', 'Settings: Help Content Title', 'geditorial-admin' ),
				$module->title
			),
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
			: 'Modules-'.Services\Modulation::moduleSlug( $module->name );

		echo gnetwork_github( [
			'repo'    => 'geminorum/geditorial',
			'type'    => 'wiki',
			'page'    => $page,
			'context' => 'help_tab',
		] );
	}

	// TODO: move to new main: `Fields`
	// TODO: support HTML `pattern`: https://input-pattern.com/en/tutorial.php
	// TODO: support HTML `title_attr`
	public static function fieldType( $atts, &$scripts )
	{
		if ( FALSE === $atts )
			return;

		$args = self::atts( [
			'title'       => '&nbsp;',
			'description' => isset( $atts['desc'] ) ? $atts['desc'] : '',
			'label_for'   => '',
			'type'        => 'enabled',
			'field'       => FALSE,
			'values'      => [],
			'exclude'     => '',

			'none_title'     => NULL,    // select option none title
			'none_value'     => NULL,    // select option none value
			'template_value' => '%s',    // used on display value output
			'filter'         => FALSE,   // will use via sanitize
			'callback'       => FALSE,   // callable for `callback` type
			'dir'            => FALSE,
			'disabled'       => FALSE,
			'readonly'       => FALSE,

			'default'  => '',
			'defaults' => [],   // default value to ignore && override the saved

			'option_group' => 'settings',
			'option_base'  => 'geditorial',
			'options'      => [],             // saved options

			'id_name_cb' => FALSE,   // id/name generator callback
			'id_attr'    => FALSE,   // override
			'name_attr'  => FALSE,   // override
			'step_attr'  => '1',     // for number type
			'min_attr'   => '0',     // for number type
			'rows_attr'  => '5',     // for textarea type
			'cols_attr'  => '45',    // for textarea type

			'placeholder' => FALSE,
			'constant'    => FALSE,   // override value if constant defined & disabling
			'ortho'       => FALSE,

			'data'   => [],     // data attr
			'extra'  => [],     // extra args to pass to deeper generator
			'hidden' => [],     // extra hidden values
			'cap'    => NULL,

			'wrap'        => FALSE,
			'class'       => '',      // now used on wrapper
			'field_class' => '',      // formally just class!
			'before'      => '',      // html to print before field
			'after'       => '',      // html to print after field

			'string_select'   => self::showOptionNone(),
			'string_disabled' => _x( 'Disabled', 'Settings', 'geditorial-admin' ),
			'string_enabled'  => _x( 'Enabled', 'Settings', 'geditorial-admin' ),
			'string_empty'    => _x( 'No options!', 'Settings', 'geditorial-admin' ),
			'string_noaccess' => _x( 'You do not have access to change this option.', 'Settings', 'geditorial-admin' ),
		], $atts );

		if ( TRUE === $args['wrap'] )
			$args['wrap'] = 'div';

		if ( 'tr' === $args['wrap'] ) {

			// NOTE: to use inside list-table

			if ( ! empty( $args['label_for'] ) )
				echo '<tr class="'.Core\HTML::prepClass( $args['class'] ).'"><th scope="row"><label for="'.Core\HTML::escape( $args['label_for'] ).'">'.$args['title'].'</label></th><td>';

			else
				echo '<tr class="'.Core\HTML::prepClass( $args['class'] ).'"><th scope="row">'.$args['title'].'</th><td>';

		} else if ( 'fieldset' === $args['wrap'] ) {

			// NOTE: to use inside quick-edit

			echo '<fieldset><div class="inline-edit-col">';
			echo '<label'.( empty( $args['label_for'] ) ? '' : ( ' for="'.Core\HTML::escape( $args['label_for'] ).'"' ) ).'>';
			echo '<span class="title">'.$args['title'].'</span></label>';
			echo '<span class="input-text-wrap">';

		} else if ( $args['wrap'] ) {

			echo '<'.$args['wrap'].' class="'.Core\HTML::prepClass( '-wrap', '-settings-field', '-'.$args['type'], $args['class'] ).'">';

			if ( ! empty( $args['label_for'] ) && '&nbsp;' !== $args['title'] )
				echo Core\HTML::tag( 'label', [ 'for' => $args['label_for'] ], $args['title'] );
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

			$id   = $args['id_attr'] ? $args['id_attr'] : ( $args['option_base'] ? $args['option_base'].'-' : '' ).$args['option_group'].'-'.Core\HTML::escape( $args['field'] );
			$name = $args['name_attr'] ? $args['name_attr'] : ( $args['option_base'] ? $args['option_base'].'_' : '' ).$args['option_group'].'['.Core\HTML::escape( $args['field'] ).']';
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
			$args['after']    = Core\HTML::code( $args['constant'] );
		}

		if ( empty( $args['data'] ) )
			$args['data'] = [];

		$args['data']['raw-value'] = $value;

		if ( is_null( $args['cap'] ) ) {

			if ( in_array( $args['type'], [ 'role', 'cap', 'user' ] ) )
				$args['cap'] = 'promote_users';

			else if ( in_array( $args['type'], [ 'file' ] ) )
				$args['cap'] = 'upload_files';

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

				echo Core\HTML::tag( 'input', [
					'type'  => 'hidden',
					'id'    => $id,
					'name'  => $name,
					'value' => $value,
					'data'  => $args['data'],
				] );

				$args['description'] = FALSE;

				break;

			case 'enabled':

				$html = Core\HTML::tag( 'option', [
					'value'    => '0',
					'selected' => '0' == $value,
				], Core\HTML::escape( empty( $args['values'][0] ) ? $args['string_disabled'] : $args['values'][0] ) );

				$html.= Core\HTML::tag( 'option', [
					'value'    => '1',
					'selected' => '1' == $value,
				], Core\HTML::escape( empty( $args['values'][1] ) ? $args['string_enabled'] : $args['values'][1] ) );

				echo Core\HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => Core\HTML::attrClass( $args['field_class'], '-type-enabled' ),
					// NOTE: `select` tag doesn't have a `readonly`, keeping `disabled` with hidden input // @REF: https://stackoverflow.com/a/368834
					'disabled' => $args['disabled'] || $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

				if ( $args['readonly'] )
					Core\HTML::inputHidden( $name, $value );

				break;

			case 'disabled':

				$html = Core\HTML::tag( 'option', [
					'value'    => '0',
					'selected' => '0' == $value,
				], empty( $args['values'][0] ) ? $args['string_enabled'] : $args['values'][0] );

				$html.= Core\HTML::tag( 'option', [
					'value'    => '1',
					'selected' => '1' == $value,
				], empty( $args['values'][1] ) ? $args['string_disabled'] : $args['values'][1] );

				echo Core\HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => Core\HTML::attrClass( $args['field_class'], '-type-disabled' ),
					// NOTE: `select` tag doesn't have a `readonly`, keeping `disabled` with hidden input // @REF: https://stackoverflow.com/a/368834
					'disabled' => $args['disabled'] || $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

				if ( $args['readonly'] )
					Core\HTML::inputHidden( $name, $value );

				break;

			case 'text':

				if ( ! $args['field_class'] )
					$args['field_class'] = 'regular-text';

				if ( FALSE === $args['values'] ) {

					Core\HTML::desc( $args['string_empty'], TRUE, '-empty' );

				} else if ( $args['values'] && count( $args['values'] ) ) {

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = Core\HTML::tag( 'input', [
							'type'        => 'text',
							'id'          => $id.'-'.$value_name,
							'name'        => $name.'['.$value_name.']',
							'value'       => $value[$value_name] ?? '',
							'class'       => Core\HTML::attrClass( $args['field_class'], '-type-text' ),
							'placeholder' => $args['placeholder'],
							'disabled'    => Core\HTML::attrBoolean( $args['disabled'], $value_name ),
							'readonly'    => Core\HTML::attrBoolean( $args['readonly'], $value_name ),
							'dir'         => $args['dir'],
							'data'        => $args['data'],
							'data-ortho'  => $args['ortho'],
						] );

						$html.= '&nbsp;<span class="-field-after">'.$value_title.'</span>';

						Core\HTML::label( $html, $id.'-'.$value_name );
					}

				} else {

					echo Core\HTML::tag( 'input', [
						'type'        => 'text',
						'id'          => $id,
						'name'        => $name,
						'value'       => $value,
						'class'       => Core\HTML::attrClass( $args['field_class'], '-type-text' ),
						'placeholder' => $args['placeholder'],
						'disabled'    => $args['disabled'],
						'readonly'    => $args['readonly'],
						'dir'         => $args['dir'],
						'data'        => $args['data'],
						'data-ortho'  => $args['ortho'],
					] );
				}

				break;

			case 'number':

				if ( ! $args['field_class'] )
					$args['field_class'] = 'small-text';

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				echo Core\HTML::tag( 'input', [
					'type'        => 'number',
					'id'          => $id,
					'name'        => $name,
					'value'       => (int) $value,
					'step'        => (int) $args['step_attr'],
					'min'         => (int) $args['min_attr'],
					'class'       => Core\HTML::attrClass( $args['field_class'], '-type-number' ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
					'data-ortho'  => $args['ortho'],
				] );

				break;

			case 'url':

				if ( ! $args['field_class'] )
					$args['field_class'] = [ 'regular-text', 'url-text' ];

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				echo Core\HTML::tag( 'input', [
					'type'        => 'url',
					'id'          => $id,
					'name'        => $name,
					'value'       => $value,
					'class'       => Core\HTML::attrClass( $args['field_class'], '-type-url' ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
					'data-ortho'  => $args['ortho'],
				] );

				break;

			case 'color':

				if ( ! $args['field_class'] )
					$args['field_class'] = [ 'small-text', 'color-text' ];

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				echo Core\HTML::tag( 'input', [
					'type'        => 'text', // it's better to be `text`
					'id'          => $id,
					'name'        => $name,
					'value'       => $value,
					'class'       => Core\HTML::attrClass( $args['field_class'], '-type-color' ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
					'data-ortho'  => $args['ortho'],
				] );

				// NOTE: CAUTION: module must enqueue `wp-color-picker` styles/scripts
				// @SEE: `Scripts::enqueueColorPicker()`
				$scripts[] = '$("#'.$id.'").wpColorPicker();';

				break;

			case 'email':

				if ( ! $args['field_class'] )
					$args['field_class'] = [ 'regular-text', 'email-text' ];

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				echo Core\HTML::tag( 'input', [
					'type'        => 'email',
					'id'          => $id,
					'name'        => $name,
					'value'       => $value,
					'class'       => Core\HTML::attrClass( $args['field_class'], '-type-email' ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
					'data-ortho'  => $args['ortho'],
				] );

				break;

			case 'checkbox':

				$html = Core\HTML::tag( 'input', [
					'type'     => 'checkbox',
					'id'       => $id,
					'name'     => $name,
					'value'    => '1',
					'checked'  => $value,
					'class'    => Core\HTML::attrClass( $args['field_class'], '-type-checkbox' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				] );

				Core\HTML::label( $html.'&nbsp;'.$args['description'], $id );

				$args['description'] = FALSE;

				break;

			case 'checkboxes':
			case 'checkboxes-values':

				if ( $args['values'] && count( $args['values'] ) ) {

					$wrap_class = $args['wrap'] ? '' : $args['class'];

					echo '<div class="'.Core\HTML::prepClass( $wrap_class ).'"'
						.Core\HTML::propData( $args['data'] ).'>';

					if ( ! is_null( $args['none_title'] ) ) {

						$html = Core\HTML::tag( 'input', [
							'type'     => 'checkbox',
							'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'name'     => $name.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'data'     => [ 'raw-value' => $args['none_value'] ?? '' ],
							'value'    => $args['none_value'] ?? '1',
							'checked'  => FALSE === $value || in_array( $args['none_value'], (array) $value ),
							'class'    => Core\HTML::attrClass( $args['field_class'], '-type-checkbox', '-option-none' ),
							'disabled' => Core\HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
							'readonly' => Core\HTML::attrBoolean( $args['readonly'], $args['none_value'] ),
							'dir'      => $args['dir'],
						] );

						$for = $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] );

						Core\HTML::label( $html.'&nbsp;'.$args['none_title'], $for );
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = Core\HTML::tag( 'input', [
							'type'     => 'checkbox',
							'id'       => $id.'-'.$value_name,
							'name'     => $name.'['.$value_name.']',
							'data'     => [ 'raw-value' => $value_name ],
							'value'    => '1',
							'checked'  => TRUE === $value || in_array( $value_name, (array) $value ),
							'class'    => Core\HTML::attrClass( $args['field_class'], '-type-checkbox' ),
							'disabled' => Core\HTML::attrBoolean( $args['disabled'], $value_name ),
							'readonly' => Core\HTML::attrBoolean( $args['readonly'], $value_name ),
							'dir'      => $args['dir'],
						] );

						$html.= '&nbsp;'.$value_title;

						if ( 'checkboxes-values' == $args['type'] )
							$html.= ' &mdash; <code>'.sprintf( $args['template_value'], $value_name ).'</code>';

						Core\HTML::label( $html, $id.'-'.$value_name );
					}

					echo '</div>';

				} else if ( is_array( $args['values'] ) ) {

					$args['description'] = FALSE;
					Core\HTML::desc( $args['string_empty'], TRUE, '-empty' );
				}

				break;

			case 'checkbox-panel':
			case 'checkboxs-panel': // TYPO: DEPRECATED
			case 'checkboxes-panel':
			case 'checkboxes-panel-expanded':

				if ( $args['values'] && count( $args['values'] ) ) {

					$wrap_class = $args['wrap'] ? '' : $args['class'];

					if ( 'checkboxes-panel-expanded' === $args['type'] )
						$wrap_class = Core\HTML::attrClass( $wrap_class, '-panel-expanded' );

					echo self::tabPanelOpen( $args['data'], $wrap_class );

					if ( ! is_null( $args['none_title'] ) ) {

						$html = Core\HTML::tag( 'input', [
							'type'     => 'checkbox',
							'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'name'     => $name.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'data'     => [ 'raw-value' => $args['none_value'] ?? '' ],
							'value'    => $args['none_value'] ?? '1',
							'checked'  => FALSE === $value || in_array( $args['none_value'], (array) $value ),
							'class'    => Core\HTML::attrClass( $args['field_class'], '-type-checkbox', '-option-none' ),
							'disabled' => Core\HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
							'readonly' => Core\HTML::attrBoolean( $args['readonly'], $args['none_value'] ),
							'dir'      => $args['dir'],
						] );

						$for = $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] );

						Core\HTML::label( $html.'&nbsp;'.$args['none_title'], $for, 'li' );
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = Core\HTML::tag( 'input', [
							'type'     => 'checkbox',
							'id'       => $id.'-'.$value_name,
							'name'     => $name.'['.$value_name.']',
							'data'     => [ 'raw-value' => $value_name ],
							'value'    => '1',
							'checked'  => TRUE === $value || in_array( $value_name, (array) $value ),
							'class'    => Core\HTML::attrClass( $args['field_class'], '-type-checkbox' ),
							'disabled' => Core\HTML::attrBoolean( $args['disabled'], $value_name ),
							'readonly' => Core\HTML::attrBoolean( $args['readonly'], $value_name ),
							'dir'      => $args['dir'],
						] );

						Core\HTML::label( $html.'&nbsp;'.$value_title, $id.'-'.$value_name, 'li' );
					}

					echo '</ul></div>';

				} else if ( is_array( $args['values'] ) ) {

					$args['description'] = FALSE;
					Core\HTML::desc( $args['string_empty'], TRUE, '-empty' );
				}

				break;

			case 'radio':
			case 'radio-values':

				if ( $args['values'] && count( $args['values'] ) ) {

					if ( ! is_null( $args['none_title'] ) ) {

						$html = Core\HTML::tag( 'input', [
							'type'     => 'radio',
							'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'name'     => $name,
							'value'    => $args['none_value'] ?? FALSE,
							'checked'  => in_array( $args['none_value'], (array) $value ),
							'class'    => Core\HTML::attrClass( $args['field_class'], '-type-radio', '-option-none' ),
							'disabled' => Core\HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
							'readonly' => Core\HTML::attrBoolean( $args['readonly'], $args['none_value'] ),
							'dir'      => $args['dir'],
						] );

						$for = $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] );

						Core\HTML::label( $html.'&nbsp;'.$args['none_title'], $for );
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = Core\HTML::tag( 'input', [
							'type'     => 'radio',
							'id'       => $id.'-'.$value_name,
							'name'     => $name,
							'value'    => $value_name,
							'checked'  => in_array( $value_name, (array) $value ),
							'class'    => Core\HTML::attrClass( $args['field_class'], '-type-radio' ),
							'disabled' => Core\HTML::attrBoolean( $args['disabled'], $value_name ),
							'readonly' => Core\HTML::attrBoolean( $args['readonly'], $value_name ),
							'dir'      => $args['dir'],
						] );

						$html.= '&nbsp;'.$value_title;

						if ( 'radio-values' == $args['type'] )
							$html.= ' &mdash; <code>'.sprintf( $args['template_value'], $value_name ).'</code>';

						Core\HTML::label( $html, $id.'-'.$value_name );
					}
				}

				break;

			case 'date-format':

				if ( ! $args['values'] )
					$args['values'] = Datetime::dateFormats( FALSE );

				if ( ! is_null( $args['none_title'] ) ) {

					$html = Core\HTML::tag( 'input', [
						'type'     => 'radio',
						'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
						'name'     => $name,
						'value'    => $args['none_value'] ?? FALSE,
						'checked'  => in_array( $args['none_value'], (array) $value ),
						'class'    => Core\HTML::attrClass( $args['field_class'], '-type-radio', '-type-date-format', '-option-none' ),
						'disabled' => Core\HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
						'readonly' => Core\HTML::attrBoolean( $args['readonly'], $args['none_value'] ),
						'dir'      => $args['dir'],
					] );

					$for = $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] );

					Core\HTML::label( $html.'&nbsp;'.$args['none_title'], $for );
				}

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html = Core\HTML::tag( 'input', [
						'type'     => 'radio',
						'id'       => $id.'-'.$value_name,
						'name'     => $name,
						'value'    => $value_name,
						'checked'  => in_array( $value_name, (array) $value ),
						'class'    => Core\HTML::attrClass( $args['field_class'], '-type-radio', '-type-date-format' ),
						'disabled' => Core\HTML::attrBoolean( $args['disabled'], $value_name ),
						'readonly' => Core\HTML::attrBoolean( $args['readonly'], $value_name ),
						'dir'      => $args['dir'],
					] );

					$html.= sprintf( '&nbsp;<span title="%s">&#8206;%s&#8207; &mdash; %s</span>',
						Core\HTML::escapeAttr( $value_name ),
						Core\HTML::code( $value_title ),  // click-to-copy not working on label
						Core\Date::get( $value_title )
					);

					Core\HTML::label( $html, $id.'-'.$value_name );
				}

				break;

			case 'select':

				if ( FALSE !== $args['values'] ) {

					if ( ! is_null( $args['none_title'] ) ) {

						if ( is_null( $args['none_value'] ) )
							$args['none_value'] = '0';

						$html.= Core\HTML::tag( 'option', [
							'value'    => $args['none_value'],
							'selected' => $value == $args['none_value'],
							'disabled' => Core\HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
						], $args['none_title'] );
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html.= Core\HTML::tag( 'option', [
							'value'    => $value_name,
							'selected' => $value == $value_name,
							'disabled' => Core\HTML::attrBoolean( $args['disabled'], $value_name ),
						], $value_title );
					}

					echo Core\HTML::tag( 'select', [
						'id'       => $id,
						'name'     => $name,
						'class'    => Core\HTML::attrClass( $args['field_class'], '-type-select' ),
						// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
						// @REF: https://stackoverflow.com/a/368834
						// `disabled` previously applied to `option` elements
						'disabled' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					], $html );

					if ( $args['readonly'] )
						Core\HTML::inputHidden( $name, $value );

				} else {

					Core\HTML::desc( $args['string_empty'], TRUE, '-empty' );
				}

				break;

			case 'textarea':
			case 'textarea-quicktags':
			case 'textarea-quicktags-tokens':
			case 'textarea-code-editor':

				if ( ! $args['field_class'] )
					$args['field_class'] = [ 'regular-text', 'textarea-autosize' ];

				if ( 'textarea-quicktags' == $args['type'] ) {

					$args['field_class'] = Core\HTML::attrClass( $args['field_class'], 'textarea-quicktags', 'code' );

					if ( ! $args['dir'] && Core\L10n::rtl() )
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

					$args['field_class'] = Core\HTML::attrClass( $args['field_class'], 'textarea-quicktags', 'code' );

					if ( ! $args['dir'] && Core\L10n::rtl() )
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

					// NOTE: CAUTION: module must enqueue `code-editor` styles/scripts
					// @SEE: `Scripts::enqueueCodeEditor()`
					$scripts[] = sprintf( 'wp.CodeMirror.fromTextArea(document.getElementById("%s"), %s);',
						$id, Core\HTML::encode( $args['values'] ) );
				}

				echo Core\HTML::tag( 'textarea', [
					'id'          => $id,
					'name'        => $name,
					'rows'        => $args['rows_attr'],
					'cols'        => $args['cols_attr'],
					'class'       => Core\HTML::attrClass( $args['field_class'], '-type'.$args['type'] ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
					'data-ortho'  => $args['ortho'],
				], esc_textarea( $value ) );

				break;

			case 'page':

				$args['values'] = $args['values'] ?: 'page';

				$query = array_merge( [
					'post_type'   => $args['values'],
					'selected'    => $value,
					'exclude'     => implode( ',', $exclude ),
					'sort_column' => 'menu_order',
					'sort_order'  => 'asc',
					'post_status' => WordPress\Status::acceptable( $args['values'], 'dropdown', [ 'pending' ] ),
				], $args['extra'] );

				$pages = get_pages( $query );

				if ( ! empty( $pages ) ) {

					$html.= Core\HTML::tag( 'option', [
						'value' => $args['none_value'] ?? '0',
					], $args['none_title'] ?? $args['string_select'] );

					$html.= walk_page_dropdown_tree( $pages, ( isset( $query['depth'] ) ? $query['depth'] : 0 ), $query );

					echo Core\HTML::tag( 'select', [
						'id'       => $id,
						'name'     => $name,
						'class'    => Core\HTML::attrClass( $args['field_class'], '-type-page', '-posttype-'.$args['values'] ),
						// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
						// @REF: https://stackoverflow.com/a/368834
						'disabled' => $args['disabled'] || $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					], $html );

					if ( $args['readonly'] )
						Core\HTML::inputHidden( $name, $value );

				} else {

					$args['description'] = FALSE;
				}

				break;

			case 'navmenu':

				if ( ! $args['values'] )
					$args['values'] = Core\Arraay::pluck( wp_get_nav_menus(), 'name', 'term_id' );

				if ( ! empty( $args['values'] ) ) {

					$args['none_value'] = $args['none_value'] ?? '0';

					$html.= Core\HTML::tag( 'option', [
						'value'    => $args['none_value'],
						'selected' => $value == $args['none_value'],
						'disabled' => Core\HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
					], $args['none_title'] ?? $args['string_select'] );

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html.= Core\HTML::tag( 'option', [
							'value'    => $value_name,
							'selected' => $value == $value_name,
							'disabled' => Core\HTML::attrBoolean( $args['disabled'], $value_name ),
						], $value_title );
					}

					echo Core\HTML::tag( 'select', [
						'id'       => $id,
						'name'     => $name,
						'class'    => Core\HTML::attrClass( $args['field_class'], '-type-navmenu' ),
						// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
						// @REF: https://stackoverflow.com/a/368834
						// `disabled` previously applied to `option` elements
						'disabled' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					], $html );

					if ( $args['readonly'] )
						Core\HTML::inputHidden( $name, $value );

				} else {

					Core\HTML::desc( $args['string_empty'], TRUE, '-empty' );

					$args['description'] = FALSE;
				}

				break;

			case 'role':

				if ( ! $args['values'] )
					$args['values'] = array_reverse( get_editable_roles() );

				$args['none_value'] = $args['none_value'] ?? '0';

				$html.= Core\HTML::tag( 'option', [
					'value'    => $args['none_value'],
					'selected' => $value == $args['none_value'],
					'disabled' => Core\HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
				], $args['none_title'] ?? $args['string_select'] );

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html.= Core\HTML::tag( 'option', [
						'value'    => $value_name,
						'selected' => $value == $value_name,
						'disabled' => Core\HTML::attrBoolean( $args['disabled'], $value_name ),
					], Core\HTML::escape( translate_user_role( $value_title['name'] ) ) );
				}

				echo Core\HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => Core\HTML::attrClass( $args['field_class'], '-type-role' ),
					// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
					// @REF: https://stackoverflow.com/a/368834
					'disabled' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

				if ( $args['readonly'] )
					Core\HTML::inputHidden( $name, $value );

				break;

			case 'user':

				if ( ! $args['values'] )
					$args['values'] = WordPress\User::get( FALSE, FALSE, $args['extra'] );

				if ( ! is_null( $args['none_title'] ) ) {

					$args['none_value'] = $args['none_value'] ?? FALSE;

					$html.= Core\HTML::tag( 'option', [
						'value'    => $args['none_value'],
						'selected' => $value == $args['none_value'],
						'disabled' => Core\HTML::attrBoolean( $args['disabled'], $args['none_value'] ),
					], $args['none_title'] );
				}

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html.= Core\HTML::tag( 'option', [
						'value'    => $value_name,
						'selected' => $value == $value_name,
						'disabled' => Core\HTML::attrBoolean( $args['disabled'], $value_name ),
					], Core\HTML::escape( sprintf( '%1$s (%2$s)', $value_title->display_name, $value_title->user_login ) ) );
				}

				echo Core\HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => Core\HTML::attrClass( $args['field_class'], '-type-user' ),
					// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
					// @REF: https://stackoverflow.com/a/368834
					'disabled' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

				if ( $args['readonly'] )
					Core\HTML::inputHidden( $name, $value );

				break;

			case 'priority':

				if ( ! $args['values'] )
					$args['values'] = self::priorityOptions( FALSE );

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html.= Core\HTML::tag( 'option', [
						'value'    => $value_name,
						'selected' => $value == $value_name,
						'disabled' => Core\HTML::attrBoolean( $args['disabled'], $value_name ),
					], Core\HTML::escape( $value_title ) );
				}

				echo Core\HTML::tag( 'select', [
					'id'       => $id,
					'name'     => $name,
					'class'    => Core\HTML::attrClass( $args['field_class'], '-type-priority' ),
					// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
					// @REF: https://stackoverflow.com/a/368834
					'disabled' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				], $html );

				if ( $args['readonly'] )
					Core\HTML::inputHidden( $name, $value );

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

				echo Core\HTML::tag( 'input', [
					'type'     => 'file',
					'id'       => $id,
					'name'     => $name,
					'class'    => Core\HTML::attrClass( $args['field_class'], '-type-file' ),
					'disabled' => $args['disabled'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
					'accept'   => empty( $args['values'] ) ? FALSE : implode( ',', $args['values'] ),
				] );

				break;

			case 'posttypes':
			case 'posttypes-expanded':

				// FIXME: false to disable
				if ( ! $args['values'] )
					$args['values'] = WordPress\PostType::get( 0,
						array_merge( [ 'show_ui' => TRUE ], $args['extra'] ) );

				if ( empty( $args['values'] ) ) {

					Core\HTML::desc( $args['string_empty'], TRUE, '-empty' );
					break;
				}

				$wrap_class = $args['wrap'] ? '' : $args['class'];

				if ( 'posttypes-expanded' === $args['type'] )
					$wrap_class = Core\HTML::attrClass( $wrap_class, '-panel-expanded' );

				echo self::tabPanelOpen( $args['data'], $wrap_class );

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html = Core\HTML::tag( 'input', [
						'type'     => 'checkbox',
						'id'       => $id.'-'.$value_name,
						'name'     => $name.'['.$value_name.']',
						'data'     => [ 'raw-value' => $value_name ],
						'value'    => '1',
						'checked'  => in_array( $value_name, (array) $value ),
						'class'    => Core\HTML::attrClass( $args['field_class'], '-type-posttypes' ),
						'disabled' => Core\HTML::attrBoolean( $args['disabled'], $value_name ),
						'readonly' => Core\HTML::attrBoolean( $args['readonly'], $value_name ),
						'dir'      => $args['dir'],
					] );

					$html.= '&nbsp;'.Core\HTML::escape( $value_title );
					$html.= ' &mdash; <code>'.sprintf( $args['template_value'], $value_name ).'</code>';

					Core\HTML::label( $html, $id.'-'.$value_name, 'li' );
				}

				echo '</ul></div>';

				break;

			case 'taxonomies':
			case 'taxonomies-expanded':

				if ( ! $args['values'] )
					$args['values'] = WordPress\Taxonomy::get( 0, $args['extra'] );

				if ( empty( $args['values'] ) ) {

					Core\HTML::desc( $args['string_empty'], TRUE, '-empty' );
					break;
				}

				$wrap_class = $args['wrap'] ? '' : $args['class'];

				if ( 'taxonomies-expanded' === $args['type'] )
					$wrap_class = Core\HTML::attrClass( $wrap_class, '-panel-expanded' );

				echo self::tabPanelOpen( $args['data'], $wrap_class );

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html = Core\HTML::tag( 'input', [
						'type'     => 'checkbox',
						'id'       => $id.'-'.$value_name,
						'name'     => $name.'['.$value_name.']',
						'data'     => [ 'raw-value' => $value_name ],
						'value'    => '1',
						'checked'  => in_array( $value_name, (array) $value ),
						'class'    => Core\HTML::attrClass( $args['field_class'], '-type-taxonomies' ),
						'disabled' => Core\HTML::attrBoolean( $args['disabled'], $value_name ),
						'readonly' => Core\HTML::attrBoolean( $args['readonly'], $value_name ),
						'dir'      => $args['dir'],
					] );

					$html.= '&nbsp;'.Core\HTML::escape( $value_title );
					$html.= ' &mdash; <code>'.sprintf( $args['template_value'], $value_name ).'</code>';

					Core\HTML::label( $html, $id.'-'.$value_name, 'li' );
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

							echo Core\HTML::tag( 'a', [
								'href'  => '#',
								'class' => '-icon-button',
								'data'  => [
									'setting' => 'object-addnew',
									'target'  => $args['field'],
								],
							], Core\HTML::getDashicon( 'plus-alt' ) );

					echo '</p></div>';

					Scripts::enqueue( 'settings.typeobject' );

				} else {

					$args['description'] = FALSE;
					Core\HTML::desc( $args['string_empty'], TRUE, '-empty' );
				}

				break;

			case 'callback':

				if ( is_callable( $args['callback'] ) ) {

					call_user_func_array( $args['callback'], [ &$args,
						compact( 'html', 'value', 'name', 'id', 'exclude' ) ] );

				} else if ( WordPress\IsIt::dev() ) {

					echo 'Error: Setting Is NOT Callable!';
				}

				break;

			case 'noaccess':

				echo Core\HTML::tag( 'span', [
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

		Core\HTML::inputHiddenArray( $args['hidden'] );

		if ( $args['after'] )
			echo '&nbsp;'.$args['after'];

		if ( FALSE !== $args['values'] )
			Core\HTML::desc( $args['description'] );

		if ( 'tr' === $args['wrap'] )
			echo '</td></tr>';

		else if ( 'fieldset' === $args['wrap'] )
			echo '</span></span></div></fieldset>';

		else if ( $args['wrap'] )
			echo '</'.$args['wrap'].'>';
	}

	// TODO: support more types!
	// FIXME: WTF: not possible to pass fields with arrays (check-boxes/multiple-select)
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
			$ortho       = array_key_exists( 'ortho', $field ) ? $field['ortho'] : FALSE;
			$values      = array_key_exists( 'values', $field ) ? $field['values'] : [];

			switch ( $field['type'] ) {

				case 'select':

					if ( FALSE !== $values ) {

						foreach ( $values as $value_name => $value_title ) {

							$html.= Core\HTML::tag( 'option', [
								'value'    => $value_name,
								'selected' => $value == $value_name,
							], $value_title );
						}

						$html = Core\HTML::tag( 'select', [
							'name'  => $name,
							'class' => Core\HTML::attrClass( $field['field_class'] ?? '', '-type-select' ),
							'dir'   => $field['dir'] ?? FALSE,
						], $html );

						$html.= '&nbsp;<span class="-field-after">'.$field['title'].'</span>';
					}

					break;

				case 'number':

					$html = Core\HTML::tag( 'input', [
						'type'        => 'text',
						'name'        => $name,
						'placeholder' => $placeholder,
						'title'       => $description,
						'value'       => $value,
						'class'       => Core\HTML::attrClass( $field['field_class'] ?? 'small-text', '-type-number' ),
						'dir'         => $field['dir'] ?? 'ltr',
						'data-ortho'  => $ortho,
					] );

					$html.= '&nbsp;<span class="-field-after">'.$field['title'].'</span>';

					break;

				case 'textarea': // FIXME: make this a `textarea`
				case 'text':
				default:

					$html = Core\HTML::tag( 'input', [
						'type'        => 'text',
						'name'        => $name,
						'placeholder' => $placeholder,
						'title'       => $description,
						'value'       => $value,
						'class'       => Core\HTML::attrClass( $field['field_class'] ?? 'regular-text', '-type-text' ),
						'dir'         => $field['dir'] ?? FALSE,
						'data-ortho'  => $ortho,
					] );

					$html.= '&nbsp;<span class="-field-after">'.$field['title'].'</span>';
			}

			$group.= Core\HTML::tag( 'p', $html );
		}

		$group.= '<div data-setting="object-group-controls" class="-group-controls">';
			$group.= Core\HTML::tag( 'a', [
				'href'  => '#',
				'class' => '-icon-button',
				'data'  => [
					'setting' => 'object-remove',
					'target'  => $args['field'],
				],
			], Core\HTML::getDashicon( 'dismiss' ) );
		$group.= '</div>';

		return Core\HTML::tag( 'div', [
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
			'string_on'  => _x( 'On', 'Settings: Switch On-Off', 'geditorial-admin' ),
			'string_off' => _x( 'Off', 'Settings: Switch On-Off', 'geditorial-admin' ),
			'echo'       => TRUE,
		], $atts );

		$input = Core\HTML::tag( 'input', [
			'type'     => 'checkbox',
			'value'    => '1',
			'id'       => $args['id'],
			'name'     => $args['name'],
			'checked'  => $args['checked'],
			'disabled' => $args['disabled'],
			'class'    => Core\HTML::attrClass( $args['class'], '-type-switchonoff-input -checkbox' ), // `.checkbox`
		] );

		$html = '<span class="switch__circle"><span class="switch__circle-inner"></span></span>';
		$html.= '<span class="switch__left">'.$args['string_off'].'</span>';
		$html.= '<span class="switch__right">'.$args['string_on'].'</span>';

		$label = Core\HTML::tag( 'label', [
			'for'   => $args['id'],
			'class' => '-type-switchonoff-label -switch', // `.switch`
		], $html );

		$html = Core\HTML::wrap( $input.$label, '-type-switchonoff' );

		if ( ! $args['echo'] )
			return $html;

		echo $html;

		return TRUE;
	}

	// @REF: https://codepen.io/geminorum/pen/RwEPyWJ
	public static function tabPanelOpen( $data = [], $class = '' )
	{
		if ( empty( $data ) )
			$data = [];

		$data['select-all-label'] = _x( 'Select All', 'Settings: Tab Panel', 'geditorial-admin' );

		return '<div class="'
			.Core\HTML::prepClass( 'wp-tab-panel', '-with-select-all', $class ).'"'
			.Core\HTML::propData( $data ).'><ul>';
	}

	public static function processingListOpen()
	{
		return '<div class="'.static::BASE.'-processing-wrap"><ul>';
	}

	public static function processingListItem( $verbose, $message, $args = [], $returns = FALSE )
	{
		if ( $verbose )
			echo Core\HTML::row(
				count( $args )
					? vsprintf( $message, $args )
					: $message
				);

		return $returns;
	}

	public static function processingAllDone( $remove = NULL )
	{
		echo self::toolboxColumnOpen( Plugin::done( FALSE ) );
		echo self::toolboxAfterOpen();

			echo Core\HTML::button(
				_x( 'Go-back &larr;', 'Settings: Button', 'geditorial-admin' ),
				remove_query_arg( $remove ?? [
					'action',
					'type',
					'mime',
					// 'taxonomy', // NOTE: passing back will help the dropdown on default selected
					// 'metakey',  // NOTE: passing back will help the dropdown on default selected
					'message',
					'paged',
					'count',
				] )
			);

		echo '</div></div>';

		return TRUE;
	}

	public static function processingErrorOpen( $title = NULL )
	{
		return
			self::toolboxColumnOpen( $title ?? Plugin::wrong( FALSE ) ).
			self::toolboxAfterOpen();
	}

	public static function toolboxCardOpen( $title = '', $buttons = TRUE )
	{
		return '<div class="-wrap '.static::BASE.'-wrap card -toolbox-card">'
			.( $title ? sprintf( '<h4 class="title -title">%s</h4>', $title ) : '' )
			.( $buttons ? '<div class="-wrap -wrap-button-row">' : '' );
	}

	public static function toolboxAfterOpen( $desc = '', $buttons = FALSE )
	{
		return '<div class="-wrap '.static::BASE.'-wrap -toolbox-after">'
			.( $desc ? sprintf( '<p class="-description">%s</p>', nl2br( $desc ) ) : '' )
			.( $buttons ? '<div class="-wrap -wrap-button-row">' : '' );
	}

	public static function toolboxColumnOpen( $title = '' )
	{
		return '<div class="-wrap '.static::BASE.'-wrap -toolbox-column">'
			.( $title ? sprintf( '<h3 class="-title">%s</h3>', $title ) : '' );
	}
}
