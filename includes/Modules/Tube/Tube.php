<?php namespace geminorum\gEditorial\Modules\Tube;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Tube extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\CoreRestrictPosts;
	use Internals\MetaBoxList;
	use Internals\O2OMetaBox;
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
				'double-paired',
				'manual-connect',
				'o2o',
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
				$this->settings_supports_option( 'primary_posttype', TRUE ),
				$this->settings_supports_option( 'secondary_posttype', TRUE ),
			],
			'_constants' => [
				'primary_posttype_constant'    => [ NULL, 'video' ],
				'primary_taxonomy_constant'    => [ NULL, 'video_category' ],
				'secondary_posttype_constant'  => [ NULL, 'channel' ],
				'secondary_taxonomy_constant'  => [ NULL, 'channel_category' ],
				'connected_shortcode_constant' => [ NULL, 'connected-videos' ],
				'children_shortcode_constant'  => [ NULL, 'channel-videos' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype'           => 'video',                   // ALT: `clip`
			'primary_posttype_connected' => 'connected_videos',
			'primary_taxonomy'           => 'video_category',
			'secondary_posttype'         => 'channel',
			'secondary_taxonomy'         => 'channel_category',
			'primary_shortcode'          => 'tube-video-category',
			'secondary_shortcode'        => 'tube-channel-category',
			'connected_shortcode'        => 'connected-videos',
			'children_shortcode'         => 'channel-videos',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'primary_posttype'   => _n_noop( 'Video', 'Videos', 'geditorial-tube' ),
				'primary_taxonomy'   => _n_noop( 'Video Category', 'Video Categories', 'geditorial-tube' ),
				'secondary_posttype' => _n_noop( 'Channel', 'Channels', 'geditorial-tube' ),
				'secondary_taxonomy' => _n_noop( 'Channel Category', 'Channel Categories', 'geditorial-tube' ),

				/* translators: `%s`: count number */
				'primary_posttype_count' => _n_noop( '%s Video', '%s Videos', 'geditorial-tube' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['o2o'] = [
			'primary_posttype' => [
				'title' => [
					'from' => _x( 'Connected Videos', 'O2O', 'geditorial-tube' ),
					'to'   => _x( 'Connected Posts', 'O2O', 'geditorial-tube' ),
				],
			],
		];

		return $strings;
	}

	// @REF: https://www.videouniversity.com/?p=6660
	public function get_global_fields()
	{
		return [
			'meta' => [
				$this->constant( 'primary_posttype' ) => [
					'over_title' => [ 'type' => 'title_before' ],
					'sub_title'  => [ 'type' => 'title_after' ],
					'lead'       => [ 'type' => 'postbox_html' ],

					'parent_post_id' => [
						'title'       => _x( 'Channel', 'Field Title', 'geditorial-tube' ),
						'description' => _x( 'Parent Channel of the Video', 'Field Description', 'geditorial-tube' ),
						'type'        => 'parent_post',
						'posttype'    => $this->get_setting( 'video_channels' ) ? $this->constant( 'secondary_posttype' ) : FALSE,
					],

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
				$this->constant( 'secondary_posttype' ) => [
					'over_title' => [ 'type' => 'title_before' ],
					'sub_title'  => [ 'type' => 'title_after' ],
					'lead'       => [ 'type' => 'postbox_html' ],

					'content_embed_url' => [ 'type' => 'embed' ],
					'text_source_url'   => [ 'type' => 'text_source' ],
					'audio_source_url'  => [ 'type' => 'audio_source' ],
					'video_source_url'  => [ 'type' => 'video_source' ],
					'image_source_url'  => [ 'type' => 'image_source' ],
				],
			],
		];
	}

	protected function posttypes_excluded( $extra = [] )
	{
		return $this->filters( 'posttypes_excluded',
			gEditorial\Settings::posttypesExcluded( $extra + [
				$this->constant( 'primary_posttype' ),
				$this->constant( 'secondary_posttype' ),
			], $this->keep_posttypes )
		);
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'primary_posttype' );

		if ( $this->get_setting( 'video_channels' ) )
			$this->register_posttype_thumbnail( 'secondary_posttype' );
	}

	public function o2o_init()
	{
		$posttypes = $this->get_setting( 'connected_posttypes', [] );

		if ( count( $posttypes ) )
			$this->_o2o = Services\O2O\API::registerConnectionType( [
				'name' => $this->constant( 'primary_posttype_connected' ),
				'to'   => $this->constant( 'primary_posttype' ),
				'from' => $posttypes,
			] );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'primary_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
		], 'primary_posttype', [
			'custom_icon' => $this->module->icon,
		] );

		$this->register_posttype( 'primary_posttype', [], [
			'primary_taxonomy' => $this->constant( 'primary_taxonomy' ),
		] );

		$this->register_shortcode( 'primary_shortcode' );
		$this->register_shortcode( 'connected_shortcode' );

		if ( $this->get_setting( 'video_channels' ) ) {

			$this->register_taxonomy( 'secondary_taxonomy', [
				'hierarchical'       => TRUE,
				'meta_box_cb'        => NULL,
				'show_admin_column'  => TRUE,
				'show_in_quick_edit' => TRUE,
				'default_term'       => NULL,
			], 'secondary_posttype', [
				'custom_icon' => 'playlist-video',
			] );

			$this->register_posttype( 'secondary_posttype', [
				'show_in_admin_bar' => FALSE,
			], [
				'custom_icon'      => 'playlist-video',
				'primary_taxonomy' => $this->constant( 'secondary_taxonomy' ),
			] );

			$this->register_shortcode( 'secondary_shortcode' );
			$this->register_shortcode( 'children_shortcode' );
		}

		if ( ! is_admin() && $this->get_setting( 'video_toolbar' ) ) {
			$this->filter( 'wp_video_shortcode', 5 );
			$this->filter( 'wp_video_shortcode_override', 4 );
		}
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'primary_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'get_default_comment_status', 3 );

				$this->posttype__media_register_headerbutton( 'primary_posttype' );
				$this->_hook_post_updated_messages( 'primary_posttype' );

				$this->o2o_register_metabox_from(
					'primary_posttype_connected',
					$this->get_setting( 'connected_posttypes', [] ),
					$screen
				);

			} else if ( 'edit' == $screen->base ) {

				// TODO: restrict videos by channel

				if ( Services\PostTypeFields::isAvailable( 'parent_post_id', $this->constant( 'primary_posttype' ) ) ) {
					$this->corerestrictposts__hook_columnrow_for_parent_post( $screen->post_type, 'playlist-video', 'meta', NULL, -10 );
					$this->corerestrictposts__hook_parsequery_for_post_parent( 'primary_posttype' );
				}

				$this->_hook_bulk_post_updated_messages( 'primary_posttype' );
				$this->postmeta__hook_meta_column_row( $screen->post_type, TRUE );
			}

		} else if ( $screen->post_type == $this->constant( 'secondary_posttype' )
			&& $this->get_setting( 'video_channels' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'get_default_comment_status', 3 );

				$this->posttype__media_register_headerbutton( 'secondary_posttype' );
				$this->_hook_post_updated_messages( 'secondary_posttype' );

				if ( Services\PostTypeFields::isAvailable( 'parent_post_id', $this->constant( 'primary_posttype' ) ) )
					$this->_hook_children_listbox( $screen, $this->constant( 'primary_posttype' ) );

			} else if ( 'edit' == $screen->base ) {

				if ( Services\PostTypeFields::isAvailable( 'parent_post_id', $this->constant( 'primary_posttype' ) ) )
					$this->corerestrictposts__hook_columnrow_for_post_children( $screen->post_type, 'primary_posttype', NULL, NULL, NULL, -10 );

				$this->_hook_bulk_post_updated_messages( 'secondary_posttype' );
				$this->postmeta__hook_meta_column_row( $screen->post_type, TRUE );
			}

		} else if ( $this->in_setting( $screen->post_type, 'connected_posttypes' ) ) {

			if ( 'post' == $screen->base ) {

				$this->o2o_register_metabox_to(
					'primary_posttype_connected',
					$this->constant( 'primary_posttype' ),
					$screen
				);

			} else if ( 'edit' == $screen->base ) {

				// TODO: columnrow_for_o2o_connected_to
			}
		}
	}

	public function meta_init()
	{
		$this->add_posttype_fields_for( 'meta', 'primary_posttype' );

		if ( $this->get_setting( 'video_channels' ) )
			$this->add_posttype_fields_for( 'meta', 'secondary_posttype' );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'primary_posttype' ) )
			$items[] = $glance;

		if ( $this->get_setting( 'video_channels' )
			&& ( $glance = $this->dashboard_glance_post( 'secondary_posttype' ) ) )
				$items[] = $glance;

		return $items;
	}

	public function wp_video_shortcode_override( $override, $attr, $content, $instance )
	{
		$this->_wp_video_shortcode_attr = $attr;
		return $override;
	}

	// TODO: move to `ModuleHelper`
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
				'class' => Core\HTML::buttonClass(),
				'title' => _x( 'The date of this video', 'Button', 'geditorial-tube' ),
			], $this->icon( 'calendar', 'gridicons' ).' '.$attr['date'] );

		if ( ! empty( $attr['time'] ) )
			$html.= Core\HTML::tag( 'button', [
				'class' => Core\HTML::buttonClass(),
				'title' => _x( 'Total time of this video', 'Button', 'geditorial-tube' ),
			], $this->icon( 'time' ).' '.Core\Number::localize( $attr['time'] ) );

		if ( ! empty( $attr['src'] ) )
			$html.= Core\HTML::tag( 'a', [
				'href'  => $attr['src'],
				'class' => Core\HTML::buttonClass(),
				'title' => _x( 'Download this video', 'Button', 'geditorial-tube' ),
			], $this->icon( 'download' ).' '._x( 'Download', 'Button', 'geditorial-tube' ) );

		if ( ! empty( $attr['youtube'] ) )
			$html.= Core\HTML::tag( 'a', [
				'href'   => $attr['youtube'],
				'class'  => Core\HTML::buttonClass(),
				'title'  => _x( 'View this video on YouTube', 'Button', 'geditorial-tube' ),
				'target' => '_blank',
			], $this->icon( 'youtube', 'social-logos' ).' '._x( 'YouTube', 'Button', 'geditorial-tube' ) );

		if ( ! empty( $attr['aparat'] ) )
			$html.= Core\HTML::tag( 'a', [
				'href'   => $attr['aparat'],
				'class'  => Core\HTML::buttonClass(),
				'title'  => _x( 'View this video on Aparat', 'Button', 'geditorial-tube' ),
				'target' => '_blank',
			], $this->icon( 'gorbeh-aparat', 'misc-512' ).' '._x( 'Aparat', 'Button', 'geditorial-tube' ) );

		$link = empty( $attr['shortlink'] ) ? WordPress\Post::shortlink( $post_id ) : $attr['shortlink'];

		$html.= Core\HTML::tag( 'a', [
			'href'   => $link,
			'class'  => Core\HTML::buttonClass(),
			'title'  => _x( 'Shortlink to this video', 'Button', 'geditorial-tube' ),
			'target' => '_blank',
		], $this->icon( 'link' ).' '._x( 'Shortlink', 'Button', 'geditorial-tube' ) );

		$html.= Core\HTML::tag( 'a', [
			'href'   => sprintf( 'https://telegram.me/share/url?url=%s', urlencode( $link ) ),
			'class'  => Core\HTML::buttonClass( TRUE, '-button-icon' ),
			'title'  => _x( 'Share this video', 'Button', 'geditorial-tube' ),
			'target' => '_blank',
		], $this->icon( 'telegram', 'social-logos' ) );

		$html.= Core\HTML::tag( 'a', [
			'href'   => sprintf( 'https://twitter.com/intent/tweet?url=%s', urlencode( $link ) ),
			'class'  => Core\HTML::buttonClass( TRUE, '-button-icon' ),
			'title'  => _x( 'Share this video', 'Button', 'geditorial-tube' ),
			'target' => '_blank',
		], $this->icon( 'twitter-alt', 'social-logos' ) );

		$html.= Core\HTML::tag( 'a', [
			'href'   => sprintf( 'https://www.facebook.com/sharer/sharer.php?u=%s', urlencode( $link ) ),
			'class'  => Core\HTML::buttonClass( TRUE, '-button-icon' ),
			'title'  => _x( 'Share this video', 'Button', 'geditorial-tube' ),
			'target' => '_blank',
		], $this->icon( 'facebook', 'social-logos' ) );

		return $output.Core\HTML::wrap( $html, $this->classs( 'video' ) );
	}

	public function primary_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'assigned',
			$this->constant( 'primary_posttype' ),
			$this->constant( 'primary_taxonomy' ),
			array_merge( [
				'post_id' => NULL,
			], (array) $atts ),
			$content,
			$this->constant( 'primary_shortcode', $tag ),
			$this->key
		);
	}

	public function connected_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		if ( ! $this->_o2o )
			return $content;

		return gEditorial\ShortCode::listPosts( 'objects2objects',
			$this->constant( 'primary_posttype' ),
			'',
			array_merge( [
				'post_id'       => NULL,
				'posttypes'     => $this->get_setting( 'connected_posttypes', [] ),
				// 'title_cb'      => [ $this, 'shortcode_title_cb' ],
				// 'item_after_cb' => [ $this, 'shortcode_item_after_cb' ],
				'title_anchor'  => $this->posttype_anchor( 'primary_posttype' ),
				'title_link'    => FALSE,
			], (array) $atts ),
			$content,
			$this->constant( 'connected_shortcode', $tag ),
			$this->key
		);
	}

	public function secondary_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'assigned',
			$this->constant( 'secondary_posttype' ),
			$this->constant( 'secondary_taxonomy' ),
			array_merge( [
				'post_id' => NULL,
			], (array) $atts ),
			$content,
			$this->constant( 'secondary_shortcode', $tag ),
			$this->key
		);
	}

	public function children_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'children',
			$this->constant( 'secondary_posttype' ),
			'',
			array_merge( [
				'post_id'   => NULL,
				'posttypes' => [ $this->constant( 'primary_posttype' ) ],
			], (array) $atts ),
			$content,
			$this->constant( 'children_shortcode', $tag ),
			$this->key
		);
	}
}
