<?php namespace geminorum\gEditorial\Modules\Bookmarked;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
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
	use Internals\ViewEngines;

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
				'tabmodule',
			],
		];
	}

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
				'reports_post_edit',
				'assign_roles'  => [ NULL, $roles ],
				'assign_post_edit',
			],
			'_editpost' => [
				'admin_rowactions',
			],
			'_frontend' => [
				'tabs_support',
				'tab_title'    => [ NULL, $this->strings['frontend']['tab_title'] ],
				'tab_priority' => [ NULL, 80 ],
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
			'noaccess' => _x( 'You do not have the necessary permission to manage the bookmarks data.', 'Notice', 'geditorial-bookmarked' ),
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'supportedbox_title'  => _x( 'Bookmarks', 'MetaBox Title', 'geditorial-bookmarked' ),
			// 'metabox_action' => _x( 'Bookmarks', 'MetaBox Action', 'geditorial-bookmarked' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'mainbutton_title' => _x( 'Bookmarks of %1$s', 'Button Title', 'geditorial-bookmarked' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'mainbutton_text'  => _x( '%1$s Manage the Bookmarks of %2$s', 'Button Text', 'geditorial-bookmarked' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'rowaction_title' => _x( 'Bookmarks of %1$s', 'Action Title', 'geditorial-bookmarked' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'rowaction_text'  => _x( 'Bookmarks', 'Action Text', 'geditorial-bookmarked' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'columnrow_title' => _x( 'Bookmarks of %1$s', 'Row Title', 'geditorial-bookmarked' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
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
						'order'       => 1400,
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

	protected function subcontent_define_type_options( $context, $posttype = NULL )
	{
		return ModuleHelper::getTypeOptions( $context );
	}

	public function after_setup_theme()
	{
		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		$this->filter_self( 'prepped_data', 5, 8 );
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

					gEditorial\Scripts::enqueueColorBox();
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
			$this->_hook_submenu_adminpage( 'overview', 'exist' );
	}

	public function load_submenu_adminpage()
	{
		$this->_load_submenu_adminpage( 'overview' );
		$this->subcontent_do_enqueue_app();
	}

	public function render_submenu_adminpage()
	{
		$this->subcontent_do_render_iframe_content(
			'overview',
			/* translators: `%s`: post title */
			_x( 'Bookmarks Grid for %s', 'Page Title', 'geditorial-bookmarked' ),
			/* translators: `%s`: post title */
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
		return Services\Modulation::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_empty_subcontent_data' ) => _x( 'Empty Bookmarks Data', 'Default Term: Audit', 'geditorial-bookmarked' ),
		] ) : $terms;
	}

	// TODO: support: `Core\Third::getHandleURL()`
	private function _generate_link( $atts, $parent = NULL, $context = NULL )
	{
		// TODO: maybe cache the arrays
		$data = self::atts( array_fill_keys( array_keys( $this->subcontent_define_fields() ), NULL ), $atts );
		$post = WordPress\Post::get( $parent );
		$link = FALSE;

		// NOTE: extra tokens
		$data['_iso639'] = Core\L10n::getISO639();

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

	public function prepped_data( $data, $context, $post, $raw, $types )
	{
		if ( in_array( $context, [ 'summary', 'tabs' ] ) )
			return ModuleHelper::prepDataForSummary(
				$data,
				Core\Arraay::reKey( $this->subcontent_define_type_options( $context, WordPress\Post::type( $post ) ), 'name' ),
				$context
			);

		return $data;
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports', TRUE );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->subcontent_reports_render_table( $uri, $sub, 'reports', _x( 'Overview of the Bookmarks', 'Header', 'geditorial-bookmarked' ) ) )
			return gEditorial\Info::renderNoReportsAvailable();
	}
}
