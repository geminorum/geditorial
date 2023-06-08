<?php namespace geminorum\gEditorial\Modules\Lingo;

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

class Lingo extends gEditorial\Module
{

	protected $disable_no_customs = TRUE;
	protected $imports_datafile   = 'languages-20230325.json';

	public static function module()
	{
		return [
			'name'   => 'lingo',
			'title'  => _x( 'Lingo', 'Modules: Lingo', 'geditorial' ),
			'desc'   => _x( 'Language Identifiers', 'Modules: Lingo', 'geditorial' ),
			'icon'   => 'translation',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'posttypes_option' => 'posttypes_option',
			'_editpost' => [
				'assign_default_term',
				'metabox_advanced',
			],
			'_roles' => [
				[
					'field'       => 'manage_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Manage Roles', 'Setting Title', 'geditorial-lingo' ),
					'description' => _x( 'Roles that can Manage, Edit and Delete Language Identifiers.', 'Setting Description', 'geditorial-lingo' ),
					'values'      => $roles,
				],
				[
					'field'       => 'assign_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Assign Roles', 'Setting Title', 'geditorial-lingo' ),
					'description' => _x( 'Roles that can Assign Language Identifiers.', 'Setting Description', 'geditorial-lingo' ),
					'values'      => $roles,
				],
				[
					'field'       => 'reports_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Reports Roles', 'Setting Title', 'geditorial-lingo' ),
					'description' => _x( 'Roles that can see Language Identifiers Reports.', 'Setting Description', 'geditorial-lingo' ),
					'values'      => $roles,
				],
				[
					'field'       => 'restricted_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Restricted Roles', 'Setting Title', 'geditorial-lingo' ),
					'description' => _x( 'Roles that check for Language Identifiers visibility.', 'Setting Description', 'geditorial-lingo' ),
					'values'      => $roles,
				],
				[
					'field'       => 'restricted',
					'type'        => 'select',
					'title'       => _x( 'Restricted Identifiers', 'Setting Title', 'geditorial-lingo' ),
					'description' => _x( 'Handles visibility of each identifier based on meta values.', 'Setting Description', 'geditorial-lingo' ),
					'default'     => 'disabled',
					'values'      => [
						'disabled' => _x( 'Disabled', 'Setting Option', 'geditorial-lingo' ),
						'hidden'   => _x( 'Hidden', 'Setting Option', 'geditorial-lingo' ),
					],
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'language_taxonomy' => 'language',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'language_taxonomy' => NULL,
			],
		];
	}

	protected function tool_box_content()
	{
		/* translators: %s: iso code */
		HTML::desc( sprintf( _x( 'Helps with Importing Language Identifiers from %s into WordPress.', 'Tool Box', 'geditorial-lingo' ), HTML::code( 'ISO 639-1' ) ) );
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'language_taxonomy' => _n_noop( 'Language', 'Languages', 'geditorial-lingo' ),
			],
			'labels' => [
				'language_taxonomy' => [
					'show_option_all'  => _x( 'Languages', 'Label: `show_option_all`', 'geditorial-lingo' ),
					'show_option_none' => _x( '(Unidentified)', 'Label: `show_option_none`', 'geditorial-lingo' ),
					'uncategorized'    => _x( 'Unidentified', 'Taxonomy Label', 'geditorial-lingo' ),
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
				/* translators: %s: iso code */
				'description' => sprintf( _x( 'Language Identifiers from %s into WordPress', 'Importer: Description', 'geditorial-lingo' ), HTML::code( 'ISO 639-1' ) ),
				/* translators: %s: redirect url */
				'redirect'    => _x( 'If your browser doesn&#8217;t redirect automatically, <a href="%s">click here</a>.', 'Importer: Redirect', 'geditorial-lingo' ),
			],
		];

		$strings['default_terms'] = [
			'language_taxonomy' => [
				// @SEE: https://en.wikipedia.org/wiki/ISO_639
				'arabic'  => _x( 'Arabic', 'Default Term: Language', 'geditorial-lingo' ),
				'persian' => _x( 'Farsi', 'Default Term: Language', 'geditorial-lingo' ),
				'english' => _x( 'English', 'Default Term: Language', 'geditorial-lingo' ),
				'french'  => _x( 'French', 'Default Term: Language', 'geditorial-lingo' ),
			],
		];

		return $strings;
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
			'meta_box_cb'        => $this->get_setting( 'metabox_advanced' ) ? NULL : '__checklist_terms_callback',
		], NULL, TRUE );

		$this->filter( 'map_meta_cap', 4 );

		if ( ! is_admin() )
			return;

		$this->filter_module( 'terms', 'column_title', 4 );
		$this->filter_module( 'terms', 'field_tagline_title', 4 );

		$this->filter( 'imports_data_summary', 1, 10, FALSE, $this->base );

		$this->_hook_wp_register_importer();
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'language_taxonomy' ) == $screen->taxonomy ) {

			if ( 'edit-tags' == $screen->base ) {

				$this->action( 'taxonomy_tab_extra_content', 2, 12, FALSE, 'gnetwork' );

			} else if ( 'term' == $screen->base ) {

			}

			$this->filter_string( 'parent_file', 'options-general.php' );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

			} else if ( 'edit' == $screen->base ) {

				if ( $this->role_can( 'reports' ) )
					$this->_hook_screen_restrict_taxonomies();
			}
		}
	}

	protected function get_taxonomies_for_restrict_manage_posts()
	{
		return [ 'language_taxonomy' ];
	}

	public function admin_menu()
	{
		$this->_hook_menu_taxonomy( 'language_taxonomy', 'options-general.php' );
	}

	public function map_meta_cap( $caps, $cap, $user_id, $args )
	{
		$taxonomy = $this->constant( 'language_taxonomy' );

		switch ( $cap ) {

			case 'manage_'.$taxonomy:
			case 'edit_'.$taxonomy:
			case 'delete_'.$taxonomy:

				return $this->role_can( 'manage', $user_id )
					? [ 'read' ]
					: [ 'do_not_allow' ];

				break;

			case 'assign_'.$taxonomy:

				return $this->role_can( 'assign', $user_id )
					? [ 'read' ]
					: [ 'do_not_allow' ];

				break;

			case 'assign_term':

				$term = get_term( (int) $args[0] );

				if ( ! $term || is_wp_error( $term ) )
					return $caps;

				if ( $taxonomy != $term->taxonomy )
					return $caps;

				if ( ! $roles = get_term_meta( $term->term_id, 'roles', TRUE ) )
					return $caps;

				if ( ! WordPress\User::hasRole( array_merge( [ 'administrator' ], (array) $roles ), $user_id ) )
					return [ 'do_not_allow' ];
		}

		return $caps;
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_taxonomy( 'language_taxonomy' ) )
			$items[] = $glance;

		return $items;
	}

	// TODO: move to ModuleInfo
	public function imports_data_summary( $data )
	{
		$data[] = [
			'title'       => $this->imports_datafile,
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

					if ( ! Taxonomy::insertDefaultTerms( $this->constant( 'language_taxonomy' ), $terms, ( $update ? 'not_name' : FALSE ) ) )
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
		HTML::h3( _x( 'Import Language Identifiers', 'Header', 'geditorial-lingo' ) );

		echo '<table class="form-table">';
		echo '<tr><th scope="row">'.HTML::code( _x( 'ISO 639-1 Alpha-2', 'Imports', 'geditorial-lingo' ), 'description' ).'</th><td>';

		if ( $data = $this->get_imports_raw_data() ) {

			HTML::tableList( [
				'_cb'  => 'code',
				'code' => [
					'title' => _x( 'Code', 'Table Column', 'geditorial-lingo' ),
					'class' => '-ltr',
				],
				'term' => [
					'title'    => _x( 'Term', 'Table Column', 'geditorial-lingo' ),
					'class'    => '-ltr',
					'args'     => [ 'taxonomy' => $this->constant( 'language_taxonomy' ) ],
					'callback' => function( $value, $row, $column, $index, $key, $args ) {

						if ( $term = Term::exists( $row['name'], $column['args']['taxonomy'] ) )
							return Helper::getTermTitleRow( $term );

						return Helper::htmlEmpty();
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
				'empty' => HTML::warning( _x( 'There are no language identifiers available!', 'Message: Table Empty', 'geditorial-lingo' ), FALSE ),
			] );

		} else {

			echo gEditorial\Plugin::wrong();
		}

		echo '</td></tr>';
		echo '<tr><th scope="row">&nbsp;</th><td>';
		echo $this->wrap_open_buttons( '-imports' );

		Settings::submitButton( 'language_taxonomy_create',
			_x( 'Create Language Terms', 'Button', 'geditorial-lingo' ), TRUE );

		Settings::submitCheckBox( 'language_taxonomy_update',
			_x( 'Update Existing Terms', 'Button', 'geditorial-lingo' ) );

		echo '</p>';

		HTML::desc( _x( 'Check for available language identifiers and create corresponding terms.', 'Message', 'geditorial-lingo' ) );

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
		return $taxonomy === $this->constant( 'language_taxonomy' )
			? _x( 'Native Name', 'Table Column', 'geditorial-lingo' )
			: $title;
	}

	public function taxonomy_tab_extra_content( $taxonomy, $object )
	{
		$this->render_imports_toolbox_card();
	}
}
