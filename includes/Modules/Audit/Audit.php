<?php namespace geminorum\gEditorial\Modules\Audit;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;
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
			'_general' => [
				[
					'field'       => 'auto_audit_empty',
					'title'       => _x( 'Auto-Audit for Empties', 'Setting Title', 'geditorial-audit' ),
					'description' => _x( 'Tries to automatically assign empty attributes on supported posts.', 'Setting Description', 'geditorial-audit' ),
				],
			],
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
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'menu_name'           => _x( 'Audit', 'Taxonomy Menu', 'geditorial-audit' ),
			'tweaks_column_title' => _x( 'Audit Attributes', 'Column Title', 'geditorial-audit' ),
			'show_option_all'     => _x( 'Audit', 'Show Option All', 'geditorial-audit' ),
			'show_option_none'    => _x( '(Not audited)', 'Show Option None', 'geditorial-audit' ),
		];

		$strings['terms'] = [
			'audit_tax' => [
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

		$this->register_taxonomy( 'audit_tax', [
			'public'             => FALSE,
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
			'show_in_rest'       => $this->role_can( 'assign' ), // QUESTION: what if auth by plugin
		], NULL, TRUE );

		$this->filter( 'map_meta_cap', 4 );

		if ( $this->get_setting( 'auto_audit_empty' ) )
			$this->action( 'save_post', 3, 99 );

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

				if ( ! $post = PostType::getPost( $args[0] ) )
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

	public function save_post( $post_id, $post, $update )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return;

		if ( ! in_array( $post->post_status, [ 'publish', 'future', 'draft', 'pending' ], TRUE ) )
			return;

		$taxonomy = $this->constant( 'audit_tax' );
		$terms    = Taxonomy::getObjectTerms( $taxonomy, $post->ID );
		$currents = $terms;

		if ( $exists = term_exists( $this->_get_attribute( 'empty_title' ), $taxonomy ) ) {

			if ( empty( $post->post_title ) )
				$terms[] = $exists['term_id'];

			else
				$terms = Arraay::stripByValue( $terms, $exists['term_id'] );
		}

		if ( $exists = term_exists( $this->_get_attribute( 'empty_content' ), $taxonomy ) ) {

			if ( empty( $post->post_content ) )
				$terms[] = $exists['term_id'];

			else
				$terms = Arraay::stripByValue( $terms, $exists['term_id'] );
		}

		if ( $exists = term_exists( $this->_get_attribute( 'empty_excerpt' ), $taxonomy ) ) {

			if ( empty( $post->post_excerpt ) )
				$terms[] = $exists['term_id'];

			else
				$terms = Arraay::stripByValue( $terms, $exists['term_id'] );
		}

		$terms = $this->filters( 'auto_audit_save_post', $terms, $post, $taxonomy, $currents, $update );

		if ( $terms == $currents )
			return;

		$result = wp_set_object_terms( $post->ID, Arraay::prepNumeral( $terms ), $taxonomy );

		if ( ! is_wp_error( $result ) )
			clean_object_term_cache( $post->ID, $taxonomy );
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
				// 'html'  => HTML::tag( 'span', [
				// 	'class' => 'quick-assign-data',
				// 	'data'  => [
				// 		'post'     => $post_id,
				// 		'taxonomy' => $this->constant( 'audit_tax' ),
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
		if ( $this->constant( 'audit_tax' ) == $screen->taxonomy ) {

			if ( 'edit-tags' == $screen->base ) {

				$this->filter( 'taxonomy_empty_terms', 2, 8, FALSE, 'gnetwork' );

				$this->action( 'taxonomy_tab_extra_content', 2, 20, FALSE, 'gnetwork' );
				$this->action( 'taxonomy_handle_tab_content_actions', 1, 8, FALSE, 'gnetwork' );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit' == $screen->base ) {

				if ( $this->role_can( 'assign' ) ) {
					$this->_hook_screen_restrict_taxonomies();
					$this->action( 'restrict_manage_posts', 2, 20, 'restrict_taxonomy' );
					$this->action( 'parse_query', 1, 12, 'restrict_taxonomy' );
				}

				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}
		}
	}

	protected function get_taxonomies_for_restrict_manage_posts()
	{
		return [ 'audit_tax' ];
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

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( $action = self::req( $this->classs( 'empty-fields' ) ) )
					$this->_handle_action_empty_fields( $action );

				WordPress::redirectReferer( 'huh' );
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
		$taxonomy = $this->constant( 'audit_tax' );

		$this->_raise_resources();

		switch ( Arraay::keyFirst( $action ) ) {

			case 'flush_empty_title':

				$attribute = $this->_get_attribute( 'empty_title' );

				if ( FALSE === ( $count = Taxonomy::removeTermObjects( $attribute, $taxonomy ) ) )
					WordPress::redirectReferer( 'wrong' );

				$this->_update_term_count( $attribute, $taxonomy );

				WordPress::redirectReferer( [
					'message' => 'emptied',
					'count'   => $count,
				] );
				break;

			case 'flush_empty_content':

				$attribute = $this->_get_attribute( 'empty_content' );

				if ( FALSE === ( $count = Taxonomy::removeTermObjects( $attribute, $taxonomy ) ) )
					WordPress::redirectReferer( 'wrong' );

				$this->_update_term_count( $attribute, $taxonomy );

				WordPress::redirectReferer( [
					'message' => 'emptied',
					'count'   => $count,
				] );
				break;

			case 'flush_empty_excerpt':

				$attribute = $this->_get_attribute( 'empty_excerpt' );

				if ( FALSE === ( $count = Taxonomy::removeTermObjects( $attribute, $taxonomy ) ) )
					WordPress::redirectReferer( 'wrong' );

				$this->_update_term_count( $attribute, $taxonomy );

				WordPress::redirectReferer( [
					'message' => 'emptied',
					'count'   => $count,
				] );
				break;

			case 'mark_empty_title':

				$posttypes = self::req( 'posttype-empty-title' ) ?: NULL;
				$attribute = $this->_get_attribute( 'empty_title' );

				if ( FALSE === ( $posts = $this->_get_posts_empty( 'title', $attribute, $posttypes ) ) )
					WordPress::redirectReferer( 'wrong' );

				if ( empty( $posts ) )
					WordPress::redirectReferer( 'nochange' );

				if ( FALSE === ( $count = Taxonomy::setTermObjects( $posts, $attribute, $taxonomy ) ) )
					WordPress::redirectReferer( 'wrong' );

				$this->_update_term_count( $attribute, $taxonomy );

				WordPress::redirectReferer( [
					'message' => 'synced',
					'count'   => $count,
				] );
				break;

			case 'mark_empty_content':

				$posttypes = self::req( 'posttype-empty-content' ) ?: NULL;
				$attribute = $this->_get_attribute( 'empty_content' );

				if ( FALSE === ( $posts = $this->_get_posts_empty( 'content', $attribute, $posttypes ) ) )
					WordPress::redirectReferer( 'wrong' );

				if ( empty( $posts ) )
					WordPress::redirectReferer( 'nochange' );

				if ( FALSE === ( $count = Taxonomy::setTermObjects( $posts, $attribute, $taxonomy ) ) )
					WordPress::redirectReferer( 'wrong' );

				$this->_update_term_count( $attribute, $taxonomy );

				WordPress::redirectReferer( [
					'message' => 'synced',
					'count'   => $count,
				] );
				break;

			case 'mark_empty_excerpt':

				$posttypes = self::req( 'posttype-empty-excerpt' ) ?: NULL;
				$attribute = $this->_get_attribute( 'empty_excerpt' );

				if ( FALSE === ( $posts = $this->_get_posts_empty( 'excerpt', $attribute, $posttypes ) ) )
					WordPress::redirectReferer( 'wrong' );

				if ( empty( $posts ) )
					WordPress::redirectReferer( 'nochange' );

				if ( FALSE === ( $count = Taxonomy::setTermObjects( $posts, $attribute, $taxonomy ) ) )
					WordPress::redirectReferer( 'wrong' );

				$this->_update_term_count( $attribute, $taxonomy );

				WordPress::redirectReferer( [
					'message' => 'synced',
					'count'   => $count,
				] );
				break;
		}

		WordPress::redirectReferer( 'huh' );
	}

	protected function render_tools_html( $uri, $sub )
	{
		HTML::h3( _x( 'Content Audit Tools', 'Header', 'geditorial-audit' ) );
		$this->_render_tools_empty_fields();
	}

	public function taxonomy_empty_terms( $terms, $taxonomy )
	{
		if ( empty( $terms ) || ! $taxonomy )
			return $terms;

		if ( $taxonomy != $this->constant( 'audit_tax' ) )
			return $terms;

		foreach ( [ 'empty_title', 'empty_content', 'empty_excerpt' ] as $for )
			if ( $exists = term_exists( $this->_get_attribute( $for ), $taxonomy ) )
				$terms = Arraay::stripByValue( $terms, $exists['term_id'] );

		return $terms;
	}

	public function taxonomy_tab_extra_content( $taxonomy, $object )
	{
		$this->render_form_start( NULL, 'import', 'download', 'tabs', FALSE );
			$this->nonce_field( 'do-empty-fields' );
			$this->_render_tools_empty_fields( TRUE );
		$this->render_form_end( NULL, 'import', 'download', 'tabs' );
	}

	private function _render_tools_empty_fields( $lite = FALSE )
	{
		$taxonomy = $this->constant( 'audit_tax' );
		$action   = $this->classs( 'empty-fields' );
		$empty    = TRUE;

		echo $this->wrap_open( [ 'card', '-toolbox-card' ] );

		if ( term_exists( $this->_get_attribute( 'empty_title' ), $taxonomy ) ) {

			HTML::h4( _x( 'Posts with Empty Title', 'Card Title', 'geditorial-audit' ), 'title' );
			if ( ! $lite ) $this->_render_tools_empty_fields_summary( 'empty_title' );

			echo $this->wrap_open( '-wrap-button-row -mark_empty_title' );
			echo HTML::dropdown( $this->list_posttypes(), [
				'name'       => 'posttype-empty-title',
				'none_title' => _x( 'All Supported Post-Types', 'Card: None-Title', 'geditorial-audit' ),
			] );
			echo '&nbsp;&nbsp;';
			Settings::submitButton( $action.'[mark_empty_title]', _x( 'Mark Posts', 'Card: Button', 'geditorial-audit' ) );
			Settings::submitButton( $action.'[flush_empty_title]', _x( 'Flush Attribute', 'Card: Button', 'geditorial-audit' ), 'danger', TRUE, '' );
			HTML::desc( _x( 'Tries to set the attribute on supported posts with no title.', 'Card: Description', 'geditorial-audit' ) );

			echo '</div>';
			$empty = FALSE;
		}

		if ( term_exists( $this->_get_attribute( 'empty_content' ), $taxonomy ) ) {

			HTML::h4( _x( 'Posts with Empty Content', 'Card Title', 'geditorial-audit' ), 'title' );
			if ( ! $lite ) $this->_render_tools_empty_fields_summary( 'empty_content' );

			echo $this->wrap_open( '-wrap-button-row -mark_empty_content' );
			echo HTML::dropdown( $this->list_posttypes(), [
				'name'       => 'posttype-empty-content',
				'none_title' => _x( 'All Supported Post-Types', 'Card: None-Title', 'geditorial-audit' ),
			] );
			echo '&nbsp;&nbsp;';
			Settings::submitButton( $action.'[mark_empty_content]', _x( 'Mark Posts', 'Card: Button', 'geditorial-audit' ) );
			Settings::submitButton( $action.'[flush_empty_content]', _x( 'Flush Attribute', 'Card: Button', 'geditorial-audit' ), 'danger', TRUE, '' );
			HTML::desc( _x( 'Tries to set the attribute on supported posts with no content.', 'Card: Description', 'geditorial-audit' ) );

			echo '</div>';
			$empty = FALSE;
		}

		if ( term_exists( $this->_get_attribute( 'empty_excerpt' ), $taxonomy ) ) {

			HTML::h4( _x( 'Posts with Empty Excerpt', 'Card Title', 'geditorial-audit' ), 'title' );
			if ( ! $lite ) $this->_render_tools_empty_fields_summary( 'empty_excerpt' );

			echo $this->wrap_open( '-wrap-button-row -mark_empty_excerpt' );
			echo HTML::dropdown( $this->list_posttypes(), [
				'name'       => 'posttype-empty-excerpt',
				'none_title' => _x( 'All Supported Post-Types', 'Card: None-Title', 'geditorial-audit' ),
			] );
			echo '&nbsp;&nbsp;';
			Settings::submitButton( $action.'[mark_empty_excerpt]', _x( 'Mark Posts', 'Card: Button', 'geditorial-audit' ) );
			Settings::submitButton( $action.'[flush_empty_excerpt]', _x( 'Flush Attribute', 'Card: Button', 'geditorial-audit' ), 'danger', TRUE, '' );
			HTML::desc( _x( 'Tries to set the attribute on supported posts with no excerpt.', 'Card: Description', 'geditorial-audit' ) );

			echo '</div>';
			$empty = FALSE;
		}

		if ( $empty ) {
			HTML::h4( _x( 'Posts with Empty Fields', 'Card Title', 'geditorial-audit' ), 'title' );
			HTML::desc( _x( 'No empty attribute available. Please install the default attributes.', 'Message', 'geditorial-audit' ), TRUE, '-empty' );
		}

		echo '</div>';
	}

	private function _render_tools_empty_fields_summary( $for )
	{
		if ( ! $attribute = $this->_get_attribute( $for ) )
			return;

		$posts = $this->_get_posts_empty( $for, $attribute, NULL, FALSE );
		$count = Taxonomy::countTermObjects( $attribute, $this->constant( 'audit_tax' ) );

		/* translators: %1$s: empty post count, %2$s: assigned term count */
		HTML::desc( vsprintf( _x( 'Currently found %1$s empty posts and %2$s assigned to the attribute.', 'Card: Description', 'geditorial-audit' ), [
			FALSE === $posts ? gEditorial()->na() : Number::format( count( $posts ) ),
			FALSE === $count ? gEditorial()->na() : Number::format( $count ),
		] ) );
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		HTML::h3( _x( 'Audit Reports', 'Header', 'geditorial-audit' ) );

		if ( ! Taxonomy::hasTerms( $this->constant( 'audit_tax' ) ) )
			return HTML::desc( _x( 'No reports available!', 'Message', 'geditorial-audit' ), TRUE, '-empty' );

		$this->_render_reports_by_user_summary();
	}

	// TODO: export option
	private function _render_reports_by_user_summary()
	{
		$args = $this->get_current_form( [
			'user_id' => '0',
		], 'reports' );

		echo $this->wrap_open( [ 'card', '-toolbox-card' ] );
		HTML::h4( _x( 'Summary by User', 'Card Title', 'geditorial-audit' ), 'title' );
		echo $this->wrap_open( '-wrap-button-row -mark_empty_excerpt' );

		$this->do_settings_field( [
			'type'         => 'user',
			'field'        => 'user_id',
			'none_title'   => _x( 'All Users', 'Card: None-Title', 'geditorial-audit' ),
			'none_value'   => '0',
			'default'      => $args['user_id'],
			'option_group' => 'reports',
			'cap'          => TRUE,
		] );

		echo '&nbsp;&nbsp;';
		Settings::submitButton( 'user_stats', _x( 'Apply Filter', 'Card: Button', 'geditorial-audit' ) );
		echo '</div>';

		if ( $summary = $this->get_dashboard_term_summary( 'audit_tax', NULL, NULL, ( $args['user_id'] ? 'current' : 'all' ), $args['user_id'] ) )
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
			$taxonomy = $this->constant( 'audit_tax' );

		$term = get_term_by( 'slug', $attribute, $taxonomy );
		return wp_update_term_count_now( [ $term->term_id ], $taxonomy );
	}

	// API METHOD
	// FIXME: move this up to main module
	public function set_terms( $post, $terms, $append = TRUE )
	{
		if ( ! $post = PostType::getPost( $post ) )
			return FALSE;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return FALSE;

		$result = wp_set_object_terms( $post->ID, $terms, $this->constant( 'audit_tax' ), $append );

		if ( is_wp_error( $result ) )
			return FALSE;

		clean_object_term_cache( $post->ID, $this->constant( 'audit_tax' ) );

		return TRUE;
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

		$args = [
			'post_type'   => $posttypes ?: $this->posttypes(),
			'post_status' => [ 'publish', 'future', 'draft', 'pending' ],

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
				'taxonomy' => $this->constant( 'audit_tax' ),
				'terms'    => $attribute,
				'field'    => 'slug',
				'operator' => 'NOT IN',
			] ];

		add_filter( 'posts_where', $callback, 9999 );
		$query = new \WP_Query;
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

	private function _raise_resources( $count = 0 )
	{
		Taxonomy::disableTermCounting();

		do_action( 'qm/cease' ); // QueryMonitor: Cease data collections

		$this->raise_resources( $count, 60, 'audit' );
	}
}
