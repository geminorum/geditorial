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

	public static function getURLbyContext( string $context, $full = TRUE, $extra = [] )
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
			case 'kiosks':
			case 'reports':

				$url = sprintf( 'index.php?page=%s', self::classs( $context ) );
		}

		if ( $full )
			$url = get_admin_url( NULL, $url );

		return $extra ? add_query_arg( $extra, $url ) : $url;
	}

	public static function isScreenContext( string $context, ?object $screen = NULL ): bool
	{
		$screen = $screen ?? get_current_screen();

		if ( ! empty( $screen->base ) && Core\Text::has( $screen->base, self::classs( $context ) ) )
			return TRUE;

		return FALSE;
	}

	// NOTE: better to use `$this->get_module_url()`
	#[\Deprecated()]
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

	// TODO: Move to `Fields` Service
	public static function getPageExcludes( array $include = [], ?string $context = NULL ): array
	{
		$pages = [];

		if ( ! in_array( 'front', $include, TRUE ) )
			$pages[] = get_option( 'page_on_front' );

		if ( ! in_array( 'posts', $include, TRUE ) )
			$pages[] = get_option( 'page_for_posts' );

		if ( ! in_array( 'privacy', $include, TRUE ) )
			$pages[] = get_option( 'wp_page_for_privacy_policy' );

		return Core\Arraay::prepNumeral(
			apply_filters( self::und( static::BASE, 'page_excludes' ),
				$pages,
				$context ?? 'settings'
			)
		);
	}

	// TODO: Move to `Fields` Service
	public static function priorityOptions( bool $format = TRUE ): array
	{
		return
			array_reverse( Core\Arraay::range( -100, -1000, 100, $format ), TRUE ) +
			array_reverse( Core\Arraay::range( -10, -100, 10, $format ), TRUE ) +
			Core\Arraay::range( 0, 100, 10, $format ) +
			Core\Arraay::range( 100, 1000, 100, $format );
	}

	// TODO: Move to `Fields` Service
	public static function minutesOptions(): array
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

	// TODO: Move to `CustomPostType` Service
	public static function supportsOptions(): array
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

	public static function posttypesParents( string|array $extra = [], ?string $context = NULL ): array
	{
		$list = [
			'human',
			'team_member',
			'department',
			'publication',
			'entry',
		];

		return Core\Arraay::prepString(
			apply_filters( self::und( static::BASE, 'posttypes_parents' ),
				array_merge(
					$list,
					(array) $extra
				),
				$context ?? 'settings'
			)
		);
	}

	/**
	 * Returns the filtered list of general excluded post-types.
	 *
	 * @param string|array $extra
	 * @param string|array $keeps
	 * @param string $context
	 * @return array
	 */
	public static function posttypesExcluded( string|array $extra = [], string|array $keeps = [], ?string $context = NULL ): array
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
			'wp_font_face',        // WP Core
			'wp_font_family',      // WP Core
			'wp_sync_storage',     // WP Core
			'user_request',        // WP Core
			'oembed_cache',        // WP Core

			'fdentry' ,   // WTF?!
			'adstxt'  ,   // WTF?!

			'buddypress'        ,   // BuddyPress
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
			'wpephpcompat_jobs' ,

			'acf-ui-options-page' ,   // Advanced Custom Fields / Secure Custom Fields
			'acf-field'           ,   // Advanced Custom Fields / Secure Custom Fields
			'acf-field-group'     ,   // Advanced Custom Fields / Secure Custom Fields
			'acf-taxonomy'        ,   // Advanced Custom Fields / Secure Custom Fields
			'acf-post-type'       ,   // Advanced Custom Fields / Secure Custom Fields
		];

		if ( class_exists( 'bbPress' ) )
			$list = array_merge( $list, [
				'forum',
				'topic',
				'reply',
			] );

		// @hook: `geditorial_posttypes_excluded`
		return apply_filters( self::und( static::BASE, 'posttypes', 'excluded' ),
			array_diff( array_merge( $list, (array) $extra ), (array) $keeps ),
			$context ?? 'settings',
			(array) $extra,
			(array) $keeps
		);
	}

	public static function taxonomiesExcluded( string|array $extra = [], string|array $keeps = [], ?string $context = NULL ): array
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
			// 'affiliation'     ,   // Editorial: People
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
		return apply_filters( self::und( static::BASE, 'taxonomies', 'excluded' ),
			array_diff( array_merge( $list, (array) $extra ), (array) $keeps ),
			$context ?? 'settings',
			(array) $extra,
			(array) $keeps
		);
	}

	public static function rolesExcluded( string|array $extra = [], string|array $keeps = [], ?string $context = NULL ): array
	{
		$list = [
			'administrator',  // WP Core
			'subscriber',     // WP Core

			'backwpup_admin',
			'backwpup_check',
			'backwpup_helper',
		];

		// @hook: `geditorial_roles_excluded`
		return apply_filters( self::und( static::BASE, 'roles', 'excluded' ),
			array_diff( array_merge( $list, (array) $extra ), (array) $keeps ),
			$context ?? 'settings',
			(array) $extra,
			(array) $keeps
		);
	}

	public static function showOptionNone( ?string $string = NULL ): string
	{
		if ( $string )
			return sprintf(
				/* translators: `%s`: options */
				_x( '&ndash; Select %s &ndash;', 'Settings: Dropdown Select Option None', 'geditorial-admin' ),
				$string
			);

		return _x( '&ndash; Select &ndash;', 'Settings: Dropdown Select Option None', 'geditorial-admin' );
	}

	public static function showRadioNone( ?string $string = NULL ): string
	{
		if ( $string )
			return sprintf(
				/* translators: `%s`: options */
				_x( 'None %s', 'Settings: Radio Select Option None', 'geditorial-admin' ),
				$string
			);

		return _x( 'None', 'Settings: Radio Select Option None', 'geditorial-admin' );
	}

	public static function showOptionAll( ?string $string = NULL ): string
	{
		if ( $string )
			return sprintf(
				/* translators: `%s`: options */
				_x( '&ndash; All %s &ndash;', 'Settings: Dropdown Select Option All', 'geditorial-admin' ),
				$string
			);

		return _x( '&ndash; All &ndash;', 'Settings: Dropdown Select Option All', 'geditorial-admin' );
	}

	public static function fieldSeparate( string $string = 'from', string $before = '', string $after = '' ): void
	{
		switch ( $string ) {
			case 'count': $string = _x( 'count', 'Settings: Field Separate', 'geditorial-admin' ); break;
			case 'from' : $string = _x( 'from', 'Settings: Field Separate', 'geditorial-admin' );  break;
			case 'into' : $string = _x( 'into', 'Settings: Field Separate', 'geditorial-admin' );  break;
			case 'like' : $string = _x( 'like', 'Settings: Field Separate', 'geditorial-admin' );  break;
			case 'via'  : $string = _x( 'via', 'Settings: Field Separate', 'geditorial-admin' );   break;
			case 'ex'   : $string = _x( 'ex', 'Settings: Field Separate', 'geditorial-admin' );    break;
			case 'in'   : $string = _x( 'in', 'Settings: Field Separate', 'geditorial-admin' );    break;
			case 'to'   : $string = _x( 'to', 'Settings: Field Separate', 'geditorial-admin' );    break;
			case 'as'   : $string = _x( 'as', 'Settings: Field Separate', 'geditorial-admin' );    break;
			case 'or'   : $string = _x( 'or', 'Settings: Field Separate', 'geditorial-admin' );    break;
			case 'on'   : $string = _x( 'on', 'Settings: Field Separate', 'geditorial-admin' );    break;
		}

		printf( '%s<span class="-field-sep">&nbsp;&mdash; %s &mdash;&nbsp;</span>%s', $before, $string, $after );
	}

	public static function fieldAfterText( false|string $text, string $wrap = 'span', string $class = '-text-wrap' ): string
	{
		return $text ? Core\HTML::tag( $wrap, [ 'class' => '-field-after '.$class ], $text ) : '';
	}

	public static function fieldAfterIcon( $url = '', $title = NULL, $icon = 'info' ): string
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
	public static function fieldAfterPostTypeConstant(): string
	{
		return self::fieldAfterIcon( '#',
			_x( 'Must not exceed 20 characters and may only contain lowercase alphanumeric characters, dashes, and underscores.', 'Setting: Setting Info', 'geditorial-admin' ) );
	}

	// NOTE: @see `WordPress\Taxonomy::NAME_INPUT_PATTERN`
	public static function fieldAfterTaxonomyConstant(): string
	{
		return self::fieldAfterIcon( '#',
			_x( 'Must not exceed 32 characters and may only contain lowercase alphanumeric characters, dashes, and underscores.', 'Setting: Setting Info', 'geditorial-admin' ) );
	}

	// NOTE: @see `WordPress\ShortCode::NAME_INPUT_PATTERN`
	public static function fieldAfterShortCodeConstant(): string
	{
		return self::fieldAfterIcon( '#',
			_x( 'Do not use spaces or reserved characters.', 'Setting: Setting Info', 'geditorial-admin' ) );
	}

	/**
	 * Retrieves the list of capabilities with role titles.
	 * NOTE: must check with `WordPress\User::cuc()`
	 * TODO: Move to `Fields` Service
	 *
	 * @param string $single_title
	 * @param bool $pseudo_caps
	 * @param string $none_title
	 * @param string $none_value
	 * @return array|string
	 */
	public static function getUserCapList( ?string $single_title = NULL, ?bool $pseudo_caps = NULL, ?string $none_title = NULL, ?string $none_value = NULL ): array|string
	{
		$multisite   = is_multisite();
		$pseudo_caps = $pseudo_caps ?? $multisite;
		$none_value  = $none_value  ?? 'none';
		$none_title  = $none_title  ?? _x( '&ndash; No One &ndash;', 'Fields: Role Dropdown', 'geditorial' );

		$list = [
			'edit_theme_options'   => _x( 'Administrators', 'Fields: Role Dropdown', 'geditorial' ),
			'edit_others_posts'    => _x( 'Editors', 'Fields: Role Dropdown', 'geditorial' ),
			'edit_published_posts' => _x( 'Authors', 'Fields: Role Dropdown', 'geditorial' ),
			'edit_posts'           => _x( 'Contributors', 'Fields: Role Dropdown', 'geditorial' ),
		];

		if ( $multisite ) {

			if ( $pseudo_caps )
				$list['_member_of_site'] = _x( 'Site Users', 'Fields: Role Dropdown', 'geditorial' );

			$list = [
				'manage_network' => _x( 'Super Admins', 'Fields: Role Dropdown', 'geditorial' ),
			] + $list;

			if ( $pseudo_caps )
				$list['_member_of_network'] = _x( 'Network Users', 'Fields: Role Dropdown', 'geditorial' );
		}

		// @hook: `geditorial_settings_user_cap_list`
		$list = apply_filters( self::und( static::BASE, 'settings', 'user_cap_list' ),
			$list,
			$pseudo_caps,
			$none_value,
			$none_title,
		);

		if ( $none_title )
			$list[$none_value] = $none_title;

		return $single_title ? $list[$single_title] : $list;
	}

	public static function sub( string|false $default = 'general' ): string
	{
		return trim( self::req( 'sub', $default ) );
	}

	public static function wrapOpen( string $sub, ?string $context = NULL, string $iframe_title = '' ): void
	{
		$context = $context ?? 'settings';

		if ( self::req( 'noheader' ) ) {

			add_filter( 'show_admin_bar', '__return_false', 9999 );
			wp_dequeue_script( 'admin-bar' );
			wp_dequeue_style( 'admin-bar' );

			self::define( 'IFRAME_REQUEST', TRUE );
			iframe_header( $iframe_title );
		}

		echo '<div id="'.self::dsh( static::BASE, $context ).'" class="'.Core\HTML::prepClass(
			'wrap',
			'-settings-wrap',
			self::req( 'noheader' ) ? '-iframe-wrap' : '',
			self::dsh( static::BASE, 'admin-wrap' ),
			self::dsh( static::BASE, $context ),
			self::dsh( static::BASE, $context, $sub ),
			self::dsh( 'sub', $sub )
		).'">';
	}

	public static function wrapClose( bool $iframe_exit = TRUE, ?string $context = NULL ): void
	{
		$context = $context ?? 'settings';

		echo '<div class="clear"></div></div>';

		if ( self::req( 'noheader' ) ) {

			iframe_footer();

			if ( $iframe_exit )
				exit;
		}
	}

	public static function wrapError( string $message, ?string $title = NULL, bool $returns = FALSE ): bool
	{
		self::wrapOpen( 'error' );
			self::headerTitle( 'error', $title );
			echo $message;
		self::wrapClose();
		
		return $returns;
	}

	// @REF: `get_admin_page_title()`
	public static function headerTitle(
		?string $context = NULL,
		?string $title = NULL,
		false|string|array|null $back = NULL,
		?string $to = NULL,
		string|array|false $icon = '',
		false|int $count = FALSE,
		bool $search = FALSE,
		bool $filters = FALSE,
	): void {

		$system = Plugin::system();
		$before = $class = '';
		$title  = $title ?? $system ?: _x( 'Editorial', 'Settings', 'geditorial-admin' );

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
				'id'          => self::dsh( static::BASE, 'setting-search' ), // Browser needs `id` or `name` for an `input`
				'type'        => 'search',
				'class'       => [ 'settings-title-search', '-search', 'hide-if-no-js' ], // 'fuzzy-search' // fuzzy wont work on Persian
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

		echo '<hr class="wp-header-end">'; // NOTE: notices will be pulled after this!
	}

	public static function sideOpen(
		?string $title = NULL,
		?string $uri = '',
		?string $active = '',
		array $subs = [],
		string|false|null $heading = NULL,
	): void {

		echo '<div class="side-nav-wrap">';

		$title = $title ?? ( Plugin::system() ?: _x( 'Editorial', 'Settings: Header Title', 'geditorial-admin' ) );
		$extra = [ 'style' => sprintf( '--side-nav-title-length: %d', Core\Text::utf8Len( $title ) ) ];

		Core\HTML::h2( $title, '-title' );
		Core\HTML::headerNav( $uri, $active, $subs, $extra, 'side-nav', 'ul', 'li' );

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
		// echo '<hr class="wp-header-end">'; // NOTE: notices will be pulled after this!
	}

	public static function sideClose(): void
	{
		// echo '</div><div class="clear"></div></div>';
		echo '</div></div>';
	}

	// @SEE: `wp_admin_notice()` @since WP 6.4.0
	// @SEE: https://core.trac.wordpress.org/ticket/57791
	public static function message( ?array $messages = NULL ): void
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
	public static function messages(): array
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

	public static function messageExtra(): string
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

		Core\HTML::label( $input.$text, $id, FALSE );

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

	public static function cheatin( ?string $message = NULL ): bool
	{
		echo Core\HTML::error( $message ?? _x( 'Cheatin&#8217; uh?', 'Settings: Message', 'geditorial-admin' ) );

		return FALSE; // help the caller
	}

	public static function huh( ?string $message = NULL ): string
	{
		if ( $message )
			return sprintf(
				/* translators: `%s`: message */
				_x( 'huh? %s', 'Settings: Message', 'geditorial-admin' ),
				$message
			);

		return _x( 'huh?', 'Settings: Message', 'geditorial-admin' );
	}

	// TODO: Move to `Fields` Service: `Fields::renderType()`
	// TODO: support HTML `pattern`: https://input-pattern.com/en/tutorial.php
	// TODO: support HTML `title_attr`
	public static function fieldType( array $atts, array &$scripts ): void
	{
		if ( FALSE === $atts )
			return;

		$args = self::parsed( [
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
			'validator'   => NULL,

			'data'   => [],     // data attr
			'extra'  => [],     // extra args to pass to deeper generator
			'hidden' => [],     // extra hidden values
			'cap'    => NULL,

			'wrap'        => FALSE,
			'class'       => '',      // now used on wrapper
			'field_class' => '',      // formally just class!
			'before'      => '',      // HTML to print before field
			'after'       => '',      // HTML to print after field

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

			$id   = $args['id_attr']   ? $args['id_attr']   : ( $args['option_base'] ? $args['option_base'].'-' : '' ).$args['option_group'].'-'.Core\HTML::escape( $args['field'] );
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
							'type'           => 'text',
							'id'             => $id.'-'.$value_name,
							'name'           => $name.'['.$value_name.']',
							'value'          => $value[$value_name] ?? '',
							'class'          => Core\HTML::attrClass( $args['field_class'], '-type-text' ),
							'placeholder'    => $args['placeholder'],
							'disabled'       => Core\HTML::attrBoolean( $args['disabled'], $value_name ),
							'readonly'       => Core\HTML::attrBoolean( $args['readonly'], $value_name ),
							'dir'            => $args['dir'],
							'data'           => $args['data'],
							'data-ortho'     => $args['ortho'],
							'data-validator' => $args['validator'] ?: FALSE,
						] );

						$html.= '&nbsp;<span class="-field-after">'.$value_title.'</span>';

						Core\HTML::label( $html, $id.'-'.$value_name );
					}

				} else {

					echo Core\HTML::tag( 'input', [
						'type'           => 'text',
						'id'             => $id,
						'name'           => $name,
						'value'          => $value,
						'class'          => Core\HTML::attrClass( $args['field_class'], '-type-text' ),
						'placeholder'    => $args['placeholder'],
						'disabled'       => $args['disabled'],
						'readonly'       => $args['readonly'],
						'dir'            => $args['dir'],
						'data'           => $args['data'],
						'data-ortho'     => $args['ortho'],
						'data-validator' => $args['validator'],
					] );
				}

				break;

			case 'number':

				if ( ! $args['field_class'] )
					$args['field_class'] = 'small-text';

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				echo Core\HTML::tag( 'input', [
					'type'           => 'number',
					'id'             => $id,
					'name'           => $name,
					'value'          => (int) $value,
					'step'           => (int) $args['step_attr'],
					'min'            => (int) $args['min_attr'],
					'class'          => Core\HTML::attrClass( $args['field_class'], '-type-number' ),
					'placeholder'    => $args['placeholder'],
					'disabled'       => $args['disabled'],
					'readonly'       => $args['readonly'],
					'dir'            => $args['dir'],
					'data'           => $args['data'],
					'data-ortho'     => $args['ortho'],
					'data-validator' => $args['validator'] ?? 'number',
				] );

				break;

			case 'url':

				if ( ! $args['field_class'] )
					$args['field_class'] = [ 'regular-text', 'url-text' ];

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				echo Core\HTML::tag( 'input', [
					'type'           => 'url',
					'id'             => $id,
					'name'           => $name,
					'value'          => $value,
					'class'          => Core\HTML::attrClass( $args['field_class'], '-type-url' ),
					'placeholder'    => $args['placeholder'],
					'disabled'       => $args['disabled'],
					'readonly'       => $args['readonly'],
					'dir'            => $args['dir'],
					'data'           => $args['data'],
					'data-ortho'     => $args['ortho'],
					'data-validator' => $args['validator'] ?? 'url',
				] );

				break;

			case 'color':

				if ( ! $args['field_class'] )
					$args['field_class'] = [ 'small-text', 'color-text' ];

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				echo Core\HTML::tag( 'input', [
					'type'           => 'text', // NOTE: better to be `text`
					'id'             => $id,
					'name'           => $name,
					'value'          => $value,
					'class'          => Core\HTML::attrClass( $args['field_class'], '-type-color' ),
					'placeholder'    => $args['placeholder'],
					'disabled'       => $args['disabled'],
					'readonly'       => $args['readonly'],
					'dir'            => $args['dir'],
					'data'           => $args['data'],
					'data-ortho'     => $args['ortho'],
					'data-validator' => $args['validator'] ?? 'color',
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
					'type'           => 'email',
					'id'             => $id,
					'name'           => $name,
					'value'          => $value,
					'class'          => Core\HTML::attrClass( $args['field_class'], '-type-email' ),
					'placeholder'    => $args['placeholder'],
					'disabled'       => $args['disabled'],
					'readonly'       => $args['readonly'],
					'dir'            => $args['dir'],
					'data'           => $args['data'],
					'data-ortho'     => $args['ortho'],
					'data-validator' => $args['validator'] ?? 'email',
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
						Core\HTML::escape( $value_name ),
						Core\HTML::code( $value_title ),  // NOTE: `clicktoclip` does not work on labels!
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
					'id'             => $id,
					'name'           => $name,
					'rows'           => $args['rows_attr'],
					'cols'           => $args['cols_attr'],
					'class'          => Core\HTML::attrClass( $args['field_class'], '-type'.$args['type'] ),
					'placeholder'    => $args['placeholder'],
					'disabled'       => $args['disabled'],
					'readonly'       => $args['readonly'],
					'dir'            => $args['dir'],
					'data'           => $args['data'],
					'data-ortho'     => $args['ortho'],
					'data-validator' => $args['validator'] ?: FALSE,
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

			case 'cap':

				if ( ! $args['values'] )
					$args['values'] = self::getUserCapList( NULL, NULL, $args['none_title'], $args['none_value'] );

				if ( count( $args['values'] ) ) {

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
						'class'    => Core\HTML::attrClass( $args['field_class'], '-type-cap' ),
						// `select` doesn't have a `readonly`, keeping `disabled` with hidden input
						// @REF: https://stackoverflow.com/a/368834
						'disabled' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					], $html );

					if ( $args['readonly'] )
						Core\HTML::inputHidden( $name, $value );

				} else {

					$args['description'] = FALSE;
				}

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

					Scripts::enqueue( self::dot( 'settings', 'typeobject' ) );

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

	// TODO: Move to `Fields` Service
	// TODO: support more types!
	// FIXME: WTF: not possible to pass fields with arrays (check-boxes/multiple-select)
	private static function fieldType_getObjectForm( array $args, array $fields, string $name_prefix = '', array $options = [] ): string
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
			$validator   = array_key_exists( 'validator', $field ) ? $field['validator'] : NULL;
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
						'type'           => 'text',
						'name'           => $name,
						'placeholder'    => $placeholder,
						'title'          => $description,
						'value'          => $value,
						'class'          => Core\HTML::attrClass( $field['field_class'] ?? 'small-text', '-type-number' ),
						'dir'            => $field['dir'] ?? 'ltr',
						'data-ortho'     => $ortho,
						'data-validator' => $validator ?? 'number',
					] );

					$html.= '&nbsp;<span class="-field-after">'.$field['title'].'</span>';

					break;

				case 'textarea': // FIXME: make this a `textarea`
				case 'text':
				default:

					$html = Core\HTML::tag( 'input', [
						'type'           => 'text',
						'name'           => $name,
						'placeholder'    => $placeholder,
						'title'          => $description,
						'value'          => $value,
						'class'          => Core\HTML::attrClass( $field['field_class'] ?? 'regular-text', '-type-text' ),
						'dir'            => $field['dir'] ?? FALSE,
						'data-ortho'     => $ortho,
						'data-validator' => $validator ?: FALSE,
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

	// TODO: Move to `Fields` Service
	public static function fieldType_switchOnOff( array $atts = [] ): string|true
	{
		$args = self::parsed( [
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
	public static function tabPanelOpen( mixed $data = [], string|array $class = '' ): string
	{
		if ( empty( $data ) )
			$data = [];

		$data['select-all-label'] = _x( 'Select All', 'Settings: Tab Panel', 'geditorial-admin' );

		return '<div class="'
			.Core\HTML::prepClass( 'wp-tab-panel', '-with-select-all', $class ).'"'
			.Core\HTML::propData( $data ).'><ul>';
	}

	public static function processingListOpen(): string
	{
		return '<div class="'.static::BASE.'-processing-wrap"><ul>';
	}

	public static function processingListItem( bool $verbose, ?string $message, array $args = [], mixed $returns = FALSE ): mixed
	{
		if ( $verbose )
			echo Core\HTML::row(
				count( $args )
					? vsprintf( $message, $args )
					: $message
				);

		return $returns;
	}

	public static function processingAllDone( ?string $message = NULL, string|array|null $remove = NULL ): true
	{
		if ( FALSE !== $message )
			echo self::toolboxColumnOpen( $message ?? Plugin::done( FALSE ) );

		echo self::toolboxAfterOpen();

			echo self::goBackButton( $remove );

		echo '</div>';

		if ( FALSE !== $message )
			echo '</div>';

		return TRUE;
	}

	public static function goBackButton( string|array|null $remove = NULL ): string
	{
		return Core\Link::button(
			_x( '&larr; Go-back', 'Settings: Button', 'geditorial-admin' ),
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
	}

	public static function processingErrorOpen( ?string $title = NULL ): string
	{
		return
			self::toolboxColumnOpen( $title ?? Plugin::wrong( FALSE ) ).
			self::toolboxAfterOpen();
	}

	// EXTRA: `.-tablelist-card`
	public static function toolboxCardOpen( string $title = '', bool $buttons = TRUE, string|array $extra = [] ): string
	{
		return '<div class="'.Core\HTML::prepClass( '-wrap',  static::BASE.'-wrap', 'card', '-toolbox-card', $extra ).'">'
			.( $title ? sprintf( '<h4 class="title -title">%s</h4>', $title ) : '' )
			.( $buttons ? '<div class="-wrap -wrap-button-row">' : '' );
	}

	public static function toolboxAfterOpen( string $desc = '', bool $buttons = FALSE ): string
	{
		return '<div class="-wrap '.static::BASE.'-wrap -toolbox-after">'
			.( $desc ? sprintf( '<p class="description -description">%s</p>', nl2br( $desc ) ) : '' )
			.( $buttons ? '<div class="-wrap -wrap-button-row">' : '' );
	}

	public static function toolboxAfterLinks( array $links, string $desc = '' ): void
	{
		echo self::toolboxAfterOpen( $desc, TRUE );

			$buttons = [];

			foreach ( $links as $link )
				$buttons[] = Core\Link::button( $link['title'], $link['url'] );

			echo Core\HTML::wrap( Core\HTML::rows( $buttons ), '-toolbox-links -centered' );

		echo '</div></div>';
	}

	public static function toolboxColumnOpen( string $title = '' ): string
	{
		return '<div class="-wrap '.static::BASE.'-wrap -toolbox-column">'
			.( $title ? sprintf( '<h3 class="-title">%s</h3>', $title ) : '' );
	}
}
