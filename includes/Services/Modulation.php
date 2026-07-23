<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Modulation extends gEditorial\Service
{
	const PRODUCTION_BLACKLIST = [
		'alpha',
		'experimental',
		'unknown',
	];

	/**
	 * Retrieves the module setup object for given arguments.
	 *
	 * @param array $arguments
	 * @param string|bool $folder
	 * @param string $class
	 * @return false|array
	 */
	public static function moduleObject( array $arguments, string|bool $folder = FALSE, ?string $class = NULL ): false|array
	{
		if ( FALSE === $arguments )
			return FALSE;

		if ( ! isset( $arguments['name'], $arguments['title'] ) )
			return FALSE;

		$defaults = [
			'folder'     => $folder,
			'class'      => $class ?: self::moduleClass( $arguments['name'], FALSE ),
			'textdomain' => self::dsh( static::BASE, Core\Text::sanitizeBase( $arguments['name'] ) ),   // or `NULL` for plugin base

			'icon'      => 'screenoptions',   // `dashicons` class / SVG icon array
			'configure' => TRUE,              // or `settings`, `tools`, `reports`, `imports`, `customs`, `FALSE` to disable
			'i18n'      => TRUE,              // or `FALSE`, `adminonly`, `frontonly`, `restonly`
			'frontend'  => TRUE,              // Whether or not the module should be loaded on the frontend
			'autoload'  => FALSE,             // Autoloading a module will remove the ability to enable/disable it
			'disabled'  => FALSE,             // FALSE or string explaining why the module is not available
			'access'    => 'unknown',         // or `private`, `stable`, `beta`, `alpha`, `beta`, `deprecated`, `planned`
			'keywords'  => [],
		];

		return [
			$arguments['name'],
			(object) array_merge( $defaults, $arguments ),
		];
	}

	public static function moduleClass( string $module, bool $check = TRUE, ?string $root = NULL ): false|string
	{
		$class = '';
		$root  = $root ?? '\\geminorum\\gEditorial\\Modules';

		foreach ( explode( '-', str_replace( '_', '-', $module ) ) as $word )
			$class.= ucfirst( $word ).'';

		$class = $root.'\\'.$class.'\\'.$class;

		if ( $check && ! class_exists( $class ) )
			return FALSE;

		return $class;
	}

	public static function moduleSlug( string $module, bool $link = TRUE ): string
	{
		return $link
			? ucwords( str_replace( [ '_', ' ' ], '-', $module ), '-' )
			: ucwords( str_replace( [ '_', '-' ], ' ', $module ) );
	}

	public static function moduleLoading( object $module, ?string $stage = NULL ): bool
	{
		if ( 'private' === $module->access && ! GEDITORIAL_LOAD_PRIVATES )
			return FALSE;

		if ( 'beta' === $module->access && ! GEDITORIAL_BETA_FEATURES )
			return FALSE;

		if ( ! GEDITORIAL_CRM_FEATURES && in_array( 'crm-feature', $module->keywords, TRUE ) )
			return FALSE;

		$stage = $stage ?? self::const( 'WP_STAGE', 'production' ); // 'development'

		if ( 'production' !== $stage )
			return TRUE;

		if ( in_array( $module->access, static::PRODUCTION_BLACKLIST, TRUE ) )
			return FALSE;

		return TRUE;
	}

	public static function moduleEnabled( object $options ): bool
	{
		$enabled = $options->enabled ?? FALSE;

		if ( 'off' === $enabled )
			return FALSE;

		if ( 'on' === $enabled )
			return TRUE;

		return $enabled;
	}

	public static function getSectionTitle( string $suffix ): false|array
	{
		switch ( $suffix ) {
			case '_general'    : return [ _x( 'General', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_setup'      : return [ _x( 'Setup', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_config'     : return [ _x( 'Configuration', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_defaults'   : return [ _x( 'Defaults', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_tweaks'     : return [ _x( 'Tweaks', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_misc'       : return [ _x( 'Miscellaneous', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_archives'   : return [ _x( 'Archives', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_frontend'   : return [ _x( 'Front-end', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_backend'    : return [ _x( 'Back-end', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_content'    : return [ _x( 'Generated Contents', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_featured'   : return [ _x( 'Featured Contents', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_connected'  : return [ _x( 'Connected Contents', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_supports'   : return [ _x( 'Feature Support', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_dashboard'  : return [ _x( 'Admin Dashboard', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_editlist'   : return [ _x( 'Admin Edit List', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_comments'   : return [ _x( 'Admin Comment List', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_editpost'   : return [ _x( 'Admin Edit Post', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_edittags'   : return [ _x( 'Admin Edit Terms', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_columns'    : return [ _x( 'Admin List Columns', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_import'     : return [ _x( 'Import Preferences', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_reports'    : return [ _x( 'Report Preferences', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_printpage'  : return [ _x( 'Print Preferences', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_shortcode'  : return [ _x( 'ShortCode Preferences', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_strings'    : return [ _x( 'Custom Strings', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_formats'    : return [ _x( 'Custom Formats', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_avatars'    : return [ _x( 'User Avatars', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_tabs'       : return [ _x( 'Content Tabs', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_types'      : return [ _x( 'Data Types', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_fields'     : return [ _x( 'Data Fields', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_constants'  : return [ _x( 'Custom Constants', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_posttypes'  : return [ _x( 'Post-Types', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_taxonomies' : return [ _x( 'Taxonomies', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_subcontent' : return [ _x( 'Sub-Contents', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_bulkactions': return [ _x( 'Bulk Actions', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_customtabs' : return [ _x( 'Custom Tabs', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_roles'      : return [ _x( 'Availability', 'Service: Modulation: Section Title', 'geditorial-admin' ), _x( 'Though Administrators have it all!', 'Service: Modulation: Section Description', 'geditorial-admin' ) ];
			case '_units'      : return [ _x( 'Units Fields', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_geo'        : return [ _x( 'Geo Fields', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_woocommerce': return [ _x( 'WooCommerce', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_p2p'        : return [ _x( 'Posts-to-Posts', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
			case '_o2o'        : return [ _x( 'Objects-to-Objects', 'Service: Modulation: Section Title', 'geditorial-admin' ), NULL ];
		}

		return FALSE;
	}

	public static function makeSectionTitle( string $suffix ): string
	{
		$title = '';

		foreach ( explode( '_', str_replace( ' ', '_', $suffix ) ) as $word )
			$title.= ucfirst( $word ).' ';

		return $title;
	}

	// @source: `add_settings_section()`
	public static function addSection( string $page, array $atts = [] ): false|array
	{
		global $wp_settings_sections;

		$args = self::parsed( [
			'id'            => FALSE,
			'title'         => FALSE,
			'description'   => FALSE,
			'link'          => FALSE,
			'callback'      => '__return_false',
			'section_class' => '',
		], $atts );

		if ( ! $args['id'] )
			return FALSE;

		if ( ! $args['title'] )
			$args['title'] = self::makeSectionTitle( $args['id'] );

		return $wp_settings_sections[$page][$args['id']] = $args;
	}

	// @SOURCE: `do_settings_sections()`
	public static function renderSections( string $page ): void
	{
		global $wp_settings_sections, $wp_settings_fields;

		if ( empty( $wp_settings_sections[$page] ) )
			return;

		$tabs   = Core\Arraay::pluck( $wp_settings_sections[$page], 'title', 'id' );
		$active = Core\Arraay::keyFirst( $tabs );

		echo '<div class="base-tabs-list -base nav-tab-base">';

			Core\HTML::tabNav( $active, $tabs );

			foreach ( (array) $wp_settings_sections[$page] as $section ) {

				echo '<div class="nav-tab-content -content'.( $active === $section['id'] ? ' nav-tab-active -active' : '' ).'" data-tab="'.$section['id'].'">';

				if ( $section['callback'] && '__return_false' !== $section['callback'] )
					call_user_func( $section['callback'], $section );

				else
					self::fieldSection( $section );

				if ( ! isset( $wp_settings_fields )
					|| ! isset( $wp_settings_fields[$page] )
					|| ! isset( $wp_settings_fields[$page][$section['id']] ) ) {

					echo '</div>';
					continue;
				}

				echo '<table class="form-table -section-table"><tbody class="-section-body -list">';
					// do_settings_fields( $page, $section['id'] );
					self::sectionFields( $page, $section['id'] );
				echo '</tbody></table>';

				echo '</div>';
			}

		echo '</div>';
	}

	// NOTE: THE OLD CLASSIC WAY!
	public static function renderSections_OLD( string $page ): void
	{
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! isset( $wp_settings_sections[$page] ) )
			return;

		foreach ( (array) $wp_settings_sections[$page] as $section ) {

			echo '<div class="'.Core\HTML::prepClass( '-section-wrap', $section['section_class'] ).'">';

				Core\HTML::h2( $section['title'], '-section-title' );

				if ( $section['callback'] )
					call_user_func( $section['callback'], $section );

				if ( ! isset( $wp_settings_fields )
					|| ! isset( $wp_settings_fields[$page] )
					|| ! isset( $wp_settings_fields[$page][$section['id']] ) ) {

					echo '</div>';
					continue;
				}

				echo '<table class="form-table -section-table"><tbody class="-section-body -list">';
					// do_settings_fields( $page, $section['id'] );
					self::sectionFields( $page, $section['id'] );
				echo '</tbody></table>';

			echo '</div>';
		}
	}

	// @SOURCE: `do_settings_fields()`
	public static function sectionFields( string $page, string $section ): void
	{
		global $wp_settings_fields;

		if ( ! isset( $wp_settings_fields[$page][$section] ) )
			return;

		foreach ( (array) $wp_settings_fields[$page][$section] as $field ) {
			$class = [ '-field' ];

			if ( ! empty( $field['args']['class'] ) )
				$class[] = $field['args']['class'];

			echo '<tr class="'.Core\HTML::prepClass( $class ).'">';

			if ( ! empty( $field['args']['label_for'] ) )
				echo '<th class="-th" scope="row"><label for="'
					.Core\HTML::escape( $field['args']['label_for'] )
					.'">'.$field['title'].'</label></th>';

			else
				echo '<th class="-th" scope="row">'.$field['title'].'</th>';

			echo '<td class="-td">';
				call_user_func( $field['callback'], $field['args'] );
			echo '</td></tr>';
		}
	}

	public static function sectionEmpty( string $description ): void
	{
		Core\HTML::desc( $description, TRUE, '-section-description -section-empty' );
	}

	public static function fieldSection( array $section, string $tag = 'h2' ): void
	{
		echo Core\HTML::tag( $tag, ( $section['title'] ?? '' ).gEditorial\Settings::fieldAfterIcon( $section['link'] ?? '' ) );

		Core\HTML::desc( ( $section['description'] ?? '' ) );
	}

	public static function fieldSectionAdopted( string $source, ?string $author = NULL, ?string $link = NULL ): string
	{
		$template = $author
			/* translators: `%1$s`: source name, `%2$s`: author name */
			? _x( 'Adopted from %1$s by %2$s.', 'Service: Modulation: Section Description', 'geditorial-admin' )
			/* translators: `%1$s`: source name */
			: _x( 'Adopted from %1$s.', 'Service: Modulation: Section Description', 'geditorial-admin' );

		$html = sprintf( $template,
			Core\HTML::code( $source ),
			Core\HTML::code( $author ?? '' )
		);

		if ( empty( $link ) )
			return $html;

		return Core\Text::spc(
			$html,
			sprintf(
				/* translators: `%1$s`: link start, `%2$s`: link end */
				_x( 'Visit %1$shere%2$s for more information.', 'Service: Modulation: Section Description', 'geditorial-admin' ),
				sprintf( '<a href="%s" target="_blank">', Core\HTML::escapeURL( $link ) ),
				'</a>'
			)
		);
	}

	public static function renderButtons( object $module, bool $enabled = FALSE ): void
	{
		if ( $module->autoload ) {
			echo Core\HTML::wrap( _x( 'Auto-loaded!', 'Service: Modulation: Notice', 'geditorial-admin' ), '-autoloaded -warning', FALSE );
			return;
		}

		echo Core\HTML::tag( 'input', [
			'type'  => 'submit',
			'value' => _x( 'Enable', 'Service: Modulation: Button', 'geditorial-admin' ),
			'style' => $enabled ? 'display:none' : FALSE,
			'class' => Core\Link::buttonClass( TRUE, [ 'button-primary', 'hide-if-no-js' ] ),
			'data'  => [
				'module' => $module->name,
				'do'     => 'enable',
			],
		] );

		echo Core\HTML::tag( 'input', [
			'type'  => 'submit',
			'value' => _x( 'Disable', 'Service: Modulation: Button', 'geditorial-admin' ),
			'style' => $enabled ? FALSE : 'display:none',
			'class' => Core\Link::buttonClass( TRUE, [ 'button-secondary', 'hide-if-no-js', '-danger' ] ),
			'data'  => [
				'module' => $module->name,
				'do'     => 'disable',
			],
		] );

		echo Core\HTML::tag( 'span', [
			'class' => Core\Link::buttonClass( TRUE, [ '-danger', 'hide-if-js' ] ),
		], _x( 'You have to enable Javascript!', 'Service: Modulation: Notice', 'geditorial-admin' ) );
	}

	public static function renderConfigure( object $module, bool $enabled = FALSE ): void
	{
		if ( ! $module->configure )
			return;

		if ( 'tools' === $module->configure )
			echo Core\HTML::tag( 'a', [
				'href'  => gEditorial\Settings::getURLbyContext( 'tools', TRUE, [ 'sub' => $module->name ] ),
				'style' => $enabled ? FALSE : 'display:none',
				'class' => Core\Link::buttonClass( TRUE, 'button-primary' ),
				'data'  => [
					'module' => $module->name,
					'do'     => 'configure',
				],
			], _x( 'Tools', 'Service: Modulation: Button', 'geditorial-admin' ) );


		else if ( 'reports' === $module->configure )
			echo Core\HTML::tag( 'a', [
				'href'  => gEditorial\Settings::getURLbyContext( 'reports', TRUE, [ 'sub' => $module->name ] ),
				'style' => $enabled ? FALSE : 'display:none',
				'class' => Core\Link::buttonClass( TRUE, 'button-primary' ),
				'data'  => [
					'module' => $module->name,
					'do'     => 'configure',
				],
			], _x( 'Reports', 'Service: Modulation: Button', 'geditorial-admin' ) );

		else
			echo Core\HTML::tag( 'a', [
				'href'  => gEditorial\Settings::getURLbyContext( 'settings', TRUE, [ 'module' => $module->name ] ),
				'style' => $enabled ? FALSE : 'display:none',
				'class' => Core\Link::buttonClass( TRUE, 'button-primary' ),
				'data'  => [
					'module' => $module->name,
					'do'     => 'configure',
				],
			], _x( 'Configure', 'Service: Modulation: Button', 'geditorial-admin' ) );
	}

	public static function renderInfo( object $module, bool $enabled = FALSE, string $tag = 'h3' ): void
	{
		$access = ( ! empty( $module->access ) && 'stable' !== $module->access )
			? sprintf( ' <code title="%3$s" class="-acccess -access-%1$s">%2$s</code>',
				$module->access,
				strtoupper( $module->access ),
				_x( 'Access Code', 'Service: Modulation', 'geditorial-admin' )
			) : '';

		if ( $wiki = SystemHelp::getModuleDocsURL( $module ) )
			Core\HTML::h3( Core\HTML::tag( 'a', [
				'href'   => $wiki,
				'target' => '_blank',
				'title'  => sprintf(
					/* translators: `%s`: module title */
					_x( '%s Documentation', 'Service: Modulation', 'geditorial-admin' ),
					$module->title
				),
			], $module->title ).$access, '-title' );

		else
			Core\HTML::h3( $module->title.$access, '-title' );


		Core\HTML::desc( Core\Text::wordWrap( $module->desc ?? '' ) );

		// `list.js` filters
		echo '<span class="-module-title" style="display:none;" aria-hidden="true">'.$module->title.'</span>';
		echo '<span class="-module-key" style="display:none;" aria-hidden="true">'.$module->name.'</span>';
		echo '<span class="-module-access" style="display:none;" aria-hidden="true">'.$module->access.'</span>';
		echo '<span class="-module-keywords" style="display:none;" aria-hidden="true">'.implode( ' ', Core\Arraay::prepString( $module->keywords ) ).'</span>';
		echo '<span class="status" data-do="enabled" style="display:none;" aria-hidden="true">'.( $enabled ? 'true' : 'false' ).'</span>';
	}

	// TODO: must check for minimum version of WooCommerce
	public static function moduleCheckWooCommerce( ?string $message = NULL ): false|string
	{
		return WordPress\WooCommerce::isActive()
			? FALSE
			: ( is_null( $message ) ? _x( 'Needs WooCommerce', 'Service: Modulation', 'geditorial-admin' ) : $message );
	}

	public static function moduleCheckPersianDate( ?string $message = NULL ): false|string
	{
		return self::const( 'GPERSIANDATE_VERSION' )
			? FALSE
			: _x( 'Needs gPersianDate', 'Service: Modulation', 'geditorial-admin' );
	}

	public static function moduleCheckDocumentRevisions( ?string $message = NULL ): false|string
	{
		return class_exists( 'WP_Document_Revisions' )
			? FALSE
			: Core\HTML::link(
				_x( 'Needs WP-Document-Revisions', 'Service: Modulation', 'geditorial-admin' ),
				'https://github.com/wp-document-revisions/wp-document-revisions',
				TRUE
			);
	}

	public static function moduleCheckLocale( string|array $locale, ?string $message = NULL ): false|string
	{
		$current = Core\L10n::locale( TRUE );

		foreach ( (array) $locale as $code )
			if ( $current === $code )
				return FALSE;

		return $message ?? _x( 'Not Available on Current Locale', 'Service: Modulation', 'geditorial-admin' );
	}

	public static function enqueueVirastar(): false|string
	{
		if ( ! gEditorial()->enabled( 'ortho' ) )
			return FALSE;

		return gEditorial()->module( 'ortho' )->enqueueVirastar();
	}

	public static function hasByline( mixed $post, $context = NULL ): bool
	{
		if ( ! gEditorial()->enabled( 'byline' ) )
			return FALSE;

		return (bool) gEditorial()->module( 'byline' )->has_content_for_post( $post, $context );
	}

	public static function isTaxonomyGenre( string $taxonomy, string $fallback = 'genre' ): bool
	{
		return $taxonomy === gEditorial()->constant( 'genres', 'main_taxonomy', $fallback );
	}

	public static function isTaxonomyAudit( string $taxonomy, string $fallback = 'audit_attribute' ): bool
	{
		return $taxonomy === gEditorial()->constant( 'audit', 'main_taxonomy', $fallback );
	}

	public static function setTaxonomyAudit( mixed $post, int|string|array $term_ids, bool $append = TRUE, string $fallback = 'audit_attribute' ): false|array
	{
		if ( ! gEditorial()->enabled( 'audit' ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! gEditorial()->module( 'audit' )->posttype_supported( $post->post_type ) )
			return FALSE;

		if ( $append && empty( $term_ids ) )
			return FALSE;

		else if ( empty( $term_ids ) )
			$terms = NULL;

		else if ( is_string( $term_ids ) )
			$terms = $term_ids;

		else
			$terms = Core\Arraay::prepNumeral( $term_ids );

		$taxonomy = gEditorial()->constant( 'audit', 'main_taxonomy', $fallback );
		$result   = wp_set_object_terms( $post->ID, $terms, $taxonomy, $append );

		if ( is_wp_error( $result ) )
			return FALSE;

		clean_object_term_cache( $post->ID, $taxonomy );

		return $result;
	}
}
