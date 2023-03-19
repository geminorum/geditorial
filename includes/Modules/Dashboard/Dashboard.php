<?php namespace geminorum\gEditorial\Modules\Dashboard;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress\PostType;

class Dashboard extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'  => 'dashboard',
			'title' => _x( 'Dashboard', 'Modules: Dashboard', 'geditorial' ),
			'desc'  => _x( 'Front-end Editorial Dashboard', 'Modules: Dashboard', 'geditorial' ),
			'icon'  => 'dashboard',
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
					'exclude'     => Settings::getPageExcludes( [ 'front' ] ),
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
				/* translators: %s: HTML word */
				'before_content' => sprintf( _x( 'Adds %s before contents on dashboard homepage.', 'Settings: Setting Description', 'geditorial-dashboard' ), '<code>HTML</code>' ),
				/* translators: %s: HTML word */
				'after_content'  => sprintf( _x( 'Adds %s after contents on dashboard homepage.', 'Settings: Setting Description', 'geditorial-dashboard' ), '<code>HTML</code>' ),
			],
		];
	}

	public function default_buttons( $module = FALSE )
	{
		parent::default_buttons( $module );
		$this->register_button( $this->_get_page_permalink(), _x( 'Dashboard Page', 'Setting Button', 'geditorial-dashboard' ), 'link' );
	}

	protected function setup_disabled()
	{
		if ( ! $page = $this->get_setting( 'dashboard_page_id', 0 ) )
			return TRUE;

		if ( ! $post = PostType::getPost( $page ) )
			return TRUE;

		// only ASCII slugs!
		return $post->post_name !== urldecode( $post->post_name );
	}

	public function init()
	{
		parent::init();

		$this->_register_pages();

		if ( is_admin() ) {

			$this->filter( 'display_post_states', 2, 12 );

		} else {

			$this->action_self( 'dashbard_content', 1, 10, 'callback' );
			$this->action_self( 'content_page_home', 3 );

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
			'id'             => $name,
			'name'           => _x( 'Editorial: Dashboard', 'Widget Area', 'geditorial-dashboard' ),
			'description'    => _x( 'Widgets appear on Front-end Dashboard', 'Widget Area', 'geditorial-dashboard' ),
			'before_widget'  => '<section id="%1$s" class="widget %2$s">',
			'after_widget'   => '</section>',
			'before_title'   => '<h4 class="widgettitle">',
			'after_title'    => '</h4>',
			'before_sidebar' => '<div class="'.$this->classs( 'sidebar' ).'">',
			'after_sidebar'  => '</div>',
		], $name ) );
	}

	private function _get_page_permalink( $slug = FALSE )
	{
		$dashboard = get_permalink( $this->get_setting( 'dashboard_page_id', 0 ) );
		return $slug ? URL::trail( $dashboard ).$slug : URL::untrail( $dashboard );
	}

	private function _get_pages()
	{
		return $this->filters( 'pages', [] );
	}

	private function _register_pages()
	{
		$home = $this->dahboard_is_homepage();

		foreach ( $this->_get_pages() as $slug => $title )
			add_rewrite_endpoint( $slug, $home ? EP_ROOT | EP_PAGES : EP_PAGES, $this->classs( $slug ) );
	}

	private function dahboard_is_homepage()
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

	// FIXME: problems on home as dashboard
	public function template_include( $template )
	{
		if ( ! is_page( $this->get_setting( 'dashboard_page_id', 0 ) ) )
			return $template;

		nocache_headers();
		// WordPress::doNotCache();

		$this->actions( 'include' );

		$queried = FALSE;

		foreach ( $this->_get_pages() as $slug => $title ) {

			if ( FALSE === ( $queried = get_query_var( $this->classs( $slug ), FALSE ) ) )
				continue;

			$this->actions( sprintf( 'include_page_%s', $slug ), $queried, $slug, $title );
			break;
		}

		if ( FALSE === $queried )
			$this->actions( 'include_page_home', 'home', '', '' );

		$this->filter( 'the_title', 2, 9 );
		$this->filter( 'nav_menu_item_title', 4, 9 );
		$this->filter( 'the_content', 2, 9 );
		$this->filter_append( 'post_class', 'editorial-dashboard' );

		return $template;
	}

	// hides dashboard title for active theme
	public function the_title( $title, $post_id )
	{
		if ( $post_id == $this->get_setting( 'dashboard_page_id', 0 ) )
			return '';

		return $title;
	}

	// prevents dashboard page empty title on menu
	public function nav_menu_item_title( $title, $menu_item, $args, $depth )
	{
		return $menu_item->object_id == $this->get_setting( 'dashboard_page_id', 0 )
			? PostType::getPostTitle( $menu_item->object_id, $title, FALSE )
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

		return HTML::wrap( $before, 'geditorial-wrap-dashbard dashbard-before' )
			.$html
		.HTML::wrap( $after, 'geditorial-wrap-dashbard dashbard-after' );
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
		$queried = FALSE;

		foreach ( $this->_get_pages() as $slug => $title ) {

			if ( FALSE === ( $queried = get_query_var( $this->classs( $slug ), FALSE ) ) )
				continue;

			$this->actions( sprintf( 'content_page_%s', $slug ), $queried, $slug, $title );
			break;
		}

		if ( FALSE === $queried )
			$this->actions( 'content_page_home', 'home', '', '' );
	}

	public function content_page_home( $queried, $slug, $title )
	{
		$this->actions( 'content_page_home_before', $queried, $slug, $title );

		if ( $before = $this->get_setting( 'before_content' ) )
			echo $this->wrap( apply_shortcodes( $before ), '-before' );

		if ( $this->get_setting( 'widget_support' ) )
			dynamic_sidebar( $this->classs() );

		$this->actions( 'content_page_home_main', $queried, $slug, $title );

		if ( $after = $this->get_setting( 'after_content' ) )
			echo $this->wrap( apply_shortcodes( $after ), '-after' );

		$this->actions( 'content_page_home_after', $queried, $slug, $title );
	}

	public function navigation_general_items( $items )
	{
		foreach ( $this->_get_pages() as $slug => $title )
			$items[] = [
				// NOTE: must have `custom-` prefix to whitelist in gNetwork Navigation
				'slug' => sprintf( 'custom-dashboard-%s', $slug ),
				'link' => $this->_get_page_permalink( $slug ),
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
