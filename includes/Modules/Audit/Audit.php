<?php namespace geminorum\gEditorial\Modules\Audit;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Audit extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\CoreRowActions;
	use Internals\DashboardSummary;
	use Internals\FramePage;
	use Internals\ViewEngines;

	protected $disable_no_posttypes   = TRUE;
	protected $priority_adminbar_init = 8;

	public static function module()
	{
		return [
			'name'     => 'audit',
			'title'    => _x( 'Audit', 'Modules: Audit', 'geditorial-admin' ),
			'desc'     => _x( 'Content Inventory Tools', 'Modules: Audit', 'geditorial-admin' ),
			'icon'     => 'visibility',
			'access'   => 'stable',
			'keywords' => [
				'content-report',
				'has-adminbar',
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
			'_general'         => [
				[
					'field'       => 'auto_audit_empty',
					'title'       => _x( 'Auto-Audit for Empties', 'Setting Title', 'geditorial-audit' ),
					'description' => _x( 'Tries to automatically assign empty attributes on supported posts.', 'Setting Description', 'geditorial-audit' ),
				],
			],
			'_roles'     => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy', TRUE, TRUE, $terms, $empty ),
			'_dashboard' => [
				'dashboard_widgets',
				'summary_parents',
				'summary_excludes' => [ NULL, $terms, $empty ],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_editpost' => [
				'admin_rowactions',
				'admin_bulkactions',
			],
			'_editlist' => [
				'admin_restrict',
				'show_in_quickedit',
			],
			'_frontend' => [
				'adminbar_summary',
				'adminbar_tools',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'audit_attribute',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Audit Attribute', 'Audit Attributes', 'geditorial-audit' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'menu_name'            => _x( 'Content Audit', 'Label: Menu Name', 'geditorial-audit' ),
					'show_option_all'      => _x( 'Audit', 'Label: Show Option All', 'geditorial-audit' ),
					'show_option_no_items' => _x( '(Unaudited)', 'Label: Show Option No Terms', 'geditorial-audit' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			/* translators: `%1$s`: current post title, `%2$s`: posttype singular name */
			'rowaction_title' => _x( 'Audit Attributes of %1$s', 'Action Title', 'geditorial-audit' ),
			/* translators: `%1$s`: icon markup, `%2$s`: posttype singular name */
			'rowaction_text'  => _x( 'Audit', 'Action Text', 'geditorial-audit' ),

			/* translators: `%1$s`: current post title, `%2$s`: posttype singular name */
			'columnrow_title' => _x( 'Audit Attributes of %1$s', 'Row Title', 'geditorial-audit' ),
			/* translators: `%1$s`: icon markup, `%2$s`: posttype singular name */
			'columnrow_text'  => _x( 'Audit', 'Row Text', 'geditorial-audit' ),
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'main_taxonomy' => [
				'audited'       => _x( 'Audited', 'Default Term', 'geditorial-audit' ),
				'outdated'      => _x( 'Outdated', 'Default Term', 'geditorial-audit' ),
				'redundant'     => _x( 'Redundant', 'Default Term', 'geditorial-audit' ),
				'review-seo'    => _x( 'Review SEO', 'Default Term', 'geditorial-audit' ),
				'review-style'  => _x( 'Review Style', 'Default Term', 'geditorial-audit' ),
				'trivial'       => _x( 'Trivial', 'Default Term', 'geditorial-audit' ),
				'initial-copy'  => _x( 'Initial Copy', 'Default Term', 'geditorial-audit' ),
				'unfinished'    => _x( 'Unfinished', 'Default Term', 'geditorial-audit' ),
				'title-empty'   => _x( 'No Title', 'Default Term', 'geditorial-audit' ),
				'text-empty'    => _x( 'No Content', 'Default Term', 'geditorial-audit' ),
				'excerpt-empty' => _x( 'No Excerpt', 'Default Term', 'geditorial-audit' ),
				'imported'      => _x( 'Imported', 'Default Term', 'geditorial-audit' ),
			],
		];
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'public'             => FALSE,
			'rewrite'            => FALSE,
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit' ),
			'show_in_menu'       => FALSE,
			'meta_box_cb'        => '__checklist_restricted_terms_callback',
		], NULL, [
			'custom_captype' => TRUE,
		] );

		$this->hook_taxonomy_tabloid_exclude_rendered( 'main_taxonomy' );
		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );
		$this->action( 'save_post', 3, 99 );

		if ( $this->get_setting( 'auto_audit_empty' ) )
			$this->filter_self( 'auto_audit_save_post', 5 );
	}

	public function do_ajax()
	{
		$post = self::unslash( $_POST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		if ( empty( $post['post_id'] ) )
			gEditorial\Ajax::errorMessage();

		if ( ! current_user_can( 'edit_post', $post['post_id'] ) )
			gEditorial\Ajax::errorUserCant();

		gEditorial\Ajax::checkReferer( $this->hook( $post['post_id'] ) );

		switch ( $what ) {

			case 'list':

				gEditorial\Ajax::success( $this->get_adminbar_checklist( $post['post_id'] ) );

			break;
			case 'store':

				$taxonomy = $this->constant( 'main_taxonomy' );

				parse_str( $post['data'], $data );

				$terms = empty( $data['tax_input'][$taxonomy] ) ? [] : $data['tax_input'][$taxonomy];

				wp_set_object_terms( $post['post_id'], Core\Arraay::prepNumeral( $terms ), $taxonomy, FALSE );
				clean_object_term_cache( $post['post_id'], $taxonomy );

				gEditorial\Ajax::success( $this->get_adminbar_checklist( $post['post_id'] ) );
		}

		gEditorial\Ajax::errorWhat();
	}

	private function get_adminbar_checklist( $post_id )
	{
		require_once ABSPATH.'wp-admin/includes/template.php';

		$html = wp_terms_checklist( $post_id, [
			'taxonomy'      => $this->constant( 'main_taxonomy' ),
			'checked_ontop' => FALSE,
			'echo'          => FALSE,
		] );

		return Core\HTML::wrap( Core\HTML::tag( 'ul', $html ), '-assignnbox' );
	}

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return $this->_override_module_cuc_by_taxonomy( 'main_taxonomy', $context, $fallback );
	}

	public function save_post( $post_id, $post, $update )
	{
		if ( ! empty( $this->process_disabled['import'] ) )
			return;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return;

		if ( ! in_array( $post->post_status, WordPress\Status::acceptable( $post->post_type, 'audit' ), TRUE ) )
			return;

		if ( FALSE !== ModuleHelper::doAutoAuditPost( $post, $update ) )
			clean_object_term_cache( $post->ID, $this->constant( 'main_taxonomy' ) );
	}

	public function auto_audit_save_post( $terms, $post, $taxonomy, $currents, $update )
	{
		if ( $exists = term_exists( ModuleHelper::getAttributeSlug( 'empty_title' ), $taxonomy ) ) {

			if ( empty( $post->post_title ) )
				$terms[] = $exists['term_id'];

			else
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );
		}

		if ( $exists = term_exists( ModuleHelper::getAttributeSlug( 'empty_content' ), $taxonomy ) ) {

			if ( empty( $post->post_content ) )
				$terms[] = $exists['term_id'];

			else
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );
		}

		if ( $exists = term_exists( ModuleHelper::getAttributeSlug( 'empty_excerpt' ), $taxonomy ) ) {

			if ( empty( $post->post_excerpt ) )
				$terms[] = $exists['term_id'];

			else
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );
		}

		return $terms;
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( ! $post = $this->adminbar__check_singular_post( NULL, 'edit_post' ) )
			return;

		$node_id  = $this->classs();
		$icon     = $this->adminbar__get_icon();
		$taxonomy = $this->constant( 'main_taxonomy' );
		$reports  = $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' );
		$terms    = [];

		if ( $reports ) {

			$nodes[] = [
				'parent' => $parent,
				'id'     => $node_id,
				'title'  => $icon._x( 'Audit Attributes', 'Node: Title', 'geditorial-audit' ),
				'href'   => $this->get_module_url(),
				'meta'   => [
					'class' => $this->adminbar__get_css_class(),
				],
			];

			if ( $terms = WordPress\Taxonomy::getPostTerms( $taxonomy, $post ) )
				foreach ( $terms as $term )
					$nodes[] = [
						'parent' => $node_id,
						'id'     => $this->classs( 'attribute', $term->term_id ),
						'title'  => WordPress\Term::title( $term ),
						'href'   => WordPress\Term::link( $term ),
						'meta'   => [
							'class' => $this->adminbar__get_css_class(),
						],
					];

			else
				$nodes[] = [
					'parent' => $node_id,
					'id'     => $this->classs( 'attribute', 0 ),
					'title'  => Services\CustomTaxonomy::getLabel( $taxonomy, 'show_option_no_items' ),
					'href'   => FALSE,
					'meta'   => [
						'class' => $this->adminbar__get_css_class( [ '-not-linked', '-danger' ] ),
					],
				];
		}

		if ( ! $this->get_setting( 'adminbar_tools' ) )
			return;

		if ( ! $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'assign' ) )
			return;

		if ( ! WordPress\Taxonomy::hasTerms( $taxonomy ) )
			return;

		$this->action( 'admin_bar_menu', 1, 1001 );

		$this->enqueue_asset_js( [
			'post_id' => $post->ID,
			'_nonce'  => wp_create_nonce( $this->hook( $post->ID ) ),
		], $this->dotted( 'adminbar' ) );
	}

	public function admin_bar_menu( $wp_admin_bar )
	{
		$node_id = $this->classs( 'assignbox' );
		$spinner = $this->adminbar__get_spinner();
		$icon    = Services\Icons::adminBarMarkup( 'visibility' );  // better visuals than `adminbar__get_icon()`

		$wp_admin_bar->add_node( [
			'id'    => $node_id,
			'href'  => $this->get_module_url(),
			'title' => $icon._x( 'Auditing', 'Node: Title', 'geditorial-audit' ).$spinner,
			'meta'  => [
				'rel'   => $this->constant( 'main_taxonomy' ),
				'class' => $this->adminbar__get_css_class( [ '-has-icon', '-has-loading' ] ),
			],
		] );

		$wp_admin_bar->add_node( [
			'parent' => $node_id,
			'id'     => $this->classs( 'assignbox', 'content' ),
			'title'  => _x( 'Click to load attributes &hellip;', 'Node: Title', 'geditorial-audit' ),
			'meta'   => [
				'class' => $this->adminbar__get_css_class( '-has-assignbox' ),
			],
		] );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'options-general.php' );
			$this->modulelinks__register_headerbuttons();

			if ( 'edit-tags' == $screen->base ) {

				$this->filter( 'taxonomy_empty_terms', 2, 8, FALSE, 'gnetwork' );

				$this->action( 'taxonomy_tab_extra_content', 2, 20, FALSE, 'gnetwork' );
				$this->action( 'taxonomy_handle_tab_content_actions', 1, 8, FALSE, 'gnetwork' );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit' == $screen->base ) {

				if ( $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) ) {
					$this->corerestrictposts__hook_screen_taxonomies( 'main_taxonomy', FALSE, 5 );

					// TODO: fallback to custom tweaks column/hide on tweaks default
					if ( $this->rowactions__hook_mainlink_for_post( $screen->post_type, 20 ) )
						gEditorial\Scripts::enqueueColorBox();
				}

				if ( $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'assign' ) )
					$this->rowactions__hook_admin_bulkactions( $screen, TRUE );
			}
		}
	}

	public function admin_menu()
	{
		$this->_hook_submenu_adminpage( 'overview' );
		$this->_hook_menu_taxonomy( 'main_taxonomy', 'options-general.php' );
	}

	public function render_submenu_adminpage()
	{
		$this->render_default_mainpage( 'overview', 'update' );
	}

	public function dashboard_widgets()
	{
		$this->add_dashboard_term_summary( 'main_taxonomy' );
	}

	protected function rowaction_get_mainlink_for_post( $post )
	{
		return [
			$this->classs().' hide-if-no-js' => $this->framepage_get_mainlink_for_post( $post, [
				'context'      => 'rowaction',
				'link_context' => 'overview',
				'maxwidth'     => '920px',
				'extra'        => [
					'-audit-overview',
				]
			] ),
		];
	}

	protected function render_overview_content()
	{
		if ( ! $linked = self::req( 'linked' ) )
			return gEditorial\Info::renderNoPostsAvailable();

		if ( ! $post = WordPress\Post::get( $linked ) )
			return gEditorial\Info::renderNoPostsAvailable();

		$this->_render_view_for_post( $post, 'overview' );
	}

	public function rowactions_bulk_actions( $actions )
	{
		return array_merge( $actions, [
			$this->hook( 'forceautoaudit' ) => _x( 'Force Auto-Audit', 'Action', 'geditorial-audit' ),
		] );
	}

	public function rowactions_handle_bulk_actions( $redirect_to, $doaction, $post_ids )
	{
		if ( $doaction != $this->hook( 'forceautoaudit' ) )
			return $redirect_to;

		if ( ! $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'assign' ) )
			return $redirect_to;

		$count    = 0;
		$taxonomy = $this->constant( 'main_taxonomy' );

		foreach ( $post_ids as $post_id ) {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				continue;

			if ( ModuleHelper::doAutoAuditPost( $post_id, TRUE, $taxonomy ) )
				++$count;
		}

		return add_query_arg( $this->hook( 'audited' ), $count, $redirect_to );
	}

	public function rowactions_admin_notices()
	{
		$hook = $this->hook( 'audited' );

		if ( ! $count = self::req( $hook ) )
			return;

		$_SERVER['REQUEST_URI'] = remove_query_arg( $hook, $_SERVER['REQUEST_URI'] );

		echo Core\HTML::success( sprintf(
			/* translators: `%s`: count */
			_x( '%s items(s) Audited!', 'Message', 'geditorial-audit' ),
			Core\Number::format( $count )
		) );
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools', 'per_page' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( $action = self::req( ModuleSettings::ACTION_EMPTY_FIELDS_AUDIT ) ) {

					$this->raise_resources();

					ModuleSettings::handleToolsEmptyFields( $action, $this->constant( 'main_taxonomy' ) );

				} else {

					WordPress\Redirect::doReferer( 'huh' );
				}
			}
		}
	}

	public function taxonomy_handle_tab_content_actions( $taxonomy )
	{
		if ( ! $action = self::req( ModuleSettings::ACTION_EMPTY_FIELDS_AUDIT ) )
			return;

		$this->nonce_check( 'do-empty-fields' );
		$this->raise_resources();

		ModuleSettings::handleToolsEmptyFields( $action, $this->constant( 'main_taxonomy' ) );
	}

	protected function render_tools_html( $uri, $sub )
	{
		echo gEditorial\Settings::toolboxColumnOpen( _x( 'Content Audit Tools', 'Header', 'geditorial-audit' ) );

		$available = FALSE;
		$taxonomy  = $this->constant( 'main_taxonomy' );
		$posttypes = $this->list_posttypes();

		if ( ModuleSettings::renderToolsEmptyFields( $posttypes, $taxonomy ) )
			$available = TRUE;

		if ( ModuleSettings::renderCard_force_auto_audit( $posttypes, $taxonomy ) )
			$available = TRUE;

		if ( ! $available )
			gEditorial\Info::renderNoToolsAvailable();

		echo '</div>';
	}

	protected function render_tools_html_before( $uri, $sub )
	{
		if ( $this->_do_tools_force_auto_audit( $sub ) )
			return FALSE; // avoid further UI
	}

	public function taxonomy_empty_terms( $terms, $taxonomy )
	{
		if ( empty( $terms ) || ! $taxonomy )
			return $terms;

		if ( $taxonomy != $this->constant( 'main_taxonomy' ) )
			return $terms;

		foreach ( [ 'empty_title', 'empty_content', 'empty_excerpt' ] as $for )
			if ( $exists = term_exists( ModuleHelper::getAttributeSlug( $for ), $taxonomy ) )
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

		return $terms;
	}

	public function taxonomy_tab_extra_content( $taxonomy, $object )
	{
		$this->render_form_start( NULL, 'empty-fields', 'extra', 'tabs' );
			$this->nonce_field( 'do-empty-fields' );
			ModuleSettings::renderToolsEmptyFields( $this->list_posttypes(), $this->constant( 'main_taxonomy' ), TRUE );
		$this->render_form_end( NULL, 'empty-fields', 'extra', 'tabs' );
	}

	private function _do_tools_force_auto_audit( $sub )
	{
		if ( ! self::do( ModuleSettings::ACTION_FORCE_AUTO_AUDIT ) )
			return FALSE;

		if ( ! $posttype = self::req( 'type' ) )
			return ! gEditorial\Info::renderEmptyPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $this->posttype_supported( $posttype ) )
			return ! gEditorial\Info::renderNotSupportedPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		$this->raise_resources();

		return ModuleSettings::handleTool_force_auto_audit(
			$posttype,
			$this->constant( 'main_taxonomy' ),
			$this->get_sub_limit_option( $sub, 'tools' )
		);
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		Core\HTML::h3( _x( 'Audit Reports', 'Header', 'geditorial-audit' ) );

		if ( ! WordPress\Taxonomy::hasTerms( $this->constant( 'main_taxonomy' ) ) )
			return gEditorial\Info::renderNoReportsAvailable();

		$this->_render_reports_by_user_summary();
	}

	// TODO: export option
	// TODO: move to `ModuleSettings`
	private function _render_reports_by_user_summary()
	{
		$args = $this->get_current_form( [
			'user_id' => '0',
		], 'reports' );

		echo gEditorial\Settings::toolboxCardOpen( _x( 'Summary by User', 'Card Title', 'geditorial-audit' ) );

		$this->do_settings_field( [
			'type'         => 'user',
			'field'        => 'user_id',
			'none_title'   => _x( 'All Users', 'Card: None-Title', 'geditorial-audit' ),
			'none_value'   => '0',
			'default'      => $args['user_id'],
			'option_group' => 'reports',
			'cap'          => TRUE,
		] );

		gEditorial\Settings::submitButton( 'user_stats', _x( 'Apply Filter', 'Card: Button', 'geditorial-audit' ) );
		echo '</div>';

		if ( $summary = $this->get_dashboard_term_summary( 'main_taxonomy', NULL, NULL, ( $args['user_id'] ? 'current' : 'all' ), $args['user_id'] ) )
			echo '<div><ul class="-wrap-list-items">'.$summary.'</ul></div>';

		echo '</div>';
	}

	private function _render_view_for_post( $post, $context )
	{
		if ( ! $view = $this->viewengine__view_by_post( $post, $context ) )
			return gEditorial\Info::renderSomethingIsWrong();

		$data = $this->_get_view_data_for_post( $post, $context );

		echo $this->wrap_open( '-view -'.$context );
			$this->actions( 'render_view_post_before', $post, $context, $data, $view );
			$this->viewengine__render( $view, $data );
			$this->actions( 'render_view_post_after', $post, $context, $data, $view );
		echo '</div>';

		// $this->_print_script( $post, $context, $data );

		echo $this->wrap_open( '-debug -debug-data', TRUE, $this->classs( 'raw' ), TRUE );
			Core\HTML::tableSide( $data );
		echo '</div>';
	}

	private function _get_view_data_for_post( $post, $context )
	{
		$data = [];

		if ( $post_route = WordPress\Post::getRestRoute( $post ) )
			$data['post'] = WordPress\Rest::doInternalRequest( $post_route, [ 'context' => 'view' ] );

		// fallback if `title` is not supported by the posttype
		if ( empty( $data['post']['title'] ) )
			$data['post']['title'] = [ 'rendered' => WordPress\Post::title( $post ) ];

		if ( $terms_route = WordPress\Taxonomy::getRestRoute( $this->constant( 'main_taxonomy' ) ) )
			$data['terms'] = WordPress\Rest::doInternalRequest( $terms_route, [ 'post' => $post->ID, 'context' => 'view' ] );

		foreach ( $data['terms'] as &$term ) {
			unset( $term['_links'] );
		}

		$data['__direction']  = Core\L10n::rtl() ? 'rtl' : 'ltr';
		$data['__can_debug']  = WordPress\IsIt::dev() || WordPress\User::isSuperAdmin();
		// $data['__summaries']  = $this->filters( 'post_summaries', [], $data, $post, $context );

		return $this->filters( 'view_data_for_post', $data, $post, $context );
	}

	protected function raise_resources( $count = 1, $per = 60, $context = NULL )
	{
		WordPress\Taxonomy::disableTermCounting();
		Services\LateChores::termCountCollect();

		if ( ! WordPress\IsIt::dev() )
			do_action( 'qm/cease' ); // Query Monitor: Cease data collections

		return $this->raise_memory_limit( $count, $per, $context ?? 'audit' );
	}
}
