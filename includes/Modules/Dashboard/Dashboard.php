<?php namespace geminorum\gEditorial\Modules\Dashboard;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Dashboard extends gEditorial\Module
{

	protected $priority_template_redirect = 9; // NOTE: before `redirect_canonical`

	public static function module()
	{
		return [
			'name'   => 'dashboard',
			'title'  => _x( 'Dashboard', 'Modules: Dashboard', 'geditorial-admin' ),
			'desc'   => _x( 'Front-end Editorial Dashboard', 'Modules: Dashboard', 'geditorial-admin' ),
			'icon'   => 'dashboard',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'dashboard_page_id',
					'type'        => 'page',
					'title'       => _x( 'Dashboard Page', 'Setting Title', 'geditorial-dashboard' ),
					'description' => _x( 'Displays the selected page as dashboard page.', 'Setting Description', 'geditorial-dashboard' ),
					'default'     => '0',
					'exclude'     => gEditorial\Settings::getPageExcludes( [ 'front' ] ),
				],
				[
					'field'       => 'dashboard_navmenu',
					'type'        => 'navmenu',
					'title'       => _x( 'Navigation Menu', 'Setting Title', 'geditorial-dashboard' ),
					'description' => _x( 'Displays the selected nav-menu as dashboard navigation.', 'Setting Description', 'geditorial-dashboard' ),
				],
				'widget_support',
			],
			'_frontend' => [
				'before_content' => sprintf(
					/* translators: `%s`: HTML word */
					_x( 'Adds %s before contents on dashboard homepage.', 'Settings: Setting Description', 'geditorial-dashboard' ),
					Core\HTML::code( 'HTML' )
				),
				'after_content' => sprintf(
					/* translators: `%s`: HTML word */
					_x( 'Adds %s after contents on dashboard homepage.', 'Settings: Setting Description', 'geditorial-dashboard' ),
					Core\HTML::code( 'HTML' )
				),
			],
		];
	}

	protected function register_settings_extra_buttons( $module )
	{
		$this->register_button(
			$this->_get_dashboard_permalink(),
			_x( 'Dashboard Page', 'Setting Button', 'geditorial-dashboard' ),
			'link'
		);
	}

	public function setup_disabled()
	{
		if ( ! $page = $this->get_setting( 'dashboard_page_id', 0 ) )
			return TRUE;

		if ( ! $post = WordPress\Post::get( $page ) )
			return TRUE;

		// only ASCII slugs!
		return $post->post_name !== urldecode( $post->post_name );
	}

	public function init()
	{
		parent::init();

		$this->_init_rewrite_rules();

		if ( is_admin() ) {

			// FIXME: move this to `current_screen` for pages
			$this->filter( 'display_post_states', 2, 12 );

		} else {

			$this->action_self( 'dashbard_content', 1, 10, 'callback' );
			$this->action_self( 'content_page_home' );

			if ( $this->get_setting( 'dashboard_navmenu' ) )
				$this->action_self( 'dashbard_before', 1, 1, 'navmenu' );

			$this->filter( 'wp_sitemaps_posts_query_args', 2, 12 );
			$this->filter( 'wpseo_exclude_from_sitemap_by_post_ids', 1, 12 );
		}

		$this->filter( 'navigation_general_items', 1, 10, FALSE, 'gnetwork' );
	}

	public function widgets_init()
	{
		$name = $this->classs();

		register_sidebar( $this->filters( 'sidebar_args', [
			'id'   => $name,
			'name' => sprintf(
				/* translators: `%s`: system string */
				_x( '%s: Dashboard', 'Widget Area', 'geditorial-dashboard' ),
				gEditorial\Plugin::system()
			),
			'description'    => _x( 'Widgets appear on Front-end Dashboard', 'Widget Area', 'geditorial-dashboard' ),
			'before_widget'  => '<section id="%1$s" class="widget %2$s">',
			'after_widget'   => '</section>',
			'before_title'   => '<h4 class="widgettitle">',
			'after_title'    => '</h4>',
			'before_sidebar' => '<div class="'.$this->classs( 'sidebar' ).'">',
			'after_sidebar'  => '</div>',
		], $name ) );
	}

	// add_rewrite_rule( 'api/items/([0-9]+)/?', 'index.php?api_item_id=$matches[1]', 'top' );
	// add_rewrite_rule( '^dashboard/([^/]*)/?', 'index.php?city=$matches[1]','top' );
	private function _init_rewrite_rules()
	{
		$key  = $this->classs();
		$slug = $this->_get_dashboard_slug();
		$home = $this->_dahboard_is_homepage();

		add_rewrite_tag( "%{$key}%", '([^&]+)' );
		// add_rewrite_tag( "%{$key}%", '([0-9]+)' );

		foreach ( $this->_get_pages() as $page => $title )
			add_rewrite_rule( ( $home ? ( '^'.$page.'/?$' ) : ( '^'.$slug.'/'.$page.'/?$' ) ),
				sprintf( 'index.php?pagename=%s&%s=%s', $slug, $key, $page ), 'top' );
	}

	private function _get_dashboard_slug( $page = FALSE )
	{
		if ( ! $id = $this->get_setting( 'dashboard_page_id', 0 ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $id ) )
			return FALSE;

		return $page ? ( $post->post_name.'/'.$page ) : $post->post_name;
	}

	private function _get_dashboard_permalink( $page = FALSE )
	{
		$dashboard = get_permalink( $this->get_setting( 'dashboard_page_id', 0 ) );
		return $page ? Core\URL::trail( $dashboard ).$page : Core\URL::untrail( $dashboard );
	}

	private function _get_pages()
	{
		return $this->filters( 'pages', [] );
	}

	private function _dahboard_is_homepage()
	{
		if ( 'page' === get_option( 'show_on_front' )
			&& $this->get_setting( 'dashboard_page_id', 0 ) == get_option( 'page_on_front' ) )
				return TRUE;

		return FALSE;
	}

	public function display_post_states( $states, $post )
	{
		if ( 'page' !== $post->post_type )
			return $states;

		if ( $post->ID === (int) $this->get_setting( 'dashboard_page_id', 0 ) )
			$states[$this->key] = _x( 'Dashboard', 'Page-State', 'geditorial-dashboard' );

		return $states;
	}

	private function _init_hooks()
	{
		WordPress\Theme::resetQueryExtras( [
			'disable_robots' => TRUE,
			'disable_cache'  => TRUE,
		] );

		$page = get_query_var( $this->classs(), FALSE );

		if ( $page && ( ! in_array( $page, array_keys( $this->_get_pages() ), TRUE ) ) )
			return FALSE;

		$this->actions( 'include', $page );
		$this->actions( sprintf( 'include_page_%s', $page ), $page );

		if ( FALSE === $page )
			$this->actions( 'include_page_home', 'home', '', '' );

		$this->filter( 'the_title', 2, 9 );
		$this->filter( 'nav_menu_item_title', 4, 9 );
		$this->filter( 'the_content', 2, 9 );
		$this->filter_append( 'post_class', 'editorial-dashboard' );

		return TRUE;
	}

	public function template_redirect()
	{
		if ( get_query_var( $this->classs(), FALSE ) )
			remove_action( 'template_redirect', 'redirect_canonical' );
	}

	public function template_include( $template )
	{
		if ( $this->_dahboard_is_homepage() ) {

			if ( ! is_front_page() )
				return $template;

			if ( ! $this->_init_hooks() )
				return get_404_template();

			return get_page_template();

		} else {

			if ( ! is_page( $this->get_setting( 'dashboard_page_id', 0 ) ) )
				return $template;

			if ( ! $this->_init_hooks() )
				return get_404_template();

			return $template;
		}
	}

	// Hides dashboard title for active theme
	public function the_title( $title, $post_id )
	{
		if ( $post_id == $this->get_setting( 'dashboard_page_id', 0 ) )
			return '';

		return $title;
	}

	// Prevents dashboard page empty title on menu
	public function nav_menu_item_title( $title, $menu_item, $args, $depth )
	{
		return $menu_item->object_id == $this->get_setting( 'dashboard_page_id', 0 )
			? WordPress\Post::title( $menu_item->object_id, $title, FALSE )
			: $title;
	}

	public function the_content( $content )
	{
		$html = $before = $after = '';

		if ( has_action( $this->hook( 'dashbard_before' ) ) ) {
			ob_start();
				$this->actions( 'dashbard_before', $content );
			$before = trim( ob_get_clean() );
		}

		if ( has_action( $this->hook( 'dashbard_content' ) ) ) {
			ob_start();
				$this->actions( 'dashbard_content', $content );
			$html = trim( ob_get_clean() );
		}

		if ( has_action( $this->hook( 'dashbard_after' ) ) ) {
			ob_start();
				$this->actions( 'dashbard_after', $content );
			$after = trim( ob_get_clean() );
		}

		return Core\HTML::wrap( $before, 'geditorial-wrap-dashbard dashbard-before' )
			.$html
		.Core\HTML::wrap( $after, 'geditorial-wrap-dashbard dashbard-after' );
	}

	public function dashbard_before_navmenu()
	{
		$term_id  = $this->get_setting( 'dashboard_navmenu', 0 );
		$nav_menu = wp_get_nav_menu_object( $term_id );

		wp_nav_menu( $this->filters( 'navmenu_args', [
			'fallback_cb' => '',
			'menu'        => $nav_menu,
			'container'   => 'nav',
			'menu_class'  => 'nav', // BS4/BS5
		], $nav_menu, $term_id ) );
	}

	public function dashbard_content_callback()
	{
		if ( $page = get_query_var( $this->classs(), FALSE ) )
			$this->actions( sprintf( 'content_page_%s', $page ), $page );

		else
			$this->actions( 'content_page_home', 'home' );
	}

	public function content_page_home( $page )
	{
		$this->actions( 'content_page_home_before', $page );

		if ( $before = $this->get_setting( 'before_content' ) )
			echo $this->wrap( WordPress\ShortCode::apply( $before ), '-before' );

		if ( $this->get_setting( 'widget_support' ) )
			dynamic_sidebar( $this->classs() );

		$this->actions( 'content_page_home_main', $page );

		if ( $after = $this->get_setting( 'after_content' ) )
			echo $this->wrap( WordPress\ShortCode::apply( $after ), '-after' );

		$this->actions( 'content_page_home_after', $page );
	}

	public function navigation_general_items( $items )
	{
		foreach ( $this->_get_pages() as $page => $title )
			$items[] = [
				// NOTE: must have `custom-` prefix to whitelist in gNetwork Navigation
				'slug' => sprintf( 'custom-dashboard-%s', $page ),
				'link' => $this->_get_dashboard_permalink( $page ),
				'name' => $title,
			];

		return $items;
	}

	// @REF: https://perishablepress.com/customize-wordpress-sitemaps/
	public function wp_sitemaps_posts_query_args( $args, $post_type )
	{
		if ( 'page' !== $post_type )
			return $args;

		if ( ! array_key_exists( 'post__not_in', $args ) )
			$args['post__not_in'] = [];

		$args['post__not_in'][] = (int) $this->get_setting( 'dashboard_page_id', 0 );

		return $args;
	}

	// @REF: https://preventdirectaccess.com/5-ways-remove-pages-from-sitemap/
	public function wpseo_exclude_from_sitemap_by_post_ids( $excluded_posts_ids )
	{
		$excluded_posts_ids[] = (int) $this->get_setting( 'dashboard_page_id', 0 );

		return $excluded_posts_ids;
	}
}
