<?php namespace geminorum\gEditorial\Modules\Regional;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Term;
use geminorum\gEditorial\WordPress\Taxonomy;

class Regional extends gEditorial\Module
{

	protected $imports_datafile = 'languages-20230325.json';

	public static function module()
	{
		return [
			'name'  => 'regional',
			'title' => _x( 'Regional', 'Modules: Regional', 'geditorial' ),
			'desc'  => _x( 'Regional MetaData', 'Modules: Regional', 'geditorial' ),
			'icon'  => 'translation',
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_editpost' => [
				'assign_default_term',
				'metabox_advanced',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'lang_tax' => 'language',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'lang_tax' => NULL,
			],
		];
	}

	protected function tool_box_content()
	{
		/* translators: %s: iso code */
		HTML::desc( sprintf( _x( 'Helps with Importing Regional Languages from %s into WordPress.', 'Tool Box', 'geditorial-regional' ), HTML::code( 'ISO 639-1' ) ) );
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'lang_tax' => _n_noop( 'Language', 'Languages', 'geditorial-regional' ),
			],
			'labels' => [
				'lang_tax' => [
					'uncategorized' => _x( 'Unknown', 'Taxonomy Label', 'geditorial-regional' ),
				]
			],
			'defaults' => [
				'lang_tax' => [
					'name'        => _x( '[Unknown]', 'Default Term: Name', 'geditorial-regional' ),
					'description' => _x( 'Unknown Languages', 'Default Term: Description', 'geditorial-regional' ),
					'slug'        => 'unknown',
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'wp_importer' => [
				'title'       => _x( 'Import Languages', 'Importer: Title', 'geditorial-regional' ),
				/* translators: %s: iso code */
				'description' => sprintf( _x( 'Regional Languages from %s into WordPress', 'Importer: Description', 'geditorial-regional' ), HTML::code( 'ISO 639-1' ) ),
				/* translators: %s: redirect url */
				'redirect'    => _x( 'If your browser doesn&#8217;t redirect automatically, <a href="%s">click here</a>.', 'Importer: Redirect', 'geditorial-regional' ),
			],

			'show_option_all'  => _x( 'Language', 'Show Option All', 'geditorial-regional' ),
			'show_option_none' => _x( '(Uknonwn Language)', 'Show Option None', 'geditorial-regional' ),
		];

		$strings['terms'] = [
			'lang_tax' => [
				// @SEE: https://en.wikipedia.org/wiki/ISO_639
				'arabic'  => _x( 'Arabic', 'Default Term: Language', 'geditorial-regional' ),
				'persian' => _x( 'Farsi', 'Default Term: Language', 'geditorial-regional' ),
				'english' => _x( 'English', 'Default Term: Language', 'geditorial-regional' ),
				'french'  => _x( 'French', 'Default Term: Language', 'geditorial-regional' ),
			],
		];

		return $strings;
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'lang_tax', [
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
			'show_in_nav_menus'  => TRUE,
			'show_in_menu'       => FALSE,
			'default_term'       => NULL,
			'meta_box_cb'        => $this->get_setting( 'metabox_advanced' ) ? NULL : '__checklist_terms_callback',
		] );

		if ( ! is_admin() )
			return;

		$this->filter_module( 'terms', 'column_title', 4 );
		$this->filter_module( 'terms', 'field_tagline_title', 4 );

		$this->register_default_terms( 'lang_tax' );
		$this->filter( 'imports_data_summary', 1, 10, FALSE, $this->base );

		$this->_hook_wp_register_importer();
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'lang_tax' ) == $screen->taxonomy ) {

			if ( 'edit-tags' == $screen->base ) {

				$this->action( 'taxonomy_tab_extra_content', 2, 12, FALSE, 'gnetwork' );

			} else if ( 'term' == $screen->base ) {
			}

			$this->filter_string( 'parent_file', 'options-general.php' );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

			} else if ( 'edit' == $screen->base ) {
			}
		}
	}

	public function admin_menu()
	{
		$this->_hook_menu_taxonomy( 'lang_tax', 'options-general.php' );
	}

	public function get_adminmenu( $page = TRUE, $extra = [] )
	{
		return FALSE;
	}

	public function imports_data_summary( $data )
	{
		$data[] = [
			'title'       => $this->imports_datafile,
			'updated'     => '2023-03-25',
			'description' => 'List of languages with ISO 639-1 Alpha-2 codes in JSON.',
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

	public function imports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'imports' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'imports', $sub );

				if ( Tablelist::isAction( 'language_taxonomy_create', TRUE ) ) {

					if ( ! $data = $this->get_imports_raw_data() )
						WordPress::redirectReferer( 'wrong' );

					$count  = 0;
					$terms  = [];
					$data   = Arraay::reKey( $data, 'code' );
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
						$count++;
					}

					if ( ! Taxonomy::insertDefaultTerms( $this->constant( 'lang_tax' ), $terms, ( $update ? 'not_name' : FALSE ) ) )
						WordPress::redirectReferer( 'noadded' );

					WordPress::redirectReferer( [
						'message' => 'created',
						'count'   => $count,
					] );
				}
			}
		}
	}

	protected function render_imports_html( $uri, $sub )
	{
		HTML::h3( _x( 'Import Languages', 'Header', 'geditorial-regional' ) );

		echo '<table class="form-table">';
		echo '<tr><th scope="row">'.HTML::code( _x( 'ISO 639-1 Alpha-2', 'Imports', 'geditorial-regional' ), 'description' ).'</th><td>';

		if ( $data = $this->get_imports_raw_data() ) {

			HTML::tableList( [
				'_cb'  => 'code',
				'code' => [
					'title' => _x( 'Code', 'Table Column', 'geditorial-regional' ),
					'class' => '-ltr',
				],
				'term' => [
					'title'    => _x( 'Term', 'Table Column', 'geditorial-regional' ),
					'class'    => '-ltr',
					'args'     => [ 'taxonomy' => $this->constant( 'lang_tax' ) ],
					'callback' => function( $value, $row, $column, $index, $key, $args ) {

						if ( $term = Term::exists( $row['name'], $column['args']['taxonomy'] ) )
							return Helper::getTermTitleRow( $term );

						return Helper::htmlEmpty();
					}
				],
				'name' => [
					'title' => _x( 'English Name', 'Table Column', 'geditorial-regional' ),
					'class' => '-ltr',
				],
				'native' => [
					'title' => _x( 'Native Name', 'Table Column', 'geditorial-regional' ),
					'class' => '-ltr',
				],

			], $data, [
				'empty' => HTML::warning( _x( 'There are no languages available!', 'Message: Table Empty', 'geditorial-regional' ), FALSE ),
			] );

		} else {

			echo gEditorial\Plugin::wrong();
		}

		echo '</td></tr>';
		echo '<tr><th scope="row">&nbsp;</th><td>';
		echo $this->wrap_open_buttons( '-imports' );

		Settings::submitButton( 'language_taxonomy_create',
			_x( 'Create Language Terms', 'Button', 'geditorial-regional' ), TRUE );

		Settings::submitCheckBox( 'language_taxonomy_update',
			_x( 'Update Existing Terms', 'Button', 'geditorial-regional' ) );

		echo '</p>';

		HTML::desc( _x( 'Check for available languages and create corresponding language terms.', 'Message', 'geditorial-regional' ) );

		echo '</td></tr>';

		echo '</table>';
	}

	public function terms_column_title( $title, $field, $taxonomy, $fallback )
	{
		if ( 'tagline' !== $field )
			return $title;

		return $taxonomy === $this->constant( 'lang_tax' )
			? _x( 'Native Name', 'Table Column', 'geditorial-regional' )
			: $title;
	}

	public function terms_field_tagline_title( $title, $taxonomy, $field, $term )
	{
		return $taxonomy === $this->constant( 'lang_tax' )
			? _x( 'Native Name', 'Table Column', 'geditorial-regional' )
			: $title;
	}

	public function taxonomy_tab_extra_content( $taxonomy, $object )
	{
		$this->render_imports_toolbox_card();
	}
}
