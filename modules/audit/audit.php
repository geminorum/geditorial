<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
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
					'description' => _x( 'Roles that can Manage, Edit and Delete Audit Attributes. Though Administrators have it all!', 'Modules: Audit: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
				],
				[
					'field'       => 'assign_roles',
					'type'        => 'checkbox',
					'title'       => _x( 'Assign Roles', 'Modules: Audit: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles that can Assign Audit Attributes. Though Administrators have it all!', 'Modules: Audit: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
				],
				[
					'field'       => 'reports_roles',
					'type'        => 'checkbox',
					'title'       => _x( 'Reports Roles', 'Modules: Audit: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles that can see Audit Attributes Reports. Though Administrators have it all!', 'Modules: Audit: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
				],
			],
			'_general' => [
				'dashboard_widgets',
				'summary_scope',
				'count_not',
				'admin_restrict',
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
		return [
			'misc' => [
				'menu_name'           => _x( 'Audit', 'Modules: Audit: Audit Attributes Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
				'tweaks_column_title' => _x( 'Audit Attributes', 'Modules: Audit: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'show_option_all'     => _x( 'Audit', 'Modules: Audit: Show Option All', GEDITORIAL_TEXTDOMAIN ),
				'show_option_none'    => _x( '(Not audited)', 'Modules: Audit: Show Option All', GEDITORIAL_TEXTDOMAIN ),
			],
			'noops' => [
				'audit_tax' => _nx_noop( 'Audit Attribute', 'Audit Attributes', 'Modules: Audit: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
			'terms' => [
				'audit_tax' => [
					'audited'      => _x( 'Audited', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'outdated'     => _x( 'Outdated', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'redundant'    => _x( 'Redundant', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'review-seo'   => _x( 'Review SEO', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'review-style' => _x( 'Review Style', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'trivial'      => _x( 'Trivial', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				],
			],
		];
	}

	public function before_settings( $page = NULL )
	{
		if ( isset( $_POST['install_def_audit_tax'] ) )
			$this->insert_default_terms( 'audit_tax' );
	}

	public function default_buttons( $page = NULL )
	{
		parent::default_buttons( $page );
		$this->register_button( 'install_def_audit_tax', _x( 'Install Default Attributes', 'Modules: Audit: Setting Button', GEDITORIAL_TEXTDOMAIN ) );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'audit_tax', [
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
		], NULL, [
			'manage_terms' => 'manage_audit_tax',
			'edit_terms'   => 'edit_audit_tax',
			'delete_terms' => 'delete_audit_tax',
			'assign_terms' => 'assign_audit_tax',
		] );

		$this->filter( 'map_meta_cap', 4 );
	}

	// @REF: https://make.wordpress.org/core/?p=20496
	public function map_meta_cap( $caps, $cap, $user_id, $args )
	{
		switch ( $cap ) {

			case 'manage_audit_tax':
			case 'edit_audit_tax':
			case 'delete_audit_tax':
				return $this->audit_can( 'manage', $user_id ) ? [ 'read' ] : [ 'do_not_allow' ];
			break;

			case 'assign_audit_tax':
				return $this->audit_can( 'assign', $user_id ) ? [ 'read' ] : [ 'do_not_allow' ];
		}

		return $caps;
	}

	private function audit_can( $what = 'assign', $user_id = NULL )
	{
		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		if ( ! $user_id )
			return FALSE;

		$roles = array_merge( $this->get_setting( $what.'_roles', [] ), [ 'administrator' ] );

		if ( User::hasRole( $roles, $user_id ) )
			return TRUE;

		return User::isSuperAdmin( $user_id );
	}

	// Override
	public function cuc( $context = 'settings', $fallback = '' )
	{
		return 'reports' == $context ? $this->audit_can( 'reports' ) : parent::cuc( $context, $fallback );
	}

	// FIXME: no need / instead top level with ajax change option
	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() || ! is_singular( $this->post_types() ) )
			return;

		if ( ! $this->audit_can() )
			return;

		$nodes[] = [
			'id'     => $this->classs(),
			'title'  => _x( 'Audit Attributes', 'Modules: Audit: Adminbar', GEDITORIAL_TEXTDOMAIN ),
			'parent' => $parent,
			'href'   => Settings::subURL( $this->key, 'reports' ),
		];

		$terms = Taxonomy::getTerms( $this->constant( 'audit_tax' ), NULL, TRUE );

		foreach ( $terms as $term )
			$nodes[] = [
				'id'     => $this->classs( 'attribute', $term->term_id ),
				'title'  => sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ),
				'parent' => $this->classs(),
				'href'   => get_term_link( $term ),
			];
	}

	public function current_screen( $screen )
	{
		if ( 'dashboard' == $screen->base ) {

			if ( $this->get_setting( 'dashboard_widgets', FALSE ) && $this->audit_can() )
				$this->action( 'activity_box_end', 0, 9 );

		} else if ( in_array( $screen->post_type, $this->post_types() ) ) {

			if ( 'edit' == $screen->base ) {

				if ( $this->get_setting( 'admin_restrict', FALSE ) && $this->audit_can() ) {
					$this->action( 'restrict_manage_posts', 2, 20 );
					$this->filter( 'parse_query' );
				}

				$this->_tweaks_taxonomy();
			}
		}
	}

	public function activity_box_end()
	{
		$user_id = 'all' == $this->get_setting( 'summary_scope', 'all' ) ? 0 : get_current_user_id();

		$key = $this->hash( 'activityboxend', $user_id );

		if ( WordPress::isFlush() )
			delete_transient( $key );

		if ( FALSE === ( $html = get_transient( $key ) ) ) {

			$html  = '';
			$terms = Taxonomy::getTerms( $this->constant( 'audit_tax' ), FALSE, TRUE, 'slug', [ 'hide_empty' => TRUE ] );

			if ( count( $terms ) ) {

				if ( $summary = $this->get_summary( $this->post_types(), $terms, $user_id ) ) {

					$html .= '<div class="geditorial-admin-wrap -audit"><h3>';

					if ( $user_id )
						$html .= _x( 'Your Audit Summary', 'Modules: Audit: Activity Box End', GEDITORIAL_TEXTDOMAIN );
					else
						$html .= _x( 'Editorial Audit Summary', 'Modules: Audit: Activity Box End', GEDITORIAL_TEXTDOMAIN );

					$html .= ' '.HTML::tag( 'a', [
						'href'  => add_query_arg( 'flush', '' ),
						'title' => _x( 'Click to refresh the summary', 'Modules: Audit: Activity Box End', GEDITORIAL_TEXTDOMAIN ),
						'class' => '-action -flush page-title-action',
					], _x( 'Refresh', 'Modules: Audit: Activity Box End', GEDITORIAL_TEXTDOMAIN ) );

					$html .= '</h3><ul>'.$summary.'</ul></div>';

					$html = Text::minifyHTML( $html );

					set_transient( $key, $html, 12 * HOUR_IN_SECONDS );
				}
			}
		}

		echo $html;
	}

	private function get_summary( $posttypes, $terms, $user_id = 0, $list = 'li' )
	{
		$html   = '';
		$tax    = $this->constant( 'audit_tax' );
		$all    = PostType::get( 3 );
		$counts = Database::countPostsByTaxonomy( $terms, $posttypes, $user_id );

		$objects = [];

		foreach ( $counts as $term => $posts ) {

			$name = sanitize_term_field( 'name', $terms[$term]->name, $terms[$term]->term_id, $terms[$term]->taxonomy, 'display' );

			foreach ( $posts as $type => $count ) {

				if ( ! $count )
					continue;

				$text = vsprintf( '%3$s %1$s (%2$s)', [
					Helper::noopedCount( $count, $all[$type] ),
					Helper::trimChars( $name, 35 ),
					Number::format( $count ),
				] );

				if ( empty( $objects[$type] ) )
					$objects[$type] = get_post_type_object( $type );

				$class = 'geditorial-glance-item -audit -term -taxonomy-'.$tax.' -term-'.$term.'-'.$type.'-count';

				if ( $objects[$type] && current_user_can( $objects[$type]->cap->edit_posts ) )
					$text = HTML::tag( 'a', [
						'href'  => WordPress::getPostTypeEditLink( $type, $user_id, [ $tax => $term ] ),
						'class' => $class,
					], $text );

				else
					$text = HTML::tag( 'div', [ 'class' => $class ], $text );

				$html .= HTML::tag( $list, $text );
			}
		}

		if ( $this->get_setting( 'count_not', FALSE ) ) {

			$not = Database::countPostsByNotTaxonomy( $tax, $posttypes, $user_id );

			foreach ( $not as $type => $count ) {

				if ( ! $count )
					continue;

				$text = vsprintf( '%3$s %1$s %2$s', [
					Helper::noopedCount( $count, $all[$type] ),
					$this->get_string( 'show_option_none', 'audit_tax', 'misc' ),
					Number::format( $count ),
				] );

				if ( empty( $objects[$type] ) )
					$objects[$type] = get_post_type_object( $type );

				$class = 'geditorial-glance-item -audit -not-in -taxonomy-'.$tax.' -not-in-'.$type.'-count';

				if ( $objects[$type] && current_user_can( $objects[$type]->cap->edit_posts ) )
					$text = HTML::tag( 'a', [
						'href'  => WordPress::getPostTypeEditLink( $type, $user_id, [ $tax => '-1' ] ),
						'class' => $class,
					], $text );

				else
					$text = HTML::tag( 'div', [ 'class' => $class ], $text );

				$html .= HTML::tag( $list, $text );
			}
		}

		return $html;
	}

	public function restrict_manage_posts( $post_type, $which )
	{
		$this->do_restrict_manage_posts_taxes( 'audit_tax' );
	}

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query, 'audit_tax' );
	}

	public function meta_box_cb_audit_tax( $post, $box )
	{
		MetaBox::checklistTerms( $post, $box );
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports' );
	}

	public function reports_sub( $uri, $sub )
	{
		$terms = Taxonomy::getTerms( $this->constant( 'audit_tax' ), FALSE, TRUE, 'slug', [ 'hide_empty' => TRUE ] );

		if ( ! count( $terms ) )
			return HTML::warning( _x( 'No Audit Terms', 'Modules: Audit', GEDITORIAL_TEXTDOMAIN ), FALSE );

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
			if ( $summary = $this->get_summary( $this->post_types(), $terms, $args['user_id'] ) )
				echo '<div><ul>'.$summary.'</ul></div>';

			echo '</td></tr>';
			echo '</table>';
		$this->settings_form_after( $uri, $sub );
	}
}
