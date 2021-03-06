<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\Helpers\Tube as ModuleHelper;

class Tube extends gEditorial\Module
{

	protected $partials = [ 'Helper' ];

	public static function module()
	{
		return [
			'name'  => 'tube',
			'title' => _x( 'Tube', 'Modules: Tube', 'geditorial' ),
			'desc'  => _x( 'Video Clip Managment', 'Modules: Tube', 'geditorial' ),
			'icon'  => 'video-alt2',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'video_channels',
					'title'       => _x( 'Channels Support', 'Setting Title', 'geditorial-tube' ),
					'description' => _x( 'Supports channel post-type and related features.', 'Setting Description', 'geditorial-tube' ),
				],
				[
					'field'       => 'video_toolbar',
					'title'       => _x( 'Video Toolbar', 'Setting Title', 'geditorial-tube' ),
					'description' => _x( 'Displays customized toolbar after player.', 'Setting Description', 'geditorial-tube' ),
				],
			],
			'_supports' => [
				'comment_status',
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'video_cpt', TRUE ),
				$this->settings_supports_option( 'channel_cpt', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'video_cpt'           => 'video', // clip
			'video_cpt_archive'   => 'videos',
			'video_cpt_p2p'       => 'related_videos',
			'video_cat'           => 'video_category',
			'video_cat_slug'      => 'video-categories',
			'subject_tax'         => 'video_subject',
			'channel_cpt'         => 'channel',
			'channel_cpt_archive' => 'channels',
			'channel_cpt_p2p'     => 'related_channels',
			'channel_cat'         => 'channel_category', // MAYBE rename to `channel_subject`
			'channel_cat_slug'    => 'channel-categories',

			'video_shortcode'       => 'tube-video',
			'video_cat_shortcode'   => 'tube-video-category',
			'channel_shortcode'     => 'tube-channel',
			'channel_cat_shortcode' => 'tube-channel-category',
		];
	}

	protected function get_module_icons()
	{
		return [
			'post_types' => [
				'video_cpt'   => NULL,
				'channel_cpt' => 'playlist-video',
			],
			'taxonomies' => [
				'video_cat'   => NULL,
				'channel_cat' => 'playlist-video',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'video_cpt'   => _nx_noop( 'Video', 'Videos', 'Noop', 'geditorial-tube' ),
				'video_cat'   => _nx_noop( 'Video Category', 'Video Categories', 'Noop', 'geditorial-tube' ),
				'channel_cpt' => _nx_noop( 'Channel', 'Channels', 'Noop', 'geditorial-tube' ),
				'channel_cat' => _nx_noop( 'Channel Category', 'Channel Categories', 'Noop', 'geditorial-tube' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc']['video_cat']['tweaks_column_title']   = _x( 'Video Categories', 'Column Title', 'geditorial-tube' );
		$strings['misc']['channel_cat']['tweaks_column_title'] = _x( 'Channel Categories', 'Column Title', 'geditorial-tube' );

		$strings['p2p'] = [
			'video_cpt' => [
				'title' => [
					'from' => _x( 'Connected Videos', 'O2O', 'geditorial-tube' ),
					'to'   => _x( 'Connected Posts', 'O2O', 'geditorial-tube' ),
				],
			],
			'channel_cpt' => [
				'title' => [
					'from' => _x( 'Connected Channels', 'O2O', 'geditorial-tube' ),
					'to'   => _x( 'Connected Videos', 'O2O', 'geditorial-tube' ),
				],
			],
		];

		return $strings;
	}

	// @REF: https://www.videouniversity.com/?p=6660
	public function get_global_fields()
	{
		return [
			$this->constant( 'video_cpt' ) => [
				'over_title' => [ 'type' => 'title_before' ],
				'sub_title'  => [ 'type' => 'title_after' ],

				'featured_people' => [
					'title'       => _x( 'Featured People', 'Field Title', 'geditorial-tube' ),
					'description' => _x( 'Featured People', 'Field Description', 'geditorial-tube' ),
					'icon'        => 'groups',
					'quickedit'   => TRUE,
				],
				'creation_date' => [
					'title'       => _x( 'Creation Date', 'Field Title', 'geditorial-tube' ),
					'description' => _x( 'Creation Date', 'Field Description', 'geditorial-tube' ),
					'icon'        => 'calendar-alt',
				],
				'video_duration' => [
					'title'       => _x( 'Video Duration', 'Field Title', 'geditorial-tube' ),
					'description' => _x( 'Video Duration', 'Field Description', 'geditorial-tube' ),
					'icon'        => 'backup',
				],

				'source_title' => [ 'type' => 'text' ],
				'source_url'   => [ 'type' => 'link' ],
				'highlight'    => [ 'type' => 'note' ],
			],
		];
	}

	protected function posttypes_excluded()
	{
		return Settings::posttypesExcluded( [
			$this->constant( 'video_cpt' ),
			$this->constant( 'channel_cpt' ),
		] );
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'video_cpt' );

		if ( $this->get_setting( 'video_channels' ) )
			$this->register_posttype_thumbnail( 'channel_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'video_cat', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'video_cpt' );

		$this->register_posttype( 'video_cpt' );
		$this->register_shortcode( 'video_cat_shortcode' );

		if ( $this->get_setting( 'video_channels' ) ) {

			$this->register_taxonomy( 'channel_cat', [
				'hierarchical'       => TRUE,
				'meta_box_cb'        => NULL,
				'show_admin_column'  => TRUE,
				'show_in_quick_edit' => TRUE,
			], 'channel_cpt' );

			$this->register_posttype( 'channel_cpt', [
				'show_in_admin_bar' => FALSE,
			] );

			$this->register_shortcode( 'channel_cat_shortcode' );
		}

		if ( ! is_admin() && $this->get_setting( 'video_toolbar' ) ) {
			$this->filter( 'wp_video_shortcode', 5 );
			$this->filter( 'wp_video_shortcode_override', 4 );
		}
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'video_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'get_default_comment_status', 3 );

			} else if ( 'edit' == $screen->base ) {

				$this->action_module( 'meta', 'column_row', 3 );
			}

		} else if ( $screen->post_type == $this->constant( 'channel_cpt' )
			&& $this->get_setting( 'video_channels' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'get_default_comment_status', 3 );

			} else if ( 'edit' == $screen->base ) {

				$this->action_module( 'meta', 'column_row', 3 );
			}
		}
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'video_cpt' ) );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'video_cpt' ) )
			$items[] = $glance;

		if ( $this->get_setting( 'video_channels' )
			&& ( $glance = $this->dashboard_glance_post( 'channel_cpt' ) ) )
				$items[] = $glance;

		return $items;
	}

	public function wp_video_shortcode_override( $override, $attr, $content, $instance )
	{
		$this->wp_video_shortcode_attr = $attr;
		return $override;
	}

	public function wp_video_shortcode( $output, $atts, $video, $post_id, $library )
	{
		if ( ! isset( $this->wp_video_shortcode_attr ) )
			return $output;

		$attr = $this->wp_video_shortcode_attr;

		if ( isset( $attr['toolbar'] ) && ! $attr['toolbar'] )
			return $output;

		if ( ! empty( $attr['title'] ) )
			$output.= HTML::tag( 'h3', $attr['title'] );

		$html = '';

		if ( ! empty( $attr['date'] ) )
			$html.= HTML::tag( 'button', [
				'class' => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
				'title' => _x( 'The date of this video', 'Button', 'geditorial-tube' ),
			], $this->icon( 'calendar', 'gridicons' ).' '.$attr['date'] );

		if ( ! empty( $attr['time'] ) )
			$html.= HTML::tag( 'button', [
				'class' => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
				'title' => _x( 'Total time of this video', 'Button', 'geditorial-tube' ),
			], $this->icon( 'time' ).' '.Number::localize( $attr['time'] ) );

		if ( ! empty( $attr['src'] ) )
			$html.= HTML::tag( 'a', [
				'href'  => $attr['src'],
				'class' => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
				'title' => _x( 'Download this video', 'Button', 'geditorial-tube' ),
			], $this->icon( 'download' ).' '._x( 'Download', 'Button', 'geditorial-tube' ) );

		if ( ! empty( $attr['youtube'] ) )
			$html.= HTML::tag( 'a', [
				'href'   => $attr['youtube'],
				'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
				'title'  => _x( 'View this video on YouTube', 'Button', 'geditorial-tube' ),
				'target' => '_blank',
			], $this->icon( 'youtube', 'social-logos' ).' '._x( 'YouTube', 'Button', 'geditorial-tube' ) );

		if ( ! empty( $attr['aparat'] ) )
			$html.= HTML::tag( 'a', [
				'href'   => $attr['aparat'],
				'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
				'title'  => _x( 'View this video on Aparat', 'Button', 'geditorial-tube' ),
				'target' => '_blank',
			], $this->icon( 'aparat', 'gorbeh' ).' '._x( 'Aparat', 'Button', 'geditorial-tube' ) );

		$link = empty( $attr['shortlink'] ) ? WordPress::getPostShortLink( $post_id ) : $attr['shortlink'];

		$html.= HTML::tag( 'a', [
			'href'   => $link,
			'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
			'title'  => _x( 'Shortlink to this video', 'Button', 'geditorial-tube' ),
			'target' => '_blank',
		], $this->icon( 'link' ).' '._x( 'Shortlink', 'Button', 'geditorial-tube' ) );

		$html.= HTML::tag( 'a', [
			'href'   => sprintf( 'https://telegram.me/share/url?url=%s', urlencode( $link ) ),
			'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs', '-button-icon' ],
			'title'  => _x( 'Share this video', 'Button', 'geditorial-tube' ),
			'target' => '_blank',
		], $this->icon( 'telegram', 'social-logos' ) );

		$html.= HTML::tag( 'a', [
			'href'   => sprintf( 'https://twitter.com/intent/tweet?url=%s', urlencode( $link ) ),
			'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs', '-button-icon' ],
			'title'  => _x( 'Share this video', 'Button', 'geditorial-tube' ),
			'target' => '_blank',
		], $this->icon( 'twitter-alt', 'social-logos' ) );

		$html.= HTML::tag( 'a', [
			'href'   => sprintf( 'https://www.facebook.com/sharer/sharer.php?u=%s', urlencode( $link ) ),
			'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs', '-button-icon' ],
			'title'  => _x( 'Share this video', 'Button', 'geditorial-tube' ),
			'target' => '_blank',
		], $this->icon( 'facebook', 'social-logos' ) );

		return $output.HTML::wrap( $html, $this->classs( 'video' ) );
	}

	public function video_cat_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::getTermPosts(
			$this->constant( 'video_cpt' ),
			$this->constant( 'video_cat' ),
			$atts,
			$content,
			$this->constant( 'video_cat_shortcode' )
		);
	}

	public function channel_cat_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::getTermPosts(
			$this->constant( 'channel_cpt' ),
			$this->constant( 'channel_cat' ),
			$atts,
			$content,
			$this->constant( 'channel_cat_shortcode' )
		);
	}
}
