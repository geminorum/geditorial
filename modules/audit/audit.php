<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialAudit extends gEditorialModuleCore
{

	protected $caps = array(
		'default' => 'edit_others_posts',
		'tools'   => 'edit_others_posts',
		'reports' => 'edit_others_posts',
	);

	public static function module()
	{
		return array(
			'name'  => 'audit',
			'title' => _x( 'Audit', 'Modules: Audit', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Content Inventory Tools', 'Modules: Audit', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'visibility',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				'dashboard_widgets',
				'summary_scope',
				'count_not',
				'admin_restrict',
			),
			'posttypes_option' => 'posttypes_option',
		);
	}

	protected function get_global_constants()
	{
		return array(
			'audit_tax' => 'audit_attribute',
		);
	}

	protected function get_module_icons()
	{
		return array(
			'taxonomies' => array(
				'audit_tax' => NULL,
			),
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
				'menu_name'           => _x( 'Audit', 'Modules: Audit: Audit Attributes Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
				'tweaks_column_title' => _x( 'Audit Attributes', 'Modules: Audit: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'show_option_all'     => _x( 'Audit', 'Modules: Audit: Show Option All', GEDITORIAL_TEXTDOMAIN ),
				'show_option_none'    => _x( '(Not audited)', 'Modules: Audit: Show Option All', GEDITORIAL_TEXTDOMAIN ),
			),
			'settings' => array(
				'install_def_audit_tax' => _x( 'Install Default Attributes', 'Modules: Audit: Setting Button', GEDITORIAL_TEXTDOMAIN ),
			),
			'noops' => array(
				'audit_tax' => _nx_noop( 'Audit Attribute', 'Audit Attributes', 'Modules: Audit: Noop', GEDITORIAL_TEXTDOMAIN ),
			),
			'terms' => array(
				'audit_tax' => array(
					'audited'      => _x( 'Audited', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'outdated'     => _x( 'Outdated', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'redundant'    => _x( 'Redundant', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'review-seo'   => _x( 'Review SEO', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'review-style' => _x( 'Review Style', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'trivial'      => _x( 'Trivial', 'Modules: Audit: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				),
			),
		);
	}

	public function init()
	{
		parent::init();

		// FIXME: add setting option to choose editing role
		if ( $this->cuc( 'default' ) )
			$this->register_taxonomy( 'audit_tax', array(
				'hierarchical'       => TRUE,
				'show_in_quick_edit' => TRUE,
			) );
	}

	public function current_screen( $screen )
	{
		if ( 'dashboard' == $screen->base ) {

			if ( $this->get_setting( 'dashboard_widgets', FALSE ) )
				add_action( 'activity_box_end', array( $this, 'activity_box_end' ), 9 );

		} else if ( in_array( $screen->post_type, $this->post_types() ) ) {

			if ( 'edit' == $screen->base ) {

				if ( $this->get_setting( 'admin_restrict', FALSE ) ) {
					add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ), 20 ,2 );
					add_filter( 'parse_query', array( $this, 'parse_query' ) );
				}

				$this->_tweaks_taxonomy();
			}
		}
	}

	public function activity_box_end()
	{
		$user_id = 'all' == $this->get_setting( 'summary_scope', 'all' ) ? 0 : get_current_user_id();

		$key = $this->hash( 'activityboxend', $user_id );

		if ( gEditorialWordPress::isFlush() )
			delete_transient( $key );

		if ( FALSE === ( $html = get_transient( $key ) ) ) {

			$html  = '';
			$terms = gEditorialWPTaxonomy::getTerms( $this->constant( 'audit_tax' ), FALSE, TRUE, 'slug', array( 'hide_empty' => TRUE ) );

			if ( count( $terms ) ) {

				if ( $summary = $this->get_summary( $this->post_types(), $terms, $user_id ) ) {

					$html .= '<div class="geditorial-admin-wrap -audit"><h3>';

					if ( $user_id )
						$html .= _x( 'Your Audit Summary', 'Modules: Audit: Activity Box End', GEDITORIAL_TEXTDOMAIN );
					else
						$html .= _x( 'Editorial Audit Summary', 'Modules: Audit: Activity Box End', GEDITORIAL_TEXTDOMAIN );

					$html .= ' '.gEditorialHTML::tag( 'a', array(
						'href'  => add_query_arg( 'flush', '' ),
						'title' => _x( 'Click to refresh the summary', 'Modules: Audit: Activity Box End', GEDITORIAL_TEXTDOMAIN ),
						'class' => '-action -flush page-title-action',
					), _x( 'Refresh', 'Modules: Audit: Activity Box End', GEDITORIAL_TEXTDOMAIN ) );

					$html .= '</h3><ul>'.$summary.'</ul></div>';

					$html = gEditorialCoreText::minifyHTML( $html );

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
		$all    = gEditorialWPPostType::get( 3 );
		$counts = gEditorialWPDatabase::countPostsByTaxonomy( $terms, $posttypes, $user_id );

		$objects = array();

		foreach ( $counts as $term => $posts ) {

			$name = sanitize_term_field( 'name', $terms[$term]->name, $terms[$term]->term_id, $terms[$term]->taxonomy, 'display' );

			foreach ( $posts as $type => $count ) {

				if ( ! $count )
					continue;

				$text = vsprintf( '%3$s %1$s (%2$s)', array(
					gEditorialHelper::noopedCount( $count, $all[$type] ),
					gEditorialHelper::trimChars( $name, 35 ),
					gEditorialNumber::format( $count ),
				) );

				if ( empty( $objects[$type] ) )
					$objects[$type] = get_post_type_object( $type );

				$class = 'geditorial-glance-item -audit -term -taxonomy-'.$tax.' -term-'.$term.'-'.$type.'-count';

				if ( $objects[$type] && current_user_can( $objects[$type]->cap->edit_posts ) )
					$text = gEditorialHTML::tag( 'a', array(
						'href'  => gEditorialWordPress::getPostTypeEditLink( $type, $user_id, array( $tax => $term ) ),
						'class' => $class,
					), $text );

				else
					$text = gEditorialHTML::tag( 'div', array(
						'class' => $class,
					), $text );

				$html .= gEditorialHTML::tag( $list, array(), $text );
			}
		}

		if ( $this->get_setting( 'count_not', FALSE ) ) {

			$not = gEditorialWPDatabase::countPostsByNotTaxonomy( $tax, $posttypes, $user_id );

			foreach ( $not as $type => $count ) {

				if ( ! $count )
					continue;

				$text = vsprintf( '%3$s %1$s %2$s', array(
					gEditorialHelper::noopedCount( $count, $all[$type] ),
					$this->get_string( 'show_option_none', 'audit_tax', 'misc' ),
					gEditorialNumber::format( $count ),
				) );

				if ( empty( $objects[$type] ) )
					$objects[$type] = get_post_type_object( $type );

				$class = 'geditorial-glance-item -audit -not-in -taxonomy-'.$tax.' -not-in-'.$type.'-count';

				if ( $objects[$type] && current_user_can( $objects[$type]->cap->edit_posts ) )
					$text = gEditorialHTML::tag( 'a', array(
						'href'  => gEditorialWordPress::getPostTypeEditLink( $type, $user_id, array( $tax => '-1' ) ),
						'class' => $class,
					), $text );

				else
					$text = gEditorialHTML::tag( 'div', array(
						'class' => $class,
					), $text );

				$html .= gEditorialHTML::tag( $list, $text );
			}
		}

		return $html;
	}

	public function register_settings( $page = NULL )
	{
		if ( ! $this->is_register_settings( $page ) )
			return;

		if ( isset( $_POST['install_def_audit_tax'] ) )
			$this->insert_default_terms( 'audit_tax' );

		parent::register_settings( $page );
		$this->register_button( 'install_def_audit_tax' );
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
		gEditorialMetaBox::checklistTerms( $post, $box );
	}

	public function reports_settings( $sub )
	{
		if ( ! $this->cuc( 'reports' ) )
			return;

		if ( $this->module->name == $sub )
			add_action( 'geditorial_reports_sub_'.$sub, array( $this, 'reports_sub' ), 10, 2 );

		add_filter( 'geditorial_reports_subs', array( $this, 'append_sub' ), 10, 2 );
	}

	public function reports_sub( $uri, $sub )
	{
		$terms = gEditorialWPTaxonomy::getTerms( $this->constant( 'audit_tax' ), FALSE, TRUE, 'slug', array( 'hide_empty' => TRUE ) );

		if ( ! count( $terms ) )
			return gEditorialHTML::warning( _x( 'No Audit Terms', 'Modules: Audit', GEDITORIAL_TEXTDOMAIN ), TRUE );

		$args = $this->settings_form_req( [
			'user_id' => '0',
		], 'reports' );

		$this->settings_form_before( $uri, $sub, 'bulk', 'reports', FALSE );

			gEditorialHTML::h3( _x( 'Audit Reports', 'Modules: Audit', GEDITORIAL_TEXTDOMAIN ) );

			echo '<table class="form-table">';

			echo '<tr><th scope="row">'._x( 'By User', 'Modules: Audit', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			$this->do_settings_field( array(
				'type'         => 'user',
				'field'        => 'user_id',
				'none_title'   => _x( 'All Users', 'Modules: Audit', GEDITORIAL_TEXTDOMAIN ),
				'default'      => $args['user_id'],
				'option_group' => 'reports',
			) );

			echo '&nbsp;';

			gEditorialSettingsCore::submitButton( 'user_stats',
				_x( 'Apply Filter', 'Modules: Audit: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

			// FIXME: style this!
			if ( $summary = $this->get_summary( $this->post_types(), $terms, $args['user_id'] ) )
				echo '<div><ul>'.$summary.'</ul></div>';

			echo '</td></tr>';
			echo '</table>';
		$this->settings_form_after( $uri, $sub );
	}
}
