<?php namespace geminorum\gEditorial\Modules\Byline;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

class Byline extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\CoreMenuPage;
	use Internals\FramePage;
	use Internals\MetaBoxSupported;
	use Internals\ViewEngines;

	const APP_NAME  = 'assignment-dock';
	const APP_ASSET = '_assignment';

	protected $disable_no_taxonomies = TRUE;

	public static function module()
	{
		return [
			'name'     => 'byline',
			'title'    => _x( 'Byline', 'Modules: Byline', 'geditorial-admin' ),
			'desc'     => _x( 'Indicating the Authors', 'Modules: Byline', 'geditorial-admin' ),
			'icon'     => 'edit',
			'access'   => 'beta',
			'keywords' => [
				'author',
				'person',
				'individual',
				'literature',
				'woocommerce',
			],
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'posttypes_option'  => 'posttypes_option',
			'taxonomies_option' => 'taxonomies_option',
			'_roles'            => [
				'manage_roles'  => [ _x( 'Roles that can manage, edit and delete <strong>relations</strong>.', 'Setting Description', 'geditorial-byline' ), $roles ],
				'reports_roles' => [ NULL, $roles ],
				'reports_post_edit',
				'assign_roles'  => [ NULL, $roles ],
				'assign_post_edit',
			],
			'_supports' => [
				'tabs_support',
				'woocommerce_support',
			],
			'_frontend' => [
				'adminbar_summary',
				[
					'field'       => 'tab_title',
					'type'        => 'text',
					'title'       => _x( 'Tab Title', 'Setting Title', 'geditorial-byline' ),
					'description' => _x( 'Template for the custom byline tab title. Leave empty to use defaults.', 'Setting Description', 'geditorial-byline' ),
					'placeholder' => _x( 'People', 'Setting Default', 'geditorial-byline' ),
				],
				[
					'field'       => 'tab_priority',
					'type'        => 'priority',
					'title'       => _x( 'Tab Priority', 'Setting Title', 'geditorial-byline' ),
					'description' => _x( 'Priority of the custom byline tab.', 'Setting Description', 'geditorial-byline' ),
					'default'     => 20,
				],
			],
			'_constants' => [
				'main_taxonomy_constant' => [ NULL, 'relation' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy'     => 'relation',
			'restapi_attribute' => 'byline',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Relation', 'Relations', 'geditorial-byline' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'extended_label'       => _x( 'Byline Relations', 'Label: `extended_label`', 'geditorial-byline' ),
					'show_option_all'      => _x( 'Byline Relations', 'Label: `show_option_all`', 'geditorial-byline' ),
					'show_option_no_items' => _x( '(Unrelated)', 'Label: `show_option_no_items`', 'geditorial-byline' ),
					'assign_description'   => _x( 'Defines the relation on the byline.', 'Label: `assign_description`', 'geditorial-byline' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['js'] = [
			static::APP_ASSET => [
				'initial'     => _x( 'Search the directory and add as byline to this post.', 'Javascript String', 'geditorial-byline' ),
				'loading'     => _x( 'Loading', 'Javascript String', 'geditorial-byline' ),
				'newname'     => _x( 'New Name …', 'Javascript String', 'geditorial-byline' ),
				'newslug'     => _x( 'Slug', 'Javascript String', 'geditorial-byline' ),
				'placeholder' => _x( 'Type and press enter …', 'Javascript String', 'geditorial-byline' ),
				'search'      => _x( 'Search', 'Javascript String', 'geditorial-byline' ),
				'select'      => _x( '– Select –', 'Javascript String', 'geditorial-byline' ),
				'store'       => _x( 'Save', 'Javascript String', 'geditorial-byline' ),
				'offline'     => _x( 'You are Offline …', 'Javascript String', 'geditorial-byline' ),
				'online'      => _x( 'Back Online …', 'Javascript String', 'geditorial-byline' ),
			],
		];

		$strings['misc'] = [
			'column_icon_title' => _x( 'Byline', 'Misc: `column_icon_title`', 'geditorial-byline' ),
		];

		$strings['metabox'] = [
			'supportedbox_title'  => _x( 'Byline', 'MetaBox Title', 'geditorial-byline' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'mainbutton_title' => _x( 'By-line of %2$s', 'Button Title', 'geditorial-byline' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'mainbutton_text'  => _x( '%1$s Manage %2$s By-line', 'Button Text', 'geditorial-byline' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'heading_title' => _x( 'Byline Report for %1$s', 'Button Title', 'geditorial-byline' ),
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'main_taxonomy' => [
				'author'       => _x( 'Author', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'co_author'    => _x( 'Co-Author', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'editor'       => _x( 'Editor', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'prefacer'     => _x( 'Prefacer', 'Main Taxonomy: Default Term', 'geditorial-byline' ),       // @REF: https://abadis.ir/entofa/prefacer/
				'photographer' => _x( 'Photographer', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'translator'   => _x( 'Translator', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'reporter'     => _x( 'Reporter', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'commentator'  => _x( 'Commentator', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'artist'       => _x( 'Artist', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'co_artist'    => _x( 'Co-Artist', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'illustrator'  => _x( 'Illustrator', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'collector'    => _x( 'Collector', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'curator'      => _x( 'Curator', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'poet'         => _x( 'Poet', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'interviewer'  => _x( 'Interviewer', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'interviewee'  => _x( 'Interviewee', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'director'     => _x( 'Director', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'selector'     => _x( 'Selector', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'conductor'    => _x( 'Conductor', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'speaker'      => _x( 'Speaker', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'critic'       => _x( 'Critic', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'proofreader'  => _x( 'Proofreader', 'Main Taxonomy: Default Term', 'geditorial-byline' ),    // @REF: https://www.flexjobs.com/blog/post/how-to-become-proofreader
			],
		];
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'show_in_menu' => FALSE,
			'public'       => FALSE,
			'rewrite'      => FALSE,
		], FALSE, [
			'custom_icon'   => 'nametag',
			'admin_managed' => $this->role_can( 'manage' ) ? NULL : TRUE,
		] );

		$this->filter( 'searchselect_result_extra_for_term', 3, 12, FALSE, $this->base );
		$this->filter( 'termrelations_supported', 4, 9, FALSE, $this->base );

		if ( $this->get_setting( 'tabs_support', TRUE ) )
			$this->_init_post_tabs();

		if ( $this->get_setting( 'woocommerce_support' ) )
			$this->_init_woocommerce();
	}

	private function _init_post_tabs()
	{
		if ( ! gEditorial()->enabled( 'tabs' ) )
			return FALSE;

		add_filter( $this->hook_base( 'tabs', 'builtins_tabs' ),
			function ( $tabs, $posttype ) {

				if ( $this->posttype_supported( $posttype ) )
					$tabs[] = [

						'name'  => $this->classs(),
						'title' => $this->get_setting_fallback( 'tab_title', _x( 'People', 'Setting Default', 'geditorial-byline' ) ),

						'viewable' => function ( $post ) {
							return (bool) $this->has_byline_for_post( $post, 'tabs' );
						},

						'callback' => function ( $post ) {

							ModuleTemplate::renderDefault( [
								'default'  => $this->get_notice_for_empty( 'tabs', 'empty', FALSE ),
								'template' => 'cardsrow',
								'hidden'   => TRUE,
								'walker'   => [ __NAMESPACE__.'\\ModuleHelper', 'bylineTemplateWalker' ],
							], $post );
						},

						'priority' => $this->get_setting( 'tab_priority', 20 ),
					];

				return $tabs;
			}, 10, 2 );

		return TRUE;
	}

	private function _init_woocommerce()
	{
		if ( is_admin() )
			return;

		$priority = $this->get_setting( 'tab_priority', 12 );

		$this->filter( 'product_tabs', 1, $priority, FALSE, 'woocommerce' );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'users.php' );
			$this->modulelinks__register_headerbuttons();

		} else if ( $this->taxonomy_supported( $screen->taxonomy ) ) {

			$this->register_headerbutton_for_taxonomy( 'main_taxonomy' );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' === $screen->base ) {

				if ( $this->role_can( [ 'reports', 'assign' ] ) )
					$this->_hook_general_supportedbox( $screen );

				if ( $this->role_can( 'assign' ) )
					$this->enqueue_asset_js( [
						'route' => WordPress\Post::getRestRoute(),
						'attr'  => $this->constant( 'restapi_attribute' ),
					], $screen, [
						'jquery',
						'wp-api-request',
						Scripts::enqueueColorBox(),
					] );

				if ( $this->role_can( 'reports' ) )
					$this->_register_header_button();

			} else if ( 'edit' == $screen->base ) {

				if ( $this->role_can( [ 'reports', 'assign' ] ) )
					$this->_hook_tweaks_column( $screen );
			}
		}
	}

	public function admin_menu()
	{
		if ( $this->role_can( [ 'assign', 'reports' ] ) )
			$this->_hook_submenu_adminpage( 'framepage', 'exist' );

		if ( $this->role_can( [ 'manage' ] ) )
			$this->_hook_menu_taxonomy( 'main_taxonomy', 'users.php' );
	}

	public function load_submenu_adminpage( $context = 'framepage' )
	{
		$this->_load_submenu_adminpage( $context );

		$target = self::req( 'target', 'mainapp' );

		if ( 'mainapp' === $target )
			$this->_do_enqueue_app();

		else if ( 'summaryreport' === $target && $this->role_can( 'reports' ) )
			$this->action( 'admin_print_styles', 0, 99, 'summaryreport' );
	}

	private function _do_enqueue_app( $atts = [] )
	{
		$args = self::atts( [
			'app'     => static::APP_NAME,
			'asset'   => static::APP_ASSET,
			'can'     => 'assign',
			'linked'  => NULL,
			'targets' => $this->list_taxonomies(),
			'context' => 'edit',
			'strings' => [],
		], $atts );

		if ( ! $this->role_can( $args['can'] ) || empty( $args['targets'] ) )
			return;

		if ( ! $linked = $args['linked'] ?? self::req( 'linked', FALSE ) )
			return;

		if ( ! $post = WordPress\Post::get( $linked ) )
			return;

		$routes = [];

		foreach ( $args['targets'] as $target => $label )
			if ( WordPress\Taxonomy::can( $target, 'manage_terms' ) )
				$routes[$target] = WordPress\Taxonomy::getRestRoute( $target );

		$asset = [
			'strings' => $this->get_strings( $args['asset'], 'js', $args['strings'] ),
			'fields'  => $this->_get_supported_fields( $args['context'], $post->post_type ),
			'linked'  => [
				'id'      => $post->ID,
				'text'    => WordPress\Post::title( $post ),
				'rest'    => WordPress\Post::getRestRoute( $post ),
				'base'    => WordPress\PostType::getRestRoute( $post ),
				'extra'   => Services\SearchSelect::getExtraForPost( $post, [ 'context' => $args['asset'] ], WordPress\Post::summary( $post, 'edit' ) ),
				'image'   => Services\SearchSelect::getImageForPost( $post, [ 'context' => $args['asset'] ] ),
				'related' => Services\TermRelations::POSTTYPE_ATTR,
			],
			'config' => [
				'linked'       => $linked,
				'context'      => $args['context'],
				'targets'      => array_keys( $args['targets'] ),
				'labels'       => $args['targets'],
				'routes'       => $routes,
				'searchselect' => Services\SearchSelect::namespace(),
				// 'discovery'    => Services\LineDiscovery::namespace(), // NOT USED YET
				'hints'        => Services\ObjectHints::namespace(),
				'attribute'    => $this->constant( 'restapi_attribute' ),
				'perpage'      => 5,
			],
		];

		$this->enqueue_asset_js( $asset, FALSE, [
			Scripts::enqueueApp( $args['app'] )
		], $args['asset'] );
	}

	public function render_framepage_adminpage( $context )
	{
		if ( ! $post = self::req( 'linked' ) )
			return Info::renderNoPostsAvailable();

		if ( ! $post = WordPress\Post::get( $post ) )
			return Info::renderNoPostsAvailable();

		$target = self::req( 'target', 'mainapp' );

		if ( $this->role_can( 'assign' ) && 'mainapp' === $target ) {

			/* translators: `%s`: post title */
			$assign_template = _x( 'Byline Dock for %s', 'Page Title', 'geditorial-byline' );

			Settings::wrapOpen( $this->key, $context, sprintf( $assign_template ?? '%s', WordPress\Post::title( $post ) ) );

				Scripts::renderAppMounter( static::APP_NAME, $this->key );
				Scripts::noScriptMessage();

			Settings::wrapClose( FALSE );

		} else if ( $this->role_can( 'reports' ) && 'summaryreport' === $target ) {

			/* translators: `%s`: post title */
			$reports_template = _x( 'Byline Overview for %s', 'Page Title', 'geditorial-byline' );

			Settings::wrapOpen( $this->key, $context, sprintf( $reports_template ?? '%s', WordPress\Post::title( $post ) ) );

				ModuleTemplate::renderDefault( [
					'default'  => $this->get_notice_for_empty( $target, 'empty', FALSE ),
					'template' => 'cardsrow',
					'hidden'   => TRUE,
					'walker'   => [ __NAMESPACE__.'\\ModuleHelper', 'bylineTemplateWalker' ],
				], $post );

			Settings::wrapClose( FALSE );

		} else {

			Core\HTML::desc( gEditorial\Plugin::denied( FALSE ), TRUE, '-denied' );
		}
	}

	public function admin_print_styles_summaryreport()
	{
		Scripts::linkBootstrap5();
	}

	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		echo Core\HTML::tag( 'div', [
			'id'    => $this->classs( 'rendered' ),
			'class' => [ 'field-wrap', '-text-preview', '-byline' ],
			'data'  => [ 'empty' => _x( 'The Byline is Empty', 'Message', 'geditorial-byline' ) ],
		], $this->get_byline_for_post( $object ) );

		if ( $this->role_can( 'assign' ) )
			echo Core\HTML::wrap( $this->framepage_get_mainlink_for_post( $object, [
				'context'  => 'mainbutton',
				'target'   => 'mainapp', // OR: `summaryreport`
				'maxwidth' => '800px',
			] ), 'field-wrap -buttons' );
	}

	public function searchselect_result_extra_for_term( $data, $term, $queried )
	{
		if ( empty( $queried['context'] )
			|| in_array( $queried['context'], [ 'select2', 'pairedimports' ], TRUE ) )
			return $data;

		if ( ! $term = WordPress\Term::get( $term ) )
			return $data;

		if ( $this->taxonomy_supported( $term->taxonomy ) )
			return WordPress\Term::summary( $term );

		return $data;
	}

	public function termrelations_supported( $fields, $taxonomy, $context, $posttype )
	{
		if ( $this->taxonomy_supported( $taxonomy ) )
			return array_merge( $fields, $this->_get_supported_fields( $context, $posttype ) );

		return $fields;
	}

	private function _get_registered_relations()
	{
		return $this->filters( 'registered_relations',
			Core\Arraay::pluck(
				WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ), 'all', [], FALSE ),
				'name',
				'slug'
			)
		);
	}

	private function _get_supported_fields( $context = NULL, $posttype = FALSe )
	{
		return $this->filters( 'supported_fields', [

			// OLD: `rel`
			'relation' => [
				'title'   => _x( 'Relation', 'Field Title', 'geditorial-byline' ),
				'desc'    => _x( 'Subject of relation to the post.', 'Field Description', 'geditorial-byline' ),
				'type'    => 'string',
				'default' => '',
				// TODO: use `Terms` module `posttypes` meta to filter by
				'options' => $this->_get_registered_relations( $context, $posttype ),
			],

			'filter' => [
				'title'   => _x( 'Filter', 'Field Title', 'geditorial-byline' ),
				/* translators: do not translate `%s` */
				'desc'    => _x( 'String to use before or with `%s` as name.', 'Field Description', 'geditorial-byline' ),
				'type'    => 'string',
				'default' => '',
			],

			// OLD: `override`
			'overwrite' => [
				'title'   => _x( 'Overwrite', 'Field Title', 'geditorial-byline' ),
				'desc'    => _x( 'String to override the name.', 'Field Description', 'geditorial-byline' ),
				'type'    => 'string',
				'default' => '',
			],

			'notes' => [
				'title'   => _x( 'Notes', 'Field Title', 'geditorial-byline' ),
				'desc'    => _x( 'About relation to the post.', 'Field Description', 'geditorial-byline' ),
				'type'    => 'string',
				'default' => '',
			],

			// OLD: `feat`
			'featured' => [
				'title'   => _x( 'Featured', 'Field Title', 'geditorial-byline' ),
				'desc'    => _x( 'Features the individual on the extended list.', 'Field Description', 'geditorial-byline' ),
				'type'    => 'boolean',
				'default' => FALSE,
			],

			// OLD: `visibility`
			'hidden' => [
				'title'   => _x( 'Hidden', 'Field Title', 'geditorial-byline' ),
				'desc'    => _x( 'Hides the name from byline but assignes the term.', 'Field Description', 'geditorial-byline' ),
				'type'    => 'boolean',
				'default' => FALSE,
			],
		], $context, $posttype );
	}

	public function setup_restapi()
	{
		$this->_register_rest_fields();
	}

	private function _register_rest_fields()
	{
		$this->filter( 'rest_terms_rendered_html', 4, 8, FALSE, 'gnetwork' );

		register_rest_field(
			$this->posttypes(),
			$this->constant( 'restapi_attribute' ),
			[
				'get_callback' => function ( $post, $attr, $request, $object_type ) {
					return $this->get_byline_for_post( (int) $post['id'] );
				}
			]
		);
	}

	// @FILTER: `gnetwork_rest_terms_rendered_html`
	public function rest_terms_rendered_html( $html, $taxonomy, $post, $object_type )
	{
		return $this->taxonomy_supported( $taxonomy->name )
			? $this->get_byline_for_post( $post['id'] )
			: $html;
	}

	// NOTE: checks for assigned supported terms to the post
	public function has_byline_for_post( $post = NULL, $context = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		foreach ( $this->taxonomies() as $supported ) {

			// hits the cache
			if ( ! $terms = get_the_terms( $post, $supported ) )
				continue;

			if ( count( $terms ) && ! self::isError( $terms ) )
				return TRUE;
		}

		return FALSE;
	}

	public function get_byline_for_post( $post = NULL, $atts = [], $fallback = '' )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return $fallback;

		// NOTE: already ordered!
		$terms = wp_get_object_terms( $post->ID, $this->taxonomies() );

		if ( empty( $terms ) || self::isError( $terms ) )
			return $fallback;

		$rows   = [];
		$fields = $this->_get_supported_fields( 'display', $post->post_type );

		$atts = array_merge( [
			'default' => $fallback,
		], $atts, [
			'post'       => $post,
			'_fields'    => $fields,
			'_relations' => empty( $fields['relation']['options'] ) ? [] : $fields['relation']['options'],
			'_legacy'    => Core\Arraay::pluck( get_post_meta( $post->ID, ModuleSettings::METAKEY_FROM_PEOPLE_PLUGIN, TRUE ), 'id' ),
		] );

		if ( empty( $atts['walker'] ) )
			$atts['walker'] = [ __NAMESPACE__.'\\ModuleHelper', 'bylineDefaultWalker' ];

		foreach ( $terms as $term )
			if ( FALSE !== ( $relation = $this->get_term_relations( $term, $post, $fields, $atts['_legacy'] ) ) )
				$rows[] = compact( 'term', 'relation' );

		return call_user_func_array( $atts['walker'], [ $rows, $atts ] );
	}

	public function get_term_relations( $term, $post, $fields, $legacy = [] )
	{
		$relation = [];
		$meta     = WordPress\Term::getMeta( $term );
		$fallback = array_key_exists( $term->term_id, $legacy ) ? $legacy[$term->term_id] : [];

		foreach ( $fields as $field => $args ) {

			$metakey = Services\TermRelations::getMetakey( $field, $post->ID, 'post' );

			if ( ! empty( $meta[$metakey] ) ) {

				$relation[$field] = $meta[$metakey];

			} else if ( ! empty( $fallback[$field] ) ) {

				$relation[$field] = $fallback[$field];

			} else if ( 'relation' === $field
				&& ! empty( $fallback['rel'] ) ) {

				$relation[$field] = $fallback['rel'];

			} else if ( 'overwrite' === $field
				&& ! empty( $fallback['override'] ) ) {

				$relation[$field] = $fallback['override'];

			} else if ( 'featured' === $field
				&& ! empty( $fallback['feat'] ) ) {

				$relation[$field] = $fallback['feat'];

			} else if ( 'hidden' === $field
				&& ! empty( $fallback['vis'] )
				&& in_array( $fallback['vis'], [ 'hidden', 'none' ], TRUE ) ) {

				$relation[$field] = TRUE;
			}
		}

		if ( ! empty( $fallback['feat'] ) )
			$relation['featured'] = $fallback['feat'];

		if ( ! empty( $fallback['temp'] ) )
			$relation['_temp'] = $fallback['temp'];

		return $this->filters( 'term_relations', $relation, $term, $post, $fields, $legacy );
	}

	public function product_tabs( $tabs )
	{
		global $product;

		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) )
			return $tabs;

		$post_id = $product->get_id();

		if ( ! $this->has_byline_for_post( $post_id, 'woocommerce' ) )
			return $tabs;

		return array_merge( $tabs, [
			$this->key => [
				'title'    => $this->get_setting_fallback( 'tab_title', _x( 'People', 'Setting Default', 'geditorial-byline' ) ),
				'callback' => function () use ( $post_id ) {
					ModuleTemplate::renderDefault( [
						'default'  => $this->get_notice_for_empty( 'woocommerce', 'empty', FALSE ),
						'template' => 'cardsrow',
						'hidden'   => TRUE,
						'walker'   => [ __NAMESPACE__.'\\ModuleHelper', 'bylineTemplateWalker' ],
					], $post_id );
				},
			],
		] );
	}

	// NOTE: check for access before
	private function _register_header_button( $post = NULL, $target = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$button = Services\HeaderButtons::register( $this->key, [
			'text'     => _x( 'Byline Report', 'Header Button', 'geditorial-byline' ),
			'title'    => $this->strings_metabox_title_via_posttype( $post->post_type, 'heading', NULL, $post ),
			'link'     => $this->framepage_get_mainlink_url( $post->ID, $target ?? 'summaryreport' ),
			'icon'     => $this->module->icon,
			'priority' => 79,
			'newtab'   => TRUE,
			'class'    => 'do-colorbox-iframe',
			'data'     => [
				'module'     => $this->key,
				'linked'     => $post->ID,
				'target'     => 'summaryreport',
				'max-width'  => '800',
				// 'max-height' => '310',
			],
		] );

		Scripts::enqueueColorBox();

		return $button;
	}

	private function _hook_tweaks_column( $screen )
	{
		add_action( $this->hook_base( 'tweaks', 'column_row', $screen->post_type ),
			function ( $post, $before, $after, $module ) {

				if ( $this->role_can_post( $post, 'assign' ) )
					$edit = $this->framepage_get_mainlink_url( $post->ID, 'mainapp' );

				else if ( $this->role_can_post( $post, 'reports' ) )
					$edit = $this->framepage_get_mainlink_url( $post->ID, 'summaryreport' );

				else if ( ! WordPress\Post::can( $post, 'read_post' ) )
					return;

				$icon = $this->get_column_icon( $edit ?? FALSE, NULL, NULL, $post->post_type, 'do-colorbox-iframe' );

				echo $this->get_byline_for_post( $post, [
					'before' => sprintf( $before, '-post-byline' ).$icon,
					'after'  => $after,
				] );

			}, -4, 4 );

		Scripts::enqueueColorBox();

		return TRUE;
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() || ! is_singular( $this->posttypes() ) )
			return;

		$post_id = get_queried_object_id();
		$classs  = $this->classs();
		$assign  = $this->role_can( 'assign' );
		$reports = $this->role_can( 'reports' );

		if ( ! $reports && ! $assign )
			return;

		$nodes[] = [
			'parent' => $parent,
			'id'     => $classs,
			'title'  => _x( 'Byline', 'Adminbar', 'geditorial-byline' ),
			'href'   => $reports ? $this->get_module_url( 'reports' ) : FALSE,
		];

		$nodes[] = [
			'parent' => $classs,
			'id'     => $classs.'-rendered',
			'title'  => $this->get_byline_for_post( $post_id, [ 'link' => FALSE ], Helper::htmlEmpty() ),
			'href'   => $this->framepage_get_mainlink_url( $post_id, $assign ? 'mainapp' : 'summaryreport' ),
			'meta' => [
				'class' => 'do-colorbox-iframe-for-child',
				'title' => _x( 'Byline', 'Adminbar', 'geditorial-byline' ),
			],
		];

		Scripts::enqueueColorBox();
	}

	public function imports_settings( $sub )
	{
		$this->check_settings( $sub, 'imports', 'per_page' );
	}

	protected function render_imports_html( $uri, $sub )
	{
		echo ModuleSettings::toolboxColumnOpen( _x( 'Byline Imports', 'Header', 'geditorial-byline' ) );

		$available = FALSE;
		$posttypes = $this->list_posttypes();

		if ( ModuleSettings::renderCard_import_from_people_plugin( $posttypes ) )
			$available = TRUE;

		if ( ! $available )
			Info::renderNoImportsAvailable();

		echo '</div>';
	}

	protected function render_imports_html_before( $uri, $sub )
	{
		if ( $this->_do_import_from_people_plugin( $sub ) )
			return FALSE; // avoid further UI
	}

	private function _do_import_from_people_plugin( $sub )
	{
		if ( ! self::do( ModuleSettings::ACTION_FROM_PEOPLE_PLUGIN ) )
			return FALSE;

		if ( ! $posttype = self::req( 'type' ) )
			return Info::renderEmptyPosttype();

		if ( ! $this->posttype_supported( $posttype ) )
			return Info::renderNotSupportedPosttype();

		$this->raise_resources();

		return ModuleSettings::handleImport_from_people_plugin(
			$posttype,
			$this->get_sub_limit_option( $sub )
		);
	}
}
