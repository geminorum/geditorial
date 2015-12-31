<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

use SitemapPHP\Sitemap;

class gEditorialSitemap extends gEditorialModuleCore
{

	public static function module()
	{
		if ( ! self::isDev() )
			return FALSE;

		return array(
			'name'     => 'sitemap',
			'title'    => _x( 'Sitemap', 'Sitemap Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'XML Sitemap for Search Engines', 'Sitemap Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon' => 'networking',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'posttypes_option'  => 'posttypes_option',
			'taxonomies_option' => 'taxonomies_option',
		);
	}

	public function setup( $partials = array() )
	{
		parent::setup();
	}

	public function init()
	{
		do_action( 'geditorial_sitemap_init', $this->module );
		$this->do_globals();
	}

	public function admin_init()
	{
		// if ( $this->get_setting( 'group_taxonomies', FALSE ) )
	}

	public function get_path( $folder = 'sitemaps' )
	{
		if ( $folder )
			$path = '/'.path_join( $folder, get_current_blog_id() ).'/';
		else
			$path = '/'.get_current_blog_id().'/';

		if ( wp_mkdir_p( WP_CONTENT_DIR.$path ) )
			return array(
				'dir' => WP_CONTENT_DIR.$path,
				'url' => WP_CONTENT_URL.$path,
			);

		return FALSE;
	}

	public function create_sitemap()
	{
		if ( $path = $this->get_path() ) {

			$sitemap = new Sitemap( home_url() );
			$sitemap->setPath( $path['dir'] );
			$sitemap->setFilename('sitemap');

			foreach ( $this->post_types() as $post_type ) {
				$post_type_obj = get_post_type_object( $post_type );

				if ( ! $post_type_obj->public )
					continue;

				// get perm structure
				// query db
				// add item
			}

			$sitemap->addItem('/', '1.0', 'daily', 'Today');
			$sitemap->addItem('/about', '0.8', 'monthly', 'Jun 25');
			$sitemap->addItem('/contact', '0.6', 'yearly', '14-12-2009');
			$sitemap->addItem('/otherpage');

			$sitemap->createSitemapIndex( $path['url'], 'Today');
			return TRUE;
		}

		return FALSE;
	}
}
