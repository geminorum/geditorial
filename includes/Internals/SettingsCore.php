<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait SettingsCore
{
	protected function settings_insert_priority_option( $default = 10, $prefix = FALSE )
	{
		return [
			'field'   => 'insert_priority'.( $prefix ? '_'.$prefix : '' ),
			'type'    => 'priority',
			'title'   => _x( 'Insert Priority', 'Settings: Setting Title', 'geditorial-admin' ),
			'default' => $default,
		];
	}

	protected function settings_shortcode_constant( $constant, $title = NULL, $extended = NULL )
	{
		$unfiltered = $this->get_global_constants();
		$name       = $title ?? str_ireplace( '_', ' ', trim( Core\Text::removeStartEnd( $constant, 'shortcode' ), '_' ) );

		return [
			'field' => sprintf( '%s_constant', $constant ),
			'type'  => 'text',
			'title' => sprintf(
				/* translators: `%s`: short-code name */
				_x( '%s Shortcode', 'Settings: Setting Title', 'geditorial-admin' ),
				$title ?? Core\Text::titleCase( $name )
			),
			'description' => sprintf(
				/* translators: `%s`: short-code name extended */
				_x( 'Customizes the %s short-code tag. Leave blank for default.', 'Settings: Setting Description', 'geditorial-admin' ),
				Core\HTML::strong( $extended ?? Core\Text::strToLower( $name ) )
			),
			'after'       => gEditorial\Settings::fieldAfterShortCodeConstant(),
			'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
			'placeholder' => $unfiltered[$constant],
			'field_class' => [
				'medium-text',
				'code-text',
			],
		];
	}

	// NOTE: features are `TRUE` by default
	public function get_feature( $field, $fallback = TRUE )
	{
		$settings = $this->options->settings ?? [];

		if ( array_key_exists( $field, $settings ) )
			return $settings[$field];

		if ( array_key_exists( $field, $this->features ) )
			return $this->features[$field];

		return $fallback;
	}

	public function get_setting( $field, $fallback = NULL )
	{
		$settings = $this->options->settings ?? [];

		if ( array_key_exists( $field, $settings ) )
			return $settings[$field];

		if ( array_key_exists( $field, $this->deafults ) )
			return $this->deafults[$field];

		return $fallback;
	}

	public function get_setting_fallback( $field, $fallback, $empty = '' )
	{
		$settings = $this->options->settings ?? [];

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

	// Checks arrays with support of old settings
	public function in_setting( $item, $field, $default = [] )
	{
		$setting = $this->get_setting( $field );

		if ( FALSE === $setting || TRUE === $setting )
			return $setting;

		if ( is_null( $setting ) )
			$setting = $default;

		return in_array( $item, (array) $setting, TRUE );
	}

	public function settings_from()
	{
		echo '<form class="'.$this->base.'-form -form -'.$this->module->name
			.'" action="'.$this->get_module_url( 'settings' ).'" method="post">';

			$this->render_form_fields( $this->module->name );

			gEditorial\Settings::moduleSections( $this->hook_base( $this->module->name ) );

			echo '<input id="geditorial_module_name" name="geditorial_module_name" type="hidden" value="'.Core\HTML::escape( $this->module->name ).'" />';

			$this->render_form_buttons();

		echo '</form>';

		if ( WordPress\IsIt::dev() )
			echo Core\HTML::wrap( self::dump( $this->options, TRUE, FALSE ), '-debug-wrap' );
	}

	public function register_settings_default_buttons( $module = FALSE )
	{
		$this->register_button( 'submit', NULL, TRUE );
		$this->register_button( 'reset', NULL, 'reset', TRUE );

		if ( ! $this->module->autoload )
			$this->register_button( 'disable', _x( 'Disable Module', 'Module: Button', 'geditorial-admin' ), 'danger' );

		foreach ( $this->get_module_links() as $link )
			if ( ! empty( $link['context'] ) && in_array( $link['context'], [ 'tools', 'reports', 'imports', 'customs', 'listtable' ], TRUE ) )
				$this->register_button( $link['url'], $link['title'], 'link' );

		$this->register_settings_extra_buttons( $module );
	}

	public function register_button( $key, $value = NULL, $type = FALSE, $atts = [] )
	{
		$this->buttons[] = [
			'key'   => $key,
			'value' => $value ?? $this->get_string( $key, 'buttons', 'settings', NULL ),
			'type'  => $type,
			'atts'  => $atts,
		];
	}

	protected function render_form_buttons( $module = FALSE, $wrap = '', $buttons = NULL )
	{
		if ( FALSE !== $wrap )
			echo $this->wrap_open_buttons( $wrap );

		foreach ( $buttons ?? $this->buttons as $button )
			gEditorial\Settings::submitButton(
				$button['key'],
				$button['value'],
				$button['type'],
				$button['atts']
			);

		if ( FALSE !== $wrap )
			echo '</p>';
	}

	protected function render_form_start( $uri, $sub = NULL, $action = 'update', $context = 'settings', $check = FALSE )
	{
		$sub   = $sub ?? $this->module->name;
		$class = [
			$this->base.'-form',
			'-form',
			'-'.$this->module->name,
			'-sub-'.$sub,
		];

		if ( $check && $sidebox = method_exists( $this, $context.'_sidebox' ) )
			$class[] = 'has-sidebox';

		echo '<form enctype="multipart/form-data" class="'.Core\HTML::prepClass( $class ).'" method="post" action="">';

			if ( in_array( $context, [ 'settings', 'tools', 'reports', 'imports', 'customs' ], TRUE ) )
				$this->render_form_fields( $sub, $action, $context );

			if ( $check && $sidebox ) {
				echo '<div class="'.Core\HTML::prepClass( '-sidebox', '-'.$this->module->name, '-sidebox-'.$sub ).'">';
					call_user_func_array( [ $this, $context.'_sidebox' ], [ $sub, $uri, $context ] );
				echo '</div>';
			}
	}

	protected function render_form_end( $uri, $sub = NULL, $action = 'update', $context = 'settings', $check = FALSE )
	{
		echo '</form>';
	}

	// DEFAULT METHOD: tools sub html
	public function tools_sub( $uri, $sub )
	{
		$this->render_form_start( $uri, $sub, 'bulk', 'tools', TRUE );

			if ( FALSE === $this->render_tools_html_before( $uri, $sub ) )
				return $this->render_form_end( $uri, $sub ); // bail if explicitly FALSE

			if ( $this->render_tools_html( $uri, $sub ) )
				$this->render_form_buttons();

			$this->render_tools_html_after( $uri, $sub );

		$this->render_form_end( $uri, $sub );
	}

	// DEFAULT METHOD: used for tools default sub html
	protected function render_tools_html( $uri, $sub ) {}
	protected function render_tools_html_before( $uri, $sub ) {}
	protected function render_tools_html_after( $uri, $sub ) {}

	// DEFAULT METHOD: roles sub html
	public function roles_sub( $uri, $sub )
	{
		$this->render_form_start( $uri, $sub, 'bulk', 'roles', TRUE );

			if ( FALSE === $this->render_roles_html_before( $uri, $sub ) )
				return $this->render_form_end( $uri, $sub ); // bail if explicitly FALSE

			if ( $this->render_roles_html( $uri, $sub ) )
				$this->render_form_buttons();

			$this->render_roles_html_after( $uri, $sub );

		$this->render_form_end( $uri, $sub );
	}

	// DEFAULT METHOD: used for roles default sub html
	protected function render_roles_html( $uri, $sub ) {}
	protected function render_roles_html_before( $uri, $sub ) {}
	protected function render_roles_html_after( $uri, $sub ) {}

	// DEFAULT METHOD: reports sub html
	public function reports_sub( $uri, $sub )
	{
		$this->render_form_start( $uri, $sub, 'bulk', 'reports', TRUE );

			if ( FALSE === $this->render_reports_html_before( $uri, $sub ) )
				return $this->render_form_end( $uri, $sub ); // bail if explicitly FALSE

			if ( $this->render_reports_html( $uri, $sub ) )
				$this->render_form_buttons();

			$this->render_reports_html_after( $uri, $sub );

		$this->render_form_end( $uri, $sub );
	}

	// DEFAULT METHOD: used for reports default sub html
	protected function render_reports_html( $uri, $sub ) {}
	protected function render_reports_html_before( $uri, $sub ) {}
	protected function render_reports_html_after( $uri, $sub ) {}

	// DEFAULT METHOD: imports sub html
	public function imports_sub( $uri, $sub )
	{
		$this->render_form_start( $uri, $sub, 'bulk', 'imports', TRUE );

			if ( FALSE === $this->render_imports_html_before( $uri, $sub ) )
				return $this->render_form_end( $uri, $sub ); // bail if explicitly FALSE

			if ( $this->render_imports_html( $uri, $sub ) )
				$this->render_form_buttons();

			$this->render_imports_html_after( $uri, $sub );

		$this->render_form_end( $uri, $sub );
	}

	// DEFAULT METHOD: used for imports default sub html
	protected function render_imports_html( $uri, $sub ) {}
	protected function render_imports_html_before( $uri, $sub ) {}
	protected function render_imports_html_after( $uri, $sub ) {}

	// DEFAULT METHOD: customs sub html
	public function customs_sub( $uri, $sub )
	{
		$this->render_form_start( $uri, $sub, 'bulk', 'customs', TRUE );

			if ( FALSE === $this->render_customs_html_before( $uri, $sub ) )
				return $this->render_form_end( $uri, $sub ); // bail if explicitly FALSE

			if ( $this->render_customs_html( $uri, $sub ) )
				$this->render_form_buttons();

			$this->render_customs_html_after( $uri, $sub );

		$this->render_form_end( $uri, $sub );
	}

	// DEFAULT METHOD: used for customs default sub html
	protected function render_customs_html( $uri, $sub ) {}
	protected function render_customs_html_before( $uri, $sub ) {}
	protected function render_customs_html_after( $uri, $sub ) {}

	protected function get_current_form( $defaults, $context = 'settings' )
	{
		$req = empty( $_REQUEST[$this->hook_base( $this->module->name )][$context] )
			? []
			: $_REQUEST[$this->hook_base( $this->module->name )][$context];

		return self::atts( $defaults, $req );
	}

	protected function fields_current_form( $fields, $context = 'settings', $excludes = [] )
	{
		foreach ( $fields as $key => $value ) {

			if ( in_array( $key, $excludes ) )
				continue;

			Core\HTML::inputHidden( $this->hook_base( $this->module->name ).'['.$context.']['.$key.']', $value );
		}
	}

	protected function render_form_fields( $sub, $action = 'update', $context = 'settings' )
	{
		Core\HTML::inputHidden( 'base', $this->base );
		Core\HTML::inputHidden( 'key', $this->key );
		Core\HTML::inputHidden( 'context', $context );
		Core\HTML::inputHidden( 'sub', $sub );
		Core\HTML::inputHidden( 'action', $action );

		WordPress\Redirect::fieldReferer();
		$this->nonce_field( $context, $sub );
	}

	// DEFAULT METHOD
	// NOTE: `$extra` argument is for extending in modules.
	public function append_sub( $subs, $context = 'settings', $extra = [] )
	{
		if ( ! $this->cuc( $context ) )
			return $subs;

		return array_merge( $subs, [ $this->module->name => [
			'title' => $this->module->title,
			'hint'  => $this->module->desc,
			'icon'  => Services\Icons::get( $this->module->icon ),
		] ], $extra );
	}

	public function settings_validate( $options, $context = 'settings' )
	{
		if ( 'settings' === $context ) {

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
		}

		if ( isset( $options[$context] ) ) {

			foreach ( (array) $options[$context] as $setting => $option ) {

				if ( FALSE === ( $args = $this->get_settings_field( $setting, $context ) ) )
					continue; // bailing!

				// skip disabled settings
				if ( array_key_exists( 'values', $args ) && FALSE === $args['values'] )
					continue;

				if ( ! array_key_exists( 'type', $args ) || 'enabled' == $args['type'] ) {

					$options[$context][$setting] = (bool) $option;

				} else if ( 'object' == $args['type'] ) {

					if ( empty( $option ) || ! is_array( $option ) || empty( $args['values'] ) ) {

						unset( $options[$context][$setting] );

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
							$options[$context][$setting] = $sanitized;

						else
							unset( $options[$context][$setting] );
					}

				} else if ( is_array( $option ) ) {

					if ( 'text' == $args['type'] ) {

						// multiple texts
						$options[$context][$setting] = [];

						foreach ( $option as $key => $value )
							if ( $string = trim( self::unslash( $value ) ) )
								$options[$context][$setting][sanitize_key( $key )] = $string;

					} else {

						// multiple checkboxes
						$options[$context][$setting] = array_keys( $option );
					}

				} else {

					$options[$context][$setting] = trim( self::unslash( $option ) );
				}
			}

			if ( ! count( $options[$context] ) )
				unset( $options[$context] );
		}

		return $options;
	}

	protected function get_settings_field( $setting, $context = 'settings' )
	{
		foreach ( $this->{$context} as $section ) {

			if ( is_array( $section ) ) {

				foreach ( $section as $key => $field ) {

					if ( FALSE === $field )
						continue;

					if ( is_string( $key ) && $setting == $key )
						return method_exists( gEditorial\Settings::class, 'getSetting_'.$key )
							? call_user_func_array( [ gEditorial\Settings::class, 'getSetting_'.$key ], (array) $field )
							: [];

					else if ( is_string( $field ) && $setting == $field )
						return method_exists( gEditorial\Settings::class, 'getSetting_'.$field )
							? call_user_func( [ gEditorial\Settings::class, 'getSetting_'.$field ] )
							: [];

					else if ( is_array( $field ) && isset( $field['field'] ) && $setting == $field['field'] )
						return $field;
				}
			}
		}

		return [];
	}

	protected function check_settings( $sub, $context = 'tools', $screen_option = FALSE, $extra = [], $key = NULL )
	{
		if ( ! $this->cuc( $context ) )
			return FALSE;

		if ( is_null( $key ) )
			$key = $this->key;

		if ( $key == $this->key )
			add_filter( $this->hook_base( $context, 'subs' ), [ $this, 'append_sub' ], 10, 2 );

		$subs = array_merge( [ $key ], (array) $extra );

		if ( ! in_array( $sub, $subs ) )
			return FALSE;

		foreach ( $subs as $supported )
			add_action( $this->hook_base( $context, 'sub', $supported ), [ $this, $context.'_sub' ], 10, 2 );

		if ( 'settings' != $context ) {

			$this->register_help_tabs( NULL, $context );
			$this->add_sub_screen_option( $sub, $context, $screen_option );

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

		if ( $this->nonce_verify( 'settings', NULL, $module ) )
			$this->handle_settings_extra_buttons( $module );

		add_action( 'admin_notices',
			function () use ( $module ) {
				$this->notice_settings_extra_buttons( $module );
			} );

		// NOTE: DEPRECATED
		if ( method_exists( $this, 'before_settings' ) )
			$this->before_settings( $module );

		foreach ( $this->settings as $section_suffix => $fields ) {

			if ( is_array( $fields ) ) {

				$section = $this->hook_base( $this->module->name ).$section_suffix;
				$title   = gEditorial\Settings::getModuleSectionTitle( $section_suffix );

				if ( ! $title && method_exists( $this, 'settings_section_titles' ) )
					$title = call_user_func_array( [ $this, 'settings_section_titles' ], [ $section_suffix ] );

				if ( method_exists( $this, 'settings_section'.$section_suffix ) )
					$callback = [ $this, 'settings_section'.$section_suffix ];
				else if ( method_exists( gEditorial\Settings::class, 'settings_section'.$section_suffix ) )
					$callback = [ gEditorial\Settings::class, 'settings_section'.$section_suffix ];
				else
					$callback = '__return_false';

				gEditorial\Settings::addModuleSection( $this->hook_base( $this->module->name ), [
					'id'            => $section,
					'callback'      => $callback,
					'title'         => empty( $title[0] ) ? gEditorial\Settings::makeModuleSectionTitle( $section_suffix ) : $title[0],
					'description'   => empty( $title[1] ) ? FALSE : $title[1],
					'section_class' => 'settings_section',
				] );

				foreach ( $fields as $key => $field ) {

					if ( FALSE === $field )
						continue;

					if ( is_string( $key ) && method_exists( gEditorial\Settings::class, 'getSetting_'.$key ) )
						$args = call_user_func_array( [ gEditorial\Settings::class, 'getSetting_'.$key ], (array) $field );

					else if ( is_string( $field ) && method_exists( gEditorial\Settings::class, 'getSetting_'.$field ) )
						$args = call_user_func( [ gEditorial\Settings::class, 'getSetting_'.$field ] );

					else if ( ! is_string( $key ) && is_array( $field ) )
						$args = $field;

					else
						continue;

					if ( FALSE === $args )
						continue; // bailing!

					$this->add_settings_field( array_merge( $args, [ 'section' => $section ] ) );
				}

			} else if ( method_exists( $this, 'register_settings_'.$section_suffix ) ) {

				$title = $section_suffix == $fields ? NULL : $fields;

				call_user_func_array( [ $this, 'register_settings_'.$section_suffix ], [ $title ] );
			}
		}

		$this->register_help_tabs();
		$this->register_settings_default_buttons( $module );

		// Registers settings on the settings page only.
		add_action( 'admin_print_footer_scripts', [ $this, 'settings_print_scripts' ], 99 );
	}

	public function settings_header()
	{
		$back = $count = $flush = $filters = FALSE;

		if ( 'config' == $this->module->name ) {

			$title   = NULL;
			$count   = gEditorial()->count();
			$flush   = WordPress\URL::maybeFlushRules();
			$filters = TRUE;

		} else {

			$back  = gEditorial\Settings::getURLbyContext( 'settings' );
			$title = sprintf(
				/* translators: `%1$s`: system title, `%2$s`: module title */
				_x( '%1$s: %2$s', 'Module', 'geditorial-admin' ),
				gEditorial\Plugin::system(),
				$this->module->title
			);
		}

		gEditorial\Settings::wrapOpen( $this->module->name );

			gEditorial\Settings::headerTitle( 'settings', $title, $back, NULL, $this->module->icon, $count, TRUE, $filters );
			gEditorial\Settings::message();

			if ( $flush )
				echo Core\HTML::warning( _x( 'You need to flush rewrite rules!', 'Module', 'geditorial-admin' ), FALSE );

			echo '<div class="-header">';
			Core\HTML::h4( $this->module->desc ?? '' );

			if ( method_exists( $this, 'settings_intro' ) )
				$this->settings_intro();

		gEditorial\Settings::wrapClose();
	}

	protected function settings_footer()
	{
		if ( 'config' == $this->module->name )
			gEditorial\Settings::settingsCredits();

		else
			$this->settings_signature( 'settings' );
	}

	protected function settings_signature( $context = 'settings' )
	{
		gEditorial\Settings::settingsSignature( $context );
	}

	public function add_settings_field( $r = [] )
	{
		$args = array_merge( [
			'page'        => $this->hook_base( $this->module->name ),
			'section'     => $this->hook_base( $this->module->name, 'general' ),
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
			'options'      => $this->options->settings ?? [],
			'option_base'  => $this->hook_base( $this->module->name ),
			'option_group' => 'settings',
			'id_name_cb'   => [ $this, 'settings_id_name_cb' ],
		], $atts );

		if ( empty( $args['cap'] ) )
			$args['cap'] = empty( $this->caps[$args['option_group']] ) ? NULL : $this->caps[$args['option_group']];

		gEditorial\Settings::fieldType( $args, $this->scripts );
	}

	public function settings_print_scripts()
	{
		if ( $this->scripts_printed )
			return;

		if ( count( $this->scripts ) )
			Core\HTML::wrapjQueryReady( implode( "\n", $this->scripts ) );

		$this->scripts_printed = TRUE;
	}

	/**
	 * Renders file upload field if upload checks passed.
	 *
	 * @ref `wp_import_handle_upload()`
	 * @ref `WordPress\Media::handleImportUpload()`
	 *
	 * @param string|array $mimes
	 * @param string $name
	 * @return false|int
	 */
	protected function settings_render_upload_field( $mimes, $name = 'import' )
	{
		$wpupload = WordPress\Media::upload();

		if ( ! empty( $wpupload['error'] ) ) {

			echo Core\HTML::error( sprintf(
				/* translators: `%s`: error message */
				_x( 'Before you can upload a file, you will need to fix the following error: %s', 'Internal: Settings Core: Message', 'geditorial-admin' ),
				Core\HTML::code( $wpupload['error'] )
			), FALSE, 'inline' );

			return FALSE;
		}

		$this->do_settings_field( [
			'type'      => 'file',
			'field'     => 'import_users_file',
			'name_attr' => $name,
			'cap'       => 'upload_files',
			'values'    => (array) $mimes,
		] );

		return Core\File::formatSize( apply_filters( 'import_upload_size_limit', wp_max_upload_size() ) );
	}

	protected function notice_settings_extra_buttons( $module ) {}
	protected function handle_settings_extra_buttons( $module ) {}
	protected function register_settings_extra_buttons( $module ) {}
}
