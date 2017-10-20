<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Core\Date;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\WordPress;

class Modified extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'  => 'modified',
			'title' => _x( 'Modified', 'Modules: Modified', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Last Modifications', 'Modules: Modified', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'update',
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_dashboard' => [
				'dashboard_widgets',
				'dashboard_authors',
				'dashboard_count',
			],
			'_frontend' => [
				'insert_content',
				[
					'field'       => 'insert_prefix',
					'type'        => 'text',
					'title'       => _x( 'Content Prefix', 'Modules: Modified: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'String before the modified time on the content', 'Modules: Modified: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => _x( 'Last modified on', 'Modules: Modified: Setting Default', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'insert_format',
					'type'        => 'text',
					'title'       => _x( 'Insert Format', 'Modules: Modified: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays last modified in this format on the content', 'Modules: Modified: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => get_option( 'date_format' ), // TODO: add new setting type to select format
				],
				'insert_priority',
				[
					'field'       => 'display_after',
					'type'        => 'select',
					'title'       => _x( 'Display After', 'Modules: Modified: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Skip displaying modified time since original content published', 'Modules: Modified: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '60',
					'values'      => Settings::minutesOptions(),
				],
			],
			'_supports' => [
				'shortcode_support',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'post_modified_shortcode' => 'post-modified',
			'site_modified_shortcode' => 'site-modified',
		];
	}

	public function init()
	{
		parent::init();

		$this->register_shortcode( 'post_modified_shortcode' );
		$this->register_shortcode( 'site_modified_shortcode' );

		if ( is_admin() )
			return;

		$insert = $this->get_setting( 'insert_content', 'none' );

		if ( 'none' != $insert ) {

			add_action( 'gnetwork_themes_content_'.$insert, [ $this, 'insert_content' ],
				$this->get_setting( 'insert_priority', 30 ) );

			$this->enqueue_styles();
		}

		$this->filter( 'wp_nav_menu_items', 2 );
	}

	protected function dashboard_widgets()
	{
		wp_add_dashboard_widget( $this->classs(),
			_x( 'Latest Changes', 'Modules: Modified: Dashboard Widget Title', GEDITORIAL_TEXTDOMAIN ),
			[ $this, 'dashboard_widget_summary' ]
		);
	}

	public function dashboard_widget_summary()
	{
		$args = [
			'orderby'     => 'modified',
			'post_type'   => $this->post_types(),
			'post_status' => [ 'publish', 'future', 'draft', 'pending' ],

			'posts_per_page'      => $this->get_setting( 'dashboard_count', 10 ),
			'ignore_sticky_posts' => TRUE,
			'suppress_filters'    => TRUE,
			'no_found_rows'       => TRUE,

			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		];

		$query = new \WP_Query;

		$columns = [ 'title' => Helper::tableColumnPostTitleSummary() ];

		if ( $this->get_setting( 'dashboard_authors', FALSE ) )
			$columns['author'] = Helper::tableColumnPostAuthorSummary();

		$columns['modified'] = Helper::tableColumnPostDateModified();

		HTML::tableList( $columns, $query->query( $args ), [
			'empty' => Helper::tableArgEmptyPosts( FALSE ),
		] );
	}

	public function insert_content( $content )
	{
		if ( ! $this->is_content_insert( NULL, FALSE ) )
			return;

		if ( ! $modified = $this->get_post_modified() )
			return;

		echo $this->wrap( '<small>'.$modified.'</small>', '-'.$this->get_setting( 'insert_content', 'none' ) );

		Helper::enqueueTimeAgo();
	}

	// `Posted on 22nd May 2014 This post was last updated on 23rd April 2016`
	public function post_modified_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'       => get_queried_object_id(),
			'format'   => Helper::dateFormats( 'dateonly' ),
			'title'    => 'timeago',
			'round'    => FALSE,
			'link'     => FALSE,
			'context'  => NULL,
			'wrap'     => TRUE,
			'before'   => '',
			'after'    => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $post = get_post( $args['id'] ) )
			return NULL;

		$gmt   = strtotime( $post->post_modified_gmt );
		$local = strtotime( $post->post_modified );

		if ( 'timeago' == $args['title'] )
			$title = Helper::enqueueTimeAgo()
				? FALSE
				: Helper::humanTimeDiffRound( $local, $args['round'] );
		else
			$title = esc_attr( $args['title'] );

		$html = Date::htmlDateTime( $local, $gmt, $args['format'], $title );

		if ( $args['link'] )
			$html = HTML::link( $html, $args['link'] );

		return ShortCode::wrap( $html, 'post-modified', $args, FALSE );
	}

	public function get_post_modified( $format = NULL, $post = NULL )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		$gmt     = strtotime( $post->post_modified_gmt );
		$local   = strtotime( $post->post_modified );
		$publish = strtotime( $post->post_date_gmt );

		if ( is_null( $format ) )
			$format = $this->get_setting( 'insert_format', get_option( 'date_format' ) );

		$minutes = $this->get_setting( 'display_after', '60' );
		$prefix  = $this->get_setting( 'insert_prefix', '' );

		if ( $gmt >= $publish + ( absint( $minutes ) * MINUTE_IN_SECONDS ) )
			return $prefix.' '.Date::htmlDateTime( $local, $gmt, $format,
					Helper::humanTimeDiffRound( $local, FALSE ) );

		return FALSE;
	}

	// just put {SITE_LAST_MODIFIED} on a menu item text!
	public function wp_nav_menu_items( $items, $args )
	{
		if ( ! Text::has( $items, '{SITE_LAST_MODIFIED}' ) )
			return $items;

		if ( ! isset( $this->site_modified ) )
			$this->site_modified = $this->get_site_modified();

		return preg_replace( '%{SITE_LAST_MODIFIED}%', $this->site_modified, $items );
	}

	public function site_modified_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'format'   => Helper::dateFormats( 'dateonly' ),
			'title'    => 'timeago',
			'round'    => FALSE,
			'link'     => FALSE,
			'context'  => NULL,
			'wrap'     => TRUE,
			'before'   => '',
			'after'    => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$site  = $this->get_site_modified( TRUE );
		$gmt   = strtotime( $site[1] );
		$local = strtotime( $site[0] );

		if ( 'timeago' == $args['title'] )
			$title = Helper::enqueueTimeAgo()
				? FALSE
				: Helper::humanTimeDiffRound( $local, $args['round'] );
		else
			$title = esc_attr( $args['title'] );

		$html = Date::htmlDateTime( $local, $gmt, $args['format'], $title );

		if ( $args['link'] )
			$html = HTML::link( $html, $args['link'] );

		return ShortCode::wrap( $html, 'site-modified', $args, FALSE );
	}

	public function get_site_modified( $format = NULL, $post_types = NULL )
	{
		global $wpdb;

		if ( is_null( $format ) )
			$format = get_option( 'date_format' );

		if ( is_null( $post_types ) )
			$post_types = $this->post_types();

		else if ( FALSE === $post_types )
			$post_types = [];

		else if ( ! is_array( $post_types ) )
			$post_types = [ $post_types ];

		if ( count( $post_types ) ) {

			$query = "
				SELECT post_modified, post_modified_gmt
				FROM {$wpdb->posts}
				WHERE post_status = 'publish'
				AND post_type IN ( '".join( "', '", esc_sql( $post_types ) )."' )
				ORDER BY post_modified_gmt DESC
				LIMIT 1
			";

		} else {

			$query = "
				SELECT post_modified, post_modified_gmt
				FROM {$wpdb->posts}
				WHERE post_status = 'publish'
				ORDER BY post_modified_gmt DESC
				LIMIT 1
			";
		}

		$results = $wpdb->get_results( $query );

		if ( FALSE === $format )
			return $results[0]->post_modified;

		if ( TRUE === $format )
			return [ $results[0]->post_modified, $results[0]->post_modified_gmt ];

		return date_i18n( $format, strtotime( $results[0]->post_modified ), FALSE );
	}
}
