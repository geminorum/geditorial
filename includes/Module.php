<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Module extends WordPress\Module
{
	use Internals\Assets;
	use Internals\CoreConstants;
	use Internals\CoreIncludes;
	use Internals\CorePostTypes;
	use Internals\CoreRoles;
	use Internals\CoreTaxonomies;
	use Internals\CoreComments;
	use Internals\DefaultTerms;
	use Internals\ModuleLinks;
	use Internals\SettingsCore;
	use Internals\SettingsFields;
	use Internals\SettingsHelp;
	use Internals\SettingsPostTypes;
	use Internals\SettingsRoles;
	use Internals\SettingsTaxonomies;
	use Internals\ShortCodes;
	use Internals\Strings;

	public $module;
	public $options;
	public $settings;

	public $enabled  = FALSE;
	public $meta_key = '_ge'; // TODO: DEPRECATE

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

	protected $screens   = []; // screen-id by context/constant
	protected $positions = []; // menu positions by context/constant
	protected $deafults  = []; // default settings

	protected $constants = [];
	protected $strings   = [];
	protected $features  = [];
	protected $fields    = [];

	protected $partials         = [];
	protected $partials_remote  = [];
	protected $process_disabled = [];

	protected $disable_no_customs    = FALSE; // Avoids hooking module if has no post-types/taxonomies
	protected $disable_no_posttypes  = FALSE; // Avoids hooking module if has no post-types
	protected $disable_no_taxonomies = FALSE; // Avoids hooking module if has no taxonomies

	protected $keep_posttypes  = [];  // keeps from excludes
	protected $keep_taxonomies = [];  // keeps from excludes
	protected $keep_roles      = [];  // keeps from excludes

	protected $image_sizes  = [];
	protected $kses_allowed = [];

	protected $scripts = [];
	protected $buttons = [];
	protected $errors  = [];
	protected $cache   = [];

	protected $caps = [
		'default'   => 'manage_options',
		'settings'  => 'manage_options',
		'imports'   => 'import',
		'customs'   => 'edit_theme_options',
		'reports'   => 'edit_others_posts',    // also used for `exports`
		'tools'     => 'edit_others_posts',
		'roles'     => 'edit_users',
		'adminbar'  => 'edit_others_posts',
		'dashboard' => 'edit_others_posts',
		'uploads'   => 'upload_files',

		// 'paired_create' => 'manage_options', // to restrict main post
		// 'paired_delete' => 'manage_options', // to restrict main post
	];

	protected $root_key = FALSE; // ROOT CONSTANT
	protected $_p2p     = FALSE; // P2P ENABLED/Connection Type
	protected $_o2o     = FALSE; // O2O ENABLED/Connection Type
	protected $_paired  = FALSE; // PAIRED API ENABLED/taxonomy paired

	protected $scripts_printed = FALSE;
	protected $current_queried = NULL; // usually contains `get_queried_object_id()`

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
		global $wp_textdomain_registry;

		if ( FALSE === $this->module->i18n )
			return FALSE;

		if ( 'adminonly' === $this->module->i18n && ! is_admin() )
			return FALSE;

		if ( 'restonly' === $this->module->i18n && ! WordPress\IsIt::rest() )
			return FALSE;

		if ( 'frontonly' === $this->module->i18n && is_admin() )
			return FALSE;

		$domain = $domain ?? $this->get_textdomain();

		if ( ! $domain )
			return FALSE;

		$locale = $locale ?? apply_filters( 'plugin_locale', Core\L10n::locale(), $this->base );

		$path = GEDITORIAL_DIR."languages/{$this->module->folder}";
		$wp_textdomain_registry->set_custom_path( $domain, $path );

		return load_textdomain( $domain, $path."/{$locale}.mo" );
	}

	protected function setup_remote( $args = [] )
	{
		$this->require_code( $this->partials_remote );
	}

	protected function setup( $args = [] )
	{
		$admin = is_admin();
		$ajax  = WordPress\IsIt::ajax();
		$ui    = WordPress\Screen::mustRegisterUI( FALSE );

		if ( $admin && $ui && ( TRUE === $this->module->configure || 'settings' === $this->module->configure ) )
			add_action( $this->hook_base( 'settings_load' ), [ $this, 'register_settings' ] );

		if ( $this->setup_disabled() )
			return FALSE;

		$this->require_code( $this->partials );

		if ( method_exists( $this, 'plugin_loaded' ) )
			add_action( $this->hook_base( 'loaded' ), [ $this, 'plugin_loaded' ] );

		if ( method_exists( $this, 'o2o_init' ) )
			$this->action( 'o2o_init' ); // NOTE: runs on `wp_loaded`

		else if ( method_exists( $this, 'p2p_init' ) )
			$this->action( 'p2p_init' ); // NOTE: runs on `wp_loaded`

		if ( method_exists( $this, 'widgets_init' ) && $this->get_setting( 'widget_support' ) )
			$this->action( 'widgets_init' );

		if ( ! $ajax && method_exists( $this, 'tinymce_strings' ) )
			add_filter( $this->hook_base( 'tinymce_strings' ), [ $this, 'tinymce_strings' ] );

		if ( method_exists( $this, 'meta_init' ) )
			add_action( $this->hook_base( 'meta_init' ), [ $this, 'meta_init' ], 10, 2 );

		if ( method_exists( $this, 'units_init' ) )
			add_action( $this->hook_base( 'units_init' ), [ $this, 'units_init' ], 10, 2 );

		if ( method_exists( $this, 'terms_init' ) )
			add_action( $this->hook_base( 'terms_init' ), [ $this, 'terms_init' ], 10, 2 );

		if ( method_exists( $this, 'importer_init' ) )
			add_action( $this->hook_base( 'importer_init' ), [ $this, 'importer_init' ], 10, 2 );

		if ( method_exists( $this, 'elementor_register' ) )
			add_action( 'elementor/widgets/register', [ $this, 'elementor_register' ], 10, 1 );

		add_action( 'after_setup_theme', [ $this, '_after_setup_theme' ], 1 );
		add_action( 'rest_api_init', [ $this, '_rest_api_init' ], $this->priority_restapi_init );

		if ( method_exists( $this, 'after_setup_theme' ) )
			$this->action( 'after_setup_theme', 0, 20 );

		$this->action( 'init', 0, $this->priority_init );

		if ( $ui && method_exists( $this, 'adminbar_init' ) && $this->get_setting( 'adminbar_summary' ) )
			add_action( $this->hook_base( 'adminbar' ), [ $this, 'adminbar_init' ], $this->priority_adminbar_init, 2 );

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
				add_action( $this->hook_base( 'reports_settings' ), [ $this, 'reports_settings' ] );

			if ( $ui && method_exists( $this, 'tools_settings' ) )
				add_action( $this->hook_base( 'tools_settings' ), [ $this, 'tools_settings' ] );

			if ( $ui && method_exists( $this, 'roles_settings' ) )
				add_action( $this->hook_base( 'roles_settings' ), [ $this, 'roles_settings' ] );

			if ( $ui && method_exists( $this, 'imports_settings' ) )
				add_action( $this->hook_base( 'imports_settings' ), [ $this, 'imports_settings' ] );

			if ( $ui && method_exists( $this, 'customs_settings' ) )
				add_action( $this->hook_base( 'customs_settings' ), [ $this, 'customs_settings' ] );

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

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return $this->_cuc( $context, $fallback );
	}

	// NOTE: to prevent infinite loops!
	public function _cuc( $context = 'settings', $fallback = '' )
	{
		if ( ! empty( $this->caps[$context] ) )
			return current_user_can( $this->caps[$context] );

		if ( current_user_can( sprintf( 'editorial_%s', $context ) ) )
			return TRUE;

		if ( ! empty( $this->caps['default'] ) )
			return current_user_can( $this->caps['default'] );

		else if ( $fallback )
			return current_user_can( $fallback );

		return FALSE;
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
		$this->constants = $this->filters( 'constants', $this->_get_global_constants(), $this->module );
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

		$this->init_default_terms();

		$prefix   = Core\Text::sanitizeBase( $this->key );
		$callback = static function ( $key, $value ) use ( $prefix ) {
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

	// NOTE: do not use `thick-box` anymore!
	// NOTE: must `add_thickbox()` on load
	public function do_render_thickbox_mainbutton( $post, $context = 'framepage', $extra = [], $inline = FALSE, $width = '800' )
	{
		// NOTE: for inline only: modal id must be: `{$base}-{$module}-thickbox-{$context}`
		if ( $inline && $context && method_exists( $this, 'admin_footer_'.$context ) )
			$this->action( 'admin_footer', 0, 20, $context );

		$name  = Services\CustomPostType::getLabel( $post->post_type, 'singular_name' );
		$title = $this->get_string( 'mainbutton_title', $context ?: $post->post_type, 'metabox', NULL );
		$text  = $this->get_string( 'mainbutton_text', $context ?: $post->post_type, 'metabox', $name );

		if ( $inline )
			// WTF: thick-box bug: does not process the query arguments after `TB_inline`!
			$link = '#TB_inline?dummy=dummy&width='.$width.'&inlineId='.$this->classs( 'thickbox', $context ).( $extra ? '&'.http_build_query( $extra ) : '' ); // &modal=true
		else
			// WTF: thick-box bug: does not pass the query arguments after `TB_iframe`!
			$link = $this->get_adminpage_url( TRUE, array_merge( [
				'linked'   => $post->ID,
				'noheader' => 1,
				'width'    => $width,
			], $extra, [ 'TB_iframe' => 'true' ] ), $context );

		$html = Core\HTML::tag( 'a', [
			'href'  => $link,
			'id'    => $this->classs( 'mainbutton', $context ),
			'class' => Core\HTML::buttonClass( FALSE, [ '-button-full', '-button-icon', '-mainbutton', 'thickbox' ] ),
			'title' => $title ? sprintf( $title, WordPress\Post::title( $post, $name ), $name ) : FALSE,
		], sprintf( $text, Services\Icons::get( $this->module->icon ), $name ) );

		echo Core\HTML::wrap( $html, 'field-wrap -buttons' );
	}

	/**
	 * Checks if this module loaded as remote
	 * for editorial module on another site.
	 *
	 * @return bool
	 */
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

	public function slug()
	{
		return str_replace( '_', '-', $this->module->name );
	}

	/**
	 * Updates module options upon out-of-context manipulations.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return bool
	 */
	public function update_option( $key, $value )
	{
		return gEditorial()->update_module_option( $this->module->name, $key, $value );
	}

	// NOTE: better to be on module-core than the internal
	public function default_calendar( $default = NULL )
	{
		return $this->get_setting( 'calendar_type', $default ?? Core\L10n::calendar() );
	}

	public function get_search_form( $constant_or_hidden = [], $search_query = FALSE )
	{
		if ( ! $this->get_setting( 'display_searchform' ) )
			return '';

		if ( $search_query )
			add_filter( 'get_search_query',
				static function ( $query ) use ( $search_query ) {
					return $query ? $query : $search_query;
				} );

		$form = get_search_form( FALSE );

		if ( $constant_or_hidden && ! is_array( $constant_or_hidden ) )
			$constant_or_hidden = [ 'post_type[]' => $this->constant( $constant_or_hidden ) ];

		if ( ! $constant_or_hidden || ! count( $constant_or_hidden ) )
			return $form;

		$form = str_replace( '</form>', '', $form );

		foreach ( $constant_or_hidden as $name => $value )
			$form.= '<input type="hidden" name="'.Core\HTML::escape( $name ).'" value="'.Core\HTML::escape( $value ).'" />';

		return $form.'</form>';
	}

	protected function _metabox_remove_subterm( $screen, $subterms = FALSE )
	{
		if ( $subterms )
			remove_meta_box( $subterms.'div', $screen->post_type, 'side' );
	}

	protected function _hook_store_metabox( $posttype, $prefix = FALSE )
	{
		if ( $posttype )
			add_action(
				sprintf( 'save_post_%s', $posttype ),
				[ $this, 'store_metabox'.( $prefix ? '_'.$prefix : '' ) ],
				20,
				3
			);
	}

	protected function class_metabox( $screen, $context = 'mainbox' )
	{
		add_filter( 'postbox_classes_'.$screen->id.'_'.$this->classs( $context ),
			function ( $classes ) use ( $context ) {
				return Core\Arraay::prepString( $classes, [
					$this->base.'-wrap',
					'-admin-postbox',
					'-'.$this->key,
					'-'.$this->key.'-'.$context,
				] );
			} );
	}

	// TODO: filter the results
	// TODO: MUST DEPRECATE
	public function get_meta_box_title( $constant = 'post', $url = NULL, $edit_cap = NULL, $title = NULL )
	{
		if ( is_null( $title ) )
			$title = $this->get_string( 'metabox_title', $constant, 'metabox', NULL );

		// DEPRECATED: for back-comp only
		if ( is_null( $title ) )
			$title = $this->get_string( 'meta_box_title', $constant, 'misc', _x( 'Settings', 'Module: MetaBox Default Title', 'geditorial-admin' ) );

		return $title; // <-- // FIXME: problems with block editor

		// TODO: 'metabox_icon'
		if ( $info = $this->get_string( 'metabox_info', $constant, 'metabox', NULL ) )
			$title.= WordPress\MetaBox::markupTitleHelp( $info );

		if ( FALSE === $url || FALSE === $edit_cap )
			return $title;

		if ( is_null( $edit_cap ) )
			$edit_cap = $this->caps['settings'] ?? 'manage_options';

		if ( TRUE === $edit_cap || current_user_can( $edit_cap ) ) {

			if ( is_null( $url ) )
				$url = $this->get_module_url( 'settings' );

			$action = $this->get_string( 'metabox_action', $constant, 'metabox', _x( 'Configure', 'Module: MetaBox Default Action', 'geditorial-admin' ) );
			$title.= ' <span class="postbox-title-action"><a href="'.esc_url( $url ).'" target="_blank">'.$action.'</a></span>';
		}

		return $title;
	}

	public function get_column_title( $column, $constant = NULL, $fallback = NULL )
	{
		return $this->filters( 'column_title',
			$this->get_string(
				sprintf( '%s_column_title', $column ),
				$constant,
				'misc',
				$fallback ?? $column
			),
			$column,
			$constant,
			$fallback
		);
	}

	public function get_column_title_posttype( $constant, $taxonomy = FALSE, $fallback = NULL )
	{
		$title = Services\CustomPostType::getLabel( $this->constant( $constant ), 'column_title', 'name', $fallback );
		return $this->filters( 'column_title', $title, $taxonomy, $constant, $fallback );
	}

	public function get_column_title_taxonomy( $constant, $posttype = FALSE, $fallback = NULL )
	{
		$title = Services\CustomTaxonomy::getLabel( $this->constant( $constant ), 'column_title', 'name', $fallback );
		return $this->filters( 'column_title', $title, $posttype, $constant, $fallback );
	}

	public function get_column_title_icon( $column, $constant = NULL, $fallback = NULL )
	{
		$title = $this->get_column_title( $column, $constant, $fallback );
		return sprintf( '<span class="-column-icon %3$s" title="%2$s">%1$s</span>', $title, esc_attr( $title ), $this->classs( $column ) );
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

	// NOTE: for Ajax calls on quick-edit
	public function is_inline_save_posttype( $target = FALSE, $request = NULL, $key = 'post_type' )
	{
		if ( ! WordPress\IsIt::ajaxAdmin() )
			return FALSE;

		if ( is_null( $request ) )
			$request = $_REQUEST;

		if ( empty( $request['bulk_edit'] )
			&& ( empty( $request['action'] ) || 'inline-save' != $request['action'] ) )
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

	// NOTE: for Ajax calls on quick-edit
	public function is_inline_save_taxonomy( $target = FALSE, $request = NULL, $key = 'taxonomy' )
	{
		if ( ! WordPress\IsIt::ajaxAdmin() )
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

	public function get_column_icon( $link = FALSE, $icon = NULL, $title = NULL, $posttype = 'post', $extra = [] )
	{
		$icon  = $icon  ?? $this->module->icon;
		$title = $title ?? $this->get_string( 'column_icon_title', $posttype, 'misc', '' );

		return Core\HTML::tag( ( $link ? 'a' : 'span' ), [
			'href'   => $link ?: FALSE,
			'title'  => $title ?: FALSE,
			'class'  => array_merge( [ '-icon', ( $link ? '-link' : '-info' ) ], (array) $extra ),
			'target' => $link ? '_blank' : FALSE,
		], Services\Icons::get( $icon ) );
	}

	// NOTE: adds the `{$module_key}-enabled` class to body in admin
	public function _admin_enabled( $extra = [] )
	{
		add_filter( 'admin_body_class',
			function ( $classes ) use ( $extra ) {
				return trim( $classes ).' '.Core\HTML::prepClass( $this->classs( 'enabled' ), $extra );
			} );
	}

	// Should we insert content?
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

		add_action( $this->hook_base( 'content', $insert ),
			[ $this, 'insert_content' ],
			$this->get_setting( 'insert_priority', $default_priority )
		);

		return TRUE;
	}

	public function icon( $name, $group = NULL )
	{
		return gEditorial()->icon( $name, ( is_null( $group ) ? $this->icon_group : $group ) );
	}

	// Checks to bail early if meta-box/widget is hidden
	protected function check_hidden_metabox( $box, $posttype = FALSE, $after = '' )
	{
		if ( FALSE === $box )
			return FALSE;

		// NOTE: argument order changed
		return MetaBox::checkHidden(
			empty( $box['id'] ) ? $this->classs( $box ) : $box['id'],
			$after,
			$posttype
		);
	}

	// Checks to bail early if column is hidden
	protected function check_hidden_column( $column, $after = '' )
	{
		if ( FALSE === $column )
			return FALSE;

		return Listtable::checkHidden(
			$column ?? $this->classs(),
			$after
		);
	}

	// DEFAULT METHOD
	// NOTE: must be available to all modules
	public function prep_meta_row( $value, $field_key = NULL, $field = [], $raw = NULL )
	{
		if ( ! empty( $field['prep'] ) && is_callable( $field['prep'] ) )
			return call_user_func_array( $field['prep'], [ $value, $field_key, $field, $raw ] );

		if ( method_exists( $this, 'prep_meta_row_module' ) ) {

			$prepped = $this->prep_meta_row_module( $value, $field_key, $field, $raw );

			if ( $prepped !== $value )
				return $prepped; // bail if already prepped
		}

		return Services\PostTypeFields::prepFieldRow( $value, $field_key, $field, $raw, 'meta' );
	}

	// TODO: customize column position/sorting
	// NOTE: appends custom meta fields into Terms Module
	// @SEE: `Socialite` Module
	protected function _hook_terms_meta_field( $constant, $field, $args = [] )
	{
		if ( ! gEditorial()->enabled( 'terms' ) )
			return FALSE;

		$taxonomy = $this->constant( $constant );
		$title    = $this->get_string( 'field_title', $field, 'terms_meta_field', $field );
		$desc     = $this->get_string( 'field_desc', $field, 'terms_meta_field', '' );

		add_filter( $this->hook_base( 'terms', 'supported_fields' ),
			static function ( $list, $tax ) use ( $taxonomy, $field ) {

				if ( FALSE === $tax || $tax === $taxonomy )
					$list[] = $field;

				return $list;
			}, 12, 2 );

		add_filter( $this->hook_base( 'terms', 'list_supported_fields' ),
			static function ( $list, $tax ) use ( $taxonomy, $field, $title ) {

				if ( FALSE === $tax || $tax === $taxonomy )
					$list[$field] = $title;

				return $list;
			}, 12, 2 );

		add_filter( $this->hook_base( 'terms', 'supported_field_taxonomies' ),
			static function ( $taxonomies, $_field ) use ( $taxonomy, $field ) {

				if ( $_field === $field )
					$taxonomies[] = $taxonomy;

				return $taxonomies;
			}, 12, 2 );

		if ( ! is_admin() )
			return;

		$this->filter_string( $this->hook_base( 'terms', 'field', $field, 'title' ), $title );
		$this->filter_string( $this->hook_base( 'terms', 'field', $field, 'desc' ), $desc );

		add_filter( $this->hook_base( 'terms', 'column_title' ),
			static function ( $_title, $column, $constant, $fallback ) use ( $taxonomy, $field, $title ) {

				if ( $column === $field )
					return $title;

				return $_title;
			}, 12, 4 );
	}

	protected function log( $level, $message = '', $context = [] )
	{
		return Helper::log( $message, $this->classs(), $level, $context );
	}

	protected function raise_resources( $count = 1, $per = 60, $context = NULL )
	{
		gEditorial()->disable_process( 'audit', $context ?? 'import' );
		gEditorial()->disable_process( 'personage', 'aftercare' );
		gEditorial()->disable_process( 'was_born', 'aftercare' );

		WordPress\Media::disableThumbnailGeneration();
		WordPress\Taxonomy::disableTermCounting();
		Services\LateChores::termCountCollect();
		wp_defer_comment_counting( TRUE );

		if ( ! WordPress\IsIt::dev() )
			do_action( 'qm/cease' ); // Query-Monitor: Cease data collections

		return $this->raise_memory_limit( $count, $per, $context ?? 'import' );
	}

	public function disable_process( $context = 'import' )
	{
		return $this->process_disabled[$context] = TRUE;
	}

	public function enable_process( $context = 'import' )
	{
		return $this->process_disabled[$context] = FALSE;
	}

	public function is_thrift_mode()
	{
		if ( self::const( 'GEDITORIAL_THRIFT_MODE' ) )
			return TRUE;

		return $this->get_setting( 'thrift_mode', FALSE );
	}

	public function is_debug_mode()
	{
		if ( self::const( 'GEDITORIAL_DEBUG_MODE' ) )
			return TRUE;

		return $this->get_setting( 'debug_mode', FALSE );
	}
}
