<?php namespace geminorum\gEditorial\Modules\Modified;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Modified extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\ViewEngines;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'modified',
			'title'    => _x( 'Modified', 'Modules: Modified', 'geditorial-admin' ),
			'desc'     => _x( 'Last Modification of Contents', 'Modules: Modified', 'geditorial-admin' ),
			'icon'     => 'update',
			'access'   => 'beta',
			'keywords' => [
				'shortcodemodule',
			],
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
					'values'      => gEditorial\Settings::minutesOptions(),
				],
			],
			'_supports' => [
				'shortcode_support',
			],
			'_constants' => [
				[
					'field'       => 'post_modified_shortcode_constant',
					'type'        => 'text',
					'title'       => _x( 'Post Shortcode Tag', 'Setting: Setting Title', 'geditorial-modified' ),
					'description' => _x( 'Customizes the post modified short-code tag. Leave blank for default.', 'Setting: Setting Description', 'geditorial-modified' ),
					'after'       => gEditorial\Settings::fieldAfterShortCodeConstant(),
					'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
					'field_class' => [ 'medium-text', 'code-text' ],
					'placeholder' => 'post-modified',
				],
				[
					'field'       => 'site_modified_shortcode_constant',
					'type'        => 'text',
					'title'       => _x( 'Site Shortcode Tag', 'Setting: Setting Title', 'geditorial-modified' ),
					'description' => _x( 'Customizes the site modified short-code tag. Leave blank for default.', 'Setting: Setting Description', 'geditorial-modified' ),
					'after'       => gEditorial\Settings::fieldAfterShortCodeConstant(),
					'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
					'field_class' => [ 'medium-text', 'code-text' ],
					'placeholder' => 'site-modified',
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'entry_modified_shortcode' => 'entry-modified',
			'post_modified_shortcode'  => 'post-modified',
			'site_modified_shortcode'  => 'site-modified',
		];
	}

	public function init()
	{
		parent::init();

		$this->register_shortcode( 'entry_modified_shortcode' );
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

	public function dashboard_widgets()
	{
		$this->add_dashboard_widget( 'latest-summary', _x( 'Latest Changes', 'Dashboard Widget Title', 'geditorial-modified' ) );
	}

	public function render_widget_latest_summary( $object, $box )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		$type = $this->posttypes();
		$args = [
			'orderby'     => 'modified',
			'post_type'   => $type,
			'post_status' => WordPress\Status::acceptable( $type ),

			'posts_per_page'      => $this->get_setting( 'dashboard_count', 10 ),
			'ignore_sticky_posts' => TRUE,
			'suppress_filters'    => TRUE,
			'no_found_rows'       => TRUE,

			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		];

		$query = new \WP_Query();

		$columns = [ 'title' => gEditorial\Tablelist::columnPostTitleSummary() ];

		if ( $this->get_setting( 'dashboard_statuses', FALSE ) )
			$columns['status'] = gEditorial\Tablelist::columnPostStatusSummary();

		if ( $this->get_setting( 'dashboard_authors', FALSE ) )
			$columns['author'] = gEditorial\Tablelist::columnPostAuthorSummary();

		$columns['modified'] = gEditorial\Tablelist::columnPostDateModified();

		Core\HTML::tableList( $columns, $query->query( $args ), [
			'empty' => Services\CustomPostType::getLabel( 'post', 'not_found' ),
		] );
	}

	public function insert_content( $content )
	{
		if ( ! $this->is_content_insert( FALSE, FALSE ) )
			return;

		if ( ! $modified = $this->get_post_modified() )
			return;

		echo $this->wrap( '<small>'.$modified.'</small>', '-'.$this->get_setting( 'insert_content', 'none' ) );

		gEditorial\Scripts::enqueueTimeAgo();
	}

	public function entry_modified_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return $this->modified_data_summary( array_merge( [
			'echo' => FALSE,
		], (array) $atts ) );
	}

	// `Posted on 22nd May 2014 This post was last updated on 23rd April 2016`
	public function post_modified_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'       => get_queried_object_id(),
			'format'   => gEditorial\Datetime::dateFormats( 'dateonly' ),
			'title'    => 'timeago',
			'round'    => FALSE,
			'link'     => FALSE,
			'context'  => NULL,
			'wrap'     => TRUE,
			'before'   => '',
			'after'    => '',
		], $atts, $tag ?: $this->constant( 'post_modified_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $post = WordPress\Post::get( $args['id'] ) )
			return $content;

		$gmt   = strtotime( $post->post_modified_gmt );
		$local = strtotime( $post->post_modified );

		if ( 'timeago' == $args['title'] )
			$title = gEditorial\Scripts::enqueueTimeAgo()
				? FALSE
				: gEditorial\Datetime::humanTimeDiffRound( $local, $args['round'] );
		else
			$title = $args['title'];

		$html = Core\Date::htmlDateTime( $local, $gmt, $args['format'], $title );

		if ( $args['link'] )
			$html = Core\HTML::link( $html, $args['link'] );

		return gEditorial\ShortCode::wrap( $html, 'post-modified', $args, FALSE );
	}

	public function get_post_modified( $format = NULL, $post = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$gmt     = strtotime( $post->post_modified_gmt );
		$local   = strtotime( $post->post_modified );
		$publish = strtotime( $post->post_date_gmt );

		if ( is_null( $format ) )
			$format = $this->get_setting( 'insert_format', get_option( 'date_format' ) );

		$minutes = $this->get_setting( 'display_after', '60' );
		$prefix  = $this->get_setting( 'insert_prefix', '' );

		if ( $gmt >= $publish + ( absint( $minutes ) * MINUTE_IN_SECONDS ) )
			return $prefix.' '.Core\Date::htmlDateTime( $local, $gmt, $format,
				gEditorial\Datetime::humanTimeDiffRound( $local, FALSE ) );

		return FALSE;
	}

	// just put {SITE_LAST_MODIFIED} on a menu item text!
	public function wp_nav_menu_items( $items, $args )
	{
		if ( ! Core\Text::has( $items, '{SITE_LAST_MODIFIED}' ) )
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
			'format'   => gEditorial\Datetime::dateFormats( 'dateonly' ),
			'title'    => 'timeago',
			'round'    => FALSE,
			'link'     => FALSE,
			'context'  => NULL,
			'wrap'     => TRUE,
			'before'   => '',
			'after'    => '',
		], $atts, $tag ?: $this->constant( 'site_modified_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( FALSE === ( $site = $this->get_site_modified( TRUE ) ) )
			return NULL;

		$gmt   = strtotime( $site[1] );
		$local = strtotime( $site[0] );

		if ( 'timeago' == $args['title'] )
			$title = gEditorial\Scripts::enqueueTimeAgo()
				? FALSE
				: gEditorial\Datetime::humanTimeDiffRound( $local, $args['round'] );
		else
			$title = $args['title'];

		$html = Core\Date::htmlDateTime( $local, $gmt, $args['format'], $title );

		if ( $args['link'] )
			$html = Core\HTML::link( $html, $args['link'] );

		return gEditorial\ShortCode::wrap( $html, 'site-modified', $args, FALSE );
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

		return Core\Date::get( $format, strtotime( $results[0]->{$date} ) );
	}

	// @source https://make.wordpress.org/core/handbook/tutorials/installing-wordpress-locally/
	// @template https://codepen.io/geminorum/pen/yyJjaaP
	public function modified_data_summary( $atts = [], $post = NULL )
	{
		$args = $this->filters( 'data_summary_args', self::atts( [
			'id'       => $post,
			'fields'   => NULL,
			'context'  => NULL,
			'template' => NULL,
			'default'  => FALSE,
			'before'   => '',
			'after'    => '',
			'echo'     => TRUE,
			'render'   => NULL,
		], $atts ), $post );

		if ( ! $post = WordPress\Post::get( $args['id'] ) )
			return $args['default'];

		if ( ! $data = $this->modified_get_data_for_post( $post, $args['context'] ?? 'summary' ) )
			return $args['default'];

		if ( ! method_exists( $this, 'viewengine__render' ) ) {
			$this->log( 'CRITICAL', 'VIEW ENGINE NOT AVAILABLE' );
			return $args['default'];
		}

		if ( ! $view = $this->viewengine__view_by_template( $args['template'] ?? 'data-summary', 'post' ) )
			return $args['default'];

		if ( ! $html = $this->viewengine__render( $view, [ 'data' => $data ], FALSE ) )
		if ( ! $html = $this->viewengine__render( $view, [ 'data' => $data ], FALSE ) )
			return $args['default'];

		$html = $args['before'].$html.$args['after'];

		if ( ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	public function modified_get_data_for_post( $post = NULL, $context = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$data = [
			'texts' => [
				'published' => _x( 'First published', 'View Text', 'geditorial-modified' ),   // `Created on`
				'updated'   => _x( 'Last updated', 'View Text', 'geditorial-modified' ),      // `Updated on`
			],
			'titles' => [
				'published' => _x( 'Publicly accessed on', 'View Title', 'geditorial-modified' ),
				'updated'   => _x( 'Lastly edited on', 'View Title', 'geditorial-modified' ),
			],
			'dates' => [
				'published' => gEditorial\Datetime::dateFormat( $post->post_date, 'dateonly' ),
				'updated'   => gEditorial\Datetime::dateFormat( $post->post_modified, 'dateonly' ),
			],
			'times' => [
				'published' => gEditorial\Datetime::dateFormat( $post->post_date, 'timeonly' ),
				'updated'   => gEditorial\Datetime::dateFormat( $post->post_modified, 'timeonly' ),
			],
			'datetimes' => [
				'published' => wp_date( 'c', strtotime( $post->post_date ) ),
				'updated'   => wp_date( 'c', strtotime( $post->post_modified ) ),
			],
			'actions' => [],
		];

		if ( $edit = WordPress\Post::edit( $post ) )
			$data['actions'][] = [
				'link'  => $edit,
				'text'  => Services\CustomPostType::getLabel( $post, 'edit_item' ),
				'title' => Services\CustomPostType::getLabel( $post, 'extended_label' ),
				'class' => 'text-bg-dark', // `text-bg-danger`
			];

		return $this->filters( 'data_summary', $data, $post, $context );
	}
}
