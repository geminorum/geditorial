<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Tablelist;
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
			'title' => _x( 'Modified', 'Modules: Modified', 'geditorial' ),
			'desc'  => _x( 'Last Modifications', 'Modules: Modified', 'geditorial' ),
			'icon'  => 'update',
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_dashboard' => [
				'dashboard_widgets',
				'dashboard_statuses',
				'dashboard_authors',
				'dashboard_count',
			],
			'_frontend' => [
				[
					'field'       => 'last_published',
					'title'       => _x( 'Last Published', 'Setting Title', 'geditorial-modified' ),
					'description' => _x( 'Displays last published instead of modified date.', 'Setting Description', 'geditorial-modified' ),
				],
				'insert_content',
				[
					'field'       => 'insert_prefix',
					'type'        => 'text',
					'title'       => _x( 'Content Prefix', 'Setting Title', 'geditorial-modified' ),
					'description' => _x( 'Custom string before the modified time on the content.', 'Setting Description', 'geditorial-modified' ),
					'default'     => _x( 'Last modified on', 'Setting Default', 'geditorial-modified' ),
				],
				[
					'field'       => 'insert_format',
					'type'        => 'text',
					'title'       => _x( 'Insert Format', 'Setting Title', 'geditorial-modified' ),
					'description' => _x( 'Displays the date in this format on the content.', 'Setting Description', 'geditorial-modified' ),
					'default'     => get_option( 'date_format' ), // TODO: add new setting type to select format
				],
				'insert_priority',
				[
					'field'       => 'display_after',
					'type'        => 'select',
					'title'       => _x( 'Display After', 'Setting Title', 'geditorial-modified' ),
					'description' => _x( 'Skips displaying modified since the original content published time.', 'Setting Description', 'geditorial-modified' ),
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

		$this->filter( 'wp_nav_menu_items', 2 );

		if ( ! is_admin() )
			return;

		add_action( 'gnetwork_navigation_help_placeholders', [ $this, 'help_placeholders' ], 10, 2 );
	}

	public function template_redirect()
	{
		if ( ! is_singular( $this->posttypes() ) )
			return;

		if ( $this->hook_insert_content( 30 ) )
			$this->enqueue_styles(); // widget must add this itself!
	}

	protected function dashboard_widgets()
	{
		wp_add_dashboard_widget( $this->classs( 'summary' ),
			_x( 'Latest Changes', 'Dashboard Widget Title', 'geditorial-modified' ),
			[ $this, 'dashboard_widget_summary' ]
		);
	}

	public function dashboard_widget_summary( $object, $box )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		$args = [
			'orderby'     => 'modified',
			'post_type'   => $this->posttypes(),
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

		$columns = [ 'title' => Tablelist::columnPostTitleSummary() ];

		if ( $this->get_setting( 'dashboard_statuses', FALSE ) )
			$columns['status'] = Tablelist::columnPostStatusSummary();

		if ( $this->get_setting( 'dashboard_authors', FALSE ) )
			$columns['author'] = Tablelist::columnPostAuthorSummary();

		$columns['modified'] = Tablelist::columnPostDateModified();

		HTML::tableList( $columns, $query->query( $args ), [
			'empty' => $this->get_posttype_label( 'post', 'not_found' ),
		] );
	}

	public function insert_content( $content )
	{
		if ( ! $this->is_content_insert( FALSE, FALSE ) )
			return;

		if ( ! $modified = $this->get_post_modified() )
			return;

		echo $this->wrap( '<small>'.$modified.'</small>', '-'.$this->get_setting( 'insert_content', 'none' ) );

		Scripts::enqueueTimeAgo();
	}

	// `Posted on 22nd May 2014 This post was last updated on 23rd April 2016`
	public function post_modified_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'       => get_queried_object_id(),
			'format'   => Datetime::dateFormats( 'dateonly' ),
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
			$title = Scripts::enqueueTimeAgo()
				? FALSE
				: Datetime::humanTimeDiffRound( $local, $args['round'] );
		else
			$title = $args['title'];

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
					Datetime::humanTimeDiffRound( $local, FALSE ) );

		return FALSE;
	}

	// just put {SITE_LAST_MODIFIED} on a menu item text!
	public function wp_nav_menu_items( $items, $args )
	{
		if ( ! Text::has( $items, '{SITE_LAST_MODIFIED}' ) )
			return $items;

		return preg_replace( '%{SITE_LAST_MODIFIED}%', $this->get_site_modified() ?: '', $items );
	}

	public function help_placeholders( $before, $after )
	{
		echo $before.'<code>{SITE_LAST_MODIFIED}</code>'.$after;
	}

	public function site_modified_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'format'   => Datetime::dateFormats( 'dateonly' ),
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

		if ( FALSE === ( $site = $this->get_site_modified( TRUE ) ) )
			return NULL;

		$gmt   = strtotime( $site[1] );
		$local = strtotime( $site[0] );

		if ( 'timeago' == $args['title'] )
			$title = Scripts::enqueueTimeAgo()
				? FALSE
				: Datetime::humanTimeDiffRound( $local, $args['round'] );
		else
			$title = $args['title'];

		$html = Date::htmlDateTime( $local, $gmt, $args['format'], $title );

		if ( $args['link'] )
			$html = HTML::link( $html, $args['link'] );

		return ShortCode::wrap( $html, 'site-modified', $args, FALSE );
	}

	public function get_site_modified( $format = NULL, $posttypes = NULL, $published = NULL )
	{
		global $wpdb;

		if ( is_null( $format ) )
			$format = get_option( 'date_format' );

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		else if ( FALSE === $posttypes )
			$posttypes = [];

		else if ( ! is_array( $posttypes ) )
			$posttypes = [ $posttypes ];

		if ( is_null( $published ) )
			$published = $this->get_setting( 'last_published', FALSE );

		if ( $published ) {
			$date = 'post_date';
			$gmt  = 'post_date_gmt';
		} else {
			$date = 'post_modified';
			$gmt  = 'post_modified_gmt';
		}

		if ( count( $posttypes ) ) {

			$query = "
				SELECT {$date}, {$gmt}
				FROM {$wpdb->posts}
				WHERE post_status = 'publish'
				AND post_type IN ( '".implode( "', '", esc_sql( $posttypes ) )."' )
				ORDER BY {$gmt} DESC
				LIMIT 1
			";

		} else {

			$query = "
				SELECT {$date}, {$gmt}
				FROM {$wpdb->posts}
				WHERE post_status = 'publish'
				ORDER BY {$gmt} DESC
				LIMIT 1
			";
		}

		if ( ! $results = $wpdb->get_results( $query ) )
			return FALSE;

		if ( FALSE === $format )
			return $results[0]->{$date};

		if ( TRUE === $format )
			return [ $results[0]->{$date}, $results[0]->{$gmt} ];

		return date_i18n( $format, strtotime( $results[0]->{$date} ), FALSE );
	}
}
