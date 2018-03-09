<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Database;
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
			'title' => _x( 'Audit', 'Modules: Audit', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Content Inventory Tools', 'Modules: Audit', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'visibility',
		];
	}

	protected function get_global_settings()
	{
		$roles   = User::getAllRoleList();
		$exclude = [ 'administrator', 'subscriber' ];

		return [
			'posttypes_option' => 'posttypes_option',
			'_roles' => [
				[
					'field'       => 'manage_roles',
					'type'        => 'checkbox',
					'title'       => _x( 'Manage Roles', 'Modules: Audit: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles that can Manage, Edit and Delete Audit Attributes.', 'Modules: Audit: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
				],
				[
					'field'       => 'assign_roles',
					'type'        => 'checkbox',
					'title'       => _x( 'Assign Roles', 'Modules: Audit: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles that can Assign Audit Attributes.', 'Modules: Audit: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
				],
				[
					'field'       => 'reports_roles',
					'type'        => 'checkbox',
					'title'       => _x( 'Reports Roles', 'Modules: Audit: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles that can see Audit Attributes Reports.', 'Modules: Audit: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
				],
				[
					'field'       => 'restricted_roles',
					'type'        => 'checkbox',
					'title'       => _x( 'Restricted Roles', 'Modules: Audit: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles that check for Audit Attributes visibility.', 'Modules: Audit: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
				],
				[
					'field'       => 'restricted',
					'type'        => 'select',
					'title'       => _x( 'Restricted Terms', 'Modules: Audit: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Handles visibility of each term based on meta values.', 'Modules: Audit: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => 'disabled',
					'values'      => [
						'disabled' => _x( 'Disabled', 'Modules: Audit: Setting Option', GEDITORIAL_TEXTDOMAIN ),
						'hidden'   => _x( 'Hidden', 'Modules: Audit: Setting Option', GEDITORIAL_TEXTDOMAIN ),
					],
				],
				[
					'field'        => 'locking_terms',
					'type'         => 'checkbox',
					'title'        => _x( 'Locking Terms', 'Modules: Audit: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description'  => _x( 'Selected terms will lock editing the post to audit managers.', 'Modules: Audit: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'string_empty' => _x( 'There\'s no audit attributes available!', 'Modules: Audit: Setting', GEDITORIAL_TEXTDOMAIN ),
					'values'       => Taxonomy::listTerms( $this->constant( 'audit_tax' ) ),
				],
			],
			'_dashboard' => [
				'dashboard_widgets',
				'summary_scope',
				[
					'field'       => 'summary_drafts',
					'title'       => _x( 'Include Drafts', 'Modules: Audit: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Include drafted items in the content summary.', 'Modules: Audit: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
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
				'audit_tax' => _nx_noop( 'Audit Attribute', 'Audit Attributes', 'Modules: Audit: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
			'misc' => [
				'menu_name'           => _x( 'Audit', 'Modules: Audit: Audit Attributes Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
				'tweaks_column_title' => _x( 'Audit Attributes', 'Modules: Audit: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'show_option_all'     => _x( 'Audit', 'Modules: Audit: Show Option All', GEDITORIAL_TEXTDOMAIN ),
				'show_option_none'    => _x( '(Not audited)', 'Modules: Audit: Show Option All', GEDITORIAL_TEXTDOMAIN ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['terms'] = [
			'audit_tax' => [
				'audited'      => _x( 'Audited', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				'outdated'     => _x( 'Outdated', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				'redundant'    => _x( 'Redundant', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				'review-seo'   => _x( 'Review SEO', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				'review-style' => _x( 'Review Style', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				'trivial'      => _x( 'Trivial', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				'text-empty'   => _x( 'No Content', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
			],
		];

		return $strings;
	}

	public function before_settings( $module = FALSE )
	{
		if ( isset( $_POST['install_def_audit_tax'] ) )
			$this->insert_default_terms( 'audit_tax' );
	}

	public function default_buttons( $module = FALSE )
	{
		parent::default_buttons( $module );

		$this->register_button( 'install_def_audit_tax', _x( 'Install Default Attributes', 'Modules: Audit: Setting Button', GEDITORIAL_TEXTDOMAIN ) );
	}

	public function init()
	{
		parent::init();

		$taxonomy = $this->constant( 'audit_tax' );

		$this->register_taxonomy( 'audit_tax', [
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
		require_once( ABSPATH.'wp-admin/includes/template.php' );

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

				if ( ! in_array( $post->post_type, $this->posttypes() ) )
					return $caps;

				foreach ( $locking as $term_id )
					if ( is_object_in_term( $post->ID, $taxonomy, intval( $term_id ) ) )
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

		$post_id = get_queried_object_id();
		$classs  = $this->classs();

		if ( $this->role_can( 'reports' )
			|| current_user_can( 'edit_post', $post_id ) ) {

			$nodes[] = [
				'id'     => $classs,
				'title'  => _x( 'Audit Attributes', 'Modules: Audit: Adminbar', GEDITORIAL_TEXTDOMAIN ),
				'parent' => $parent,
				'href'   => $this->get_module_url(),
			];

			if ( $terms = Taxonomy::getTerms( $this->constant( 'audit_tax' ), $post_id, TRUE ) )
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
			'title' => _x( 'Auditing', 'Modules: Audit: Adminbar', GEDITORIAL_TEXTDOMAIN ).Ajax::spinner(),
			'meta'  => [ 'class' => 'geditorial-adminbar-node -action '.$this->classs() ],
		] );

		$wp_admin_bar->add_node( [
			'id'     => $this->classs( 'box' ),
			'parent' => $this->classs( 'attributes' ),
			'title'  => _x( 'Click to load attributes &hellip;', 'Modules: Audit: Adminbar', GEDITORIAL_TEXTDOMAIN ),
			'meta'   => [ 'class' => 'geditorial-adminbar-wrap -wrap '.$this->classs() ],
		] );
	}

	public function current_screen( $screen )
	{
		if ( in_array( $screen->post_type, $this->posttypes() ) ) {

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
			? _x( 'Your Audit Summary', 'Modules: Audit: Dashboard Widget Title', GEDITORIAL_TEXTDOMAIN )
			: _x( 'Editorial Audit Summary', 'Modules: Audit: Dashboard Widget Title', GEDITORIAL_TEXTDOMAIN );

		$title.= MetaBox::titleActionRefresh();

		wp_add_dashboard_widget( $this->classs( 'summary' ), $title, [ $this, 'dashboard_widget_summary' ] );
	}

	public function dashboard_widget_summary( $object, $box )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		// using core styles
		echo '<div id="dashboard_right_now" class="geditorial-wrap -admin-widget -audit -core-styles">';

		$scope  = $this->get_setting( 'summary_scope', 'all' );
		$suffix = 'all' == $scope ? 'all' : get_current_user_id();
		$key    = $this->hash( 'widgetsummary', $scope, $suffix );

		if ( WordPress::isFlush() )
			delete_transient( $key );

		if ( FALSE === ( $html = get_transient( $key ) ) ) {

			if ( $this->check_hidden_metabox( $box, '</div>' ) )
				return;

			$terms = Taxonomy::getTerms( $this->constant( 'audit_tax' ), FALSE, TRUE, 'slug', [ 'hide_empty' => TRUE ] );

			if ( $summary = $this->get_summary( $this->posttypes(), $terms, $scope ) ) {

				$html = Text::minifyHTML( $summary );
				set_transient( $key, $html, 12 * HOUR_IN_SECONDS );

			} else {

				HTML::desc( _x( 'No reports available!', 'Modules: Audit', GEDITORIAL_TEXTDOMAIN ), FALSE, '-empty' );
			}
		}

		if ( $html )
			echo '<div class="main"><ul>'.$html.'</ul></div>';

		echo '</div>';
	}

	private function get_summary( $posttypes, $terms, $scope = 'all', $user_id = NULL, $list = 'li' )
	{
		$html    = '';
		$check   = FALSE;
		$tax     = $this->constant( 'audit_tax' );
		$all     = PostType::get( 3 );
		$exclude = Database::getExcludeStatuses();

		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		if ( 'roles' == $scope && $this->role_can( 'restricted', $user_id, FALSE, FALSE ) )
			$check = TRUE; // 'hidden' == $this->get_setting( 'restricted', 'disabled' );

		if ( $this->get_setting( 'summary_drafts', FALSE ) )
			$exclude = array_diff( $exclude, [ 'draft' ] );

		if ( count( $terms ) ) {

			$counts  = Database::countPostsByTaxonomy( $terms, $posttypes, ( 'current' == $scope ? $user_id : 0 ), $exclude );
			$objects = [];

			foreach ( $counts as $term => $posts ) {

				if ( $check && ( $roles = get_term_meta( $terms[$term]->term_id, 'roles', TRUE ) ) ) {

					if ( ! User::hasRole( array_merge( [ 'administrator' ], (array) $roles ), $user_id ) )
						continue;
				}

				$name = sanitize_term_field( 'name', $terms[$term]->name, $terms[$term]->term_id, $terms[$term]->taxonomy, 'display' );

				foreach ( $posts as $type => $count ) {

					if ( ! $count )
						continue;

					if ( count( $posttypes ) > 1 )
						$text = vsprintf( '%3$s %1$s (%2$s)', [
							Helper::noopedCount( $count, $all[$type] ),
							Helper::trimChars( $name, 35 ),
							Number::format( $count ),
						] );

					else
						$text = sprintf( '%2$s %1$s', $name, Number::format( $count ) );

					if ( empty( $objects[$type] ) )
						$objects[$type] = get_post_type_object( $type );

					$class = 'geditorial-glance-item -audit -term -taxonomy-'.$tax.' -term-'.$term.'-'.$type.'-count';

					if ( $objects[$type] && current_user_can( $objects[$type]->cap->edit_posts ) )
						$text = HTML::tag( 'a', [
							'href'  => WordPress::getPostTypeEditLink( $type, ( 'current' == $scope ? $user_id : 0 ), [ $tax => $term ] ),
							'class' => $class,
						], $text );

					else
						$text = HTML::wrap( $text, $class );

					$html.= HTML::tag( $list, $text );
				}
			}
		}

		if ( $this->get_setting( 'count_not', FALSE ) ) {

			$not = Database::countPostsByNotTaxonomy( $tax, $posttypes, ( 'current' == $scope ? $user_id : 0 ), $exclude );

			foreach ( $not as $type => $count ) {

				if ( ! $count )
					continue;

				if ( count( $posttypes ) > 1 )
					$text = vsprintf( '%3$s %1$s %2$s', [
						Helper::noopedCount( $count, $all[$type] ),
						$this->get_string( 'show_option_none', 'audit_tax', 'misc' ),
						Number::format( $count ),
					] );

				else
					$text = sprintf( '%2$s %1$s', $this->get_string( 'show_option_none', 'audit_tax', 'misc' ), Number::format( $count ) );

				if ( empty( $objects[$type] ) )
					$objects[$type] = get_post_type_object( $type );

				$class = 'geditorial-glance-item -audit -not-in -taxonomy-'.$tax.' -not-in-'.$type.'-count';

				if ( $objects[$type] && current_user_can( $objects[$type]->cap->edit_posts ) )
					$text = HTML::tag( 'a', [
						'href'  => WordPress::getPostTypeEditLink( $type, ( 'current' == $scope ? $user_id : 0 ), [ $tax => '-1' ] ),
						'class' => $class,
					], $text );

				else
					$text = HTML::wrap( $text, $class );

				$html.= HTML::tag( $list, [ 'class' => 'warning' ],  $text );
			}
		}

		return $html;
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
		if ( $this->check_hidden_metabox( $box ) )
			return;

		if ( $this->role_can( 'restricted', NULL, FALSE, FALSE ) )
			$box['args']['role'] = $this->get_setting( 'restricted', 'disabled' );

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $box['args'] );
		echo '</div>';
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports' );
	}

	public function reports_sub( $uri, $sub )
	{
		$terms = Taxonomy::getTerms( $this->constant( 'audit_tax' ), FALSE, TRUE, 'slug', [ 'hide_empty' => TRUE ] );

		if ( empty( $terms ) )
			return HTML::desc( _x( 'No reports available!', 'Modules: Audit', GEDITORIAL_TEXTDOMAIN ), TRUE, '-empty' );

		$args = $this->settings_form_req( [
			'user_id' => '0',
		], 'reports' );

		$this->settings_form_before( $uri, $sub, 'bulk', 'reports', FALSE );

			HTML::h3( _x( 'Audit Reports', 'Modules: Audit', GEDITORIAL_TEXTDOMAIN ) );

			echo '<table class="form-table">';

			echo '<tr><th scope="row">'._x( 'By User', 'Modules: Audit', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			$this->do_settings_field( [
				'type'         => 'user',
				'field'        => 'user_id',
				'none_title'   => _x( 'All Users', 'Modules: Audit', GEDITORIAL_TEXTDOMAIN ),
				'none_value'   => '0',
				'default'      => $args['user_id'],
				'option_group' => 'reports',
				'cap'          => 'read',
			] );

			echo '&nbsp;';

			Settings::submitButton( 'user_stats',
				_x( 'Apply Filter', 'Modules: Audit: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

			// FIXME: style this!
			if ( $summary = $this->get_summary( $this->posttypes(), $terms, ( $args['user_id'] ? 'current' : 'all' ), $args['user_id'] ) )
				echo '<div><ul>'.$summary.'</ul></div>';

			echo '</td></tr>';
			echo '</table>';

		$this->settings_form_after( $uri, $sub );
	}
}
