<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait AdminPage
{

	protected function get_adminpage_subs( $context = 'mainpage' )
	{
		// $subs = $this->list_posttypes( NULL, NULL, 'create_posts' );
		$subs = $this->get_string( 'subs', $context, 'adminpage', [] );

		// FIXME: check capabilities
		// $can  = $this->role_can( $context ) ? 'exist' : 'do_not_allow';

		return $this->filters( $context.'_subs', $subs );
	}

	protected function get_adminpage_default_sub( $subs = NULL, $context = 'mainpage' )
	{
		if ( is_null( $subs ) )
			$subs = $this->get_adminpage_subs( $context );

		return $this->filters( $context.'_default_sub', Core\Arraay::keyFirst( $subs ) );
	}

	protected function _hook_menu_adminpage( $context = 'mainpage', $position = NULL, $capability = NULL )
	{
		$slug    = $this->get_adminpage_url( FALSE, [], $context );
		$subs    = $this->get_adminpage_subs( $context );
		$default = $this->get_adminpage_default_sub( $subs, $context );
		$cap     = $capability ?? $this->role_can( $context ) ? 'exist' : 'do_not_allow';
		$menu    = $this->get_string( 'menu_title', $context, 'adminpage', $this->key );

		if ( is_null( $position ) )
			$position = empty( $this->positions[$context] ) ? 3 : $this->positions[$context];

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
				/* translators: `%1$s`: menu title, `%2$s`: sub-menu title */
				sprintf( _x( '%1$s &lsaquo; %2$s', 'Module: Page Title', 'geditorial-admin' ), $submenu, $menu ), // FIXME: only shows the first sub
				$submenu,
				$cap,
				$slug.( $sub == $default ? '' : '&sub='.$sub ),
				[ $this, 'render_menu_adminpage' ]
			);

		if ( $this->screens[$context] )
			add_action(
				sprintf( 'load-%s', $this->screens[$context] ),
				[ $this, 'load_menu_adminpage' ],
				10,
				0
			);

		return $slug;
	}

	public function load_menu_adminpage( $context = 'mainpage' )
	{
		$this->_load_menu_adminpage( $context );
		// $this->enqueue_asset_js( [], $this->dotted( $context ), [ 'jquery', 'wp-api-request' ] );
		// $this->enqueue_asset_style( $context );
	}

	protected function _load_menu_adminpage( $context = 'mainpage' )
	{
		$subs    = $this->get_adminpage_subs( $context );
		$default = $this->get_adminpage_default_sub( $subs, $context );
		$page    = self::req( 'page', NULL );
		$sub     = self::req( 'sub', $default );

		if ( $sub && $sub != $default )
			$GLOBALS['submenu_file'] = $this->get_adminpage_url( FALSE, [], $context ).'&sub='.$sub;

		$this->register_help_tabs( NULL, $context );
		$this->actions( 'load_adminpage', $page, $sub, $context );
	}

	public function render_menu_adminpage()
	{
		$this->render_default_mainpage( 'mainpage', 'update' );
	}

	protected function render_default_mainpage( $context = 'mainpage', $action = 'update' )
	{
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
	}

	// DEFAULT CALLBACK
	protected function render_mainpage_content() // ( $sub = NULL, $uri = NULL, $context = '', $subs = [] )
	{
		Core\HTML::desc( gEditorial()->na(), TRUE, '-empty' );
	}

	protected function _hook_submenu_adminpage( $context = 'subpage', $capability = NULL, $parent_slug = '' )
	{
		$slug = $this->get_adminpage_url( FALSE, [], $context );
		$cap  = $capability ?? $this->role_can( $context ) ? 'exist' : 'do_not_allow';
		$cb   = [ $this, 'render_submenu_adminpage' ];
		$load = [ $this, 'load_submenu_adminpage' ];

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

	public function load_submenu_adminpage()
	{
		$this->_load_submenu_adminpage( 'subpage' );
	}

	protected function _load_submenu_adminpage( $context = 'subpage' )
	{
		$page = self::req( 'page', NULL );
		$sub  = self::req( 'sub', NULL );

		$this->register_help_tabs( NULL, $context );
		$this->actions( 'load_adminpage', $page, $sub, $context );

		if ( self::req( 'noheader' ) )
			self::define( 'QM_DISABLED', TRUE );
	}

	public function render_submenu_adminpage()
	{
		$this->render_default_mainpage( 'subpage', 'update' );
	}

	// Allows for filtering the page title
	// TODO: add compact mode to hide this on user screen setting
	protected function render_adminpage_header_title( $title = NULL, $links = NULL, $icon = NULL, $context = 'mainpage' )
	{
		if ( self::req( 'noheader' ) )
			return;

		if ( is_null( $title ) )
			$title = $this->get_string( 'page_title', $context, 'adminpage', NULL );

		if ( is_null( $links ) )
			$links = $this->get_adminpage_header_links( $context );

		if ( is_null( $icon ) )
			$icon = $this->module->icon;

		if ( $title )
			gEditorial\Settings::headerTitle( 'adminpage', $title, $links, NULL, $icon );
	}

	protected function render_adminpage_header_nav( $uri = '', $sub = NULL, $subs = NULL, $context = 'mainpage' )
	{
		if ( self::req( 'noheader' ) ) {
			echo '<div class="base-tabs-list -base nav-tab-base">';
			Core\HTML::tabNav( $sub, $subs );
		} else {
			echo $this->wrap_open( [ $context, $sub ?? '' ] );
			Core\HTML::headerNav( $uri, $sub, $subs );
		}
	}

	protected function render_adminpage_signature( $uri = '', $sub = NULL, $subs = NULL, $context = 'mainpage' )
	{
		if ( ! self::req( 'noheader' ) )
			$this->settings_signature( $context );

		echo '</div>';
	}

	// `array` for custom, `NULL` to settings, `FALSE` to disable
	protected function get_adminpage_header_links( $context = 'mainpage' )
	{
		if ( $action = $this->get_string( 'page_action', $context, 'adminpage', NULL ) )
			return [ $this->get_adminpage_url() => $action ];

		return FALSE;
	}
}
