<?php namespace geminorum\gEditorial\Modules\Placard;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Placard extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\MetaBoxList;
	use Internals\PostMeta;
	use Internals\TemplateTaxonomy;
	use Internals\ViewEngines;

	private $_templates = [
		'bootstrap-carousel',
	];

	protected $supports = [
		'main_posttype' => [
			'title',
			'excerpt',
			// 'author',
			'editorial-roles',
		],
	];

	public static function module(): array
	{
		return [
			'name'     => 'placard',
			'title'    => _x( 'Placard', 'Modules: Placard', 'geditorial-admin' ),
			'desc'     => _x( 'Banners for Contents', 'Modules: Placard', 'geditorial-admin' ),
			'icon'     => 'images-alt',
			'i18n'     => 'adminonly',
			'access'   => 'beta',
			'keywords' => [
				'banner',
				'has-shortcodes',
				'has-widgets',
				'cptmodule',
				'tabmodule',
			],
		];
	}

	protected function get_global_settings(): array
	{
		$roles = $this->get_settings_default_roles();

		return [
			'_general' => [
				[
					'field'       => 'default_template',
					'type'        => 'select',
					'title'       => _x( 'Default Template', 'Setting Title', 'geditorial-placard' ),
					'description' => _x( 'Defines the the default template for rendering the banners on front-end.', 'Setting Description', 'geditorial-placard' ),
					'values'      => WordPress\Strings::makeLabelsByKeys( $this->_templates ),
					'default'     => $this->_templates[0],
				],
				'tabs_support',
				'shortcode_support',
				'widget_support',
			],
			'_supports' => [
				$this->settings_supports_option( 'main_posttype' ),
			],
			'_frontend' => [
				'tab_title'      => [ NULL, _x( 'Slides', 'Setting Default', 'geditorial-placard' ) ],
				'tab_priority'   => [ NULL, 20 ],
			],
			'_roles' => [
				'custom_captype',
				'reports_roles' => [ NULL, $roles ],
			],
			'_editpost' => [
				'metabox_advanced',
			],
			'_editlist' => [
				'show_in_quickedit' => [ $this->get_taxonomy_show_in_quickedit_desc( 'category_taxonomy' ), '1' ],
			],
			'posttypes_option' => 'posttypes_option',
			'_constants'       => [
				'main_posttype_constant'     => [ NULL, 'content_banner' ],
				'category_taxonomy_constant' => [ NULL, 'content_banner_category' ],
				'main_shortcode_constant'    => [ NULL, 'content-banner' ],
			],
		];
	}

	protected function get_global_constants(): array
	{
		return [
			'main_posttype'     => 'content_banner',
			'category_taxonomy' => 'content_banner_category',
			'type_taxonomy'     => 'content_banner_type',
			'main_shortcode'    => 'content-banners',

			'metakey_rawdata' => '_editorial_rawdata',
		];
	}

	protected function get_global_strings(): array
	{
		$strings = [
			'noops' => [
				'main_posttype'     => _n_noop( 'Content Banner', 'Content Banners', 'geditorial-placard' ),
				'category_taxonomy' => _n_noop( 'Banner Category', 'Banner Categories', 'geditorial-placard' ),
				'type_taxonomy'     => _n_noop( 'Banner Type', 'Banner Types', 'geditorial-placard' ),

				/* translators: `%s`: count number */
				'main_posttype_count' => _n_noop( '%s Banner', '%s Banners', 'geditorial-placard' ),
			],
			'labels' => [
				'main_posttype' => [
					'all_items' => _x( 'Content Banners', 'Posttype Menu-name', 'geditorial-placard' ),
				],
				'category_taxonomy' => [
					'menu_name'            => _x( 'Banner Categories', 'Menu Title', 'geditorial-placard' ),
					'show_option_all'      => _x( 'Categories', 'Label: Show Option All', 'geditorial-placard' ),
					'show_option_no_items' => _x( '(Uncategorized)', 'Label: Show Option No Terms', 'geditorial-placard' ),
				],
				'type_taxonomy' => [
					'menu_name' => _x( 'Banner Types', 'Menu Title', 'geditorial-placard' ),
				],
			],
			'js' => [
				'media' => [
					'modal_title'  => _x( 'Choose a Banner Image', 'JavaScript String', 'geditorial-placard' ),
					'modal_button' => _x( 'Select an Image', 'JavaScript String', 'geditorial-placard' ),
				],
				'orderedlist' => [
					'title_placeholder'   => _x( 'Title', 'JavaScript String', 'geditorial-placard' ),
					'caption_placeholder' => _x( 'Caption', 'JavaScript String', 'geditorial-placard' ),
					'class_placeholder'   => _x( 'CSS Class', 'JavaScript String', 'geditorial-placard' ),
					'url_placeholder'     => _x( 'URL', 'JavaScript String', 'geditorial-placard' ),
				],
			],
		];

		return $strings;
	}

	protected function define_default_terms(): array
	{
		return [
			'category_taxonomy' => [
				'dashboard' => _x( 'Dashboard', 'Main Taxonomy: Default Term', 'geditorial-placard' ),
				'widget'    => _x( 'Widget', 'Main Taxonomy: Default Term', 'geditorial-placard' ),
			],
		];
	}

	public function get_global_fields(): array
	{
		$posttype = $this->constant( 'main_posttype' );

		return [
			'meta' => [
				$posttype => [
					'parent_post_id' => [
						'title'       => _x( 'Parent', 'Field Title', 'geditorial-placard' ),
						'description' => _x( 'Parent Post of the Banners', 'Field Description', 'geditorial-placard' ),
						'type'        => 'parent_post',
						'posttype'    => $this->posttypes(),
					],

					'display_location' => [
						'title'       => _x( 'Display Location', 'Field Title', 'geditorial-placard' ),
						'description' => _x( 'Intended location of the Banners', 'Field Description', 'geditorial-placard' ),
						'type'        => 'context',
					],

					'content_embed_url' => [ 'type' => 'embed' ],
					'text_source_url'   => [ 'type' => 'text_source' ],
					'audio_source_url'  => [ 'type' => 'audio_source' ],
					'video_source_url'  => [ 'type' => 'video_source' ],
					'image_source_url'  => [ 'type' => 'image_source' ],
				],
			],
		];
	}

	protected function posttypes_excluded( string|array $extra = [] ): array
	{
		return $this->filters( 'posttypes_excluded',
			gEditorial\Settings::posttypesExcluded( $extra + [
				$this->constant( 'main_posttype' ),
			], $this->keep_posttypes )
		);
	}

	public function meta_init(): void
	{
		$this->add_posttype_fields_for( 'meta', 'main_posttype' );
	}

	public function widgets_init(): void
	{
		register_widget( __NAMESPACE__.'\\Widgets\\ContentBanners' );
	}

	public function init(): void
	{
		parent::init();

		$captype = $this->get_setting( 'custom_captype', FALSE )
			? $this->constant_plural( 'main_posttype' )
			: FALSE;

		// TODO: header button for menu / filter parent_file
		$this->register_taxonomy( 'category_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => $this->get_setting( 'metabox_advanced' ) ? NULL : FALSE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit' ),
			'show_in_nav_menus'  => FALSE,
		], 'main_posttype', [
			'is_viewable'    => FALSE,
			'custom_captype' => $captype,
		] );

		// TODO: header button for menu / filter parent_file
		$this->register_taxonomy( 'type_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'main_posttype', [
			'is_viewable'     => FALSE,
			'custom_icon'     => 'screenoptions',
			'auto_parents'    => TRUE,
			'admin_managed'   => TRUE,
			'single_selected' => TRUE,
		] );

		$this->register_posttype( 'main_posttype', [
			'hierarchical' => TRUE, // for widget dropdown
			'public'       => FALSE,
			'rewrite'      => FALSE,
			'has_archive'  => FALSE,
			// 'show_in_rest' => FALSE,
			'ical_source'  => FALSE,
			'show_in_menu' => 'themes.php',
		], [
			'is_viewable'      => FALSE,
			'custom_captype'   => $captype,
			'primary_taxonomy' => $this->constant( 'category_taxonomy' ),
		] );

		if ( $this->get_setting( 'tabs_support', TRUE ) )
			$this->_init_post_tabs();

		$this->register_shortcode( 'main_shortcode' );
	}

	private function _init_post_tabs(): bool
	{
		if ( ! gEditorial()->enabled( 'tabs' ) )
			return FALSE;

		add_filter( $this->hook_base( 'tabs', 'builtins_tabs' ),
			function ( $tabs, $posttype ) {

				if ( $this->posttype_supported( $posttype ) )
					$tabs[] = [

						'name'  => $this->classs(),
						'title' => $this->get_setting_fallback( 'tab_title', _x( 'Slides', 'Setting Default', 'geditorial-placard' ) ),

						'viewable' => function ( $post ) {
							return (bool) $this->get_content_banners_by_parent( $post, 'tabs' );
						},

						'callback' => function ( $post ) {

							echo $this->main_shortcode( [
								'parent'  => $post,
								'context' => 'tabs',
								'wrap'    => FALSE,
							], $this->get_notice_for_empty( 'tabs', NULL, FALSE ) );
						},

						'priority' => $this->get_setting( 'tab_priority', 20 ),
					];

				return $tabs;
			}, 10, 2 );

		return TRUE;
	}

	/**
	 * Fires after the current screen has been set.
	 *
	 * @param object $screen
	 * @return void
	 */
	public function current_screen( object $screen ): void
	{
		if ( $this->is_screen_taxonomy( 'type_taxonomy', $screen ) ) {

			$this->_hook_parentfile_for_optionsgeneralphp();
			$this->modulelinks__register_headerbuttons();

		} else if ( $this->is_screen_taxonomy( 'category_taxonomy', $screen ) ) {

			$this->_hook_parentfile_for_optionsgeneralphp();
			$this->modulelinks__register_headerbuttons();

		} else if ( $this->is_screen_posttype( 'main_posttype', $screen ) ) {

			if ( 'edit' === $screen->base ) {

				if ( Services\PostTypeFields::isAvailable( 'parent_post_id', $this->constant( 'main_posttype' ) ) ) {
					$this->corerestrictposts__hook_columnrow_for_parent_post( $screen->post_type, NULL, 'meta', NULL, -10 );
					$this->corerestrictposts__hook_parsequery_for_post_parent( 'main_posttype' );
				}

				$this->modulelinks__register_headerbuttons();
				$this->corerestrictposts__hook_screen_taxonomies( [
					'category_taxonomy',
					'type_taxonomy',
				] );

			} else if ( 'post' === $screen->base ) {

				$this->_hook_store_metabox( $screen->post_type );
				$this->action( 'edit_form_after_title', 1, 12 );

				$this->action_module( 'meta', 'render_metabox', 4, 1 );
				$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
				$this->filter_false_module( 'tweaks', 'metabox_parent' );
				remove_meta_box( 'pageparentdiv', $screen, 'side' );

				$this->posttypes__media_register_headerbutton( 'main_posttype' );
				$this->_hook_post_updated_messages( 'main_posttype' );

				wp_enqueue_media();

				$this->enqueue_asset_js( [
					'strings' => $this->get_strings( 'media', 'js' ),
					'config'  => [
						// 'mimetypes' => $this->_get_source_mimetypes(),
						// 'selector' => $this->classs( 'orderedlist' ),
					],
				], $screen, [
					'jquery',
					'jquery-ui-sortable',
					'media-upload',
				] );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' === $screen->base ) {

				if ( Services\PostTypeFields::isAvailable( 'parent_post_id', $this->constant( 'main_posttype' ) ) ) {
					$this->_hook_children_listbox( $screen, $this->constant( 'main_posttype' ) );
				}

			} else if ( 'edit' === $screen->base ) {

				if ( Services\PostTypeFields::isAvailable( 'parent_post_id', $this->constant( 'main_posttype' ) ) )
					$this->corerestrictposts__hook_columnrow_for_post_children( $screen->post_type, 'main_posttype', NULL, NULL, NULL, -10 );
			}
		}
	}

	public function admin_menu(): void
	{
		$this->_hook_menu_taxonomy( 'category_taxonomy', 'options-general.php' ); // `themes.php`
		$this->_hook_menu_taxonomy( 'type_taxonomy', 'options-general.php' ); // `themes.php`
	}

	private function _fetch_postmeta( int $post_id, mixed $fallback = FALSE ): mixed
	{
		return $this->fetch_postmeta( $post_id, $fallback, $this->constant( 'metakey_rawdata' ) );
	}

	// NOTE: front only!
	private function _get_default_template( ?string $context = NULL ): string
	{
		return $this->filters( 'default_template',
			$this->get_setting( 'default_template', $this->_templates[0] ),
			$this->_templates,
			$context,
		);
	}

	public function get_content_banners_by_parent( mixed $parent ): false|object
	{
		if ( ! $parent = WordPress\Post::get( $parent ) )
			return FALSE;

		if ( ! $this->posttype_supported( $parent->post_type ) )
			return FALSE;

		if ( ! $metakey = Services\PostTypeFields::getPostMetaKey( 'parent_post' ) )
			return FALSE;

		if ( ! $matches = WordPress\PostType::getIDbyMeta( $metakey, $parent->ID, FALSE ) )
			return FALSE;

		$posttype   = $this->constant( 'main_posttype' );
		$acceptable = WordPress\Status::acceptable( $posttype );

		foreach ( $matches as $match ) {

			if ( ! $post = WordPress\Post::get( $match ) )
				continue;

			if ( $posttype !== $post->post_type )
				continue;

			if ( ! in_array( $post->post_status, $acceptable, TRUE ) )
				continue;

			return $post;
		}

		return FALSE;
	}

	public function get_content_banners_by_location( mixed $location ): false|object
	{
		if ( ! $location = Core\Text::force( $location ) )
			return FALSE;

		if ( ! $metakey = Services\PostTypeFields::getPostMetaKey( 'display_location' ) )
			return FALSE;

		if ( ! $matches = WordPress\PostType::getIDbyMeta( $metakey, $location, FALSE ) )
			return FALSE;

		$posttype   = $this->constant( 'main_posttype' );
		$acceptable = WordPress\Status::acceptable( $posttype );

		foreach ( $matches as $match ) {

			if ( ! $post = WordPress\Post::get( $match ) )
				continue;

			if ( $posttype !== $post->post_type )
				continue;

			if ( ! in_array( $post->post_status, $acceptable, TRUE ) )
				continue;

			return $post;
		}

		return FALSE;
	}

	public function edit_form_after_title( object $post ): void
	{
		$this->_render_orderedlist( $post );
	}

	private function _render_orderedlist( object $post, ?string $context = NULL ): bool
	{
		$data    = [];
		$context = $context ?? 'orderedlist';

		if ( ! $view = $this->viewengine__view_by_template( $context, 'admin' ) )
			return Core\HTML::desc( gEditorial\Plugin::wrong( FALSE ) );

		foreach ( $this->_fetch_postmeta( $post->ID, [] ) as $row )
			$data[] = self::parsed( [
				'title'      => '',
				'caption'    => '',
				'class'      => '',
				'url'        => '',
				'attachment' => '',
				'image'      => WordPress\Post::image( $post, $context, 'thumbnail', (int) $row['attachment'] ?? 0 ) ?: '',
			], $row );

		return $this->viewengine__render( $view, [
			'context'  => $context,
			'selector' => $this->classs( $context ),
			'strings'  => $this->get_strings( $context, 'js' ),
			'data'     => $data,
		] );
	}

	public function store_metabox( int $post_id, object $post, bool $update, ?string $context = NULL ): void
	{
		if ( ! $this->is_save_post( $post, 'main_posttype' ) )
			return;

		$context = $context ?? 'orderedlist';
		$parsed  = Core\Arraay::parseInputGroups( self::req( $this->classs( $context ), [] ) );

		foreach ( $parsed as &$item ) {

			if ( ! empty( $item['url'] ) )
				$item['url'] = Core\URL::sanitizeForStorage( $item['url'] );

			if ( ! empty( $item['class'] ) )
				$item['class'] = Core\HTML::prepClass( $item['class'] );
		}

		update_post_meta(
			$post_id,
			$this->constant( 'metakey_rawdata' ),
			$parsed,
		);
	}

	public function meta_render_metabox( object $post, false|array $box, ?array $fields = NULL, ?string $context = NULL ): void
	{
		gEditorial\MetaBox::fieldPostMenuOrder( $post );
	}

	public function main_shortcode( null|string|array $atts = [], ?string $content = NULL, string $tag = '' ): mixed
	{
		$args = WordPress\ShortCode::attributes( [
			'id'       => FALSE,
			'parent'   => get_queried_object_id(),
			'location' => NULL,
			'template' => NULL,
			'status'   => TRUE,                      // check for acceptable status
			'context'  => NULL,
			'wrap'     => TRUE,
			'class'    => '',
			'before'   => '',
			'after'    => '',
		], $atts, $tag ?: $this->constant( 'main_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( $args['id'] && ( ! $post = WordPress\Post::get( $args['id'] ) ) )
			return $content;

		if ( empty( $post ) && $args['location'] && ( ! $post = $this->get_content_banners_by_location( $args['location'] ) ) )
			return $content;

		if ( empty( $post ) && $args['parent'] && ( ! $parent = WordPress\Post::get( $args['parent'] ) ) )
			return $content;

		if ( empty( $post ) && ! empty( $parent ) )
			$post = $this->get_content_banners_by_parent( $parent );

		if ( empty( $post ) )
			return $content;

		if ( $args['status'] && ! in_array( $post->post_status, WordPress\Status::acceptable( $this->constant( 'main_posttype' ), 'display' ), TRUE ) )
			return $content;

		$context = $args['context'] ?? 'summary';

		if ( ! $data = $this->_get_data_for_post( $post, $context ) )
			return $content;

		if ( ! method_exists( $this, 'viewengine__render' ) ) {
			$this->log( 'CRITICAL', 'VIEW ENGINE NOT AVAILABLE' );
			return $content;
		}

		$template = $args['template'] ?? $this->_get_default_template( $context );

		if ( ! $view = $this->viewengine__view_by_template( $template, 'front' ) )
			return $content;

		if ( ! $html = $this->viewengine__render( $view, [
			'context'  => $context,
			'template' => $template,
			'data'     => $data,
			'selector' => $this->classs( $post->ID ),
		], FALSE ) )
			return $content;

		return gEditorial\ShortCode::wrap(
			$html,
			$this->constant( 'main_shortcode' ),
			$args
		);
	}

	public function _get_data_for_post( mixed $post = NULL, ?string $context = NULL, ?string $format = NULL ): false|array
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$first   = TRUE;
		$context = $context ?? 'summary';
		$rawdata = $this->_fetch_postmeta( $post->ID, [] );

		if ( empty( $rawdata ) )
			return FALSE;

		$data = [
			'context'    => $context,
			'items'      => [],
			'indicators' => [],
			'controls'   => [
				'next' => _x( 'Next', 'Carousel Control', 'geditorial-placard' ),
				'prev' => _x( 'Previous', 'Carousel Control', 'geditorial-placard' ),
			],
		];

		foreach ( $rawdata as $offset => $row ) {

			$item = self::parsed( [
				'offset'      => (string) $offset,
				'active'      => $first ? 'active' : '',
				'link_class'  => 'd-block w-100',
				'image_class' => 'd-block w-100 img-fluid',

				'title'      => '',
				'caption'    => '',
				'class'      => '', // item CSS class
				'url'        => '',
				'attachment' => '',
				'image'      => WordPress\Post::image( $post, $context, 'thumbnail', (int) $row['attachment'] ?? 0 ) ?: '',
			], $row );

			$data['items'][]      = $item;
			$data['indicators'][] = [
				'offset' => (string) $offset,
				'active' => $first ? 'active' : '',
				'title'  => $item['title'],
			];

			$first = FALSE;
		}

		return $this->filters( 'data_summary', $data, $post, $context, $format );
	}
}
