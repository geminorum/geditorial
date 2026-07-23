<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait AdminPage
{
	public $module = NULL;

	protected $base = NULL;
	protected $key  = NULL;

	protected function get_adminpage_subs( ?string $context = NULL ): array
	{
		$context = $context ?? 'mainpage';

		// `$subs = $this->list_posttypes( NULL, NULL, 'create_posts' );`
		$subs = $this->get_strings( 'subs', $context );

		// FIXME: check capabilities
		// `$can  = $this->role_can( $context ) ? 'exist' : 'do_not_allow';`

		return $this->filters( self::und( $context, 'subs' ),
			$subs,
			$context,
		);
	}

	protected function get_adminpage_default_sub( ?array $subs = NULL, ?string $context = NULL ): ?string
	{
		$context = $context ?? 'mainpage';
		$subs    = $subs    ?? $this->get_adminpage_subs( $context );

		return $this->filters( self::und( $context, 'default', 'sub' ),
			Core\Arraay::keyFirst( $subs ),
			$subs,
			$context,
		);
	}

	protected function _hook_menu_adminpage( ?string $context = NULL, ?int $position = NULL, ?string $capability = NULL ): string
	{
		$context = $context ?? 'mainpage';
		$slug    = $this->get_adminpage_url( FALSE, [], $context );
		$subs    = $this->get_adminpage_subs( $context );
		$default = $this->get_adminpage_default_sub( $subs, $context );
		$menu    = $this->get_string( 'menu_title', $context, 'adminpage', $this->key );

		$cap      = $capability ?? $this->role_can( $context ) ? 'exist' : 'do_not_allow';
		$position = $position   ?? empty( $this->positions[$context] ) ? 3 : $this->positions[$context];

		$this->screens[$context] = add_menu_page(
			$this->get_string( 'page_title', $context, 'adminpage', $this->key ),
			$menu,
			$cap,
			$slug,
			[ $this, 'render_menu_adminpage' ],
			Services\Icons::menu( $this->module->icon ),
			$position
		);

		foreach ( $subs as $sub => $submenu )
			add_submenu_page(
				$slug,
				sprintf(
					/* translators: `%1$s`: menu title, `%2$s`: sub-menu title */
					_x( '%1$s &lsaquo; %2$s', 'Module: Page Title', 'geditorial-admin' ),
					$submenu, // FIXME: only shows the first sub
					$menu
				),
				$submenu,
				$cap,
				$slug.( $sub === $default ? '' : '&sub='.$sub ),
				[ $this, 'render_menu_adminpage' ]
			);

		if ( $this->screens[$context] )
			add_action(
				self::dsh( 'load', $this->screens[$context] ),
				[ $this, 'load_menu_adminpage' ],
				10,
				0
			);

		return $slug;
	}

	public function load_menu_adminpage(): void
	{
		$context = $context ?? 'mainpage';

		$this->_load_menu_adminpage( $context );
		// `$this->enqueue_asset_js( [], $this->dotted( $context ), [ 'jquery', 'wp-api-request' ] );`
		// `$this->enqueue_asset_style( $context );`
	}

	protected function _load_menu_adminpage( ?string $context = NULL ): void
	{
		$context = $context ?? 'mainpage';
		$subs    = $this->get_adminpage_subs( $context );
		$default = $this->get_adminpage_default_sub( $subs, $context );
		$page    = self::req( 'page', NULL );
		$sub     = self::req( 'sub', $default );

		if ( $sub && $sub != $default )
			$GLOBALS['submenu_file'] = $this->get_adminpage_url( FALSE, [], $context ).'&sub='.$sub;

		$this->register_help_tabs( NULL, $context );
		$this->actions( 'load_adminpage',
			$page,
			$sub,
			$context
		);
	}

	public function render_menu_adminpage(): bool
	{
		$context = $context ?? 'mainpage';
		$action  = $action  ?? 'update';

		return $this->render_default_mainpage( $context, $action );
	}

	protected function render_default_mainpage( ?string $context = NULL, ?string $action = NULL ): bool
	{
		$context = $context ?? 'mainpage';
		$action  = $action  ?? 'update';
		$uri     = $this->get_adminpage_url( TRUE, [], $context );
		$subs    = $this->get_adminpage_subs( $context );
		$default = $this->get_adminpage_default_sub( $subs, $context );
		$content = [ $this, 'render_mainpage_content' ];
		$sub     = self::req( 'sub', $default );

		if ( $context && method_exists( $this, 'render_'.$context.'_content' ) )
			$content = [ $this, 'render_'.$context.'_content' ];

		gEditorial\Settings::wrapOpen( $this->key, $context, $this->get_string( 'page_title', $context, 'adminpage', '' ) );

			$this->render_adminpage_header_title( NULL, NULL, NULL, $context );
			$this->render_adminpage_header_nav( $uri, $sub, $subs, $context );
			$this->render_form_start( $uri, $sub, $action, $context, FALSE );
				$this->nonce_field( $context );
				call_user_func_array( $content, [ $sub, $uri, $context, $subs ] );
			$this->render_form_end( $uri, $sub, $action, $context, FALSE );
			$this->render_adminpage_signature( $uri, $sub, $subs, $context );

		gEditorial\Settings::wrapClose();

		return TRUE;
	}

	// DEFAULT CALLBACK
	protected function render_mainpage_content( ?string $sub, ?string $uri, ?string $context, ?array $subs ): bool
	{
		Core\HTML::desc( gEditorial()->na(), TRUE, '-empty' );

		return TRUE;
	}

	protected function _hook_submenu_adminpage( ?string $context = NULL, ?string $capability = NULL, string $parent_slug = '' ): string
	{
		$context = $context ?? 'subpage';
		$slug    = $this->get_adminpage_url( FALSE, [], $context );
		$cap     = $capability ?? $this->role_can( $context ) ? 'exist' : 'do_not_allow';
		$cb      = [ $this, 'render_submenu_adminpage' ];
		$load    = [ $this, 'load_submenu_adminpage' ];

		if ( $context && method_exists( $this, 'render_'.$context.'_adminpage' ) )
			$cb = [ $this, 'render_'.$context.'_adminpage' ];

		if ( $context && method_exists( $this, 'load_'.$context.'_adminpage' ) )
			$load = [ $this, 'load_'.$context.'_adminpage' ];

		$hook = add_submenu_page(
			$parent_slug, // or `index.php`
			$this->get_string( 'page_title', $context, 'adminpage', $this->key ),
			$this->get_string( 'menu_title', $context, 'adminpage', '' ),
			$cap,
			$slug,
			$cb
		);

		add_action( 'load-'.$hook, $load, 10, 0 );

		return $slug;
	}

	public function load_submenu_adminpage(): void
	{
		$context = $context ?? 'subpage';

		$this->_load_submenu_adminpage(  $context );
	}

	protected function _load_submenu_adminpage( ?string $context = NULL ): void
	{
		$context = $context ?? 'subpage';
		$page    = self::req( 'page', NULL );
		$sub     = self::req( 'sub', NULL );

		$this->register_help_tabs( NULL, $context );
		$this->actions( 'load_adminpage',
			$page,
			$sub,
			$context
		);

		if ( self::req( 'noheader' ) )
			self::define( 'QM_DISABLED', TRUE );
	}

	public function render_submenu_adminpage(): bool
	{
		return $this->render_default_mainpage( 'subpage', 'update' );
	}

	// Allows for filtering the page title
	// TODO: add compact mode to hide this on user screen setting
	protected function render_adminpage_header_title(
		?string $title = NULL,
		false|array|null $links = NULL,
		string|array|null $icon = NULL,
		?string $context = NULL
	): void {

		$context = $context ?? 'mainpage';

		if ( self::req( 'noheader' ) )
			return;

		$title = $title ?? $this->get_string( 'page_title', $context, 'adminpage', NULL );

		if ( ! $title )
			return;

		gEditorial\Settings::headerTitle(
			'adminpage',
			$title,
			$links ?? $this->get_adminpage_header_links( $context ),
			NULL,
			$icon ?? $this->module->icon,
		);
	}

	protected function render_adminpage_header_nav(
		string $uri = '',
		?string $sub = NULL,
		?array $subs = NULL,
		?string $context = NULL,
	): void {

		$context = $context ?? 'mainpage';

		if ( self::req( 'noheader' ) ) {

			echo '<div class="base-tabs-list -base nav-tab-base">';
			Core\HTML::tabNav( $sub, $subs );

		} else {

			echo $this->wrap_open( [ $context, $sub ?? '' ] );
			Core\HTML::headerNav( $uri, $sub, $subs );
		}
	}

	protected function render_adminpage_signature(
		string $uri = '',
		?string $sub = NULL,
		?array $subs = NULL,
		?string $context = NULL,
	): void {

		$context = $context ?? 'mainpage';

		if ( ! self::req( 'noheader' ) )
			$this->settings_signature( $context );

		echo '</div>';
	}

	// `array` for custom, `NULL` to settings, `FALSE` to disable
	protected function get_adminpage_header_links( ?string $context = NULL ): false|array
	{
		$context = $context ?? 'mainpage';

		if ( $action = $this->get_string( 'page_action', $context, 'adminpage', NULL ) )
			return [ $this->get_adminpage_url() => $action ];

		return FALSE;
	}
}
