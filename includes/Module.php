<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\File;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Icon;
use geminorum\gEditorial\Core\L10n;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\Validation;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Database;
use geminorum\gEditorial\WordPress\Module as Base;
use geminorum\gEditorial\WordPress\Media;
use geminorum\gEditorial\WordPress\Post;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Strings;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\Theme;
use geminorum\gEditorial\WordPress\User;
use geminorum\gEditorial\Services\O2O;
use geminorum\gEditorial\Services\Paired;

class Module extends Base
{

	public $module;
	public $options;
	public $settings;

	public $enabled  = FALSE;
	public $meta_key = '_ge';

	protected $cookie     = 'geditorial';
	protected $icon_group = 'genericons-neue';

	protected $rest_api_version = 'v1';

	protected $priority_init              = 10;
	protected $priority_init_ajax         = 10;
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
		$this->path = File::normalize( $root.$module->folder.'/' );
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

		if ( 'restonly' === $this->module->i18n && ! WordPress::isREST() )
			return FALSE;

		if ( 'frontonly' === $this->module->i18n && is_admin() )
			return FALSE;

		if ( is_null( $domain ) )
			$domain = $this->get_textdomain();

		if ( ! $domain )
			return FALSE;

		if ( is_null( $locale ) )
			$locale = apply_filters( 'plugin_locale', L10n::locale(), $this->base );

