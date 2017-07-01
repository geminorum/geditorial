<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\Helpers\Tube as ModuleHelper;

class Tube extends gEditorial\Module
{

	protected $partials = [ 'helper' ];

	public static function module()
	{
		return [
			'name'  => 'tube',
			'title' => _x( 'Tube', 'Modules: Tube', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Video Clip Managment', 'Modules: Tube', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'video-alt2',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'video_toolbar',
					'title'       => _x( 'Video Toolbar', 'Modules: Tube: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Display customized toolbar after player', 'Modules: Tube: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
			],
			'_supports' => [
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
			'video_cat'           => 'video_cat',
			'video_cat_slug'      => 'video-category',
			'video_shortcode'     => 'tube-video',
			'channel_cpt'         => 'channel',
			'channel_cpt_archive' => 'channels',
			'channel_cpt_p2p'     => 'related_channels',
			'channel_cat'         => 'channel_cat',
			'channel_cat_slug'    => 'channel-category',
			'channel_shortcode'   => 'tube-channel',
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
				'video_cpt'   => _nx_noop( 'Video', 'Videos', 'Modules: Tube: Noop', GEDITORIAL_TEXTDOMAIN ),
				'video_cat'   => _nx_noop( 'Video Category', 'Video Categories', 'Modules: Tube: Noop', GEDITORIAL_TEXTDOMAIN ),
				'channel_cpt' => _nx_noop( 'Channel', 'Channels', 'Modules: Tube: Noop', GEDITORIAL_TEXTDOMAIN ),
				'channel_cat' => _nx_noop( 'Channel Category', 'Channel Categories', 'Modules: Tube: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc']['video_cat']['tweaks_column_title']   = _x( 'Video Categories', 'Modules: Tube: Column Title', GEDITORIAL_TEXTDOMAIN );
		$strings['misc']['channel_cat']['tweaks_column_title'] = _x( 'Channel Categories', 'Modules: Tube: Column Title', GEDITORIAL_TEXTDOMAIN );

		$strings['p2p'] = [
			'video_cpt' => [
				'title' => [
					'from' => _x( 'Connected Videos', 'Modules: Tube: O2O', GEDITORIAL_TEXTDOMAIN ),
					'to'   => _x( 'Connected Posts', 'Modules: Tube: O2O', GEDITORIAL_TEXTDOMAIN ),
				],
			],
			'channel_cpt' => [
				'title' => [
					'from' => _x( 'Connected Channels', 'Modules: Tube: O2O', GEDITORIAL_TEXTDOMAIN ),
					'to'   => _x( 'Connected Videos', 'Modules: Tube: O2O', GEDITORIAL_TEXTDOMAIN ),
				],
			],
		];

		return $strings;
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'video_cpt' );
		$this->register_post_type_thumbnail( 'channel_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->post_types_excluded = [
			'attachment',
			$this->constant( 'video_cpt' ),
			$this->constant( 'channel_cpt' ),
		];

		$this->register_post_type( 'video_cpt' );
		$this->register_post_type( 'channel_cpt' );

		$this->register_taxonomy( 'video_cat', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'video_cpt' );

		$this->register_taxonomy( 'channel_cat', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'channel_cpt' );

		if ( ! is_admin() && $this->get_setting( 'video_toolbar' ) ) {
			$this->filter( 'wp_video_shortcode', 5 );
			$this->filter( 'wp_video_shortcode_override', 4 );
		}
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
			$output .= HTML::tag( 'h3', $attr['title'] );

		$html = '';

		if ( ! empty( $attr['date'] ) )
			$html .= HTML::tag( 'button', [
				'class' => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
				'title' => _x( 'The date of this video', 'Modules: Tube: Button', GEDITORIAL_TEXTDOMAIN ),
			], $this->get_icon( 'calendar', 'gridicons' ).' '.$attr['date'] );

		if ( ! empty( $attr['time'] ) )
			$html .= HTML::tag( 'button', [
				'class' => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
				'title' => _x( 'Total time of this video', 'Modules: Tube: Button', GEDITORIAL_TEXTDOMAIN ),
			], $this->get_icon( 'time' ).' '.Number::format( $attr['time'] ) );

		if ( ! empty( $attr['src'] ) )
			$html .= HTML::tag( 'a', [
				'href'  => $attr['src'],
				'class' => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
				'title' => _x( 'Download this video', 'Modules: Tube: Button', GEDITORIAL_TEXTDOMAIN ),
			], $this->get_icon( 'download' ).' '._x( 'Download', 'Modules: Tube: Button', GEDITORIAL_TEXTDOMAIN ) );

		if ( ! empty( $attr['youtube'] ) )
			$html .= HTML::tag( 'a', [
				'href'   => $attr['youtube'],
				'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
				'title'  => _x( 'View this video on YouTube', 'Modules: Tube: Button', GEDITORIAL_TEXTDOMAIN ),
				'target' => '_blank',
			], $this->get_icon( 'youtube', 'social-logos' ).' '._x( 'YouTube', 'Modules: Tube: Button', GEDITORIAL_TEXTDOMAIN ) );

		if ( ! empty( $attr['aparat'] ) )
			$html .= HTML::tag( 'a', [
				'href'   => $attr['aparat'],
				'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
				'title'  => _x( 'View this video on Aparat', 'Modules: Tube: Button', GEDITORIAL_TEXTDOMAIN ),
				'target' => '_blank',
			], $this->get_icon( 'aparat', 'gorbeh' ).' '._x( 'Aparat', 'Modules: Tube: Button', GEDITORIAL_TEXTDOMAIN ) );

		$link = empty( $attr['shortlink'] ) ? WordPress::getPostShortLink( $post_id ) : $attr['shortlink'];

		$html .= HTML::tag( 'a', [
			'href'   => $link,
			'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
			'title'  => _x( 'Shortlink to this video', 'Modules: Tube: Button', GEDITORIAL_TEXTDOMAIN ),
			'target' => '_blank',
		], $this->get_icon( 'link' ).' '._x( 'Shortlink', 'Modules: Tube: Button', GEDITORIAL_TEXTDOMAIN ) );

		$html .= HTML::tag( 'a', [
			'href'   => sprintf( 'https://telegram.me/share/url?url=%s', urlencode( $link ) ),
			'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs', '-icon-button' ],
			'title'  => _x( 'Share this video', 'Modules: Tube: Button', GEDITORIAL_TEXTDOMAIN ),
			'target' => '_blank',
		], $this->get_icon( 'telegram', 'social-logos' ) );

		$html .= HTML::tag( 'a', [
			'href'   => sprintf( 'https://twitter.com/intent/tweet?url=%s', urlencode( $link ) ),
			'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs', '-icon-button' ],
			'title'  => _x( 'Share this video', 'Modules: Tube: Button', GEDITORIAL_TEXTDOMAIN ),
			'target' => '_blank',
		], $this->get_icon( 'twitter-alt', 'social-logos' ) );

		$html .= HTML::tag( 'a', [
			'href'   => sprintf( 'https://plus.google.com/share?url=%s', urlencode( $link ) ),
			'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs', '-icon-button' ],
			'title'  => _x( 'Share this video', 'Modules: Tube: Button', GEDITORIAL_TEXTDOMAIN ),
			'target' => '_blank',
		], $this->get_icon( 'google-plus-alt', 'social-logos' ) );

		$html .= HTML::tag( 'a', [
			'href'   => sprintf( 'https://www.facebook.com/sharer/sharer.php?u=%s', urlencode( $link ) ),
			'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs', '-icon-button' ],
			'title'  => _x( 'Share this video', 'Modules: Tube: Button', GEDITORIAL_TEXTDOMAIN ),
			'target' => '_blank',
		], $this->get_icon( 'facebook', 'social-logos' ) );

		return $output.HTML::wrap( $html, $this->classs( 'video' ) );
	}
}
