<?php namespace geminorum\gEditorial\Modules\Tube;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\WordPress;

class Tube extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\PostMeta;

	private $_wp_video_shortcode_attr = '';

	public static function module()
	{
		return [
			'name'     => 'tube',
			'title'    => _x( 'Tube', 'Modules: Tube', 'geditorial-admin' ),
			'desc'     => _x( 'Video Clip Management', 'Modules: Tube', 'geditorial-admin' ),
			'icon'     => 'video-alt2',
			'access'   => 'beta',
			'keywords' => [
				'video',
				'clip',
				'cptmodule',
			],
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
			'_connected' => [
				[
					'field'  => 'connected_posttypes',
					'type'   => 'posttypes',
					'title'  => _x( 'Connected Post-types', 'Setting Title', 'geditorial-tube' ),
					'values' => $this->all_posttypes(),
				],
			],
			'_supports' => [
				'assign_default_term',
				'comment_status',
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'video_posttype', TRUE ),
				$this->settings_supports_option( 'channel_posttype', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'video_posttype'             => 'video',                   // ALT: `clip`
			'video_posttype_connected'   => 'connected_videos',
			'category_video'             => 'video_category',
			'subject_taxonomy'           => 'video_subject',
			'channel_posttype'           => 'channel',
			'channel_posttype_connected' => 'connected_channels',
			'category_channel'           => 'channel_category',

			'video_shortcode'            => 'tube-video',
			'category_video_shortcode'   => 'tube-video-category',
			'channel_shortcode'          => 'tube-channel',
			'category_channel_shortcode' => 'tube-channel-category',
		];
	}

	protected function get_module_icons()
	{
		return [
			'post_types' => [
				'video_posttype'   => NULL,
				'channel_posttype' => 'playlist-video',
			],
			'taxonomies' => [
				'category_video'   => NULL,
				'category_channel' => 'playlist-video',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'video_posttype'   => _n_noop( 'Video', 'Videos', 'geditorial-tube' ),
				'category_video'   => _n_noop( 'Video Category', 'Video Categories', 'geditorial-tube' ),
				'channel_posttype' => _n_noop( 'Channel', 'Channels', 'geditorial-tube' ),
				'category_channel' => _n_noop( 'Channel Category', 'Channel Categories', 'geditorial-tube' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['o2o'] = [
			'video_posttype' => [
				'title' => [
					'from' => _x( 'Connected Videos', 'O2O', 'geditorial-tube' ),
					'to'   => _x( 'Connected Posts', 'O2O', 'geditorial-tube' ),
				],
			],
			'channel_posttype' => [
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
		return [ 'meta' => [
			$this->constant( 'video_posttype' ) => [
				'over_title' => [ 'type' => 'title_before' ],
				'sub_title'  => [ 'type' => 'title_after' ],
				'lead'       => [ 'type' => 'postbox_html' ],

				'featured_people' => [
					'title'       => _x( 'Featured People', 'Field Title', 'geditorial-tube' ),
					'description' => _x( 'People Who Featured in This Video', 'Field Description', 'geditorial-tube' ),
					'type'        => 'people',
					'quickedit'   => TRUE,
				],
				'creation_date' => [
					'title'       => _x( 'Creation Date', 'Field Title', 'geditorial-tube' ),
					'description' => _x( 'Creation Date of the Video', 'Field Description', 'geditorial-tube' ),
					'type'        => 'datestring',
					'icon'        => 'calendar-alt',
					'quickedit'   => TRUE,
					'bulkedit'    => FALSE,
				],
				'video_duration' => [
					'title'       => _x( 'Video Duration', 'Field Title', 'geditorial-tube' ),
					'description' => _x( 'Duration of the Video', 'Field Description', 'geditorial-tube' ),
					'type'        => 'duration',
					'quickedit'   => TRUE,
					'bulkedit'    => FALSE,
				],
				'video_embed_url' => [
					'title'       => _x( 'Video Embed URL', 'Field Title', 'geditorial-tube' ),
					'description' => _x( 'Embeddable URL of the External Video', 'Field Description', 'geditorial-tube' ),
					'type'        => 'embed',
				],
				'source_title' => [ 'type' => 'text' ],
				'source_url'   => [ 'type' => 'link' ],
				'highlight'    => [ 'type' => 'note' ],

				'content_embed_url' => [ 'type' => 'embed' ],
				'text_source_url'   => [ 'type' => 'text_source' ],
				'audio_source_url'  => [ 'type' => 'audio_source' ],
				'video_source_url'  => [ 'type' => 'video_source' ],
				'image_source_url'  => [ 'type' => 'image_source' ],
			],
			$this->constant( 'channel_posttype' ) => [
				'over_title' => [ 'type' => 'title_before' ],
				'sub_title'  => [ 'type' => 'title_after' ],
				'lead'       => [ 'type' => 'postbox_html' ],

				'content_embed_url' => [ 'type' => 'embed' ],
				'text_source_url'   => [ 'type' => 'text_source' ],
				'audio_source_url'  => [ 'type' => 'audio_source' ],
				'video_source_url'  => [ 'type' => 'video_source' ],
				'image_source_url'  => [ 'type' => 'image_source' ],
			],
		] ];
	}

	protected function posttypes_excluded( $extra = [] )
	{
		return $this->filters( 'posttypes_excluded', Settings::posttypesExcluded( $extra + [
			$this->constant( 'video_posttype' ),
			$this->constant( 'channel_posttype' ),
		] ) );
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'video_posttype' );

		if ( $this->get_setting( 'video_channels' ) )
			$this->register_posttype_thumbnail( 'channel_posttype' );
	}

	public function o2o_init()
	{
		$posttypes = $this->get_setting( 'connected_posttypes', [] );

		if ( count( $posttypes ) )
			$this->_o2o = Services\O2O\API::registerConnectionType( [
				'name' => $this->constant( 'video_posttype_connected' ),
				'to'   => $this->constant( 'video_posttype' ),
				'from' => $posttypes,
			] );

		if ( $this->get_setting( 'video_channels' ) )
			Services\O2O\API::registerConnectionType( [
				'name' => $this->constant( 'channel_posttype_connected' ),
				'to'   => $this->constant( 'channel_posttype' ),
				'from' => $this->constant( 'video_posttype' ),

				'reciprocal' => TRUE,
			] );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'category_video', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
		], 'video_posttype' );

		$this->register_posttype( 'video_posttype', [], [
			'primary_taxonomy' => $this->constant( 'category_video' ),
		] );

		$this->register_shortcode( 'category_video_shortcode' );

		if ( $this->get_setting( 'video_channels' ) ) {

			$this->register_taxonomy( 'category_channel', [
				'hierarchical'       => TRUE,
				'meta_box_cb'        => NULL,
				'show_admin_column'  => TRUE,
				'show_in_quick_edit' => TRUE,
				'default_term'       => NULL,
			], 'channel_posttype' );

			$this->register_posttype( 'channel_posttype', [
				'show_in_admin_bar' => FALSE,
				'primary_taxonomy'  => $this->constant( 'category_channel' ),
			] );

			$this->register_shortcode( 'category_channel_shortcode' );
		}

		if ( ! is_admin() && $this->get_setting( 'video_toolbar' ) ) {
			$this->filter( 'wp_video_shortcode', 5 );
			$this->filter( 'wp_video_shortcode_override', 4 );
		}
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'video_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'get_default_comment_status', 3 );

				$this->posttype__media_register_headerbutton( 'video_posttype' );
				$this->_hook_post_updated_messages( 'video_posttype' );
			} else if ( 'edit' == $screen->base ) {

				$this->_hook_bulk_post_updated_messages( 'video_posttype' );
				$this->postmeta__hook_meta_column_row( $screen->post_type, TRUE );
			}

		} else if ( $screen->post_type == $this->constant( 'channel_posttype' )
			&& $this->get_setting( 'video_channels' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'get_default_comment_status', 3 );

				$this->posttype__media_register_headerbutton( 'channel_posttype' );
				$this->_hook_post_updated_messages( 'channel_posttype' );
			} else if ( 'edit' == $screen->base ) {

				$this->_hook_bulk_post_updated_messages( 'channel_posttype' );
				$this->postmeta__hook_meta_column_row( $screen->post_type, TRUE );
			}
		}
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'video_posttype' ) );

		if ( $this->get_setting( 'video_channels' ) )
			$this->add_posttype_fields( $this->constant( 'channel_posttype' ) );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'video_posttype' ) )
			$items[] = $glance;

		if ( $this->get_setting( 'video_channels' )
			&& ( $glance = $this->dashboard_glance_post( 'channel_posttype' ) ) )
				$items[] = $glance;

		return $items;
	}

	public function wp_video_shortcode_override( $override, $attr, $content, $instance )
	{
		$this->_wp_video_shortcode_attr = $attr;
		return $override;
	}

	public function wp_video_shortcode( $output, $atts, $video, $post_id, $library )
	{
		if ( ! isset( $this->_wp_video_shortcode_attr ) )
			return $output;

		$attr = $this->_wp_video_shortcode_attr;

		if ( isset( $attr['toolbar'] ) && ! $attr['toolbar'] )
			return $output;

		if ( ! empty( $attr['title'] ) )
			$output.= Core\HTML::tag( 'h3', $attr['title'] );

		$html = '';

		if ( ! empty( $attr['date'] ) )
			$html.= Core\HTML::tag( 'button', [
				'class' => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
				'title' => _x( 'The date of this video', 'Button', 'geditorial-tube' ),
			], $this->icon( 'calendar', 'gridicons' ).' '.$attr['date'] );

		if ( ! empty( $attr['time'] ) )
			$html.= Core\HTML::tag( 'button', [
				'class' => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
				'title' => _x( 'Total time of this video', 'Button', 'geditorial-tube' ),
			], $this->icon( 'time' ).' '.Core\Number::localize( $attr['time'] ) );

		if ( ! empty( $attr['src'] ) )
			$html.= Core\HTML::tag( 'a', [
				'href'  => $attr['src'],
				'class' => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
				'title' => _x( 'Download this video', 'Button', 'geditorial-tube' ),
			], $this->icon( 'download' ).' '._x( 'Download', 'Button', 'geditorial-tube' ) );

		if ( ! empty( $attr['youtube'] ) )
			$html.= Core\HTML::tag( 'a', [
				'href'   => $attr['youtube'],
				'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
				'title'  => _x( 'View this video on YouTube', 'Button', 'geditorial-tube' ),
				'target' => '_blank',
			], $this->icon( 'youtube', 'social-logos' ).' '._x( 'YouTube', 'Button', 'geditorial-tube' ) );

		if ( ! empty( $attr['aparat'] ) )
			$html.= Core\HTML::tag( 'a', [
				'href'   => $attr['aparat'],
				'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
				'title'  => _x( 'View this video on Aparat', 'Button', 'geditorial-tube' ),
				'target' => '_blank',
			], $this->icon( 'gorbeh-aparat', 'misc-512' ).' '._x( 'Aparat', 'Button', 'geditorial-tube' ) );

		$link = empty( $attr['shortlink'] ) ? Core\WordPress::getPostShortLink( $post_id ) : $attr['shortlink'];

		$html.= Core\HTML::tag( 'a', [
			'href'   => $link,
			'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs' ],
			'title'  => _x( 'Shortlink to this video', 'Button', 'geditorial-tube' ),
			'target' => '_blank',
		], $this->icon( 'link' ).' '._x( 'Shortlink', 'Button', 'geditorial-tube' ) );

		$html.= Core\HTML::tag( 'a', [
			'href'   => sprintf( 'https://telegram.me/share/url?url=%s', urlencode( $link ) ),
			'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs', '-button-icon' ],
			'title'  => _x( 'Share this video', 'Button', 'geditorial-tube' ),
			'target' => '_blank',
		], $this->icon( 'telegram', 'social-logos' ) );

		$html.= Core\HTML::tag( 'a', [
			'href'   => sprintf( 'https://twitter.com/intent/tweet?url=%s', urlencode( $link ) ),
			'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs', '-button-icon' ],
			'title'  => _x( 'Share this video', 'Button', 'geditorial-tube' ),
			'target' => '_blank',
		], $this->icon( 'twitter-alt', 'social-logos' ) );

		$html.= Core\HTML::tag( 'a', [
			'href'   => sprintf( 'https://www.facebook.com/sharer/sharer.php?u=%s', urlencode( $link ) ),
			'class'  => [ '-button', 'btn', 'btn-default', 'btn-xs', '-button-icon' ],
			'title'  => _x( 'Share this video', 'Button', 'geditorial-tube' ),
			'target' => '_blank',
		], $this->icon( 'facebook', 'social-logos' ) );

		return $output.Core\HTML::wrap( $html, $this->classs( 'video' ) );
	}

	public function category_video_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listPosts( 'assigned',
			$this->constant( 'video_posttype' ),
			$this->constant( 'category_video' ),
			$atts,
			$content,
			$this->constant( 'category_video_shortcode', $tag ),
			$this->key
		);
	}

	public function category_channel_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listPosts( 'assigned',
			$this->constant( 'channel_posttype' ),
			$this->constant( 'category_channel' ),
			$atts,
			$content,
			$this->constant( 'category_channel_shortcode', $tag ),
			$this->key
		);
	}
}
