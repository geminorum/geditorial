<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\User;

class Audit extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;

	protected $caps = [
		'default' => 'edit_others_posts',
	];

	public static function module()
	{
		return [
			'name'  => 'audit',
			'title' => _x( 'Audit', 'Modules: Audit', 'geditorial' ),
			'desc'  => _x( 'Content Inventory Tools', 'Modules: Audit', 'geditorial' ),
			'icon'  => 'visibility',
		];
	}

	protected function get_global_settings()
	{
		$terms = Taxonomy::listTerms( $this->constant( 'audit_tax' ) );
		$empty = $this->get_taxonomy_label( 'audit_tax', 'no_terms', _x( 'There\'s no audit attributes available!', 'Setting', 'geditorial-audit' ) );
		$roles = $this->get_settings_default_roles( [ 'administrator', 'subscriber' ] );

		return [
			'posttypes_option' => 'posttypes_option',
			'_roles' => [
				[
					'field'       => 'manage_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Manage Roles', 'Setting Title', 'geditorial-audit' ),
					'description' => _x( 'Roles that can Manage, Edit and Delete Audit Attributes.', 'Setting Description', 'geditorial-audit' ),
					'values'      => $roles,
				],
				[
					'field'       => 'assign_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Assign Roles', 'Setting Title', 'geditorial-audit' ),
					'description' => _x( 'Roles that can Assign Audit Attributes.', 'Setting Description', 'geditorial-audit' ),
					'values'      => $roles,
				],
				[
					'field'       => 'reports_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Reports Roles', 'Setting Title', 'geditorial-audit' ),
					'description' => _x( 'Roles that can see Audit Attributes Reports.', 'Setting Description', 'geditorial-audit' ),
					'values'      => $roles,
				],
				[
					'field'       => 'restricted_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Restricted Roles', 'Setting Title', 'geditorial-audit' ),
					'description' => _x( 'Roles that check for Audit Attributes visibility.', 'Setting Description', 'geditorial-audit' ),
					'values'      => $roles,
				],
				[
					'field'       => 'restricted',
					'type'        => 'select',
					'title'       => _x( 'Restricted Terms', 'Setting Title', 'geditorial-audit' ),
					'description' => _x( 'Handles visibility of each term based on meta values.', 'Setting Description', 'geditorial-audit' ),
					'default'     => 'disabled',
					'values'      => [
						'disabled' => _x( 'Disabled', 'Setting Option', 'geditorial-audit' ),
						'hidden'   => _x( 'Hidden', 'Setting Option', 'geditorial-audit' ),
					],
				],
				[
					'field'        => 'locking_terms',
					'type'         => 'checkbox-panel',
					'title'        => _x( 'Locking Terms', 'Setting Title', 'geditorial-audit' ),
					'description'  => _x( 'Selected terms will lock editing the post to audit managers.', 'Setting Description', 'geditorial-audit' ),
					'string_empty' => $empty,
					'values'       => $terms,
				],
			],
			'_dashboard' => [
				'dashboard_widgets',
				'summary_excludes' => [ NULL, $terms, $empty ],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_editlist' => [
				'admin_restrict',
			],
			'_frontend' => [
				'adminbar_summary',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'audit_tax' => 'audit_attribute',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'audit_tax' => NULL,
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'audit_tax' => _n_noop( 'Audit Attribute', 'Audit Attributes', 'geditorial-audit' ),
			],
			'misc' => [
				'menu_name'           => _x( 'Audit', 'Taxonomy Menu', 'geditorial-audit' ),
				'tweaks_column_title' => _x( 'Audit Attributes', 'Column Title', 'geditorial-audit' ),
				'show_option_all'     => _x( 'Audit', 'Show Option All', 'geditorial-audit' ),
				'show_option_none'    => _x( '(Not audited)', 'Show Option None', 'geditorial-audit' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['terms'] = [
			'audit_tax' => [
				'audited'      => _x( 'Audited', 'Default Term', 'geditorial-audit' ),
				'outdated'     => _x( 'Outdated', 'Default Term', 'geditorial-audit' ),
				'redundant'    => _x( 'Redundant', 'Default Term', 'geditorial-audit' ),
				'review-seo'   => _x( 'Review SEO', 'Default Term', 'geditorial-audit' ),
				'review-style' => _x( 'Review Style', 'Default Term', 'geditorial-audit' ),
				'trivial'      => _x( 'Trivial', 'Default Term', 'geditorial-audit' ),
				'initial-copy' => _x( 'Initial Copy', 'Default Term', 'geditorial-audit' ),
				'unfinished'   => _x( 'Unfinished', 'Default Term', 'geditorial-audit' ),
				'text-empty'   => _x( 'No Content', 'Default Term', 'geditorial-audit' ),
				'imported'     => _x( 'Imported', 'Default Term', 'geditorial-audit' ),
			],
		];

		return $strings;
	}

	public function before_settings( $module = FALSE )
	{
		if ( isset( $_POST['install_def_audit_tax'] ) )
			$this->insert_default_terms( 'audit_tax' );

		$this->help_tab_default_terms( 'audit_tax' );
	}

	public function default_buttons( $module = FALSE )
	{
		parent::default_buttons( $module );

		$this->register_button( 'install_def_audit_tax', _x( 'Install Default Attributes', 'Button', 'geditorial-audit' ) );
	}

	public function init()
	{
		parent::init();

		$taxonomy = $this->constant( 'audit_tax' );

		$this->register_taxonomy( 'audit_tax', [
			'public'             => FALSE,
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
			'show_in_rest'       => $this->role_can( 'assign' ), // QUESTION: what if auth by plugin
		], NULL, [
			'manage_terms' => 'manage_'.$taxonomy,
			'edit_terms'   => 'edit_'.$taxonomy,
			'delete_terms' => 'delete_'.$taxonomy,
			'assign_terms' => 'assign_'.$taxonomy,
		] );

		$this->filter( 'map_meta_cap', 4 );

		$this->register_default_terms( 'audit_tax' );
	}

	public function init_ajax()
	{
		$this->_hook_ajax();
	}

	public function ajax()
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

				$taxonomy = $this->constant( 'audit_tax' );

				parse_str( $post['data'], $data );

				$terms = empty( $data['tax_input'][$taxonomy] ) ? NULL
					: array_map( 'intval', (array) $data['tax_input'][$taxonomy] );

				wp_set_object_terms( $post['post_id'], $terms, $taxonomy, FALSE );
				clean_object_term_cache( $post['post_id'], $taxonomy );

				Ajax::success( $this->get_adminbar_checklist( $post['post_id'] ) );
		}

		Ajax::errorWhat();
	}

	private function get_adminbar_checklist( $post_id )
	{
		require_once ABSPATH.'wp-admin/includes/template.php';

		$html = wp_terms_checklist( $post_id, [
			'taxonomy'      => $this->constant( 'audit_tax' ),
			'checked_ontop' => FALSE,
			'echo'          => FALSE,
		] );

		return HTML::wrap( '<ul>'.$html.'</ul>', 'geditorial-adminbar-box-wrap' );
	}

	// @REF: https://make.wordpress.org/core/?p=20496
	public function map_meta_cap( $caps, $cap, $user_id, $args )
	{
		$taxonomy = $this->constant( 'audit_tax' );

		switch ( $cap ) {

			case 'edit_post':
			case 'edit_page':
			case 'delete_post':
			case 'delete_page':
			case 'publish_post':

				$locking = $this->get_setting( 'locking_terms', [] );

				if ( empty( $locking ) )
					return $caps;

				if ( ! $post = get_post( $args[0] ) )
					return $caps;

				if ( ! $this->posttype_supported( $post->post_type ) )
					return $caps;

				foreach ( $locking as $term_id )
					if ( is_object_in_term( $post->ID, $taxonomy, (int) $term_id ) )
						return $this->role_can( 'manage', $user_id ) ? $caps : [ 'do_not_allow' ];

			break;
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

				if ( ! User::hasRole( array_merge( [ 'administrator' ], (array) $roles ), $user_id ) )
					return [ 'do_not_allow' ];
		}

		return $caps;
	}

	// override
	public function cuc( $context = 'settings', $fallback = '' )
	{
		return 'reports' == $context ? $this->role_can( 'reports' ) : parent::cuc( $context, $fallback );
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() || ! is_singular( $this->posttypes() ) )
			return;

		$post_id  = get_queried_object_id();
		$classs   = $this->classs();
		$taxonomy = $this->constant( 'audit_tax' );
		$terms    = [];

		if ( $this->role_can( 'reports' )
			|| current_user_can( 'edit_post', $post_id ) ) {

			$nodes[] = [
				'id'     => $classs,
				'title'  => _x( 'Audit Attributes', 'Adminbar', 'geditorial-audit' ),
				'parent' => $parent,
				'href'   => $this->get_module_url(),
			];

			if ( $terms = Taxonomy::getTerms( $taxonomy, $post_id, TRUE ) )
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
					'title'  => $this->get_string( 'show_option_none', 'audit_tax', 'misc' ),
					'href'   => FALSE,
					'meta'   => [ 'class' => '-danger '.$classs ],
				];
		}

		if ( ! $this->role_can( 'assign' ) )
			return;

		if ( empty( $terms ) && ! Taxonomy::hasTerms( $taxonomy ) )
			return;

		$this->action( 'admin_bar_menu', 1, 699 );

		$this->enqueue_styles();
		$this->enqueue_asset_js( [
			'post_id' => $post_id,
			'_nonce'  => wp_create_nonce( $this->hook( $post_id ) ),
		] );
	}

	public function admin_bar_menu( $wp_admin_bar )
	{
		$wp_admin_bar->add_node( [
			'id'    => $this->classs( 'attributes' ),
			'href'  => $this->get_module_url(),
			'title' => _x( 'Auditing', 'Adminbar: Title Attr', 'geditorial-audit' ).Ajax::spinner(),
			'meta'  => [ 'class' => 'geditorial-adminbar-node -action '.$this->classs() ],
		] );

		$wp_admin_bar->add_node( [
			'id'     => $this->classs( 'box' ),
			'parent' => $this->classs( 'attributes' ),
			'title'  => _x( 'Click to load attributes &hellip;', 'Adminbar: Title Attr', 'geditorial-audit' ),
			'meta'   => [ 'class' => 'geditorial-adminbar-wrap -wrap '.$this->classs() ],
		] );
	}

	public function current_screen( $screen )
	{
		if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit' == $screen->base ) {

				if ( $this->get_setting( 'admin_restrict', FALSE ) && $this->role_can( 'assign' ) ) {
					$this->action( 'restrict_manage_posts', 2, 20 );
					$this->filter( 'parse_query' );
				}

				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}
		}
	}

	protected function dashboard_widgets()
	{
		if ( ! $this->role_can( 'assign' ) )
			return;

		$title = 'current' == $this->get_setting( 'summary_scope', 'all' )
			? _x( 'Your Audit Summary', 'Dashboard Widget Title', 'geditorial-audit' )
			: _x( 'Editorial Audit Summary', 'Dashboard Widget Title', 'geditorial-audit' );

		$this->add_dashboard_widget( 'term-summary', $title, 'refresh' );
	}

	public function render_widget_term_summary( $object, $box )
	{
		$this->do_dashboard_term_summary( 'audit_tax', $box );
	}

	public function restrict_manage_posts( $posttype, $which )
	{
		$this->do_restrict_manage_posts_taxes( 'audit_tax' );
	}

	public function parse_query( &$query )
	{
		$this->do_parse_query_taxes( $query, 'audit_tax' );
	}

	public function meta_box_cb_audit_tax( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$args = [
			'taxonomy' => $box['args']['taxonomy'],
			'posttype' => $post->post_type,
		];

		if ( $this->role_can( 'restricted', NULL, FALSE, FALSE ) )
			$args['role'] = $this->get_setting( 'restricted', 'disabled' );

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $args );
		echo '</div>';
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		$terms = Taxonomy::getTerms( $this->constant( 'audit_tax' ), FALSE, TRUE, 'slug', [ 'hide_empty' => TRUE ] );

		if ( empty( $terms ) )
			return HTML::desc( _x( 'No reports available!', 'Message', 'geditorial-audit' ), TRUE, '-empty' );

		$args = $this->get_current_form( [
			'user_id' => '0',
		], 'reports' );

		HTML::h3( _x( 'Audit Reports', 'Header', 'geditorial-audit' ) );

		echo '<table class="form-table">';

		echo '<tr><th scope="row">'._x( 'By User', 'Reports', 'geditorial-audit' ).'</th><td>';

		$this->do_settings_field( [
			'type'         => 'user',
			'field'        => 'user_id',
			'none_title'   => _x( 'All Users', 'None', 'geditorial-audit' ),
			'none_value'   => '0',
			'default'      => $args['user_id'],
			'option_group' => 'reports',
			'cap'          => TRUE,
		] );

		echo '&nbsp;';

		Settings::submitButton( 'user_stats', _x( 'Apply Filter', 'Button', 'geditorial-audit' ) );

		// FIXME: style this!
		if ( $summary = $this->get_dashboard_term_summary( 'audit_tax', NULL, $terms, ( $args['user_id'] ? 'current' : 'all' ), $args['user_id'] ) )
			echo '<div><ul>'.$summary.'</ul></div>';

		echo '</td></tr>';
		echo '</table>';
	}

	// API METHOD
	// FIXME: move this up to main module
	public function set_terms( $post, $terms, $append = TRUE )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return FALSE;

		$result = wp_set_object_terms( $post->ID, $terms, $this->constant( 'audit_tax' ), $append );

		if ( is_wp_error( $result ) )
			return FALSE;

		clean_object_term_cache( $post->ID, $this->constant( 'audit_tax' ) );

		return TRUE;
	}
}