		return load_textdomain( $domain, GEDITORIAL_DIR."languages/{$this->module->folder}/{$locale}.mo" );
	}

	protected function setup_remote( $args = [] )
	{
		$this->require_code( $this->partials_remote );
	}

	protected function setup( $args = [] )
	{
		$admin = is_admin();
		$ajax  = WordPress::isAJAX();
		$ui    = WordPress::mustRegisterUI( FALSE );

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

		if ( method_exists( $this, 'after_setup_theme' ) )
			$this->action( 'after_setup_theme', 0, 20 );

		$this->action( 'init', 0, $this->priority_init );

		if ( method_exists( $this, 'setup_restapi' ) )
			add_action( 'rest_api_init', [ $this, 'setup_restapi' ], $this->priority_restapi_init, 0 );

		if ( $ui && method_exists( $this, 'adminbar_init' ) && $this->get_setting( 'adminbar_summary' ) )
			add_action( $this->base.'_adminbar', [ $this, 'adminbar_init' ], $this->priority_adminbar_init, 2 );

		if ( $admin ) {

			add_action( 'admin_init', [ $this, '_admin_init' ], 1 );

			if ( method_exists( $this, 'admin_init' ) )
				$this->action( 'admin_init' );

			if ( $ui && method_exists( $this, 'admin_menu' ) )
				$this->action( 'admin_menu', 0, $this->priority_admin_menu );

			if ( $ajax && method_exists( $this, 'init_ajax' ) )
				$this->action( 'init', 0, $this->priority_init_ajax, 'ajax' );

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
				Arraay::mapAssoc( $callback, $templates )
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
			default          : $url = URL::current();
		}

		if ( FALSE === $url )
			return FALSE;

		return add_query_arg( array_merge( [
			'sub' => $sub,
		], $extra ), $url );
	}

	// FIXME: DEPRECATED: use `$this->get_adminpage_url( FALSE )`
	// OVERRIDE: if has no admin menu but using the hook
	public function get_adminmenu( $page = TRUE, $extra = [] )
	{
		self::_dep( '$this->get_adminpage_url( FALSE )' );

		if ( $page )
			return $this->classs();

		$url = get_admin_url( NULL, 'index.php' );

		return add_query_arg( array_merge( [ 'page' => $this->classs() ], $extra ), $url );
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

		return $this->filters( $context.'_default_sub', Arraay::keyFirst( $subs ) );
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
		HTML::desc( gEditorial()->na(), TRUE, '-empty' );
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
			return HTML::desc( gEditorial\Plugin::denied( FALSE ), TRUE, '-denied' );

		$meta = $this->filters( 'newpost_content_meta', $meta, $posttype, $target, $linked, $status );

		echo $this->wrap_open( '-newpost-layout' );
		echo '<div class="-main">';

		$this->actions( 'newpost_content_before_title', $posttype, $post, $target, $linked, $status, $meta );

		$field = $this->classs( $posttype, 'title' );
		$label = $this->get_string( 'post_title', $posttype, 'newpost', __( 'Add title' ) );

		$html = HTML::tag( 'input', [
			'type'        => 'text',
			'class'       => 'large-text',
			'id'          => $field,
			'name'        => 'title',
			'placeholder' => apply_filters( 'enter_title_here', $label, $post ),
		] );

		HTML::label( $html, $field );

		$this->actions( 'newpost_content_after_title', $posttype, $post, $target, $linked, $status, $meta );

		$field = $this->classs( $posttype, 'excerpt' );
		$label = $this->get_string( 'post_excerpt', $posttype, 'newpost', __( 'Excerpt' ) );

		$html = HTML::tag( 'textarea', [
			'id'           => $field,
			'name'         => 'excerpt',
			'placeholder'  => $label,
			'class'        => [ 'mceEditor', 'large-text' ],
			'rows'         => 2,
			'cols'         => 15,
			'autocomplete' => 'off',
		], '' );

		HTML::label( $html, $field );

		$field = $this->classs( $posttype, 'content' );
		$label = $this->get_string( 'post_content', $posttype, 'newpost', __( 'What&#8217;s on your mind?' ) );

		$html = HTML::tag( 'textarea', [
			'id'           => $field,
			'name'         => 'content',
			'placeholder'  => $label,
			'class'        => [ 'mceEditor', 'large-text' ],
			'rows'         => 6,
			'cols'         => 15,
			'autocomplete' => 'off',
		], '' );

		HTML::label( $html, $field );

		if ( $object->hierarchical )
			MetaBox::fieldPostParent( $post, FALSE, 'parent' );

		$this->actions( 'newpost_content', $posttype, $post, $target, $linked, $status, $meta );

		HTML::inputHidden( 'type', $posttype );
		HTML::inputHidden( 'status', $status === 'publish' ? 'publish' : 'draft' ); // only publish/draft
		HTML::inputHiddenArray( $meta, 'meta' );

		echo $this->wrap_open_buttons();

		echo '<span class="-message"></span>';
		echo Ajax::spinner();

		echo HTML::tag( 'a', [
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
			HTML::desc( sprintf( _x( 'Or select one from Recent %s.', 'Module: Recents', 'geditorial' ), $object->labels->name ) );

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

	public function render_printpage_adminpage()
	{
		$this->render_content_printpage();
	}

	public function render_content_printpage()
	{
		$head_callback = [ $this, 'render_print_head' ];
		$head_title    = $this->get_print_layout_pagetitle();
		$body_class    = $this->get_print_layout_bodyclass();
		$rtl           = is_rtl();

		if ( $header = Helper::getLayout( 'print.header' ) )
			require_once $header; // to expose scope vars

		$this->actions( 'print_contents' );

		if ( $footer = Helper::getLayout( 'print.footer' ) )
			require_once $footer; // to expose scope vars

		exit; // avoiding query monitor output
	}

	protected function render_print_head()
	{
		$this->actions( 'print_head' );
	}

	protected function get_print_layout_pagetitle()
	{
		return $this->filters( 'print_layout_pagetitle',
			_x( 'Print Me!', 'Module', 'geditorial' ) );
	}

	protected function get_print_layout_bodyclass( $extra = [] )
	{
		return $this->filters( 'print_layout_bodyclass',
			HTML::prepClass( 'printpage', ( is_rtl() ? 'rtl' : 'ltr' ), $extra ) );
	}

	protected function get_printpage_url( $extra = [], $context = 'printpage' )
	{
		$extra['noheader'] = 1;
		return $this->get_adminpage_url( TRUE, $extra, $context );
	}

	// @SEE: https://stackoverflow.com/questions/819416/adjust-width-and-height-of-iframe-to-fit-with-content-in-it
	protected function render_print_iframe( $printpage = NULL )
	{
		if ( is_null( $printpage ) )
			$printpage = $this->get_printpage_url( [ 'single' => '1' ] );

		// prefix to avoid conflicts
		$func = $this->hook( 'resizeIframe' );
		$html = HTML::tag( 'iframe', [
			'src'    => $printpage,
			'width'  => '100%',
			'height' => '0',
			'border' => '0',
			'onload' => $func.'(this)',
		], _x( 'Print Preview', 'Module', 'geditorial' ) );

		echo HTML::wrap( $html, 'field-wrap -iframe -print-iframe' );

		// @REF: https://stackoverflow.com/a/9976309
		echo '<script>function '.$func.'(obj){obj.style.height=obj.contentWindow.document.documentElement.scrollHeight+"px";}</script>';
	}

	protected function render_print_button( $printpage = NULL, $button_class = '' )
	{
		if ( is_null( $printpage ) )
			$printpage = $this->get_printpage_url( [ 'single' => '1' ] );

		// prefix to avoid conflicts
		$func = $this->hook( 'printIframe' );
		$id   = $this->classs( 'printiframe' );

		echo HTML::tag( 'iframe', [
			'id'     => $id,
			'src'    => $printpage,
			'class'  => '-hidden-print-iframe',
			'width'  => '0',
			'height' => '0',
			'border' => '0',
		], '' );

		echo HTML::tag( 'a', [
			'href'    => '#',
			'class'   => [ 'button', $button_class ], //  button-small',
			'onclick' => $func.'("'.$id.'")',
		], _x( 'Print', 'Module', 'geditorial' ) );

		// @REF: https://hdtuto.com/article/print-iframe-content-using-jquery-example
		echo '<script>function '.$func.'(id){var frm=document.getElementById(id).contentWindow;frm.focus();frm.print();return false;}</script>';
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

		$html = HTML::tag( 'a', [
			'href'  => $link,
			'id'    => $this->classs( 'mainbutton', $context ),
			'class' => [ 'button', '-button', '-button-full', '-button-icon', '-mainbutton', 'thickbox' ],
			'title' => $title ? sprintf( $title, Post::title( $post, $name ), $name ) : FALSE,
		], sprintf( $text, Helper::getIcon( $this->module->icon ), $name ) );

		echo HTML::wrap( $html, 'field-wrap -buttons' );
	}

	// LEGACY: do not use thickbox anymore!
	// NOTE: must `add_thickbox()` on load
	public function do_render_thickbox_newpostbutton( $post, $constant, $context = 'newpost', $extra = [], $inline = FALSE, $width = '600' )
	{
		$posttype = $this->constant( $constant );
		$object   = PostType::object( $posttype );

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

		$html = HTML::tag( 'a', [
			'href'  => $link,
			'id'    => $this->classs( 'newpostbutton', $context ),
			'class' => [ 'button', '-button', '-button-full', '-button-icon', '-newpostbutton', 'thickbox' ],
			'title' => $title ? sprintf( $title, Post::title( $post, $name ), $name ) : FALSE,
		], sprintf( $text, Helper::getIcon( $this->module->icon ), $name ) );

		echo HTML::wrap( $html, 'field-wrap -buttons hide-if-no-js' );
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

	public function is_posttype( $posttype_key, $post = NULL )
	{
		if ( ! $post = Post::get( $post ) )
			return FALSE;

		return $this->constant( $posttype_key ) == $post->post_type;
	}

	public function is_post_viewable( $post = NULL )
	{
		return $this->filters( 'is_post_viewable', Post::viewable( $post ), Post::get( $post ) );
	}

	public function list_posttypes( $pre = NULL, $posttypes = NULL, $capability = NULL, $args = [ 'show_ui' => TRUE ], $user_id = NULL )
	{
		if ( is_null( $pre ) )
			$pre = [];

		else if ( TRUE === $pre )
			$pre = [ 'all' => _x( 'All PostTypes', 'Module', 'geditorial' ) ];

		$all = PostType::get( 0, $args, $capability, $user_id );

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
		return Arraay::stripByKeys( PostType::get( 0, $args ), Arraay::prepString( $this->posttypes_excluded( $exclude_extra ) ) );
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

		$all = Taxonomy::get( 0, $args, FALSE, $capability, $user_id );

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
		return Arraay::stripByKeys( Taxonomy::get( 0, $args ), Arraay::prepString( $this->taxonomies_excluded( $exclude_extra ) ) );
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
			HTML::tabNav( $sub, $subs );
		} else {
			echo $this->wrap_open( $context, $sub );
			HTML::headerNav( $uri, $sub, $subs );
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
			HTML::desc( $before );

		echo '<div class="wp-tab-panel"><ul>';

		foreach ( $this->all_posttypes() as $posttype => $label ) {

			$html = HTML::tag( 'input', [
				'type'    => 'checkbox',
				'value'   => 'enabled',
				'id'      => 'type-'.$posttype,
				'name'    => $this->base.'_'.$this->module->name.'[post_types]['.$posttype.']',
				'checked' => ! empty( $this->options->post_types[$posttype] ),
			] );

			$html.= '&nbsp;'.HTML::escape( $label );
			$html.= ' &mdash; <code>'.$posttype.'</code>';

			HTML::label( $html, 'type-'.$posttype, 'li' );
		}

		echo '</ul></div>';

		if ( $after = $this->get_string( 'post_types_after', 'post', 'settings', NULL ) )
			HTML::desc( $after );
	}

	public function settings_taxonomies_option()
	{
		if ( $before = $this->get_string( 'taxonomies_before', 'post', 'settings', NULL ) )
			HTML::desc( $before );

		echo '<div class="wp-tab-panel"><ul>';

		foreach ( $this->all_taxonomies() as $taxonomy => $label ) {

			$html = HTML::tag( 'input', [
				'type'    => 'checkbox',
				'value'   => 'enabled',
				'id'      => 'tax-'.$taxonomy,
				'name'    => $this->base.'_'.$this->module->name.'[taxonomies]['.$taxonomy.']',
				'checked' => ! empty( $this->options->taxonomies[$taxonomy] ),
			] );

			$html.= '&nbsp;'.HTML::escape( $label );
			$html.= ' &mdash; <code>'.$taxonomy.'</code>';

			HTML::label( $html, 'tax-'.$taxonomy, 'li' );
		}

		echo '</ul></div>';

		if ( $after = $this->get_string( 'taxonomies_after', 'post', 'settings', NULL ) )
			HTML::desc( $after );
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

	// FIXME: DEPRECATED
	// get stored post meta by the field
	public function get_postmeta( $post_id, $field = FALSE, $default = '', $metakey = NULL )
	{
		self::_dep( '$this->get_postmeta_legacy() || $this->get_postmeta_field()' );

		global $gEditorialPostMeta;

		if ( is_null( $metakey ) )
			$metakey = $this->meta_key;

		if ( ! isset( $gEditorialPostMeta[$post_id][$metakey] ) )
			$gEditorialPostMeta[$post_id][$metakey] = get_metadata( 'post', $post_id, $metakey, TRUE );

		if ( empty( $gEditorialPostMeta[$post_id][$metakey] ) )
			return $default;

		if ( FALSE === $field )
			return $gEditorialPostMeta[$post_id][$metakey];

		foreach ( $this->sanitize_postmeta_field_key( $field ) as $field_key )
			if ( isset( $gEditorialPostMeta[$post_id][$metakey][$field_key] ) )
				return $gEditorialPostMeta[$post_id][$metakey][$field_key];

		return $default;
	}

	public function get_postid_by_field( $value, $field, $prefix = NULL )
	{
		if ( is_null( $prefix ) )
			$prefix = 'meta'; // the exception!

		if ( $post_id = PostType::getIDbyMeta( $this->get_postmeta_key( $field, $prefix ), $value ) )
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

	// FIXME: DEPRECATED
	public function set_meta( $post_id, $postmeta, $key_suffix = '' )
	{
		self::_dep( '$this->store_postmeta()' );

		global $gEditorialPostMeta;

		if ( ! empty( $postmeta ) )
			update_post_meta( $post_id, $this->meta_key.$key_suffix, $postmeta );
		else
			delete_post_meta( $post_id, $this->meta_key.$key_suffix );

		unset( $gEditorialPostMeta[$post_id][$this->meta_key.$key_suffix] );
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

		$html = HTML::tag( 'input', [
			'type'    => 'checkbox',
			'value'   => 'enabled',
			'class'   => 'fields-check',
			'name'    => $name,
			'id'      => $id,
			'checked' => $value,
		] );

		echo '<div>';
			HTML::label( $html.'&nbsp;'.$args['field_title'], $id, FALSE );
			HTML::desc( $args['description'] );
		echo '</div>';
	}

	public function settings_fields_option_all( $args )
	{
		$html = HTML::tag( 'input', [
			'type'  => 'checkbox',
			'class' => 'fields-check-all',
			'id'    => $args['post_type'].'_fields_all',
		] );

		$html.= '&nbsp;<span class="description">'._x( 'Select All Fields', 'Module', 'geditorial' ).'</span>';

		HTML::label( $html, $args['post_type'].'_fields_all', FALSE );
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

			echo '<input id="geditorial_module_name" name="geditorial_module_name" type="hidden" value="'.HTML::escape( $this->module->name ).'" />';

			$this->render_form_buttons();

		echo '</form>';

		if ( WordPress::isDev() )
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

		echo '<form enctype="multipart/form-data" class="'.HTML::prepClass( $class ).'" method="post" action="">';

			if ( in_array( $context, [ 'settings', 'tools', 'reports', 'imports' ] ) )
				$this->render_form_fields( $sub, $action, $context );

			if ( $check && $sidebox ) {
				echo '<div class="'.HTML::prepClass( '-sidebox', '-'.$this->module->name, '-sidebox-'.$sub ).'">';
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

			HTML::inputHidden( $this->base.'_'.$this->module->name.'['.$context.']['.$key.']', $value );
		}
	}

	protected function render_form_fields( $sub, $action = 'update', $context = 'settings' )
	{
		HTML::inputHidden( 'base', $this->base );
		HTML::inputHidden( 'key', $this->key );
		HTML::inputHidden( 'context', $context );
		HTML::inputHidden( 'sub', $sub );
		HTML::inputHidden( 'action', $action );

		WordPress::fieldReferer();
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
						$first_key = Arraay::keyFirst( $option );

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
										$group[$key] = Number::intval( trim( $option[$key][$index] ) );
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
		return PostType::supports( $posttype, ( is_null( $module ) ? $this->module->name : $module ).'_fields' );
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

			if ( ! array_key_exists( 'context', $args )
				&& array_key_exists( 'type', $args ) ) {

				if ( in_array( $args['type'], [ 'postbox_legacy', 'title_before', 'title_after' ] ) )
					$args['context'] = 'nobox'; // OLD: 'raw'

				else if ( in_array( $args['type'], [ 'postbox_html', 'postbox_tiny' ] ) )
					$args['context'] = 'lonebox'; // OLD: 'lone'
			}

			if ( ! array_key_exists( 'default', $args )
				&& array_key_exists( 'type', $args ) ) {

				if ( in_array( $args['type'], [ 'array' ] ) || ! empty( $args['repeat'] ) )
					$args['default'] = [];

				else if ( in_array( $args['type'], [ 'integer', 'number', 'float', 'price' ] ) )
					$args['default'] = 0;

				else
					$args['default'] = '';
			}

			if ( ! array_key_exists( 'quickedit', $args )
				&& array_key_exists( 'type', $args ) )
					$args['quickedit'] = in_array( $args['type'], [ 'title_before', 'title_after' ] );

			// TODO: migrate!
			// $args = PostTypeFields::getFieldDefaults( $field, $args );

			if ( ! isset( $args['icon'] ) )
				$args['icon'] = $this->get_posttype_field_icon( $field, $posttype, $args );

			$fields[$field] = self::atts( [
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
				'icon'        => 'smiley',
				'type'        => 'text',
				'context'     => 'mainbox', // OLD: 'main'
				'quickedit'   => FALSE,
				'values'      => [],
				'repeat'      => FALSE,
				'ltr'         => FALSE,
				'taxonomy'    => FALSE,
				'posttype'    => NULL,
				'role'        => FALSE,
				'group'       => 'general',
				'order'       => 1000 + $i,
			], $args );
		}

		$gEditorialPostTypeFields[$this->key][$posttype] = Arraay::multiSort( $fields, [
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

			} else if ( $post = Post::get( $post ) ) {

				$access = in_array( $context, [ 'edit' ], TRUE )
					? user_can( $user_id, 'edit_post', $post->ID )
					: Post::viewable( $post );

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

				// TODO: use `Taxonomy::getTerm( $data, $field['taxonomy'] )`
				$sanitized = empty( $data ) ? FALSE : (int) $data;

			break;

			case 'embed':
			case 'text_source':
			case 'audio_source':
			case 'video_source':
			case 'image_source':
			case 'link':
				$sanitized = trim( $data );

 				// @SEE: `esc_url()`
				if ( $sanitized && ! preg_match( '/^http(s)?:\/\//', $sanitized ) )
					$sanitized = 'http://'.$sanitized;

			break;
			case 'code':
				$sanitized = trim( $data );

			break;
			case 'email':
				$sanitized = sanitize_email( trim( $data ) );

			break;
			case 'contact':
				$sanitized = Number::intval( trim( $data ), FALSE );
				break;

			case 'identity':
				$sanitized = Validation::sanitizeIdentityNumber( $data );
				break;

			case 'isbn':
				$sanitized = Core\ISBN::sanitize( $data, TRUE );
				break;

			case 'iban':
				$sanitized = Validation::sanitizeIBAN( $data );
				break;

			case 'phone':
				$sanitized = Core\Phone::sanitize( $data );
				break;

			case 'mobile':
			 	$sanitized = Core\Mobile::sanitize( $data );
				break;

			case 'date':
				$sanitized = Number::intval( trim( $data ), FALSE );
				$sanitized = Datetime::makeMySQLFromInput( $sanitized, 'Y-m-d', $this->default_calendar(), NULL, $sanitized );
				break;

			case 'time':
				$sanitized = Number::intval( trim( $data ), FALSE );
				break;

			case 'datetime':

				// @SEE: https://html.spec.whatwg.org/multipage/common-microsyntaxes.html#dates

				$sanitized = Number::intval( trim( $data ), FALSE );
				$sanitized = Datetime::makeMySQLFromInput( $sanitized, NULL, $this->default_calendar(), NULL, $sanitized );
				break;

			case 'price':
			case 'number':
				$sanitized = Number::intval( trim( $data ) );

			break;
			case 'float':
				$sanitized = Number::floatval( trim( $data ) );

			break;
			case 'text':
			case 'datestring':
			case 'title_before':
			case 'title_after':
				$sanitized = trim( Helper::kses( $data, 'none' ) );

			break;
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
			$fields = array_merge( PostType::supports( $posttype, $type.'_fields' ), $fields );

		add_post_type_support( $posttype, [ $type.'_fields' ], $fields );
		add_post_type_support( $posttype, 'custom-fields' ); // must for rest meta fields
	}

	public function has_posttype_fields_support( $constant, $type = 'meta' )
	{
		return post_type_supports( $this->constant( $constant ), $type.'_fields' );
	}

	public function get_strings( $subgroup, $group = 'titles', $fallback = [] )
	{
		if ( $subgroup && isset( $this->strings[$group][$subgroup] ) )
			return $this->strings[$group][$subgroup];

		if ( isset( $this->strings[$group] ) )
			return $this->strings[$group];

		return $fallback;
	}

	public function get_string( $string, $subgroup = 'post', $group = 'titles', $fallback = FALSE )
	{
		if ( $subgroup && isset( $this->strings[$group][$subgroup][$string] ) )
			return $this->strings[$group][$subgroup][$string];

		if ( isset( $this->strings[$group]['post'][$string] ) )
			return $this->strings[$group]['post'][$string];

		if ( isset( $this->strings[$group][$string] ) )
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
		return sprintf( Helper::noopedCount( $count, $this->get_noop( $constant ) ), Number::format( $count ) );
	}

	public function constant( $key, $default = FALSE )
	{
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
		if ( ! $taxonomy = Taxonomy::object( $this->constant( $constant ) ) )
			return;

		/* translators: %s: taxonomy object label */
		$title  = sprintf( _x( 'Default Terms for %s', 'Module', 'geditorial' ), $taxonomy->label );
		/* translators: %s: taxonomy object label */
		$edit   = sprintf( _x( 'Edit Terms for %s', 'Module', 'geditorial' ), $taxonomy->label );
		$terms  = $this->get_default_terms( $constant );
		$link   = WordPress::getEditTaxLink( $taxonomy->name );
		$before = HTML::tag( 'p', $title );
		$after  = HTML::tag( 'p', HTML::link( $edit, $link, TRUE ) );
		$args   = [ 'title' => $taxonomy->label, 'id' => $this->classs( 'help-default-terms', '-'.$taxonomy->name ) ];

		if ( ! empty( $terms ) )
			$args['content'] = $before.HTML::wrap( HTML::tableCode( $terms, TRUE ), '-info' ).$after;

		else
			$args['content'] = $before.HTML::wrap( _x( 'No Default Terms', 'Module', 'geditorial' ), '-info' ).$after;

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

		else if ( $added = Taxonomy::insertDefaultTerms( $taxonomy, $terms ) )
			$message = [
				'message' => 'created',
				'count'   => count( $added ),
			];

		else
			$message = 'wrong';

		WordPress::redirectReferer( $message );
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
			static function() use ( $terms ) { return $terms; } );
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
		if ( ! WordPress::mustRegisterUI( FALSE ) )
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
			$flush   = WordPress::maybeFlushRules();
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
				echo HTML::warning( _x( 'You need to flush rewrite rules!', 'Module', 'geditorial' ), FALSE );

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

	public function settings_section_printpage()
	{
		Settings::fieldSection(
			_x( 'Printing', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_p2p()
	{
		Settings::fieldSection(
			_x( 'Posts-to-Posts', 'Module: Setting Section Title', 'geditorial' )
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
		if ( ! $post = Post::get( $post ) )
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
			HTML::wrapjQueryReady( implode( "\n", $this->scripts ) );

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

	// for out of context manipulations
	public function update_option( $key, $value )
	{
		return gEditorial()->update_module_option( $this->module->name, $key, $value );
	}

	public function set_cookie( $data, $append = TRUE, $expire = '+ 365 day' )
	{
		if ( $append ) {

			$old = isset( $_COOKIE[$this->cookie] ) ? json_decode( self::unslash( $_COOKIE[$this->cookie] ) ) : [];
			$new = wp_json_encode( self::recursiveParseArgs( $data, $old ) );

		} else {

			$new = wp_json_encode( $data );
		}

		return setcookie( $this->cookie, $new, strtotime( $expire ), COOKIEPATH, COOKIE_DOMAIN, FALSE );
	}

	public function get_cookie()
	{
		return isset( $_COOKIE[$this->cookie] ) ? json_decode( self::unslash( $_COOKIE[$this->cookie] ), TRUE ) : [];
	}

	public function delete_cookie()
	{
		setcookie( $this->cookie, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, FALSE );
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
			$icon = Icon::getBase64( $icon[1], $icon[0] );

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
		$plural   = str_replace( '_', '-', L10n::pluralize( $posttype ) );

		$args = self::recursiveParseArgs( $atts, [
			'description' => isset( $this->strings['labels'][$constant]['description'] ) ? $this->strings['labels'][$constant]['description'] : '',

			'show_in_menu'  => NULL, // or TRUE or `$parent_slug`
			'menu_icon'     => $this->get_posttype_icon( $constant ),
			'menu_position' => empty( $this->positions[$constant] ) ? 4 : $this->positions[$constant],

			// 'show_in_nav_menus' => TRUE,
			// 'show_in_admin_bar' => TRUE,

			'query_var'   => $this->constant( $constant.'_query_var', $posttype ),
			'has_archive' => $this->constant( $constant.'_archive', $plural ),

			'rewrite' => [
				'slug'       => $this->constant( $constant.'_slug', str_replace( '_', '-', $posttype ) ),
				'ep_mask'    => $this->constant( $constant.'_endpoint', EP_PERMALINK | EP_PAGES ), // https://make.wordpress.org/plugins?p=29
				'with_front' => FALSE,
				'feeds'      => TRUE,
				'pages'      => TRUE,
			],

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
			PostType::PRIMARY_TAXONOMY_PROP => NULL,   // @SEE: `PostType::getPrimaryTaxonomy()`
			Paired::PAIRED_TAXONOMY_PROP    => FALSE,  // @SEE: `Paired::isPostType()`

			/// Misc Props
			// @SEE: https://github.com/torounit/custom-post-type-permalinks
			'cptp_permalink_structure' => $this->constant( $constant.'_permalink', FALSE ), // will lock the permalink

			// only `%post_id%` and `%postname%`
			// @SEE: https://github.com/torounit/simple-post-type-permalinks
			'sptp_permalink_structure' => $this->constant( $constant.'_permalink', FALSE ), // will lock the permalink
		] );

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

	public function get_taxonomy_label( $constant, $label = 'name', $fallback = '' )
	{
		return Helper::getTaxonomyLabel( $this->constant( $constant, $constant ), $label, NULL, $fallback );
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

	protected function _get_taxonomy_caps( $taxonomy, $caps, $posttypes )
	{
		if ( is_array( $caps ) )
			return $caps;

		// custom capabilities
		if ( TRUE === $caps )
			return [
				'manage_terms' => 'manage_'.$taxonomy,
				'edit_terms'   => 'edit_'.$taxonomy,
				'delete_terms' => 'delete_'.$taxonomy,
				'assign_terms' => 'assign_'.$taxonomy,
			];

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
		$plural   = str_replace( '_', '-', L10n::pluralize( $taxonomy ) );

		if ( is_string( $posttypes ) && in_array( $posttypes, [ 'user', 'comment', 'taxonomy' ] ) )
			$cpt_tax = FALSE;

		else if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		else if ( ! is_array( $posttypes ) )
			$posttypes = [ $this->constant( $posttypes ) ];

		$args = self::recursiveParseArgs( $atts, [
			'labels'               => $this->get_taxonomy_labels( $constant ),
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
			'rewrite'              => [

				// NOTE: we can use `example.com/cpt/tax` if cpt registered after the tax
				// @REF: https://developer.wordpress.org/reference/functions/register_taxonomy/#comment-2274

				// NOTE: taxonomy prefix slugs are singular: `/category/`, `/tag/`
				'slug'         => $this->constant( $constant.'_slug', str_replace( '_', '-', $taxonomy ) ),
				'with_front'   => FALSE,
				// 'hierarchical' => FALSE, // will set by `hierarchical` in args
				// 'ep_mask'      => EP_NONE,
			],

			// 'sort' => NULL, // Whether terms in this taxonomy should be sorted in the order they are provided to `wp_set_object_terms()`.
			// 'args' => [], //  Array of arguments to automatically use inside `wp_get_object_terms()` for this taxonomy.

			'show_in_rest' => TRUE,
			'rest_base'    => $this->constant( $constant.'_rest', $this->constant( $constant.'_archive', $plural ) ),
			// 'rest_namespace' => 'wp/v2', // @SEE: https://core.trac.wordpress.org/ticket/54536

			/// gEditorial Props
			Taxonomy::TARGET_TAXONOMIES_PROP => FALSE,  // or array of taxonomies
			Paired::PAIRED_POSTTYPE_PROP     => FALSE,  // @SEE: `Paired::isTaxonomy()`
		] );

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

		if ( is_array( $args['rewrite'] ) && ! array_key_exists( 'hierarchical', $args['rewrite'] ) )
			$args['rewrite']['hierarchical'] = $args['hierarchical'];

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

		$object = register_taxonomy( $taxonomy, $posttypes, $args );

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
		$terms = Taxonomy::listTerms( $box['args']['taxonomy'], 'all', [ 'order' => 'DESC' ] );

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

		if ( $this->role_can( 'restricted', NULL, FALSE, FALSE ) )
			$args['role'] = $this->get_setting( 'restricted', 'disabled' );

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
	protected function paired_register_objects( $posttype, $paired, $subterm = FALSE, $primary = FALSE, $extra = [], $supported = NULL )
	{
		if ( is_null( $supported ) )
			$supported = $this->posttypes();

		if ( count( $supported ) ) {

			// adding the main posttype
			$supported[] = $this->constant( $posttype );

			if ( $subterm && $this->get_setting( 'subterms_support' ) )
				$this->register_taxonomy( $subterm, [
					'hierarchical'       => TRUE,
					'meta_box_cb'        => NULL,
					'show_admin_column'  => FALSE,
					'show_in_nav_menus'  => TRUE,
				], $supported );

			$this->register_taxonomy( $paired, [
				Paired::PAIRED_POSTTYPE_PROP => $this->constant( $posttype ),
				'show_ui'                    => FALSE,
				'show_in_rest'               => FALSE,
				'hierarchical'               => TRUE,
				// the paired taxonomies are often in plural
				// FIXME: WTF: conflict on the posttype rest base!
				// 'rest_base'    => $this->constant( $paired.'_slug', str_replace( '_', '-', $this->constant( $paired ) ) ),
			], $supported );

			$this->_paired = $this->constant( $paired );
			$this->filter_unset( 'wp_sitemaps_taxonomies', $this->_paired );
		}

		if ( $primary && ! array_key_exists( 'primary_taxonomy', $extra ) )
			$extra['primary_taxonomy'] = $this->constant( $primary );

		return $this->register_posttype( $posttype, array_merge( [
			Paired::PAIRED_TAXONOMY_PROP => $this->_paired,
			'hierarchical'               => TRUE,
			'show_in_nav_menus'          => TRUE,
			'show_in_admin_bar'          => FALSE,
			'rewrite'                    => [
				'feeds' => (bool) $this->get_setting( 'posttype_feeds', FALSE ),
				'pages' => (bool) $this->get_setting( 'posttype_pages', FALSE ),
			],
		], $extra ) );
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

				foreach ( Media::defaultImageSizes() as $size => $args )
					$this->image_sizes[$posttype][$posttype.'-'.$size] = $args;
			}
		}

		return $this->image_sizes[$posttype];
	}

	// FIXME: DEPRECATED
	public function get_image_size_key( $constant, $size = 'thumbnail' )
	{
		$posttype = $this->constant( $constant );

		if ( isset( $this->image_sizes[$posttype][$posttype.'-'.$size] ) )
			return $posttype.'-'.$size;

		if ( isset( $this->image_sizes[$posttype]['post-'.$size] ) )
			return 'post-'.$size;

		return $size;
	}

	// use this on 'after_setup_theme'
	public function register_posttype_thumbnail( $constant )
	{
		if ( ! $this->get_setting( 'thumbnail_support', FALSE ) )
			return;

		$posttype = $this->constant( $constant );

		Media::themeThumbnails( [ $posttype ] );

		foreach ( $this->get_image_sizes( $posttype ) as $name => $size )
			Media::registerImageSize( $name, array_merge( $size, [ 'p' => [ $posttype ] ] ) );
	}

	protected function _hook_admin_bulkactions( $screen, $cap_check = NULL )
	{
		if ( ! $this->get_setting( 'admin_bulkactions' ) )
			return;

		if ( FALSE === $cap_check )
			return;

		if ( TRUE !== $cap_check && ! PostType::can( $screen->post_type, is_null( $cap_check ) ? 'edit_posts' : $cap_check ) )
			return;

		add_filter( 'bulk_actions-'.$screen->id, [ $this, 'bulk_actions' ] );
		add_filter( 'handle_bulk_actions-'.$screen->id, [ $this, 'handle_bulk_actions' ], 10, 3 );

		$this->action( 'admin_notices' );
	}

	// PAIRED API
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

	// PAIRED API
	public function paired_bulk_input_add_new_item( $taxonomy, $action )
	{
		/* translators: %s: clone into input */
		printf( _x( 'as: %s', 'Module: Taxonomy Bulk Input Label', 'geditorial' ),
			'<input name="'.$this->classs( 'paired-add-new-item-target' ).'" type="text" placeholder="'
			._x( 'New Item Title', 'Module: Taxonomy Bulk Input PlaceHolder', 'geditorial' ).'" /> ' );

		echo HTML::dropdown( [
			'separeted-terms' => _x( 'Separeted Terms', 'Module: Taxonomy Bulk Input Option', 'geditorial' ),
			'cross-terms'     => _x( 'Cross Terms', 'Module: Taxonomy Bulk Input Option', 'geditorial' ),
			'union-terms'     => _x( 'Union Terms', 'Module: Taxonomy Bulk Input Option', 'geditorial' ),
		], [
			'name'     => $this->classs( 'paired-add-new-item-type' ),
			'style'    => 'float:none',
			'selected' => 'separeted-terms',
		] );
	}

	// PAIRED API
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
				if ( PostType::getIDbySlug( sanitize_title( $target ), $paired_posttype ) )
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

				$object_ids = Arraay::prepNumeral( array_intersect( ...$object_lists ) );

				// bail if cross term results are ampty
				if ( empty( $object_ids ) )
					return FALSE;

				$inserted = wp_insert_term( $target, $paired_taxonomy, [
					'slug' => sanitize_title( $target ),
				] );

				if ( self::isError( $inserted ) )
					return FALSE;

				$cloned  = get_term( $inserted['term_id'], $paired_taxonomy );
				$post_id = PostType::newPostFromTerm( $cloned, $paired_taxonomy, $paired_posttype );

				if ( self::isError( $post_id ) )
					return FALSE;

				if ( ! $this->paired_set_to_term( $post_id, $cloned, $constants[0], $constants[1] ) )
					return FALSE;

				Taxonomy::disableTermCounting();

				foreach ( $object_ids as $object_id )
					wp_set_object_terms( $object_id, $cloned->term_id, $paired_taxonomy, FALSE ); // overrides!

				break;

			case 'union-terms':

				// bail if no target given
				if ( empty( $target ) )
					return FALSE;

				// bail if post with same slug exists
				if ( PostType::getIDbySlug( sanitize_title( $target ), $paired_posttype ) )
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

				$object_ids = Arraay::prepNumeral( ...$object_lists );

				// bail if cross term results are ampty
				if ( empty( $object_ids ) )
					return FALSE;

				$inserted = wp_insert_term( $target, $paired_taxonomy, [
					'slug' => sanitize_title( $target ),
				] );

				if ( self::isError( $inserted ) )
					return FALSE;

				$cloned  = get_term( $inserted['term_id'], $paired_taxonomy );
				$post_id = PostType::newPostFromTerm( $cloned, $paired_taxonomy, $paired_posttype );

				if ( self::isError( $post_id ) )
					return FALSE;

				if ( ! $this->paired_set_to_term( $post_id, $cloned, $constants[0], $constants[1] ) )
					return FALSE;

				Taxonomy::disableTermCounting();

				foreach ( $object_ids as $object_id )
					wp_set_object_terms( $object_id, $cloned->term_id, $paired_taxonomy, FALSE ); // overrides!

				break;
			default:
			case 'separeted-terms':

				Taxonomy::disableTermCounting();

				foreach ( $term_ids as $term_id ) {

					$term = get_term( $term_id, $taxonomy );

					// bail if post with same slug exists
					if ( PostType::getIDbySlug( $term->slug, $paired_posttype ) )
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
					$post_id = PostType::newPostFromTerm( $cloned, $paired_taxonomy, $paired_posttype );

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

	// PAIRED API
	// excludes paired posttype from subterm archives
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

			$primaries = PostType::getIDs( $this->constant( $constants[0] ), [
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

	// PAIRED API
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

	// PAIRED API
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
		if ( WordPress::isWPcompatible( '5.9.0' ) )
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

	// PAIRED API
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

	// WARNING: every script must have a .min copy
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

		return $title.' – '.Post::title( $linked );
	}

	public function get_calendars( $default = [ 'gregorian' ], $filtered = TRUE )
	{
		$settings = $this->get_setting( 'calendar_list', $default );
		$defaults = Datetime::getDefualtCalendars( $filtered );
		return array_intersect_key( $defaults, array_flip( $settings ) );
	}

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
			$form.= '<input type="hidden" name="'.HTML::escape( $name ).'" value="'.HTML::escape( $value ).'" />';

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
		$posttypes = PostType::get( 0, [ 'show_ui' => TRUE ], $capability );

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
		$supported = User::getAllRoleList( $filtered );
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

		if ( $admins && User::isSuperAdmin( $user_id ) )
			return TRUE;

		foreach ( (array) $whats as $what ) {

			$setting = $this->get_setting( $what.$prefix, [] );

			if ( TRUE === $setting )
				return $setting;

			if ( FALSE === $setting || ( empty( $setting ) && ! $admins ) )
				continue; // check others

			if ( $admins )
				$setting = array_merge( $setting, [ 'administrator' ] );

			if ( User::hasRole( $setting, $user_id ) )
				return TRUE;
		}

		return $fallback;
	}

	// DEFAULT METHOD
	public function dISABLED_render_metabox( $post, $box, $fields = NULL, $context = NULL )
	{
		if ( is_null( $fields ) )
			$fields = $this->get_posttype_fields( $post->post_type );

		foreach ( $fields as $field => $args ) {

			if ( $context != $args['context'] )
				continue;

			echo '<div class="-wrap field-wrap -setting-field" title="'.HTML::escape( $args['description'] ).'">';

			$atts = [
				'field'       => $this->constant( 'metakey_'.$field, $field ),
				'type'        => $args['type'],
				'title'       => $args['title'],
				'placeholder' => $args['title'],
				'values'      => $args['values'],
			];

			if ( 'checkbox' == $atts['type'] )
				$atts['description'] = $atts['title'];

			$this->do_posttype_field( $atts, $post );

			echo '</div>';
		}
	}

	/**
	 * returns post ids with selected terms from settings
	 * that will be excluded form dropdown on supported post-types
	 *
	 * @api PAIRED API
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

	// PAIRED API
	// NOTE: subterms must be hierarchical
	// OLD: `do_render_metabox_assoc()`
	protected function paired_do_render_metabox( $post, $posttype_constant, $paired_constant, $subterm_constant = FALSE, $display_empty = FALSE )
	{
		$subterm   = FALSE;
		$dropdowns = $displayed = $parents = [];
		$excludes  = $this->paired_get_dropdown_excludes();
		$multiple  = $this->get_setting( 'multiple_instances', FALSE );
		$forced    = $this->get_setting( 'paired_force_parents', FALSE );
		$posttype  = $this->constant( $posttype_constant );
		$paired    = $this->constant( $paired_constant );
		$terms     = Taxonomy::getPostTerms( $paired, $post );
		$none_main = Helper::getPostTypeLabel( $posttype, 'show_option_select' );
		$prefix    = $this->classs();

		if ( $subterm_constant && $this->get_setting( 'subterms_support' ) ) {

			$subterm  = $this->constant( $subterm_constant );
			$none_sub = Helper::getTaxonomyLabel( $subterm, 'show_option_select' );
			$subterms = Taxonomy::getPostTerms( $subterm, $post, FALSE );
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
			$dropdowns = Arraay::stripByKeys( $dropdowns, Arraay::prepNumeral( $parents ) );

		$excludes = Arraay::prepNumeral( $excludes, $displayed );

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

	protected function _hook_general_mainbox( $screen, $constant_key = 'post', $remove_parent_order = TRUE, $context = 'mainbox', $metabox_context = 'side' )
	{
		$this->filter_false_module( 'meta', 'mainbox_callback', 12 );

		if ( $remove_parent_order ) {
			$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
			$this->filter_false_module( 'tweaks', 'metabox_parent' );
			remove_meta_box( 'pageparentdiv', $screen, 'side' );
		}

		$this->class_metabox( $screen, $context );

		$action   = sprintf( 'render_%s_metabox', $context );
		$callback = function( $post, $box ) use ( $context, $action ) {

			if ( $this->check_hidden_metabox( $box, $post->post_type ) )
				return;

			$action_context = sprintf( '%s_%s', $context, $post->post_type );

			echo $this->wrap_open( '-admin-metabox' );

				$this->actions( $action, $post, $box, NULL, $action_context );

				do_action( 'geditorial_meta_render_metabox', $post, $box, NULL );

				$this->_render_mainbox_extra( $post, $box, $context );

			echo '</div>';
		};

		add_meta_box( $this->classs( $context ),
			$this->get_meta_box_title_posttype( $constant_key ),
			$callback,
			$screen,
			$metabox_context,
			'default'
		);
	}

	protected function _hook_paired_mainbox( $screen, $remove_parent_order = TRUE, $context = 'mainbox', $metabox_context = 'side' )
	{
		if ( ! $this->_paired )
			return FALSE;

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		$this->filter_false_module( 'meta', 'mainbox_callback', 12 );

		if ( $remove_parent_order ) {
			$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
			$this->filter_false_module( 'tweaks', 'metabox_parent' );
			remove_meta_box( 'pageparentdiv', $screen, 'side' );
		}

		$this->class_metabox( $screen, $context );

		$action   = sprintf( 'render_%s_metabox', $context );
		$callback = function( $post, $box ) use ( $constants, $context, $action ) {

			if ( $this->check_hidden_metabox( $box, $post->post_type ) )
				return;

			$action_context = sprintf( '%s_%s', $context, $this->constant( $constants[0] ) );

			echo $this->wrap_open( '-admin-metabox' );

				$this->actions( $action, $post, $box, NULL, $action_context );

				do_action( 'geditorial_meta_render_metabox', $post, $box, NULL );

				$this->_render_mainbox_extra( $post, $box );

			echo '</div>';
		};

		add_meta_box( $this->classs( $context ),
			$this->get_meta_box_title( $constants[0], FALSE ),
			$callback,
			$screen,
			$metabox_context,
			'high'
		);
	}

	// DEFAULT METHOD
	protected function _render_mainbox_extra( $post, $box, $context = 'mainbox' )
	{
		MetaBox::fieldPostMenuOrder( $post );
		MetaBox::fieldPostParent( $post );
	}

	protected function _hook_paired_listbox( $screen, $context = 'listbox', $metabox_context = 'advanced' )
	{
		if ( ! $this->_paired )
			return FALSE;

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		$this->class_metabox( $screen, $context );

		$action   = sprintf( 'render_%s_metabox', $context );
		$callback = function( $post, $box ) use ( $constants, $context, $action ) {

			if ( $this->check_hidden_metabox( $box, $post->post_type ) )
				return;

			if ( $this->check_draft_metabox( $box, $post ) )
				return;

			$action_context = sprintf( '%s_%s', $context, $this->constant( $constants[0] ) );

			echo $this->wrap_open( '-admin-metabox' );

			$this->actions( $action, $post, $box, NULL, $action_context );

			$term = $this->paired_get_to_term( $post->ID, $constants[0], $constants[1] );

			if ( $list = MetaBox::getTermPosts( $this->constant( $constants[1] ), $term, $this->posttypes() ) )
				echo $list;

			else
				echo HTML::wrap( _x( 'No items connected!', 'Module: Paired: Message', 'geditorial' ), 'field-wrap -empty' );

			$this->_render_listbox_extra( $post, $box, $context );

			echo '</div>';
		};

		/* translators: %1$s: current post title, %2$s: posttype singular name */
		$default = _x( 'In &ldquo;%1$s&rdquo; %2$s', 'Module: Metabox Title: `listbox_title`', 'geditorial' );
		$title   = $this->get_string( sprintf( '%s_title', $context ), $constants[0], 'metabox', $default );
		$name    = Helper::getPostTypeLabel( $screen->post_type, 'singular_name' );

		add_meta_box( $this->classs( $context ),
			sprintf( $title, Post::title( NULL, $name ), $name ),
			$callback,
			$screen,
			$metabox_context,
			'low'
		);

		if ( $this->role_can( 'import', NULL, TRUE ) )
			Scripts::enqueueThickBox();
	}

	// DEFAULT METHOD
	// EXPORTS API
	// TODO: support for `post_actions` on Actions module
	protected function _render_listbox_extra( $post, $box, $context = 'listbox' )
	{
		$html = '';

		if ( $this->role_can( 'export', NULL, TRUE ) ) {

			foreach ( $this->exports_get_types( $context ) as $type => $type_args ) {

				/* translators: %1$s: icon markup, %2$s: export type title */
				$label = sprintf( _x( '%1$s Export: %2$s', 'Module: Exports: Button Label', 'geditorial' ), Helper::getIcon( 'download' ), $type_args['title'] );

				$html.= HTML::tag( 'a', [
					'href'  => $this->exports_get_type_download_link( $post->ID, $type, $context, $type_args['target'] ),
					'class' => [ 'button', 'button-small', '-button', '-button-icon', '-exportbutton', '-button-download' ],
					'title' => _x( 'Download Exported CSV File', 'Module: Exports: Button Title', 'geditorial' ),
				], $label );
			}
		}

		if ( $this->role_can( 'import', NULL, TRUE ) ) {

			/* translators: %s: icon markup */
			$label = sprintf( _x( '%s Upload', 'Module: Exports: Button Label', 'geditorial' ), Helper::getIcon( 'upload' ) );

			$args = [
				'ref'      => $post->ID,
				'target'   => 'paired',
				'type'     => $type,
				'context'  => $context,
				'noheader' => 1
			];

			if ( $link = $this->get_adminpage_url( TRUE, $args, 'importitems' ) )
				$html.= HTML::tag( 'a', [
					'href'  => $link,
					'class' => [ 'button', 'button-small', '-button', '-button-icon', '-importbutton', 'thickbox' ],
					'title' => _x( 'Import Items CSV File', 'Module: Exports: Button Title', 'geditorial' ),
				], $label );
		}

		echo HTML::wrap( $html, 'field-wrap -buttons' );
	}

	protected function _hook_paired_pairedbox( $screen, $menuorder = FALSE, $context = 'pairedbox' )
	{
		if ( ! $this->_paired )
			return FALSE;

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		if ( empty( $constants[2] ) )
			$constants[2] = FALSE;

		$this->class_metabox( $screen, $context );

		$action   = sprintf( 'render_%s_metabox', $context );
		$callback = function( $post, $box ) use ( $constants, $context, $action, $menuorder ) {

			if ( $this->check_hidden_metabox( $box, $post->post_type ) )
				return;

			$action_context = sprintf( '%s_%s', $context, $this->constant( $constants[0] ) );

			echo $this->wrap_open( '-admin-metabox' );

			if ( $this->get_setting( 'quick_newpost' ) ) {

				$this->actions( $action, $post, $box, NULL, $action_context );

			} else {

				if ( ! Taxonomy::hasTerms( $this->constant( $constants[1] ) ) )
					MetaBox::fieldEmptyPostType( $this->constant( $constants[0] ) );

				else
					$this->actions( $action, $post, $box, NULL, $action_context );
			}

			do_action( $this->base.'_meta_render_metabox', $post, $box, NULL, $action_context );

			if ( $menuorder )
				MetaBox::fieldPostMenuOrder( $post );

			echo '</div>';
		};

		add_meta_box( $this->classs( $context ),
			$this->get_meta_box_title_posttype( $constants[0] ),
			$callback,
			$screen,
			'side'
		);

		add_action( $this->hook( $action ), function( $post, $box, $fields = NULL, $action_context = NULL ) use ( $constants, $context ) {

			if ( $newpost = $this->get_setting( 'quick_newpost' ) )
				$this->do_render_thickbox_newpostbutton( $post, $constants[0], 'newpost', [ 'target' => 'paired' ] );

			$this->paired_do_render_metabox( $post, $constants[0], $constants[1], $constants[2], $newpost );

		}, 10, 4 );

		if ( $this->get_setting( 'quick_newpost' ) )
			Scripts::enqueueThickBox();
	}

	protected function _hook_paired_store_metabox( $posttype )
	{
		if ( ! $this->_paired )
			return;

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		if ( empty( $constants[2] ) )
			$constants[2] = FALSE;

		add_action( 'save_post_'.$posttype, function( $post_id, $post, $update ) use ( $constants ) {

			if ( ! $this->is_save_post( $post, $this->posttypes() ) )
				return;

			$this->paired_do_store_metabox( $post, $constants[0], $constants[1], $constants[2] );

		}, 20, 3 );
	}

	// PAIRED API
	// OLD: `do_store_metabox_assoc()`
	protected function paired_do_store_metabox( $post, $posttype_constant, $paired_constant, $subterm_constant = FALSE )
	{
		$posttype = $this->constant( $posttype_constant );
		$forced   = $this->get_setting( 'paired_force_parents', FALSE );
		$paired   = self::req( $this->classs( $posttype ), FALSE );

		if ( FALSE === $paired )
			return;

		$terms = [];

		foreach ( (array) $paired as $paired_id ) {

			if ( ! $paired_id )
				continue;

			if ( ! $term = $this->paired_get_to_term( $paired_id, $posttype_constant, $paired_constant ) )
				continue;

			$terms[] = $term->term_id;

			if ( $forced )
				$terms = array_merge( Taxonomy::getTermParents( $term->term_id, $term->taxonomy ), $terms );
		}

		wp_set_object_terms( $post->ID, Arraay::prepNumeral( $terms ), $this->constant( $paired_constant ), FALSE );

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

		wp_set_object_terms( $post->ID, Arraay::prepNumeral( $subterms ), $subterm, FALSE );
	}

	protected function _hook_store_metabox( $posttype )
	{
		if ( $posttype )
			add_action( 'save_post_'.$posttype, [ $this, 'store_metabox' ], 20, 3 );
	}

	// DEFAULT METHOD
	// INTENDED HOOK: `save_post`, `save_post_[post_type]`
	public function dISABLED_store_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		$fields = $this->get_posttype_fields( $post->post_type );

		foreach ( $fields as $field => $args ) {

			if ( $context != $args['context'] )
				continue;

			$key = $this->constant( 'metakey_'.$field, $field );

			// FIXME: DO THE SAVINGS!
		}
	}

	// FIXME: DEPRECATED
	// CAUTION: tax must be hierarchical
	public function add_meta_box_checklist_terms( $constant, $posttype, $role = NULL, $type = FALSE )
	{
		$taxonomy = $this->constant( $constant );
		$metabox  = $this->classs( $taxonomy );
		$edit     = WordPress::getEditTaxLink( $taxonomy );

		if ( $type )
			$this->remove_meta_box( $constant, $posttype, $type );

		add_meta_box( $metabox,
			$this->get_meta_box_title( $constant, $edit, TRUE ),
			[ $this, 'add_meta_box_checklist_terms_cb' ],
			NULL,
			'side',
			'default',
			[
				'taxonomy' => $taxonomy,
				'posttype' => $posttype,
				'metabox'  => $metabox,
				'edit'     => $edit,
				'role'     => $role,
			]
		);
	}

	public function add_meta_box_checklist_terms_cb( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $box['args'] );
		echo '</div>';
	}

	public function add_meta_box_author( $constant, $callback = 'post_author_meta_box' )
	{
		$posttype = PostType::object( $this->constant( $constant ) );

		if ( PostType::supportBlocks( $posttype->name ) )
			return;

		if ( ! apply_filters( $this->base.'_module_metabox_author', TRUE, $posttype->name ) )
			return;

		if ( ! current_user_can( $posttype->cap->edit_others_posts ) )
			return;

		add_meta_box( 'authordiv', // same as core to override
			$this->get_posttype_label( $constant, 'author_label', __( 'Author' ) ),
			$callback,
			NULL,
			'normal',
			'core'
		);
	}

	public function add_meta_box_excerpt( $constant, $callback = 'post_excerpt_meta_box' )
	{
		$posttype = $this->constant( $constant );

		if ( PostType::supportBlocks( $posttype ) )
			return;

		if ( ! apply_filters( $this->base.'_module_metabox_excerpt', TRUE, $posttype ) )
			return;

		add_meta_box( 'postexcerpt', // same as core to override
			$this->get_posttype_label( $constant, 'excerpt_label', __( 'Excerpt' ) ),
			$callback,
			NULL,
			'normal',
			'high'
		);
	}

	// FIXME: DEPRECATED
	public function remove_meta_box( $constant, $posttype, $type = 'tag' )
	{
		if ( 'tag' == $type )
			remove_meta_box( 'tagsdiv-'.$this->constant( $constant ), $posttype, 'side' );

		else if ( 'cat' == $type )
			remove_meta_box( $this->constant( $constant ).'div', $posttype, 'side' );

		else if ( 'parent' == $type )
			remove_meta_box( 'pageparentdiv', $posttype, 'side' );

		else if ( 'image' == $type )
			remove_meta_box( 'postimagediv', $this->constant( $constant ), 'side' );

		else if ( 'author' == $type )
			remove_meta_box( 'authordiv', $this->constant( $constant ), 'normal' );

		else if ( 'excerpt' == $type )
			remove_meta_box( 'postexcerpt', $posttype, 'normal' );

		else if ( 'submit' == $type )
			remove_meta_box( 'submitdiv', $posttype, 'side' );
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
			$title.= ' <span class="postbox-title-info" style="display:none" data-title="info" title="'.HTML::escape( $info ).'">'.HTML::getDashicon( 'editor-help' ).'</span>';

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
		$object = Taxonomy::object( $this->constant( $constant ) );

		if ( is_null( $title ) )
			$title = $this->get_string( 'metabox_title', $constant, 'metabox', NULL );

		if ( is_null( $title ) && ! empty( $object->labels->metabox_title ) )
			$title = $object->labels->metabox_title;

		if ( is_null( $title ) && ! empty( $object->labels->name ) )
			$title = $object->labels->name;

		return $title; // <-- // FIXME: problems with block editor

		// TODO: 'metabox_icon'
		if ( $info = $this->get_string( 'metabox_info', $constant, 'metabox', NULL ) )
			$title.= ' <span class="postbox-title-info" style="display:none" data-title="info" title="'.HTML::escape( $info ).'">'.HTML::getDashicon( 'info' ).'</span>';

		if ( is_null( $url ) )
			$url = WordPress::getEditTaxLink( $object->name, FALSE, [ 'post_type' => $posttype ] );

		if ( $url ) {
			$action = $this->get_string( 'metabox_action', $constant, 'metabox', _x( 'Manage', 'Module: MetaBox Default Action', 'geditorial' ) );
			$title.= ' <span class="postbox-title-action"><a href="'.esc_url( $url ).'" target="_blank">'.$action.'</a></span>';
		}

		return $title;
	}

	public function get_meta_box_title_posttype( $constant, $url = NULL, $title = NULL )
	{
		$object = PostType::object( $this->constant( $constant ) );

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
			$title.= ' <span class="postbox-title-info" style="display:none" data-title="info" title="'.HTML::escape( $info ).'">'.HTML::getDashicon( 'info' ).'</span>';

		if ( current_user_can( $object->cap->edit_others_posts ) ) {

			if ( is_null( $url ) )
				$url = WordPress::getPostTypeEditLink( $object->name );

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
		$object = PostType::object( $this->constant( $constant ) );
		$title  = empty( $object->labels->column_title )
			? ( is_null( $fallback ) ? $object->labels->name : $fallback )
			: $object->labels->column_title;

		return $this->filters( 'column_title', $title, $taxonomy, $constant, $fallback );
	}

	public function get_column_title_taxonomy( $constant, $posttype = FALSE, $fallback = NULL )
	{
		$object = Taxonomy::object( $this->constant( $constant ) );
		$title  = empty( $object->labels->column_title )
			? ( is_null( $fallback ) ? $object->labels->name : $fallback )
			: $object->labels->column_title;

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

	public function is_current_posttype( $constant )
	{
		return PostType::current() == $this->constant( $constant );
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
		if ( ! WordPress::isAdminAJAX() )
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
		if ( ! WordPress::isAdminAJAX() )
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

	// PAIRED API
	// OLD: `_hook_paired_to()`
	protected function _hook_paired_sync_primary_posttype()
	{
		if ( ! $this->_paired )
			return;

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		$paired_posttype = $this->constant( $constants[0] );
		// $paired_taxonomy = $this->constant( $constants[1] );

		add_action( 'save_post_'.$paired_posttype, function( $post_id, $post, $update ) use ( $constants ) {

			// we handle updates on another action, @SEE: `post_updated` action
			if ( ! $update )
				$this->paired_do_save_to_post_new( $post, $constants[0], $constants[1] );

		}, 20, 3 );

		add_action( 'post_updated', function( $post_id, $post_after, $post_before ) use ( $constants ) {
			$this->paired_do_save_to_post_update( $post_after, $post_before, $constants[0], $constants[1] );
		}, 20, 3 );

		add_action( 'wp_trash_post', function( $post_id ) use ( $constants ) {
			$this->paired_do_trash_to_post( $post_id, $constants[0], $constants[1] );
		} );

		add_action( 'untrash_post', function( $post_id ) use ( $constants ) {
			$this->paired_do_untrash_to_post( $post_id, $constants[0], $constants[1] );
		} );

		add_action( 'before_delete_post', function( $post_id ) use ( $constants ) {
			$this->paired_do_before_delete_to_post( $post_id, $constants[0], $constants[1] );
		} );
	}

	// PAIRED API
	// OLD: `get_linked_term()`
	public function paired_get_to_term( $post_id, $posttype_constant_key, $tax_constant_key )
	{
		return $this->paired_get_to_term_direct( $post_id,
			$this->constant( $posttype_constant_key ),
			$this->constant( $tax_constant_key )
		);
	}

	// PAIRED API
	// NOTE: here so modules can override
	public function paired_get_to_term_direct( $post_id, $posttype, $taxonomy )
	{
		return Paired::getToTerm( $post_id, $posttype, $taxonomy );
	}

	// PAIRED API
	// OLD: `set_linked_term()`
	public function paired_set_to_term( $post_id, $term_or_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $post_id )
			return FALSE;

		if ( ! $the_term = Taxonomy::getTerm( $term_or_id, $this->constant( $taxonomy_key ) ) )
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

	// PAIRED API
	// OLD: `remove_linked_term()`
	public function paired_remove_to_term( $post_id, $term_or_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $the_term = Taxonomy::getTerm( $term_or_id, $this->constant( $taxonomy_key ) ) )
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

	// FIXME: DEPRECATED
	public function get_linked_post_id( $term_or_id, $posttype_constant_key, $tax_constant_key, $check_slug = TRUE )
	{
		self::_dep( '$this->paired_get_to_post_id()' );

		return $this->paired_get_to_post_id( $term_or_id, $posttype_constant_key, $tax_constant_key, $check_slug );
	}

	// PAIRED API
	// OLD: `get_linked_post_id()`
	public function paired_get_to_post_id( $term_or_id, $posttype_constant_key, $tax_constant_key, $check_slug = TRUE )
	{
		if ( ! $term = Taxonomy::getTerm( $term_or_id, $this->constant( $tax_constant_key ) ) )
			return FALSE;

		$post_id = get_term_meta( $term->term_id, $this->constant( $posttype_constant_key ).'_linked', TRUE );

		if ( ! $post_id && $check_slug )
			$post_id = PostType::getIDbySlug( $term->slug, $this->constant( $posttype_constant_key ) );

		return $post_id;
	}

	// PAIRED API
	// FIXME: DEPRECATED
	public function get_linked_posts( $post_id, $posttype_constant_key, $tax_constant_key, $count = FALSE, $term_id = NULL )
	{
		self::_dep( '$this->paired_get_from_posts()' );

		return $this->paired_get_from_posts( $post_id, $posttype_constant_key, $tax_constant_key, $count, $term_id );
	}

	// PAIRED API: get (from) posts connected to the pair
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

	// PAIRED API
	public function paired_do_get_to_posts( $posttype_constant_key, $tax_constant_key, $post = NULL, $single = FALSE, $published = TRUE )
	{
		$posts  = $parents = [];
		$terms  = Taxonomy::getPostTerms( $this->constant( $tax_constant_key ), $post );
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
			$posts = Arraay::stripByKeys( $posts, Arraay::prepNumeral( $parents ) );

		if ( ! count( $posts ) )
			return FALSE;

		return $single ? reset( $posts ) : $posts;
	}

	// PAIRED API
	protected function paired_do_save_to_post_update( $after, $before, $posttype_key, $taxonomy_key )
	{
		if ( ! $this->is_save_post( $after, $posttype_key ) )
			return FALSE;

		if ( 'trash' == $after->post_status )
			return FALSE;

		$parent = $this->paired_get_to_term( $after->post_parent, $posttype_key, $taxonomy_key );

		if ( empty( $before->post_name ) )
			$before->post_name = sanitize_title( $before->post_title );

		if ( empty( $after->post_name ) )
			$after->post_name = sanitize_title( $after->post_title );

		$term_args = [
			'name'        => $after->post_title,
			'slug'        => $after->post_name,
			'description' => $after->post_excerpt,
			'parent'      => $parent ? $parent->term_id : 0,
		];

		$taxonomy = $this->constant( $taxonomy_key );

		if ( $paired = $this->paired_get_to_term( $after->ID, $posttype_key, $taxonomy_key ) )
			$the_term = wp_update_term( $paired->term_id, $taxonomy, $term_args );

		else if ( $before_slug = get_term_by( 'slug', $before->post_name, $taxonomy ) )
			$the_term = wp_update_term( $before_slug->term_id, $taxonomy, $term_args );

		else if ( $after_slug = get_term_by( 'slug', $after->post_name, $taxonomy ) )
			$the_term = wp_update_term( $after_slug->term_id, $taxonomy, $term_args );

		else
			$the_term = wp_insert_term( $after->post_title, $taxonomy, $term_args );

		if ( is_wp_error( $the_term ) )
			return FALSE;

		return $this->paired_set_to_term( $after->ID, $the_term['term_id'], $posttype_key, $taxonomy_key );
	}

	// PAIRED API
	protected function paired_do_save_to_post_new( $post, $posttype_key, $taxonomy_key )
	{
		if ( ! $this->is_save_post( $post, $posttype_key ) )
			return FALSE;

		$parent = $this->paired_get_to_term( $post->post_parent, $posttype_key, $taxonomy_key );

		$slug = empty( $post->post_name )
			? sanitize_title( $post->post_title )
			: $post->post_name;

		$term_args = [
			'slug'        => $slug,
			'parent'      => $parent ? $parent->term_id : 0,
			'name'        => $post->post_title,
			'description' => $post->post_excerpt,
		];

		$taxonomy = $this->constant( $taxonomy_key );

		// link to existing term
		if ( $namesake = get_term_by( 'slug', $slug, $taxonomy ) )
			$the_term = wp_update_term( $namesake->term_id, $taxonomy, $term_args );

		else
			$the_term = wp_insert_term( $post->post_title, $taxonomy, $term_args );

		if ( is_wp_error( $the_term ) )
			return FALSE;

		return $this->paired_set_to_term( $post->ID, $the_term['term_id'], $posttype_key, $taxonomy_key );
	}

	// PAIRED API:
	// OLD: `do_trash_post()`
	protected function paired_do_trash_to_post( $post_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $this->is_posttype( $posttype_key, $post_id ) )
			return;

		if ( $the_term = $this->paired_get_to_term( $post_id, $posttype_key, $taxonomy_key ) )
			wp_update_term( $the_term->term_id, $this->constant( $taxonomy_key ), [
				'name' => $the_term->name.'___TRASHED',
				'slug' => $the_term->slug.'-trashed',
			] );
	}

	// PAIRED API
	// OLD: `do_untrash_post()`
	protected function paired_do_untrash_to_post( $post_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $this->is_posttype( $posttype_key, $post_id ) )
			return;

		if ( $the_term = $this->paired_get_to_term( $post_id, $posttype_key, $taxonomy_key ) )
			wp_update_term( $the_term->term_id, $this->constant( $taxonomy_key ), [
				'name' => str_ireplace( '___TRASHED', '', $the_term->name ),
				'slug' => str_ireplace( '-trashed', '', $the_term->slug ),
			] );
	}

	// PAIRED API
	// OLD: `do_before_delete_post()`
	protected function paired_do_before_delete_to_post( $post_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $this->is_posttype( $posttype_key, $post_id ) )
			return;

		if ( $the_term = $this->paired_get_to_term( $post_id, $posttype_key, $taxonomy_key ) ) {
			wp_delete_term( $the_term->term_id, $this->constant( $taxonomy_key ) );
			delete_metadata( 'term', $the_term->term_id, $this->constant( $taxonomy_key ).'_linked' );
		}
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

				$posttypes = array_unique( array_map( function( $r ){
					return $r->post_type;
				}, $posts ) );

				$args = [ $this->constant( $constants[1] ) => $post->post_name ];

				if ( empty( $this->cache['posttypes'] ) )
					$this->cache['posttypes'] = PostType::get( 2 );

				echo '<span class="-counted">'.$this->nooped_count( 'connected', $count ).'</span>';

				$list = [];

				foreach ( $posttypes as $posttype )
					$list[] = HTML::tag( 'a', [
						'href'   => WordPress::getPostTypeEditLink( $posttype, 0, $args ),
						'title'  => _x( 'View the connected list', 'Module: Paired: Title Attr', 'geditorial' ),
						'target' => '_blank',
					], $this->cache['posttypes'][$posttype] );

				echo Strings::getJoined( $list, ' <span class="-posttypes">(', ')</span>' );

			echo '</li>';

		} );
	}

	// PAIRED API
	protected function paired_sync_paired_terms( $posttype_key, $taxonomy_key )
	{
		$count    = 0;
		$taxonomy = $this->constant( $taxonomy_key );
		$metakey  = sprintf( '%s_linked', $this->constant( $posttype_key ) );

		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => FALSE,
			'orderby'    => 'none',
			'fields'     => 'ids',
		] );

		if ( ! $terms || is_wp_error( $terms ) )
			return FALSE;

		$this->raise_resources( count( $terms ) );

		foreach ( $terms as $term_id ) {

			if ( ! $post_id = get_term_meta( $term_id, $metakey, TRUE ) )
				continue;

			$result = wp_set_object_terms( (int) $post_id, $term_id, $taxonomy, FALSE );

			if ( ! is_wp_error( $result ) )
				$count++;
		}

		return $count;
	}

	// PAIRED API
	protected function paired_create_paired_terms( $posttype_key, $taxonomy_key )
	{
		$count = 0;
		$args  = [
			'orderby'     => 'none',
			'post_status' => 'any',
			'post_type'   => $this->constant( $posttype_key ),
			'tax_query'   => [ [
				'taxonomy' => $this->constant( $taxonomy_key ),
				'operator' => 'NOT EXISTS',
			] ],
			'suppress_filters' => TRUE,
			'posts_per_page'   => -1,
		];

		$query = new \WP_Query();
		$posts = $query->query( $args );

		if ( empty( $posts ) )
			return FALSE;

		$this->raise_resources( count( $posts ) );

		foreach ( $posts as $post )
			if ( $this->paired_do_save_to_post_new( $post, $posttype_key, $taxonomy_key ) )
				$count++;

		return $count;
	}

	// PAIRED API
	protected function paired_tools_render_card( $posttype_key, $taxonomy_key )
	{
		if ( ! $this->_paired )
			return FALSE;

		echo $this->wrap_open( [ 'card', '-toolbox-card' ] );
		HTML::h2( _x( 'Paired Tools', 'Module: Paired: Card Title', 'geditorial' ), 'title' );

		echo $this->wrap_open( '-wrap-button-row -sync_paired_terms' );
		Settings::submitButton( 'sync_paired_terms', _x( 'Sync Paired Terms', 'Module: Paired: Button', 'geditorial' ) );
		HTML::desc( _x( 'Tries to set the paired term for all the main posts.', 'Module: Paired: Button Description', 'geditorial' ), FALSE );
		echo '</div>';

		echo $this->wrap_open( '-wrap-button-row -create_paired_terms' );
		Settings::submitButton( 'create_paired_terms', _x( 'Create Paired Terms', 'Module: Paired: Button', 'geditorial' ) );
		HTML::desc( _x( 'Tries to create paired terms for all the main posts.', 'Module: Paired: Button Description', 'geditorial' ), FALSE );
		echo '</div>';

		echo '</div>';
	}

	// PAIRED API
	protected function paired_tools_handle_tablelist( $posttype_key, $taxonomy_key )
	{
		if ( Tablelist::isAction( 'create_paired_posts', TRUE ) ) {

			$terms = Taxonomy::getTerms( $this->constant( $taxonomy_key ), FALSE, TRUE );
			$posts = [];

			foreach ( $_POST['_cb'] as $term_id ) {

				if ( ! isset( $terms[$term_id] ) )
					continue;

				if ( PostType::getIDbySlug( $terms[$term_id]->slug, $this->constant( $posttype_key ) ) )
					continue;

				$posts[] = PostType::newPostFromTerm(
					$terms[$term_id],
					$this->constant( $taxonomy_key ),
					$this->constant( $posttype_key ),
					gEditorial()->user( TRUE )
				);
			}

			WordPress::redirectReferer( [
				'message' => 'created',
				'count'   => count( $posts ),
			] );

		} else if ( Tablelist::isAction( 'resync_paired_images', TRUE ) ) {

			$meta_key = $this->constant( 'metakey_term_image', 'image' );
			$count    = 0;

			foreach ( $_POST['_cb'] as $term_id ) {

				if ( ! $post_id = $this->paired_get_to_post_id( $term_id, $posttype_key, $taxonomy_key ) )
					continue;

				if ( $thumbnail = get_post_thumbnail_id( $post_id ) )
					update_term_meta( $term_id, $meta_key, $thumbnail );

				else
					delete_term_meta( $term_id, $meta_key );

				$count++;
			}

			WordPress::redirectReferer( [
				'message' => 'synced',
				'count'   => $count,
			] );

		} else if ( Tablelist::isAction( 'resync_paired_descs', TRUE ) ) {

			$count = 0;

			foreach ( $_POST['_cb'] as $term_id ) {

				if ( ! $post_id = $this->paired_get_to_post_id( $term_id, $posttype_key, $taxonomy_key ) )
					continue;

				if ( ! $post = Post::get( $post_id ) )
					continue;

				if ( wp_update_term( $term_id, $this->constant( $taxonomy_key ), [ 'description' => $post->post_excerpt ] ) )
					$count++;
			}

			WordPress::redirectReferer( [
				'message' => 'synced',
				'count'   => $count,
			] );

		} else if ( Tablelist::isAction( 'store_paired_orders', TRUE ) ) {

			if ( ! gEditorial()->enabled( 'meta' ) )
				WordPress::redirectReferer( 'wrong' );

			$count = 0;
			$field = $this->constant( 'field_paired_order', sprintf( 'in_%s_order', $this->constant( $posttype_key ) ) );

			foreach ( $_POST['_cb'] as $term_id ) {

				foreach ( $this->paired_get_from_posts( NULL, $posttype_key, $taxonomy_key, FALSE, $term_id ) as $post ) {

					if ( $post->menu_order )
						continue;

					if ( $order = gEditorial()->module( 'meta' )->get_postmeta_field( $post->ID, $field ) ) {

						wp_update_post( [
							'ID'         => $post->ID,
							'menu_order' => $order,
						] );

						$count++;
					}
				}
			}

			WordPress::redirectReferer( [
				'message' => 'ordered',
				'count'   => $count,
			] );

		} else if ( Tablelist::isAction( 'empty_paired_descs', TRUE ) ) {

			$args  = [ 'description' => '' ];
			$count = 0;

			foreach ( $_POST['_cb'] as $term_id )
				if ( wp_update_term( $term_id, $this->constant( $taxonomy_key ), $args ) )
					$count++;

			WordPress::redirectReferer( [
				'message' => 'purged',
				'count'   => $count,
			] );

		} else if ( Tablelist::isAction( 'connect_paired_posts', TRUE ) ) {

			$terms = Taxonomy::getTerms( $this->constant( $taxonomy_key ), FALSE, TRUE );
			$count = 0;

			foreach ( $_POST['_cb'] as $term_id ) {

				if ( ! isset( $terms[$term_id] ) )
					continue;

				if ( ! $post_id = PostType::getIDbySlug( $terms[$term_id]->slug, $this->constant( $posttype_key ) ) )
					continue;

				if ( $this->paired_set_to_term( $post_id, $terms[$term_id], $posttype_key, $taxonomy_key ) )
					$count++;
			}

			WordPress::redirectReferer( [
				'message' => 'updated',
				'count'   => $count,
			] );

		} else if ( Tablelist::isAction( 'delete_paired_terms', TRUE ) ) {

			$count = 0;

			foreach ( $_POST['_cb'] as $term_id ) {

				if ( $this->paired_remove_to_term( NULL, $term_id, $posttype_key, $taxonomy_key ) ) {

					$deleted = wp_delete_term( $term_id, $this->constant( $taxonomy_key ) );

					if ( $deleted && ! is_wp_error( $deleted ) )
						$count++;
				}
			}

			WordPress::redirectReferer( [
				'message' => 'deleted',
				'count'   => $count,
			] );

		} else if ( Tablelist::isAction( 'sync_paired_terms' ) ) {

			if ( FALSE === ( $count = $this->paired_sync_paired_terms( $posttype_key, $taxonomy_key ) ) )
				WordPress::redirectReferer( 'wrong' );

			WordPress::redirectReferer( [
				'message' => 'synced',
				'count'   => $count,
			] );

		} else if ( Tablelist::isAction( 'create_paired_terms' ) ) {

			if ( FALSE === ( $count = $this->paired_create_paired_terms( $posttype_key, $taxonomy_key ) ) )
				WordPress::redirectReferer( 'wrong' );

			WordPress::redirectReferer( [
				'message' => 'created',
				'count'   => $count,
			] );
		}

		return TRUE;
	}

	// PAIRED API
	protected function paired_tools_render_tablelist( $posttype_key, $taxonomy_key, $actions = NULL, $title = NULL )
	{
		if ( ! $this->_paired ) {
			if ( $title ) echo HTML::tag( 'h3', $title );
			HTML::desc( gEditorial()->na(), TRUE, '-empty' );
			return FALSE;
		}

		$columns = [
			'_cb'  => 'term_id',
			'name' => Tablelist::columnTermName(),

			'related' => [
				'title'    => _x( 'Slugged / Paired', 'Module: Paired: Table Column', 'geditorial' ),
				'callback' => function( $value, $row, $column, $index, $key, $args ) use ( $posttype_key, $taxonomy_key ) {

					if ( $post_id = PostType::getIDbySlug( $row->slug, $this->constant( $posttype_key ) ) )
						$html = Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';
					else
						$html = Helper::htmlEmpty();

					$html.= '<hr />';

					if ( $post_id = $this->paired_get_to_post_id( $row, $posttype_key, $taxonomy_key, FALSE ) )
						$html.= Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';
					else
						$html.= Helper::htmlEmpty();

					return $html;
				},
			],

			'description' => [
				'title'    => _x( 'Desc. / Exce.', 'Module: Paired: Table Column', 'geditorial' ),
				'class'    => 'html-column',
				'callback' => function( $value, $row, $column, $index, $key, $args ) use ( $posttype_key, $taxonomy_key ) {

					if ( empty( $row->description ) )
						$html = Helper::htmlEmpty();
					else
						$html = Helper::prepDescription( $row->description );

					if ( $post_id = $this->paired_get_to_post_id( $row, $posttype_key, $taxonomy_key, FALSE ) ) {

						$html.= '<hr />';

						if ( ! $post = Post::get( $post_id ) )
							return $html.gEditorial()->na();

						if ( empty( $post->post_excerpt ) )
							$html.= Helper::htmlEmpty();
						else
							$html.= Helper::prepDescription( $post->post_excerpt );
					}

					return $html;
				},
			],

			'count' => [
				'title'    => _x( 'Count', 'Module: Paired: Table Column', 'geditorial' ),
				'callback' => function( $value, $row, $column, $index, $key, $args ) use ( $posttype_key, $taxonomy_key ) {

					if ( $post_id = PostType::getIDbySlug( $row->slug, $this->constant( $posttype_key ) ) )
						return Number::format( $this->paired_get_from_posts( $post_id, $posttype_key, $taxonomy_key, TRUE ) );

					return Number::format( $row->count );
				},
			],

			'thumb_image' => [
				'title'    => _x( 'Thumbnail', 'Module: Paired: Table Column', 'geditorial' ),
				'class'    => 'image-column',
				'callback' => function( $value, $row, $column, $index, $key, $args ) use ( $posttype_key, $taxonomy_key ) {
					$html = '';

					if ( $post_id = $this->paired_get_to_post_id( $row, $posttype_key, $taxonomy_key, FALSE ) )
						$html = PostType::htmlFeaturedImage( $post_id, [ 45, 72 ] );

					return $html ?: Helper::htmlEmpty();
				},
			],

			'term_image' => [
				'title'    => _x( 'Image', 'Module: Paired: Table Column', 'geditorial' ),
				'class'    => 'image-column',
				'callback' => static function( $value, $row, $column, $index, $key, $args ) {
					$html = Taxonomy::htmlFeaturedImage( $row->term_id, [ 45, 72 ] );
					return $html ?: Helper::htmlEmpty();
				},
			],
		];

		list( $data, $pagination ) = Tablelist::getTerms( [], [], $this->constant( $taxonomy_key ) );

		if ( FALSE !== $actions ) {

			if ( is_null( $actions ) )
				$actions = [];

			$pagination['actions'] = array_merge( [
				'create_paired_posts'  => _x( 'Create Paired Posts', 'Module: Paired: Table Action', 'geditorial' ),
				'connect_paired_posts' => _x( 'Connect Paired Posts', 'Module: Paired: Table Action', 'geditorial' ),
				'resync_paired_images' => _x( 'Re-Sync Paired Images', 'Module: Paired: Table Action', 'geditorial' ),
				'resync_paired_descs'  => _x( 'Re-Sync Paired Descriptions', 'Module: Paired: Table Action', 'geditorial' ),
				'empty_paired_descs'   => _x( 'Empty Paired Descriptions', 'Module: Paired: Table Action', 'geditorial' ),
				'store_paired_orders'  => _x( 'Store Paired Orders', 'Module: Paired: Table Action', 'geditorial' ),
				'delete_paired_terms'  => _x( 'Delete Paired Terms', 'Module: Paired: Table Action', 'geditorial' ),
			], $actions );
		}

		$pagination['before'][] = Tablelist::filterSearch();

		$args = [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', $title ?: _x( 'Paired Terms Tools', 'Module: Paired: Header', 'geditorial' ) ),
			'empty'      => _x( 'There are no terms available!', 'Module: Paired: Message', 'geditorial' ),
			'pagination' => $pagination,
		];

		return HTML::tableList( $columns, $data, $args );
	}

	// PAIRED API
	protected function paired_get_paired_constants()
	{
		return [
			FALSE, // posttype: `primary_posttype`
			FALSE, // taxonomy: `primary_paired`
			FALSE, // subterm:  `primary_subterm`
			FALSE, // exclude:  `primary_taxonomy`
		];
	}

	// for main posttypes
	protected function get_taxonomies_for_restrict_manage_posts()
	{
		return [];
	}

	// PAIRED API
	protected function _hook_screen_restrict_paired( $priority = 10 )
	{
		$constants = $this->paired_get_paired_constants();

		if ( ! empty( $constants[1] ) )
			add_filter( $this->base.'_screen_restrict_taxonomies', function( $taxonomies, $screen ) use ( $constants ) {
				return array_merge( $taxonomies, [ $this->constant( $constants[1] ) ] );
			}, $priority, 2 );

		$this->action( 'restrict_manage_posts', 2, 12, 'restrict_paired' );
		$this->action( 'parse_query', 1, 12, 'restrict_paired' );
	}

	protected function _hook_screen_restrict_taxonomies( $priority = 10 )
	{
		$constants = $this->get_taxonomies_for_restrict_manage_posts();

		if ( empty( $constants ) )
			return FALSE;

		add_filter( $this->base.'_screen_restrict_taxonomies', function( $taxonomies, $screen ) use ( $constants ) {
			return array_merge( $taxonomies, $this->constants( $constants ) );
		}, $priority, 2 );

		$this->action( 'restrict_manage_posts', 2, 20, 'restrict_taxonomy' );
		$this->action( 'parse_query', 1, 12, 'restrict_taxonomy' );

		return TRUE;
	}

	// DEFAULT FILTER
	// USAGE: `$this->action( 'restrict_manage_posts', 2, 12, 'restrict_taxonomy' );`
	public function restrict_manage_posts_restrict_taxonomy( $posttype, $which )
	{
		$constants = $this->get_taxonomies_for_restrict_manage_posts();

		if ( empty( $constants ) )
			return;

		$selected = get_user_option( sprintf( '%s_restrict_%s', $this->base, $posttype ) );

		foreach ( $constants as $constant ) {

			$taxonomy = $this->constant( $constant );

			if ( FALSE !== $selected && ! in_array( $taxonomy, (array) $selected ) )
				continue;

			Listtable::restrictByTaxonomy( $taxonomy );
		}
	}

	// DEFAULT FILTER
	// USAGE: `$this->action( 'restrict_manage_posts', 2, 12, 'restrict_paired' );`
	public function restrict_manage_posts_restrict_paired( $posttype, $which )
	{
		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return;

		$selected = get_user_option( sprintf( '%s_restrict_%s', $this->base, $posttype ) );
		$taxonomy = $this->constant( $constants[1] );

		if ( FALSE === $selected || in_array( $taxonomy, (array) $selected ) )
			Listtable::restrictByTaxonomy( $taxonomy );
	}

	// DEFAULT FILTER
	// USAGE: `$this->action( 'parse_query', 1, 12, 'restrict_paired' );`
	public function parse_query_restrict_paired( &$query )
	{
		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return;

		Listtable::parseQueryTaxonomy( $query, $this->constant( $constants[1] ) );
	}

	// DEFAULT FILTER
	// USAGE: `$this->action( 'parse_query', 1, 12, 'restrict_taxonomy' );`
	public function parse_query_restrict_taxonomy( &$query )
	{
		$constants = $this->get_taxonomies_for_restrict_manage_posts();

		if ( empty( $constants ) )
			return;

		foreach ( $constants as $constant )
			Listtable::parseQueryTaxonomy( $query, $this->constant( $constant ) );
	}

	// FIXME: DEPRECATED
	protected function do_restrict_manage_posts_taxes( $taxes, $posttype_constant_key = TRUE )
	{
		self::_dev_dep( 'restrict_manage_posts_restrict_taxonomy()' );

		if ( TRUE === $posttype_constant_key ||
			$this->is_current_posttype( $posttype_constant_key ) ) {

			foreach ( (array) $taxes as $constant )
				Listtable::restrictByTaxonomy( $this->constant( $constant ) );
		}
	}

	// FIXME: DEPRECATED
	protected function do_parse_query_taxes( &$query, $taxes, $posttype_constant_key = TRUE )
	{
		self::_dev_dep( 'parse_query_restrict_taxonomy()' );

		if ( TRUE === $posttype_constant_key ||
			$this->is_current_posttype( $posttype_constant_key ) ) {

			foreach ( (array) $taxes as $constant )
				Listtable::parseQueryTaxonomy( $query, $this->constant( $constant ) );
		}
	}

	// FIXME: DEPRECATED
	protected function do_restrict_manage_posts_posts( $tax_constant_key, $posttype_constant_key )
	{
		self::_dev_dep( 'restrict_manage_posts_restrict_paired()' );

		Listtable::restrictByPosttype(
			$this->constant( $tax_constant_key ),
			$this->constant( $posttype_constant_key )
		);
	}

	protected function do_posts_clauses_taxes( $pieces, $query, $taxes, $posttype_constant_key = TRUE )
	{
		if ( TRUE === $posttype_constant_key ||
			$this->is_current_posttype( $posttype_constant_key ) ) {

			foreach ( (array) $taxes as $constant ) {

				$taxonomy = $this->constant( $constant );

				if ( isset( $query->query['orderby'] ) && 'taxonomy-'.$taxonomy == $query->query['orderby'] )
					return Listtable::orderClausesByTaxonomy( $pieces, $query, $taxonomy );
			}
		}

		return $pieces;
	}

	protected function do_restrict_manage_posts_authors( $posttype )
	{
		$extra = [];
		$all   = $this->get_string( 'show_option_all', $posttype, 'misc', NULL );

		if ( ! is_null( $all ) )
			$extra['show_option_all'] = $all;

		Listtable::restrictByAuthor( $GLOBALS['wp_query']->get( 'author' ) ?: 0, 'author', $extra );
	}

	// NOTE: cannot use 'wp_insert_post_data' filter
	protected function _hook_autofill_posttitle( $posttype )
	{
		add_action( 'save_post_'.$posttype, [ $this, '_save_autofill_posttitle' ], 20, 3 );
	}

	public function _save_autofill_posttitle( $post_id, $post, $update )
	{
		remove_action( 'save_post_'.$post->post_type, [ $this, '_save_autofill_posttitle' ], 20, 3 );

		if ( FALSE === ( $posttitle = $this->_get_autofill_posttitle( $post ) ) )
			return;

		if ( ! wp_update_post( [ 'ID' => $post->ID, 'post_title' => $posttitle ] ) )
			$this->log( 'FAILED', sprintf( 'updating title of post #%s', $post->ID ) );
	}

	// DEFAULT CALLBACK
	protected function _get_autofill_posttitle( $post )
	{
		return FALSE;
	}

	// @REF: https://make.wordpress.org/core/2012/12/01/more-hooks-on-the-edit-screen/
	protected function _hook_editform_readonly_title()
	{
		add_action( 'edit_form_after_title', function( $post ) {
			$html = Post::title( $post );
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

	protected function dashboard_glance_post( $constant )
	{
		return MetaBox::glancePosttype(
			$this->constant( $constant ),
			$this->get_noop( $constant ),
			'-'.$this->slug()
		);
	}

	protected function dashboard_glance_taxonomy( $constant )
	{
		return MetaBox::glanceTaxonomy(
			$this->constant( $constant ),
			$this->get_noop( $constant ),
			'-'.$this->slug()
		);
	}

	// @REF: `wp_add_dashboard_widget()`
	protected function add_dashboard_widget( $name, $title = NULL, $action = FALSE, $extra = [], $callback = NULL, $context = 'dashboard' )
	{
		// FIXME: test this
		// if ( ! $this->cuc( $context ) )
		// 	return FALSE;

		if ( is_null( $title ) )
			$title = $this->get_string( 'widget_title',
				$this->get_setting( 'summary_scope', 'all' ),
				$context,
				_x( 'Editorial Content Summary', 'Module: Dashboard Widget Title', 'geditorial' )
			);

		$screen = get_current_screen();
		$hook   = self::sanitize_hook( $name );
		$id     = $this->classs( $name );
		$title  = $this->filters( 'dashboard_widget_title', $title, $name, $context );
		$args   = array_merge( [
			'__widget_basename' => $title, // passing title without extra markup
		], $extra );

		if ( is_array( $action ) ) {

			$title.= MetaBox::getTitleAction( $action );

		} else if ( $action ) {

			switch ( $action ) {

				case 'refresh':
					$title.= MetaBox::titleActionRefresh( $hook );
					break;

				case 'info' :

					if ( method_exists( $this, 'get_widget_'.$hook.'_info' ) )
						$title.= MetaBox::titleActionInfo( call_user_func( [ $this, 'get_widget_'.$hook.'_info' ] ) );

					break;
			}
		}

		if ( is_null( $callback ) )
			$callback = [ $this, 'render_widget_'.$hook ];

		add_meta_box( $id, $title, $callback, $screen, 'normal', 'default', $args );

		add_filter( 'postbox_classes_'.$screen->id.'_'.$id, function( $classes ) use ( $name, $context ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-admin-postbox'.'-'.$name,
				'-'.$this->key,
				'-'.$this->key.'-'.$name,
				'-context-'.$context,
			] );
		} );

		if ( in_array( $id, get_hidden_meta_boxes( $screen ) ) )
			return FALSE; // prevent scripts

		return TRUE;
	}

	protected function do_dashboard_term_summary( $constant, $box, $posttypes = NULL, $edit = NULL )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		// using core styles
		echo $this->wrap_open( [ '-admin-widget', '-core-styles' ], TRUE, 'dashboard_right_now' );

		$taxonomy = Taxonomy::object( $this->constant( $constant ) );

		if ( ! Taxonomy::hasTerms( $taxonomy->name ) ) {

			if ( is_null( $edit ) )
				$edit = WordPress::getEditTaxLink( $taxonomy->name );

			if ( $edit )
				$empty = HTML::tag( 'a', [
					'href'   => $edit,
					'title'  => $taxonomy->labels->add_new_item,
					'target' => '_blank',
				], $taxonomy->labels->no_terms );

			else
				$empty = gEditorial()->na();

			HTML::desc( $empty, FALSE, '-empty' );
			echo '</div>';
			return;
		}

		$scope  = $this->get_setting( 'summary_scope', 'all' );
		$suffix = 'all' == $scope ? 'all' : get_current_user_id();
		$key    = $this->hash( 'widgetsummary', $scope, $suffix );

		if ( WordPress::isFlush( 'read' ) )
			delete_transient( $key );

		if ( FALSE === ( $html = get_transient( $key ) ) ) {

			if ( $this->check_hidden_metabox( $box, FALSE, '</div>' ) )
				return;

			if ( $summary = $this->get_dashboard_term_summary( $constant, $posttypes, NULL, $scope ) ) {

				$html = Text::minifyHTML( $summary );
				set_transient( $key, $html, 12 * HOUR_IN_SECONDS );

			} else {

				HTML::desc( _x( 'There are no reports available!', 'Module: Message', 'geditorial' ), FALSE, '-empty' );
			}
		}

		if ( $html )
			echo '<div class="main"><ul>'.$html.'</ul></div>';

		echo '</div>';
	}

	protected function get_dashboard_term_summary( $constant, $posttypes = NULL, $terms = NULL, $scope = 'all', $user_id = NULL, $list = 'li' )
	{
		$html     = '';
		$check    = FALSE;
		$all      = PostType::get( 3 );
		$exclude  = Database::getExcludeStatuses();
		$taxonomy = $this->constant( $constant );

		if ( ! $object = Taxonomy::object( $taxonomy ) )
			return FALSE;

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		if ( is_null( $terms ) )
			$terms = Taxonomy::getTerms( $taxonomy, FALSE, TRUE, 'slug', [
				'hide_empty' => TRUE,
				'exclude'    => $this->get_setting( 'summary_excludes', '' ),
			] );

		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		if ( 'roles' == $scope && $this->role_can( 'restricted', $user_id, FALSE, FALSE ) )
			$check = TRUE; // 'hidden' == $this->get_setting( 'restricted', 'disabled' );

		if ( $this->get_setting( 'summary_drafts', FALSE ) )
			$exclude = array_diff( $exclude, [ 'draft' ] );

		if ( count( $terms ) ) {

			$counts  = Database::countPostsByTaxonomy( $terms, $posttypes, ( 'current' == $scope ? $user_id : 0 ), $exclude );
			$objects = [];

			foreach ( $counts as $term => $posts ) {

				if ( $check && ( $roles = get_term_meta( $terms[$term]->term_id, 'roles', TRUE ) ) ) {

					if ( ! User::hasRole( array_merge( [ 'administrator' ], (array) $roles ), $user_id ) )
						continue;
				}

				$name = sanitize_term_field( 'name', $terms[$term]->name, $terms[$term]->term_id, $terms[$term]->taxonomy, 'display' );

				foreach ( $posts as $type => $count ) {

					if ( ! $count )
						continue;

					if ( count( $posttypes ) > 1 )
						$text = sprintf( '<b>%3$s</b> %1$s: <b title="%4$s">%2$s</b>', Helper::noopedCount( $count, $all[$type] ), Strings::trimChars( $name, 35 ), Number::format( $count ), $name );
					else
						$text = sprintf( '<b>%2$s</b> %1$s', $name, Number::format( $count ) );

					if ( empty( $objects[$type] ) )
						$objects[$type] = PostType::object( $type );

					$classes = [
						'geditorial-glance-item',
						'-'.$this->key,
						'-term',
						'-taxonomy-'.$taxonomy,
						'-term-'.$term.'-'.$type.'-count',
					];

					if ( $objects[$type] && current_user_can( $objects[$type]->cap->edit_posts ) )
						$text = HTML::tag( 'a', [
							'href'  => WordPress::getPostTypeEditLink( $type, ( 'current' == $scope ? $user_id : 0 ), [ $taxonomy => $term ] ),
							'class' => $classes,
						], $text );

					else
						$text = HTML::wrap( $text, $classes, FALSE );

					$html.= HTML::tag( $list, $text );
				}
			}
		}

		if ( $this->get_setting( 'count_not', FALSE ) ) {

			$none = Helper::getTaxonomyLabel( $object, 'show_option_no_items' );
			$not  = Database::countPostsByNotTaxonomy( $taxonomy, $posttypes, ( 'current' == $scope ? $user_id : 0 ), $exclude );

			foreach ( $not as $type => $count ) {

				if ( ! $count )
					continue;

				if ( count( $posttypes ) > 1 )
					$text = sprintf( '<b>%3$s</b> %1$s %2$s', Helper::noopedCount( $count, $all[$type] ), $none, Number::format( $count ) );
				else
					$text = sprintf( '<b>%2$s</b> %1$s', $none, Number::format( $count ) );

				if ( empty( $objects[$type] ) )
					$objects[$type] = PostType::object( $type );

				$classes = [
					'geditorial-glance-item',
					'-'.$this->key,
					'-not-in',
					'-taxonomy-'.$taxonomy,
					'-not-in-'.$type.'-count',
				];

				if ( $objects[$type] && current_user_can( $objects[$type]->cap->edit_posts ) )
					$text = HTML::tag( 'a', [
						'href'  => WordPress::getPostTypeEditLink( $type, ( 'current' == $scope ? $user_id : 0 ), [ $taxonomy => '-1' ] ),
						'class' => $classes,
					], $text );

				else
					$text = HTML::wrap( $text, $classes, FALSE );

				$html.= HTML::tag( $list, [ 'class' => 'warning' ], $text );
			}
		}

		return $html;
	}

	public function get_column_icon( $link = FALSE, $icon = NULL, $title = NULL, $posttype = 'post', $extra = [] )
	{
		if ( is_null( $icon ) )
			$icon = $this->module->icon;

		if ( is_null( $title ) )
			$title = $this->get_string( 'column_icon_title', $posttype, 'misc', '' );

		return HTML::tag( ( $link ? 'a' : 'span' ), [
			'href'   => $link ?: FALSE,
			'title'  => $title ?: FALSE,
			'class'  => array_merge( [ '-icon', ( $link ? '-link' : '-info' ) ], (array) $extra ),
			'target' => $link ? '_blank' : FALSE,
		], Helper::getIcon( $icon ) );
	}

	// adds the module enabled class to body in admin
	public function _admin_enabled()
	{
		add_action( 'admin_body_class', [ $this, 'admin_body_class_enabled' ] );
	}

	public function admin_body_class_enabled( $classes )
	{
		return ' '.$this->classs( 'enabled' ).$classes;
	}

	// @REF: https://github.com/scribu/wp-posts-to-posts/wiki
	public function p2p_register( $constant, $posttypes = NULL )
	{
		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		if ( empty( $posttypes ) )
			return FALSE;

		$to  = $this->constant( $constant );
		$p2p = $this->constant( $constant.'_p2p' );
		$pre = empty( $this->strings['p2p'][$constant] ) ? [] : $this->strings['p2p'][$constant];

		$args = array_merge( [
			'name'            => $p2p,
			'from'            => $posttypes,
			'to'              => $to,
			'can_create_post' => FALSE,
			'admin_column'    => 'from', // 'any', 'from', 'to', FALSE
			'admin_box'       => [
				'show'    => 'from',
				'context' => 'advanced',
			],
		], $pre );

		$hook = 'geditorial_'.$this->module->name.'_'.$to.'_p2p_args';

		if ( $args = apply_filters( $hook, $args, $posttypes ) )
			if ( p2p_register_connection_type( $args ) )
				$this->_p2p = $p2p;
	}

	public function p2p_get_meta( $p2p_id, $meta_key, $before = '', $after = '', $args = [] )
	{
		if ( ! $this->_p2p )
			return '';

		if ( ! $meta = p2p_get_meta( $p2p_id, $meta_key, TRUE ) )
			return '';

		if ( ! empty( $args['type'] ) && 'text' == $args['type'] )
			$meta = apply_filters( 'string_format_i18n', $meta );

		if ( ! empty( $args['template'] ) )
			$meta = sprintf( $args['template'], $meta );

		if ( ! empty( $args['title'] ) )
			$meta = '<span title="'.HTML::escape( $args['title'] ).'">'.$meta.'</span>';

		return $before.$meta.$after;
	}

	public function p2p_get_meta_row( $constant, $p2p_id, $before = '', $after = '' )
	{
		if ( ! $this->_p2p )
			return '';

		$row = '';

		if ( ! empty( $this->strings['p2p'][$constant]['fields'] ) )
			foreach ( $this->strings['p2p'][$constant]['fields'] as $field => $args )
				$row.= $this->p2p_get_meta( $p2p_id, $field, $before, $after, $args );

		return $row;
	}

	// @REF: https://github.com/scribu/wp-posts-to-posts/wiki/Creating-connections-programmatically
	public function p2p_connect( $constant, $from, $to, $meta = [] )
	{
		if ( ! $this->_p2p )
			return FALSE;

		$type = p2p_type( $this->constant( $constant.'_p2p' ) );
		// $id   = $type->connect( $from, $to, [ 'date' => current_time( 'mysql' ) ] );
		$id   = $type->connect( $from, $to, $meta );

		if ( is_wp_error( $id ) )
			return FALSE;

		// foreach ( $meta as $key => $value )
		// 	p2p_add_meta( $id, $key, $value );

		return TRUE;
	}

	protected function column_row_p2p_to_posttype( $constant, $post )
	{
		static $icons = [];

		if ( ! $this->_p2p )
			return;

		$extra = [ 'p2p:per_page' => -1, 'p2p:context' => 'admin_column' ];
		$type  = $this->constant( $constant.'_p2p' );

		if ( ! $p2p_type = p2p_type( $type ) )
			return;

		$p2p   = $p2p_type->get_connected( $post, $extra, 'abstract' );
		$count = count( $p2p->items );

		if ( ! $count )
			return;

		if ( empty( $icons[$constant] ) )
			$icons[$constant] = $this->get_column_icon( FALSE, NULL, $this->strings['p2p'][$constant]['title']['to'] );

		if ( empty( $this->cache['posttypes'] ) )
			$this->cache['posttypes'] = PostType::get( 2 );

		$posttypes = array_unique( array_map( function( $r ){
			return $r->post_type;
		}, $p2p->items ) );

		$args = [
			'connected_direction' => 'to',
			'connected_type'      => $type,
			'connected_items'     => $post->ID,
		];

		echo '<li class="-row -p2p -connected">';

			echo $icons[$constant];

			echo '<span class="-counted">'.$this->nooped_count( 'connected', $count ).'</span>';

			$list = [];

			foreach ( $posttypes as $posttype )
				$list[] = HTML::tag( 'a', [
					'href'   => WordPress::getPostTypeEditLink( $posttype, 0, $args ),
					'title'  => _x( 'View the connected list', 'Module: P2P', 'geditorial' ),
					'target' => '_blank',
				], $this->cache['posttypes'][$posttype] );

			echo Strings::getJoined( $list, ' <span class="-posttypes">(', ')</span>' );

		echo '</li>';
	}

	protected function column_row_p2p_from_posttype( $constant, $post )
	{
		static $icons = [];

		if ( ! $this->_p2p )
			return;

		if ( empty( $icons[$constant] ) )
			$icons[$constant] = $this->get_column_icon( FALSE, NULL, $this->strings['p2p'][$constant]['title']['from'] );

		$extra = [ 'p2p:per_page' => -1, 'p2p:context' => 'admin_column' ];
		$type  = $this->constant( $constant.'_p2p' );

		if ( ! $p2p_type = p2p_type( $type ) )
			return;

		$p2p = $p2p_type->get_connected( $post, $extra, 'abstract' );

		foreach ( $p2p->items as $item ) {
			echo '<li class="-row -book -p2p -connected">';

				if ( current_user_can( 'edit_post', $item->get_id() ) )
					echo $this->get_column_icon( get_edit_post_link( $item->get_id() ),
						NULL, $this->strings['p2p'][$constant]['title']['from'] );
				else
					echo $icons[$constant];

				$args = [
					'connected_direction' => 'to',
					'connected_type'      => $type,
					'connected_items'     => $item->get_id(),
				];

				echo HTML::tag( 'a', [
					'href'   => WordPress::getPostTypeEditLink( $post->post_type, 0, $args ),
					'title'  => _x( 'View all connected', 'Module: P2P', 'geditorial' ),
					'target' => '_blank',
				], Strings::trimChars( $item->get_title(), 85 ) );

				echo $this->p2p_get_meta_row( $constant, $item->p2p_id, ' &ndash; ', '' );

			echo '</li>';
		}
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
			$data['menu_order'] = PostType::getLastMenuOrder( $postarr['post_type'],
				( isset( $postarr['ID'] ) ? $postarr['ID'] : '' ) ) + 1;

		return $data;
	}

	// DEFAULT FILTER
	// USAGE: `$this->action_self( 'newpost_content', 4, 99, 'menu_order' );`
	public function newpost_content_menu_order( $posttype, $post, $target, $linked )
	{
		HTML::inputHidden( 'menu_order', PostType::getLastMenuOrder( $posttype, $post->ID ) + 1 );
	}

	protected function _hook_menu_posttype( $constant, $parent_slug = 'index.php', $context = 'adminpage' )
	{
		if ( ! $posttype = get_post_type_object( $this->constant( $constant ) ) )
			return FALSE;

		return add_submenu_page(
			$parent_slug,
			HTML::escape( $this->get_string( 'page_title', $constant, $context, $posttype->labels->all_items ) ),
			HTML::escape( $this->get_string( 'menu_title', $constant, $context, $posttype->labels->menu_name ) ),
			$posttype->cap->edit_posts,
			'edit.php?post_type='.$posttype->name
		);
	}

	// $parent_slug options: `options-general.php`, `users.php`
	// also set `$this->filter_string( 'parent_file', $parent_slug );`
	protected function _hook_menu_taxonomy( $constant, $parent_slug = 'index.php', $context = 'submenu' )
	{
		if ( ! $taxonomy = get_taxonomy( $this->constant( $constant ) ) )
			return FALSE;

		return add_submenu_page(
			$parent_slug,
			HTML::escape( $this->get_string( 'page_title', $constant, $context, $taxonomy->labels->name ) ),
			HTML::escape( $this->get_string( 'menu_title', $constant, $context, $taxonomy->labels->menu_name ) ),
			$taxonomy->cap->manage_terms,
			'edit-tags.php?taxonomy='.$taxonomy->name
		);
	}

	// NOTE: hack to keep the submenu only on primary paired posttype
	// for hiding the menu just set `show_in_menu` to `FALSE` on taxonomy args
	protected function remove_taxonomy_submenu( $taxonomies, $posttypes = NULL )
	{
		if ( ! $taxonomies )
			return;

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		foreach ( (array) $taxonomies as $taxonomy )
			foreach ( $posttypes as $posttype )
				remove_submenu_page(
					'post' == $posttype ? 'edit.php' : 'edit.php?post_type='.$posttype,
					'post' == $posttype ? 'edit-tags.php?taxonomy='.$taxonomy : 'edit-tags.php?taxonomy='.$taxonomy.'&amp;post_type='.$posttype
				);
	}

	protected function _hook_ajax( $auth = TRUE, $hook = NULL, $method = 'ajax' )
	{
		if ( is_null( $hook ) )
			$hook = $this->hook();

		if ( is_null( $auth ) || TRUE === $auth )
			add_action( 'wp_ajax_'.$hook, [ $this, $method ] );

		if ( is_null( $auth ) || FALSE === $auth )
			add_action( 'wp_ajax_nopriv_'.$hook, [ $this, $method ] );
	}

	// DEFAULT FILTER
	public function ajax()
	{
		Ajax::errorWhat();
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

	// DEFAULT FILTER
	public function tweaks_taxonomy_info( $info, $object, $posttype )
	{
		$paired = $this->paired_get_paired_constants();

		// avoid paired tax on paired posttype's taxonomy column
		if ( ! empty( $paired[0] ) && ! empty( $paired[1] )
			&& $posttype === $this->constant( $paired[0] )
			&& $object->name === $this->constant( $paired[1] ) )
				return FALSE;

		$icons = $this->get_module_icons();

		if ( empty( $icons['taxonomies'] ) )
			return $info;

		foreach ( $icons['taxonomies'] as $constant => $icon )
			if ( $object->name == $this->constant( $constant ) )
				return [
					'icon'  => is_null( $icon ) ? $this->module->icon : $icon,
					'title' => $this->get_column_title( 'tweaks', $constant, $object->labels->name ),
					'edit'  => NULL,
				];

		return $info;
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

		return 'admin-post';
	}

	// DEFAULT FILTER
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

		HTML::desc( $message, TRUE, 'field-wrap -empty' );

		return TRUE;
	}

	// TODO: move to 'User` Core
	protected function get_blog_users( $fields = NULL, $list = FALSE, $admins = FALSE )
	{
		if ( is_null( $fields ) )
			$fields = [
				'ID',
				'display_name',
				'user_login',
				'user_email',
			];

		$excludes = $this->get_setting( 'excluded_roles', [] );

		if ( $admins )
			$excludes[] = 'administrator';

		$args = [
			'number'       => -1,
			'orderby'      => 'post_count',
			'fields'       => $fields,
			'role__not_in' => $excludes,
			'count_total'  => FALSE,
		];

		if ( $list )
			$args['include'] = (array) $list;

		$query = new \WP_User_Query( $args );

		return $query->get_results();
	}

	// EXPORTS API
	protected function exports_get_types( $context )
	{
		$types = [
			'simple'   => [
				'title'  => _x( 'Simple', 'Module: Export Type Title', 'geditorial' ),
				'target' => 'paired',
			],

			'advanced' => [
				'title'  => _x( 'Advanced', 'Module: Export Type Title', 'geditorial' ),
				'target' => 'paired',
			],

			'full' => [
				'title'  => _x( 'Full', 'Module: Export Type Title', 'geditorial' ),
				'target' => 'paired',
			],
		];

		return $this->filters( 'export_types', $types, $context );
	}

	// EXPORTS API
	protected function exports_get_type_download_link( $reference, $type, $context, $target = 'default', $extra = [] )
	{
		return add_query_arg( array_merge( [
			'action'  => $this->classs( 'exports' ),
			'ref'     => $reference,
			'target'  => $target,
			'type'    => $type,
			'context' => $context,
		], $extra ), get_admin_url() );
	}

	// NOTE: only fires on admin
	// EXPORTS API
	protected function exports_do_check_requests()
	{
		if ( $this->classs( 'exports' ) != self::req( 'action' ) )
			return FALSE;

		$reference = self::req( 'ref', NULL );
		$target    = self::req( 'target', 'default' );
		$type      = self::req( 'type', 'simple' );
		$context   = self::req( 'context', 'default' );

		if ( FALSE !== ( $data = $this->exports_get_export_data( $reference, $target, $type, $context ) ) )
			Core\Text::download( $data, Core\File::prepName( sprintf( '%s-%s.csv', $context, $type ) ) );

		Core\WordPress::redirectReferer( 'wrong' );
	}

	// EXPORTS API
	protected function exports_prep_posts_for_csv_export( $posts, $props, $fields = [], $metas = [] )
	{
		$data  = [ array_merge(
			$props,
			Core\Arraay::prefixValues( $fields, 'field__' ),
			Core\Arraay::prefixValues( $metas, 'meta__' )
		) ];

		foreach ( $posts as $post ) {

			$row = [];

			foreach ( $props as $prop ) {

				if ( 'post_name' === $prop )
					$row[] = urldecode( $post->{$prop} );

				else if ( property_exists( $post, $prop ) )
					$row[] = trim( $post->{$prop} );

				else
					$row[] = ''; // unknown field!
			}

			foreach ( $fields as $field )
				$row[] = Template::getMetaFieldRaw( $field, $post->ID, 'meta' ) ?: '';

			$saved = get_post_meta( $post->ID );

			foreach ( $metas as $meta )
				$row[] = ( empty( $saved[$meta][0] ) ? '' : trim( $saved[$meta][0] ) ) ?: '';

			$data[] = $row;
		}

		return Core\Text::toCSV( $data );
	}

	// EXPORTS API
	protected function exports_get_export_data( $reference, $target, $type, $context )
	{
		$data = FALSE;

		switch ( $target ) {

			case 'paired':

				$constants = $this->paired_get_paired_constants();

				if ( empty( $constants[0] ) || empty( $constants[1] ) )
					return FALSE;

				if ( ! $posttypes = $this->posttypes() )
					return FALSE;

				if ( ! $paired = $this->paired_get_to_term( (int) $reference, $constants[0], $constants[1] ) )
					return FALSE;

				$args = [
					'posts_per_page' => -1,
					'orderby'        => [ 'menu_order', 'date' ],
					'order'          => 'ASC',
					'post_type'      => $posttypes,
					'post_status'    => [ 'publish', 'future', 'pending', 'draft' ],
					'tax_query'      => [ [
						'taxonomy' => $this->constant( $constants[1] ),
						'field'    => 'id',
						'terms'    => [ $paired->term_id ],
					] ],
				];

				$posts  = get_posts( $args );
				$props  = $this->exports_get_post_props( $posttypes, $reference, $target, $type, $context );
				$fields = $this->exports_get_post_fields( $posttypes, $reference, $target, $type, $context );
				$metas  = $this->exports_get_post_metas( $posttypes, $reference, $target, $type, $context );
				$data   = $this->exports_prep_posts_for_csv_export( $posts, $props, $fields, $metas );

				break;
		}

		return $this->filters( 'get_export_data', $data, $reference, $target, $type, $context );
	}

	protected function exports_get_post_props( $posttypes, $reference, $target, $type, $context )
	{
		$list = [
			'ID',
			'post_title',
		];

		switch ( $type ) {

			case 'simple':

				break;

			case 'advanced':

				$list = array_merge( $list, [
					'post_date',
					'post_content',
					'post_excerpt',
					'post_type',
				] );
				break;

			case 'full':

				$list = array_merge( $list, [
					'post_author',
					'post_date',
					'post_content',
					'post_excerpt',
					'post_status',
					'post_name',
					'post_parent',
					'menu_order',
					'post_type',
				] );
				break;
		}

		return $this->filters( 'get_post_props', Core\Arraay::prepString( $list ), $posttypes, $reference, $target, $type, $context );
	}

	protected function exports_get_post_fields( $posttypes, $reference, $target, $type, $context )
	{
		$list = [];

		foreach ( $posttypes as $posttype ) {

			$fields = PostType::supports( $posttype, 'meta_fields' );

			if ( empty( $fields ) )
				continue;

			switch ( $type ) {

				case 'simple':

					$keys = [
						'first_name',
						'last_name',
						'identity_number',
					];

					$keeps = Core\Arraay::keepByKeys( $fields, $keys );
					$list  = array_merge( $list, array_keys( $keeps ) );

					break;

				case 'advanced':

					$keys = [
						'first_name',
						'last_name',
						'identity_number',
						'mobile_number',
						'date_of_birth',
					];

					$keeps = Core\Arraay::keepByKeys( $fields, $keys );
					$list  = array_merge( $list, array_keys( $keeps ) );

					break;

				case 'full':

					$list = array_merge( $list, array_keys( $fields ) );

					break;
			}
		}

		return $this->filters( 'get_post_fields', Core\Arraay::prepString( $list ), $posttypes, $reference, $target, $type, $context );
	}

	protected function exports_get_post_metas( $posttypes, $reference, $target, $type, $context )
	{
		$list = [];

		foreach ( $posttypes as $posttype ) {

			switch ( $type ) {

				case 'simple':

					break;

				case 'advanced':

					$list = array_merge( $list, [] );
					break;

				case 'full':

					$list = array_merge( $list, [] );
					break;
			}
		}

		return $this->filters( 'get_post_metas', Core\Arraay::prepString( $list ), $posttypes, $reference, $target, $type, $context );
	}

	protected function do_template_include( $template, $constant, $archive_callback = NULL, $empty_callback = NULL )
	{
		if ( ! $this->get_setting( 'archive_override', TRUE ) )
			return $template;

		if ( is_embed() || is_search() )
			return $template;

		$posttype = $this->constant( $constant );

		if ( $posttype != $GLOBALS['wp_query']->get( 'post_type' ) )
			return $template;

		if ( ! is_404() && ! is_post_type_archive( $posttype ) )
			return $template;

		if ( is_404() ) {

			// if new posttype disabled
			if ( FALSE === $empty_callback )
				return $template;

			// helps with 404 redirections
			if ( ! is_user_logged_in() )
				return $template;

			if ( is_null( $empty_callback ) )
				$empty_callback = [ $this, 'template_empty_content' ];

			nocache_headers();
			// WordPress::doNotCache();

			Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->template_get_empty_title(),
				'post_type'  => $posttype,
				'is_single'  => TRUE,
				'is_404'     => TRUE,
			], $empty_callback );

			$this->filter_append( 'post_class', [ 'empty-posttype', 'empty-'.$posttype ] );

			// $template = get_singular_template();
			$template = get_single_template();

		} else {

			if ( is_null( $archive_callback ) )
				$archive_callback = [ $this, 'template_archive_content' ];

			Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->template_get_archive_title( $posttype ),
				'post_type'  => $posttype,
				'is_page'    => TRUE,
				'is_archive' => TRUE,
			], $archive_callback );

			$this->filter_append( 'post_class', [ 'archive-posttype', 'archive-'.$posttype ] );
			$this->filter( 'post_type_archive_title', 2 );
			// $this->filter( 'gtheme_navigation_crumb_archive', 2 );
			$this->filter_false( 'gtheme_navigation_crumb_archive' );

			$template = Theme::getTemplate( $this->get_setting( 'archive_template' ) );
		}

		$this->filter_empty_string( 'previous_post_link' );
		$this->filter_empty_string( 'next_post_link' );

		$this->enqueue_styles();

		defined( 'GNETWORK_DISABLE_CONTENT_ACTIONS' )
			or define( 'GNETWORK_DISABLE_CONTENT_ACTIONS', TRUE );

		defined( 'GEDITORIAL_DISABLE_CONTENT_ACTIONS' )
			or define( 'GEDITORIAL_DISABLE_CONTENT_ACTIONS', TRUE );

		return $template;
	}

	// DEFAULT METHOD: title for overrided empty page
	public function template_get_empty_title( $fallback = NULL )
	{
		if ( $title = URL::prepTitleQuery( $GLOBALS['wp_query']->get( 'name' ) ) )
			return $title;

		if ( is_null( $fallback ) )
			return _x( '[Untitled]', 'Module: Template Title', 'geditorial' );

		return $fallback;
	}

	// DEFAULT METHOD: content for overrided empty page
	public function template_get_empty_content( $atts = [] )
	{
		if ( $content = $this->get_setting( 'empty_content' ) )
			return Text::autoP( trim( $content ) );

		return '';
	}

	// DEFAULT METHOD: title for overrided archive page
	public function template_get_archive_title( $posttype )
	{
		return $this->get_setting_fallback( 'archive_title',
			Helper::getPostTypeLabel( $posttype, 'all_items' ) );
	}

	// no need to check for posttype
	public function post_type_archive_title( $name, $posttype )
	{
		return $this->get_setting_fallback( 'archive_title', $name );
	}

	public function gtheme_navigation_crumb_archive( $crumb, $args )
	{
		return $this->get_setting_fallback( 'archive_title', $crumb );
	}

	// DEFAULT METHOD: content for overrided archive page
	public function template_get_archive_content()
	{
		$setting = $this->get_setting_fallback( 'archive_content', NULL );

		if ( ! is_null( $setting ) )
			return $setting; // might be empty string

		// NOTE: here to avoid further process
		if ( $default = $this->template_get_archive_content_default() )
			return $default;

		if ( is_post_type_archive() )
			return ShortCode::listPosts( 'assigned',
				PostType::current(),
				'',
				[
					'orderby' => 'menu_order', // WTF: must apply to `assigned`
					'id'      => 'all',
					'future'  => 'off',
					'title'   => FALSE,
					'wrap'    => FALSE,
				]
			);

		return '';
	}

	public function template_get_archive_content_default()
	{
		return '';
	}

	// DEFAULT METHOD: button for overrided empty/archive page
	public function template_get_add_new( $posttype, $title = FALSE, $label = NULL )
	{
		$object = PostType::object( $posttype );

		if ( ! current_user_can( $object->cap->create_posts ) )
			return '';

		// FIXME: must check if post is unpublished

		return HTML::tag( 'a', [
			'href'          => WordPress::getPostNewLink( $object->name, [ 'post_title' => $title ] ),
			'class'         => [ 'button', '-add-posttype', '-add-posttype-'.$object->name ],
			'target'        => '_blank',
			'data-posttype' => $object->name,
		], $label ?: $object->labels->add_new_item );
	}

	// DEFAULT FILTER
	public function template_empty_content( $content )
	{
		if ( ! $post = Post::get() )
			return $content;

		$title = $this->template_get_empty_title( '' );
		$html  = $this->template_get_empty_content();
		$html .= $this->get_search_form( [ 'post_type[]' => $post->post_type ], $title );

		// TODO: list other entries that linked to this title via content

		if ( $add_new = $this->template_get_add_new( $post->post_type, $title ) )
			$html.= '<p class="-actions">'.$add_new.'</p>';

		return HTML::wrap( $html, $this->base.'-empty-content' );
	}

	// DEFAULT FILTER
	public function template_archive_content( $content )
	{
		return HTML::wrap( $this->template_get_archive_content(), $this->base.'-archive-content' );
	}

	public function tool_box()
	{
		echo $this->wrap_open( [ 'card', '-toolbox-card' ] );
			$this->tool_box_title();

			if ( FALSE !== $this->tool_box_content() ) {

				$links = [];

				foreach ( $this->get_module_links() as $link )
					$links[] = HTML::tag( 'a' , [
						'href'  => $link['url'],
						'class' => [ 'button', '-button' ],
					], $link['title'] );

				echo HTML::wrap( HTML::renderList( $links ), '-toolbox-links' );
			}

		echo '</div>';
	}

	// DEFAULT CALLBACK: use in module for descriptions
	// protected function tool_box_content() {}

	// DEFAULT CALLBACK
	protected function tool_box_title()
	{
		HTML::h2( sprintf(
			/* translators: %s: module title */
			_x( 'Editorial: %s', 'Module', 'geditorial' ),
			$this->module->title
		), 'title' );
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

	protected function _hook_wp_submenu_page( $context, $parent_slug, $page_title, $menu_title = NULL, $capability = NULL, $menu_slug = '', $callback = '', $position = NULL )
	{
		if ( ! $context )
			return FALSE;

		$default_callback = [ $this, sprintf( 'admin_%s_page', $context ) ];

		$hook = add_submenu_page(
			$parent_slug,
			$page_title,
			( is_null( $menu_title ) ? $page_title : $menu_title ),
			( is_null( $capability ) ? ( isset( $this->caps[$context] ) ? $this->caps[$context] : 'manage_options' ) : $capability ),
			( empty( $menu_slug ) ? sprintf( '%s-%s', $this->base, $context ) : $menu_slug ),
			( empty( $callback ) ? ( is_callable( $default_callback ) ? $default_callback : '' ) : $callback ),
			( is_null( $position ) ? ( isset( $this->positions[$context] ) ? $this->positions[$context] : NULL ) : $position )
		);

		if ( $hook )
			add_action( 'load-'.$hook, [ $this, sprintf( 'admin_%s_load', $context ) ] );

		return $hook;
	}
}
