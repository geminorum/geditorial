<?php namespace geminorum\gEditorial\Modules\Bookmarked;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\WordPress;

class Bookmarked extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\CoreAdmin;
	use Internals\CoreRowActions;
	use Internals\FramePage;
	use Internals\MetaBoxSupported;
	use Internals\RestAPI;
	use Internals\SubContents;

	public static function module()
	{
		return [
			'name'     => 'bookmarked',
			'title'    => _x( 'Bookmarked', 'Modules: Bookmarked', 'geditorial-admin' ),
			'desc'     => _x( 'Content External Link Management', 'Modules: Bookmarked', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'octicons-repo' ],
			'access'   => 'beta',
			'keywords' => [
				'external',
				'subcontent',
			],
		];
	}

	// TODO: optional display on front: `Tabs` module / WC Product Tabs
	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'_subcontent' => [
				'subcontent_posttypes' => [ NULL, $this->all_posttypes() ],
				'subcontent_fields'    => [ NULL, $this->subcontent_get_fields_for_settings() ],
				'subcontent_types'     => [ NULL, $this->subcontent_get_types_for_settings() ],
			],
			'_roles' => [
				'reports_roles' => [ NULL, $roles ],
				'assign_roles'  => [ NULL, $roles ],
			],
			'_editpost' => [
				'admin_rowactions',
			],
			'_frontend' => [
				'tabs_support',
			],
			'_supports' => [
				'shortcode_support',
				'woocommerce_support',
			],
			'_constants' => [
				'main_shortcode_constant' => [ NULL, 'content-bookmarks' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'restapi_namespace' => 'content-bookmarks',
			'subcontent_type'   => 'content_bookmarks',
			'subcontent_status' => 'private',
			'main_shortcode'    => 'content-bookmarks',

			'term_empty_subcontent_data' => 'bookmarks-data-empty',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'fields' => [
				'subcontent' => [
					'label'    => _x( 'Label', 'Field Label: `label`', 'geditorial-bookmarked' ),
					'link'     => _x( 'Bookmark', 'Field Label: `link`', 'geditorial-bookmarked' ),
					'type'     => _x( 'Type', 'Field Label: `type`', 'geditorial-bookmarked' ),
					'code'     => _x( 'Code', 'Field Label: `code`', 'geditorial-bookmarked' ),
					// 'cssclass' => _x( 'CSS Class', 'Field Label: `cssclass`', 'geditorial-bookmarked' ),
					// 'date'     => _x( 'Last Check', 'Field Label: `date`', 'geditorial-bookmarked' ),
					'desc'     => _x( 'Description', 'Field Label: `desc`', 'geditorial-bookmarked' ),
				],
			],
		];

		$strings['frontend'] = [
			'tab_title' => _x( 'Bookmarks', 'Tab Title', 'geditorial-bookmarked' ),
		];

		$strings['notices'] = [
			'empty'    => _x( 'There is no bookmarks information available!', 'Notice', 'geditorial-bookmarked' ),
			'noaccess' => _x( 'You have not necessary permission to manage the bookmarks data.', 'Notice', 'geditorial-bookmarked' ),
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'supportedbox_title'  => _x( 'Bookmarks', 'MetaBox Title', 'geditorial-bookmarked' ),
			// 'metabox_action' => _x( 'Bookmarks', 'MetaBox Action', 'geditorial-bookmarked' ),

			/* translators: %1$s: current post title, %2$s: post-type singular name */
			'mainbutton_title' => _x( 'Bookmarks of %1$s', 'Button Title', 'geditorial-bookmarked' ),
			/* translators: %1$s: icon markup, %2$s: post-type singular name */
			'mainbutton_text'  => _x( '%1$s Manage the Bookmarks of %2$s', 'Button Text', 'geditorial-bookmarked' ),

			/* translators: %1$s: current post title, %2$s: post-type singular name */
			'rowaction_title' => _x( 'Bookmarks of %1$s', 'Action Title', 'geditorial-bookmarked' ),
			/* translators: %1$s: icon markup, %2$s: post-type singular name */
			'rowaction_text'  => _x( 'Bookmarks', 'Action Text', 'geditorial-bookmarked' ),

			/* translators: %1$s: current post title, %2$s: post-type singular name */
			'columnrow_title' => _x( 'Bookmarks of %1$s', 'Row Title', 'geditorial-bookmarked' ),
			/* translators: %1$s: icon markup, %2$s: post-type singular name */
			'columnrow_text'  => _x( 'Bookmarks', 'Row Text', 'geditorial-bookmarked' ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'meta' => [
				'_supported' => [
					'bookmarked_title' => [
						'title'       => _x( 'Bookmarks Title', 'Field Title', 'geditorial-bookmarked' ),
						'description' => _x( 'The Bookmarks Table Caption', 'Field Description', 'geditorial-bookmarked' ),
						'order'       => 400,
					],
				],
			]
		];
	}

	protected function subcontent_get_data_mapping()
	{
		return array_merge( $this->subcontent_base_data_mapping(), [
			'comment_content' => 'desc',    // `text`
			'comment_agent'   => 'label',   // `varchar(255)`
			'comment_karma'   => 'order',   // `int(11)`

			'comment_author'       => 'link',   // `tinytext`
			'comment_author_url'   => 'type',   // `varchar(200)`
			'comment_author_email' => 'date',   // `varchar(100)`
			'comment_author_IP'    => 'code',   // `varchar(100)`
		] );
	}

	protected function subcontent_get_meta_mapping()
	{
		return [
			'cssclass' => 'cssclass',
			'postid'   => '_post_ref',
		];
	}

	protected function subcontent_define_required_fields()
	{
		return [
			'label',
			'type',
		];
	}

	protected function subcontent_define_selectable_fields( $context, $posttype = NULL )
	{
		return [
			'type' => $this->subcontent_list_type_options( $context, $posttype ),
		];
	}

	// TODO: support: `Core\Third::getHandleURL()`
	protected function subcontent_define_type_options( $context, $posttype = NULL )
	{
		return [
			[
				'name'     => 'default',
				'title'    => _x( 'Custom Link', 'Type Option', 'geditorial-bookmarked' ),
				'template' => '{{link}}',
				'cssclass' => '-custom-link',
				'icon'     => 'external',
				'logo'     => '',
			],
			[
				'name'     => 'post',
				'title'    => _x( 'Site Post', 'Type Option', 'geditorial-bookmarked' ),
				'template' => Core\WordPress::getPostShortLink( '{{code}}' ),
				'cssclass' => '-internal-post',
				'icon'     => 'admin-post',
				'logo'     => '',
			],
			[
				'name'     => 'attachment',
				'title'    => _x( 'Site Attachment', 'Type Option', 'geditorial-bookmarked' ),
				'template' => Core\WordPress::getPostShortLink( '{{code}}' ),
				'cssclass' => '-internal-attachment',
				'icon'     => 'media-default',
				'logo'     => '',
			],
			[
				'name'     => 'term',
				'title'    => _x( 'Site Term', 'Type Option', 'geditorial-bookmarked' ),
				'template' => Core\WordPress::getTermShortLink( '{{code}}' ),
				'cssclass' => '-internal-term',
				'icon'     => 'tag',
				'logo'     => '',
			],
			[
				// TODO: move to `NationalLibrary` module
				'name'     => 'nlai',
				'title'    => _x( 'National Library', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://opac.nlai.ir/opac-prod/bibliographic/{{code}}',
				'cssclass' => '-national-library',
				'icon'     => [ 'misc-88', 'nlai.ir' ],
				'logo'     => '',
				'desc'     => _x( 'See the page about this on National Library website.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'goodreads',
				'title'    => _x( 'Goodreads Book', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://www.goodreads.com/book/show/{{code}}',
				'cssclass' => '-goodreads-book',
				'icon'     => [ 'misc-24', 'goodreads' ], // 'amazon',
				'logo'     => $this->_get_link_logo( 'goodreads' ),
				'desc'     => _x( 'More about this on Goodreads network.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'fidibo',
				'title'    => _x( 'Fidibo Book', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://fidibo.com/book/{{code}}',
				'cssclass' => '-fidibo-book',
				'icon'     => [ 'misc-16', 'fidibo' ], // 'location-alt',
				'logo'     => $this->_get_link_logo( 'fidibo' ),
				'desc'     => _x( 'Read this on Fidibo e-book platform.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'taaghche',
				'title'    => _x( 'Taaghche Book', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://taaghche.com/book/{{code}}',
				'cssclass' => '-taaghche-book',
				'icon'     => [ 'misc-512', 'taaghche' ], // 'book-alt',
				'logo'     => $this->_get_link_logo( 'taaghche' ),
				'desc'     => _x( 'Read this on Taaghche e-book platform.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'behkhaan',
				'title'    => _x( 'Behkhaan Profile', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://behkhaan.ir/profile/{{code}}',
				'cssclass' => '-behkhaan-profile',
				'icon'     => [ 'misc-32', 'behkhaan' ], // 'book-alt',
				'logo'     => $this->_get_link_logo( 'behkhaan', 'png' ),
				'desc'     => _x( 'More about this on Behkhaan network.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'neshan',
				'title'    => _x( 'Neshan Map', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://nshn.ir/{{code}}',
				'cssclass' => '-neshan-map',
				'icon'     => [ 'misc-512', 'neshan' ], // 'location-alt',
				'logo'     => $this->_get_link_logo( 'neshan' ),
				'desc'     => _x( 'More about this on Neshan maps.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'balad',
				'title'    => _x( 'Balad Map', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://balad.ir/p/{{code}}',
				'cssclass' => '-balad-map',
				'icon'     => [ 'misc-512', 'balad' ], // 'location-alt',
				'logo'     => $this->_get_link_logo( 'balad' ),
				'desc'     => _x( 'More about this on Balad maps.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'aparat',
				'title'    => _x( 'Aparat Video', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://www.aparat.com/v/{{code}}',
				'cssclass' => '-aparat-video',
				'icon'     => [ 'misc-24', 'aparat' ], // 'video-alt3',
				'logo'     => $this->_get_link_logo( 'aparat' ),
				'desc'     => _x( 'More about this on Aparat video platform.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'youtube',
				'title'    => _x( 'Youtube Video', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'https://www.youtube.com/watch?v={{code}}',
				'cssclass' => '-youtube-video',
				'icon'     => 'youtube',
				'logo'     => $this->_get_link_logo( 'youtube' ),
				'desc'     => _x( 'More about this on Youtube video platform.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'email',
				'title'    => _x( 'Email Address', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'mailto::{{code}}', // @SEE: `Core\HTML::mailto()`
				'cssclass' => '-email-address',
				'icon'     => 'email',
				'logo'     => '',
				'desc'     => _x( 'Contact someone about this via email.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'phone',
				'title'    => _x( 'Phone Number', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'tel::{{code}}', // @SEE: `Core\HTML::tel()`
				'cssclass' => '-phone-number',
				'icon'     => 'phone',
				'logo'     => '',
				'desc'     => _x( 'Contact someone about this via phone.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'mobile',
				'title'    => _x( 'Mobile Number', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'tel::{{code}}', // @SEE: `Core\HTML::tel()`
				'cssclass' => '-mobile-number',
				'icon'     => 'smartphone',
				'logo'     => '',
				'desc'     => _x( 'Contact someone about this via mobile phone.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'sms',
				'title'    => _x( 'SMS Number', 'Type Option', 'geditorial-bookmarked' ),
				'template' => 'sms::{{code}}', // @SEE: `Core\HTML::sanitizeSMSNumber()`
				'cssclass' => '-sms-number',
				'icon'     => 'text',
				'logo'     => '',
				'desc'     => _x( 'Contact someone about this via short message.', 'Type Description', 'geditorial-bookmarked' ),
			],
			[
				'name'     => 'pdf',
				'title'    => _x( 'PDF Document', 'Type Option', 'geditorial-bookmarked' ),
				'template' => '{{link}}',
				'cssclass' => '-pdf-document',
				'icon'     => 'pdf',
				'logo'     => '',
				'desc'     => _x( 'Read more about this as PDF format.', 'Type Description', 'geditorial-bookmarked' ),
			],
		];
	}

	public function after_setup_theme()
	{
		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		$this->filter_self( 'prepped_data', 6, 8 );
		$this->filter( 'subcontent_provide_summary', 4, 8, FALSE, $this->base );
		$this->filter_module( 'audit', 'auto_audit_save_post', 5, 12, 'subcontent' );
		$this->register_shortcode( 'main_shortcode' );

		$this->subcontent_hook__post_tabs( 10 );

		if ( $this->get_setting( 'woocommerce_support' ) )
			$this->_init_woocommerce();

		if ( ! is_admin() )
			return;

		$this->filter_module( 'tabloid', 'post_summaries', 4, 40, 'subcontent' );
	}

	private function _init_woocommerce()
	{
		if ( is_admin() )
			return;

		$this->action( 'single_product_summary', 2, 35, FALSE, 'woocommerce' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields_supported( $this->get_setting_posttypes( 'subcontent' ) );
	}

	public function current_screen( $screen )
	{
		if ( $this->in_setting_posttypes( $screen->post_type, 'subcontent' ) ) {

			if ( 'post' == $screen->base ) {

				if ( $this->role_can( [ 'reports', 'assign' ] ) )
					$this->_hook_general_supportedbox( $screen, NULL, 'advanced', 'low', '-subcontent-grid-metabox' );

				$this->subcontent_do_enqueue_asset_js( $screen );

			} else if ( 'edit' == $screen->base ) {

				if ( $this->role_can( [ 'reports', 'assign' ] ) ) {

					if ( ! $this->rowactions__hook_mainlink_for_post( $screen->post_type, 18, 'subcontent' ) )
						$this->coreadmin__hook_tweaks_column_row( $screen->post_type, 18, 'subcontent' );

					Scripts::enqueueColorBox();
				}
			}
		}
	}

	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		$this->subcontent_do_render_supportedbox_content( $object, $context ?? 'supportedbox' );
	}

	public function admin_menu()
	{
		if ( $this->role_can( [ 'assign', 'reports' ] ) )
			$this->_hook_submenu_adminpage( 'framepage', 'exist' );
	}

	public function load_submenu_adminpage( $context = 'framepage' )
	{
		$this->_load_submenu_adminpage( $context );
		$this->subcontent_do_enqueue_app();
	}

	public function render_framepage_adminpage()
	{
		$this->subcontent_do_render_iframe_content(
			'framepage',
			/* translators: %s: post title */
			_x( 'Bookmarks Grid for %s', 'Page Title', 'geditorial-bookmarked' ),
			/* translators: %s: post title */
			_x( 'Bookmarks Overview for %s', 'Page Title', 'geditorial-bookmarked' )
		);
	}

	public function setup_restapi()
	{
		$this->subcontent_restapi_register_routes();
	}

	public function subcontent_provide_summary( $data, $item, $parent, $context )
	{
		if ( ! is_null( $data ) )
			return $data;

		if ( ! $this->subcontent_is_comment_type( $item ) )
			return $data;

		if ( $link = $this->_generate_link( $item, $parent, $context ) )
			return [
				'title'       => $item['label'] ?? gEditorial\Plugin::na( FALSE ),
				'link'        => Core\HTML::escapeURL( $link ),
				'image'       => $item['logo'] ?? '',
				'description' => $item['desc'] ?? '',
			];

		return $data;
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return $this->subcontent_data_summary( array_merge( [
			'default' => '',
			'echo'    => FALSE,
		], (array) $atts ) );
	}

	public function single_product_summary( $before = '', $after = '' )
	{
		return $this->main_shortcode( [
			'before' => $before,
			'after'  => $after,
			'echo'   => TRUE,
		] );
	}

	public function audit_get_default_terms( $terms, $taxonomy )
	{
		return Helper::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_empty_subcontent_data' ) => _x( 'Empty Bookmarks Data', 'Default Term: Audit', 'geditorial-bookmarked' ),
		] ) : $terms;
	}

	private function _generate_link( $atts, $parent = NULL, $context = NULL )
	{
		// TODO: maybe cache the arrays
		$data = self::atts( array_fill_keys( array_keys( $this->subcontent_define_fields() ), NULL ), $atts );
		$post = WordPress\Post::get( $parent );
		$link = FALSE;

		if ( ! empty( $data['link'] ) ) {

			$link = $data['link'];

		} else if ( ! empty( $data['type'] ) ) {

			$types = Core\Arraay::reKey(
				$this->subcontent_get_type_options( $context, $post ? $post->post_type : NULL ),
				'name'
			);

			if ( array_key_exists( $data['type'], $types ) && ! empty( $types[$data['type']]['template'] ) )
				$link = Core\Text::replaceTokens( $types[$data['type']]['template'], $data );
		}

		return $this->filters( 'generate_link', $link, $data, $post, $context );
	}

	private function _get_link_logo( $key, $ext = NULL, $path = NULL )
	{
		return sprintf( '%s%s%s.%s',
			Core\URL::fromPath( $path ?? $this->path ),
			'data/logos/',
			$key,
			$ext ?? self::const( 'SCRIPT_DEBUG' ) ? 'svg' : 'min.svg'
		);
	}

	public function prepped_data( $list, $context, $post, $data, $types, $selectable )
	{
		$posttype = WordPress\Post::type( $post );
		$options  = Core\Arraay::reKey( $this->subcontent_define_type_options( $context, $posttype ), 'name' );

		foreach ( $data as $key => $row ) {

			$type = empty( $row['type'] ) ? 'default' : $row['type'];

			if ( empty( $row['link'] ) && ! empty( $options[$type]['template'] ) )
				$list[$key]['link'] = Core\Text::replaceTokens( $options[$type]['template'], $list[$key] );
			else
				$list[$key]['link'] = $row['link'];

			if ( empty( $row['desc'] ) && ! empty( $options[$type]['desc'] ) )
				$list[$key]['desc'] = Core\Text::replaceTokens( $options[$type]['desc'], $list[$key] );
			else
				$list[$key]['desc'] = $row['desc'];

			if ( empty( $list[$key]['_icon'] ) && ! empty( $options[$type]['icon'] ) )
				$list[$key]['_icon'] = Helper::getIcon( $options[$type]['icon'] );

			if ( empty( $list[$key]['_logo'] ) && ! empty( $options[$type]['logo'] ) )
				$list[$key]['_logo'] = $options[$type]['logo'];

			$list[$key]['_type_options'] = $options[$type];
		}

		return $list;
	}
}
