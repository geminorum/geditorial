<?php namespace geminorum\gEditorial\Modules\Lingo;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Lingo extends gEditorial\Module
{
	use Internals\BulkExports;
	use Internals\CoreAdmin;
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\CoreToolBox;
	use Internals\RawImports;

	protected $disable_no_customs = TRUE;
	protected $imports_datafiles  = [
		'default' => 'languages-20230325.json',
	];

	public static function module()
	{
		return [
			'name'     => 'lingo',
			'title'    => _x( 'Lingo', 'Modules: Lingo', 'geditorial-admin' ),
			'desc'     => _x( 'Language Identifiers', 'Modules: Lingo', 'geditorial-admin' ),
			'icon'     => 'translation',
			'access'   => 'beta',
			'keywords' => [
				'taxmodule',
				'language',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_editpost' => [
				'metabox_advanced',
				'selectmultiple_term',
			],
			'_roles' => $this->corecaps_taxonomy_get_roles_settings( 'language_taxonomy', TRUE ),
		];
	}

	protected function get_global_constants()
	{
		return [
			'language_taxonomy' => 'language',
		];
	}

	protected function tool_box_content()
	{
		Core\HTML::desc( sprintf(
			/* translators: `%s`: ISO code */
			_x( 'Helps with Importing Language Identifiers from %s into WordPress.', 'Tool Box', 'geditorial-lingo' ),
			Core\HTML::code( 'ISO 639-1' )
		) );
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'language_taxonomy' => _n_noop( 'Language', 'Languages', 'geditorial-lingo' ),
			],
			'labels' => [
				'language_taxonomy' => [
					'extended_label'   => _x( 'Language Identifiers', 'Label: `extended_label`', 'geditorial-lingo' ),
					'show_option_all'  => _x( 'Languages', 'Label: `show_option_all`', 'geditorial-lingo' ),
					'show_option_none' => _x( '(Unidentified)', 'Label: `show_option_none`', 'geditorial-lingo' ),
					'uncategorized'    => _x( 'Unidentified', 'Label: `uncategorized`', 'geditorial-lingo' ),
				]
			],
			'defaults' => [
				'language_taxonomy' => [
					'name'        => _x( '[Unidentified]', 'Default Term: Name', 'geditorial-lingo' ),
					'description' => _x( 'Unidentified Languages', 'Default Term: Description', 'geditorial-lingo' ),
					'slug'        => 'unidentified',
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'wp_importer' => [
				'title'       => _x( 'Import Language Identifiers', 'Importer: Title', 'geditorial-lingo' ),
				'description' => sprintf(
					/* translators: `%s`: ISO code */
					_x( 'Language Identifiers from %s into WordPress', 'Importer: Description', 'geditorial-lingo' ),
					Core\HTML::code( 'ISO 639-1' )
				),
				/* translators: `%s`: redirect URL */
				'redirect' => _x( 'If your browser doesn&#8217;t redirect automatically, <a href="%s">click here</a>.', 'Importer: Redirect', 'geditorial-lingo' ),
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'language_taxonomy' => [
				// @SEE: https://en.wikipedia.org/wiki/ISO_639
				// https://en.wikipedia.org/wiki/List_of_ISO_639_language_codes
				'arabic'  => _x( 'Arabic', 'Default Term: Language', 'geditorial-lingo' ),
				'persian' => _x( 'Farsi', 'Default Term: Language', 'geditorial-lingo' ),
				'english' => _x( 'English', 'Default Term: Language', 'geditorial-lingo' ),
				'french'  => _x( 'French', 'Default Term: Language', 'geditorial-lingo' ),
			],
		];
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'language_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
			'show_in_nav_menus'  => TRUE,
			'show_in_menu'       => FALSE,
			'default_term'       => NULL,
			'meta_box_cb'        => $this->get_setting( 'metabox_advanced' ) ? NULL : FALSE,
		], NULL, [
			'custom_captype'  => TRUE,
			'custom_icon'     => $this->module->icon,
			'admin_managed'   => TRUE,
			'single_selected' => ! $this->get_setting( 'selectmultiple_term' ),
		] );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'language_taxonomy' );
		$this->coreadmin__ajax_taxonomy_multiple_supported_column( 'language_taxonomy' );

		if ( ! is_admin() )
			return;

		$this->filter( 'imports_data_summary', 1, 10, FALSE, $this->base );

		$this->_hook_wp_register_importer();
	}

	public function terms_init()
	{
		if ( ! is_admin() )
			return;

		$this->filter_module( 'terms', 'column_title', 4 );
		$this->filter_module( 'terms', 'field_tagline_title', 4 );
		$this->filter_module( 'terms', 'disable_field_edit', 3, 12 );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'language_taxonomy' ) == $screen->taxonomy ) {

			if ( 'edit-tags' == $screen->base ) {

				$this->_admin_enabled();
				$this->action( 'taxonomy_tab_extra_content', 2, 12, FALSE, 'gnetwork' );

			} else if ( 'term' == $screen->base ) {

			}

			$this->_hook_parentfile_for_optionsgeneralphp();
			$this->modulelinks__register_headerbuttons();
			$this->bulkexports__hook_supportedbox_for_term( 'language_taxonomy', $screen );
			$this->coreadmin__hook_taxonomy_multiple_supported_column( $screen );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

				if ( ! $this->get_setting( 'metabox_advanced' ) )
					$this->hook_taxonomy_metabox_mainbox(
						'language_taxonomy',
						$screen->post_type,
						$this->get_setting( 'selectmultiple_term' )
							? '__checklist_restricted_terms_callback'
							: '__singleselect_restricted_terms_callback'
					);

			} else if ( 'edit' == $screen->base ) {

				$this->corerestrictposts__hook_screen_taxonomies( 'language_taxonomy', 'reports' );
			}
		}
	}

	public function admin_menu()
	{
		$this->_hook_menu_taxonomy( 'language_taxonomy', 'options-general.php' );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_taxonomy( 'language_taxonomy' ) )
			$items[] = $glance;

		return $items;
	}

	// TODO: move to `ModuleInfo`
	public function imports_data_summary( $data )
	{
		$data[] = [
			'title'       => $this->imports_datafiles['default'],
			'updated'     => '2023-03-25',
			'description' => 'List of language identifiers with ISO 639-1 Alpha-2 codes in JSON.',
			'path'        => $this->get_imports_datafile(),
			'sources' => [
				[
					'link'  => 'https://gist.github.com/joshuabaker/d2775b5ada7d1601bcd7b31cb4081981',
					'title' => 'GitHub Gist',
				],
			],
		];

		return $data;
	}

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return $this->_override_module_cuc_by_taxonomy( 'language_taxonomy', $context, $fallback );
	}

	public function imports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'imports' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'imports', $sub );

				if ( gEditorial\Tablelist::isAction( 'language_taxonomy_create', TRUE ) ) {

					if ( ! $data = $this->get_imports_raw_data() )
						WordPress\Redirect::doReferer( 'wrong' );

					$count  = 0;
					$terms  = [];
					$data   = Core\Arraay::reKey( $data, 'code' );
					$update = self::req( 'language_taxonomy_update', FALSE );

					foreach ( $_POST['_cb'] as $code ) {

						if ( ! array_key_exists( $code, $data ) )
							continue;

						$term = [
							'slug' => strtolower( $data[$code]['name'] ),
							'name' => $data[$code]['name'],
							'meta' => [
								'code'    => $data[$code]['code'],
								'tagline' => $data[$code]['native'],
							],
						];

						$terms[] = $term;
						++$count;
					}

					if ( ! WordPress\Taxonomy::insertDefaultTerms( $this->constant( 'language_taxonomy' ), $terms, ( $update ? 'not_name' : FALSE ) ) )
						WordPress\Redirect::doReferer( 'noadded' );

					WordPress\Redirect::doReferer( [
						'message' => 'created',
						'count'   => $count,
					] );
				}
			}
		}
	}

	protected function render_imports_html( $uri, $sub )
	{
		Core\HTML::h3( _x( 'Import Language Identifiers', 'Header', 'geditorial-lingo' ) );

		echo '<table class="form-table">';
		echo '<tr><th scope="row">'.Core\HTML::code( _x( 'ISO 639-1 Alpha-2', 'Imports', 'geditorial-lingo' ), 'description' ).'</th><td>';

		if ( $data = $this->get_imports_raw_data() ) {

			Core\HTML::tableList( [
				'_cb'  => 'code',
				'code' => [
					'title' => _x( 'Code', 'Table Column', 'geditorial-lingo' ),
					'class' => '-ltr',
				],
				'term' => [
					'title'    => _x( 'Term', 'Table Column', 'geditorial-lingo' ),
					'class'    => '-ltr',
					'args'     => [ 'taxonomy' => $this->constant( 'language_taxonomy' ) ],
					'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

						if ( $term = WordPress\Term::exists( $row['name'], $column['args']['taxonomy'] ) )
							return gEditorial\Helper::getTermTitleRow( $term );

						return gEditorial\Helper::htmlEmpty();
					}
				],
				'name' => [
					'title' => _x( 'English Name', 'Table Column', 'geditorial-lingo' ),
					'class' => '-ltr',
				],
				'native' => [
					'title' => _x( 'Native Name', 'Table Column', 'geditorial-lingo' ),
					'class' => '-ltr',
				],

			], $data, [
				'empty' => Core\HTML::warning( _x( 'There are no language identifiers available!', 'Message: Table Empty', 'geditorial-lingo' ), FALSE ),
			] );

		} else {

			echo gEditorial\Plugin::wrong();
		}

		echo '</td></tr>';
		echo '<tr><th scope="row">&nbsp;</th><td>';
		echo $this->wrap_open_buttons( '-imports' );

		gEditorial\Settings::submitButton( 'language_taxonomy_create',
			_x( 'Create Language Terms', 'Button', 'geditorial-lingo' ), TRUE );

		gEditorial\Settings::submitCheckBox( 'language_taxonomy_update',
			_x( 'Update Existing Terms', 'Button', 'geditorial-lingo' ) );

		echo '</p>';

		Core\HTML::desc( _x( 'Check for available language identifiers and create corresponding terms.', 'Message', 'geditorial-lingo' ) );

		echo '</td></tr>';

		echo '</table>';
	}

	public function terms_column_title( $title, $field, $taxonomy, $fallback )
	{
		if ( 'tagline' !== $field )
			return $title;

		return $taxonomy === $this->constant( 'language_taxonomy' )
			? _x( 'Native Name', 'Table Column', 'geditorial-lingo' )
			: $title;
	}

	public function terms_field_tagline_title( $title, $taxonomy, $field, $term )
	{
		if ( 'tagline' !== $field )
			return $title;

		return $taxonomy === $this->constant( 'language_taxonomy' )
			? _x( 'Native Name', 'Table Column', 'geditorial-lingo' )
			: $title;
	}

	public function terms_disable_field_edit( $disabled, $field, $taxonomy )
	{
		return $taxonomy === $this->constant( 'language_taxonomy' )
			? ( ! current_user_can( 'manage_options' ) ) // restrict to administrators only!
			: $disabled;
	}

	public function taxonomy_tab_extra_content( $taxonomy, $object )
	{
		$this->render_imports_toolbox_card();
	}
}
