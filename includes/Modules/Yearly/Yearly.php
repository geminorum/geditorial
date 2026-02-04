<?php namespace geminorum\gEditorial\Modules\Yearly;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Yearly extends gEditorial\Module
{
	use Internals\BulkExports;
	use Internals\CoreAdmin;
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\DashboardSummary;
	use Internals\MetaBoxSupported;
	use Internals\TaxonomyOverview;
	use Internals\TemplateTaxonomy;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'yearly',
			'title'    => _x( 'Yearly', 'Modules: Yearly', 'geditorial-admin' ),
			'desc'     => _x( 'Content By Years', 'Modules: Yearly', 'geditorial-admin' ),
			'icon'     => 'clock',
			'access'   => 'beta',
			'keywords' => [
				'calendar',
				'taxmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		$terms = WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ) );
		$empty = $this->get_taxonomy_label( 'main_taxonomy', 'no_items_available', NULL, 'no_terms' );

		return [
			'posttypes_option' => 'posttypes_option',
			'_roles'     => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy', TRUE, TRUE, $terms, $empty ),
			'_dashboard' => [
				'dashboard_widgets',
				'summary_excludes' => [ NULL, $terms, $empty ],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_editpost' => [
				'metabox_advanced',
				'selectmultiple_term',
			],
			'_editlist' => [
				'parents_as_views',
				'admin_restrict',
				'show_in_quickedit' => [ $this->get_taxonomy_show_in_quickedit_desc( 'main_taxonomy' ) ],
			],
			'_frontend' => [
				'contents_viewable',
				'show_in_navmenus',
				'archive_override',
			],
			'_misc' => [
				[
					'field'       => 'append_posttypes',
					'type'        => 'posttypes',
					'title'       => _x( 'Append to Titles', 'Setting Title', 'geditorial-yearly' ),
					'description' => _x( 'Automatically adds a suffix of assigned year to the title of the selected post-types.', 'Settings', 'geditorial-yearly' ),
					'values'      => $this->list_posttypes(),
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'year_span',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Year Span', 'Year Spans', 'geditorial-yearly' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'show_option_all'      => _x( 'Years', 'Label: Show Option All', 'geditorial-yearly' ),
					'show_option_no_items' => _x( '(Undefined)', 'Label: Show Option No Terms', 'geditorial-yearly' ),
				],
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'main_taxonomy' => Datetime::getYears( '-5 years' ),
		];
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_menu'       => FALSE,
			'meta_box_cb'        => $this->get_setting( 'metabox_advanced' ) ? NULL : FALSE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit' ),
			'show_in_nav_menus'  => (bool) $this->get_setting( 'show_in_navmenus' ),
			'data_length'        => _x( '4', 'Main Taxonomy Argument: `data_length`', 'geditorial-yearly' ),
		], NULL, [
			'is_viewable'     => $this->get_setting( 'contents_viewable', TRUE ),
			'single_selected' => ! $this->get_setting( 'selectmultiple_term' ),
			'custom_captype'  => TRUE,
			'reverse_ordered' => 'name',
		] );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );
		$this->coreadmin__ajax_taxonomy_multiple_supported_column( 'main_taxonomy' );
		$this->hook_dashboardsummary_paired_post_summaries( 'main_taxonomy' );
		$this->bulkexports__hook_tabloid_term_assigned( 'main_taxonomy' );

		$this->_init_append_to_title();

		if ( is_admin() )
			return;

		$this->filter( 'posts_clauses', 2, 20, 'orderbyname' );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->_hook_parentfile_for_optionsgeneralphp();
			$this->modulelinks__register_headerbuttons();
			$this->bulkexports__hook_supportedbox_for_term( 'main_taxonomy', $screen );
			$this->coreadmin__hook_taxonomy_multiple_supported_column( $screen );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit' == $screen->base ) {

				if ( $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) ) {

					if ( ! $this->hook_taxonomy_parents_as_views( $screen, 'main_taxonomy' ) )
						$this->corerestrictposts__hook_screen_taxonomies( 'main_taxonomy' );
				}

			} else if ( 'post' === $screen->base ) {

				if ( ! $this->get_setting( 'metabox_advanced' ) )
					$this->hook_taxonomy_metabox_mainbox(
						'main_taxonomy',
						$screen->post_type,
						$this->get_setting( 'selectmultiple_term' )
							? '__checklist_restricted_terms_callback'
							: '__singleselect_restricted_terms_callback'
					);
			}
		}
	}

	private function _init_append_to_title()
	{
		if ( ! $posttypes = $this->get_setting_posttypes( 'append' ) )
			return FALSE;

		$taxonomy = $this->constant( 'main_taxonomy' );

		foreach ( $posttypes as $posttype ) {

			if ( ! $this->posttype_supported( $posttype ) )
				continue;

			add_filter( 'the_title',
				function ( $post_title, $post_id = NULL ) use ( $posttype, $taxonomy ) {

					if ( ! $post = WordPress\Post::get( $post_id ) )
						return $post_title;

					if ( $posttype !== $post->post_type )
						return $post_title;

					if ( ! $terms = WordPress\Taxonomy::getPostTerms( $taxonomy, $post ) )
						return $post_title;

					foreach ( $terms as $term ) {

						if ( ! $name = WordPress\Term::title( $term, FALSE ) )
							continue;

						// TODO: customize the template
						$post_title.= sprintf( ' [%s]', apply_filters( 'string_format_i18n', $name ) );
					}

					return $post_title;

				}, 8, 2 );
		}

		return $posttypes;
	}

	public function admin_menu()
	{
		$this->_hook_menu_taxonomy( 'main_taxonomy', 'options-general.php' );
	}

	public function dashboard_widgets()
	{
		$this->add_dashboard_term_summary( 'main_taxonomy' );
	}

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return $this->_override_module_cuc_by_taxonomy( 'main_taxonomy', $context, $fallback );
	}

	public function template_include( $template )
	{
		return $this->get_setting( 'contents_viewable', TRUE )
			? $this->templatetaxonomy__include( $template, $this->constant( 'main_taxonomy' ) )
			: $template;
	}

	/**
	 * Modifies the SQL query clauses for custom post ordering.
	 * @source https://gist.github.com/wpscholar/ef8fe292b469f59aa9dde644b960c690
	 *
	 * @param array $clauses The list of clauses for the query.
	 * @param WP_Query $query The WP_Query instance.
	 * @return array Modified clauses.
	 */
	public function posts_clauses_orderbyname( array $clauses, \WP_Query $query )
	{
		global $wpdb;

		$orderby  = $query->get( 'orderby' );
		$taxonomy = $this->constant( 'main_taxonomy' );

		if ( $taxonomy === $orderby || ( is_array( $orderby ) && array_key_exists( $taxonomy, $orderby ) ) ) {

			$prefix = str_replace( [ ' ', '-' ], '_', $taxonomy );
			$tr     = esc_sql( "{$prefix}_term_relationships" );
			$tt     = esc_sql( "{$prefix}_term_taxonomy" );
			$t      = esc_sql( "{$prefix}_terms" );

			$clauses['join'] .= <<<SQL
LEFT OUTER JOIN {$wpdb->term_relationships} AS {$tr} ON {$wpdb->posts}.ID={$tr}.object_id
LEFT OUTER JOIN {$wpdb->term_taxonomy} AS {$tt} ON {$tr}.term_taxonomy_id={$tt}.term_taxonomy_id
LEFT OUTER JOIN {$wpdb->terms} AS {$t} ON {$tt}.term_id={$t}.term_id
SQL;

			$clauses['where']  .= " AND (taxonomy = '{$taxonomy}' OR taxonomy IS NULL)";
			$clauses['groupby'] = "{$tr}.object_id";
			$clauses['orderby'] = "GROUP_CONCAT({$t}.name ORDER BY name ASC) ";
			$clauses['orderby'].= ( 'ASC' === strtoupper( $query->get( 'order', 'DESC' ) ) ) ? 'ASC' : 'DESC';
		}

		return $clauses;
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports', 'per_page' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->taxonomy_overview_render_table( 'main_taxonomy', $uri, $sub ) )
			return Info::renderNoReportsAvailable();
	}
}
