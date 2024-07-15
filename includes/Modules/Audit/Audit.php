<?php namespace geminorum\gEditorial\Modules\Audit;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Tablelist;
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

	protected $disable_no_posttypes = TRUE;

	protected $caps = [
		'default' => 'edit_others_posts',
	];

	public static function module()
	{
		return [
			'name'     => 'audit',
			'title'    => _x( 'Audit', 'Modules: Audit', 'geditorial-admin' ),
			'desc'     => _x( 'Content Inventory Tools', 'Modules: Audit', 'geditorial-admin' ),
			'icon'     => 'visibility',
			'access'   => 'stable',
			'keywords' => [
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
			'_general' => [
				[
					'field'       => 'auto_audit_empty',
					'title'       => _x( 'Auto-Audit for Empties', 'Setting Title', 'geditorial-audit' ),
					'description' => _x( 'Tries to automatically assign empty attributes on supported posts.', 'Setting Description', 'geditorial-audit' ),
				],
			],
			'_roles'     => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy', TRUE, TRUE, $terms, $empty ),
			'_dashboard' => [
				'dashboard_widgets',
				'summary_excludes' => [ NULL, $terms, $empty ],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_editpost' => [
				'admin_rowactions',
				'admin_bulkactions',
			],
			'_frontend' => [
				'adminbar_summary',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'audit_attribute',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'main_taxonomy' => NULL,
			],
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

		$strings['dashboard'] = [
			'current' => [ 'widget_title' => _x( 'Your Audit Summary', 'Dashboard Widget Title', 'geditorial-audit' ), ],
			'all'     => [ 'widget_title' => _x( 'Editorial Audit Summary', 'Dashboard Widget Title', 'geditorial-audit' ), ],
		];

		$strings['metabox'] = [
			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'rowaction_title' => _x( 'Audit Attributes of %1$s', 'Action Title', 'geditorial-audit' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'rowaction_text'  => _x( 'Audit', 'Action Text', 'geditorial-audit' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'columnrow_title' => _x( 'Audit Attributes of %1$s', 'Row Title', 'geditorial-audit' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
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
			'show_in_quick_edit' => TRUE,
			'show_in_menu'       => FALSE,
			'meta_box_cb'        => '__checklist_restricted_terms_callback',
		], NULL, [
			'custom_captype' => TRUE,
		] );

		$this->filter_module( 'tabloid', 'view_data', 3, 9, 'strip_audits' );
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
			Ajax::errorMessage();

		if ( ! current_user_can( 'edit_post', $post['post_id'] ) )
			Ajax::errorUserCant();

		Ajax::checkReferer( $this->hook( $post['post_id'] ) );

		switch ( $what ) {

			case 'list':

				Ajax::success( $this->get_adminbar_checklist( $post['post_id'] ) );

			break;
			case 'store':

				$taxonomy = $this->constant( 'main_taxonomy' );

				parse_str( $post['data'], $data );

				$terms = empty( $data['tax_input'][$taxonomy] ) ? [] : $data['tax_input'][$taxonomy];

				wp_set_object_terms( $post['post_id'], Core\Arraay::prepNumeral( $terms ), $taxonomy, FALSE );
				clean_object_term_cache( $post['post_id'], $taxonomy );

				Ajax::success( $this->get_adminbar_checklist( $post['post_id'] ) );
		}

		Ajax::errorWhat();
	}

	private function get_adminbar_checklist( $post_id )
	{
		require_once ABSPATH.'wp-admin/includes/template.php';

		$html = wp_terms_checklist( $post_id, [
			'taxonomy'      => $this->constant( 'main_taxonomy' ),
			'checked_ontop' => FALSE,
			'echo'          => FALSE,
		] );

		return Core\HTML::wrap( '<ul>'.$html.'</ul>', 'geditorial-adminbar-box-wrap' );
	}

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return 'reports' == $context
			? $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports', NULL, $fallback )
			: parent::cuc( $context, $fallback );
	}

	public function save_post( $post_id, $post, $update )
	{
		if ( ! empty( $this->process_disabled['import'] ) )
			return;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return;

		if ( ! in_array( $post->post_status, WordPress\Status::acceptable( $post->post_type, 'audit' ), TRUE ) )
			return;

		if ( FALSE !== $this->_do_auto_audit_post( $post, $update ) )
			clean_object_term_cache( $post->ID, $this->constant( 'main_taxonomy' ) );
	}

	private function _do_auto_audit_post( $post, $update = FALSE, $taxonomy = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$taxonomy = $taxonomy ?? $this->constant( 'main_taxonomy' );
		$currents = WordPress\Taxonomy::getObjectTerms( $taxonomy, $post->ID );

		$terms = $this->filters( 'auto_audit_save_post', $currents, $post, $taxonomy, $currents, $update );
		$terms = Core\Arraay::prepNumeral( $terms );

		if ( Core\Arraay::equalNoneAssoc( $terms, $currents ) )
			return NULL;

		$result = wp_set_object_terms( $post->ID, $terms, $taxonomy );

		return self::isError( $result ) ? FALSE : $result;
	}

	public function auto_audit_save_post( $terms, $post, $taxonomy, $currents, $update )
	{
		if ( $exists = term_exists( $this->_get_attribute( 'empty_title' ), $taxonomy ) ) {

			if ( empty( $post->post_title ) )
				$terms[] = $exists['term_id'];

			else
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );
		}

		if ( $exists = term_exists( $this->_get_attribute( 'empty_content' ), $taxonomy ) ) {

			if ( empty( $post->post_content ) )
				$terms[] = $exists['term_id'];

			else
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );
		}

		if ( $exists = term_exists( $this->_get_attribute( 'empty_excerpt' ), $taxonomy ) ) {

			if ( empty( $post->post_excerpt ) )
				$terms[] = $exists['term_id'];

			else
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );
		}

		return $terms;
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() || ! is_singular( $this->posttypes() ) )
			return;

		$post_id  = get_queried_object_id();
		$classs   = $this->classs();
		$taxonomy = $this->constant( 'main_taxonomy' );
		$terms    = [];

		if ( $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' )
			|| current_user_can( 'edit_post', $post_id ) ) {

			$nodes[] = [
				'id'     => $classs,
				'title'  => _x( 'Audit Attributes', 'Adminbar', 'geditorial-audit' ),
				'parent' => $parent,
				'href'   => $this->get_module_url(),
			];

			if ( $terms = WordPress\Taxonomy::getPostTerms( $taxonomy, $post_id ) )
				foreach ( $terms as $term )
					$nodes[] = [
						'id'     => $this->classs( 'attribute', $term->term_id ),
						'parent' => $classs,
						'title'  => sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ),
						'href'   => get_term_link( $term ),
					];

			else
				$nodes[] = [
					'id'     => $this->classs( 'attribute', 0 ),
					'parent' => $classs,
					'title'  => Helper::getTaxonomyLabel( $taxonomy, 'show_option_no_items' ),
					'href'   => FALSE,
					'meta'   => [ 'class' => '-danger '.$classs ],
				];
		}

		if ( ! $this->role_can( 'assign' ) )
			return;

		if ( empty( $terms ) && ! WordPress\Taxonomy::hasTerms( $taxonomy ) )
			return;

		$this->action( 'admin_bar_menu', 1, 699 );

		$this->enqueue_styles();
		$this->enqueue_asset_js( [
			'post_id' => $post_id,
			'_nonce'  => wp_create_nonce( $this->hook( $post_id ) ),
		] );
	}

	// @SEE: https://core.trac.wordpress.org/ticket/38636
	public function admin_bar_menu( $wp_admin_bar )
	{
		// $post_id = get_queried_object_id();

		$wp_admin_bar->add_node( [
			'id'    => $this->classs( 'attributes' ),
			'href'  => $this->get_module_url(),
			'title' => _x( 'Auditing', 'Adminbar: Title Attr', 'geditorial-audit' ).Ajax::spinner(),
			'meta'  => [
				'class' => 'geditorial-adminbar-node -action quick-assign-action '.$this->classs(),
				// working but not implemented on js yet!
				// 'html'  => Core\HTML::tag( 'span', [
				// 	'class' => 'quick-assign-data',
				// 	'data'  => [
				// 		'post'     => $post_id,
				// 		'taxonomy' => $this->constant( 'main_taxonomy' ),
				// 		'nonce'    => wp_create_nonce( $this->hook( $post_id ) ),
				// 	],
				// ] ),
			],
		] );

		$wp_admin_bar->add_node( [
			'id'     => $this->classs( 'box' ),
			'parent' => $this->classs( 'attributes' ),
			'title'  => _x( 'Click to load attributes &hellip;', 'Adminbar: Title Attr', 'geditorial-audit' ),
			'meta'   => [ 'class' => 'geditorial-adminbar-wrap -wrap quick-assign-box '.$this->classs() ],
		] );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'options-general.php' );

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
						Scripts::enqueueColorBox();
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
		if ( ! $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) )
			return;

		$this->add_dashboard_widget( 'term-summary', NULL, 'refresh' );
	}

	public function render_widget_term_summary( $object, $box )
	{
		$this->do_dashboard_term_summary( 'main_taxonomy', $box );
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
			return Info::renderNoPostsAvailable();

		if ( ! $post = WordPress\Post::get( $linked ) )
			return Info::renderNoPostsAvailable();

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

			if ( $this->_do_auto_audit_post( $post_id, TRUE, $taxonomy ) )
				$count++;
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
			/* translators: %s: count */
			_x( '%s items(s) Audited!', 'Message', 'geditorial-audit' ),
			Core\Number::format( $count )
		) );
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools', 'per_page' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( $action = self::req( $this->classs( 'empty-fields' ) ) )
					$this->_handle_action_empty_fields( $action );

				Core\WordPress::redirectReferer( 'huh' );
			}
		}
	}

	public function taxonomy_handle_tab_content_actions( $taxonomy )
	{
		if ( ! $action = self::req( $this->classs( 'empty-fields' ) ) )
			return;

		$this->nonce_check( 'do-empty-fields' );
		$this->_handle_action_empty_fields( $action );
	}

	// NOTE: must check nonce before
	private function _handle_action_empty_fields( $action )
	{
		$taxonomy = $this->constant( 'main_taxonomy' );

		$this->raise_resources();

		switch ( Core\Arraay::keyFirst( $action ) ) {

			case 'flush_empty_title':

				$attribute = $this->_get_attribute( 'empty_title' );

				if ( FALSE === ( $count = WordPress\Taxonomy::removeTermObjects( $attribute, $taxonomy ) ) )
					Core\WordPress::redirectReferer( 'wrong' );

				$this->_update_term_count( $attribute, $taxonomy );

				Core\WordPress::redirectReferer( [
					'message' => 'emptied',
					'count'   => $count,
				] );
				break;

			case 'flush_empty_content':

				$attribute = $this->_get_attribute( 'empty_content' );

				if ( FALSE === ( $count = WordPress\Taxonomy::removeTermObjects( $attribute, $taxonomy ) ) )
					Core\WordPress::redirectReferer( 'wrong' );

				$this->_update_term_count( $attribute, $taxonomy );

				Core\WordPress::redirectReferer( [
					'message' => 'emptied',
					'count'   => $count,
				] );
				break;

			case 'flush_empty_excerpt':

				$attribute = $this->_get_attribute( 'empty_excerpt' );

				if ( FALSE === ( $count = WordPress\Taxonomy::removeTermObjects( $attribute, $taxonomy ) ) )
					Core\WordPress::redirectReferer( 'wrong' );

				$this->_update_term_count( $attribute, $taxonomy );

				Core\WordPress::redirectReferer( [
					'message' => 'emptied',
					'count'   => $count,
				] );
				break;

			case 'mark_empty_title':

				$posttypes = self::req( 'posttype-empty-title' ) ?: NULL;
				$attribute = $this->_get_attribute( 'empty_title' );

				if ( FALSE === ( $posts = $this->_get_posts_empty( 'title', $attribute, $posttypes ) ) )
					Core\WordPress::redirectReferer( 'wrong' );

				if ( empty( $posts ) )
					Core\WordPress::redirectReferer( 'nochange' );

				if ( FALSE === ( $count = WordPress\Taxonomy::setTermObjects( $posts, $attribute, $taxonomy ) ) )
					Core\WordPress::redirectReferer( 'wrong' );

				$this->_update_term_count( $attribute, $taxonomy );

				Core\WordPress::redirectReferer( [
					'message' => 'synced',
					'count'   => $count,
				] );
				break;

			case 'mark_empty_content':

				$posttypes = self::req( 'posttype-empty-content' ) ?: NULL;
				$attribute = $this->_get_attribute( 'empty_content' );

				if ( FALSE === ( $posts = $this->_get_posts_empty( 'content', $attribute, $posttypes ) ) )
					Core\WordPress::redirectReferer( 'wrong' );

				if ( empty( $posts ) )
					Core\WordPress::redirectReferer( 'nochange' );

				if ( FALSE === ( $count = WordPress\Taxonomy::setTermObjects( $posts, $attribute, $taxonomy ) ) )
					Core\WordPress::redirectReferer( 'wrong' );

				$this->_update_term_count( $attribute, $taxonomy );

				Core\WordPress::redirectReferer( [
					'message' => 'synced',
					'count'   => $count,
				] );
				break;

			case 'mark_empty_excerpt':

				$posttypes = self::req( 'posttype-empty-excerpt' ) ?: NULL;
				$attribute = $this->_get_attribute( 'empty_excerpt' );

				if ( FALSE === ( $posts = $this->_get_posts_empty( 'excerpt', $attribute, $posttypes ) ) )
					Core\WordPress::redirectReferer( 'wrong' );

				if ( empty( $posts ) )
					Core\WordPress::redirectReferer( 'nochange' );

				if ( FALSE === ( $count = WordPress\Taxonomy::setTermObjects( $posts, $attribute, $taxonomy ) ) )
					Core\WordPress::redirectReferer( 'wrong' );

				$this->_update_term_count( $attribute, $taxonomy );

				Core\WordPress::redirectReferer( [
					'message' => 'synced',
					'count'   => $count,
				] );
				break;
		}

		Core\WordPress::redirectReferer( 'huh' );
	}

	protected function render_tools_html( $uri, $sub )
	{
		echo Settings::toolboxColumnOpen( _x( 'Content Audit Tools', 'Header', 'geditorial-audit' ) );

		if ( $this->_do_tools_force_auto_audit( $sub ) )
			return;

		$this->_render_tools_empty_fields();
		$this->_render_tools_force_auto_audit();

		echo '</div>';
	}

	public function taxonomy_empty_terms( $terms, $taxonomy )
	{
		if ( empty( $terms ) || ! $taxonomy )
			return $terms;

		if ( $taxonomy != $this->constant( 'main_taxonomy' ) )
			return $terms;

		foreach ( [ 'empty_title', 'empty_content', 'empty_excerpt' ] as $for )
			if ( $exists = term_exists( $this->_get_attribute( $for ), $taxonomy ) )
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

		return $terms;
	}

	public function taxonomy_tab_extra_content( $taxonomy, $object )
	{
		$this->render_form_start( NULL, 'empty-fields', 'extra', 'tabs', FALSE );
			$this->nonce_field( 'do-empty-fields' );
			$this->_render_tools_empty_fields( TRUE );
		$this->render_form_end( NULL, 'empty-fields', 'extra', 'tabs' );
	}

	// TODO: auto-audit: mark no thumbnail with selected attribiute
	private function _render_tools_empty_fields( $lite = FALSE )
	{
		$taxonomy = $this->constant( 'main_taxonomy' );
		$action   = $this->classs( 'empty-fields' );
		$empty    = TRUE;

		echo $this->wrap_open( [ 'card', '-toolbox-card' ] );

		if ( term_exists( $this->_get_attribute( 'empty_title' ), $taxonomy ) ) {

			Core\HTML::h4( _x( 'Posts with Empty Title', 'Card Title', 'geditorial-audit' ), 'title' );
			if ( ! $lite ) $this->_render_tools_empty_fields_summary( 'empty_title' );

			echo $this->wrap_open( '-wrap-button-row' );
			echo Core\HTML::dropdown( $this->list_posttypes(), [
				'name'       => 'posttype-empty-title',
				'none_title' => _x( 'All Supported Post-Types', 'Card: None-Title', 'geditorial-audit' ),
			] );

			Settings::submitButton( $action.'[mark_empty_title]', _x( 'Mark Posts', 'Card: Button', 'geditorial-audit' ) );
			Settings::submitButton( $action.'[flush_empty_title]', _x( 'Flush Attribute', 'Card: Button', 'geditorial-audit' ), 'danger', TRUE, '' );
			Core\HTML::desc( _x( 'Tries to set the attribute on supported posts with no title.', 'Card: Description', 'geditorial-audit' ) );

			echo '</div>';
			$empty = FALSE;
		}

		if ( term_exists( $this->_get_attribute( 'empty_content' ), $taxonomy ) ) {

			Core\HTML::h4( _x( 'Posts with Empty Content', 'Card Title', 'geditorial-audit' ), 'title' );
			if ( ! $lite ) $this->_render_tools_empty_fields_summary( 'empty_content' );

			echo $this->wrap_open( '-wrap-button-row' );
			echo Core\HTML::dropdown( $this->list_posttypes(), [
				'name'       => 'posttype-empty-content',
				'none_title' => _x( 'All Supported Post-Types', 'Card: None-Title', 'geditorial-audit' ),
			] );

			Settings::submitButton( $action.'[mark_empty_content]', _x( 'Mark Posts', 'Card: Button', 'geditorial-audit' ) );
			Settings::submitButton( $action.'[flush_empty_content]', _x( 'Flush Attribute', 'Card: Button', 'geditorial-audit' ), 'danger', TRUE, '' );
			Core\HTML::desc( _x( 'Tries to set the attribute on supported posts with no content.', 'Card: Description', 'geditorial-audit' ) );

			echo '</div>';
			$empty = FALSE;
		}

		if ( term_exists( $this->_get_attribute( 'empty_excerpt' ), $taxonomy ) ) {

			Core\HTML::h4( _x( 'Posts with Empty Excerpt', 'Card Title', 'geditorial-audit' ), 'title' );
			if ( ! $lite ) $this->_render_tools_empty_fields_summary( 'empty_excerpt' );

			echo $this->wrap_open( '-wrap-button-row' );
			echo Core\HTML::dropdown( $this->list_posttypes(), [
				'name'       => 'posttype-empty-excerpt',
				'none_title' => _x( 'All Supported Post-Types', 'Card: None-Title', 'geditorial-audit' ),
			] );

			Settings::submitButton( $action.'[mark_empty_excerpt]', _x( 'Mark Posts', 'Card: Button', 'geditorial-audit' ) );
			Settings::submitButton( $action.'[flush_empty_excerpt]', _x( 'Flush Attribute', 'Card: Button', 'geditorial-audit' ), 'danger', TRUE, '' );
			Core\HTML::desc( _x( 'Tries to set the attribute on supported posts with no excerpt.', 'Card: Description', 'geditorial-audit' ) );

			echo '</div>';
			$empty = FALSE;
		}

		if ( $empty ) {

			Core\HTML::h4( _x( 'Posts with Empty Fields', 'Card Title', 'geditorial-audit' ), 'title' );
			Core\HTML::desc( _x( 'No empty attribute available. Please install the default attributes.', 'Message', 'geditorial-audit' ), TRUE, '-empty' );
		}

		echo '</div>';
	}

	private function _do_tools_force_auto_audit( $sub )
	{
		if ( 'do_tools_force_auto_audit' !== self::req( 'action' ) )
			return FALSE;

		if ( ! $posttype = self::req( 'type' ) )
			return Info::renderEmptyPosttype();

		if ( ! $this->posttype_supported( $posttype ) )
			return Info::renderNotSupportedPosttype();

		$this->raise_resources();

		$taxonomy = $this->constant( 'main_taxonomy' );
		$query    = [];

		list( $posts, $pagination ) = Tablelist::getPosts( $query, [], $posttype, $this->get_sub_limit_option( $sub ) );

		if ( empty( $posts ) )
			return FALSE;

		echo Settings::processingListOpen();

		foreach ( $posts as $post )
			$this->_post_force_auto_audit( $post, $taxonomy, TRUE );

		echo '</ul></div>';

		Core\WordPress::redirectJS( add_query_arg( [
			'action' => 'do_tools_force_auto_audit',
			'type'   => $posttype,
			'paged'  => self::paged() + 1,
		] ) );

		return TRUE;
	}

	private function _post_force_auto_audit( $post, $taxonomy = NULL, $verbose = FALSE )
	{
		if ( ! $result = $this->_do_auto_audit_post( $post, TRUE, $taxonomy ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: post title */
				_x( 'No Audits applied for &ldquo;%s&rdquo;', 'Notice', 'geditorial-audit' ) ),
				WordPress\Post::title( $post ) ) : TRUE ) && FALSE;

		if ( $verbose )
			echo Core\HTML::tag( 'li',
				/* translators: %1$s: count terms, %2$s: post title */
				sprintf( _x( '%1$s attributes set for &ldquo;%2$s&rdquo;', 'Notice', 'geditorial-audit' ),
				Core\HTML::code( count( $result ) ),
				WordPress\Post::title( $post )
			) );

		return TRUE;
	}

	private function _render_tools_force_auto_audit()
	{
		echo Settings::toolboxCardOpen( _x( 'Force Auto Audit', 'Card Title', 'geditorial-audit' ) );

			foreach ( $this->list_posttypes() as $posttype => $label )
				Settings::submitButton( add_query_arg( [
					'action' => 'do_tools_force_auto_audit',
					'type'   => $posttype,
				/* translators: %s: posttype label */
				] ), sprintf( _x( 'On %s', 'Button', 'geditorial-audit' ), $label ), 'link-small' );

			Core\HTML::desc( _x( 'Tries to auto-set the attributes on supported posts.', 'Button Description', 'geditorial-audit' ) );
		echo '</div></div>';
	}

	private function _render_tools_empty_fields_summary( $for )
	{
		if ( ! $attribute = $this->_get_attribute( $for ) )
			return;

		$posts = $this->_get_posts_empty( $for, $attribute, NULL, FALSE );
		$count = WordPress\Taxonomy::countTermObjects( $attribute, $this->constant( 'main_taxonomy' ) );

		/* translators: %1$s: empty post count, %2$s: assigned term count */
		Core\HTML::desc( vsprintf( _x( 'Currently found %1$s empty posts and %2$s assigned to the attribute.', 'Card: Description', 'geditorial-audit' ), [
			FALSE === $posts ? gEditorial()->na() : Core\Number::format( count( $posts ) ),
			FALSE === $count ? gEditorial()->na() : Core\Number::format( $count ),
		] ) );
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		Core\HTML::h3( _x( 'Audit Reports', 'Header', 'geditorial-audit' ) );

		if ( ! WordPress\Taxonomy::hasTerms( $this->constant( 'main_taxonomy' ) ) )
			return Info::renderNoReportsAvailable();

		$this->_render_reports_by_user_summary();
	}

	// TODO: export option
	private function _render_reports_by_user_summary()
	{
		$args = $this->get_current_form( [
			'user_id' => '0',
		], 'reports' );

		echo Settings::toolboxCardOpen( _x( 'Summary by User', 'Card Title', 'geditorial-audit' ) );

		$this->do_settings_field( [
			'type'         => 'user',
			'field'        => 'user_id',
			'none_title'   => _x( 'All Users', 'Card: None-Title', 'geditorial-audit' ),
			'none_value'   => '0',
			'default'      => $args['user_id'],
			'option_group' => 'reports',
			'cap'          => TRUE,
		] );

		Settings::submitButton( 'user_stats', _x( 'Apply Filter', 'Card: Button', 'geditorial-audit' ) );
		echo '</div>';

		if ( $summary = $this->get_dashboard_term_summary( 'main_taxonomy', NULL, NULL, ( $args['user_id'] ? 'current' : 'all' ), $args['user_id'] ) )
			echo '<div><ul class="-wrap-list-items">'.$summary.'</ul></div>';

		echo '</div>';
	}

	// TODO: add setting/filter for this
	private function _get_attribute( $for, $default = FALSE )
	{
		switch ( $for ) {
			case 'empty_title': return 'title-empty';
			case 'empty_content': return 'text-empty';
			case 'empty_excerpt': return 'excerpt-empty';
		}

		return $default;
	}

	private function _update_term_count( $attribute, $taxonomy = NULL )
	{
		if ( is_null( $taxonomy ) )
			$taxonomy = $this->constant( 'main_taxonomy' );

		$term = get_term_by( 'slug', $attribute, $taxonomy );
		return wp_update_term_count_now( [ $term->term_id ], $taxonomy );
	}

	// @REF: https://wordpress.stackexchange.com/a/251385
	// @REF: https://stackoverflow.com/a/16395673
	private function _get_posts_empty( $for, $attribute, $posttypes = NULL, $check_taxonomy = TRUE )
	{
		switch ( $for ) {
			case 'title':   case 'empty_title':   $callback = [ __CLASS__, 'whereEmptyTitle' ];   break;
			case 'content': case 'empty_content': $callback = [ __CLASS__, 'whereEmptyContent' ]; break;
			case 'excerpt': case 'empty_excerpt': $callback = [ __CLASS__, 'whereEmptyExcerpt' ]; break;
			default: return FALSE;
		}

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		$args = [
			'post_type'   => $posttypes,
			'post_status' => WordPress\Status::acceptable( $posttypes ),

			'orderby' => 'none',
			'fields'  => 'ids',

			'posts_per_page'         => -1,
			'no_found_rows'          => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		];

		if ( $check_taxonomy )
			$args['tax_query'] = [ [
				'taxonomy' => $this->constant( 'main_taxonomy' ),
				'terms'    => $attribute,
				'field'    => 'slug',
				'operator' => 'NOT IN',
			] ];

		add_filter( 'posts_where', $callback, 9999 );

		$query = new \WP_Query();
		$posts = $query->query( $args );

		remove_filter( 'posts_where', $callback, 9999 );

		return $posts;
	}

	public static function whereEmptyTitle( $where )
	{
		global $wpdb;
		return $where.= " AND (TRIM(COALESCE({$wpdb->posts}.post_title, '')) = '') ";
	}

	public static function whereEmptyContent( $where )
	{
		global $wpdb;
		return $where.= " AND (TRIM(COALESCE({$wpdb->posts}.post_content, '')) = '') ";
	}

	public static function whereEmptyExcerpt( $where )
	{
		global $wpdb;
		return $where.= " AND (TRIM(COALESCE({$wpdb->posts}.post_excerpt, '')) = '') ";
	}

	private function _render_view_for_post( $post, $context )
	{
		$part = $this->get_view_part_by_post( $post, $context );
		$data = $this->_get_view_data_for_post( $post, $context );

		echo $this->wrap_open( '-view -'.$part );
			$this->actions( 'render_view_before', $post, $context, $data, $part );
			$this->render_view( $part, $data );
			$this->actions( 'render_view_after', $post, $context, $data, $part );
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

		$data['__direction']  = Core\HTML::rtl() ? 'rtl' : 'ltr';
		$data['__can_debug']  = Core\WordPress::isDev() || Core\User::isSuperAdmin();
		// $data['__summaries']  = $this->filters( 'post_summaries', [], $data, $post, $context );

		return $this->filters( 'view_data', $data, $post, $context );
	}

	protected function raise_resources( $count = 1, $per = 60, $context = NULL )
	{
		WordPress\Taxonomy::disableTermCounting();
		Services\LateChores::termCountCollect();

		if ( ! Core\WordPress::isDev() )
			do_action( 'qm/cease' ); // QueryMonitor: Cease data collections

		return $this->raise_memory_limit( $count, $per, $context ?? 'audit' );
	}

	public function tabloid_view_data_strip_audits( $data, $post, $context )
	{
		if ( ! $this->posttype_supported( $post->post_type ) || empty( $data['terms_rendered'] ) )
			return $data;

		// NOTE: needs to be non-associative array to render via Mustache
		$data['terms_rendered'] = array_values( Core\Arraay::filter( $data['terms_rendered'], [
			'name' => $this->constant( 'main_taxonomy' ),
		], 'NOT' ) );

		return $data;
	}
}
