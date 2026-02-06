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

	protected $disable_no_posttypes   = TRUE;
	protected $priority_adminbar_init = 910;

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
			'_general' => [
				[
					'field'       => 'last_published',
					'title'       => _x( 'Last Published', 'Setting Title', 'geditorial-modified' ),
					'description' => _x( 'Displays last published instead of modified date for site.', 'Setting Description', 'geditorial-modified' ),
				],
				[
					'field'       => 'prefix',
					'type'        => 'text',
					'title'       => _x( 'Content Prefix', 'Setting Title', 'geditorial-modified' ),
					'description' => sprintf(
						/* translators: `%s`: zero placeholder */
						_x( 'Custom string before the modified time on the content. Leave blank for default or %s to disable.', 'Setting Description', 'geditorial-modified' ),
						Core\HTML::code( '0' )
					),
					'placeholder' => _x( 'Last modified on', 'Setting Default', 'geditorial-modified' ),
				],
				[
					'field'       => 'display_after',
					'type'        => 'select',
					'title'       => _x( 'Display After', 'Setting Title', 'geditorial-modified' ),
					'description' => _x( 'Skips displaying modified since the original content published time.', 'Setting Description', 'geditorial-modified' ),
					'default'     => '60',
					'values'      => gEditorial\Settings::minutesOptions(),
				],
			],
			'posttypes_option' => 'posttypes_option',
			'_dashboard' => [
				'dashboard_widgets',
				'dashboard_statuses',
				'dashboard_authors',
				'dashboard_count',
			],
			'_content' => [
				'insert_content',
				'insert_priority',
				[
					'field'       => 'insert_context',
					'type'        => 'radio',
					'title'       => _x( 'Content Context', 'Setting Title', 'geditorial-modified' ),
					'description' => _x( 'Which context the modified information must be rendered.', 'Setting Description', 'geditorial-modified' ),
					'default'     => 'delayed',
					'values'      => [
						'summary' => _x( 'Summary', 'Setting Option', 'geditorial-modified' ),
						'delayed' => _x( 'Delayed', 'Setting Option', 'geditorial-modified' ),
					],
				],
				[
					'field'       => 'insert_format',
					'type'        => 'date-format',
					'title'       => _x( 'Insert Format', 'Setting Title', 'geditorial-modified' ),
					'description' => _x( 'Displays the date in this format on the content.', 'Setting Description', 'geditorial-modified' ),
					'default'     => 'dateonly',
				],
			],
			'_supports' => [
				'shortcode_support',
			],
			'_frontend' => [
				'adminbar_summary',
			],
			'_constants' => [
				$this->settings_shortcode_constant(
					'entry_modified_shortcode',
					_x( 'Entry Modified', 'Setting: Short-code Title', 'geditorial-modified' )
				),
				$this->settings_shortcode_constant(
					'post_modified_shortcode',
					_x( 'Post Modified', 'Setting: Short-code Title', 'geditorial-modified' )
				),
				$this->settings_shortcode_constant(
					'site_modified_shortcode',
					_x( 'Site Modified', 'Setting: Short-code Title', 'geditorial-modified' )
				),
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
		$this->register_shortcode( 'post_modified_shortcode', FALSE, [ 'last-edited', 'lastupdate' ] );
		$this->register_shortcode( 'site_modified_shortcode' );

		$this->filter( 'wp_nav_menu_items', 2 );

		if ( ! is_admin() )
			return;

		$this->action_module( 'pointers', 'post', 5, 120 );
		$this->filter( 'dashboard_pointers', 1, 4, FALSE, 'gnetwork' );
		$this->filter( 'navigation_help_placeholders', 2, 10, FALSE, 'gnetwork' );
	}

	public function template_redirect()
	{
		if ( ! is_singular( $this->posttypes() ) )
			return;

		$this->hook_content_insert( 30 );
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( ! $post = $this->adminbar__check_singular_post( NULL, 'read_post' ) )
			return;

		if ( $modified = $this->get_post_modified( gEditorial\Datetime::dateFormats( 'datetime' ), $post ) ) {

			$node_id = $this->classs();
			$icon    = $this->adminbar__get_icon( 'edit' );

			$nodes[] = [
				'parent' => $parent,
				'id'     => $node_id,
				'title'  => $icon.$modified,
				'href'   => FALSE, // $this->get_module_url(),
				'meta'   => [
					'class' => $this->adminbar__get_css_class( '-not-linked' ),
					'title' => sprintf(
						/* translators: `%1$s`: singular post-type label, `%2$s`: published date */
						_x( 'This %1$s was published on %2$s.', 'Node: Title', 'geditorial-modified' ),
						Services\CustomPostType::getLabel( $post, 'singular_name' ),
						gEditorial\Datetime::dateFormat( $post->post_date, 'fulltime' )
					),
				],
			];
		}
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

	public function pointers_post( $post, $before, $after, $context, $screen )
	{
		if ( ! $html = $this->get_post_modified( NULL, $post ) )
			return;

		printf( $before, '-modified' );
			echo Core\Text::spaced(
				$this->get_column_icon(),
				$html
			);
		echo $after;
	}

	public function dashboard_pointers( $items )
	{
		if ( $content = $this->site_modified_shortcode( [ 'title' => NULL ] ) )
			$items[] = Core\HTML::tag( 'span', [
				// 'href'  => $this->get_module_url(), // TODO
				'title' => $this->get_setting( 'last_published' )
					? _x( 'This is the time of last published post is this site.', 'Pointer: Site', 'geditorial-modified' )
					: _x( 'This is the time of last modified post is this site.', 'Pointer: Site', 'geditorial-modified' ),
				'class' => '-site-modified',
			], sprintf(
				/* translators: `%s`: site modified */
				_x( 'Last updated on %s', 'Pointer: Site', 'geditorial-modified' ),
				$content
			) );

		return $items;
	}

	public function insert_content( $content )
	{
		if ( ! $this->is_content_insert( FALSE, FALSE ) )
			return;

		if ( ! $this->is_page_content_insert() )
			return;

		if ( 'summary' === $this->get_setting( 'insert_context', 'delayed' ) ) {

			if ( ! $html = $this->modified_data_summary( [ 'echo' => FALSE ] ) )
				return;

		} else {

			if ( ! $modified = $this->get_post_modified() )
				return;

			$html = Core\HTML::small( $modified, 'text-muted' );
			gEditorial\Scripts::enqueueTimeAgo();
		}

		$this->wrap_content_insert( $html, 'clearfix' );
	}

	public function entry_modified_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return $this->modified_data_summary( array_merge( [
			'echo' => FALSE,
		], (array) $atts ) );
	}

	public function post_modified_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'             => get_queried_object_id(),
			'format'         => NULL,
			'format_context' => NULL,
			'title'          => 'timeago',                 // `FALSE` or `(string)` or `NULL` for full-time.
			'round'          => FALSE,
			'link'           => FALSE,
			'context'        => NULL,
			'wrap'           => TRUE,
			'before'         => '',
			'after'          => '',
		], $atts, $tag ?: $this->constant( 'post_modified_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $post = WordPress\Post::get( $args['id'] ) )
			return $content;

		if ( 'timeago' == $args['title'] )
			$title = gEditorial\Scripts::enqueueTimeAgo()
				? gEditorial\Datetime::dateFormat( $post->post_modified, 'fulltime' )
				: gEditorial\Datetime::humanTimeDiffRound( $post->post_modified, $args['round'] );

		else if ( is_null( $args['title'] ) )
			$title = gEditorial\Datetime::dateFormat( $post->post_modified, 'fulltime' );

		else
			$title = $args['title'];

		$html = Core\Date::htmlDateTime(
			$post->post_modified,
			$args['format'] ?? gEditorial\Datetime::dateFormats( $args['format_context'] ?? 'daydate' ),
			$title
		);

		return gEditorial\ShortCode::wrap(
			$args['link'] ? Core\HTML::link( $html, $args['link'] ) : $html,
			$this->constant( 'post_modified_shortcode' ),
			$args,
			FALSE
		);
	}

	public function get_post_modified( $format = NULL, $post = NULL, $prefix = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$threshold = $this->get_setting( 'display_after', '60' );

		if ( WordPress\Post::publishedInLast( $post, $threshold, MINUTE_IN_SECONDS ) )
			return FALSE;

		return Core\Text::spaced(
			$prefix ?? $this->get_setting_fallback( 'prefix', _x( 'Last modified on', 'Setting Default', 'geditorial-modified' ) ),
			Core\Date::htmlDateTime(
				$post->post_modified,
				$format ?? gEditorial\Datetime::dateFormats( $this->get_setting( 'insert_format', 'dateonly' ) ),
				gEditorial\Datetime::dateFormat( $post->post_modified, 'fulltime' )
			)
		);
	}

	// NOTE: just put `{SITE_LAST_MODIFIED}` on a menu item text!
	public function wp_nav_menu_items( $items, $args )
	{
		if ( ! Core\Text::has( $items, '{SITE_LAST_MODIFIED}' ) )
			return $items;

		return preg_replace( '%{SITE_LAST_MODIFIED}%', $this->get_site_modified() ?: '', $items );
	}

	//@hook: `gnetwork_navigation_help_placeholders`
	public function navigation_help_placeholders( $before, $after )
	{
		echo $before.Core\HTML::code( '{SITE_LAST_MODIFIED}', FALSE, TRUE ).$after;
	}

	public function site_modified_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'format'         => NULL,
			'format_context' => NULL,
			'title'          => 'timeago',   // `FALSE` or `(string)` or `NULL` for full-time.
			'round'          => FALSE,
			'link'           => FALSE,
			'context'        => NULL,
			'wrap'           => TRUE,
			'before'         => '',
			'after'          => '',
		], $atts, $tag ?: $this->constant( 'site_modified_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( FALSE === ( $site = $this->get_site_modified( TRUE ) ) )
			return NULL;

		if ( 'timeago' == $args['title'] )
			$title = gEditorial\Scripts::enqueueTimeAgo()
				? gEditorial\Datetime::dateFormat( $site[0], 'fulltime' )
				: gEditorial\Datetime::humanTimeDiffRound( $site[0], $args['round'] );

		else if ( is_null( $args['title'] ) )
			$title = gEditorial\Datetime::dateFormat( $site[0], 'fulltime' );

		else
			$title = $args['title'];

		$html = Core\Date::htmlDateTime(
			$site[0],
			$args['format'] ?? gEditorial\Datetime::dateFormats( $args['format_context'] ?? 'daydate' ),
			$title
		);

		return gEditorial\ShortCode::wrap(
			$args['link'] ? Core\HTML::link( $html, $args['link'] ) : $html,
			$this->constant( 'site_modified_shortcode' ),
			$args,
			FALSE
		);
	}

	public function get_site_modified( $format = NULL, $posttypes = NULL, $published = NULL )
	{
		global $wpdb;

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		else if ( FALSE === $posttypes )
			$posttypes = [];

		else if ( ! is_array( $posttypes ) )
			$posttypes = [ $posttypes ];

		if ( $published ?? $this->get_setting( 'last_published', FALSE ) ) {
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
			return [
				$results[0]->{$date},
				$results[0]->{$gmt},
			];

		return Core\Date::get(
			$format ?? gEditorial\Datetime::dateFormats( 'daydate' ),
			$results[0]->{$date}
		);
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

	public function modified_get_data_for_post( $post = NULL, $context = NULL, $format = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$context = $context ?? 'summary';
		$format  = $format  ?? $this->get_setting( 'insert_format', 'dateonly' );

		$data = [
			'context' => $context,
			'blocks'  => [],
			'actions' => [],
		];

		$data['blocks'][] = [
			'context'   => 'published',
			'text'      => _x( 'First published', 'View Text', 'geditorial-modified' ),         // `Created on` // TODO: Optional Customization
			'title'     => _x( 'Publicly accessed on', 'View Title', 'geditorial-modified' ),
			'date'      => gEditorial\Datetime::dateFormat( $post->post_date, $format ),
			'fulltime'  => gEditorial\Datetime::dateFormat( $post->post_date, 'fulltime' ),
			'datetime'  => Core\Date::getISO8601( $post->post_date ),
			'titlelink' => FALSE,
			'datelink'  => FALSE,
			'wrapclass' => '-post_date',
		];

		$data['blocks'][] = [
			'context'   => 'updated',
			'text'      => _x( 'Last updated', 'View Text', 'geditorial-modified' ),              // `Updated on` // TODO: Optional Customization
			'title'     => _x( 'Lastly edited on', 'View Title', 'geditorial-modified' ),
			'date'      => gEditorial\Datetime::dateFormat( $post->post_modified, $format ),
			'fulltime'  => gEditorial\Datetime::dateFormat( $post->post_modified, 'fulltime' ),
			'datetime'  => Core\Date::getISO8601( $post->post_modified ),
			'titlelink' => FALSE,
			'datelink'  => FALSE,
			'wrapclass' => '-post_modified',
		];

		if ( $edit = WordPress\Post::edit( $post ) )
			$data['actions'][] = [
				'link'  => $edit,
				'text'  => Services\CustomPostType::getLabel( $post, 'edit_item' ),
				'title' => Services\CustomPostType::getLabel( $post, 'extended_label' ),
				'class' => 'text-bg-dark', // `text-bg-danger`
			];

		return $this->filters( 'data_summary', $data, $post, $context, $format );
	}
}
