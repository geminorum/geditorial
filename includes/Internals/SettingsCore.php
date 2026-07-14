<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait SettingsCore
{
	protected function settings_insert_priority_option( int $default = 10, string $suffix = '' ): array
	{
		return [
			'field'   => self::und( 'insert_priority', $suffix ),
			'type'    => 'priority',
			'title'   => _x( 'Insert Priority', 'Settings: Setting Title', 'geditorial-admin' ),
			'default' => $default,
		];
	}

	protected function settings_shortcode_constant(
		string $constant,
		?string $title = NULL,
		?string $extended = NULL,
	): array {

		$unfiltered = $this->get_global_constants();
		$name       = $title ?? str_ireplace( '_', ' ', trim( Core\Text::removeStartEnd( $constant, 'shortcode' ), '_' ) );

		return [
			'field' => self::und( $constant, 'constant' ),
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
	public function get_feature( string $field, mixed $fallback = TRUE ): mixed
	{
		$settings = $this->options->settings ?? [];

		if ( array_key_exists( $field, $settings ) )
			return $settings[$field];

		if ( array_key_exists( $field, $this->features ) )
			return $this->features[$field];

		return $fallback;
	}

	public function get_setting( string $field, mixed $fallback = NULL ): mixed
	{
		$settings = $this->options->settings ?? [];

		if ( array_key_exists( $field, $settings ) )
			return $settings[$field];

		if ( array_key_exists( $field, $this->deafults ) )
			return $this->deafults[$field];

		return $fallback;
	}

	public function get_setting_fallback( string $field, mixed $fallback = NULL, mixed $empty = '' ): mixed
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

		if ( is_null( $fallback ) ) {

			$callback = [ gEditorial\Settings::class, self::und( 'getSetting', $field ) ];
			$setting  = is_callable( $callback ) ? call_user_func( $callback ) : [];

			if ( ! empty( $setting['default'] ) )
				$fallback = $setting['default'];

			else if ( ! empty( $setting['placeholder'] ) )
				$fallback = $setting['placeholder'];

			// keep the `NULL` as fallback
		}

		return $fallback;
	}

	// Checks arrays with support of old settings
	public function in_setting( string $item, string $field, array $default = [] ): bool
	{
		$setting = $this->get_setting( $field );

		if ( FALSE === $setting || TRUE === $setting )
			return $setting;

		$setting = $setting ?? $default;

		return in_array( $item, (array) $setting, TRUE );
	}

	public function settings_from(): void
	{
		$context = $context ?? 'settings';

		echo '<form class="'.$this->base.'-form -form -'.$this->module->name
			.'" action="'.$this->get_module_url( $context ).'" method="post">';

			$this->render_form_fields( $this->module->name );
			Core\HTML::inputHidden( self::und( $this->base, 'module', 'name' ), $this->module->name );

			Services\Modulation::renderSections( $this->hook_base( $this->module->name ) );

			$this->render_form_buttons( $context );

		echo '</form>';

		if ( WordPress\IsIt::dev() )
			echo Core\HTML::wrap( self::dump( $this->options, TRUE, FALSE ), '-debug-wrap' );
	}

	public function register_settings_default_buttons( ?string $module = NULL ): void
	{
		$this->register_button( 'submit', NULL, TRUE );
		$this->register_button( 'reset', NULL, 'reset', TRUE );

		if ( ! $this->module->autoload )
			$this->register_button( 'disable', _x( 'Disable Module', 'Module: Button', 'geditorial-admin' ), 'danger' );

		foreach ( $this->get_module_links( TRUE ) as $link )
			$this->register_button( $link['url'], $link['title'], 'link' );

		$this->register_settings_extra_buttons( $module ?? $this->module->name );
	}

	public function register_button(
		string $key,
		?string $value = NULL,
		false|string $type = FALSE,
		mixed $atts = [],
	): void {

		$this->buttons[] = [
			'key'   => $key,
			'value' => $value ?? $this->get_string( $key, 'buttons', 'settings', NULL ),
			'type'  => $type,
			'atts'  => $atts,
		];
	}

	protected function render_form_buttons( ?string $context = NULL, ?string $module = NULL, false|string $wrap = '', ?array $buttons = NULL )
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

	protected function render_form_start(
		string $uri = '',
		?string $sub = NULL,
		?string $action = NULL,
		?string $context = NULL,
		bool $check_sidebox = FALSE,
	): true {

		$sub     = $sub     ?? $this->module->name;
		$context = $context ?? 'settings';
		$action  = $action  ?? 'update';
		$class   = [
			$this->base.'-form',
			'-form',
			'-'.$this->module->name,
			'-sub-'.$sub,
		];

		$sidebox = $check_sidebox ? method_exists( $this, self::und( $context, 'sidebox' ) ) : FALSE;

		if ( $sidebox )
			$class[] = 'has-sidebox';

		echo '<form enctype="multipart/form-data" class="'.Core\HTML::prepClass( $class ).'" method="post" action="">';

			if ( in_array( $context, [ 'settings', 'tools', 'reports', 'imports', 'customs' ], TRUE ) )
				$this->render_form_fields( $sub, $action, $context );

			if ( $sidebox ) {
				echo '<div class="'.Core\HTML::prepClass( '-sidebox', '-'.$this->module->name, '-sidebox-'.$sub ).'">';
					call_user_func_array( [ $this, self::und( $context, 'sidebox' ) ], [ $sub, $uri, $context ] );
				echo '</div>';
			}

		return TRUE;
	}

	protected function render_form_end(
		string $uri = '',
		?string $sub = NULL,
		?string $action = NULL,
		?string $context = NULL,
		bool $check_sidebox = FALSE
	): true {

		echo '</form>';
		return TRUE;
	}

	// DEFAULT METHOD: tools-sub HTML
	public function tools_sub( string $uri = '', ?string $sub = NULL ): true
	{
		$context = 'tools';
		$action  = 'bulk';

		$this->render_form_start( $uri, $sub, $action, $context, TRUE );

			if ( FALSE === $this->render_tools_html_before( $uri, $sub, $action, $context ) )
				return $this->render_form_end( $uri, $sub, $action, $context ); // bail if explicitly FALSE

			if ( $this->render_tools_html( $uri, $sub, $action, $context ) )
				$this->render_form_buttons( $context );

			$this->render_tools_html_after( $uri, $sub, $action, $context );

		return $this->render_form_end( $uri, $sub, $action, $context, TRUE );
	}

	// DEFAULT METHOD: used for `tools` default sub HTML
	protected function render_tools_html( string $uri, string $sub, string $action, string $context ): bool { return FALSE; }
	protected function render_tools_html_before( string $uri, string $sub, string $action, string $context ): bool { return TRUE; }
	protected function render_tools_html_after( string $uri, string $sub, string $action, string $context ): bool { return TRUE; }

	// DEFAULT METHOD: `roles` sub HTML
	public function roles_sub( string $uri = '', ?string $sub = NULL ): true
	{
		$context = 'roles';
		$action  = 'bulk';

		$this->render_form_start( $uri, $sub, $action, $context, TRUE );

			if ( FALSE === $this->render_roles_html_before( $uri, $sub, $action, $context ) )
				return $this->render_form_end( $uri, $sub, $action, $context ); // bail if explicitly FALSE

			if ( $this->render_roles_html( $uri, $sub, $action, $context ) )
				$this->render_form_buttons( $context );

			$this->render_roles_html_after( $uri, $sub, $action, $context );

		return $this->render_form_end( $uri, $sub, $action, $context, TRUE );
	}

	// DEFAULT METHOD: used for `roles` default sub HTML
	protected function render_roles_html( string $uri, string $sub, string $action, string $context ): bool { return FALSE; }
	protected function render_roles_html_before( string $uri, string $sub, string $action, string $context ): bool { return TRUE; }
	protected function render_roles_html_after( string $uri, string $sub, string $action, string $context ): bool { return TRUE; }

	// DEFAULT METHOD: `reports` sub HTML
	public function reports_sub( string $uri = '', ?string $sub = NULL ): true
	{
		$context = 'reports';
		$action  = 'bulk';

		$this->render_form_start( $uri, $sub, $action, $context, TRUE );

			if ( FALSE === $this->render_reports_html_before( $uri, $sub, $action, $context ) )
				return $this->render_form_end( $uri, $sub, $action, $context ); // bail if explicitly FALSE

			if ( $this->render_reports_html( $uri, $sub, $action, $context ) )
				$this->render_form_buttons( $context );

			$this->render_reports_html_after( $uri, $sub, $action, $context );

		return $this->render_form_end( $uri, $sub, $action, $context, TRUE );
	}

	// DEFAULT METHOD: used for `reports` default sub HTML
	protected function render_reports_html( string $uri, string $sub, string $action, string $context ): bool { return FALSE; }
	protected function render_reports_html_before( string $uri, string $sub, string $action, string $context ): bool { return TRUE; }
	protected function render_reports_html_after( string $uri, string $sub, string $action, string $context ): bool { return TRUE; }

	// DEFAULT METHOD: `imports` sub HTML
	public function imports_sub( string $uri = '', ?string $sub = NULL ): true
	{
		$context = 'imports';
		$action  = 'bulk';

		$this->render_form_start( $uri, $sub, $action, $context, TRUE );

			if ( FALSE === $this->render_imports_html_before( $uri, $sub, $action, $context ) )
				return $this->render_form_end( $uri, $sub, $action, $context ); // bail if explicitly FALSE

			if ( $this->render_imports_html( $uri, $sub, $action, $context ) )
				$this->render_form_buttons( $context );

			$this->render_imports_html_after( $uri, $sub, $action, $context );

		return $this->render_form_end( $uri, $sub, $action, $context, TRUE );
	}

	// DEFAULT METHOD: used for `imports` default sub HTML
	protected function render_imports_html( string $uri, string $sub, string $action, string $context ): bool { return FALSE; }
	protected function render_imports_html_before( string $uri, string $sub, string $action, string $context ): bool { return TRUE; }
	protected function render_imports_html_after( string $uri, string $sub, string $action, string $context ): bool { return TRUE; }

	// DEFAULT METHOD: `customs` sub HTML
	public function customs_sub( string $uri = '', ?string $sub = NULL ): true
	{
		$context = 'customs';
		$action  = 'bulk';

		$this->render_form_start( $uri, $sub, $action, $context, TRUE );

			if ( FALSE === $this->render_customs_html_before( $uri, $sub, $action, $context ) )
				return $this->render_form_end( $uri, $sub, $action, $context ); // bail if explicitly FALSE

			if ( $this->render_customs_html( $uri, $sub, $action, $context ) )
				$this->render_form_buttons( $context );

			$this->render_customs_html_after( $uri, $sub, $action, $context );

		return $this->render_form_end( $uri, $sub, $action, $context, TRUE );
	}

	// DEFAULT METHOD: used for `customs` default sub HTML
	protected function render_customs_html( string $uri, string $sub, string $action, string $context ): bool { return FALSE; }
	protected function render_customs_html_before( string $uri, string $sub, string $action, string $context ): bool { return TRUE; }
	protected function render_customs_html_after( string $uri, string $sub, string $action, string $context ): bool { return TRUE; }

	protected function get_current_form( array $defaults, ?string $context = NULL ): array
	{
		$context = $context ?? 'settings';

		$req = empty( $_REQUEST[$this->hook_base( $this->module->name )][$context] )
			? []
			: $_REQUEST[$this->hook_base( $this->module->name )][$context];

		return self::atts( $defaults, $req );
	}

	protected function fields_current_form( array $fields, ?string $context = NULL, array $excludes = [] ): void
	{
		$context = $context ?? 'settings';

		foreach ( $fields as $key => $value ) {

			if ( in_array( $key, $excludes ) )
				continue;

			Core\HTML::inputHidden( $this->hook_base( $this->module->name ).'['.$context.']['.$key.']', $value );
		}
	}

	protected function render_form_fields( string $sub, ?string $action = NULL, ?string $context = NULL ): void
	{
		$context = $context ?? 'settings';

		Core\HTML::inputHidden( 'base', $this->base );
		Core\HTML::inputHidden( 'key', $this->key );
		Core\HTML::inputHidden( 'context', $context );
		Core\HTML::inputHidden( 'sub', $sub );
		Core\HTML::inputHidden( 'action', $action ?? 'update' );

		WordPress\Redirect::fieldReferer();
		$this->nonce_field( $context, $sub );
	}

	// DEFAULT METHOD
	// NOTE: `$extra` argument is for extending in modules.
	public function append_sub( array $subs, ?string $context = NULL, array $extra = [] ): array
	{
		$context = $context ?? 'settings';

		if ( ! $this->cuc( $context ) )
			return $subs;

		return array_merge( $subs, [ $this->module->name => [
			'title' => $this->module->title,
			'hint'  => $this->module->desc,
			'icon'  => Services\Icons::get( $this->module->icon ),
		] ], $extra );
	}

	// TODO: must avoid storing the defaults!
	public function settings_validate( array $options, ?string $context = NULL ): array
	{
		$context = $context ?? 'settings';

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

					if ( isset( $args['default'] ) && ( (bool) $args['default'] === (bool) $option ) )
						unset( $options[$context][$setting] );

					else if ( (bool) $option )
						$options[$context][$setting] = TRUE;

					else
						unset( $options[$context][$setting] );

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

						// NOTE: Array keys are preserved in `array_filter()`
						$options[$context][$setting] = array_filter( $options[$context][$setting] );

					} else {

						// multiple checkboxes
						$options[$context][$setting] = array_filter( array_keys( $option ) );

						if ( empty( $options[$context][$setting] ) )
							unset( $options[$context][$setting] );

						else if ( isset( $args['default'] ) && is_array( $args['default'] )
							&& Core\Arraay::identicalValues( $args['default'], $options[$context][$setting] ) )
								unset( $options[$context][$setting] );

						// NOTE: `default` may set to `TRUE` for pre-select all options
						else if ( isset( $args['default'] ) && TRUE === $args['default']
							&& Core\Arraay::identicalValues( array_keys( $args['values'] ), $options[$context][$setting] ) )
								unset( $options[$context][$setting] );
					}

				} else {

					$options[$context][$setting] = trim( self::unslash( $option ) );

					if ( isset( $args['default'] ) && $args['default'] === $options[$context][$setting] )
						unset( $options[$context][$setting] );

					else if ( isset( $args['default'] ) && in_array( $args['type'], [
						'number',
					], TRUE ) && (int) $args['default'] === (int) $options[$context][$setting] )
						unset( $options[$context][$setting] );

					else if ( in_array( $args['type'], [
						'hidden',
						'priority',
						'url',
						'color',
						'email',
						'text',
						'textarea',
						'textarea-quicktags',
						'textarea-quicktags-tokens',
						'textarea-code-editor',
					], TRUE ) && '' === $options[$context][$setting] )
						unset( $options[$context][$setting] );
				}
			}

			if ( ! count( $options[$context] ) )
				unset( $options[$context] );
		}

		return $options;
	}

	protected function get_settings_field( string $setting, ?string $context = NULL ): array
	{
		$context = $context ?? 'settings';

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

	protected function check_settings(
		string $sub,
		?string $context = NULL,
		null|bool|string $screen_option = FALSE,
		array $extra = [],
		?string $key = NULL,
	): bool {

		$context = $context ?? 'tools'; // WTF?!

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

	public function init_settings(): void
	{
		if ( ! isset( $this->settings ) )
			$this->settings = $this->filters( 'settings', $this->get_global_settings(), $this->module );
	}

	public function register_settings( string $module = '' ): void
	{
		if ( $module !== $this->module->name )
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
				$title   = Services\Modulation::getSectionTitle( $section_suffix );

				if ( ! $title && method_exists( $this, 'settings_section_titles' ) )
					$title = call_user_func_array( [ $this, 'settings_section_titles' ], [ $section_suffix ] );

				if ( method_exists( $this, 'settings_section'.$section_suffix ) )
					$callback = [ $this, 'settings_section'.$section_suffix ];
				else if ( method_exists( gEditorial\Settings::class, 'settings_section'.$section_suffix ) )
					$callback = [ gEditorial\Settings::class, 'settings_section'.$section_suffix ];
				else
					$callback = '__return_false';

				Services\Modulation::addSection( $this->hook_base( $this->module->name ), [
					'id'            => $section,
					'callback'      => $callback,
					'title'         => empty( $title[0] ) ? Services\Modulation::makeSectionTitle( $section_suffix ) : $title[0],
					'description'   => empty( $title[1] ) ? FALSE : $title[1],
					'link'          => empty( $title[2] ) ? FALSE : $title[2],
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

	public function settings_header( ?string $context = NULL ): void
	{
		$context = $context ?? 'settings';
		$back    = $count = $flush = $filters = FALSE;

		if ( 'config' == $this->module->name ) {

			$title   = NULL;
			$count   = gEditorial()->count_active_modules();
			$flush   = WordPress\URL::maybeFlushRules();
			$filters = TRUE;

		} else {

			$back  = gEditorial\Settings::getURLbyContext( $context );
			$title = sprintf(
				/* translators: `%1$s`: system title, `%2$s`: module title */
				_x( '%1$s: %2$s', 'Module', 'geditorial-admin' ),
				gEditorial\Plugin::system(),
				$this->module->title
			);
		}

		gEditorial\Settings::wrapOpen( $this->module->name, $context );

			gEditorial\Settings::headerTitle( $context, $title, $back, NULL, $this->module->icon, $count, TRUE, $filters );
			gEditorial\Settings::message();

			if ( $flush )
				echo Core\HTML::warning( _x( 'You need to flush rewrite rules!', 'Module', 'geditorial-admin' ), FALSE );

			echo '<div class="-header">';
			Core\HTML::h4( $this->module->desc ?? '' );

			if ( method_exists( $this, 'settings_intro' ) )
				$this->settings_intro( $context );

		gEditorial\Settings::wrapClose( TRUE, $context );
	}

	protected function settings_footer( ?string $context = NULL ): void
	{
		$context = $context ?? 'settings';

		if ( 'config' === $this->module->name )
			Services\SystemBranding::credits( $context );

		else
			$this->settings_signature( $context );
	}

	protected function settings_signature( ?string $context = NULL ): void
	{
		Services\SystemBranding::signature( $context ?? 'settings' );
	}

	public function add_settings_field( $r = [], ?string $context = NULL ): mixed
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
			return FALSE;

		if ( empty( $args['title'] ) )
			$args['title'] = $args['field'];

		return add_settings_field(
			$args['field'],
			$args['title'],
			$args['callback'],
			$args['page'],
			$args['section'],
			$args
		);
	}

	public function settings_id_name_cb( $args )
	{
		if ( $args['option_group'] )
			return [
				$args['id_attr'] ?: self::dsh( $args['option_base'], $args['option_group'], $args['field'] ),
				$args['name_attr'] ?: sprintf( '%s[%s][%s]', $args['option_base'], $args['option_group'], $args['field'] ),
			];

		return [
			$args['id_attr'] ?: self::dsh( $args['option_base'], $args['field'] ),
			$args['name_attr'] ?: sprintf( '%s[%s]', $args['option_base'], $args['field'] ),
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
	protected function settings_render_upload_field( $mimes, $size_after = TRUE, $name = 'import', $field_wrap = 'div' )
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

		$filesize = Core\File::formatSize( apply_filters( 'import_upload_size_limit', wp_max_upload_size() ) );

		if ( $field_wrap )
			echo '<'.$field_wrap.' class="-wrap -settings-field -file">';

		$this->do_settings_field( [
			'type'      => 'file',
			'field'     => 'import_users_file',
			'name_attr' => $name,
			'cap'       => 'upload_files',
			'values'    => (array) $mimes,
		] );

		// NOTE: here to avoid `NBSP` on `after` argument
		if ( $size_after )
			echo gEditorial\Settings::fieldAfterText( sprintf(
				/* translators: `%s`: file size */
				_x( 'Maximum upload size: %s', 'Internal: Settings Core: Message', 'geditorial-admin' ),
				Core\HTML::code( Core\HTML::wrapLTR( $filesize ) )
			) );

		if ( $field_wrap )
			echo '</'.$field_wrap.'>';

		return $filesize;
	}

	protected function notice_settings_extra_buttons( ?string $module = NULL ): void {}
	protected function handle_settings_extra_buttons( ?string $module = NULL ): void {}
	protected function register_settings_extra_buttons( ?string $module = NULL ): void {}
}
