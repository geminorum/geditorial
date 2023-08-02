<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Module extends WordPress\Module
{

	public $module;
	public $options;
	public $settings;

	public $enabled  = FALSE;
	public $meta_key = '_ge';

	protected $icon_group = 'genericons-neue';

	protected $rest_api_version = 'v1';

	protected $priority_init              = 10;
	protected $priority_init_ajax         = 12;
	protected $priority_restapi_init      = 10;
	protected $priority_current_screen    = 10;
	protected $priority_admin_menu        = 10;
	protected $priority_adminbar_init     = 10;
	protected $priority_template_redirect = 10;
	protected $priority_template_include  = 10;

	protected $positions = []; // menu positions by context/constant
	protected $deafults  = []; // default settings

	protected $constants = [];
	protected $strings   = [];
	protected $features  = [];
	protected $fields    = [];

	protected $partials         = [];
	protected $partials_remote  = [];
	protected $process_disabled = [];

	protected $disable_no_customs    = FALSE; // not hooking module if has no posttypes/taxonomies
	protected $disable_no_posttypes  = FALSE; // not hooking module if has no posttypes
	protected $disable_no_taxonomies = FALSE; // not hooking module if has no taxonomies

	protected $image_sizes  = [];
	protected $kses_allowed = [];

	protected $scripts = [];
	protected $buttons = [];
	protected $errors  = [];
	protected $cache   = [];

	protected $caps = [
		'default'   => 'manage_options',
		'settings'  => 'manage_options',
		'reports'   => 'edit_others_posts',
		'tools'     => 'edit_others_posts',
		'adminbar'  => 'edit_others_posts',
		'dashboard' => 'edit_others_posts',
	];

	protected $root_key = FALSE; // ROOT CONSTANT
	protected $_p2p     = FALSE; // P2P ENABLED/Connection Type
	protected $_o2o     = FALSE; // O2O ENABLED/Connection Type
	protected $_paired  = FALSE; // PAIRED API ENABLED/taxonomy paired

	protected $scripts_printed = FALSE;
	protected $current_queried = FALSE; // usually contains `get_queried_object_id()`

	public function __construct( &$module, &$options, $root, $locale = NULL )
	{
		$this->base = 'geditorial';
		$this->key  = $module->name;
		$this->path = Core\File::normalize( $root.$module->folder.'/' );
		$this->site = get_current_blog_id();

		$this->module  = $module;
		$this->options = $options;

		if ( FALSE !== $module->disabled )
			return;

		$this->setup_textdomain( $locale );

		if ( $this->remote() )
			$this->setup_remote();

		else
			$this->setup();
	}

	protected function setup_textdomain( $locale = NULL, $domain = NULL )
	{
		if ( FALSE === $this->module->i18n )
			return FALSE;

		if ( 'adminonly' === $this->module->i18n && ! is_admin() )
			return FALSE;

		if ( 'restonly' === $this->module->i18n && ! Core\WordPress::isREST() )
			return FALSE;

		if ( 'frontonly' === $this->module->i18n && is_admin() )
			return FALSE;

		if ( is_null( $domain ) )
			$domain = $this->get_textdomain();

		if ( ! $domain )
			return FALSE;

		if ( is_null( $locale ) )
			$locale = apply_filters( 'plugin_locale', Core\L10n::locale(), $this->base );

		return load_textdomain( $domain, GEDITORIAL_DIR."languages/{$this->module->folder}/{$locale}.mo" );
	}

	protected function setup_remote( $args = [] )
	{
		$this->require_code( $this->partials_remote );
	}

	protected function setup( $args = [] )
	{
		$admin = is_admin();
		$ajax  = Core\WordPress::isAJAX();
		$ui    = Core\WordPress::mustRegisterUI( FALSE );

		if ( $admin && $ui && ( TRUE === $this->module->configure || 'settings' === $this->module->configure ) )
			add_action( $this->base.'_settings_load', [ $this, 'register_settings' ] );

		if ( $this->setup_disabled() )
			return FALSE;

		$this->require_code( $this->partials );

		if ( method_exists( $this, 'plugin_loaded' ) )
			add_action( sprintf( '%s_loaded', $this->base ), [ $this, 'plugin_loaded' ] );

		if ( method_exists( $this, 'o2o_init' ) )
			$this->action( 'o2o_init' ); // NOTE: runs on `wp_loaded`

		else if ( method_exists( $this, 'p2p_init' ) )
			$this->action( 'p2p_init' ); // NOTE: runs on `wp_loaded`

		if ( method_exists( $this, 'widgets_init' ) && $this->get_setting( 'widget_support' ) )
			$this->action( 'widgets_init' );

		if ( ! $ajax && method_exists( $this, 'tinymce_strings' ) )
			add_filter( $this->base.'_tinymce_strings', [ $this, 'tinymce_strings' ] );

		if ( method_exists( $this, 'meta_init' ) )
			add_action( $this->base.'_meta_init', [ $this, 'meta_init' ], 10, 2 );

		if ( method_exists( $this, 'elementor_register' ) )
			add_action( 'elementor/widgets/register', [ $this, 'elementor_register' ], 10, 1 );

		add_action( 'after_setup_theme', [ $this, '_after_setup_theme' ], 1 );
		add_action( 'rest_api_init', [ $this, '_rest_api_init' ], $this->priority_restapi_init );

		if ( method_exists( $this, 'after_setup_theme' ) )
			$this->action( 'after_setup_theme', 0, 20 );

		$this->action( 'init', 0, $this->priority_init );

		if ( $ui && method_exists( $this, 'adminbar_init' ) && $this->get_setting( 'adminbar_summary' ) )
			add_action( $this->base.'_adminbar', [ $this, 'adminbar_init' ], $this->priority_adminbar_init, 2 );

		if ( $admin ) {

			add_action( 'admin_init', [ $this, '_admin_init' ], 1 );

			if ( method_exists( $this, 'admin_init' ) )
				$this->action( 'admin_init' );

			if ( $ui && method_exists( $this, 'admin_menu' ) )
				$this->action( 'admin_menu', 0, $this->priority_admin_menu );

			if ( $ajax )
				add_action( 'init', [ $this, '_init_ajax' ], $this->priority_init_ajax );

			if ( $ui )
				add_action( 'wp_dashboard_setup', [ $this, 'setup_dashboard' ] );

			if ( ( $ui || $ajax ) && method_exists( $this, 'register_shortcode_ui' ) )
				$this->action( 'register_shortcode_ui' );

			if ( $ui && method_exists( $this, 'add_meta_boxes' ) )
				$this->action( 'add_meta_boxes', 2, 12 );

			if ( $ui && method_exists( $this, 'current_screen' ) )
				$this->action( 'current_screen', 1, $this->priority_current_screen );

			if ( $ui && method_exists( $this, 'reports_settings' ) )
				add_action( $this->base.'_reports_settings', [ $this, 'reports_settings' ] );

			if ( $ui && method_exists( $this, 'tools_settings' ) )
				add_action( $this->base.'_tools_settings', [ $this, 'tools_settings' ] );

			if ( $ui && method_exists( $this, 'imports_settings' ) )
				add_action( $this->base.'_imports_settings', [ $this, 'imports_settings' ] );

			if ( $ui && method_exists( $this, 'tool_box_content' ) )
				$this->action( 'tool_box' );

		} else {

			if ( $ui && method_exists( $this, 'template_redirect' ) )
				add_action( 'template_redirect', [ $this, 'template_redirect' ], $this->priority_template_redirect );

			if ( $ui && method_exists( $this, 'template_include' ) )
				add_filter( 'template_include', [ $this, 'template_include' ], $this->priority_template_include );
		}

		return TRUE;
	}

	public function setup_disabled()
	{
		if ( $this->disable_no_customs && ! count( $this->posttypes() ) && ! count( $this->taxonomies() ) )
			return TRUE;

		if ( $this->disable_no_posttypes && ! count( $this->posttypes() ) )
			return TRUE;

		if ( $this->disable_no_taxonomies && ! count( $this->taxonomies() ) )
			return TRUE;

		return FALSE;
	}

	public function setup_dashboard()
	{
		if ( method_exists( $this, 'dashboard_glance_items' ) )
			$this->filter( 'dashboard_glance_items' );

		if ( method_exists( $this, 'dashboard_widgets' )
			&& $this->get_setting( 'dashboard_widgets', FALSE ) )
				$this->dashboard_widgets();
	}

	// NOTE: ALWAYS HOOKED
	public function _after_setup_theme()
	{
		$this->constants = $this->filters( 'constants', $this->get_global_constants(), $this->module );
		$this->fields    = $this->filters( 'fields', $this->get_global_fields(), $this->module );
	}

	// NOTE: ALWAYS HOOKED
	public function _rest_api_init()
	{
		if ( method_exists( $this, 'setup_restapi' ) )
			$this->setup_restapi();

		if ( method_exists( $this, 'pairedcore__hook_sync_paired' ) )
			$this->pairedcore__hook_sync_paired();
	}

	// NOTE: ALWAYS HOOKED: PRIORITY: `12`
	public function _init_ajax()
	{
		if ( method_exists( $this, 'setup_ajax' ) )
			$this->setup_ajax();

		if ( method_exists( $this, 'pairedcore__hook_sync_paired_for_ajax' ) )
			$this->pairedcore__hook_sync_paired_for_ajax();

		if ( method_exists( $this, 'do_ajax' ) )
			$this->_hook_ajax();
	}

	// NOTE: MUST ALWAYS CALLED BY THE MODULE
	public function init()
	{
		$this->actions( 'init', $this->options, $this->module );

		$this->features = $this->filters( 'features', $this->get_global_features(), $this->module );
		$this->strings  = $this->filters( 'strings', $this->get_global_strings(), $this->module );
	}

	// NOTE: ALWAYS HOOKED
	public function _admin_init()
	{
		if ( method_exists( $this, 'exports_do_check_requests' ) )
			$this->exports_do_check_requests();

		// auto-hook register default terms
		// helps if strings filtered
		if ( ! empty( $this->strings['default_terms'] ) )
			foreach ( array_keys( $this->strings['default_terms'] ) as $taxonomy_constant )
				$this->register_default_terms( $taxonomy_constant );

		$prefix   = self::sanitize_base( $this->key );
		$callback = static function( $key, $value ) use ( $prefix ) {
			return [ sprintf( '%s-%s.php', $prefix, $key ) => $value ];
		};

		foreach ( $this->get_module_templates() as $constant => $templates ) {

			if ( empty( $templates ) )
				continue;

			$this->filter_set(
				sprintf( 'theme_%s_templates', $this->constant( $constant ) ),
				Core\Arraay::mapAssoc( $callback, $templates )
			);
		}
	}

	protected function get_textdomain()
	{
		if ( NULL === $this->module->textdomain )
			return $this->base;

		if ( FALSE === $this->module->textdomain )
			return FALSE;

		return empty( $this->module->textdomain )
			? $this->classs()
			: $this->module->textdomain;
	}

	protected function get_global_settings() { return []; }
	protected function get_global_constants() { return []; }
	protected function get_global_strings() { return []; }
	protected function get_global_features() { return []; }
	protected function get_global_fields() { return []; }

	protected function get_module_templates() { return []; }
	protected function get_module_icons() { return []; }

	protected function settings_help_tabs( $context = 'settings' )
	{
		return Settings::helpContent( $this->module );
	}

	protected function settings_help_sidebar( $context = 'settings' )
	{
		return Settings::helpSidebar( $this->get_module_links() );
	}

	// FIXME: get dashboard menu for the module
	protected function get_module_links()
	{
		$links  = [];
		$screen = get_current_screen();

		if ( method_exists( $this, 'reports_settings' ) && ! Settings::isReports( $screen ) )
			foreach ( $this->append_sub( [], 'reports' ) as $sub => $title )
				$links[] = [
					'context' => 'reports',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'reports', $sub ),
					/* translators: %s: sub title */
					'title'   => sprintf( _x( '%s Reports', 'Module: Extra Link: Reports', 'geditorial' ), $title ),
				];

		if ( method_exists( $this, 'tools_settings' ) && ! Settings::isTools( $screen ) )
			foreach ( $this->append_sub( [], 'tools' ) as $sub => $title )
				$links[] = [
					'context' => 'tools',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'tools', $sub ),
					/* translators: %s: sub title */
					'title'   => sprintf( _x( '%s Tools', 'Module: Extra Link: Tools', 'geditorial' ), $title ),
				];

		if ( method_exists( $this, 'imports_settings' ) && ! Settings::isImports( $screen ) )
			foreach ( $this->append_sub( [], 'imports' ) as $sub => $title )
				$links[] = [
					'context' => 'imports',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'imports', $sub ),
					/* translators: %s: sub title */
					'title'   => sprintf( _x( '%s Imports', 'Module: Extra Link: Tools', 'geditorial' ), $title ),
				];

		if ( isset( $this->caps['settings'] ) && ! Settings::isSettings( $screen ) && $this->cuc( 'settings' ) )
			$links[] = [
				'context' => 'settings',
				'sub'     => $this->key,
				'text'    => $this->module->title,
				'url'     => $this->get_module_url( 'settings' ),
				/* translators: %s: module title */
				'title'   => sprintf( _x( '%s Settings', 'Module: Extra Link: Settings', 'geditorial' ), $this->module->title ),
			];

		if ( $docs = $this->get_module_url( 'docs' ) )
			$links[] = [
				'context' => 'docs',
				'sub'     => $this->key,
				'text'    => $this->module->title,
				'url'     => $docs,
				/* translators: %s: module title */
				'title'   => sprintf( _x( '%s Documentation', 'Module: Extra Link: Documentation', 'geditorial' ), $this->module->title ),
			];

		if ( 'config' != $this->module->name )
			$links[] = [
				'context' => 'docs',
				'sub'     => FALSE,
				'text'    => _x( 'Editorial Documentation', 'Module: Extra Link: Documentation', 'geditorial' ),
				'url'     => Settings::getModuleDocsURL( FALSE ),
				'title'   => _x( 'Editorial Documentation', 'Module: Extra Link: Documentation', 'geditorial' ),
			];

		return $links;
	}

	public function get_module_url( $context = 'reports', $sub = NULL, $extra = [] )
	{
		if ( is_null( $sub ) )
			$sub = $this->key;

		switch ( $context ) {
			case 'config'    : $url = Settings::settingsURL(); break;
			case 'reports'   : $url = Settings::reportsURL(); break;
			case 'tools'     : $url = Settings::toolsURL(); break;
			case 'imports'   : $url = Settings::importsURL(); break;
			case 'docs'      : $url = Settings::getModuleDocsURL( $this->module ); $sub = FALSE; break;
			case 'settings'  : $url = add_query_arg( 'module', $this->module->name, Settings::settingsURL() ); $sub = FALSE; break;
			case 'listtable' : $url = $this->get_adminpage_url( TRUE, [], 'adminmenu' ); $sub = FALSE; break;
			default          : $url = Core\URL::current();
		}

		if ( FALSE === $url )
			return FALSE;

		return add_query_arg( array_merge( [
			'sub' => $sub,
		], $extra ), $url );
	}

	protected function get_adminpage_url( $full = TRUE, $extra = [], $context = 'mainpage', $admin_base = NULL )
	{
		$page = in_array( $context, [ 'mainpage', 'adminmenu' ], TRUE )
			? $this->classs()
			: $this->classs( $context );

		if ( ! $full )
			return $page;

		if ( is_null( $admin_base ) )
			$admin_base = in_array( $context, [ 'adminmenu', 'printpage', 'framepage', 'newpost', 'importitems' ], TRUE )
				? get_admin_url( NULL, 'index.php' )
				: get_admin_url( NULL, 'admin.php' );

		return add_query_arg( array_merge( [ 'page' => $page ], $extra ), $admin_base );
	}

	protected function get_adminpage_subs( $context = 'mainpage' )
	{
		// $subs = $this->list_posttypes( NULL, NULL, 'create_posts' );
		$subs = $this->get_string( 'subs', $context, 'adminpage', [] );

		// FIXME: check capabilities
		// $can  = $this->role_can( $context ) ? 'read' : 'do_not_allow';

		return $this->filters( $context.'_subs', $subs );
	}

	protected function get_adminpage_default_sub( $subs = NULL, $context = 'mainpage' )
	{
		if ( is_null( $subs ) )
			$subs = $this->get_adminpage_subs( $context );

		return $this->filters( $context.'_default_sub', Core\Arraay::keyFirst( $subs ) );
	}

	protected function _hook_menu_adminpage( $context = 'mainpage', $position = NULL )
	{
		$slug    = $this->get_adminpage_url( FALSE, [], $context );
		$subs    = $this->get_adminpage_subs( $context );
		$default = $this->get_adminpage_default_sub( $subs, $context );
		$can     = $this->role_can( $context ) ? 'read' : 'do_not_allow';
		$menu    = $this->get_string( 'menu_title', $context, 'adminpage', $this->key );

		if ( is_null( $position ) )
			$position = empty( $this->positions[$context] ) ? 3 : $this->positions[$context];

		$hook = add_menu_page(
			$this->get_string( 'page_title', $context, 'adminpage', $this->key ),
			$menu,
			$can,
			$slug,
			[ $this, 'render_menu_adminpage' ],
			$this->get_posttype_icon(),
			$position
		);

		foreach ( $subs as $sub => $submenu )
			add_submenu_page(
				$slug,
				/* translators: %1$s: menu title, %2$s: submenu title */
				sprintf( _x( '%1$s &lsaquo; %2$s', 'Module: Page Title', 'geditorial' ), $submenu, $menu ), // FIXME: only shows the first sub
				$submenu,
				$can,
				$slug.( $sub == $default ? '' : '&sub='.$sub ),
				[ $this, 'render_menu_adminpage' ]
			);

		add_action( 'load-'.$hook, [ $this, 'load_menu_adminpage' ], 10, 0 );

		return $slug;
	}

	public function load_menu_adminpage( $context = 'mainpage' )
	{
		$subs    = $this->get_adminpage_subs( $context );
		$default = $this->get_adminpage_default_sub( $subs, $context );
		$page    = self::req( 'page', NULL );
		$sub     = self::req( 'sub', $default );

		if ( $sub && $sub != $default )
			$GLOBALS['submenu_file'] = $this->get_adminpage_url( FALSE, [], $context ).'&sub='.$sub;

		$this->register_help_tabs( NULL, $context );
		$this->actions( 'load_adminpage', $page, $sub, $context );
	}

	public function render_menu_adminpage()
	{
		$this->render_default_mainpage( 'mainpage', 'update' );
	}

	protected function render_default_mainpage( $context = 'mainpage', $action = 'update' )
	{
		$uri     = $this->get_adminpage_url( TRUE, [], $context );
		$subs    = $this->get_adminpage_subs( $context );
		$default = $this->get_adminpage_default_sub( $subs, $context );
		$content = [ $this, 'render_mainpage_content' ];
		$sub     = self::req( 'sub', $default );

		if ( $context && method_exists( $this, 'render_'.$context.'_content' ) )
			$content = [ $this, 'render_'.$context.'_content' ];

		Settings::wrapOpen( $this->key, $context, $this->get_string( 'page_title', $context, 'adminpage', '' ) );

			$this->render_adminpage_header_title( NULL, NULL, NULL, $context );
			$this->render_adminpage_header_nav( $uri, $sub, $subs, $context );
			$this->render_form_start( $uri, $sub, $action, $context, FALSE );
				$this->nonce_field( $context );
				call_user_func_array( $content, [ $sub, $uri, $context, $subs ] );
			$this->render_form_end( $uri, $sub, $action, $context, FALSE );
			$this->render_adminpage_signature( $uri, $sub, $subs, $context );

		Settings::wrapClose();
	}

	// DEFAULT CALLBACK
	protected function render_mainpage_content() // ( $sub = NULL, $uri = NULL, $context = '', $subs = [] )
	{
		Core\HTML::desc( gEditorial()->na(), TRUE, '-empty' );
	}

	protected function _hook_submenu_adminpage( $context = 'subpage', $parent_slug = '' )
	{
		$slug = $this->get_adminpage_url( FALSE, [], $context );
		$can  = $this->role_can( $context ) ? 'read' : 'do_not_allow';
		$cb   = [ $this, 'render_submenu_adminpage' ];
		$load = [ $this, 'load_submenu_adminpage' ];

		if ( $context && method_exists( $this, 'render_'.$context.'_adminpage' ) )
			$cb = [ $this, 'render_'.$context.'_adminpage' ];

		if ( $context && method_exists( $this, 'load_'.$context.'_adminpage' ) )
			$load = [ $this, 'load_'.$context.'_adminpage' ];

		$hook = add_submenu_page(
			$parent_slug, // or `index.php`
			$this->get_string( 'page_title', $context, 'adminpage', $this->key ),
			$this->get_string( 'menu_title', $context, 'adminpage', '' ),
			$can,
			$slug,
			$cb
		);

		add_action( 'load-'.$hook, $load, 10, 0 );

		return $slug;
	}

	public function load_submenu_adminpage( $context = 'subpage' )
	{
		$page = self::req( 'page', NULL );
		$sub  = self::req( 'sub', NULL );

		$this->register_help_tabs( NULL, $context );
		$this->actions( 'load_adminpage', $page, $sub, $context );
	}

	public function render_submenu_adminpage()
	{
		$this->render_default_mainpage( 'subpage', 'update' );
	}

	public function render_newpost_adminpage()
	{
		$this->render_default_mainpage( 'newpost', 'insert' );
	}

	// TODO: link to edit posttype screen
	// TODO: get default posttype/status somehow!
	protected function render_newpost_content()
	{
		$posttype = self::req( 'type', 'post' );
		$status   = self::req( 'status', 'draft' );
		$target   = self::req( 'target', 'none' );
		$linked   = self::req( 'linked', FALSE );
		$meta     = self::req( 'meta', [] );
		$object   = get_post_type_object( $posttype );
		$post     = get_default_post_to_edit( $posttype );

		if ( ! current_user_can( $object->cap->create_posts ) )
			return Core\HTML::desc( gEditorial\Plugin::denied( FALSE ), TRUE, '-denied' );

		$meta = $this->filters( 'newpost_content_meta', $meta, $posttype, $target, $linked, $status );

		echo $this->wrap_open( '-newpost-layout' );
		echo '<div class="-main">';

		$this->actions( 'newpost_content_before_title', $posttype, $post, $target, $linked, $status, $meta );

		$field = $this->classs( $posttype, 'title' );
		$label = $this->get_string( 'post_title', $posttype, 'newpost', __( 'Add title' ) );

		$html = Core\HTML::tag( 'input', [
			'type'        => 'text',
			'class'       => 'large-text',
			'id'          => $field,
			'name'        => 'title',
			'placeholder' => apply_filters( 'enter_title_here', $label, $post ),
		] );

		Core\HTML::label( $html, $field );

		$this->actions( 'newpost_content_after_title', $posttype, $post, $target, $linked, $status, $meta );

		$field = $this->classs( $posttype, 'excerpt' );
		$label = $this->get_string( 'post_excerpt', $posttype, 'newpost', __( 'Excerpt' ) );

		$html = Core\HTML::tag( 'textarea', [
			'id'           => $field,
			'name'         => 'excerpt',
			'placeholder'  => $label,
			'class'        => [ 'mceEditor', 'large-text' ],
			'rows'         => 2,
			'cols'         => 15,
			'autocomplete' => 'off',
		], '' );

		Core\HTML::label( $html, $field );

		$field = $this->classs( $posttype, 'content' );
		$label = $this->get_string( 'post_content', $posttype, 'newpost', __( 'What&#8217;s on your mind?' ) );

		$html = Core\HTML::tag( 'textarea', [
			'id'           => $field,
			'name'         => 'content',
			'placeholder'  => $label,
			'class'        => [ 'mceEditor', 'large-text' ],
			'rows'         => 6,
			'cols'         => 15,
			'autocomplete' => 'off',
		], '' );

		Core\HTML::label( $html, $field );

		if ( $object->hierarchical )
			MetaBox::fieldPostParent( $post, FALSE, 'parent' );

		$this->actions( 'newpost_content', $posttype, $post, $target, $linked, $status, $meta );

		Core\HTML::inputHidden( 'type', $posttype );
		Core\HTML::inputHidden( 'status', $status === 'publish' ? 'publish' : 'draft' ); // only publish/draft
		Core\HTML::inputHiddenArray( $meta, 'meta' );

		echo $this->wrap_open_buttons();

		echo '<span class="-message"></span>';
		echo Ajax::spinner();

		echo Core\HTML::tag( 'a', [
			'href'  => '#',
			'class' => [ 'button', '-save-draft', 'disabled' ],
			'data'  => [
				'target'   => $target,
				'type'     => $posttype,
				'linked'   => $linked,
				'endpoint' => rest_url( sprintf( '/wp/v2/%s', $object->rest_base ) ), // TODO: use `rest_get_route_for_post_type_items()`
			],
		], _x( 'Save Draft & Close', 'Module', 'geditorial' ) );

		echo '</p></div><div class="-side">';
		echo '<div class="-recents">';

			// FIXME: do actions here
			// FIXME: move recents to pre-conf action
			// FIXME: correct the selectors
			// TODO: hook action from Book module: suggestd the book by passed meta

			/* translators: %s: posttype singular name */
			$hint = sprintf( _x( 'Or select this %s', 'Module: Recents', 'geditorial' ), $object->labels->singular_name );

			Template::renderRecentByPosttype( $object, '#', NULL, $hint, [
				'post_status' => [ 'publish', 'future', 'draft' ],
			] );

		echo '</div>';

			/* translators: %s: posttype name */
			Core\HTML::desc( sprintf( _x( 'Or select one from Recent %s.', 'Module: Recents', 'geditorial' ), $object->labels->name ) );

		echo '</div></div>';

		$this->enqueue_asset_js( [
			'strings' => [
				'noparent' => _x( 'This frame has no parent window!', 'Module: NewPost: JS String', 'geditorial' ),
				'notarget' => _x( 'Cannot handle the target window!', 'Module: NewPost: JS String', 'geditorial' ),
				'closeme'  => _x( 'New post has been saved you may close this frame!', 'Module: NewPost: JS String', 'geditorial' ),
			],
		], 'module.newpost', [
			'jquery',
			'wp-api-request',
		], '_newpost' );
	}

	// LEGACY: do not use thickbox anymore!
	// NOTE: must `add_thickbox()` on load
	public function do_render_thickbox_mainbutton( $post, $context = 'framepage', $extra = [], $inline = FALSE, $width = '800' )
	{
		// for inline only
		// modal id must be: `{$base}-{$module}-thickbox-{$context}`
		if ( $inline && $context && method_exists( $this, 'admin_footer_'.$context ) )
			$this->action( 'admin_footer', 0, 20, $context );

		$name  = Helper::getPostTypeLabel( $post->post_type, 'singular_name' );
		$title = $this->get_string( 'mainbutton_title', $context ?: $post->post_type, 'metabox', NULL );
		$text  = $this->get_string( 'mainbutton_text', $context ?: $post->post_type, 'metabox', $name );

		if ( $inline )
			// WTF: thickbox bug: does not process the arg after `TB_inline`!
			$link = '#TB_inline?dummy=dummy&width='.$width.'&inlineId='.$this->classs( 'thickbox', $context ).( $extra ? '&'.http_build_query( $extra ) : '' ); // &modal=true
		else
			// WTF: thickbox bug: does not pass the args after `TB_iframe`!
			$link = $this->get_adminpage_url( TRUE, array_merge( [
				'linked'   => $post->ID,
				'noheader' => 1,
				'width'    => $width,
			], $extra, [ 'TB_iframe' => 'true' ] ), $context );

		$html = Core\HTML::tag( 'a', [
			'href'  => $link,
			'id'    => $this->classs( 'mainbutton', $context ),
			'class' => [ 'button', '-button', '-button-full', '-button-icon', '-mainbutton', 'thickbox' ],
			'title' => $title ? sprintf( $title, WordPress\Post::title( $post, $name ), $name ) : FALSE,
		], sprintf( $text, Helper::getIcon( $this->module->icon ), $name ) );

		echo Core\HTML::wrap( $html, 'field-wrap -buttons' );
	}

	// LEGACY: do not use thickbox anymore!
	// NOTE: must `add_thickbox()` on load
	public function do_render_thickbox_newpostbutton( $post, $constant, $context = 'newpost', $extra = [], $inline = FALSE, $width = '600' )
	{
		$posttype = $this->constant( $constant );
		$object   = WordPress\PostType::object( $posttype );

		if ( ! current_user_can( $object->cap->create_posts ) )
			return FALSE;

		// for inline only
		// modal id must be: `{$base}-{$module}-thickbox-{$context}`
		if ( $inline && $context && method_exists( $this, 'admin_footer_'.$context ) )
			$this->action( 'admin_footer', 0, 20, $context );

		/* translators: %1$s: current post title, %2$s: posttype singular name */
		$title = $this->get_string( 'mainbutton_title', $constant, 'newpost', _x( 'Quick New %2$s', 'Module: Button Title', 'geditorial' ) );
		$text  = $this->get_string( 'mainbutton_text', $constant, 'newpost', sprintf( '%s %s', '%1$s', $object->labels->add_new_item ) );
		$name  = $object->labels->singular_name;

		if ( $inline )
			// WTF: thickbox bug: does not process the arg after `TB_inline`!
			$link = '#TB_inline?dummy=dummy&width='.$width.'&inlineId='.$this->classs( 'thickbox', $context ).( $extra ? '&'.http_build_query( $extra ) : '' ); // &modal=true
		else
			// WTF: thickbox bug: does not pass the args after `TB_iframe`!
			$link = $this->get_adminpage_url( TRUE, array_merge( [
				'type'     => $posttype,
				'linked'   => $post->ID,
				'noheader' => 1,
				'width'    => $width,
			], $extra, [ 'TB_iframe' => 'true' ] ), $context );

		$html = Core\HTML::tag( 'a', [
			'href'  => $link,
			'id'    => $this->classs( 'newpostbutton', $context ),
			'class' => [ 'button', '-button', '-button-full', '-button-icon', '-newpostbutton', 'thickbox' ],
			'title' => $title ? sprintf( $title, WordPress\Post::title( $post, $name ), $name ) : FALSE,
		], sprintf( $text, Helper::getIcon( $this->module->icon ), $name ) );

		echo Core\HTML::wrap( $html, 'field-wrap -buttons hide-if-no-js' );
	}

	// check if this module loaded as remote for another blog's editorial module
	public function remote()
	{
		if ( ! $this->root_key )
			return FALSE;

		if ( ! defined( $this->root_key ) )
			return FALSE;

		if ( constant( $this->root_key ) == $this->site )
			return FALSE;

		return TRUE;
	}

	// DEFAULT METHOD
	protected function posttypes_excluded( $extra = [] )
	{
		$extra  = (array) $extra;
		$paired = $this->paired_get_paired_constants();

		if ( ! empty( $paired[0] ) )
			$extra[] = $this->constant( $paired[0] );

		return $this->filters( 'posttypes_excluded', Settings::posttypesExcluded( $extra ) );
	}

	// DEFAULT METHOD
	protected function posttypes_parents( $extra = [] )
	{
		return $this->filters( 'posttypes_parents', Settings::posttypesParents( $extra ) );
	}

	// enabled post types for this module
	public function posttypes( $posttypes = NULL )
	{
		if ( is_null( $posttypes ) )
			$posttypes = [];

		else if ( ! is_array( $posttypes ) )
			$posttypes = [ $this->constant( $posttypes ) ];

		if ( empty( $this->options->post_types ) )
			return $posttypes;

		$posttypes = array_merge( $posttypes, array_keys( array_filter( $this->options->post_types ) ) );

		return did_action( 'wp_loaded' )
			? array_filter( $posttypes, 'post_type_exists' )
			: $posttypes;
	}

	public function posttype_supported( $posttype )
	{
		return $posttype && in_array( $posttype, $this->posttypes(), TRUE );
	}

	public function is_posttype( $constant, $post = NULL )
	{
		if ( ! $constant )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		return $this->constant( $constant ) == $post->post_type;
	}

	public function is_taxonomy( $constant, $term = NULL )
	{
		if ( ! $constant )
			return FALSE;

		if ( ! $term = WordPress\Term::get( $term ) )
			return FALSE;

		return $this->constant( $constant ) == $term->taxonomy;
	}

	public function is_post_viewable( $post = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		return $this->filters( 'is_post_viewable', WordPress\Post::viewable( $post ), $post );
	}

	public function list_posttypes( $pre = NULL, $posttypes = NULL, $capability = NULL, $args = [ 'show_ui' => TRUE ], $user_id = NULL )
	{
		if ( is_null( $pre ) )
			$pre = [];

		else if ( TRUE === $pre )
			$pre = [ 'all' => _x( 'All PostTypes', 'Module', 'geditorial' ) ];

		$all = WordPress\PostType::get( 0, $args, $capability, $user_id );

		foreach ( $this->posttypes( $posttypes ) as $posttype ) {

			if ( array_key_exists( $posttype, $all ) )
				$pre[$posttype] = $all[$posttype];

			// only if no checks required
			else if ( is_null( $capability ) && post_type_exists( $posttype ) )
				$pre[$posttype] = $posttype;
		}

		return $pre;
	}

	public function all_posttypes( $args = [ 'show_ui' => TRUE ], $exclude_extra = [] )
	{
		return Core\Arraay::stripByKeys( WordPress\PostType::get( 0, $args ), Core\Arraay::prepString( $this->posttypes_excluded( $exclude_extra ) ) );
	}

	protected function taxonomies_excluded( $extra = [] )
	{
		return $this->filters( 'taxonomies_excluded', Settings::taxonomiesExcluded( $extra ) );
	}

	protected function _hook_taxonomies_excluded( $constant, $module = NULL )
	{
		$hook = sprintf( '%s_%s_taxonomies_excluded', $this->base, is_null( $module ) ? $this->module->name : $module );
		$this->filter_append( $hook, $this->constant( $constant ) );
	}

	// enabled taxonomies for this module
	public function taxonomies( $taxonomies = NULL )
	{
		if ( is_null( $taxonomies ) )
			$taxonomies = [];

		else if ( ! is_array( $taxonomies ) )
			$taxonomies = [ $this->constant( $taxonomies ) ];

		if ( empty( $this->options->taxonomies ) )
			return $taxonomies;

		$taxonomies = array_merge( $taxonomies, array_keys( array_filter( $this->options->taxonomies ) ) );

		return did_action( 'wp_loaded' )
			? array_filter( $taxonomies, 'taxonomy_exists' )
			: $taxonomies;
	}

	public function taxonomy_supported( $taxonomy )
	{
		return $taxonomy && in_array( $taxonomy, $this->taxonomies(), TRUE );
	}

	public function list_taxonomies( $pre = NULL, $taxonomies = NULL, $capability = NULL, $args = [ 'show_ui' => TRUE ], $user_id = NULL )
	{
		if ( is_null( $pre ) )
			$pre = [];

		else if ( TRUE === $pre )
			$pre = [ 'all' => _x( 'All Taxonomies', 'Module', 'geditorial' ) ];

		$all = WordPress\Taxonomy::get( 0, $args, FALSE, $capability, $user_id );

		foreach ( $this->taxonomies( $taxonomies ) as $taxonomy ) {

			if ( array_key_exists( $taxonomy, $all ) )
				$pre[$taxonomy] = $all[$taxonomy];

			// only if no checks required
			else if ( is_null( $capability ) )
				$pre[$taxonomy] = $taxonomy;
		}

		return $pre;
	}

	public function all_taxonomies( $args = [ 'show_ui' => TRUE ], $exclude_extra = [] )
	{
		return Core\Arraay::stripByKeys( WordPress\Taxonomy::get( 0, $args ), Core\Arraay::prepString( $this->taxonomies_excluded( $exclude_extra ) ) );
	}

	// allows for filtering the page title
	// TODO: add compact mode to hide this on user screen setting
	protected function render_adminpage_header_title( $title = NULL, $links = NULL, $icon = NULL, $context = 'mainpage' )
	{
		if ( self::req( 'noheader' ) )
			return;

		if ( is_null( $title ) )
			$title = $this->get_string( 'page_title', $context, 'adminpage', NULL );

		if ( is_null( $links ) )
			$links = $this->get_adminpage_header_links( $context );

		if ( is_null( $icon ) )
			$icon = $this->module->icon;

		if ( $title )
			Settings::headerTitle( $title, $links, NULL, $icon );
	}

	protected function render_adminpage_header_nav( $uri = '', $sub = NULL, $subs = NULL, $context = 'mainpage' )
	{
		if ( self::req( 'noheader' ) ) {
			echo '<div class="base-tabs-list -base nav-tab-base">';
			Core\HTML::tabNav( $sub, $subs );
		} else {
			echo $this->wrap_open( $context, $sub );
			Core\HTML::headerNav( $uri, $sub, $subs );
		}
	}

	protected function render_adminpage_signature( $uri = '', $sub = NULL, $subs = NULL, $context = 'mainpage' )
	{
		if ( ! self::req( 'noheader' ) )
			$this->settings_signature( $context );

		echo '</div>';
	}

	// `array` for custom, `NULL` to settings, `FALSE` to disable
	protected function get_adminpage_header_links( $context = 'mainpage' )
	{
		if ( $action = $this->get_string( 'page_action', $context, 'adminpage', NULL ) )
			return [ $this->get_adminpage_url() => $action ];

		return FALSE;
	}

	public function settings_posttypes_option()
	{
		if ( $before = $this->get_string( 'post_types_before', 'post', 'settings', NULL ) )
			Core\HTML::desc( $before );

		echo '<div class="wp-tab-panel"><ul>';

		foreach ( $this->all_posttypes() as $posttype => $label ) {

			$html = Core\HTML::tag( 'input', [
				'type'    => 'checkbox',
				'value'   => 'enabled',
				'id'      => 'type-'.$posttype,
				'name'    => $this->base.'_'.$this->module->name.'[post_types]['.$posttype.']',
				'checked' => ! empty( $this->options->post_types[$posttype] ),
			] );

			$html.= '&nbsp;'.Core\HTML::escape( $label );
			$html.= ' &mdash; <code>'.$posttype.'</code>';

			Core\HTML::label( $html, 'type-'.$posttype, 'li' );
		}

		echo '</ul></div>';

		if ( $after = $this->get_string( 'post_types_after', 'post', 'settings', NULL ) )
			Core\HTML::desc( $after );
	}

	public function settings_taxonomies_option()
	{
		if ( $before = $this->get_string( 'taxonomies_before', 'post', 'settings', NULL ) )
			Core\HTML::desc( $before );

		echo '<div class="wp-tab-panel"><ul>';

		foreach ( $this->all_taxonomies() as $taxonomy => $label ) {

			$html = Core\HTML::tag( 'input', [
				'type'    => 'checkbox',
				'value'   => 'enabled',
				'id'      => 'tax-'.$taxonomy,
				'name'    => $this->base.'_'.$this->module->name.'[taxonomies]['.$taxonomy.']',
				'checked' => ! empty( $this->options->taxonomies[$taxonomy] ),
			] );

			$html.= '&nbsp;'.Core\HTML::escape( $label );
			$html.= ' &mdash; <code>'.$taxonomy.'</code>';

			Core\HTML::label( $html, 'tax-'.$taxonomy, 'li' );
		}

		echo '</ul></div>';

		if ( $after = $this->get_string( 'taxonomies_after', 'post', 'settings', NULL ) )
			Core\HTML::desc( $after );
	}

	protected function settings_supports_option( $constant, $defaults = TRUE, $excludes = NULL )
	{
		$supports = $this->filters( $constant.'_supports', Settings::supportsOptions() );

		// has custom fields
		if ( isset( $this->fields[$this->constant( $constant )] ) )
			unset( $supports['editorial-meta'] );

		// default excludes
		if ( is_null( $excludes ) )
			$excludes = [ 'post-formats', 'trackbacks' ];

		if ( count( $excludes ) )
			$supports = array_diff_key( $supports, array_flip( (array) $excludes ) );

		if ( FALSE === $defaults )
			$defaults = [];

		else if ( TRUE === $defaults )
			$defaults = array_keys( $supports );

		// NOTE: filtered noop strings may omit context/domain keys!
		$singular = translate_nooped_plural( array_merge( [
			'context' => NULL,
			'domain'  => $this->get_textdomain() ?: 'default',
		], $this->strings['noops'][$constant] ), 1 );

		return [
			'field'       => $constant.'_supports',
			'type'        => 'checkboxes-values',
			/* translators: %s: singular posttype name */
			'title'       => sprintf( _x( '%s Supports', 'Module: Setting Title', 'geditorial' ), $singular ),
			/* translators: %s: singular posttype name */
			'description' => sprintf( _x( 'Support core and extra features for %s posttype.', 'Module: Setting Description', 'geditorial' ), $singular ),
			'default'     => $defaults,
			'values'      => $supports,
		];
	}

	protected function settings_insert_priority_option( $default = 10, $prefix = FALSE )
	{
		return [
			'field'   => 'insert_priority'.( $prefix ? '_'.$prefix : '' ),
			'type'    => 'priority',
			'title'   => _x( 'Insert Priority', 'Module: Setting Title', 'geditorial' ),
			'default' => $default,
		];
	}

	public function get_postid_by_field( $value, $field, $prefix = NULL )
	{
		if ( is_null( $prefix ) )
			$prefix = 'meta'; // the exception!

		if ( $post_id = WordPress\PostType::getIDbyMeta( $this->get_postmeta_key( $field, $prefix ), $value ) )
			return intval( $post_id );

		return FALSE;
	}

	public function get_postmeta_key( $field, $prefix = NULL )
	{
		return sprintf( '_%s_%s', ( is_null( $prefix ) ? $this->key : $prefix ), $field );
	}

	public function get_postmeta_field( $post_id, $field, $default = FALSE, $prefix = NULL, $metakey = NULL )
	{
		if ( ! $post_id )
			return $default;

		if ( is_null( $prefix ) )
			$prefix = $this->key;

		$legacy = $this->get_postmeta_legacy( $post_id, [], $metakey );

		foreach ( $this->sanitize_postmeta_field_key( $field ) as $field_key ) {

			if ( $data = $this->fetch_postmeta( $post_id, $default, $this->get_postmeta_key( $field_key, $prefix ) ) )
				return $data;

			if ( is_array( $legacy ) && array_key_exists( $field_key, $legacy ) )
				return $legacy[$field_key];
		}

		return $default;
	}

	public function set_postmeta_field( $post_id, $field, $data, $prefix = NULL )
	{
		if ( is_null( $prefix ) )
			$prefix = $this->key;

		if ( ! $this->store_postmeta( $post_id, $data, $this->get_postmeta_key( $field, $prefix ) ) )
			return FALSE;

		// tries to cleanup old field keys, upon changing in the future
		foreach ( $this->sanitize_postmeta_field_key( $field ) as $offset => $field_key )
			if ( $offset ) // skips the current key!
				delete_post_meta( $post_id, $this->get_postmeta_key( $field_key, $prefix ) );

		return TRUE;
	}

	// fetch module meta array
	// back-comp only
	public function get_postmeta_legacy( $post_id, $default = [], $metakey = NULL )
	{
		global $gEditorialPostMetaLegacy;

		if ( is_null( $metakey ) )
			$metakey = $this->meta_key;

		if ( ! isset( $gEditorialPostMetaLegacy[$post_id][$metakey] ) )
			$gEditorialPostMetaLegacy[$post_id][$metakey] = $this->fetch_postmeta( $post_id, $default, $metakey );

		return $gEditorialPostMetaLegacy[$post_id][$metakey];
	}

	public function clean_postmeta_legacy( $post_id, $fields, $legacy = NULL, $metakey = NULL )
	{
		global $gEditorialPostMetaLegacy;

		if ( is_null( $metakey ) )
			$metakey = $this->meta_key;

		if ( is_null( $legacy ) )
			$legacy = $this->get_postmeta_legacy( $post_id, [], $metakey );

		foreach ( $fields as $field => $args )
			foreach ( $this->sanitize_postmeta_field_key( $field ) as $field_key )
				if ( array_key_exists( $field_key, $legacy ) )
					unset( $legacy[$field_key] );

		unset( $gEditorialPostMetaLegacy[$post_id][$metakey] );

		return $this->store_postmeta( $post_id, array_filter( $legacy ), $metakey );
	}

	public function sanitize_postmeta_field_key( $field_key )
	{
		return (array) $field_key;
	}

	public function store_postmeta( $post_id, $data, $metakey = NULL )
	{
		if ( is_null( $metakey ) )
			$metakey = $this->meta_key; // back-comp

		if ( empty( $data ) )
			return delete_post_meta( $post_id, $metakey );

		return (bool) update_post_meta( $post_id, $metakey, $data );
	}

	public function fetch_postmeta( $post_id, $default = '', $metakey = NULL )
	{
		if ( is_null( $metakey ) )
			$metakey = $this->meta_key; // back-comp

		$data = get_metadata( 'post', $post_id, $metakey, TRUE );

		return $data ?: $default;
	}

	public function register_settings_posttypes_option( $title = NULL )
	{
		if ( is_null( $title ) )
			$title = $this->get_string( 'post_types_title', 'post', 'settings',
				_x( 'Enable for Post Types', 'Module', 'geditorial' ) );

		$option = $this->base.'_'.$this->module->name;

		Settings::addModuleSection( $option, [
			'id'            => $option.'_posttypes',
			'section_class' => 'posttypes_option_section',
		] );

		add_settings_field( 'post_types',
			$title,
			[ $this, 'settings_posttypes_option' ],
			$option,
			$option.'_posttypes'
		);
	}

	public function register_settings_taxonomies_option( $title = NULL )
	{
		if ( is_null( $title ) )
			$title = $this->get_string( 'taxonomies_title', 'post', 'settings',
				_x( 'Enable for Taxonomies', 'Module', 'geditorial' ) );

		$option = $this->base.'_'.$this->module->name;

		Settings::addModuleSection( $option, [
			'id'            => $option.'_taxonomies',
			'section_class' => 'taxonomies_option_section',
		] );

		add_settings_field( 'taxonomies',
			$title,
			[ $this, 'settings_taxonomies_option' ],
			$option,
			$option.'_taxonomies'
		);
	}

	public function register_settings_fields_option( $section_title = NULL )
	{
		if ( is_null( $section_title ) )
			/* translators: %s: posttype */
			$section_title = _x( 'Fields for %s', 'Module', 'geditorial' );

		$all = $this->all_posttypes();

		foreach ( $this->posttypes() as $posttype ) {

			$fields  = $this->posttype_fields_all( $posttype );
			$section = $posttype.'_fields';

			if ( count( $fields ) ) {

				Settings::addModuleSection( $this->base.'_'.$this->module->name, [
					'id'            => $section,
					'title'         => sprintf( $section_title, $all[$posttype] ),
					'section_class' => 'fields_option_section fields_option-'.$posttype,
				] );

				$this->add_settings_field( [
					'field'     => $posttype.'_fields_all',
					'post_type' => $posttype,
					'section'   => $section,
					'title'     => '&nbsp;',
					'callback'  => [ $this, 'settings_fields_option_all' ],
				] );

				foreach ( $fields as $field => $atts ) {

					$field_title = isset( $atts['title'] ) ? $atts['title'] : $this->get_string( $field, $posttype );

					$args = [
						'field'       => $field,
						'post_type'   => $posttype,
						'section'     => $section,
						'field_title' => sprintf( '%s &mdash; <code>%s</code>', $field_title, $field ),
						'description' => isset( $atts['description'] ) ? $atts['description'] : $this->get_string( $field, $posttype, 'descriptions' ),
						'callback'    => [ $this, 'settings_fields_option' ],
					];

					if ( is_array( $atts ) )
						$args = array_merge( $args, $atts );

					if ( $args['field_title'] == $args['description'] )
						unset( $args['description'] );

					$args['title'] = '&nbsp;';

					$this->add_settings_field( $args );
				}

			} else if ( isset( $all[$posttype] ) ) {

				Settings::addModuleSection( $this->base.'_'.$this->module->name, [
					'id'            => $section,
					'title'         => sprintf( $section_title, $all[$posttype] ),
					'callback'      => [ $this, 'settings_fields_option_none' ],
					'section_class' => 'fields_option_section fields_option_none',
				] );
			}
		}
	}

	// helps with renamed fields
	private function get_settings_fields_option_val( $args )
	{
		$fields = array_reverse( $this->sanitize_postmeta_field_key( $args['field'] ) );

		foreach ( $fields as $field_key )
			if ( isset( $this->options->fields[$args['post_type']][$field_key] ) )
				return $this->options->fields[$args['post_type']][$field_key];

		if ( isset( $this->options->fields[$args['post_type']][$args['field']] ) )
			return $this->options->fields[$args['post_type']][$args['field']];

		if ( ! empty( $args['default'] ) )
			return $args['default'];

		return FALSE;
	}

	public function settings_fields_option( $args )
	{
		$name  = $this->base.'_'.$this->module->name.'[fields]['.$args['post_type'].']['.$args['field'].']';
		$id    = $this->base.'_'.$this->module->name.'-fields-'.$args['post_type'].'-'.$args['field'];
		$value = $this->get_settings_fields_option_val( $args );

		$html = Core\HTML::tag( 'input', [
			'type'    => 'checkbox',
			'value'   => 'enabled',
			'class'   => 'fields-check',
			'name'    => $name,
			'id'      => $id,
			'checked' => $value,
		] );

		echo '<div>';
			Core\HTML::label( $html.'&nbsp;'.$args['field_title'], $id, FALSE );
			Core\HTML::desc( $args['description'] );
		echo '</div>';
	}

	public function settings_fields_option_all( $args )
	{
		$html = Core\HTML::tag( 'input', [
			'type'  => 'checkbox',
			'class' => 'fields-check-all',
			'id'    => $args['post_type'].'_fields_all',
		] );

		$html.= '&nbsp;<span class="description">'._x( 'Select All Fields', 'Module', 'geditorial' ).'</span>';

		Core\HTML::label( $html, $args['post_type'].'_fields_all', FALSE );
	}

	public function settings_fields_option_none( $args )
	{
		Settings::moduleSectionEmpty( _x( 'No fields supported', 'Module', 'geditorial' ) );
	}

	public function settings_from()
	{
		echo '<form class="'.$this->base.'-form -form -'.$this->module->name
			.'" action="'.$this->get_module_url( 'settings' ).'" method="post">';

			$this->render_form_fields( $this->module->name );

			Settings::moduleSections( $this->base.'_'.$this->module->name );

			echo '<input id="geditorial_module_name" name="geditorial_module_name" type="hidden" value="'.Core\HTML::escape( $this->module->name ).'" />';

			$this->render_form_buttons();

		echo '</form>';

		if ( Core\WordPress::isDev() )
			self::dump( $this->options );
	}

	public function default_buttons( $module = FALSE )
	{
		$this->register_button( 'submit', NULL, TRUE );
		$this->register_button( 'reset', NULL, 'reset', TRUE );

		if ( ! $this->module->autoload )
			$this->register_button( 'disable', _x( 'Disable Module', 'Module: Button', 'geditorial' ), 'danger' );

		foreach ( $this->get_module_links() as $link )
			if ( ! empty( $link['context'] ) && in_array( $link['context'], [ 'tools', 'reports', 'imports', 'listtable' ] ) )
				$this->register_button( $link['url'], $link['title'], 'link' );
	}

	public function register_button( $key, $value = NULL, $type = FALSE, $atts = [] )
	{
		if ( is_null( $value ) )
			$value = $this->get_string( $key, 'buttons', 'settings', NULL );

		$this->buttons[] = [
			'key'   => $key,
			'value' => $value,
			'type'  => $type,
			'atts'  => $atts,
		];
	}

	protected function render_form_buttons( $module = FALSE, $wrap = '', $buttons = NULL )
	{
		if ( FALSE !== $wrap )
			echo $this->wrap_open_buttons( $wrap );

		if ( is_null( $buttons ) )
			$buttons = $this->buttons;

		foreach ( $buttons as $button )
			Settings::submitButton( $button['key'], $button['value'], $button['type'], $button['atts'] );

		if ( FALSE !== $wrap )
			echo '</p>';
	}

	protected function render_form_start( $uri, $sub = NULL, $action = 'update', $context = 'settings', $check = TRUE )
	{
		if ( is_null( $sub ) )
			$sub = $this->module->name;

		$class = [
			$this->base.'-form',
			'-form',
			'-'.$this->module->name,
			'-sub-'.$sub,
		];

		if ( $check && $sidebox = method_exists( $this, 'settings_sidebox' ) )
			$class[] = 'has-sidebox';

		echo '<form enctype="multipart/form-data" class="'.Core\HTML::prepClass( $class ).'" method="post" action="">';

			if ( in_array( $context, [ 'settings', 'tools', 'reports', 'imports' ] ) )
				$this->render_form_fields( $sub, $action, $context );

			if ( $check && $sidebox ) {
				echo '<div class="'.Core\HTML::prepClass( '-sidebox', '-'.$this->module->name, '-sidebox-'.$sub ).'">';
					$this->settings_sidebox( $sub, $uri, $context );
				echo '</div>';
			}
	}

	protected function render_form_end( $uri, $sub = NULL, $action = 'update', $context = 'settings', $check = TRUE )
	{
		echo '</form>';
	}

	// DEFAULT METHOD: tools sub html
	public function tools_sub( $uri, $sub )
	{
		$this->render_form_start( $uri, $sub, 'bulk', 'tools', FALSE );

			if ( $this->render_tools_html( $uri, $sub ) )
				$this->render_form_buttons();

			$this->render_tools_html_after( $uri, $sub );

		$this->render_form_end( $uri, $sub );
	}

	// DEFAULT METHOD: used for tools default sub html
	protected function render_tools_html( $uri, $sub ) {}
	protected function render_tools_html_after( $uri, $sub ) {}

	// DEFAULT METHOD: reports sub html
	public function reports_sub( $uri, $sub )
	{
		$this->render_form_start( $uri, $sub, 'bulk', 'reports', FALSE );

			if ( $this->render_reports_html( $uri, $sub ) )
				$this->render_form_buttons();

			$this->render_reports_html_after( $uri, $sub );

		$this->render_form_end( $uri, $sub );
	}

	// DEFAULT METHOD: used for reports default sub html
	protected function render_reports_html( $uri, $sub ) {}
	protected function render_reports_html_after( $uri, $sub ) {}

	// DEFAULT METHOD: imports sub html
	public function imports_sub( $uri, $sub )
	{
		$this->render_form_start( $uri, $sub, 'bulk', 'imports', FALSE );

			if ( $this->render_imports_html( $uri, $sub ) )
				$this->render_form_buttons();

			$this->render_imports_html_after( $uri, $sub );

		$this->render_form_end( $uri, $sub );
	}

	// DEFAULT METHOD: used for imports default sub html
	protected function render_imports_html( $uri, $sub ) {}
	protected function render_imports_html_after( $uri, $sub ) {}

	protected function get_current_form( $defaults, $context = 'settings' )
	{
		$req = empty( $_REQUEST[$this->base.'_'.$this->module->name][$context] )
			? []
			: $_REQUEST[$this->base.'_'.$this->module->name][$context];

		return self::atts( $defaults, $req );
	}

	protected function fields_current_form( $fields, $context = 'settings', $excludes = [] )
	{
		foreach ( $fields as $key => $value ) {

			if ( in_array( $key, $excludes ) )
				continue;

			Core\HTML::inputHidden( $this->base.'_'.$this->module->name.'['.$context.']['.$key.']', $value );
		}
	}

	protected function render_form_fields( $sub, $action = 'update', $context = 'settings' )
	{
		Core\HTML::inputHidden( 'base', $this->base );
		Core\HTML::inputHidden( 'key', $this->key );
		Core\HTML::inputHidden( 'context', $context );
		Core\HTML::inputHidden( 'sub', $sub );
		Core\HTML::inputHidden( 'action', $action );

		Core\WordPress::fieldReferer();
		$this->nonce_field( $context, $sub );
	}

	// DEFAULT METHOD
	// `$extra` arg is for extending in modules
	public function append_sub( $subs, $context = 'settings', $extra = [] )
	{
		if ( ! $this->cuc( $context ) )
			return $subs;

		return array_merge( $subs, [ $this->module->name => $this->module->title ], $extra );
	}

	public function settings_validate( $options )
	{
		$this->init_settings();

		if ( isset( $this->settings['posttypes_option'] ) ) {

			if ( ! isset( $options['post_types'] ) )
				$options['post_types'] = [];

			foreach ( $this->all_posttypes() as $posttype => $posttype_label )
				if ( ! isset( $options['post_types'][$posttype] )
					|| $options['post_types'][$posttype] != 'enabled' )
						unset( $options['post_types'][$posttype] );
				else
					$options['post_types'][$posttype] = TRUE;

			if ( ! count( $options['post_types'] ) )
				unset( $options['post_types'] );
		}

		if ( isset( $this->settings['taxonomies_option'] ) ) {

			if ( ! isset( $options['taxonomies'] ) )
				$options['taxonomies'] = [];

			foreach ( $this->all_taxonomies() as $taxonomy => $label )
				if ( ! isset( $options['taxonomies'][$taxonomy] )
					|| $options['taxonomies'][$taxonomy] != 'enabled' )
						unset( $options['taxonomies'][$taxonomy] );
				else
					$options['taxonomies'][$taxonomy] = TRUE;

			if ( ! count( $options['taxonomies'] ) )
				unset( $options['taxonomies'] );
		}

		if ( isset( $this->settings['fields_option'] ) ) {

			if ( ! isset( $options['fields'] ) )
				$options['fields'] = [];

			foreach ( $this->posttypes() as $posttype ) {

				if ( ! isset( $options['fields'][$posttype] ) )
					$options['fields'][$posttype] = [];

				foreach ( $this->posttype_fields_all( $posttype ) as $field => $args ) {

					if ( ! isset( $options['fields'][$posttype][$field] )
						|| $options['fields'][$posttype][$field] != 'enabled' )
							unset( $options['fields'][$posttype][$field] );
					else
						$options['fields'][$posttype][$field] = TRUE;
				}

				if ( ! count( $options['fields'][$posttype] ) )
					unset( $options['fields'][$posttype] );
			}

			if ( ! count( $options['fields'] ) )
				unset( $options['fields'] );
		}

		if ( isset( $options['settings'] ) ) {

			foreach ( (array) $options['settings'] as $setting => $option ) {

				$args = $this->get_settings_field( $setting );

				// skip disabled settings
				if ( array_key_exists( 'values', $args ) && FALSE === $args['values'] )
					continue;

				if ( ! array_key_exists( 'type', $args ) || 'enabled' == $args['type'] ) {

					$options['settings'][$setting] = (bool) $option;

				} else if ( 'object' == $args['type'] ) {

					if ( empty( $option ) || ! is_array( $option ) || empty( $args['values'] ) ) {

						unset( $options['settings'][$setting] );

					} else {

						$sanitized = [];
						$first_key = Core\Arraay::keyFirst( $option );

						foreach ( $option[$first_key] as $index => $unused ) {

							// first one is empty
							if ( ! $index )
								continue;

							$group = [];

							foreach ( $args['values'] as $field ) {

								if ( empty( $field['field'] ) )
									continue;

								$key  = $field['field'];
								$type = empty( $field['type'] ) ? 'text' : $field['type'];

								switch ( $type ) {

									case 'number':
										$group[$key] = Core\Number::intval( trim( $option[$key][$index] ) );
										break;

									case 'text':
									default:
										$group[$key] = trim( self::unslash( $option[$key][$index] ) );
								}
							}

							if ( count( $group ) )
								$sanitized[] = $group;
						}

						if ( count( $sanitized ) )
							$options['settings'][$setting] = $sanitized;

						else
							unset( $options['settings'][$setting] );
					}

				} else if ( is_array( $option ) ) {

					if ( 'text' == $args['type'] ) {

						// multiple texts
						$options['settings'][$setting] = [];

						foreach ( $option as $key => $value )
							if ( $string = trim( self::unslash( $value ) ) )
								$options['settings'][$setting][sanitize_key( $key )] = $string;

					} else {

						// multiple checkboxes
						$options['settings'][$setting] = array_keys( $option );
					}

				} else {

					$options['settings'][$setting] = trim( self::unslash( $option ) );
				}
			}

			if ( ! count( $options['settings'] ) )
				unset( $options['settings'] );
		}

		return $options;
	}

	protected function get_settings_field( $setting )
	{
		foreach ( $this->settings as $section ) {

			if ( is_array( $section ) ) {

				foreach ( $section as $key => $field ) {

					if ( FALSE === $field )
						continue;

					if ( is_string( $key ) && $setting == $key )
						return method_exists( __NAMESPACE__.'\\Settings', 'getSetting_'.$key )
							? call_user_func_array( [ __NAMESPACE__.'\\Settings', 'getSetting_'.$key ], (array) $field )
							: [];

					else if ( is_string( $field ) && $setting == $field )
						return method_exists( __NAMESPACE__.'\\Settings', 'getSetting_'.$field )
							? call_user_func( [ __NAMESPACE__.'\\Settings', 'getSetting_'.$field ] )
							: [];

					else if ( is_array( $field ) && isset( $field['field'] ) && $setting == $field['field'] )
						return $field;
				}
			}
		}

		return [];
	}

	public function posttype_fields_all( $posttype = 'post', $module = NULL )
	{
		return WordPress\PostType::supports( $posttype, ( is_null( $module ) ? $this->module->name : $module ).'_fields' );
	}

	public function posttype_fields_list( $posttype = 'post', $extra = [] )
	{
		$list = [];

		foreach ( $this->posttype_fields_all( $posttype ) as $field => $args )
			$list[$field] = array_key_exists( 'title', $args )
				? $args['title']
				: $this->get_string( $field, $posttype, 'titles', $field );

		foreach ( $extra as $key => $val )
			$list[$key] = $this->get_string( $val, $posttype );

		return $list;
	}

	// enabled fields for a posttype
	public function posttype_fields( $posttype = 'post', $js = FALSE )
	{
		$fields = [];

		if ( isset( $this->options->fields[$posttype] )
			&& is_array( $this->options->fields[$posttype] ) ) {

			foreach ( $this->options->fields[$posttype] as $field_key => $enabled ) {

				$sanitized = $this->sanitize_postmeta_field_key( $field_key )[0];

				if ( $js )
					$fields[$sanitized] = (bool) $enabled;

				else if ( $enabled )
					$fields[] = $sanitized;
			}
		}

		return $fields;
	}

	// HELPER METHOD
	public function get_posttype_field_args( $field_key, $posttype )
	{
		if ( ! $posttype || ! $field_key )
			return FALSE;

		$fields = $this->get_posttype_fields( $posttype );
		$field  = array_key_exists( $field_key, $fields )
			? $fields[$field_key]
			: FALSE;

		return $this->filters( 'posttype_field_args', $field, $field_key, $posttype, $fields );
	}

	// this module enabled fields with args for a posttype
	// static contexts: `nobox`, `lonebox`, `mainbox`
	// dynamic contexts: `listbox_{$posttype}`, `pairedbox_{$posttype}`, `pairedbox_{$module}`
	public function get_posttype_fields( $posttype )
	{
		global $gEditorialPostTypeFields;

		if ( isset( $gEditorialPostTypeFields[$this->key][$posttype] ) )
			return $gEditorialPostTypeFields[$this->key][$posttype];

		$all     = $this->posttype_fields_all( $posttype );
		$enabled = $this->posttype_fields( $posttype );
		$fields  = [];

		foreach ( $enabled as $i => $field ) {

			$args = isset( $all[$field] ) && is_array( $all[$field] ) ? $all[$field] : [];

			if ( ! array_key_exists( 'type', $args ) )
				$args['type'] = 'text';

			if ( ! array_key_exists( 'context', $args ) ) {

				if ( in_array( $args['type'], [ 'postbox_legacy', 'title_before', 'title_after' ] ) )
					$args['context'] = 'nobox'; // OLD: 'raw'

				else if ( in_array( $args['type'], [ 'postbox_html', 'postbox_tiny' ] ) )
					$args['context'] = 'lonebox'; // OLD: 'lone'
			}

			if ( ! array_key_exists( 'default', $args ) ) {

				if ( in_array( $args['type'], [ 'array' ] ) || ! empty( $args['repeat'] ) )
					$args['default'] = [];

				else if ( in_array( $args['type'], [ 'integer', 'number', 'float', 'price' ] ) )
					$args['default'] = 0;

				else
					$args['default'] = '';
			}

			if ( ! array_key_exists( 'ltr', $args ) ) {

				if ( in_array( $args['type'], [ 'phone', 'mobile', 'contact', 'identity', 'iban', 'isbn' ], TRUE ) )
					$args['ltr'] = TRUE;
			}

			if ( ! array_key_exists( 'quickedit', $args ) )
				$args['quickedit'] = in_array( $args['type'], [ 'title_before', 'title_after' ] );

			// TODO: migrate!
			// $args = PostTypeFields::getFieldDefaults( $field, $args );

			if ( ! isset( $args['icon'] ) )
				$args['icon'] = $this->get_posttype_field_icon( $field, $posttype, $args );

			$fields[$field] = self::atts( [
				'type'        => 'text',
				'name'        => $field,
				'rest'        => $field, // FALSE to disable
				'title'       => $this->get_string( $field, $posttype, 'titles', $field ),
				'description' => $this->get_string( $field, $posttype, 'descriptions' ),
				'access_view' => NULL, // @SEE: `$this->access_posttype_field()`
				'access_edit' => NULL, // @SEE: `$this->access_posttype_field()`
				'sanitize'    => NULL, // callback
				'prep'        => NULL, // callback
				'pattern'     => NULL, // HTML5 input pattern
				'default'     => NULL, // currently only on rest
				'datatype'    => NULL, // DataType Class
				'icon'        => 'smiley',
				'context'     => 'mainbox', // OLD: 'main'
				'quickedit'   => FALSE,
				'values'      => $this->get_strings( $field, 'values', $this->get_strings( $args['type'], 'values', [] ) ),
				'none_title'  => $this->get_string( $field, $posttype, 'none', NULL ),
				'none_value'  => '',
				'repeat'      => FALSE,
				'ltr'         => FALSE,
				'taxonomy'    => FALSE,
				'posttype'    => NULL,
				'role'        => FALSE,
				'group'       => 'general',
				'order'       => 1000 + $i,
			], $args );

			$this->actions( sprintf( 'init_posttype_field_%s', $field ), $fields[$field], $field, $posttype );
		}

		$gEditorialPostTypeFields[$this->key][$posttype] = Core\Arraay::multiSort( $fields, [
			'group' => SORT_ASC,
			'order' => SORT_ASC,
		] );

		return $gEditorialPostTypeFields[$this->key][$posttype];
	}

	/**
	 * Checks for accessing a posttype field.
	 *
	 * - `TRUE`,`FALSE` for public/private
	 * - `NULL` for posttype `read`/`edit_post` capability
	 * - String for capability checks
	 *
	 * @param  array $field
	 * @param  null|int|object $post
	 * @param  string $context
	 * @param  null|int $user_id
	 * @return bool $access
	 */
	public function access_posttype_field( $field, $post = NULL, $context = 'view', $user_id = NULL )
	{
		if ( ! $field )
			return FALSE; // no field, no access!

		$context = in_array( $context, [ 'view', 'edit' ], TRUE ) ? $context : 'view';
		$access  = array_key_exists( 'access_'.$context, $field )
			? $field['access_'.$context] : NULL;

		if ( TRUE !== $access && FALSE !== $access ) {

			if ( is_null( $user_id ) )
				$user_id = wp_get_current_user();

			if ( ! is_null( $access ) ) {

				$access = user_can( $user_id, $access );

			} else if ( $post = WordPress\Post::get( $post ) ) {

				$access = in_array( $context, [ 'edit' ], TRUE )
					? user_can( $user_id, 'edit_post', $post->ID )
					: WordPress\Post::viewable( $post );

			} else {

				// no post, no access!
				$access = FALSE;
			}
		}

		return $this->filters( 'access_posttype_field', $access, $field, $post, $context, $user_id );
	}

	// use for all modules
	public function sanitize_posttype_field( $data, $field, $post = FALSE )
	{
		if ( ! empty( $field['sanitize'] ) && is_callable( $field['sanitize'] ) )
			return $this->filters( 'sanitize_posttype_field',
				call_user_func_array( $field['sanitize'], [ $data, $field, $post ] ),
				$field, $post, $data );

		$sanitized = $data;

		switch ( $field['type'] ) {

			case 'parent_post':
			case 'post':

				if ( ! empty( $data ) && ( $object = get_post( (int) $data ) ) )
					$sanitized = $object->ID;

				else
					$sanitized = FALSE;

				break;

			case 'user':

				if ( ! empty( $data ) && ( $object = get_user_by( 'id', (int) $data ) ) )
					$sanitized = $object->ID;

				else
					$sanitized = FALSE;

				break;

			case 'term':

				// TODO: use `WordPress\Term::get( $data, $field['taxonomy'] )`
				$sanitized = empty( $data ) ? FALSE : (int) $data;

			break;

			case 'embed':
			case 'text_source':
			case 'audio_source':
			case 'video_source':
			case 'image_source':
			case 'downloadable':
			case 'link':
				$sanitized = trim( $data );

 				// @SEE: `esc_url()`
				if ( $sanitized && ! preg_match( '/^http(s)?:\/\//', $sanitized ) )
					$sanitized = 'http://'.$sanitized;
				break;

			case 'postcode':
				$sanitized = Core\Validation::sanitizePostCode( $data );
				break;

			case 'code':
				$sanitized = trim( $data );

			break;
			case 'email':
				$sanitized = sanitize_email( trim( $data ) );

			break;
			case 'contact':
				$sanitized = Core\Number::intval( trim( $data ), FALSE );
				break;

			case 'identity':
				$sanitized = Core\Validation::sanitizeIdentityNumber( $data );
				break;

			case 'isbn':
				$sanitized = Core\ISBN::sanitize( $data, TRUE );
				break;

			case 'iban':
				$sanitized = Core\Validation::sanitizeIBAN( $data );
				break;

			case 'phone':
				$sanitized = Core\Phone::sanitize( $data );
				break;

			case 'mobile':
			 	$sanitized = Core\Mobile::sanitize( $data );
				break;

			case 'date':
				$sanitized = Core\Number::intval( trim( $data ), FALSE );
				$sanitized = Datetime::makeMySQLFromInput( $sanitized, 'Y-m-d', $this->default_calendar(), NULL, $sanitized );
				break;

			case 'time':
				$sanitized = Core\Number::intval( trim( $data ), FALSE );
				break;

			case 'datetime':

				// @SEE: https://html.spec.whatwg.org/multipage/common-microsyntaxes.html#dates

				$sanitized = Core\Number::intval( trim( $data ), FALSE );
				$sanitized = Datetime::makeMySQLFromInput( $sanitized, NULL, $this->default_calendar(), NULL, $sanitized );
				break;

			case 'price':
			case 'number':
				$sanitized = Core\Number::intval( trim( $data ) );

			break;
			case 'float':
				$sanitized = Core\Number::floatval( trim( $data ) );

			break;
			case 'text':
			case 'venue':
			case 'datestring':
			case 'title_before':
			case 'title_after':
				$sanitized = trim( Helper::kses( $data, 'none' ) );

			break;
			case 'address':
			case 'note':
			case 'textarea':
			case 'widget': // FIXME: maybe general note fields displayed by a meta widget: `primary`/`side notes`
				$sanitized = trim( Helper::kses( $data, 'text' ) );

			break;
			case 'postbox_legacy':
			case 'postbox_tiny':
			case 'postbox_html':
				$sanitized = trim( Helper::kses( $data, 'html' ) );
		}

		return $this->filters( 'sanitize_posttype_field', $sanitized, $field, $post, $data );
	}

	// for fields only in connection to the caller module
	public function add_posttype_fields_supported( $posttypes = NULL, $fields = NULL, $append = TRUE, $type = 'meta' )
	{
		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		if ( is_null( $fields ) )
			$fields = array_key_exists( '_supported', $this->fields ) ? $this->fields['_supported'] : [];

		if ( empty( $fields ) )
			return;

		foreach ( $posttypes as $posttype )
			$this->add_posttype_fields( $posttype, $fields, $append, $type );
	}

	public function add_posttype_fields( $posttype, $fields = NULL, $append = TRUE, $type = 'meta' )
	{
		if ( is_null( $fields ) )
			$fields = array_key_exists( $posttype, $this->fields ) ? $this->fields[$posttype] : [];

		if ( empty( $fields ) )
			return;

		if ( $append )
			$fields = array_merge( WordPress\PostType::supports( $posttype, $type.'_fields' ), $fields );

		add_post_type_support( $posttype, [ $type.'_fields' ], $fields );
		add_post_type_support( $posttype, 'custom-fields' ); // must for rest meta fields
	}

	public function has_posttype_fields_support( $constant, $type = 'meta' )
	{
		return post_type_supports( $this->constant( $constant ), $type.'_fields' );
	}

	// NOTE: fallback will merge if is an array
	// NOTE: moveup is FALSE by default
	public function get_strings( $subgroup, $group = 'titles', $fallback = [], $moveup = FALSE )
	{
		if ( $subgroup && isset( $this->strings[$group][$subgroup] ) )
			return is_array( $fallback )
				? array_merge( $fallback, $this->strings[$group][$subgroup] )
				: $this->strings[$group][$subgroup];

		if ( $moveup && isset( $this->strings[$group] ) )
			return is_array( $fallback )
				? array_merge( $fallback, $this->strings[$group] )
				: $this->strings[$group];

		return $fallback;
	}

	public function get_string( $string, $subgroup = 'post', $group = 'titles', $fallback = FALSE, $moveup = TRUE )
	{
		if ( $subgroup && isset( $this->strings[$group][$subgroup][$string] ) )
			return $this->strings[$group][$subgroup][$string];

		if ( isset( $this->strings[$group]['post'][$string] ) )
			return $this->strings[$group]['post'][$string];

		if ( $moveup && isset( $this->strings[$group][$string] ) )
			return $this->strings[$group][$string];

		if ( FALSE === $fallback )
			return $string;

		return $fallback;
	}

	public function get_noop( $constant )
	{
		if ( ! empty( $this->strings['noops'][$constant] ) )
			return $this->strings['noops'][$constant];

		if ( 'post' == $constant )
			/* translators: %s: posts count */
			return _nx_noop( '%s Post', '%s Posts', 'Module: Noop', 'geditorial' );

		if ( 'connected' == $constant )
			/* translators: %s: items count */
			return _nx_noop( '%s Item Connected', '%s Items Connected', 'Module: Noop', 'geditorial' );

		if ( 'word' == $constant )
			/* translators: %s: words count */
			return _nx_noop( '%s Word', '%s Words', 'Module: Noop', 'geditorial' );

		$noop = [
			'plural'   => $constant,
			'singular' => $constant,
			// 'context'  => ucwords( $module->name ).' Module: Noop', // no need
			'domain'   => 'geditorial',
		];

		if ( ! empty( $this->strings['labels'][$constant]['name'] ) )
			$noop['plural'] = $this->strings['labels'][$constant]['name'];

		if ( ! empty( $this->strings['labels'][$constant]['singular_name'] ) )
			$noop['singular'] = $this->strings['labels'][$constant]['singular_name'];

		return $noop;
	}

	public function nooped_count( $constant, $count )
	{
		return sprintf( Helper::noopedCount( $count, $this->get_noop( $constant ) ), Core\Number::format( $count ) );
	}

	public function constant( $key, $default = FALSE )
	{
		if ( ! $key )
			return $default;

		if ( isset( $this->constants[$key] ) )
			return $this->constants[$key];

		if ( 'post_cpt' === $key || 'post_posttype' === $key )
			return 'post';

		if ( 'page_cpt' === $key || 'page_posttype' === $key )
			return 'page';

		return $default;
	}

	public function constants( $keys, $pre = [] )
	{
		foreach ( (array) $keys as $key )
			if ( $constant = $this->constant( $key ) )
				$pre[] = $constant;

		return array_unique( $pre );
	}

	public function slug()
	{
		return str_replace( '_', '-', $this->module->name );
	}

	protected function help_tab_default_terms( $constant )
	{
		if ( ! $taxonomy = WordPress\Taxonomy::object( $this->constant( $constant ) ) )
			return;

		/* translators: %s: taxonomy object label */
		$title  = sprintf( _x( 'Default Terms for %s', 'Module', 'geditorial' ), $taxonomy->label );
		/* translators: %s: taxonomy object label */
		$edit   = sprintf( _x( 'Edit Terms for %s', 'Module', 'geditorial' ), $taxonomy->label );
		$terms  = $this->get_default_terms( $constant );
		$link   = Core\WordPress::getEditTaxLink( $taxonomy->name );
		$before = Core\HTML::tag( 'p', $title );
		$after  = Core\HTML::tag( 'p', Core\HTML::link( $edit, $link, TRUE ) );
		$args   = [ 'title' => $taxonomy->label, 'id' => $this->classs( 'help-default-terms', '-'.$taxonomy->name ) ];

		if ( empty( $terms ) )
			$args['content'] = $before.Core\HTML::wrap( _x( 'No Default Terms', 'Module', 'geditorial' ), '-info' ).$after;

		else if ( Core\Arraay::allStringValues( $terms ) )
			$args['content'] = $before.Core\HTML::wrap( Core\HTML::tableCode( $terms, TRUE ), '-info' ).$after;

		else
			$args['content'] = $before.Core\HTML::wrap( Core\HTML::tableCode( Core\Arraay::pluck( $terms, 'name', 'slug' ), TRUE ), '-info' ).$after;

		get_current_screen()->add_help_tab( $args );
	}

	// DEPRECATED: use `$this->register_default_terms()`
	protected function insert_default_terms( $constant, $terms = NULL )
	{
		if ( ! $this->nonce_verify( 'settings' ) )
			return;

		$taxonomy = $this->constant( $constant );

		if ( ! taxonomy_exists( $taxonomy ) )
			return;

		if ( is_null( $terms ) )
			$terms = $this->get_default_terms( $constant );

		if ( empty( $terms ) )
			$message = 'noadded';

		else if ( $added = WordPress\Taxonomy::insertDefaultTerms( $taxonomy, $terms ) )
			$message = [
				'message' => 'created',
				'count'   => count( $added ),
			];

		else
			$message = 'wrong';

		Core\WordPress::redirectReferer( $message );
	}

	// NOTE: hook filter before `init` on `after_setup_theme`
	protected function get_default_terms( $constant )
	{
		if ( ! empty( $this->strings['default_terms'][$constant] ) )
			$terms = $this->strings['default_terms'][$constant];

		// DEPRECATED: use `default_terms` key
		else if ( ! empty( $this->strings['terms'][$constant] ) )
			$terms = $this->strings['terms'][$constant];

		else
			$terms = [];

		return $this->filters( 'get_default_terms', $terms, $this->constant( $constant ) );
	}

	protected function register_default_terms( $constant, $terms = NULL )
	{
		if ( ! defined( 'GNETWORK_VERSION' ) )
			return FALSE;

		if ( ! is_admin() )
			return FALSE;

		if ( is_null( $terms ) )
			$terms = $this->get_default_terms( $constant );

		if ( empty( $terms ) )
			return FALSE;

		add_filter( 'gnetwork_taxonomy_default_terms_'.$this->constant( $constant ),
			static function( $pre ) use ( $terms ) {
				return array_merge( $pre, Core\Arraay::isAssoc( $terms ) ? $terms : Core\Arraay::sameKey( $terms ) );
			} );
	}

	protected function check_settings( $sub, $context = 'tools', $extra = [], $key = NULL )
	{
		if ( ! $this->cuc( $context ) )
			return FALSE;

		if ( is_null( $key ) )
			$key = $this->key;

		if ( $key == $this->key )
			add_filter( $this->base.'_'.$context.'_subs', [ $this, 'append_sub' ], 10, 2 );

		$subs = array_merge( [ $key ], (array) $extra );

		if ( ! in_array( $sub, $subs ) )
			return FALSE;

		foreach ( $subs as $supported )
			add_action( $this->base.'_'.$context.'_sub_'.$supported, [ $this, $context.'_sub' ], 10, 2 );

		if ( 'settings' != $context ) {

			$this->register_help_tabs( NULL, $context );

			add_action( 'admin_print_footer_scripts', [ $this, 'settings_print_scripts' ], 99 );
		}

		return TRUE;
	}

	public function init_settings()
	{
		if ( ! isset( $this->settings ) )
			$this->settings = $this->filters( 'settings', $this->get_global_settings(), $this->module );
	}

	public function register_settings( $module = FALSE )
	{
		if ( $module != $this->module->name )
			return;

		$this->init_settings();

		// FIXME: find a better way
		if ( $this->setup_disabled() )
			$this->strings = $this->filters( 'strings', $this->get_global_strings(), $this->module );

		if ( method_exists( $this, 'before_settings' ) )
			$this->before_settings( $module );

		foreach ( $this->settings as $section_suffix => $fields ) {

			if ( is_array( $fields ) ) {

				$section = $this->base.'_'.$this->module->name.$section_suffix;

				if ( method_exists( $this, 'settings_section'.$section_suffix ) )
					$callback = [ $this, 'settings_section'.$section_suffix ];
				else if ( method_exists( __NAMESPACE__.'\\Settings', 'settings_section'.$section_suffix ) )
					$callback = [ __NAMESPACE__.'\\Settings', 'settings_section'.$section_suffix ];
				else
					$callback = '__return_false';

				Settings::addModuleSection( $this->base.'_'.$this->module->name, [
					'id'            => $section,
					'callback'      => $callback,
					'section_class' => 'settings_section',
				] );

				foreach ( $fields as $key => $field ) {

					if ( FALSE === $field )
						continue;

					if ( is_string( $key ) && method_exists( __NAMESPACE__.'\\Settings', 'getSetting_'.$key ) )
						$args = call_user_func_array( [ __NAMESPACE__.'\\Settings', 'getSetting_'.$key ], (array) $field );

					else if ( is_string( $field ) && method_exists( __NAMESPACE__.'\\Settings', 'getSetting_'.$field ) )
						$args = call_user_func( [ __NAMESPACE__.'\\Settings', 'getSetting_'.$field ] );

					else if ( ! is_string( $key ) && is_array( $field ) )
						$args = $field;

					else
						continue;

					$this->add_settings_field( array_merge( $args, [ 'section' => $section ] ) );
				}

			} else if ( method_exists( $this, 'register_settings_'.$section_suffix ) ) {

				$title = $section_suffix == $fields ? NULL : $fields;

				call_user_func_array( [ $this, 'register_settings_'.$section_suffix ], [ $title ] );
			}
		}

		$this->default_buttons( $module );
		$this->register_help_tabs();

		// register settings on the settings page only
		add_action( 'admin_print_footer_scripts', [ $this, 'settings_print_scripts' ], 99 );
	}

	protected function register_help_tabs( $screen = NULL, $context = 'settings' )
	{
		if ( ! Core\WordPress::mustRegisterUI( FALSE ) || self::req( 'noheader' ) )
			return;

		if ( is_null( $screen ) )
			$screen = get_current_screen();

		foreach ( $this->settings_help_tabs( $context ) as $tab )
			$screen->add_help_tab( $tab );

		if ( $sidebar = $this->settings_help_sidebar( $context ) )
			$screen->set_help_sidebar( $sidebar );

		if ( ! in_array( $context, [ 'settings' ], TRUE ) )
			return;

		if ( ! empty( $this->strings['default_terms'] ) )
			foreach ( array_keys( $this->strings['default_terms'] ) as $taxonomy_constant )
				$this->help_tab_default_terms( $taxonomy_constant );
	}

	public function settings_header()
	{
		$back = $count = $flush = $filters = FALSE;

		if ( 'config' == $this->module->name ) {
			$title   = NULL;
			$count   = gEditorial()->count();
			$flush   = Core\WordPress::maybeFlushRules();
			$filters = TRUE;
		} else {
			/* translators: %s: module title */
			$title = sprintf( _x( 'Editorial: %s', 'Module', 'geditorial' ), $this->module->title );
			$back  = Settings::settingsURL();
		}

		Settings::wrapOpen( $this->module->name );

			Settings::headerTitle( $title, $back, NULL, $this->module->icon, $count, TRUE, $filters );
			Settings::message();

			if ( $flush )
				echo Core\HTML::warning( _x( 'You need to flush rewrite rules!', 'Module', 'geditorial' ), FALSE );

			echo '<div class="-header">';

			if ( isset( $this->module->desc ) && $this->module->desc )
				echo '<h4>'.$this->module->desc.'</h4>';

			if ( method_exists( $this, 'settings_intro' ) )
				$this->settings_intro();

		Settings::wrapClose();
	}

	protected function settings_footer()
	{
		if ( 'config' == $this->module->name )
			Settings::settingsCredits();

		else
			$this->settings_signature( 'settings' );
	}

	protected function settings_signature( $context = 'settings' )
	{
		Settings::settingsSignature();
	}

	public function settings_section_defaults()
	{
		Settings::fieldSection(
			_x( 'Defaults', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_misc()
	{
		Settings::fieldSection(
			_x( 'Miscellaneous', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_frontend()
	{
		Settings::fieldSection(
			_x( 'Front-end', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_backend()
	{
		Settings::fieldSection(
			_x( 'Back-end', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_content()
	{
		Settings::fieldSection(
			_x( 'Generated Contents', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_dashboard()
	{
		Settings::fieldSection(
			_x( 'Admin Dashboard', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_editlist()
	{
		Settings::fieldSection(
			_x( 'Admin Edit List', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_columns()
	{
		Settings::fieldSection(
			_x( 'Admin List Columns', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_editpost()
	{
		Settings::fieldSection(
			_x( 'Admin Edit Post', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_edittags()
	{
		Settings::fieldSection(
			_x( 'Admin Edit Terms', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_comments()
	{
		Settings::fieldSection(
			_x( 'Admin Comment List', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_strings()
	{
		Settings::fieldSection(
			_x( 'Custom Strings', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_roles()
	{
		Settings::fieldSection(
			_x( 'Availability', 'Module: Setting Section Title', 'geditorial' ),
			_x( 'Though Administrators have it all!', 'Module: Setting Section Description', 'geditorial' )
		);
	}

	public function add_settings_field( $r = [] )
	{
		$args = array_merge( [
			'page'        => $this->base.'_'.$this->module->name,
			'section'     => $this->base.'_'.$this->module->name.'_general',
			'field'       => FALSE,
			'label_for'   => '',
			'title'       => '',
			'description' => '',
			'callback'    => [ $this, 'do_settings_field' ],
		], $r );

		if ( ! $args['field'] )
			return;

		if ( empty( $args['title'] ) )
			$args['title'] = $args['field'];

		add_settings_field( $args['field'], $args['title'], $args['callback'], $args['page'], $args['section'], $args );
	}

	public function settings_id_name_cb( $args )
	{
		if ( $args['option_group'] )
			return [
				( $args['id_attr'] ? $args['id_attr'] : $args['option_base'].'-'.$args['option_group'].'-'.$args['field'] ),
				( $args['name_attr'] ? $args['name_attr'] : $args['option_base'].'['.$args['option_group'].']['.$args['field'].']' ),
			];

		return [
			( $args['id_attr'] ? $args['id_attr'] : $args['option_base'].'-'.$args['field'] ),
			( $args['name_attr'] ? $args['name_attr'] : $args['option_base'].'['.$args['field'].']' ),
		];
	}

	public function do_settings_field( $atts = [] )
	{
		$args = array_merge( [
			'options'      => isset( $this->options->settings ) ? $this->options->settings : [],
			'option_base'  => $this->base.'_'.$this->module->name,
			'option_group' => 'settings',
			'id_name_cb'   => [ $this, 'settings_id_name_cb' ],
		], $atts );

		if ( empty( $args['cap'] ) )
			$args['cap'] = empty( $this->caps[$args['option_group']] ) ? NULL : $this->caps[$args['option_group']];

		Settings::fieldType( $args, $this->scripts );
	}

	public function do_posttype_field( $atts = [], $post = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return;

		$args = array_merge( [
			'option_base'  => $this->hook(),
			'option_group' => 'fields',
			'id_name_cb'   => [ $this, 'settings_id_name_cb' ],
			'cap'          => TRUE,
		], $atts );

		if ( ! array_key_exists( 'options', $args ) )
			$args['options'] = get_post_meta( $post->ID ); //  $this->get_postmeta_legacy( $post->ID );

		if ( empty( $args['cap'] ) )
			$args['cap'] = empty( $this->caps[$args['option_group']] ) ? NULL : $this->caps[$args['option_group']];

		Settings::fieldType( $args, $this->scripts );
	}

	public function settings_print_scripts()
	{
		if ( $this->scripts_printed )
			return;

		if ( count( $this->scripts ) )
			Core\HTML::wrapjQueryReady( implode( "\n", $this->scripts ) );

		$this->scripts_printed = TRUE;
	}

	// NOTE: features are `TRUE` by default
	public function get_feature( $field, $fallback = TRUE )
	{
		$settings = isset( $this->options->settings ) ? $this->options->settings : [];

		if ( array_key_exists( $field, $settings ) )
			return $settings[$field];

		if ( array_key_exists( $field, $this->features ) )
			return $this->features[$field];

		return $fallback;
	}

	public function get_setting( $field, $fallback = NULL )
	{
		$settings = isset( $this->options->settings ) ? $this->options->settings : [];

		if ( array_key_exists( $field, $settings ) )
			return $settings[$field];

		if ( array_key_exists( $field, $this->deafults ) )
			return $this->deafults[$field];

		return $fallback;
	}

	public function get_setting_fallback( $field, $fallback, $empty = '' )
	{
		$settings = isset( $this->options->settings ) ? $this->options->settings : [];

		if ( array_key_exists( $field, $settings ) ) {

			if ( '0' === $settings[$field] )
				return $empty;

			if ( ! empty( $settings[$field] ) )
				return $settings[$field];
		}

		if ( array_key_exists( $field, $this->deafults ) )
			return $this->deafults[$field];

		return $fallback;
	}

	// check arrays with support of old settings
	public function in_setting( $item, $field, $default = [] )
	{
		$setting = $this->get_setting( $field );

		if ( FALSE === $setting || TRUE === $setting )
			return $setting;

		if ( is_null( $setting ) )
			$setting = $default;

		return in_array( $item, (array) $setting, TRUE );
	}

	/**
	 * Retrieves settings option for the target posttypes.
	 * NOTE: common targets: `subcontent`, `parent`, `directed`
	 *
	 * @param  string $target
	 * @param  array  $fallback
	 * @return array  $posttypes
	 */
	public function get_setting_posttypes( $target, $fallback = [] )
	{
		return $this->get_setting( sprintf( '%s_posttypes', $target ), $fallback );
	}

	/**
	 * Retrieves settings option for the target taxonomies.
	 *
	 * @param  string $target
	 * @param  array  $fallback
	 * @return array  $posttypes
	 */
	public function get_setting_taxonomies( $target, $fallback = [] )
	{
		return $this->get_setting( sprintf( '%s_taxonomies', $target ), $fallback );
	}

	/**
	 * Updates module options upon out-of-context manipulations.
	 *
	 * @param  string $key
	 * @param  mixed $value
	 * @return bool $updated
	 */
	public function update_option( $key, $value )
	{
		return gEditorial()->update_module_option( $this->module->name, $key, $value );
	}

	public function get_posttype_label( $constant, $label = 'name', $fallback = '' )
	{
		return Helper::getPostTypeLabel( $this->constant( $constant, $constant ), $label, NULL, $fallback );
	}

	public function get_posttype_labels( $constant )
	{
		if ( isset( $this->strings['labels'] )
			&& array_key_exists( $constant, $this->strings['labels'] ) )
				$labels = $this->strings['labels'][$constant];
		else
			$labels = [];

		if ( FALSE === $labels )
			return FALSE;

		// DEPRECATED: back-comp
		if ( $menu_name = $this->get_string( 'menu_name', $constant, 'misc', NULL ) )
			$labels['menu_name'] = $menu_name;

		// DEPRECATED: back-comp
		if ( $author_metabox = $this->get_string( 'author_metabox', $constant, 'misc', NULL ) )
			$labels['author_label'] = $author_metabox;

		// DEPRECATED: back-comp
		if ( $excerpt_metabox = $this->get_string( 'excerpt_metabox', $constant, 'misc', NULL ) )
			$labels['excerpt_label'] = $excerpt_metabox;

		if ( ! empty( $this->strings['noops'][$constant] ) )
			return Helper::generatePostTypeLabels(
				$this->strings['noops'][$constant],
				// DEPRECATED: back-comp: use `labels->featured_image`
				$this->get_string( 'featured', $constant, 'misc', NULL ),
				$labels,
				$this->constant( $constant )
			);

		return $labels;
	}

	protected function get_posttype_supports( $constant )
	{
		if ( isset( $this->options->settings[$constant.'_supports'] ) )
			return $this->options->settings[$constant.'_supports'];

		return array_keys( Settings::supportsOptions() );
	}

	public function get_posttype_icon( $constant = NULL, $default = 'welcome-write-blog' )
	{
		$icon  = $this->module->icon ? $this->module->icon : $default;
		$icons = $this->get_module_icons();

		if ( $constant && isset( $icons['post_types'][$constant] ) )
			$icon = $icons['post_types'][$constant];

		if ( is_array( $icon ) )
			$icon = Core\Icon::getBase64( $icon[1], $icon[0] );

		else if ( $icon )
			$icon = 'dashicons-'.$icon;

		return $icon ?: 'dashicons-'.$default;
	}

	// NOTE: also accepts: `[ 'story', 'stories' ]`
	public function get_posttype_cap_type( $constant )
	{
		$default = $this->constant( $constant.'_cap_type', 'post' );

		if ( ! gEditorial()->enabled( 'roles' ) )
			return $default;

		if ( ! in_array( $this->constant( $constant ), gEditorial()->module( 'roles' )->posttypes() ) )
			return $default;

		return gEditorial()->module( 'roles' )->constant( 'base_type' );
	}

	public function register_posttype( $constant, $atts = [], $taxonomies = [ 'post_tag' ], $block_editor = FALSE )
	{
		$posttype = $this->constant( $constant );
		$cap_type = $this->get_posttype_cap_type( $constant );
		$plural   = str_replace( '_', '-', Core\L10n::pluralize( $posttype ) );

		$args = self::recursiveParseArgs( $atts, [
			'description' => isset( $this->strings['labels'][$constant]['description'] ) ? $this->strings['labels'][$constant]['description'] : '',

			'show_in_menu'  => NULL, // or TRUE or `$parent_slug`
			'menu_icon'     => $this->get_posttype_icon( $constant ),
			'menu_position' => empty( $this->positions[$constant] ) ? 4 : $this->positions[$constant],

			// 'show_in_nav_menus' => TRUE,
			// 'show_in_admin_bar' => TRUE,

			'query_var'   => $this->constant( $constant.'_query_var', $posttype ),
			'has_archive' => $this->constant( $constant.'_archive', $plural ),

			'rewrite' => NULL,

			'hierarchical' => FALSE,
			'public'       => TRUE,
			'show_ui'      => TRUE,

			'capability_type' => $cap_type,
			'map_meta_cap'    => TRUE,

			'register_meta_box_cb' => method_exists( $this, 'add_meta_box_cb_'.$constant ) ? [ $this, 'add_meta_box_cb_'.$constant ] : NULL,

			'show_in_rest' => TRUE,
			'rest_base'    => $this->constant( $constant.'_rest', $this->constant( $constant.'_archive', $plural ) ),

			// 'rest_namespace ' => 'wp/v2', // @SEE: https://core.trac.wordpress.org/ticket/54536

			'can_export'          => TRUE,
			'delete_with_user'    => FALSE,
			'exclude_from_search' => $this->get_setting( $constant.'_exclude_search', FALSE ),

			/// gEditorial Props
			WordPress\PostType::PRIMARY_TAXONOMY_PROP => NULL,   // @SEE: `PostType::getPrimaryTaxonomy()`
			Services\Paired::PAIRED_TAXONOMY_PROP     => FALSE,  // @SEE: `Paired::isPostType()`

			/// Misc Props
			// @SEE: https://github.com/torounit/custom-post-type-permalinks
			'cptp_permalink_structure' => $this->constant( $constant.'_permalink', FALSE ), // will lock the permalink

			// only `%post_id%` and `%postname%`
			// @SEE: https://github.com/torounit/simple-post-type-permalinks
			'sptp_permalink_structure' => $this->constant( $constant.'_permalink', FALSE ), // will lock the permalink
		] );

		$rewrite = [
			'slug'       => $this->constant( $constant.'_slug', str_replace( '_', '-', $posttype ) ),
			'ep_mask'    => $this->constant( $constant.'_endpoint', EP_PERMALINK | EP_PAGES ), // https://make.wordpress.org/plugins?p=29
			'with_front' => FALSE,
			'feeds'      => TRUE,
			'pages'      => TRUE,
		];

		if ( is_null( $args['rewrite'] ) )
			$args['rewrite'] = $rewrite;

		else if ( is_array( $args['rewrite'] ) )
			$args['rewrite'] = array_merge( $rewrite, $args['rewrite'] );

		if ( ! array_key_exists( 'labels', $args ) )
			$args['labels'] = $this->get_posttype_labels( $constant );

		if ( ! array_key_exists( 'taxonomies', $args ) )
			$args['taxonomies'] = is_null( $taxonomies ) ? $this->taxonomies() : $taxonomies;

		if ( ! array_key_exists( 'supports', $args ) )
			$args['supports'] = $this->get_posttype_supports( $constant );

		// @ALSO SEE: https://core.trac.wordpress.org/ticket/22895
		if ( ! array_key_exists( 'capabilities', $args ) && 'post' != $cap_type )
			$args['capabilities'] = [ 'create_posts' => is_array( $cap_type ) ? 'create_'.$cap_type[1] : 'create_'.$cap_type.'s' ];

		$object = register_post_type( $posttype, $args );

		if ( self::isError( $object ) )
			return $this->log( 'CRITICAL', $object->get_error_message(), $args );

		if ( ! $block_editor )
			add_filter( 'use_block_editor_for_post_type', static function( $edit, $type ) use ( $posttype ) {
				return $posttype === $type ? FALSE : $edit;
			}, 12, 2 );

		return $object;
	}

	// NOTE: reversed fallback/fallback-key
	public function get_taxonomy_label( $constant, $label = 'name', $fallback = '', $fallback_key = NULL )
	{
		return Helper::getTaxonomyLabel( $this->constant( $constant, $constant ), $label, $fallback_key, $fallback );
	}

	public function get_taxonomy_labels( $constant )
	{
		if ( isset( $this->strings['labels'] )
			&& array_key_exists( $constant, $this->strings['labels'] ) )
				$labels = $this->strings['labels'][$constant];
		else
			$labels = [];

		if ( FALSE === $labels )
			return FALSE;

		// DEPRECATED: back-comp
		if ( $menu_name = $this->get_string( 'menu_name', $constant, 'misc', NULL ) )
			$labels['menu_name'] = $menu_name;

		if ( ! empty( $this->strings['noops'][$constant] ) )
			return Helper::generateTaxonomyLabels(
				$this->strings['noops'][$constant],
				$labels,
				$this->constant( $constant )
			);

		return $labels;
	}

	public function get_taxonomy_icon( $constant = NULL, $hierarchical = FALSE, $fallback = FALSE )
	{
		$icons   = $this->get_module_icons();
		$default = $hierarchical ? 'category' : 'tag';
		$module  = $this->module->icon ?? FALSE;

		if ( is_null( $fallback ) && $module )
			$icon = $module;

		else if ( $fallback )
			$icon = $fallback;

		else
			$icon = $default;

		if ( $constant && isset( $icons['taxonomies'] ) && array_key_exists( $constant, (array) $icons['taxonomies'] ) )
			$icon = $icons['taxonomies'][$constant];

		if ( is_null( $icon ) && $module )
			$icon = $module;

		if ( is_array( $icon ) )
			$icon = Core\Icon::getBase64( $icon[1], $icon[0] );

		else if ( $icon )
			$icon = 'dashicons-'.$icon;

		return $icon ?: 'dashicons-'.$default;
	}

	protected function _get_taxonomy_caps( $taxonomy, $caps, $posttypes )
	{
		if ( is_array( $caps ) )
			return $caps;

		$custom = [
			'manage_terms' => 'manage_'.$taxonomy,
			'edit_terms'   => 'edit_'.$taxonomy,
			'delete_terms' => 'delete_'.$taxonomy,
			'assign_terms' => 'assign_'.$taxonomy,
		];

		if ( TRUE === $caps )
			return $custom;

		// core default
		if ( FALSE === $caps )
			return [
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_posts',
			];

		$defaults = [
			'manage_terms' => 'edit_others_posts',
			'edit_terms'   => 'edit_others_posts',
			'delete_terms' => 'edit_others_posts',
			'assign_terms' => 'edit_posts',
		];

		// FIXME: `edit_users` is not working!
		// maybe map meta cap
		if ( 'user' == $posttypes )
			return [
				'manage_terms' => 'edit_users',
				'edit_terms'   => 'list_users',
				'delete_terms' => 'list_users',
				'assign_terms' => 'list_users',
			];

		else if ( 'taxonomy' === $posttypes )
			return $custom; // FIXME: must filter meta_cap

		else if ( 'comment' == $posttypes )
			return $defaults; // FIXME: WTF?!

		if ( ! gEditorial()->enabled( 'roles' ) )
			return $defaults;

		if ( ! is_null( $caps ) )
			$posttype = $this->constant( $caps );

		else if ( count( $posttypes ) )
			$posttype = $posttypes[0];

		else
			return $defaults;

		if ( ! in_array( $posttype, gEditorial()->module( 'roles' )->posttypes() ) )
			return $defaults;

		$base = gEditorial()->module( 'roles' )->constant( 'base_type' );

		return [
			'manage_terms' => 'edit_others_'.$base[1],
			'edit_terms'   => 'edit_others_'.$base[1],
			'delete_terms' => 'edit_others_'.$base[1],
			'assign_terms' => 'edit_'.$base[1],
		];
	}

	// WTF: the core default term system is messed-up!
	// @REF: https://core.trac.wordpress.org/ticket/43517
	protected function _get_taxonomy_default_term( $constant, $passed_arg = NULL )
	{
		return FALSE; // FIXME <------------------------------------------------

		// disabled by settings
		if ( is_null( $passed_arg ) && ! $this->get_setting( 'assign_default_term' ) )
			return FALSE;

		if ( isset( $this->strings['defaults'] )
			&& array_key_exists( $constant, $this->strings['defaults'] ) )
				$term = $this->strings['defaults'][$constant];
		else
			$term = [];

		if ( empty( $term['name'] ) )
			$term['name'] = is_string( $passed_arg )
				? $passed_arg
				: _x( 'Uncategorized', 'Module: Taxonomy Default Term Name', 'geditorial' );

		if ( empty( $term['slug'] ) )
			$term['slug'] = is_string( $passed_arg ) ? $passed_arg : 'uncategorized';

		return $term;
	}

	// @REF: https://developer.wordpress.org/reference/functions/register_taxonomy/
	public function register_taxonomy( $constant, $atts = [], $posttypes = NULL, $caps = NULL )
	{
		$cpt_tax  = TRUE;
		$taxonomy = $this->constant( $constant );
		$plural   = str_replace( '_', '-', Core\L10n::pluralize( $taxonomy ) );

		if ( is_string( $posttypes ) && in_array( $posttypes, [ 'user', 'comment', 'taxonomy' ] ) )
			$cpt_tax = FALSE;

		else if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		else if ( ! is_array( $posttypes ) )
			$posttypes = $posttypes ? [ $this->constant( $posttypes ) ] : '';

		$args = self::recursiveParseArgs( $atts, [
			'meta_box_cb'          => FALSE,
			// @REF: https://make.wordpress.org/core/2019/01/23/improved-taxonomy-metabox-sanitization-in-5-1/
			'meta_box_sanitize_cb' => method_exists( $this, 'meta_box_sanitize_cb_'.$constant ) ? [ $this, 'meta_box_sanitize_cb_'.$constant ] : NULL,
			'hierarchical'         => FALSE,
			'public'               => TRUE,
			'show_ui'              => TRUE,
			'show_admin_column'    => FALSE,
			'show_in_quick_edit'   => FALSE,
			'show_in_nav_menus'    => FALSE,
			'show_tagcloud'        => FALSE,
			'default_term'         => FALSE,
			'capabilities'         => $this->_get_taxonomy_caps( $taxonomy, $caps, $posttypes ),
			'query_var'            => $this->constant( $constant.'_query', $taxonomy ),
			'rewrite'              => NULL,

			// 'sort' => NULL, // Whether terms in this taxonomy should be sorted in the order they are provided to `wp_set_object_terms()`.
			// 'args' => [], //  Array of arguments to automatically use inside `wp_get_object_terms()` for this taxonomy.

			'show_in_rest' => TRUE,
			'rest_base'    => $this->constant( $constant.'_rest', $this->constant( $constant.'_archive', $plural ) ),
			// 'rest_namespace' => 'wp/v2', // @SEE: https://core.trac.wordpress.org/ticket/54536

			/// gEditorial Props
			WordPress\Taxonomy::TARGET_TAXONOMIES_PROP => FALSE,  // or array of taxonomies
			Services\Paired::PAIRED_POSTTYPE_PROP      => FALSE,  // @SEE: `Paired::isTaxonomy()`
		] );

		$rewrite = [

			// NOTE: we can use `example.com/cpt/tax` if cpt registered after the tax
			// @REF: https://developer.wordpress.org/reference/functions/register_taxonomy/#comment-2274

			// NOTE: taxonomy prefix slugs are singular: `/category/`, `/tag/`
			'slug'         => $this->constant( $constant.'_slug', str_replace( '_', '-', $taxonomy ) ),
			'with_front'   => FALSE,
			'hierarchical' => $args['hierarchical'],
			// 'ep_mask'      => EP_NONE,
		];

		if ( is_null( $args['rewrite'] ) )
			$args['rewrite'] = $rewrite;

		else if ( is_array( $args['rewrite'] ) )
			$args['rewrite'] = array_merge( $rewrite, $args['rewrite'] );

		if ( ! $args['meta_box_cb'] && method_exists( $this, 'meta_box_cb_'.$constant ) )
			$args['meta_box_cb'] = [ $this, 'meta_box_cb_'.$constant ];

		else if ( '__checklist_terms_callback' === $args['meta_box_cb'] )
			$args['meta_box_cb'] = [ $this, 'taxonomy_meta_box_checklist_terms_cb' ];

		else if ( '__checklist_reverse_terms_callback' === $args['meta_box_cb'] )
			$args['meta_box_cb'] = [ $this, 'taxonomy_meta_box_checklist_reverse_terms_cb' ];

		else if ( '__checklist_restricted_terms_callback' === $args['meta_box_cb'] )
			$args['meta_box_cb'] = [ $this, 'taxonomy_meta_box_checklist_restricted_terms_cb' ];

		else if ( '__singleselect_terms_callback' === $args['meta_box_cb'] )
			$args['meta_box_cb'] = [ $this, 'taxonomy_meta_box_singleselect_terms_cb' ];

		if ( ! array_key_exists( 'labels', $args ) )
			$args['labels'] = $this->get_taxonomy_labels( $constant );

		if ( ! array_key_exists( 'update_count_callback', $args ) ) {

			if ( $cpt_tax )
				// $args['update_count_callback'] = [ __NAMESPACE__.'\\WordPress\\Database', 'updateCountCallback' ];
				$args['update_count_callback'] = '_update_post_term_count';

			else if ( 'user' == $posttypes )
				$args['update_count_callback'] = [ __NAMESPACE__.'\\WordPress\\Database', 'updateUserTermCountCallback' ];

			else if ( 'comment' == $posttypes )
				$args['update_count_callback'] = [ __NAMESPACE__.'\\WordPress\\Database', 'updateCountCallback' ];

			else if ( 'taxonomy' == $posttypes )
				$args['update_count_callback'] = [ __NAMESPACE__.'\\WordPress\\Database', 'updateCountCallback' ];

			// WTF: if not else ?!

			// if ( is_admin() && ( $cpt_tax || 'user' == $posttypes || 'comment' == $posttypes ) )
			// 	$this->_hook_taxonomies_excluded( $constant, 'recount' );
		}

		if ( FALSE !== $args['default_term'] )
			$args['default_term'] = $this->_get_taxonomy_default_term( $constant, $args['default_term'] );

		// NOTE: gEditorial Prop
		if ( ! array_key_exists( 'has_archive', $args ) && $args['public'] && $args['show_ui'] )
			$args['has_archive'] = $this->constant( $constant.'_archive', $plural );

		// NOTE: gEditorial Prop
		if ( ! array_key_exists( 'menu_icon', $args ) )
			$args['menu_icon'] = $this->get_taxonomy_icon( $constant, $args['hierarchical'] );

		$object = register_taxonomy( $taxonomy, $cpt_tax ? $posttypes : '', $args );

		if ( self::isError( $object ) )
			return $this->log( 'CRITICAL', $object->get_error_message(), $args );

		return $object;
	}

	// DEFAULT CALLBACK for `__checklist_terms_callback`
	public function taxonomy_meta_box_checklist_terms_cb( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, [ 'taxonomy' => $box['args']['taxonomy'], 'posttype' => $post->post_type ] );
		echo '</div>';
	}

	// DEFAULT CALLBACK for `__checklist_reverse_terms_callback`
	public function taxonomy_meta_box_checklist_reverse_terms_cb( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		// NOTE: getting reverse-sorted span terms to pass into checklist
		$terms = WordPress\Taxonomy::listTerms( $box['args']['taxonomy'], 'all', [ 'order' => 'DESC' ] );

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, [ 'taxonomy' => $box['args']['taxonomy'], 'posttype' => $post->post_type ], $terms );
		echo '</div>';
	}

	// DEFAULT CALLBACK for `__checklist_restricted_terms_callback`
	public function taxonomy_meta_box_checklist_restricted_terms_cb( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$args = [
			'taxonomy' => $box['args']['taxonomy'],
			'posttype' => $post->post_type,
		];

		if ( $this->role_can( sprintf( 'taxonomy_%s_locking_terms', $args['taxonomy'] ), NULL, FALSE, FALSE ) )
			$args['role'] = $this->get_setting( sprintf( 'taxonomy_%s_restricted_visibility', $args['taxonomy'] ), 'disabled' );

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $args );
		echo '</div>';
	}

	// DEFAULT CALLBACK for `__singleselect_terms_callback`
	public function taxonomy_meta_box_singleselect_terms_cb( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::singleselectTerms( $post->ID, [
				'taxonomy' => $box['args']['taxonomy'],
				'posttype' => $post->post_type,
				// NOTE: metabox title already displays the taxonomy label
				'none'     => Settings::showOptionNone(),
				'empty'    => NULL, // displays empty box with link
			] );
		echo '</div>';
	}

	// PAIRED API
	// TODO: move to `PairedCore` Internal
	protected function paired_register_objects( $posttype, $paired, $subterm = FALSE, $primary = FALSE, $private = FALSE, $extra = [], $supported = NULL )
	{
		if ( is_null( $supported ) )
			$supported = $this->posttypes();

		if ( count( $supported ) ) {

			// adding the main posttype
			$supported[] = $this->constant( $posttype );

			if ( $subterm && $this->get_setting( 'subterms_support' ) )
				$this->register_taxonomy( $subterm, [
					'public'            => ! $private,
					'rewrite'           => $private ? FALSE : NULL,
					'hierarchical'      => TRUE,
					'meta_box_cb'       => NULL,
					'show_admin_column' => FALSE,
					'show_in_nav_menus' => TRUE,
				], $supported );

			$this->register_taxonomy( $paired, [
				Services\Paired::PAIRED_POSTTYPE_PROP => $this->constant( $posttype ),

				'public'       => ! $private,
				'rewrite'      => $private ? FALSE : NULL,
				'show_ui'      => FALSE,
				'show_in_rest' => FALSE,
				'hierarchical' => TRUE,

				// the paired taxonomies are often in plural
				// FIXME: WTF: conflict on the posttype rest base!
				// 'rest_base'    => $this->constant( $paired.'_slug', str_replace( '_', '-', $this->constant( $paired ) ) ),
			], $supported );

			$this->_paired = $this->constant( $paired );
			$this->filter_unset( 'wp_sitemaps_taxonomies', $this->_paired );
		}

		if ( $primary && ! array_key_exists( 'primary_taxonomy', $extra ) )
			$extra['primary_taxonomy'] = $this->constant( $primary );

		$object = $this->register_posttype( $posttype, array_merge( [
			Services\Paired::PAIRED_TAXONOMY_PROP => $this->_paired,

			'public'            => ! $private,
			'hierarchical'      => TRUE,
			'show_in_nav_menus' => TRUE,
			'show_in_admin_bar' => FALSE,
			'rewrite'           => $private ? FALSE : [
				'feeds' => (bool) $this->get_setting( 'posttype_feeds', FALSE ),
				'pages' => (bool) $this->get_setting( 'posttype_pages', FALSE ),
			],
		], $extra ) );

		if ( self::isError( $object ) )
			return $object;

		if ( method_exists( $this, 'pairedrest_register_rest_route' ) )
			$this->pairedrest_register_rest_route( $object );

		return $object;
	}

	protected function _hook_post_updated_messages( $constant )
	{
		add_filter( 'post_updated_messages', function( $messages ) use ( $constant ) {

			$posttype  = $this->constant( $constant );
			$generated = Helper::generatePostTypeMessages( $this->get_noop( $constant ), $posttype );

			return array_merge( $messages, [ $posttype => $generated ] );
		} );
	}

	protected function _hook_bulk_post_updated_messages( $constant )
	{
		add_filter( 'bulk_post_updated_messages', function( $messages, $counts ) use ( $constant ) {

			$posttype  = $this->constant( $constant );
			$generated = Helper::generateBulkPostTypeMessages( $this->get_noop( $constant ), $counts, $posttype );

			return array_merge( $messages, [ $posttype => $generated ] );
		}, 10, 2 );
	}

	public function get_image_sizes( $posttype )
	{
		if ( ! isset( $this->image_sizes[$posttype] ) ) {

			$sizes = $this->filters( $posttype.'_image_sizes', [] );

			if ( FALSE === $sizes ) {

				$this->image_sizes[$posttype] = []; // no sizes

			} else if ( count( $sizes ) ) {

				$this->image_sizes[$posttype] = $sizes; // custom sizes

			} else {

				foreach ( WordPress\Media::defaultImageSizes() as $size => $args )
					$this->image_sizes[$posttype][$posttype.'-'.$size] = $args;
			}
		}

		return $this->image_sizes[$posttype];
	}

	// use this on 'after_setup_theme'
	public function register_posttype_thumbnail( $constant )
	{
		if ( ! $this->get_setting( 'thumbnail_support', FALSE ) )
			return;

		$posttype = $this->constant( $constant );

		WordPress\Media::themeThumbnails( [ $posttype ] );

		foreach ( $this->get_image_sizes( $posttype ) as $name => $size )
			WordPress\Media::registerImageSize( $name, array_merge( $size, [ 'p' => [ $posttype ] ] ) );
	}

	// TODO: move to `Internals\PairedRowActions`
	protected function _hook_paired_taxonomy_bulk_actions( $posttype_orogin, $taxonomy_origin )
	{
		if ( ! $this->_paired )
			return FALSE;

		if ( TRUE !== $posttype_orogin && ! $this->posttype_supported( $posttype_orogin ) )
			return FALSE;

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		$paired_posttype = $this->constant( $constants[0] );
		// $paired_taxonomy = $this->constant( $constants[1] );

		if ( ! in_array( $taxonomy_origin, get_object_taxonomies( $paired_posttype ), TRUE ) )
			return FALSE;

		$label = $this->get_posttype_label( $constants[0], 'add_new_item' );
		$key   = sprintf( 'paired_add_new_%s', $paired_posttype );

		add_filter( 'gnetwork_taxonomy_bulk_actions', static function( $actions, $taxonomy ) use ( $taxonomy_origin, $key, $label ) {
			return $taxonomy === $taxonomy_origin ? array_merge( $actions, [ $key => $label ] ) : $actions;
		}, 20, 2 );

		add_filter( 'gnetwork_taxonomy_bulk_input', function( $callback, $action, $taxonomy )  use ( $taxonomy_origin, $key ) {
			return ( $taxonomy === $taxonomy_origin && $action === $key ) ? [ $this, 'paired_bulk_input_add_new_item' ] : $callback;
		}, 20, 3 );

		add_filter( 'gnetwork_taxonomy_bulk_callback', function( $callback, $action, $taxonomy )  use ( $taxonomy_origin, $key ) {
			return ( $taxonomy === $taxonomy_origin && $action === $key ) ? [ $this, 'paired_bulk_action_add_new_item' ] : $callback;
		}, 20, 3 );

		return $key;
	}

	// TODO: move to `Internals\PairedRowActions`
	public function paired_bulk_input_add_new_item( $taxonomy, $action )
	{
		/* translators: %s: clone into input */
		printf( _x( 'as: %s', 'Module: Taxonomy Bulk Input Label', 'geditorial' ),
			'<input name="'.$this->classs( 'paired-add-new-item-target' ).'" type="text" placeholder="'
			._x( 'New Item Title', 'Module: Taxonomy Bulk Input PlaceHolder', 'geditorial' ).'" /> ' );

		echo Core\HTML::dropdown( [
			'separeted-terms' => _x( 'Separeted Terms', 'Module: Taxonomy Bulk Input Option', 'geditorial' ),
			'cross-terms'     => _x( 'Cross Terms', 'Module: Taxonomy Bulk Input Option', 'geditorial' ),
			'union-terms'     => _x( 'Union Terms', 'Module: Taxonomy Bulk Input Option', 'geditorial' ),
		], [
			'name'     => $this->classs( 'paired-add-new-item-type' ),
			'style'    => 'float:none',
			'selected' => 'separeted-terms',
		] );
	}

	// TODO: move to `Internals\PairedRowActions`
	public function paired_bulk_action_add_new_item( $term_ids, $taxonomy, $action )
	{
		global $wpdb;

		if ( ! $this->_paired )
			return FALSE;

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		$paired_posttype = $this->constant( $constants[0] );
		$paired_taxonomy = $this->constant( $constants[1] );

		if ( $action !== sprintf( 'paired_add_new_%s', $paired_posttype ) )
			return FALSE;

		$target    = self::req( $this->classs( 'paired-add-new-item-target' ) );
		$supported = $this->posttypes();

		switch ( self::req( $this->classs( 'paired-add-new-item-type' ) ) ) {

			case 'cross-terms':

				// bail if no target given
				if ( empty( $target ) )
					return FALSE;

				// bail if post with same slug exists
				if ( WordPress\PostType::getIDbySlug( sanitize_title( $target ), $paired_posttype ) )
					return FALSE;

				$object_lists = [];

				foreach ( $term_ids as $term_id ) {

					$term = get_term( $term_id, $taxonomy );

					$object_lists[] = (array) $wpdb->get_col( $wpdb->prepare( "
						SELECT object_id FROM {$wpdb->term_relationships} t
						LEFT JOIN {$wpdb->posts} p ON p.ID = t.object_id
						WHERE t.term_taxonomy_id = %d
						AND p.post_type IN ( '".implode( "', '", esc_sql( $supported ) )."' )
					", $term->term_taxonomy_id ) );
				}

				$object_ids = Core\Arraay::prepNumeral( array_intersect( ...$object_lists ) );

				// bail if cross term results are ampty
				if ( empty( $object_ids ) )
					return FALSE;

				$inserted = wp_insert_term( $target, $paired_taxonomy, [
					'slug' => sanitize_title( $target ),
				] );

				if ( self::isError( $inserted ) )
					return FALSE;

				$cloned  = get_term( $inserted['term_id'], $paired_taxonomy );
				$post_id = WordPress\PostType::newPostFromTerm( $cloned, $paired_taxonomy, $paired_posttype );

				if ( self::isError( $post_id ) )
					return FALSE;

				if ( ! $this->paired_set_to_term( $post_id, $cloned, $constants[0], $constants[1] ) )
					return FALSE;

				WordPress\Taxonomy::disableTermCounting();

				foreach ( $object_ids as $object_id )
					wp_set_object_terms( $object_id, $cloned->term_id, $paired_taxonomy, FALSE ); // overrides!

				break;

			case 'union-terms':

				// bail if no target given
				if ( empty( $target ) )
					return FALSE;

				// bail if post with same slug exists
				if ( WordPress\PostType::getIDbySlug( sanitize_title( $target ), $paired_posttype ) )
					return FALSE;

				$object_lists = [];

				foreach ( $term_ids as $term_id ) {

					$term = get_term( $term_id, $taxonomy );

					$object_lists[] = (array) $wpdb->get_col( $wpdb->prepare( "
						SELECT object_id FROM {$wpdb->term_relationships} t
						LEFT JOIN {$wpdb->posts} p ON p.ID = t.object_id
						WHERE t.term_taxonomy_id = %d
						AND p.post_type IN ( '".implode( "', '", esc_sql( $supported ) )."' )
					", $term->term_taxonomy_id ) );
				}

				$object_ids = Core\Arraay::prepNumeral( ...$object_lists );

				// bail if cross term results are ampty
				if ( empty( $object_ids ) )
					return FALSE;

				$inserted = wp_insert_term( $target, $paired_taxonomy, [
					'slug' => sanitize_title( $target ),
				] );

				if ( self::isError( $inserted ) )
					return FALSE;

				$cloned  = get_term( $inserted['term_id'], $paired_taxonomy );
				$post_id = WordPress\PostType::newPostFromTerm( $cloned, $paired_taxonomy, $paired_posttype );

				if ( self::isError( $post_id ) )
					return FALSE;

				if ( ! $this->paired_set_to_term( $post_id, $cloned, $constants[0], $constants[1] ) )
					return FALSE;

				WordPress\Taxonomy::disableTermCounting();

				foreach ( $object_ids as $object_id )
					wp_set_object_terms( $object_id, $cloned->term_id, $paired_taxonomy, FALSE ); // overrides!

				break;
			default:
			case 'separeted-terms':

				WordPress\Taxonomy::disableTermCounting();

				foreach ( $term_ids as $term_id ) {

					$term = get_term( $term_id, $taxonomy );

					// bail if post with same slug exists
					if ( WordPress\PostType::getIDbySlug( $term->slug, $paired_posttype ) )
						continue;

					$current_objects = (array) $wpdb->get_col( $wpdb->prepare( "
						SELECT object_id FROM {$wpdb->term_relationships} t
						LEFT JOIN {$wpdb->posts} p ON p.ID = t.object_id
						WHERE t.term_taxonomy_id = %d
						AND p.post_type IN ( '".implode( "', '", esc_sql( $supported ) )."' )
					", $term->term_taxonomy_id ) );

					// bail if the term is empty
					if ( empty( $current_objects ) )
						continue;

					$name = $target ? sprintf( '%s (%s)', $target, $term->name ): $term->name;

					$inserted = wp_insert_term( $name, $paired_taxonomy, [
						'slug'        => $term->slug,
						'description' => $term->description,
					] );

					if ( self::isError( $inserted ) )
						continue;

					$cloned  = get_term( $inserted['term_id'], $paired_taxonomy );
					$post_id = WordPress\PostType::newPostFromTerm( $cloned, $paired_taxonomy, $paired_posttype );

					if ( self::isError( $post_id ) )
						continue;

					if ( ! $this->paired_set_to_term( $post_id, $cloned, $constants[0], $constants[1] ) )
						continue;

					foreach ( $current_objects as $current_object )
						wp_set_object_terms( $current_object, $cloned->term_id, $paired_taxonomy, FALSE ); // overrides!
				}
		}

		// flush the deferred term counts
		wp_update_term_count( NULL, NULL, TRUE );

		return TRUE;
	}

	protected function _hook_admin_ordering( $posttype, $orderby = 'menu_order', $order = 'DESC' )
	{
		if ( ! $this->get_setting( 'admin_ordering', TRUE ) )
			return FALSE;

		add_action( 'pre_get_posts', function( &$wp_query ) use ( $posttype, $orderby, $order ) {

			if ( ! $wp_query->is_admin )
				return;

			if ( $posttype !== $wp_query->get( 'post_type' ) )
				return;

			if ( $orderby && ! isset( $_GET['orderby'] ) )
				$wp_query->set( 'orderby', $orderby );

			if ( $order && ! isset( $_GET['order'] ) )
				$wp_query->set( 'order', $order );
		} );
	}

	// excludes paired posttype from subterm archives
	// TODO: move to `Internals\PairedFront`
	protected function _hook_paired_exclude_from_subterm()
	{
		if ( ! $this->get_setting( 'subterms_support' ) )
			return FALSE;

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[2] ) )
			return FALSE;

		add_action( 'pre_get_posts', function( &$wp_query ) use ( $constants ) {

			$subterms = $this->constant( $constants[2] );

			if ( ! $wp_query->is_main_query() || ! $wp_query->is_tax( $subterms ) )
				return;

			$primaries = WordPress\PostType::getIDs( $this->constant( $constants[0] ), [
				'tax_query' => [ [
					'taxonomy' => $subterms,
					'terms'    => [ get_queried_object_id() ],
				] ],
			] );

			if ( count( $primaries ) ) {

				if ( $not = $wp_query->get( 'post__not_in' ) )
					$primaries = Core\Arraay::prepNumeral( $not, $primaries );

				$wp_query->set( 'post__not_in', $primaries );
			}
		}, 8 );
	}

	// TODO: move to `Internals\PairedFront`
	protected function _hook_paired_override_term_link()
	{
		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		add_filter( 'term_link', function( $link, $term, $taxonomy ) use ( $constants ) {

			if ( $taxonomy !== $this->constant( $constants[1] ) )
				return $link;

			if ( $post_id = $this->paired_get_to_post_id( $term, $constants[0], $constants[1] ) )
				return get_permalink( $post_id );

			return $link;

		}, 9, 3 );
	}

	// TODO: move to `Internals\PairedThumbnail`
	protected function _hook_paired_thumbnail_fallback( $posttypes = NULL )
	{
		if ( ! $this->_paired )
			return;

		if ( ! $this->get_setting( 'thumbnail_support', FALSE ) )
			return;

		if ( ! $this->get_setting( 'thumbnail_fallback', FALSE ) )
			return;

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		// core filter @since WP 5.9.0
		add_filter( 'post_thumbnail_id', function( $thumbnail_id, $post ) use ( $posttypes ) {
			return $this->get_paired_fallback_thumbnail_id( $thumbnail_id, $post, $posttypes );
		}, 8, 2 );

		// no need @since WP 5.9.0
		if ( Core\WordPress::isWPcompatible( '5.9.0' ) )
			return;

		add_filter( 'geditorial_get_post_thumbnail_id', function( $thumbnail_id, $post_id ) use ( $posttypes ) {
			return $this->get_paired_fallback_thumbnail_id( $thumbnail_id, $post_id, $posttypes );
		}, 8, 2 );

		add_filter( 'gtheme_image_get_thumbnail_id', function( $thumbnail_id, $post_id ) use ( $posttypes ) {
			return $this->get_paired_fallback_thumbnail_id( $thumbnail_id, $post_id, $posttypes );
		}, 8, 2 );

		add_filter( 'gnetwork_rest_thumbnail_id', function( $thumbnail_id, $post_array ) use ( $posttypes ) {
			return $this->get_paired_fallback_thumbnail_id( $thumbnail_id, $post_array['id'], $posttypes );
		}, 8, 2 );
	}

	// TODO: move to `Internals\PairedThumbnail`
	protected function get_paired_fallback_thumbnail_id( $thumbnail_id, $post, $posttypes = NULL )
	{
		if ( $thumbnail_id || FALSE === $post )
			return $thumbnail_id;

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		if ( ! in_array( get_post_type( $post ), $posttypes, TRUE ) )
			return $thumbnail_id;

		if ( ! $parent = $this->get_linked_to_posts( $post, TRUE ) )
			return $thumbnail_id;

		if ( $parent_thumbnail = get_post_thumbnail_id( $parent ) )
			return $parent_thumbnail;

		return $thumbnail_id;
	}

	public function enqueue_asset_style( $name = NULL, $deps = [], $handle = NULL )
	{
		if ( is_null( $name ) )
			$name = $this->key;

		// screen passed
		else if ( is_object( $name ) )
			$name = $name->base;

		else
			$name = $this->key.'.'.$name;

		$name = str_replace( '_', '-', $name );

		if ( is_null( $handle ) )
			$handle = strtolower( $this->base.'-'.str_replace( '.', '-', $name ) );

		$prefix = is_admin() ? 'admin.' : 'front.';

		wp_enqueue_style( $handle, GEDITORIAL_URL.'assets/css/'.$prefix.$name.'.css', $deps, GEDITORIAL_VERSION, 'all' );
		wp_style_add_data( $handle, 'rtl', 'replace' );

		return $handle;
	}

	// NOTE: each script must have a `.min` version
	public function enqueue_asset_js( $args = [], $name = NULL, $deps = [ 'jquery' ], $key = NULL, $handle = NULL )
	{
		if ( is_null( $key ) )
			$key = $this->key;

		if ( is_null( $name ) )
			$name = $key;

		else if ( $name instanceof \WP_Screen )
			$name = $key.'.'.$name->base;

		if ( TRUE === $args ) {
			$args = [];

		} else if ( $args && $name && is_string( $args ) ) {
			$name.= '.'.$args;
			$args = [];
		}

		if ( $name ) {

			$name = str_replace( '_', '-', $name );

			if ( is_null( $handle ) )
				$handle = strtolower( $this->base.'-'.str_replace( '.', '-', $name ) );

			$prefix = is_admin() ? 'admin.' : 'front.';
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			wp_enqueue_script(
				$handle,
				GEDITORIAL_URL.'assets/js/'.$prefix.$name.$suffix.'.js',
				$deps,
				GEDITORIAL_VERSION,
				TRUE
			);
		}

		if ( ! array_key_exists( '_rest', $args ) && method_exists( $this, 'restapi_get_namespace' ) )
			$args['_rest'] = $this->restapi_get_namespace();

		if ( ! array_key_exists( '_nonce', $args ) && is_user_logged_in() )
			$args['_nonce'] = wp_create_nonce( $this->hook() );

		gEditorial()->enqueue_asset_config( $args, $key );

		return $handle;
	}

	// combined global styles
	// CAUTION: front only
	// TODO: also we need api for module specified css
	public function enqueue_styles()
	{
		gEditorial()->enqueue_styles();
	}

	public function register_editor_button( $plugin, $settings_key = 'editor_button' )
	{
		if ( ! $this->get_setting( $settings_key, TRUE ) )
			return;

		gEditorial()->register_editor_button( $this->hook( $plugin ),
			'assets/js/tinymce/'.$this->module->name.'.'.$plugin.'.js' );
	}

	protected function register_shortcode( $constant, $callback = NULL, $force = FALSE )
	{
		if ( ! $force && ! $this->get_setting( 'shortcode_support', FALSE ) )
			return;

		if ( is_null( $callback ) && method_exists( $this, $constant ) )
			$callback = [ $this, $constant ];

		$shortcode = $this->constant( $constant );

		remove_shortcode( $shortcode );
		add_shortcode( $shortcode, $callback );

		add_filter( $this->base.'_shortcode_'.$shortcode, $callback, 10, 3 );
	}

	// DEFAULT FILTER
	public function calendar_post_row_title( $title, $post, $the_day, $calendar_args )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $title;

		if ( ! $linked = $this->get_linked_to_posts( $post->ID, TRUE ) )
			return $title;

		return $title.' – '.WordPress\Post::title( $linked );
	}

	// NOTE: better to be on module-core than the internal
	public function default_calendar( $default = 'gregorian' )
	{
		return $this->get_setting( 'calendar_type', $default );
	}

	public function get_search_form( $constant_or_hidden = [], $search_query = FALSE )
	{
		if ( ! $this->get_setting( 'display_searchform' ) )
			return '';

		if ( $search_query )
			add_filter( 'get_search_query', static function( $query ) use ( $search_query ) {
				return $query ? $query : $search_query;
			} );

		$form = get_search_form( FALSE );

		if ( $constant_or_hidden && ! is_array( $constant_or_hidden ) )
			$constant_or_hidden = [ 'post_type[]' => $this->constant( $constant_or_hidden ) ];

		if ( ! count( $constant_or_hidden ) )
			return $form;

		$form = str_replace( '</form>', '', $form );

		foreach ( $constant_or_hidden as $name => $value )
			$form.= '<input type="hidden" name="'.Core\HTML::escape( $name ).'" value="'.Core\HTML::escape( $value ).'" />';

		return $form.'</form>';
	}

	/**
	 * Gets posttype parents for use in settings.
	 *
	 * @param  array        $extra
	 * @param  null|string  $capability
	 * @return array        $posttypes
	 */
	protected function get_settings_posttypes_parents( $extra = [], $capability = NULL )
	{
		$list       = [];
		$posttypes = WordPress\PostType::get( 0, [ 'show_ui' => TRUE ], $capability );

		foreach ( $this->posttypes_parents( $extra ) as $posttype ) {

			if ( array_key_exists( $posttype, $posttypes ) )
				$list[$posttype] = $posttypes[$posttype];

			// only if no checks required
			else if ( is_null( $capability ) && post_type_exists( $posttype ) )
				$list[$posttype] = $posttype;
		}

		return $list;
	}

	/**
	 * Gets default roles for use in settings.
	 *
	 * @param  array $extra_excludes
	 * @param  bool  $filtered
	 * @return array $rols
	 */
	protected function get_settings_default_roles( $extra_excludes = [], $force_include = [], $filtered = TRUE )
	{
		$supported = WordPress\User::getAllRoleList( $filtered );
		$excluded  = Settings::rolesExcluded( $extra_excludes );

		return array_merge( array_diff_key( $supported, array_flip( $excluded ), (array) $force_include ) );
	}

	// NOTE: accepts array and performs `OR` check
	protected function role_can( $whats = 'supported', $user_id = NULL, $fallback = FALSE, $admins = TRUE, $prefix = '_roles' )
	{
		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		if ( ! $user_id )
			return $fallback;

		if ( $admins && WordPress\User::isSuperAdmin( $user_id ) )
			return TRUE;

		foreach ( (array) $whats as $what ) {

			$setting = $this->get_setting( $what.$prefix, [] );

			if ( TRUE === $setting )
				return $setting;

			if ( FALSE === $setting || ( empty( $setting ) && ! $admins ) )
				continue; // check others

			if ( $admins )
				$setting = array_merge( $setting, [ 'administrator' ] );

			if ( WordPress\User::hasRole( $setting, $user_id ) )
				return TRUE;
		}

		return $fallback;
	}

	/**
	 * returns post ids with selected terms from settings
	 * that will be excluded form dropdown on supported post-types
	 *
	 * TODO: move to `Internals\PairedMetaBox`
	 *
	 * @return array
	 */
	protected function paired_get_dropdown_excludes()
	{
		if ( ! $terms = $this->get_setting( 'paired_exclude_terms' ) )
			return [];

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[3] ) )
			return [];

		$args = [
			'post_type' => $this->constant( $constants[0] ),
			'tax_query' => [ [
				'taxonomy' => $this->constant( $constants[3] ),
				'terms'    => $terms,
			] ],
			'fields'         => 'ids',
			'posts_per_page' => -1,

			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		];

		$query = new \WP_Query();
		return $query->query( $args );
	}

	// NOTE: subterms must be hierarchical
	// OLD: `do_render_metabox_assoc()`
	// TODO: move to `Internals\PairedMetaBox`
	protected function paired_do_render_metabox( $post, $posttype_constant, $paired_constant, $subterm_constant = FALSE, $display_empty = FALSE )
	{
		$subterm   = FALSE;
		$dropdowns = $displayed = $parents = [];
		$excludes  = $this->paired_get_dropdown_excludes();
		$multiple  = $this->get_setting( 'multiple_instances', FALSE );
		$forced    = $this->get_setting( 'paired_force_parents', FALSE );
		$posttype  = $this->constant( $posttype_constant );
		$paired    = $this->constant( $paired_constant );
		$terms     = WordPress\Taxonomy::getPostTerms( $paired, $post );
		$none_main = Helper::getPostTypeLabel( $posttype, 'show_option_select' );
		$prefix    = $this->classs();

		if ( $subterm_constant && $this->get_setting( 'subterms_support' ) ) {

			$subterm  = $this->constant( $subterm_constant );
			$none_sub = Helper::getTaxonomyLabel( $subterm, 'show_option_select' );
			$subterms = WordPress\Taxonomy::getPostTerms( $subterm, $post, FALSE );
		}

		foreach ( $terms as $term ) {

			if ( $term->parent )
				$parents[] = $term->parent;

			// avoid if has child in the list
			if ( $forced && in_array( $term->term_id, $parents, TRUE ) )
				continue;

			if ( ! $to_post_id = $this->paired_get_to_post_id( $term, $posttype_constant, $paired_constant ) )
				continue;

			if ( in_array( $to_post_id, $excludes, TRUE ) )
				continue;

			$dropdown = MetaBox::paired_dropdownToPosts( $posttype, $paired, $to_post_id, $prefix, $excludes, $none_main, $display_empty );

			if ( $subterm ) {

				if ( $multiple ) {

					$sub_meta = get_post_meta( $post->ID, sprintf( '_%s_subterm_%s', $posttype, $to_post_id ), TRUE );
					$selected = ( $sub_meta && $subterms && in_array( $sub_meta, $subterms ) ) ? $sub_meta : 0;

				} else {

					$selected = $subterms ? array_pop( $subterms ) : 0;
				}

				$dropdown.= MetaBox::paired_dropdownSubTerms( $subterm, $to_post_id, $this->classs( $subterm ), $selected, $none_sub );

				if ( $multiple )
					$dropdown.= '<hr />';
			}

			$dropdowns[$term->term_id] = $dropdown;
			$displayed[] = $to_post_id;
		}

		// final check if had children in the list
		if ( $forced && count( $parents ) )
			$dropdowns = Core\Arraay::stripByKeys( $dropdowns, Core\Arraay::prepNumeral( $parents ) );

		$excludes = Core\Arraay::prepNumeral( $excludes, $displayed );

		if ( empty( $dropdowns ) )
			$dropdowns[0] = MetaBox::paired_dropdownToPosts( $posttype, $paired, '0', $prefix, $excludes, $none_main, $display_empty );

		else if ( $multiple )
			$dropdowns[0] = MetaBox::paired_dropdownToPosts( $posttype, $paired, '0', $prefix, $excludes, $none_main, $display_empty );

		foreach ( $dropdowns as $dropdown )
			if ( $dropdown )
				echo $dropdown;

		// TODO: support for clear all button via js, like `subterms`

		if ( $subterm )
			$this->enqueue_asset_js( 'subterms', 'module' );
	}

	protected function _metabox_remove_subterm( $screen, $subterms = FALSE )
	{
		if ( $subterms )
			remove_meta_box( $subterms.'div', $screen->post_type, 'side' );
	}

	protected function _hook_term_supportedbox( $screen, $context = NULL, $metabox_context = 'side', $metabox_priority = 'default', $extra = [] )
	{
		if ( is_null( $context ) )
			$context = 'supportedbox';

		$callback = function( $object, $box ) use ( $context, $screen ) {

			if ( $this->check_hidden_metabox( $box, $object->taxonomy ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

			$this->actions(
				sprintf( 'render_%s_metabox_before', $context ),
				$object,
				$box,
				NULL,
				sprintf( '%s_%s', $context, $object->taxonomy )
			);

			$this->_render_supportedbox_content( $object, $box, $context, $screen );

			$this->actions(
				sprintf( 'render_%s_metabox_after', $context ),
				$object,
				$box,
				NULL,
				sprintf( '%s_%s', $context, $object->taxonomy )
			);

			echo '</div>';
		};

		/* translators: %1$s: current post title, %2$s: posttype singular name */
		$default = _x( 'For &ldquo;%1$s&rdquo;', 'Module: Metabox Title: `supportedbox_title`', 'geditorial' );
		$title   = $this->get_string( sprintf( '%s_title', $context ), $screen->taxonomy, 'metabox', $default );
		$name    = Helper::getTaxonomyLabel( $screen->taxonomy, 'singular_name' );
		$metabox = $this->classs( $context );

		add_meta_box(
			$metabox,
			sprintf( $title, WordPress\Term::title( NULL, $name ), $name ),
			$callback,
			$screen,
			$metabox_context,
			$metabox_priority
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ), function( $classes ) use ( $context, $extra ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-'.$this->key,
				'-'.$this->key.'-'.$context,
			], (array) $extra );
		} );
	}

	protected function _hook_general_supportedbox( $screen, $context = NULL, $metabox_context = 'side', $metabox_priority = 'default', $extra = [] )
	{
		if ( is_null( $context ) )
			$context = 'supportedbox';

		$callback = function( $object, $box ) use ( $context, $screen ) {

			if ( $this->check_hidden_metabox( $box, $object->post_type ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

			$this->actions(
				sprintf( 'render_%s_metabox_before', $context ),
				$object,
				$box,
				NULL,
				sprintf( '%s_%s', $context, $object->post_type )
			);

			$this->_render_supportedbox_content( $object, $box, $context, $screen );

			$this->actions(
				sprintf( 'render_%s_metabox_after', $context ),
				$object,
				$box,
				NULL,
				sprintf( '%s_%s', $context, $object->post_type )
			);

			echo '</div>';
		};

		/* translators: %1$s: current post title, %2$s: posttype singular name */
		$default = _x( 'For &ldquo;%1$s&rdquo;', 'Module: Metabox Title: `supportedbox_title`', 'geditorial' );
		$title   = $this->get_string( sprintf( '%s_title', $context ), $screen->post_type, 'metabox', $default );
		$name    = Helper::getPostTypeLabel( $screen->post_type, 'singular_name' );
		$metabox = $this->classs( $context );

		add_meta_box(
			$metabox,
			sprintf( $title, WordPress\Post::title( NULL, $name ), $name ),
			$callback,
			$screen,
			$metabox_context,
			$metabox_priority
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ), function( $classes ) use ( $context, $extra ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-'.$this->key,
				'-'.$this->key.'-'.$context,
			], (array) $extra );
		} );
	}

	// DEFAULT METHOD
	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'supportedbox';

		$this->actions(
			sprintf( 'render_%s_metabox', $context ),
			$object,
			$box,
			NULL,
			sprintf( '%s_%s', $context, $object->post_type )
		);
	}

	protected function _hook_general_mainbox( $screen, $constant_key = 'post', $remove_parent_order = TRUE, $context = NULL, $metabox_context = 'side', $extra = [] )
	{
		if ( is_null( $context ) )
			$context = 'mainbox';

		if ( ! empty( $screen->post_type ) && method_exists( $this, 'store_'.$context.'_metabox_'.$screen->post_type ) )
			add_action( sprintf( 'save_post_%s', $screen->post_type ), [ $this, 'store_'.$context.'_metabox_'.$screen->post_type ], 20, 3 );

		else if ( method_exists( $this, 'store_'.$context.'_metabox' ) )
			add_action( 'save_post', [ $this, 'store_'.$context.'_metabox' ], 20, 3 );

		$this->filter_false_module( 'meta', 'mainbox_callback', 12 );

		if ( $remove_parent_order ) {
			$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
			$this->filter_false_module( 'tweaks', 'metabox_parent' );
			remove_meta_box( 'pageparentdiv', $screen, 'side' );
		}

		$callback = function( $post, $box ) use ( $context ) {

			if ( $this->check_hidden_metabox( $box, $post->post_type ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

				$this->actions(
					sprintf( 'render_%s_metabox', $context ),
					$post,
					$box,
					NULL,
					sprintf( '%s_%s', $context, $post->post_type )
				);

				do_action( 'geditorial_meta_render_metabox', $post, $box, NULL );

				$this->_render_mainbox_extra( $post, $box, $context );

			echo '</div>';

			$this->nonce_field( $context );
		};

		$metabox = $this->classs( $context );

		add_meta_box(
			$metabox,
			$this->get_meta_box_title_posttype( $constant_key ),
			$callback,
			$screen,
			$metabox_context,
			'default'
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ), function( $classes ) use ( $context, $extra ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-'.$this->key,
				'-'.$this->key.'-'.$context,
			], (array) $extra );
		} );
	}

	// TODO: move to `Internals\PairedMetaBox`
	protected function _hook_paired_mainbox( $screen, $remove_parent_order = TRUE, $context = NULL, $metabox_context = 'side', $extra = [] )
	{
		if ( ! $this->_paired || empty( $screen->post_type ) )
			return FALSE;

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		if ( is_null( $context ) )
			$context = 'mainbox';

		if ( method_exists( $this, 'store_'.$context.'_metabox_'.$screen->post_type ) )
			add_action( sprintf( 'save_post_%s', $screen->post_type ), [ $this, 'store_'.$context.'_metabox_'.$screen->post_type ], 20, 3 );

		else if ( method_exists( $this, 'store_'.$context.'_metabox' ) )
			add_action( 'save_post', [ $this, 'store_'.$context.'_metabox' ], 20, 3 );

		$this->filter_false_module( 'meta', 'mainbox_callback', 12 );

		if ( $remove_parent_order ) {
			$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
			$this->filter_false_module( 'tweaks', 'metabox_parent' );
			remove_meta_box( 'pageparentdiv', $screen, 'side' );
		}

		$callback = function( $post, $box ) use ( $constants, $context ) {

			if ( $this->check_hidden_metabox( $box, $post->post_type ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

				$this->actions(
					sprintf( 'render_%s_metabox', $context ),
					$post,
					$box,
					NULL,
					sprintf( '%s_%s', $context, $this->constant( $constants[0] ) )
				);

				do_action( 'geditorial_meta_render_metabox', $post, $box, NULL );

				$this->_render_mainbox_extra( $post, $box, $context );

			echo '</div>';

			$this->nonce_field( $context );
		};

		$metabox = $this->classs( $context );

		add_meta_box(
			$metabox,
			$this->get_meta_box_title( $constants[0], FALSE ),
			$callback,
			$screen,
			$metabox_context,
			'high'
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ), function( $classes ) use ( $context, $extra ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-'.$this->key,
				'-'.$this->key.'-'.$context,
			], (array) $extra );
		} );
	}

	// DEFAULT METHOD
	protected function _render_mainbox_extra( $object, $box, $context = NULL, $screen = NULL )
	{
		MetaBox::fieldPostMenuOrder( $object );
		MetaBox::fieldPostParent( $object );
	}

	// TODO: move to `Internals\PairedMetaBox`
	protected function _hook_paired_listbox( $screen, $context = NULL, $metabox_context = 'advanced', $extra = [] )
	{
		if ( ! $this->_paired || empty( $screen->post_type ) )
			return FALSE;

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		if ( is_null( $context ) )
			$context = 'listbox';

		$post_title    = WordPress\Post::title(); // NOTE: gets post from query-args in admin
		$singular_name = Helper::getPostTypeLabel( $screen->post_type, 'singular_name' );

		/* translators: %1$s: current post title, %2$s: posttype singular name */
		$default = _x( 'No items connected to &ldquo;%1$s&rdquo; %2$s!', 'Module: Metabox Empty: `listbox_empty`', 'geditorial' );
		$empty   = $this->get_string( sprintf( '%s_empty', $context ), $constants[0], 'metabox', $default );
		$noitems = sprintf( $empty, $post_title, $singular_name );

		$callback = function( $object, $box ) use ( $constants, $context, $screen, $noitems ) {

			if ( $this->check_hidden_metabox( $box, $object->post_type ) )
				return;

			if ( $this->check_draft_metabox( $box, $object ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

			$this->actions(
				sprintf( 'render_%s_metabox', $context ),
				$object,
				$box,
				NULL,
				sprintf( '%s_%s', $context, $this->constant( $constants[0] ) )
			);

			$term = $this->paired_get_to_term( $object->ID, $constants[0], $constants[1] );

			if ( $list = MetaBox::getTermPosts( $this->constant( $constants[1] ), $term, $this->posttypes() ) )
				echo $list;

			else
				echo Core\HTML::wrap( $noitems, 'field-wrap -empty' );

			$this->_render_listbox_extra( $object, $box, $context, $screen );

			echo '</div>';

			$this->nonce_field( $context );
		};

		/* translators: %1$s: current post title, %2$s: posttype singular name */
		$default = _x( 'In &ldquo;%1$s&rdquo; %2$s', 'Module: Metabox Title: `listbox_title`', 'geditorial' );
		$title   = $this->get_string( sprintf( '%s_title', $context ), $constants[0], 'metabox', $default );
		$metabox = $this->classs( $context );

		add_meta_box(
			$metabox,
			sprintf( $title, $post_title, $singular_name ),
			$callback,
			$screen,
			$metabox_context,
			'low'
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ), function( $classes ) use ( $context, $extra ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-'.$this->key,
				'-'.$this->key.'-'.$context,
			], (array) $extra );
		} );

		if ( $this->role_can( 'import', NULL, TRUE ) )
			Scripts::enqueueColorBox();
	}

	// DEFAULT METHOD
	// TODO: support for `post_actions` on Actions module
	protected function _render_listbox_extra( $post, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'listbox';

		$html = '';

		if ( $this->_paired && $this->role_can( 'import', NULL, TRUE ) && method_exists( $this, 'pairedimports_get_import_buttons' ) )
			$html.= $this->pairedimports_get_import_buttons( $post, $context );

		if ( $this->role_can( 'export', NULL, TRUE ) && method_exists( $this, 'exports_get_export_buttons' ) )
			$html.= $this->exports_get_export_buttons( $post, $context );

		echo Core\HTML::wrap( $html, 'field-wrap -buttons' );
	}

	// TODO: move to `Internals\PairedMetaBox`
	protected function _hook_paired_pairedbox( $screen, $menuorder = FALSE, $context = NULL, $extra = [] )
	{
		if ( ! $this->_paired )
			return FALSE;

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		if ( empty( $constants[2] ) )
			$constants[2] = FALSE;

		if ( is_null( $context ) )
			$context = 'pairedbox';

		$action   = sprintf( 'render_%s_metabox', $context );
		$callback = function( $post, $box ) use ( $constants, $context, $action, $menuorder ) {

			if ( $this->check_hidden_metabox( $box, $post->post_type ) )
				return;

			$action_context = sprintf( '%s_%s', $context, $this->constant( $constants[0] ) );

			echo $this->wrap_open( '-admin-metabox' );

			if ( $this->get_setting( 'quick_newpost' ) ) {

				$this->actions(
					$action,
					$post,
					$box,
					NULL,
					$action_context
				);

			} else {

				if ( ! WordPress\Taxonomy::hasTerms( $this->constant( $constants[1] ) ) )
					MetaBox::fieldEmptyPostType( $this->constant( $constants[0] ) );

				else
					$this->actions(
						$action,
						$post,
						$box,
						NULL,
						$action_context
					);
			}

			do_action(
				sprintf( '%s_meta_render_metabox', $this->base ),
				$post,
				$box,
				NULL,
				$action_context
			);

			if ( $menuorder )
				MetaBox::fieldPostMenuOrder( $post );

			echo '</div>';

			$this->nonce_field( $context );
		};

		$metabox = $this->classs( $context );

		add_meta_box(
			$metabox,
			$this->get_meta_box_title_posttype( $constants[0] ),
			$callback,
			$screen,
			'side'
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ), function( $classes ) use ( $context, $extra ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-'.$this->key,
				'-'.$this->key.'-'.$context,
			], (array) $extra );
		} );

		add_action( $this->hook( $action ), function( $post, $box, $fields = NULL, $action_context = NULL ) use ( $constants, $context ) {

			if ( $newpost = $this->get_setting( 'quick_newpost' ) )
				$this->do_render_thickbox_newpostbutton( $post, $constants[0], 'newpost', [ 'target' => 'paired' ] );

			$this->paired_do_render_metabox( $post, $constants[0], $constants[1], $constants[2], $newpost );

		}, 10, 4 );

		if ( $this->get_setting( 'quick_newpost' ) )
			Scripts::enqueueThickBox();
	}

	// TODO: move to `Internals\PairedMetaBox`
	// NOTE: logic separeted for the use on edit screen
	protected function _hook_paired_store_metabox( $posttype )
	{
		if ( ! $this->_paired )
			return;

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		if ( empty( $constants[2] ) )
			$constants[2] = FALSE;

		add_action( sprintf( 'save_post_%s', $posttype ), function( $post_id, $post, $update ) use ( $constants ) {

			if ( ! $this->is_save_post( $post, $this->posttypes() ) )
				return;

			$this->paired_do_store_metabox( $post, $constants[0], $constants[1], $constants[2] );

		}, 20, 3 );
	}

	// OLD: `do_store_metabox_assoc()`
	// TODO: move to `Internals\PairedMetaBox`
	protected function paired_do_store_metabox( $post, $posttype_constant, $paired_constant, $subterm_constant = FALSE )
	{
		$posttype = $this->constant( $posttype_constant );
		$paired   = self::req( $this->classs( $posttype ), FALSE );

		if ( FALSE === $paired )
			return;

		$terms = $this->paired_do_store_connection( $post->ID, $paired, $posttype_constant, $paired_constant );

		if ( FALSE === $terms )
			return FALSE;

		if ( ! $subterm_constant || ! $this->get_setting( 'subterms_support' ) )
			return;

		$subterm = $this->constant( $subterm_constant );

		// no post, no subterm
		if ( ! count( $terms ) )
			return wp_set_object_terms( $post->ID, [], $subterm, FALSE );

		$request = self::req( $this->classs( $subterm ), FALSE );

		if ( FALSE === $request || ! is_array( $request ) )
			return;

		$subterms = [];
		$multiple = $this->get_setting( 'multiple_instances', FALSE );

		foreach ( (array) $paired as $paired_id ) {

			if ( ! $paired_id )
				continue;

			if ( ! array_key_exists( $paired_id, $request ) )
				continue;

			$sub_paired = $request[$paired_id];

			if ( $multiple ) {

				$sub_metakey = sprintf( '_%s_subterm_%s', $posttype, $paired_id );

				if ( $sub_paired )
					update_post_meta( $post->ID, $sub_metakey, (int) $sub_paired );
				else
					delete_post_meta( $post->ID, $sub_metakey );
			}

			if ( $sub_paired )
				$subterms[] = (int) $sub_paired;
		}

		wp_set_object_terms( $post->ID, Core\Arraay::prepNumeral( $subterms ), $subterm, FALSE );
	}

	// TODO: move to `Internals\PairedCore`
	protected function paired_do_store_connection( $post_ids, $paired_ids, $posttype_constant, $paired_constant, $append = FALSE, $forced = NULL )
	{
		$forced = $forced ?? $this->get_setting( 'paired_force_parents', FALSE );
		$terms  = $stored = [];

		foreach ( (array) $paired_ids as $paired_id ) {

			if ( ! $paired_id )
				continue;

			if ( ! $term = $this->paired_get_to_term( $paired_id, $posttype_constant, $paired_constant ) )
				continue;

			$terms[] = $term->term_id;

			if ( $forced )
				$terms = array_merge( WordPress\Taxonomy::getTermParents( $term->term_id, $term->taxonomy ), $terms );
		}

		$supported = $this->posttypes();
		$taxonomy  = $this->constant( $paired_constant );
		$terms     = Core\Arraay::prepNumeral( $terms );

		foreach ( (array) $post_ids as $post_id ) {

			if ( ! $post_id )
				continue;

			if ( ! $post = WordPress\Post::get( $post_id ) ) {
				$stored[$post_id] = FALSE;
				continue;
			}

			if ( ! in_array( $post->post_type, $supported, TRUE ) ) {
				$stored[$post_id] = FALSE;
				continue;
			}

			$result = wp_set_object_terms( $post->ID, $terms, $taxonomy, $append );
			$stored[$post->ID] = self::isError( $result ) ? FALSE : $result;
		}

		return is_array( $post_ids ) ? $stored : reset( $stored );
	}

	protected function _hook_store_metabox( $posttype )
	{
		if ( $posttype )
			add_action( sprintf( 'save_post_%s', $posttype ), [ $this, 'store_metabox' ], 20, 3 );
	}

	protected function class_metabox( $screen, $context = 'mainbox' )
	{
		add_filter( 'postbox_classes_'.$screen->id.'_'.$this->classs( $context ), function( $classes ) use ( $context ) {
			return array_merge( $classes, [ $this->base.'-wrap', '-admin-postbox', '-'.$this->key, '-'.$this->key.'-'.$context ] );
		} );
	}

	// TODO: filter the results
	public function get_meta_box_title( $constant = 'post', $url = NULL, $edit_cap = NULL, $title = NULL )
	{
		if ( is_null( $title ) )
			$title = $this->get_string( 'metabox_title', $constant, 'metabox', NULL );

		// DEPRECATED: for back-comp only
		if ( is_null( $title ) )
			$title = $this->get_string( 'meta_box_title', $constant, 'misc', _x( 'Settings', 'Module: MetaBox Default Title', 'geditorial' ) );

		return $title; // <-- // FIXME: problems with block editor

		// TODO: 'metabox_icon'
		if ( $info = $this->get_string( 'metabox_info', $constant, 'metabox', NULL ) )
			$title.= ' <span class="postbox-title-info" style="display:none" data-title="info" title="'.Core\HTML::escape( $info ).'">'.Core\HTML::getDashicon( 'editor-help' ).'</span>';

		if ( FALSE === $url || FALSE === $edit_cap )
			return $title;

		if ( is_null( $edit_cap ) )
			$edit_cap = isset( $this->caps['settings'] ) ? $this->caps['settings'] : 'manage_options';

		if ( TRUE === $edit_cap || current_user_can( $edit_cap ) ) {

			if ( is_null( $url ) )
				$url = $this->get_module_url( 'settings' );

			$action = $this->get_string( 'metabox_action', $constant, 'metabox', _x( 'Configure', 'Module: MetaBox Default Action', 'geditorial' ) );
			$title.= ' <span class="postbox-title-action"><a href="'.esc_url( $url ).'" target="_blank">'.$action.'</a></span>';
		}

		return $title;
	}

	public function get_meta_box_title_taxonomy( $constant, $posttype, $url = NULL, $title = NULL )
	{
		$object = WordPress\Taxonomy::object( $this->constant( $constant ) );

		if ( is_null( $title ) )
			$title = $this->get_string( 'metabox_title', $constant, 'metabox', NULL );

		if ( is_null( $title ) && ! empty( $object->labels->metabox_title ) )
			$title = $object->labels->metabox_title;

		if ( is_null( $title ) && ! empty( $object->labels->name ) )
			$title = $object->labels->name;

		return $title; // <-- // FIXME: problems with block editor

		// TODO: 'metabox_icon'
		if ( $info = $this->get_string( 'metabox_info', $constant, 'metabox', NULL ) )
			$title.= ' <span class="postbox-title-info" style="display:none" data-title="info" title="'.Core\HTML::escape( $info ).'">'.Core\HTML::getDashicon( 'info' ).'</span>';

		if ( is_null( $url ) )
			$url = Core\WordPress::getEditTaxLink( $object->name, FALSE, [ 'post_type' => $posttype ] );

		if ( $url ) {
			$action = $this->get_string( 'metabox_action', $constant, 'metabox', _x( 'Manage', 'Module: MetaBox Default Action', 'geditorial' ) );
			$title.= ' <span class="postbox-title-action"><a href="'.esc_url( $url ).'" target="_blank">'.$action.'</a></span>';
		}

		return $title;
	}

	public function get_meta_box_title_posttype( $constant, $url = NULL, $title = NULL )
	{
		$object = WordPress\PostType::object( $this->constant( $constant ) );

		if ( is_null( $title ) )
			$title = $this->get_string( 'metabox_title', $constant, 'metabox', NULL );

		if ( is_null( $title ) && ! empty( $object->labels->metabox_title ) )
			$title = $object->labels->metabox_title;

		// DEPRECATED: for back-comp only
		if ( is_null( $title ) )
			$title = $this->get_string( 'meta_box_title', $constant, 'misc', $object->labels->name );

		// FIXME: problems with block editor(on panel settings)
		return $title; // <--

		if ( $info = $this->get_string( 'metabox_info', $constant, 'metabox', NULL ) )
			$title.= ' <span class="postbox-title-info" style="display:none" data-title="info" title="'.Core\HTML::escape( $info ).'">'.Core\HTML::getDashicon( 'info' ).'</span>';

		if ( current_user_can( $object->cap->edit_others_posts ) ) {

			if ( is_null( $url ) )
				$url = Core\WordPress::getPostTypeEditLink( $object->name );

			$action = $this->get_string( 'metabox_action', $constant, 'metabox', _x( 'Manage', 'Module: MetaBox Default Action', 'geditorial' ) );
			$title.= ' <span class="postbox-title-action"><a href="'.esc_url( $url ).'" target="_blank">'.$action.'</a></span>';
		}

		return $title;
	}

	public function get_column_title( $column, $constant = NULL, $fallback = NULL )
	{
		$title = $this->get_string( $column.'_column_title', $constant, 'misc', ( is_null( $fallback ) ? $column : $fallback ) );
		return $this->filters( 'column_title', $title, $column, $constant, $fallback );
	}

	public function get_column_title_posttype( $constant, $taxonomy = FALSE, $fallback = NULL )
	{
		$title = Helper::getPostTypeLabel( $this->constant( $constant ), 'column_title', 'name', $fallback );
		return $this->filters( 'column_title', $title, $taxonomy, $constant, $fallback );
	}

	public function get_column_title_taxonomy( $constant, $posttype = FALSE, $fallback = NULL )
	{
		$title = Helper::getTaxonomyLabel( $this->constant( $constant ), 'column_title', 'name', $fallback );
		return $this->filters( 'column_title', $title, $posttype, $constant, $fallback );
	}

	public function get_column_title_icon( $column, $constant = NULL, $fallback = NULL )
	{
		$title = $this->get_column_title( $column, $constant, $fallback );
		return sprintf( '<span class="-column-icon %3$s" title="%2$s">%1$s</span>', $title, esc_attr( $title ), $this->classs( $column ) );
	}

	protected function require_code( $filenames = 'Templates', $once = TRUE )
	{
		foreach ( (array) $filenames as $filename )
			if ( $once )
				require_once $this->path.$filename.'.php';
			else
				require $this->path.$filename.'.php';
	}

	public function is_save_post( $post, $constant = FALSE )
	{
		if ( $constant ) {

			if ( is_array( $constant ) && ! in_array( $post->post_type, $constant, TRUE ) )
				return FALSE;

			if ( ! is_array( $constant ) && $post->post_type != $this->constant( $constant ) )
				return FALSE;
		}

		if ( wp_is_post_autosave( $post ) || wp_is_post_revision( $post ) )
			return FALSE;

		return TRUE;
	}

	// for ajax calls on quick edit
	// OLD: `is_inline_save()`
	public function is_inline_save_posttype( $target = FALSE, $request = NULL, $key = 'post_type' )
	{
		if ( ! Core\WordPress::isAdminAJAX() )
			return FALSE;

		if ( is_null( $request ) )
			$request = $_REQUEST;

		if ( empty( $request['action'] )
			|| 'inline-save' != $request['action'] )
				return FALSE;

		if ( empty( $request[$key] ) )
			return FALSE;

		if ( is_array( $target )
			&& ! in_array( $request[$key], $target, TRUE ) )
				return FALSE;

		if ( $target
			&& ! is_array( $target )
			&& $request[$key] != $this->constant( $target ) )
				return FALSE;

		return $request[$key];
	}

	// for ajax calls on quick edit
	public function is_inline_save_taxonomy( $target = FALSE, $request = NULL, $key = 'taxonomy' )
	{
		if ( ! Core\WordPress::isAdminAJAX() )
			return FALSE;

		if ( is_null( $request ) )
			$request = $_REQUEST;

		if ( empty( $request['action'] )
			|| ! in_array( $request['action'], [ 'add-tag', 'inline-save-tax' ], TRUE ) )
				return FALSE;

		if ( empty( $request[$key] ) )
			return FALSE;

		if ( is_array( $target )
			&& ! in_array( $request[$key], $target, TRUE ) )
				return FALSE;

		if ( $target
			&& ! is_array( $target )
			&& $request[$key] != $this->constant( $target ) )
				return FALSE;

		return $request[$key];
	}


	// OLD: `get_linked_term()`
	// TODO: move to `Internals\PairedCore`
	public function paired_get_to_term( $post_id, $posttype_constant_key, $tax_constant_key )
	{
		return $this->paired_get_to_term_direct( $post_id,
			$this->constant( $posttype_constant_key ),
			$this->constant( $tax_constant_key )
		);
	}

	// NOTE: here so modules can override
	// TODO: move to `Internals\PairedCore`
	public function paired_get_to_term_direct( $post_id, $posttype, $taxonomy )
	{
		return Services\Paired::getToTerm( $post_id, $posttype, $taxonomy );
	}

	// OLD: `set_linked_term()`
	// TODO: move to `Internals\PairedCore`
	public function paired_set_to_term( $post_id, $term_or_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $post_id )
			return FALSE;

		if ( ! $the_term = WordPress\Term::get( $term_or_id, $this->constant( $taxonomy_key ) ) )
			return FALSE;

		update_post_meta( $post_id, '_'.$this->constant( $posttype_key ).'_term_id', $the_term->term_id );
		update_term_meta( $the_term->term_id, $this->constant( $posttype_key ).'_linked', $post_id );

		wp_set_object_terms( (int) $post_id, $the_term->term_id, $the_term->taxonomy, FALSE );

		if ( $this->get_setting( 'thumbnail_support' ) ) {

			$meta_key = $this->constant( 'metakey_term_image', 'image' );

			if ( $thumbnail = get_post_thumbnail_id( $post_id ) )
				update_term_meta( $the_term->term_id, $meta_key, $thumbnail );

			else
				delete_term_meta( $the_term->term_id, $meta_key );
		}

		return TRUE;
	}

	// OLD: `remove_linked_term()`
	// TODO: move to `Internals\PairedCore`
	public function paired_remove_to_term( $post_id, $term_or_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $the_term = WordPress\Term::get( $term_or_id, $this->constant( $taxonomy_key ) ) )
			return FALSE;

		if ( ! $post_id )
			$post_id = $this->paired_get_to_post_id( $the_term, $posttype_key, $taxonomy_key );

		if ( $post_id ) {
			delete_post_meta( $post_id, '_'.$this->constant( $posttype_key ).'_term_id' );
			wp_set_object_terms( (int) $post_id, [], $the_term->taxonomy, FALSE );
		}

		delete_term_meta( $the_term->term_id, $this->constant( $posttype_key ).'_linked' );

		if ( $this->get_setting( 'thumbnail_support' ) ) {

			$meta_key  = $this->constant( 'metakey_term_image', 'image' );
			$stored    = get_term_meta( $the_term->term_id, $meta_key, TRUE );
			$thumbnail = get_post_thumbnail_id( $post_id );

			if ( $stored && $thumbnail && $thumbnail == $stored )
				delete_term_meta( $the_term->term_id, $meta_key );
		}

		return TRUE;
	}

	// OLD: `get_linked_post_id()`
	// TODO: move to `Internals\PairedCore`
	public function paired_get_to_post_id( $term_or_id, $posttype_constant_key, $tax_constant_key, $check_slug = TRUE )
	{
		if ( ! $term_or_id )
			return FALSE;

		if ( ! $term = WordPress\Term::get( $term_or_id, $this->constant( $tax_constant_key ) ) )
			return FALSE;

		$post_id = get_term_meta( $term->term_id, $this->constant( $posttype_constant_key ).'_linked', TRUE );

		if ( ! $post_id && $check_slug )
			$post_id = WordPress\PostType::getIDbySlug( $term->slug, $this->constant( $posttype_constant_key ) );

		return $post_id;
	}

	// PAIRED API: get (from) posts connected to the pair
	// TODO: move to `Internals\PairedCore`
	public function paired_get_from_posts( $post_id, $posttype_constant_key, $tax_constant_key, $count = FALSE, $term_id = NULL )
	{
		if ( is_null( $term_id ) )
			$term_id = get_post_meta( $post_id, '_'.$this->constant( $posttype_constant_key ).'_term_id', TRUE );

		$args = [
			'tax_query' => [ [
				'taxonomy' => $this->constant( $tax_constant_key ),
				'field'    => 'id',
				'terms'    => [ $term_id ]
			] ],
			'post_type'   => $this->posttypes(),
			'numberposts' => -1,
		];

		if ( $count ) {
			$args['fields'] = 'ids';
			$args['update_post_meta_cache'] = FALSE;
			$args['update_post_term_cache'] = FALSE;
			$args['lazy_load_term_meta'] = FALSE;
		}

		$items = get_posts( $args );

		if ( $count )
			return count( $items );

		return $items;
	}

	// DEFAULT METHOD
	public function get_linked_to_posts( $post = NULL, $single = FALSE, $published = TRUE )
	{
		if ( $this->_paired ) {

			$constants = $this->paired_get_paired_constants();

			if ( empty( $constants[0] ) || empty( $constants[1] ) )
				return FALSE;

			return $this->paired_do_get_to_posts( $constants[0], $constants[1], $post, $single, $published );
		}

		return FALSE;
	}

	// TODO: move to `Internals\PairedCore`
	public function paired_do_get_to_posts( $posttype_constant_key, $tax_constant_key, $post = NULL, $single = FALSE, $published = TRUE )
	{
		$posts  = $parents = [];
		$terms  = WordPress\Taxonomy::getPostTerms( $this->constant( $tax_constant_key ), $post );
		$forced = $this->get_setting( 'paired_force_parents', FALSE );

		foreach ( $terms as $term ) {

			if ( $term->parent )
				$parents[] = $term->parent;

			// avoid if has child in the list
			if ( $forced && in_array( $term->term_id, $parents, TRUE ) )
				continue;

			if ( ! $to_post_id = $this->paired_get_to_post_id( $term, $posttype_constant_key, $tax_constant_key ) )
				continue;

			if ( $single )
				return $to_post_id;

			if ( is_null( $published ) )
				$posts[$term->term_id] = $to_post_id;

			else if ( $published && ! $this->is_post_viewable( $to_post_id ) )
				continue;

			else
				$posts[$term->term_id] = $to_post_id;
		}

		// final check if had children in the list
		if ( $forced && count( $parents ) )
			$posts = Core\Arraay::stripByKeys( $posts, Core\Arraay::prepNumeral( $parents ) );

		if ( ! count( $posts ) )
			return FALSE;

		return $single ? reset( $posts ) : $posts;
	}

	// PAIRED API
	// TODO: check capability
	protected function _hook_paired_tweaks_column_attr()
	{
		if ( ! $this->_paired )
			return;

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		add_action( sprintf( '%s_tweaks_column_attr', $this->base ), function( $post ) use ( $constants ) {

			$posts = $this->paired_get_from_posts( $post->ID, $constants[0], $constants[1] );
			$count = count( $posts );

			if ( ! $count )
				return;

			$title = $this->get_posttype_label( $constants[0], 'column_title', $this->constant( $constants[0] ) );

			echo '<li class="-row -'.$this->key.' -connected">';

				echo $this->get_column_icon( FALSE, NULL, $title );

				$posttypes = array_unique( Core\Arraay::pluck( $posts, 'post_type' ) );
				$args      = [ $this->constant( $constants[1] ) => $post->post_name ];

				if ( empty( $this->cache['posttypes'] ) )
					$this->cache['posttypes'] = WordPress\PostType::get( 2 );

				echo '<span class="-counted">'.$this->nooped_count( 'connected', $count ).'</span>';

				$list = [];

				foreach ( $posttypes as $posttype )
					$list[] = Core\HTML::tag( 'a', [
						'href'   => Core\WordPress::getPostTypeEditLink( $posttype, 0, $args ),
						'title'  => _x( 'View the connected list', 'Module: Paired: Title Attr', 'geditorial' ),
						'target' => '_blank',
					], $this->cache['posttypes'][$posttype] );

				echo WordPress\Strings::getJoined( $list, ' <span class="-posttypes">(', ')</span>' );

			echo '</li>';
		} );
	}

	// TODO: move to `Internals\PairedCore`
	protected function paired_get_paired_constants()
	{
		return [
			FALSE, // posttype: `primary_posttype`
			FALSE, // taxonomy: `primary_paired`
			FALSE, // subterm:  `primary_subterm`
			FALSE, // exclude:  `primary_taxonomy`
		];
	}

	// NOTE: cannot use 'wp_insert_post_data' filter
	protected function _hook_autofill_posttitle( $posttype )
	{
		add_action( 'save_post_'.$posttype, [ $this, '_save_autofill_posttitle' ], 20, 3 );
	}

	public function _save_autofill_posttitle( $post_id, $post, $update )
	{
		remove_action( 'save_post_'.$post->post_type, [ $this, '_save_autofill_posttitle' ], 20, 3 );

		if ( FALSE === ( $posttitle = $this->get_autofill_posttitle( $post ) ) )
			return;

		if ( is_array( $posttitle ) )
			$data = array_merge( $posttitle, [ 'ID' => $post->ID ] );

		else
			$data = [
				'ID'         => $post->ID,
				'post_title' => $posttitle,
				'post_name'  => Core\Text::formatSlug( $posttitle ),
			];

		$updated = wp_update_post( $data );

		if ( ! $updated || self::isError( $updated ) )
			$this->log( 'FAILED', sprintf( 'updating title of post #%s', $post->ID ) );
	}

	// DEFAULT CALLBACK
	protected function get_autofill_posttitle( $post )
	{
		return FALSE;
	}

	// @REF: https://make.wordpress.org/core/2012/12/01/more-hooks-on-the-edit-screen/
	protected function _hook_editform_readonly_title()
	{
		add_action( 'edit_form_after_title', function( $post ) {
			$html = WordPress\Post::title( $post );
			$info = Settings::fieldAfterIcon( '#', _x( 'This Title is Auto-Generated', 'Module: ReadOnly Title Info', 'geditorial' ) );
			echo $this->wrap(
				$html.' '.$info,
				'-readonly-title',
				TRUE,
				sprintf( '%s-readonlytitle', $this->base )
			);
		}, 1, 1 );
	}

	protected function _hook_editform_meta_summary( $fields = NULL )
	{
		add_action( 'edit_form_after_title', function( $post ) use ( $fields ) {
			echo $this->wrap( Template::metaSummary( [
				'echo'   => FALSE,
				'id'     => $post->ID,
				'type'   => $post->post_type,
				'fields' => $fields,
			] ), '-meta-summary' );
		}, 1, 9 );
	}

	public function get_column_icon( $link = FALSE, $icon = NULL, $title = NULL, $posttype = 'post', $extra = [] )
	{
		if ( is_null( $icon ) )
			$icon = $this->module->icon;

		if ( is_null( $title ) )
			$title = $this->get_string( 'column_icon_title', $posttype, 'misc', '' );

		return Core\HTML::tag( ( $link ? 'a' : 'span' ), [
			'href'   => $link ?: FALSE,
			'title'  => $title ?: FALSE,
			'class'  => array_merge( [ '-icon', ( $link ? '-link' : '-info' ) ], (array) $extra ),
			'target' => $link ? '_blank' : FALSE,
		], Helper::getIcon( $icon ) );
	}

	// NOTE: adds the `{$module_key}-enabled` class to body in admin
	public function _admin_enabled( $extra = [] )
	{
		add_action( 'admin_body_class', function( $classes ) use ( $extra ) {
			return trim( $classes ).' '.Core\HTML::prepClass( $this->classs( 'enabled' ), $extra );
		} );
	}

	// should we insert content?
	public function is_content_insert( $posttypes = '', $first_page = TRUE, $embed = FALSE )
	{
		if ( ! $embed && is_embed() )
			return FALSE;

		if ( ! is_main_query() )
			return FALSE;

		if ( ! in_the_loop() )
			return FALSE;

		if ( $first_page && 1 != $GLOBALS['page'] )
			return FALSE;

		if ( FALSE === $posttypes )
			return TRUE;

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		else if ( $posttypes && ! is_array( $posttypes ) )
			$posttypes = $this->constant( $posttypes );

		if ( ! is_singular( $posttypes ) )
			return FALSE;

		return TRUE;
	}

	protected function hook_insert_content( $default_priority = 50 )
	{
		$insert = $this->get_setting( 'insert_content', 'none' );

		if ( 'none' == $insert )
			return FALSE;

		add_action( $this->base.'_content_'.$insert,
			[ $this, 'insert_content' ],
			$this->get_setting( 'insert_priority', $default_priority )
		);

		return TRUE;
	}

	// DEFAULT FILTER
	public function get_default_comment_status( $status, $posttype, $comment_type )
	{
		return $this->get_setting( 'comment_status', $status );
	}

	// DEFAULT FILTER
	// increases last menu_order for new posts
	// USAGE: `$this->filter( 'wp_insert_post_data', 2, 9, 'menu_order' );`
	public function wp_insert_post_data_menu_order( $data, $postarr )
	{
		if ( ! $data['menu_order'] && $postarr['post_type'] )
			$data['menu_order'] = WordPress\PostType::getLastMenuOrder( $postarr['post_type'],
				( isset( $postarr['ID'] ) ? $postarr['ID'] : '' ) ) + 1;

		return $data;
	}

	// DEFAULT FILTER
	// USAGE: `$this->action_self( 'newpost_content', 4, 99, 'menu_order' );`
	public function newpost_content_menu_order( $posttype, $post, $target, $linked )
	{
		Core\HTML::inputHidden( 'menu_order', WordPress\PostType::getLastMenuOrder( $posttype, $post->ID ) + 1 );
	}

	protected function _hook_ajax( $auth = TRUE, $hook = NULL, $method = 'do_ajax' )
	{
		if ( is_null( $hook ) )
			$hook = $this->hook();

		if ( is_null( $auth ) || TRUE === $auth )
			add_action( 'wp_ajax_'.$hook, [ $this, $method ] );

		if ( is_null( $auth ) || FALSE === $auth )
			add_action( 'wp_ajax_nopriv_'.$hook, [ $this, $method ] );
	}

	protected function _hook_post( $auth = TRUE, $hook = NULL, $method = 'post' )
	{
		if ( ! is_admin() )
			return;

		if ( is_null( $hook ) )
			$hook = $this->hook();

		if ( is_null( $auth ) || TRUE === $auth )
			add_action( 'admin_post_'.$hook, [ $this, $method ] );

		if ( is_null( $auth ) || FALSE === $auth )
			add_action( 'admin_post_nopriv_'.$hook, [ $this, $method ] );
	}

	// DEFAULT FILTER
	public function post()
	{
		wp_die();
	}

	// MAYBE: move to `Visual` Main
	public function get_posttype_field_icon( $field, $posttype = 'post', $args = [] )
	{
		switch ( $field ) {
			case 'over_title': return 'arrow-up-alt2';
			case 'sub_title' : return 'arrow-down-alt2';
			case 'highlight' : return 'pressthis';
			case 'byline'    : return 'admin-users';
			case 'published' : return 'calendar-alt';
			case 'lead'      : return 'editor-paragraph';
			case 'label'     : return 'megaphone';
		}

		if ( ! empty( $args['type'] ) ) {
			switch ( $args['type'] ) {
				case 'email'   : return 'email';
				case 'phone'   : return 'phone';
				case 'mobile'  : return 'smartphone';
				case 'identity': return 'id-alt';
				case 'iban'    : return 'bank';
				case 'isbn'    : return 'book';
			}
		}

		return 'admin-post';
	}

	// DEFAULT FILTER
	// NOTE: used when module defines `_supported` meta fields
	public function meta_column_row( $post, $fields, $excludes )
	{
		foreach ( $fields as $field_key => $field ) {

			if ( in_array( $field_key, $excludes ) )
				continue;

			if ( ! $value = $this->get_postmeta_field( $post->ID, $field_key ) )
				continue;

			echo '<li class="-row -'.$this->module->name.' -field-'.$field_key.'">';
				echo $this->get_column_icon( FALSE, $field['icon'], $field['title'] );
				echo $this->prep_meta_row( $value, $field_key, $field, $value );
			echo '</li>';
		}
	}

	// DEFAULT METHOD
	public function prep_meta_row( $value, $field_key = NULL, $field = [], $raw = NULL )
	{
		if ( ! empty( $field['prep'] ) && is_callable( $field['prep'] ) )
			return call_user_func_array( $field['prep'], [ $value, $field_key, $field, $raw ] );

		if ( method_exists( $this, 'prep_meta_row_module' ) ) {

			$prepped = $this->prep_meta_row_module( $value, $field_key, $field, $raw );

			if ( $prepped !== $value )
				return $prepped; // bail if already prepped
		}

		return Helper::prepMetaRow( $value, $field_key, $field, $raw );
	}

	public function icon( $name, $group = NULL )
	{
		return gEditorial()->icon( $name, ( is_null( $group ) ? $this->icon_group : $group ) );
	}

	// checks to bail early if metabox/widget is hidden
	protected function check_hidden_metabox( $box, $posttype = FALSE, $after = '' )
	{
		return MetaBox::checkHidden( ( empty( $box['id'] ) ? $this->classs( $box ) : $box['id'] ), $posttype, $after );
	}

	// TODO: move to `MetaBox` main
	protected function check_draft_metabox( $box, $post, $message = NULL )
	{
		if ( ! in_array( $post->post_status, [ 'trash', 'private', 'auto-draft' ], TRUE ) )
			return FALSE;

		if ( is_null( $message ) )
			$message = _x( 'You can see the contents once you\'ve saved this post for the first time.', 'Module: Draft Metabox', 'geditorial' );

		Core\HTML::desc( $message, TRUE, 'field-wrap -empty' );

		return TRUE;
	}

	// TODO: customize column position/sorting
	// FIXME: WTF?!
	protected function _hook_terms_meta_field( $constant, $field, $args = [] )
	{
		if ( ! gEditorial()->enabled( 'terms' ) )
			return FALSE;

		$taxonomy = $this->constant( $constant );
		$title    = $this->get_string( 'field_title', $field, 'terms_meta_field', $field );
		$desc     = $this->get_string( 'field_desc', $field, 'terms_meta_field', '' );

		add_filter( $this->base.'_terms_supported_fields', static function( $list, $tax ) use ( $taxonomy, $field ) {

			if ( FALSE === $tax || $tax === $taxonomy )
				$list[] = $field;

			return $list;
		}, 12, 2 );

		add_filter( $this->base.'_terms_list_supported_fields', static function( $list, $tax ) use ( $taxonomy, $field, $title ) {

			if ( FALSE === $tax || $tax === $taxonomy )
				$list[$field] = $title;

			return $list;
		}, 12, 2 );

		add_filter( $this->base.'_terms_supported_field_taxonomies', static function( $taxonomies, $_field ) use ( $taxonomy, $field ) {

			if ( $_field === $field )
				$taxonomies[] = $taxonomy;

			return $taxonomies;
		}, 12, 2 );

		if ( ! is_admin() )
			return;

		$this->filter_string( $this->base.'_terms_field_'.$field.'_title', $title );
		$this->filter_string( $this->base.'_terms_field_'.$field.'_desc', $desc );

		add_filter( $this->base.'_terms_column_title', static function( $_title, $column, $constant, $fallback ) use ( $taxonomy, $field, $title ) {

			if ( $column === $field )
				return $title;

			return $_title;
		}, 12, 4 );
	}

	protected function log( $level, $message = '', $context = [] )
	{
		do_action( 'gnetwork_logger_site_'.strtolower( $level ), $this->classs(), $message, $context );
		return FALSE; // to help the caller!
	}

	// self::dump( ini_get( 'memory_limit' ) );
	protected function raise_resources( $count = 1, $per = 60, $context = NULL )
	{
		$limit = $count ? ( 300 + ( $per * $count ) ) : 0;

		@set_time_limit( $limit );
		// @ini_set( 'max_execution_time', $limit ); // maybe `-1`
		// @ini_set( 'max_input_time', $limit ); // maybe `-1`

		if ( is_null( $context ) )
			$context = $this->base;

		return wp_raise_memory_limit( $context );
	}

	public function disable_process( $context = 'import' )
	{
		return $this->process_disabled[$context] = TRUE;
	}

	public function enable_process( $context = 'import' )
	{
		return $this->process_disabled[$context] = FALSE;
	}
}
