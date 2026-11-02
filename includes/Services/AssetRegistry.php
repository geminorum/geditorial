<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class AssetRegistry extends gEditorial\Service
{
	public static $asset_adminbar = FALSE;
	public static $asset_styles   = FALSE;
	public static $asset_config   = FALSE;
	public static $asset_jsargs   = [];
	public static $asset_icons    = [];
	public static $adminbar_nodes = [];

	public static function setup(): void
	{
		if ( is_admin() ) {

			add_action( 'admin_print_footer_scripts', [ __CLASS__, 'footer_asset_config_early' ], 1 );
			add_action( 'admin_print_footer_scripts', [ __CLASS__, 'footer_asset_config_late' ], 999 );

		} else {

			add_action( 'wp_footer', [ __CLASS__, 'footer_asset_config_early' ], 1 );
			add_action( 'wp_footer', [ __CLASS__, 'footer_asset_config_late' ], 999 );
			add_action( 'wp_enqueue_scripts', [ __CLASS__, 'wp_enqueue_scripts' ] );
		}

		add_action( 'admin_bar_init', [ __CLASS__, 'admin_bar_init' ] );
		add_action( 'admin_bar_menu', [ __CLASS__, 'admin_bar_menu' ], 999 );
	}

	// NOTE: override to use plugin version hash.
	public static function linkStyleSheet(
		string            $url,
		null|string|array $version = NULL,
		false|string      $media   = FALSE,
		bool              $verbose = TRUE,
	): true {

		return Core\HTML::linkStyleSheet(
			$url,
			$version ?? static::factory()->get_hash(),
			$media,
			$verbose,
		);
	}

	public static function linkStyleSheetAdmin(
		string            $page,
		null|string|array $version = NULL,
		bool              $verbose = TRUE,
		string            $prefix  = 'admin',
	): true {

		return Core\HTML::linkStyleSheet(
			static::factory()->get_url().'assets/css/'.( $prefix ? $prefix.'.' : '' ).$page.( is_rtl() ? '-rtl' : '' ).'.css',
			$version ?? static::factory()->get_hash(),
			'all',
			$verbose,
		);
	}

	// NOTE: `enqueue` means just the styles, not the admin-bar itself!
	public static function enqueue_adminbar( ?bool $value = NULL ): bool
	{
		return static::$asset_adminbar = $value ?? static::$asset_adminbar;
	}

	// NOTE: passing `NULL` returns the current state.
	public static function enqueue_styles( ?bool $value = NULL ): bool
	{
		return static::$asset_styles = $value ?? static::$asset_styles;
	}

	public static function enqueue_asset_adminbar(): bool|string
	{
		if ( ! is_admin_bar_showing() )
			return FALSE;

		if ( count( static::$adminbar_nodes ) )
			static::$asset_adminbar = TRUE;

		if ( ! static::$asset_adminbar )
			return FALSE;

		return gEditorial\Scripts::enqueueStyleSrc(
			sprintf( '%sassets/css/%s.%s.css',
				static::factory()->get_url(),
				'adminbar',
				'all',
			),
			self::dsh( static::BASE, 'adminbar' ),
			static::factory()->get_hash(),
		);
	}

	public static function wp_enqueue_scripts(): void
	{
		self::enqueue_asset_adminbar();

		if ( ! static::$asset_styles )
			return;

		if ( defined( 'GEDITORIAL_DISABLE_FRONT_STYLES' ) && GEDITORIAL_DISABLE_FRONT_STYLES )
			return;

		$url  = static::factory()->get_url();
		$hash = static::factory()->get_hash();

		gEditorial\Scripts::enqueueStyleSrc(
			sprintf( '%sassets/css/%s.%s.css', $url, 'front', 'all' ),
			self::dsh( static::BASE, 'front' ),
			$hash,
		);

		if ( WordPress\WooCommerce::isActive() )
			gEditorial\Scripts::enqueueStyleSrc(
				sprintf( '%sassets/css/%s.%s.css', $url, 'front', 'woocommerce' ),
				self::dsh( static::BASE, 'woocommerce', 'front' ),
				$hash,
			);

		if ( defined( 'GNETWORK_VERSION' ) )
			return;

		gEditorial\Scripts::enqueueStyleSrc(
			sprintf( '%sassets/css/%s.%s.css', $url, 'front', 'gnetwork' ),
			self::dsh( static::BASE, 'gnetwork', 'front' ),
			$hash,
		);
	}

	public static function enqueue_asset_config( array $args = [], ?string $module = NULL ): bool
	{
		static::$asset_config = TRUE;

		if ( empty( $args ) )
			return TRUE;

		if ( is_null( $module ) )
			static::$asset_jsargs = array_merge(
				static::$asset_jsargs,
				$args,
			);

		else if ( isset( static::$asset_jsargs[$module] ) )
			static::$asset_jsargs[$module] = array_merge(
				static::$asset_jsargs[$module],
				$args,
			);

		else
			static::$asset_jsargs[$module] = $args;

		return TRUE;
	}

	// used in front & admin
	public static function footer_asset_config_early(): void
	{
		if ( static::$asset_config )
			gEditorial\Scripts::printJSConfig( static::$asset_jsargs );
	}

	// used in front & admin
	public static function footer_asset_config_late(): void
	{
		if ( static::$asset_config )
			gEditorial\Scripts::printJSDebug();

		Core\Icon::printSprites( static::$asset_icons );
	}

	public static function admin_bar_init(): void
	{
		do_action_ref_array( 'geditorial_adminbar', [
			&static::$adminbar_nodes,
			static::BASE,
		] );
	}

	public static function admin_bar_menu( object $wp_admin_bar ): void
	{
		// NOT USED YET: WTF: it causes to `geditorial_adminbar` action fires again!
		// `do_action_ref_array( 'geditorial_adminbar_lastcall', [ &static::$adminbar_nodes, static::BASE ] );`

		if ( empty( static::$adminbar_nodes ) )
			return;

		if ( in_array( static::BASE, Core\Arraay::column( static::$adminbar_nodes, 'parent' ) ) ) {

			if ( ! is_user_logged_in() )
				$link = FALSE;

			else if ( current_user_can( 'manage_options' ) )
				$link = gEditorial\Settings::getURLbyContext( 'settings' );

			else if ( current_user_can( 'edit_others_posts' ) )
				$link = gEditorial\Settings::getURLbyContext( 'reports' );

			else
				$link = FALSE;

			$wp_admin_bar->add_node( [
				// 'parent' => 'top-secondary',
				'id'     => static::BASE,
				'title'  => Icons::adminBarMarkup(),
				// `'title'  => gEditorial()->module( 'config' )->_get_icon(),` // WTF??!
				'href'   => $link,
				'meta'   => [
					'title' => self::system() ?: _x( 'Editorial', 'Adminbar Node', 'geditorial' ),
					'class' => self::dsh( static::BASE, 'adminbar', 'node', 'icononly' ),
				],
			] );
		}

		foreach ( static::$adminbar_nodes as $node )
			$wp_admin_bar->add_node( $node );
	}

	public static function icon( string $name, string $group, array $extra = [], bool $enqueue = TRUE ): false|string|array
	{
		if ( $icon = Core\Icon::get( $name, $group, $extra ) ) {

			if ( ! $enqueue )
				return $icon;

			$key = self::und( $group, $name );

			if ( ! isset( static::$asset_icons[$key] ) )
				static::$asset_icons[$key] = [
					'icon'    => $name,
					'group'   => $group,
				];

			return $icon;
		}

		return FALSE;
	}
}
