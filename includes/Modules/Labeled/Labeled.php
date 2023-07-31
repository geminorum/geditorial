<?php namespace geminorum\gEditorial\Modules\Labeled;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Template;
use geminorum\gEditorial\WordPress;

class Labeled extends gEditorial\Module
{
	use Internals\CoreCapabilities;
	use Internals\CoreMenuPage;
	use Internals\CoreDashboard;
	use Internals\CoreRestrictPosts;
	use Internals\DashboardSummary;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'labeled',
			'title'    => _x( 'Labeled', 'Modules: Labeled', 'geditorial' ),
			'desc'     => _x( 'Custom Labels for Contents', 'Modules: Labeled', 'geditorial' ),
			'icon'     => 'tag',
			'access'   => 'beta',
			'keywords' => [ 'metafield' ],
		];
	}

	protected function get_global_settings()
	{
		$terms = WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ) );
		$empty = $this->get_taxonomy_label( 'main_taxonomy', 'no_items_available', NULL, 'no_terms' );

		return [
			'posttypes_option' => 'posttypes_option',
			'_roles'    => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy' ),
			'_editpost' => [
				'admin_restrict',
			],
			'_dashboard' => [
				'dashboard_widgets',
				'summary_excludes' => [ NULL, $terms, $empty ],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'label',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Label', 'Labels', 'geditorial-labeled' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'extended_label'       => _x( 'Content Labels', 'Label: `extended_label`', 'geditorial-labeled' ),
					'menu_name'            => _x( 'Content Labels', 'Label: Menu Name', 'geditorial-labeled' ),
					'show_option_all'      => _x( 'Labels', 'Label: Show Option All', 'geditorial-labeled' ),
					'show_option_no_items' => _x( '(Unlabeled)', 'Label: Show Option No Terms', 'geditorial-labeled' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['dashboard'] = [
			'current' => [ 'widget_title' => _x( 'Your Content Labels Summary', 'Dashboard Widget Title', 'geditorial-labeled' ), ],
			'all'     => [ 'widget_title' => _x( 'Content Labels Summary', 'Dashboard Widget Title', 'geditorial-labeled' ), ],
		];

		$strings['default_terms'] = [
			'main_taxonomy' => [
				'introduction' => _x( 'Introduction', 'Main Taxonomy: Default Term', 'geditorial-labeled' ),
				'interview'    => _x( 'Interview', 'Main Taxonomy: Default Term', 'geditorial-labeled' ),
				'review'       => _x( 'Review', 'Main Taxonomy: Default Term', 'geditorial-labeled' ),
				'report'       => _x( 'Report', 'Main Taxonomy: Default Term', 'geditorial-labeled' ),
			],
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'_supported' => [
				'label_string' => [
					'title'       => _x( 'Label', 'Field Title', 'geditorial-labeled' ),
					'description' => _x( 'Text to indicate that the content is part of an editorial column.', 'Field Description', 'geditorial-labeled' ),
				],
				'label_taxonomy' => [
					'title'       => _x( 'Label Taxonomy', 'Field Title', 'geditorial-labeled' ),
					'description' => _x( 'Taxonomy for better categorizing editorial columns.', 'Field Description', 'geditorial-labeled' ),
					'taxonomy'    => $this->constant( 'main_taxonomy' ),
					'type'        => 'term',
				],
			],
		];
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'show_in_menu' => FALSE,
			'show_in_rest' => FALSE,   // temporarily disable in block editor
		], FALSE, TRUE );

		$this->corecaps__init_taxonomy_meta_caps( 'main_taxonomy' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields_supported();

		$this->action_module( 'meta', 'init_posttype_field_label_taxonomy', 3 );
	}

	public function meta_init_posttype_field_label_taxonomy( $field, $field_key, $posttype )
	{
		register_taxonomy_for_object_type( $this->constant( 'main_taxonomy' ), $posttype );
	}

	public function admin_menu()
	{
		$this->_hook_menu_taxonomy( 'main_taxonomy', 'options-general.php' );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'options-general.php' );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit' == $screen->base ) {
				$this->action_module( 'meta', 'column_row', 3, 30 );

				if ( $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) )
					$this->corerestrictposts__hook_screen_taxonomies( 'main_taxonomy' );
			}
		}
	}

	protected function dashboard_widgets()
	{
		if ( ! $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) )
			return;

		$this->add_dashboard_widget( 'term-summary', NULL, 'refresh' );
	}

	public function render_widget_term_summary( $object, $box )
	{
		$this->do_dashboard_term_summary( 'main_taxonomy', $box );
	}

	public function meta_column_row( $post, $fields, $exclude )
	{
		if ( array_key_exists( 'label_string', $fields ) || array_key_exists( 'label_taxonomy', $fields ) )
			Template::metaTermField( [
				'field'    => 'label_string',
				'taxonomy' => $this->constant( 'main_taxonomy' ),
				'before'   => $this->wrap_open_row().$this->get_column_icon( FALSE, $fields['label_string']['icon'], $fields['label_string']['title'] ),
				'after'    => '</li>',
			] );
	}
}