<?php namespace geminorum\gEditorial\Modules\Byline;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
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

	protected $disable_no_taxonomies  = TRUE;
	protected $priority_adminbar_init = 6;

	public static function module()
	{
		return [
			'name'     => 'byline',
			'title'    => _x( 'Byline', 'Modules: Byline', 'geditorial-admin' ),
			'desc'     => _x( 'Indicating the Authors', 'Modules: Byline', 'geditorial-admin' ),
			'icon'     => 'welcome-write-blog',
			'access'   => 'beta',
			'keywords' => [
				'author',
				'person',
				'individual',
				'literature',
				'woocommerce',
				'has-adminbar',
				'tabmodule',
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
				'imports_roles' => [ NULL, $roles ],
				'reports_roles' => [ NULL, $roles ],
				'reports_post_edit',
				'assign_roles'  => [ NULL, $roles ],
				'assign_post_edit',
			],
			'_supports' => [
				'tabs_support',
				'woocommerce_support',
				'shortcode_support',
				'widget_support',
			],
			'_frontend' => [
				'adminbar_summary',
				'adminbar_tools' => [ NULL, TRUE ],
				'tab_title'      => [ NULL, _x( 'People', 'Setting Default', 'geditorial-byline' ) ],
				'tab_priority'   => [ NULL, 20 ],
			],
			'_constants' => [
				'main_taxonomy_constant'  => [ NULL, 'relation' ],
				'main_shortcode_constant' => [ NULL, 'byline' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy'     => 'relation',
			'main_shortcode'    => 'byline',
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
				'translator'   => _x( 'Translator', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'reporter'     => _x( 'Reporter', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'editor'       => _x( 'Editor', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'prefacer'     => _x( 'Prefacer', 'Main Taxonomy: Default Term', 'geditorial-byline' ),       // @REF: https://abadis.ir/entofa/prefacer/
				'subject'      => _x( 'Subject', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'guest'        => _x( 'Guest', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'host'         => _x( 'Host', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
				'photographer' => _x( 'Photographer', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
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
				'co_author'    => _x( 'Co-Author', 'Main Taxonomy: Default Term', 'geditorial-byline' ),
			],
		];
	}

	public function widgets_init()
	{
		register_widget( __NAMESPACE__.'\\Widgets\\FeaturedCards' );
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

		$this->register_shortcode( 'main_shortcode' );

		$this->filter( 'searchselect_result_extra_for_term', 3, 12, FALSE, $this->base );
		$this->filter( 'termrelations_supported', 4, 9, FALSE, $this->base );
		$this->filter( 'objecthints_tips_for_post', 5, 12, FALSE, $this->base );
		$this->filter( 'meta_summary_rows', 4, 12, FALSE, $this->base );

		if ( $this->get_setting( 'tabs_support', TRUE ) )
			$this->_init_post_tabs();

		if ( $this->get_setting( 'woocommerce_support' ) )
			$this->_init_woocommerce();

		if ( is_admin() ) {

			$this->filter( 'taxonomy_exclude_empty', 1, 10, FALSE, 'gnetwork' );

		} else {

			if ( $this->get_setting( 'adminbar_tools', TRUE ) )
				$this->action( 'admin_bar_menu', 1, -999 );
		}
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
							return (bool) $this->has_content_for_post( $post, 'tabs' );
						},

						'callback' => function ( $post ) {

							ModuleTemplate::renderDefault( [
								'default'  => $this->get_notice_for_empty( 'tabs', NULL, FALSE ),
								'template' => 'featuredcards',
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

		$this->filter( 'product_tabs', 1, $this->get_setting( 'tab_priority', 12 ), FALSE, 'woocommerce' );
	}

	public function current_screen( $screen )
	{
		if ( 'users' == $screen->base ) {

			$this->filter( 'pre_count_many_users_posts', 2 );

		} else if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'users.php' );
			$this->modulelinks__register_headerbuttons();

		} else if ( $this->taxonomy_supported( $screen->taxonomy ) ) {

			$this->register_headerbutton_for_taxonomy( 'main_taxonomy' );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' === $screen->base ) {

				if ( $this->role_can( [ 'reports', 'assign' ] ) ) {
					$this->_hook_general_supportedbox( $screen );
					gEditorial\Scripts::enqueueColorBox();
				}

				if ( $this->role_can( 'reports' ) )
					$this->_register_header_button();

			} else if ( 'edit' == $screen->base ) {

				if ( $this->role_can( [ 'reports', 'assign' ] ) ) {
					$this->_hook_tweaks_column( $screen );
					gEditorial\Scripts::enqueueColorBox();
				}
			}
		}
	}

	public function admin_menu()
	{
		if ( $this->role_can( [ 'assign', 'reports' ] ) )
			$this->_hook_submenu_adminpage( 'overview', 'exist' );

		if ( $this->role_can( [ 'manage' ] ) )
			$this->_hook_menu_taxonomy( 'main_taxonomy', 'users.php' );
	}

	public function load_overview_adminpage()
	{
		$this->_load_submenu_adminpage( 'overview' );

		$target = self::req( 'target', 'mainapp' );

		if ( 'mainapp' === $target )
			$this->_do_enqueue_app();

		else if ( 'summaryreport' === $target && $this->role_can( 'reports' ) )
			$this->action( 'admin_print_styles', 0, 99, 'summaryreport' );
	}

	private function _do_enqueue_app( $atts = [] )
	{
		$args = self::atts( [
			'app'     => defined( 'self::APP_NAME' ) ? constant( 'self::APP_NAME' ) : 'assignment-dock',
			'asset'   => defined( 'self::APP_ASSET' ) ? constant( 'self::APP_ASSET' ) : '_assignment',
			'can'     => 'assign',
			'linked'  => NULL,
			'targets' => $this->list_taxonomies(),
			'summary' => $this->constant( 'restapi_attribute' ),
			'context' => 'edit',
			'strings' => [],
		], $atts );

		if ( ! $this->role_can( $args['can'] ) || empty( $args['targets'] ) )
			return;

		if ( ! $linked = $args['linked'] ?? self::req( 'linked', FALSE ) )
			return;

		if ( ! $post = WordPress\Post::get( $linked ) )
			return;

		$targets = $routes = [];

		foreach ( $args['targets'] as $target => $label ) {

			if ( WordPress\Taxonomy::can( $target, 'manage_terms' ) )
				$routes[$target] = WordPress\Taxonomy::getRestRoute( $target );

			if ( WordPress\Taxonomy::can( $target, 'assign_terms' ) )
				$targets[$target] = $label;
		}

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
				'targets'      => array_keys( $targets ),
				'labels'       => $targets,
				'routes'       => $routes,
				'searchselect' => Services\SearchSelect::namespace(),
				// 'discovery'    => Services\LineDiscovery::namespace(), // NOT USED YET
				'hints'        => Services\ObjectHints::namespace(),
				'summary'      => $args['summary'],
				'perpage'      => 5,
			],
		];

		$this->enqueue_asset_js( $asset, FALSE, [
			gEditorial\Scripts::enqueueApp( $args['app'] )
		], $args['asset'] );
	}

	public function render_submenu_adminpage()
	{
		if ( ! $post = self::req( 'linked' ) )
			return gEditorial\Info::renderNoPostsAvailable();

		if ( ! $post = WordPress\Post::get( $post ) )
			return gEditorial\Info::renderNoPostsAvailable();

		$target = self::req( 'target', 'mainapp' );

		if ( $this->role_can_post( $post, 'assign' ) && 'mainapp' === $target ) {

			/* translators: `%s`: post title */
			$assign_template = _x( 'Byline Dock for %s', 'Page Title', 'geditorial-byline' );

			gEditorial\Settings::wrapOpen( 'overview', $this->key, sprintf( $assign_template ?? '%s', WordPress\Post::title( $post ) ) );

				gEditorial\Scripts::renderAppMounter( static::APP_NAME, $this->key );
				gEditorial\Scripts::noScriptMessage();

			gEditorial\Settings::wrapClose( FALSE );

		} else if ( $this->role_can_post( $post, 'reports' ) && 'summaryreport' === $target ) {

			/* translators: `%s`: post title */
			$reports_template = _x( 'Byline Overview for %s', 'Page Title', 'geditorial-byline' );

			gEditorial\Settings::wrapOpen( 'overview', $this->key, sprintf( $reports_template ?? '%s', WordPress\Post::title( $post ) ) );

				ModuleTemplate::renderDefault( [
					'default'  => $this->get_notice_for_empty( $target, NULL, FALSE ),
					'template' => 'adminoverview',
					'hidden'   => TRUE,
					'walker'   => [ __NAMESPACE__.'\\ModuleHelper', 'bylineTemplateWalker' ],
				], $post );

			gEditorial\Settings::wrapClose( FALSE );

		} else {

			gEditorial\Settings::wrapOpen( 'overview', $this->key, gEditorial\Plugin::denied( FALSE ) );

				Core\HTML::dieMessage( $this->get_notice_for_noaccess() );

			gEditorial\Settings::wrapClose( FALSE );
		}
	}

	public function admin_print_styles_summaryreport()
	{
		gEditorial\Scripts::linkBootstrap5();
	}

	/**
	 * Return empty counts for `count_users_many_posts()`, to bypass the heavy
	 * and unused query results.
	 * @source https://github.com/Automattic/Co-Authors-Plus/pull/1098/files
	 * @ticket https://core.trac.wordpress.org/ticket/63004
	 *
	 * @param array $counts
	 * @param array $user_ids
	 * @return array
	 */
	public function pre_count_many_users_posts( $counts, $user_ids )
	{
		return array_fill_keys( array_map( 'absint', $user_ids ), 0 );
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
				'context'      => 'mainbutton',
				'link_context' => 'overview',
				'target'       => 'mainapp', // OR: `summaryreport`
				'maxwidth'     => '800px',
				'refresh'      => $this->constant( 'restapi_attribute' ),
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

	public function meta_summary_rows( $rows, $list, $post, $args )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $rows;

		$raw = $this->get_byline_for_post( $post, [
			'context' => 'rawdata',
			'default'  => FALSE,
			'hidden'   => TRUE,
			'walker'   => [ __NAMESPACE__.'\\ModuleHelper', 'bylineTemplateWalker' ],
		], FALSE );

		if ( empty( $raw ) )
			return $rows;

		$data = [];

		foreach ( $raw as $order => $row ) {

			$key = vsprintf( '<span class="-%s" data-order="%d">%s</span>', [
				$row['rel'],
				$order,
				$row['relation'],
			] );

			$value = Core\HTML::tag( 'a', [
				'class' => '-byline-item',
				'href'  => $row['link'],
				'title' => Core\Text::stripTags( $row['desc'] ),
				'data' => [
					'taxonomy' => $row['tax'] ?: FALSE,
					'relation' => $row['rel'] ?: FALSE,
				],
			], $row['label'] );

			if ( ! empty( $row['notes'] ) )
				$value.= sprintf(
					' %s <span class="-byline-notes text-muted">%s</span>',
					'<span class="-separator">&mdash;</span>',
					WordPress\Strings::prepDescription( $row['notes'], FALSE, FALSE )
				);

			$data[$key] = $value;
		}

		return $data + $rows;
	}

	public function termrelations_supported( $fields, $taxonomy, $context, $posttype )
	{
		if ( $this->taxonomy_supported( $taxonomy ) )
			return array_merge( $fields, $this->_get_supported_fields( $context, $posttype ) );

		return $fields;
	}

	public function objecthints_tips_for_post( $tips, $post, $extend, $context, $queried )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $tips;

		return array_merge( $tips,
			ModuleHelper::generateHints(
				$post,
				$extend,
				$context,
				$queried,
				$this->_get_supported_simple_metakeys( 'objecthints' )
			)
		);
	}

	private function _get_registered_relations( $context = NULL )
	{
		return $this->filters( 'registered_relations',
			Core\Arraay::pluck(
				WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ), 'all', [], FALSE ),
				'name',
				'slug'
			),
			$context
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
		$this->filter( 'restapi_terms_rendered_html', 5, 8, FALSE, $this->base );
		$this->filter( 'rest_terms_rendered_html', 4, 8, FALSE, 'gnetwork' ); // DEPRECATED

		register_rest_field(
			$this->posttypes(),
			$this->constant( 'restapi_attribute' ),
			[
				'get_callback' => function ( $params, $attr, $request, $object_type ) {
					return $this->get_byline_for_post( (int) $params['id'] );
				}
			]
		);
	}

	// @FILTER: `geditorial_restapi_terms_rendered_html`
	public function restapi_terms_rendered_html( $rows, $taxonomy, $params, $object_type, $post )
	{
		return $this->taxonomy_supported( $taxonomy->name )
			? $this->get_byline_for_post( $post, [], $rows )
			: $rows;
	}

	// @FILTER: `gnetwork_rest_terms_rendered_html`
	public function rest_terms_rendered_html( $html, $taxonomy, $post, $object_type )
	{
		return $this->taxonomy_supported( $taxonomy->name )
			? $this->get_byline_for_post( $post['id'] )
			: $html;
	}

	// NOTE: checks for assigned supported terms to the post
	public function has_content_for_post( $post = NULL, $context = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		foreach ( $this->taxonomies() as $supported )
			if ( WordPress\Taxonomy::theTermCount( $supported, $post ) )
				return TRUE;

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

		if ( ! $this->has_content_for_post( $post_id, 'woocommerce' ) )
			return $tabs;

		return array_merge( $tabs, [
			$this->key => [
				'title'    => $this->get_setting_fallback( 'tab_title', _x( 'People', 'Setting Default', 'geditorial-byline' ) ),
				'priority' => $this->get_setting( 'tab_priority', 20 ), // NOTE: `priority` does not applied on this filter!
				'callback' => function () use ( $post_id ) {
					ModuleTemplate::renderDefault( [
						'default'  => $this->get_notice_for_empty( 'woocommerce', NULL, FALSE ),
						'template' => 'featuredcards',
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
			'link'     => $this->framepage_get_mainlink_url( $post->ID, $target ?? 'summaryreport', 'overview' ),
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

		gEditorial\Scripts::enqueueColorBox();

		return $button;
	}

	private function _hook_tweaks_column( $screen )
	{
		add_action( $this->hook_base( 'tweaks', 'column_row', $screen->post_type ),
			function ( $post, $before, $after, $module ) {

				if ( $this->role_can_post( $post, 'assign' ) )
					$edit = $this->framepage_get_mainlink_url( $post->ID, 'mainapp', 'overview' );

				else if ( $this->role_can_post( $post, 'reports' ) )
					$edit = $this->framepage_get_mainlink_url( $post->ID, 'summaryreport', 'overview' );

				else if ( ! WordPress\Post::can( $post, 'read_post' ) )
					return;

				$icon = $this->get_column_icon( $edit ?? FALSE, NULL, NULL, $post->post_type, 'do-colorbox-iframe' );

				echo $this->get_byline_for_post( $post, [
					'before' => sprintf( $before, '-post-byline' ).$icon,
					'after'  => $after,
				] );

			}, -4, 4 );

		gEditorial\Scripts::enqueueColorBox();

		return TRUE;
	}

	// @FILTER: `gnetwork_taxonomy_exclude_empty`
	public function taxonomy_exclude_empty( $excludes )
	{
		return array_merge( $excludes, [
			$this->constant( 'main_taxonomy' ),
		] );
	}

	public function admin_bar_menu( $wp_admin_bar )
	{
		if ( ! is_singular( $this->posttypes() ) || WordPress\IsIt::mobile() )
			return;

		if ( ! $post = WordPress\Post::get( get_queried_object_id() ) )
			return;

		if ( ! WordPress\Post::can( $post, 'read_post' ) )
			return;

		if ( ! $this->role_can_post( $post, 'assign' ) )
			return;

		$node_id = $this->classs();
		$label   = Services\CustomPostType::getLabel( $post, 'singular_name' );
		$byline  = $this->get_byline_for_post( $post );
		$link    = $this->framepage_get_mainlink_url( $post->ID, 'mainapp', 'overview' );

		$wp_admin_bar->add_menu( [
			'parent' => 'top-secondary',
			'id'     => $node_id,
			'href'   => $link,
			'title'  => Services\Icons::adminBarMarkup( 'edit' ),
			'meta'   => [
				// NOTE: appears as color-box title / `mainbutton_title` not available on front.
				'title' => sprintf(
					/* translators: `%s`: singular post-type label */
					_x( 'Manage byline for this %s', 'Node: Title', 'geditorial-byline' ),
					$label
				),
				'class' => $this->class_for_adminbar_node( 'do-colorbox-iframe-for-child', TRUE ),
			],
		] );

		$wp_admin_bar->add_node( [
			'parent' => $node_id,
			'id'     => $this->classs( 'box' ),
			'title'  => sprintf(
				/* translators: `%s`: singular post-type label */
				_x( 'Byline for this %s', 'Node: Title', 'geditorial-byline' ),
				$label
			),
			'meta' => [
				'class' => $this->class_for_adminbar_node( '-has-infobox' ),
				'html'  => $byline
					? Core\HTML::wrap( $byline, '-byline' )
					: $this->get_notice_for_empty( 'adminbar', NULL, FALSE ),
			],
		] );

		gEditorial\Scripts::enqueueColorBox();
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() || ! is_singular( $this->posttypes() ) || WordPress\IsIt::mobile() )
			return;

		if ( ! $post = WordPress\Post::get( get_queried_object_id() ) )
			return;

		if ( ! WordPress\Post::can( $post, 'read_post' ) )
			return;

		$classs  = $this->classs( 'summary' );
		$assign  = $this->role_can_post( $post, 'assign' );
		$reports = $this->role_can_post( $post, 'reports' );

		if ( ! $reports && ! $assign )
			return;

		$nodes[] = [
			'parent' => $parent,
			'id'     => $classs,
			'title'  => _x( 'Byline', 'Node: Title', 'geditorial-byline' ),
			'href'   => $reports ? $this->get_module_url( 'reports', NULL, [ 'linked' => $post->ID ] ) : FALSE,
			'meta' => [
				'class' => $this->class_for_adminbar_node( '-byline' ),
				'title' => $reports ? sprintf(
					/* translators: `%s`: singular post-type label */
					_x( 'View Byline Reports for this %s', 'Node: Title', 'geditorial-byline' ),
					Services\CustomPostType::getLabel( $post, 'singular_name' )
				) : '',
			],
		];

		$terms = wp_get_object_terms( $post->ID, $this->taxonomies() );

		if ( empty( $terms ) || self::isError( $terms ) ) {

			$nodes[] = [
				'parent' => $classs,
				'id'     => $this->classs( 'empty' ),
				'title'  => gEditorial\Plugin::na( FALSE ),
				'meta' => [
					'class' => $this->class_for_adminbar_node( '-empty' ),
					'title' => $this->get_string( 'empty', 'adminbar', 'notices', gEditorial\Plugin::noinfo( FALSE ) ),
				],
			];

			return;
		}

		$fields = $this->_get_supported_fields( 'display', $post->post_type );

		foreach ( $terms as $term )
			if ( FALSE !== ( $relation = $this->get_term_relations( $term, $post, $fields ) ) )
				$nodes[] = [
					'parent' => $classs,
					'id'     => $this->classs( 'term', $term->term_id ),
					'title'  => $term->name, // NOTE: raw name
					'href'   => WordPress\Term::edit( $term ),
					'meta' => [
						'class' => $this->class_for_adminbar_node( '-term' ),
						'title' => empty( $relation['relation'] )
							? _x( '[Unrelated]', 'Node: Title', 'geditorial-byline' )
							: $fields['relation']['options'][$relation['relation']],
					],
				];
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'       => get_queried_object_id(),
			'featured' => NULL,
			'hidden'   => NULL,
			'context'  => NULL,
			'wrap'     => TRUE,
			'class'    => '',
			'before'   => '',
			'after'    => '',
		], $atts, $tag ?: $this->constant( 'main_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $post = WordPress\Post::get( $args['id'] ) )
			return $content;

		$_args = Core\Arraay::keepByKeys( $args, [
			'context',
			'featured',
			'hidden',
		] );

		if ( ! $html = $this->get_byline_for_post( $post, $_args ) )
			return $content;

		return gEditorial\ShortCode::wrap(
			$html,
			$this->constant( 'main_shortcode' ),
			$args
		);
	}

	private function _get_supported_simple_metakeys( $context = NULL )
	{
		return $this->filters( 'supported_simple_metakeys',
			array_merge(
				$this->_get_registered_relations( $context ),
				$this->define_default_terms()['main_taxonomy']
			),
			$context
		);
	}

	private function _get_supported_byline_metakeys( $context = NULL )
	{
		return $this->filters( 'supported_byline_metakeys',
			[
				'_meta_byline'             => _x( 'Meta Byline', 'Metakey', 'geditorial-byline' ),
				'_meta_publication_byline' => _x( 'Publication Byline', 'Metakey', 'geditorial-byline' ),
				'_meta_featured_people'    => _x( 'Featured People', 'Metakey', 'geditorial-byline' ),
				'byline'                   => _x( 'Custom Meta Byline', 'Metakey', 'geditorial-byline' ),
			],
			$context
		);
	}

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return $this->_override_module_cuc( $context, $fallback, [ 'reports', 'imports' ] );
	}

	public function imports_settings( $sub )
	{
		$this->check_settings( $sub, 'imports', 'per_page' );
	}

	protected function render_imports_html( $uri, $sub )
	{
		echo ModuleSettings::toolboxColumnOpen( _x( 'Byline Imports', 'Header', 'geditorial-byline' ) );

		$available  = FALSE;
		$posttypes  = $this->list_posttypes();
		$taxonomies = $this->list_taxonomies();
		$simples    = $this->_get_supported_simple_metakeys( 'imports' );
		$bylines    = $this->_get_supported_byline_metakeys( 'imports' );

		if ( ModuleSettings::renderCard_import_from_people_plugin( $posttypes ) )
			$available = TRUE;

		if ( ModuleSettings::renderCard_import_from_simple_meta( $posttypes, $taxonomies, $simples ) )
			$available = TRUE;

		if ( ModuleSettings::renderCard_import_from_byline_meta( $posttypes, $taxonomies, $bylines ) )
			$available = TRUE;

		if ( ! $available )
			gEditorial\Info::renderNoImportsAvailable();

		echo '</div>';
	}

	protected function render_imports_html_before( $uri, $sub )
	{
		if ( $this->_do_import_from_byline_meta( $sub ) )
			return FALSE; // avoid further UI

		else if ( $this->_do_import_from_simple_meta( $sub ) )
			return FALSE; // avoid further UI

		else if ( $this->_do_import_from_people_plugin( $sub ) )
			return FALSE; // avoid further UI
	}

	private function _do_import_from_byline_meta( $sub )
	{
		if ( ! self::do( ModuleSettings::ACTION_FROM_BYLINE_META ) )
			return FALSE;

		if ( ! $posttype = self::req( 'type' ) )
			return ! gEditorial\Info::renderEmptyPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $metakey = self::req( 'metakey' ) )
			return ! gEditorial\Info::renderNoDataAvailable(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $taxonomy = self::req( 'taxonomy' ) )
			return ! gEditorial\Info::renderEmptyTaxonomy(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $this->posttype_supported( $posttype ) )
			return ! gEditorial\Info::renderNotSupportedPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $this->taxonomy_supported( $taxonomy ) )
			return ! gEditorial\Info::renderNotSupportedTaxonomy(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! in_array( $taxonomy, WordPress\PostType::taxonomies( $posttype ), TRUE ) )
			return ! gEditorial\Info::renderNotSupportedTaxonomy(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! array_key_exists( $metakey, $this->_get_supported_byline_metakeys( 'imports' ) ) )
			return ! gEditorial\Info::renderNotSupportedField(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		$this->raise_resources();

		return ModuleSettings::handleImport_from_byline_meta(
			$posttype,
			$taxonomy,
			$metakey,
			$this->get_sub_limit_option( $sub, 'imports' )
		);
	}

	private function _do_import_from_simple_meta( $sub )
	{
		if ( ! self::do( ModuleSettings::ACTION_FROM_SIMPLE_META ) )
			return FALSE;

		if ( ! $posttype = self::req( 'type' ) )
			return ! gEditorial\Info::renderEmptyPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $metakey = self::req( 'metakey' ) )
			return ! gEditorial\Info::renderNoDataAvailable(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $taxonomy = self::req( 'taxonomy' ) )
			return ! gEditorial\Info::renderEmptyTaxonomy(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $this->posttype_supported( $posttype ) )
			return ! gEditorial\Info::renderNotSupportedPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $this->taxonomy_supported( $taxonomy ) )
			return ! gEditorial\Info::renderNotSupportedTaxonomy(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! in_array( $taxonomy, WordPress\PostType::taxonomies( $posttype ), TRUE ) )
			return ! gEditorial\Info::renderNotSupportedTaxonomy(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! array_key_exists( $metakey, $this->_get_supported_simple_metakeys( 'imports' ) ) )
			return ! gEditorial\Info::renderNotSupportedField(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		$this->raise_resources();

		return ModuleSettings::handleImport_from_simple_meta(
			$posttype,
			$taxonomy,
			$metakey,
			$this->get_sub_limit_option( $sub, 'imports' )
		);
	}

	private function _do_import_from_people_plugin( $sub )
	{
		if ( ! self::do( ModuleSettings::ACTION_FROM_PEOPLE_PLUGIN ) )
			return FALSE;

		if ( ! $posttype = self::req( 'type' ) )
			return ! gEditorial\Info::renderEmptyPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $this->posttype_supported( $posttype ) )
			return ! gEditorial\Info::renderNotSupportedPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		$this->raise_resources();

		return ModuleSettings::handleImport_from_people_plugin(
			$posttype,
			$this->get_sub_limit_option( $sub, 'imports' )
		);
	}
}
